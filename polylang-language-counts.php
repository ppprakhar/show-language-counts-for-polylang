<?php
/**
 * Plugin Name:       Polylang Language Counts
 * Description:       Shows per-language post counts for all Polylang-enabled post types in the admin list screens without changing the default WordPress counters.
 * Version:           1.0.0
 * Author:            Your Name
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
	if ( ! function_exists( 'pll_get_post_types' ) || ! function_exists( 'pll_the_languages' ) || ! function_exists( 'pll_count_posts' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || empty( $screen->post_type ) ) {
		return;
	}

	$post_type     = $screen->post_type;
	$pll_posttypes = pll_get_post_types( array( 'status' => 'all' ) );

	// Only run for post types enabled in Polylang settings.
	if ( empty( $pll_posttypes ) || ! in_array( $post_type, $pll_posttypes, true ) ) {
		return;
	}

	// Compute counts once and then print as an admin notice.
	$counts = pllc_get_language_counts_for_post_type( $post_type );

	if ( ! empty( $counts ) ) {
		// Pass data via closure.
		add_action(
			'all_admin_notices',
			function () use ( $counts, $post_type ) {
				pllc_render_language_counts_notice( $counts, $post_type );
			}
		);
	}
}

/**
 * Get per-language counts for a given post type.
 *
 * @param string $post_type
 *
 * @return array[]
 */
function pllc_get_language_counts_for_post_type( $post_type ) {
	// raw => 1 returns an array of language data keyed by slug.
	$languages = pll_the_languages(
		array(
			'raw'              => 1,
			'hide_if_empty'    => 0,
			'hide_if_no_posts' => 0,
		)
	);

	if ( empty( $languages ) || ! is_array( $languages ) ) {
		return array();
	}

	$counts = array();

	foreach ( $languages as $slug => $lang ) {
		$name = isset( $lang['name'] ) ? $lang['name'] : ucfirst( $slug );

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
 * Render the notice box with language-wise counts.
 *
 * @param array  $counts
 * @param string $post_type
 */
function pllc_render_language_counts_notice( $counts, $post_type ) {
	if ( empty( $counts ) ) {
		return;
	}

	$post_type_obj = get_post_type_object( $post_type );
	$post_type_label = $post_type_obj ? $post_type_obj->labels->name : $post_type;

	?>
	<div class="notice notice-info is-dismissible">
		<p><strong><?php echo esc_html( sprintf( __( 'Language counts for %s (Polylang)', 'polylang-language-counts' ), $post_type_label ) ); ?></strong></p>
		<table class="widefat striped" style="max-width: 520px; margin-top: 4px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Language', 'polylang-language-counts' ); ?></th>
					<th><?php esc_html_e( 'Published', 'polylang-language-counts' ); ?></th>
					<th><?php esc_html_e( 'All statuses', 'polylang-language-counts' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $counts as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['name'] ); ?> (<?php echo esc_html( $row['slug'] ); ?>)</td>
					<td><?php echo esc_html( number_format_i18n( $row['count_published'] ) ); ?></td>
					<td><?php echo esc_html( number_format_i18n( $row['count_all'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p style="margin-top: 6px; font-size: 11px; opacity: .8;">
			<?php esc_html_e( 'Note: Default WordPress counters (All, Published, Draft…) remain unchanged; this box only adds Polylang language-wise counts.', 'polylang-language-counts' ); ?>
		</p>
	</div>
	<?php
}
