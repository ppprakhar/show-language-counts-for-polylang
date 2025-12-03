<?php
/**
 * Plugin Name:       Show Language Counts for Polylang
 * Description:       Shows per-language post counts for all Polylang-enabled post types in the admin list screens without changing the default WordPress counters.
 * Version:           1.0.0
 * Author:            Prakhar Kant Tripathi
 * Text Domain:       show-language-counts-for-polylang
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * License:           GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set up the counts when loading an edit.php screen.
 */
add_action( 'load-edit.php', 'pllc_setup_language_counts_for_screen' );
add_action( 'admin_menu', 'pllc_register_missing_translations_page' );

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
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using official Polylang filter.
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
	$views['pllc-language-counts'] = '<span class="pllc-language-counts" style="font-weight:600; color:#2271b1;">' . esc_html__( 'Languages:', 'show-language-counts-for-polylang' ) . ' ' . implode( ' | ', $bits ) . '</span>';

	return $views;
}

/**
 * Register admin page to list posts missing a translation in a target language.
 */
function pllc_register_missing_translations_page() {
	if ( ! function_exists( 'PLL' ) ) {
		return;
	}

	add_submenu_page(
		'tools.php',
		__( 'Missing Translations', 'show-language-counts-for-polylang' ),
		__( 'Missing Translations', 'show-language-counts-for-polylang' ),
		'manage_options',
		'pllc-missing-translations',
		'pllc_render_missing_translations_page'
	);
}

/**
 * Render the Missing Translations admin page.
 */
function pllc_render_missing_translations_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'show-language-counts-for-polylang' ) );
	}

	$post_types = pllc_get_translated_post_types();
	$lang_objs  = pllc_get_languages();
	$lang_map   = array();

	foreach ( $lang_objs as $lang ) {
		$slug = is_object( $lang ) ? ( $lang->slug ?? '' ) : ( is_array( $lang ) ? ( $lang['slug'] ?? '' ) : '' );
		$name = is_object( $lang ) ? ( $lang->name ?? $slug ) : ( is_array( $lang ) ? ( $lang['name'] ?? $slug ) : $slug );
		if ( $slug ) {
			$lang_map[ $slug ] = $name;
		}
	}

	$selected_post_type = isset( $_REQUEST['pllc_post_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pllc_post_type'] ) ) : '';
	$target_lang        = isset( $_REQUEST['pllc_target_lang'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pllc_target_lang'] ) ) : '';
	$base_lang          = isset( $_REQUEST['pllc_base_lang'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['pllc_base_lang'] ) ) : '';

	$messages = array();

	// Bulk actions: draft / trash.
	if ( isset( $_POST['pllc_action'] ) && isset( $_POST['pllc_ids'] ) && is_array( $_POST['pllc_ids'] ) ) {
		check_admin_referer( 'pllc_missing_action' );
		$action = sanitize_text_field( wp_unslash( $_POST['pllc_action'] ) );
		$ids    = array_map( 'intval', (array) $_POST['pllc_ids'] );

		$changed = 0;
		foreach ( $ids as $id ) {
			if ( 'draft' === $action ) {
				$res = wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'draft',
					),
					true
				);
				if ( ! is_wp_error( $res ) ) {
					$changed++;
				}
			} elseif ( 'trash' === $action ) {
				$res = wp_trash_post( $id );
				if ( ! is_wp_error( $res ) ) {
					$changed++;
				}
			}
		}

			if ( $changed > 0 ) {
				/* translators: %d: number of items updated */
				$messages[] = sprintf( _n( '%d item updated.', '%d items updated.', $changed, 'show-language-counts-for-polylang' ), $changed );
			}
	}

	$results = array();
	if ( $selected_post_type && $target_lang ) {
		$results = pllc_scan_missing_translations( $selected_post_type, $target_lang, $base_lang );
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Missing Translations', 'show-language-counts-for-polylang' ); ?></h1>
		<p><?php esc_html_e( 'Find posts/products that do not yet have a translation in the target language.', 'show-language-counts-for-polylang' ); ?></p>

		<?php if ( ! empty( $messages ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<?php foreach ( $messages as $msg ) : ?>
					<p><?php echo esc_html( $msg ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<form method="get">
			<input type="hidden" name="page" value="pllc-missing-translations" />
			<table class="form-table">
				<tr>
					<th scope="row"><label for="pllc_post_type"><?php esc_html_e( 'Post type', 'show-language-counts-for-polylang' ); ?></label></th>
					<td>
						<select name="pllc_post_type" id="pllc_post_type" required>
							<option value=""><?php esc_html_e( 'Select a post type', 'show-language-counts-for-polylang' ); ?></option>
							<?php foreach ( $post_types as $pt ) : ?>
								<option value="<?php echo esc_attr( $pt ); ?>" <?php selected( $selected_post_type, $pt ); ?>><?php echo esc_html( $pt ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pllc_target_lang"><?php esc_html_e( 'Target language (missing)', 'show-language-counts-for-polylang' ); ?></label></th>
					<td>
						<select name="pllc_target_lang" id="pllc_target_lang" required>
							<option value=""><?php esc_html_e( 'Select a language', 'show-language-counts-for-polylang' ); ?></option>
							<?php foreach ( $lang_map as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $target_lang, $slug ); ?>><?php echo esc_html( strtoupper( $slug ) . ' — ' . $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="pllc_base_lang"><?php esc_html_e( 'Only consider base language', 'show-language-counts-for-polylang' ); ?></label></th>
					<td>
						<select name="pllc_base_lang" id="pllc_base_lang">
							<option value=""><?php esc_html_e( 'Any language (default)', 'show-language-counts-for-polylang' ); ?></option>
							<?php foreach ( $lang_map as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $base_lang, $slug ); ?>><?php echo esc_html( strtoupper( $slug ) . ' — ' . $name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Optionally limit to items in a specific base language (e.g., your main language).', 'show-language-counts-for-polylang' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Scan for missing translations', 'show-language-counts-for-polylang' ) ); ?>
		</form>

		<?php if ( $selected_post_type && $target_lang ) : ?>
			<h2>
				<?php
				printf(
					/* translators: 1: count, 2: post type, 3: target language */
					esc_html__( '%1$d items missing a %3$s translation in %2$s', 'show-language-counts-for-polylang' ),
					count( $results ),
					esc_html( $selected_post_type ),
					esc_html( strtoupper( $target_lang ) )
				);
				?>
			</h2>

			<?php if ( empty( $results ) ) : ?>
				<p><?php esc_html_e( 'No items found.', 'show-language-counts-for-polylang' ); ?></p>
			<?php else : ?>
				<form method="post">
					<?php wp_nonce_field( 'pllc_missing_action' ); ?>
					<input type="hidden" name="page" value="pllc-missing-translations" />
					<input type="hidden" name="pllc_post_type" value="<?php echo esc_attr( $selected_post_type ); ?>" />
					<input type="hidden" name="pllc_target_lang" value="<?php echo esc_attr( $target_lang ); ?>" />
					<input type="hidden" name="pllc_base_lang" value="<?php echo esc_attr( $base_lang ); ?>" />

					<div style="margin: 12px 0;">
						<select name="pllc_action">
							<option value=""><?php esc_html_e( 'Bulk actions', 'show-language-counts-for-polylang' ); ?></option>
							<option value="draft"><?php esc_html_e( 'Move to draft', 'show-language-counts-for-polylang' ); ?></option>
							<option value="trash"><?php esc_html_e( 'Move to trash', 'show-language-counts-for-polylang' ); ?></option>
						</select>
						<?php submit_button( __( 'Apply', 'show-language-counts-for-polylang' ), 'secondary', '', false ); ?>
					</div>

					<table class="widefat striped">
						<thead>
							<tr>
								<th style="width:30px;"><input type="checkbox" onclick="jQuery('.pllc-row-check').prop('checked', this.checked);" /></th>
								<th><?php esc_html_e( 'Title', 'show-language-counts-for-polylang' ); ?></th>
								<th><?php esc_html_e( 'Language', 'show-language-counts-for-polylang' ); ?></th>
								<th><?php esc_html_e( 'Status', 'show-language-counts-for-polylang' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'show-language-counts-for-polylang' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $results as $row ) : ?>
								<tr>
									<td><input class="pllc-row-check" type="checkbox" name="pllc_ids[]" value="<?php echo esc_attr( $row['id'] ); ?>" /></td>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>">
											<?php echo esc_html( $row['title'] ); ?>
										</a>
									</td>
									<td><?php echo esc_html( strtoupper( $row['lang'] ) ); ?></td>
									<td><?php echo esc_html( $row['status'] ); ?></td>
									<td><a href="<?php echo esc_url( get_permalink( $row['id'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'show-language-counts-for-polylang' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Scan for posts missing a target language translation.
 *
 * @param string      $post_type
 * @param string      $target_lang
 * @param string|null $base_lang Optional base language to limit scan.
 * @return array[]
 */
function pllc_scan_missing_translations( $post_type, $target_lang, $base_lang = '' ) {
	$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'lang'             => 'all',
				'suppress_filters' => false,
			)
		);

	$rows = array();
	foreach ( $ids as $id ) {
		$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $id, 'slug' ) : '';

		// Optional base language restriction.
		if ( $base_lang && $lang !== $base_lang ) {
			continue;
		}

		// Skip if this post already is in the target language.
		if ( $lang === $target_lang ) {
			continue;
		}

		$translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $id ) : array();
		if ( is_array( $translations ) && ! empty( $translations[ $target_lang ] ) ) {
			continue;
		}

		$rows[] = array(
			'id'     => $id,
			'title'  => get_the_title( $id ),
			'lang'   => $lang ?: '?',
			'status' => get_post_status( $id ),
		);
	}

	return $rows;
}
