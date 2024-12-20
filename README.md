# WooCommerce Variation Table

A WordPress plugin that enhances the display of WooCommerce product variations by presenting them in a customizable, sortable table format.

## Description

WooCommerce Variation Table transforms the standard WooCommerce variation dropdown into a user-friendly table view. This makes it easier for customers to compare different product variations and make informed purchase decisions.

### Features

- **Customizable Table Display**: Choose which columns to show/hide and their order
  - Product Attributes
  - Price
  - Stock Status
  - Stock Quantity
  - SKU
  - Dimensions
  - Weight
  - Description
  - Product Image
  - Add to Cart Button
  - Custom Fields

- **Flexible Placement Options**:
  - In a product tab
  - Below product description
  - Above product description
  - In a modal popup
  - At the start of product description
  - At the end of product description
  - No automatic placement (use shortcode or block)

- **Table Features**:
  - Sortable columns
  - Preview mode with "Show More" button
  - Separate or combined attribute columns
  - Hide unpurchasable variations
  - Customizable table title

- **Integration Options**:
  - Gutenberg block
  - Shortcode `[variation_table]`
  - Template function `display_variation_table()`

## Installation

1. Upload the plugin files to `/wp-content/plugins/wc-variation-table`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce → Settings → Products → Variation Table to configure the plugin

## Configuration

### General Settings

- **Enable Variation Table**: Turn the table display on/off
- **Hide Unpurchasable Variations**: Hide variations that are out of stock or unavailable
- **Separate Attribute Columns**: Display each attribute in its own column

### Table Columns

Manage which columns appear in the table and their order:
1. Drag and drop to reorder columns
2. Toggle columns on/off using the switches
3. Add or remove system columns as needed
4. Add custom fields for additional variation data

### Placement Options

Choose where the variation table appears:
- **Tab**: Adds a new product tab (configurable title)
- **Below/Above**: Places table relative to product description
- **Modal**: Shows table in a popup (configurable trigger)
- **Description**: Inserts at start or end of description
- **Manual**: Use shortcode or block for custom placement

### Preview Settings

- Enable/disable preview mode
- Set number of rows to show initially
- Customize "Show More" button text

## Usage

### Shortcode

```php
[variation_table]
```

### PHP Template Function

```php
if (function_exists('display_variation_table')) {
    display_variation_table();
}
```

### Gutenberg Block

Search for "Variation Table" in the block inserter.

## Custom Development

### Filters

- `wcvt_table_columns`: Modify available table columns
- `wcvt_variation_data`: Filter variation data before display
- `wcvt_table_classes`: Add custom CSS classes to the table

### Actions

- `wcvt_before_table`: Action before table output
- `wcvt_after_table`: Action after table output
- `wcvt_before_variation_row`: Action before each variation row
- `wcvt_after_variation_row`: Action after each variation row

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Support

For bug reports and feature requests, please use the [GitHub repository](https://github.com/your-username/wc-variation-table).

I do not offer any support guarantees for this plugin, but feel free to send a pull request if you have a fix for a bug.

## License

This plugin is licensed under the GNU General Public License v3.0. 