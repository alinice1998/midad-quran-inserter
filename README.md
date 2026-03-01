# Midad Quran Inserter

A Gutenberg block plugin for WordPress that allows users to easily search and insert Quranic verses in beautiful Uthmani script via the Kalimat API.

## Features

*   **Gutenberg Native:** Fully integrated as a block in the WordPress editor.
*   **Search Flexibility:** Search by an Arabic word, Surah name, or a combination like `Surah:Ayah` (e.g., `2:255`).
*   **Uthmani Script:** Provides clean, correctly formatted Uthmani text.
*   **Kalimat API Powered:** Leverages the robust Kalimat API for blazingly fast and accurate search results.
*   **Easy Insertion:** Insert verses directly into your content with a single click.
*   **Translation Ready:** Fully localized, currently including English and Arabic translations.

## Requirements

*   WordPress 5.8 or higher
*   PHP 7.4 or higher
*   A free API key from [Kalimat.dev](https://kalimat.dev/)

## Installation

1.  Download the plugin ZIP file relative to the `midad-quran-inserter` folder.
2.  Upload the `midad-quran-inserter` directory to the `/wp-content/plugins/` directory.
3.  Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

1.  Navigate to **Settings -> Midad Quran** in your WordPress dashboard.
2.  Enter your `X-Api-Key` obtained from [Kalimat.dev](https://kalimat.dev/).
3.  Save changes.

## Usage

1.  Open any page or post in the Gutenberg editor.
2.  Add a new block and search for **"Midad"** or **"Quran"**.
3.  Select the **Midad Quran Inserter** block.
4.  Enter your search query (word, Surah name, or Surah:Ayah format).
5.  Click the **Search** button.
6.  Browse the results and click **Insert** to add a verse to your content.


## License

This plugin is licensed under the GPL-2.0+ License.
