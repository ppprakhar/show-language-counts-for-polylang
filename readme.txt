=== Show Language Counts for Polylang ===
Contributors: ppprakhar
Donate link: https://github.com/ppprakhar
Tags: polylang, multilingual, language, admin tools, post count, product count, woocommerce
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds language-wise post, product and custom-post-type counts for all Polylang-enabled content types in the WordPress admin. Does not modify the default WordPress counters. Unaffiliated helper for Polylang users.

== Description ==

A lightweight helper plugin for **Polylang** users.  
This plugin shows **per-language counts** for posts, products, pages, and any custom post types that you have enabled in Polylang.

It **does not change** WordPress’s built-in counters (`All`, `Published`, `Draft`).  
Instead, it adds a clean **info box** above the list table showing counts for each language.

Works for:

- Blog posts
- Pages
- WooCommerce products
- Elementor templates
- Any post type activated under  
  *Languages → Settings → Custom post types and Taxonomies*

### ✨ Features

- Automatically detects all Polylang-enabled post types
- Shows **published count** and **total count** per language
- Works on all `edit.php` screens (Posts, Products, Templates, etc.)
- No database changes, safe & lightweight
- Compatible with:
  - Polylang (free)
  - Polylang Pro
  - WooCommerce
  - Elementor
  - WordPress Multisite

### 📝 Example Output

A small info box appears above the list:

| Language | Published | All Statuses |
|---------|-----------|--------------|
| Svenska (sv) | 350 | 360 |
| English (en) | 280 | 290 |

### 🎯 Why You Need This

Polylang does not display language-wise counts in the admin.  
Store owners and content editors often need to know:

- How many products are translated?
- How many posts exist per language?
- How many items are missing in one language?

This plugin solves that without modifying WP core behavior.

Polylang is a registered trademark of WP SYNTEX.  
This plugin is an independent addon and is not affiliated with or endorsed by WP SYNTEX or the Polylang plugin.

== Installation ==

1. Install Polylang (required)
2. Upload the plugin folder to:
   `/wp-content/plugins/show-language-counts-for-polylang`
3. Activate **Show Language Counts for Polylang** via *Plugins → Installed Plugins*
4. Go to any post type list (Posts, Products, etc.)
5. View your new language-wise post counts

== Frequently Asked Questions ==

= Does this modify WordPress’s default post counts? =  
No. It **only adds** a new info box. Native WordPress counts remain unchanged.

= Does this affect front-end translations? =  
Not at all. This plugin is 100% admin-side only.

= Does it work with WooCommerce products? =  
Yes. As long as products are enabled in Polylang settings.

= Does it support multisite? =  
Yes, it works in each site of a network.

= Do I need Polylang Pro? =  
No, the free version works fine.

== Screenshots ==

1. Admin info box showing per-language counts above the product list.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
