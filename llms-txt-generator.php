<?php
/**
 * Plugin Name: LLMs.txt Generator
 * Plugin URI:  https://github.com/robertdevore/llms-txt-generator/
 * Description: Dynamically generates a llms.txt file for LLM-friendly site indexing. Choose which post types to include and how often to regenerate.
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: llms-txt-generator
 * Domain Path: /languages
 * Update URI: https://github.com/robertdevore/llms-txt-generator/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    wp_die();
}

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/llms-txt-generator/',
	__FILE__,
	'llms-txt-generator'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}
use RobertDevore\WPComCheck\WPComPluginHandler;
new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

define( 'LLMS_TXT_PATH', ABSPATH . 'llms.txt' );
define( 'LLMS_TXT_OPTION', 'llms_txt_generator_options' );
define( 'LLMS_TXT_CRON_HOOK', 'llms_txt_cron_event' );

/**
 * Registers the LLMs.txt Generator settings page under the Settings menu.
 *
 * This function hooks into the WordPress 'admin_menu' action and adds a page
 * where site administrators can configure which post types to include in the
 * llms.txt file and how often it should be regenerated.
 *
 * @since  1.0.0
 * @return void
 */
function llms_txt_register_settings_page() {
    add_options_page(
        esc_html__( 'LLMs.txt Generator', 'llms-txt-generator' ),
        esc_html__( 'LLMs.txt Generator', 'llms-txt-generator' ),
        'manage_options',
        'llms-txt-generator',
        'llms_txt_settings_page'
    );
}
add_action( 'admin_menu', 'llms_txt_register_settings_page' );

/**
 * Registers the llms.txt plugin settings.
 *
 * This function hooks into the WordPress 'admin_init' action and registers
 * the settings group and option used to store plugin configuration such as
 * selected post types and cron interval.
 *
 * @since  1.0.0
 * @return void
 */
function llms_txt_register_settings() {
    register_setting( 'llms_txt_settings', LLMS_TXT_OPTION );
}
add_action( 'admin_init', 'llms_txt_register_settings' );

/**
 * Outputs the settings page for the LLMs.txt Generator plugin.
 *
 * This page allows administrators to:
 * - Select which post types to include in the llms.txt file
 * - Set the automatic regeneration interval
 * - Manually regenerate the llms.txt file via a button
 *
 * Uses WordPress Settings API functions to handle form rendering and saving.
 * Triggering manual regeneration will immediately regenerate the file and
 * display a success message.
 *
 * @since  1.0.0
 * @return void
 */
function llms_txt_settings_page() {
    $options    = get_option( LLMS_TXT_OPTION, [] );
    $post_types = get_post_types( [ 'public' => true ], 'objects' );
    $selected   = isset( $options['post_types'] ) ? $options['post_types'] : [];
    $interval   = isset( $options['interval'] ) ? esc_attr( $options['interval'] ) : 'daily';

    // Handle manual regeneration.
    if ( isset( $_POST['llms_regenerate_now'] ) && check_admin_referer( 'llms_regenerate_now_action' ) ) {
        llms_txt_generate_file();
        echo '<div class="updated notice"><p><strong>' . esc_html__( 'llms.txt regenerated successfully.', 'llms-txt-generator' ) . '</strong></p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'LLMs.txt Generator', 'llms-txt-generator' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'llms_txt_settings' ); ?>

            <h2><?php esc_html_e( 'Select Post Types to Include', 'llms-txt-generator' ); ?></h2>
            <fieldset>
                <?php foreach ( $post_types as $type => $obj ) : ?>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( LLMS_TXT_OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $selected, true ) ); ?>>
                        <?php esc_html_e( $obj->labels->name ); ?> (<?php echo esc_html( $type ); ?>)
                    </label><br>
                <?php endforeach; ?>
            </fieldset>

            <h2><?php esc_html_e( 'Regenerate Frequency', 'llms-txt-generator' ); ?></h2>
            <select name="<?php echo esc_attr( LLMS_TXT_OPTION ); ?>[interval]">
                <option value="hourly" <?php selected( $interval, 'hourly' ); ?>>
                    <?php esc_html_e( 'Hourly', 'llms-txt-generator' ); ?>
                </option>
                <option value="twicedaily" <?php selected( $interval, 'twicedaily' ); ?>>
                    <?php esc_html_e( 'Twice Daily', 'llms-txt-generator' ); ?>
                </option>
                <option value="daily" <?php selected( $interval, 'daily' ); ?>>
                    <?php esc_html_e( 'Daily', 'llms-txt-generator' ); ?>
                </option>
            </select>

            <?php submit_button( esc_html__( 'Save Settings', 'llms-txt-generator' ) ); ?>
        </form>

        <form method="post" style="margin-top:2em;">
            <?php wp_nonce_field( 'llms_regenerate_now_action' ); ?>
            <input type="submit" name="llms_regenerate_now" class="button button-primary" value="<?php esc_attr_e( 'Regenerate Now', 'llms-txt-generator' ); ?>">
        </form>
    </div>
    <?php
}

/**
 * Schedules the llms.txt regeneration event based on plugin settings.
 *
 * This function hooks into the 'admin_init' action and checks if a cron event
 * is already scheduled. If not, it schedules the event to run at the selected
 * interval (hourly, twice daily, or daily).
 *
 * The selected interval is retrieved from plugin settings. The cron job will
 * trigger the LLMS_TXT_CRON_HOOK, which calls the file generation function.
 *
 * @since  1.0.0
 * @return void
 */
function llms_txt_schedule_cron_event() {
    if ( ! wp_next_scheduled( LLMS_TXT_CRON_HOOK ) ) {
        $options  = get_option( LLMS_TXT_OPTION, [] );
        $interval = isset( $options['interval'] ) ? $options['interval'] : 'daily';
        wp_schedule_event( time(), $interval, LLMS_TXT_CRON_HOOK );
    }
}
add_action( 'admin_init', 'llms_txt_schedule_cron_event' );

/**
 * Generates the llms.txt file with structured post links for LLM indexing.
 *
 * This function fetches all published posts from the selected post types,
 * builds a structured list with their titles and permalinks, and writes the
 * content to a plain text file at the root of the site (`llms.txt`).
 *
 * The file includes:
 * - Site title and description
 * - A section for each selected post type
 * - A Markdown-formatted list of links with post IDs
 *
 * Triggered via both cron and the manual “Regenerate Now” button.
 *
 * @since  1.0.0
 * @return void
 */
function llms_txt_generate_file() {
    $options = get_option( LLMS_TXT_OPTION, [] );
    $types   = isset( $options['post_types'] ) ? (array) $options['post_types'] : [];
    $lines   = [];

    $site_name = get_bloginfo( 'name' );
    $site_desc = get_bloginfo( 'description' );
    $site_url  = home_url();

    $lines[] = '# ' . $site_name;
    $lines[] = '> ' . $site_desc;
    $lines[] = "\n" . esc_html__( 'This site contains structured content formatted for LLM-friendly consumption.', 'llms-txt-generator' ) . "\n";

    foreach ( $types as $type ) {
        $obj = get_post_type_object( $type );
        if ( ! $obj ) {
            continue;
        }

        $posts = get_posts( [
            'post_type'      => $type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        if ( empty( $posts ) ) {
            continue;
        }

        $lines[] = "\n## " . $obj->labels->name;
        foreach ( $posts as $post ) {
            $title   = get_the_title( $post );
            $url     = get_permalink( $post );
            $lines[] = '- [' . $title . '](' . $url . '): ID ' . $post->ID;
        }
    }

    file_put_contents( LLMS_TXT_PATH, implode( "\n", $lines ) );
}
add_action( LLMS_TXT_CRON_HOOK, 'llms_txt_generate_file' );

/**
 * Clears the scheduled cron event on plugin deactivation.
 *
 * This ensures that the llms.txt regeneration task does not continue running
 * after the plugin has been deactivated. It removes the scheduled event
 * associated with the LLMS_TXT_CRON_HOOK constant.
 *
 * @since  1.0.0
 * @return void
 */
function llms_txt_clear_cron_on_deactivation() {
    wp_clear_scheduled_hook( LLMS_TXT_CRON_HOOK );
}
register_deactivation_hook( __FILE__, 'llms_txt_clear_cron_on_deactivation' );
