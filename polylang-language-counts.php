<?php
/**
 * Plugin Name:       Polylang Language Counts
 * Description:       Shows per-language post counts for all Polylang-enabled post types in the admin list screens without changing the default WordPress counters.
 * Version:           1.0.0
 * Author:            Prakhar Kant Tripathi
 * Text Domain:       polylang-language-counts
 * Requires at least: 5.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set up the counts when loading an edit.php screen.
 */
add_action( 'load-edit.php', 'pllc_setup_language_counts_for_screen' );

function pllc_setup_language_counts_for_screen() {
	// Polylang not active? Do nothing.
	if ( ! function_exists( 'pll_the_languages' ) || ! function_exists( 'pll_count_posts' ) || ! function_exists( 'PLL' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || empty( $screen->post_type ) ) {
		return;
	}

	$post_type     = $screen->post_type;
	if ( ! pllc_is_translated_post_type( $post_type ) ) {
		return;
	}

	$hook = 'views_edit-' . $post_type;
	add_filter(
		$hook,
		function ( $views ) use ( $post_type ) {
			$counts = pllc_get_language_counts_for_post_type( $post_type );
			if ( empty( $counts ) ) {
				return $views;
			}

			return pllc_inject_language_counts_into_views( $views, $counts );
		}
	);
}

/**
 * Get per-language counts for a given post type.
 *
 * @param string $post_type
 *
 * @return array[]
 */
function pllc_get_language_counts_for_post_type( $post_type ) {
	$languages = pllc_get_languages();
	if ( empty( $languages ) || ! is_array( $languages ) ) {
		return array();
	}

	$counts = array();

	foreach ( $languages as $lang ) {
		$slug = '';
		$name = '';

		if ( is_object( $lang ) ) {
			$slug = isset( $lang->slug ) ? $lang->slug : '';
			$name = isset( $lang->name ) ? $lang->name : $slug;
		} elseif ( is_array( $lang ) ) {
			$slug = isset( $lang['slug'] ) ? $lang['slug'] : '';
			$name = isset( $lang['name'] ) ? $lang['name'] : $slug;
		} else {
			$slug = (string) $lang;
			$name = $slug;
		}

		if ( '' === $slug ) {
			continue;
		}

		// Count all statuses for this language/post type.
		$count_all = pll_count_posts(
			$slug,
			array(
				'post_type' => $post_type,
			)
		);

		// Count only published posts for this language/post type.
		$count_published = pll_count_posts(
			$slug,
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
			)
		);

		$counts[] = array(
			'slug'           => $slug,
			'name'           => $name,
			'count_all'      => (int) $count_all,
			'count_published'=> (int) $count_published,
		);
	}

	return $counts;
}

/**
 * Get languages in admin (pll_the_languages() is frontend-only).
 *
 * @return array Array of PLL_Language objects or language slugs.
 */
function pllc_get_languages() {
	// Preferred: use the model to get PLL_Language objects.
	if ( function_exists( 'PLL' ) && is_object( PLL()->model ) ) {
		$languages = PLL()->model->get_languages_list(
			array(
				'hide_empty'   => false,
				'hide_default' => false,
			)
		);

		if ( is_array( $languages ) ) {
			return $languages;
		}
	}

	// Fallback for older Polylang versions: just grab slugs.
	if ( function_exists( 'pll_languages_list' ) ) {
		$slugs = pll_languages_list( array( 'hide_empty' => false, 'hide_default' => false ) );
		if ( is_array( $slugs ) ) {
			return array_map(
				function ( $slug ) {
					return array(
						'slug' => $slug,
						'name' => strtoupper( $slug ),
					);
				},
				$slugs
			);
		}
	}

	return array();
}

/**
 * Check if a post type is translated in Polylang settings.
 *
 * @param string $post_type
 *
 * @return bool
 */
function pllc_is_translated_post_type( $post_type ) {
	$translated = pllc_get_translated_post_types();
	return ! empty( $translated ) && in_array( $post_type, $translated, true );
}

/**
 * Return all post types that are translated in Polylang settings.
 *
 * @return string[]
 */
function pllc_get_translated_post_types() {
	// Preferred API for Polylang 3.7+.
	if ( function_exists( 'PLL' ) && is_object( PLL()->model ) ) {
		$post_types = PLL()->model->get_translated_post_types();
		if ( is_array( $post_types ) && ! empty( $post_types ) ) {
			return $post_types;
		}
	}

	// Fallback: use filter with Polylang defaults (post, page, wp_block).
	$defaults  = array( 'post' => 'post', 'page' => 'page', 'wp_block' => 'wp_block' );
	$post_types = apply_filters( 'pll_get_post_types', $defaults, false );

	if ( is_array( $post_types ) ) {
		$post_types = array_values( $post_types );
		return $post_types;
	}

	return array();
}

/**
 * Inject language counts next to the default WP counters (views row).
 *
 * @param array $views
 * @param array $counts
 * @return array
 */
function pllc_inject_language_counts_into_views( $views, $counts ) {
	if ( empty( $counts ) || ! is_array( $counts ) ) {
		return $views;
	}

	$bits = array();

	foreach ( $counts as $row ) {
		if ( empty( $row['slug'] ) ) {
			continue;
		}

		$label = $row['slug'];
		// Prioritize short code to keep it compact.
		if ( ! empty( $row['name'] ) && strlen( $row['name'] ) <= 3 ) {
			$label = $row['name'];
		}

		$bits[] = sprintf(
			'%s (%s)',
			esc_html( $label ),
			esc_html( number_format_i18n( $row['count_published'] ) )
		);
	}

	if ( empty( $bits ) ) {
		return $views;
	}

	// Add a non-clickable item to the views row.
	$views['pllc-language-counts'] = '<span class="pllc-language-counts" style="font-weight:600; color:#2271b1;">' . esc_html__( 'Languages:', 'polylang-language-counts' ) . ' ' . implode( ' | ', $bits ) . '</span>';

	return $views;
}
