# Show Language Counts for Polylang

A tiny helper plugin for WordPress + Polylang (unofficial helper).

SEO: A lightweight WordPress plugin that adds Polylang language-wise post counts for Posts, Products, Pages, and custom post types. Supports WooCommerce and multisite.

It shows **per-language post counts** for all post types that are enabled in Polylang's “Custom post types and Taxonomies” settings, directly on the admin list screens (Posts, Products, Templates, etc.).

The plugin **does not modify** the default WordPress list table counters (`All (1176)`, `Published (800)`, `Draft (2)` …).  
Instead, it appends a compact, non-clickable “Languages: sv (5) | en (1)” item in the counters row, so you can quickly see how many items exist in each language.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- [Polylang](https://wordpress.org/plugins/polylang/) (free or pro)

## Features

- Works for **all post types** that you have checked under  
  **Languages → Settings → Custom post types and Taxonomies**.
- Shows, per language:
  - Total number of posts (all statuses)
  - Number of **published** posts
- Does not touch any core / WooCommerce / Polylang code.
- Safe to use with multisite and WooCommerce.

## Usage

1. Ensure your post type is enabled for translation in **Languages → Settings → Custom post types and Taxonomies**.
2. Open the admin list table for that post type (e.g., Posts, Pages, Products).
3. The language counts appear next to the standard counters as “Languages: xx (published) | yy (published)”.

Keywords: wordpress, polylang, multilingual, post count, product count, admin tools, translation

## Installation

1. Download or clone this repository into:

   ```text
   wp-content/plugins/show-language-counts-for-polylang
