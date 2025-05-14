# LLMs.txt Generator

Automatically generate a `llms.txt` file to help large language models (LLMs) index and understand your WordPress site content more effectively.

This plugin allows site owners to define which post types should be included, set how often the file is regenerated, and optionally trigger manual updates. The output is a clean, structured `.txt` file listing your site's public content in a machine-friendly format.

## 🚀 Features

- ✅ Automatically generates a `llms.txt` file at your site root
- ✅ Select which post types to include (posts, pages, CPTs, etc.)
- ✅ Schedule automatic regeneration (hourly, twice daily, daily)
- ✅ Manual "Regenerate Now" button
- ✅ Fully translatable and escape-safe
- ✅ Works with both built-in and custom post types

## 📂 File Location

The plugin writes `llms.txt` to the root of your WordPress installation:
    
```
/public_html/llms.txt
```

This file can then be accessed at:
    
```
https://yourdomain.com/llms.txt
```

## ⚙️ Settings

Navigate to **Settings > LLMs.txt Generator** to configure:
- ✅ Which post types to include
- 🕒 How often to regenerate the file
- 🔁 Option to regenerate immediately with a button

## 📆 Installation

1. Upload the plugin folder to `/wp-content/plugins/llms-txt-generator/`

2. Activate the plugin via **Plugins > Installed Plugins**

3. Go to **Settings > LLMs.txt Generator** to configure it

## 🧠 Use Case

Many modern language models rely on text-based crawling for data. A `llms.txt` file gives them a clean, structured source of your site content, bypassing messy HTML layouts and helping improve accuracy when your site is referenced in LLM output.

## 🛠 Developers

- Uses WP cron to handle scheduled regeneration
- Fully escaped and internationalization-ready (`textdomain: llms-txt-generator`)
- Post types retrieved via `get_post_types( [ 'public' => true ] )`
- Output is Markdown-formatted for LLM readability

## 🧼 Uninstall Behavior

- Clears scheduled cron events on deactivation
- Plugin settings are retained unless manually deleted

## 📜 License

Licensed under the GPLv2 or later.

## 🤛 Support

For issues, ideas, or contributions, open an issue or pull request on the plugin repository.