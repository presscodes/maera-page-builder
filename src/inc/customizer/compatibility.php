<?php
/**
 * @package Make
 */

if ( ! function_exists( 'ttfmake_customizer_get_key_migrations' ) ) :
/**
 * Return an array of option key migration sets.
 *
 * @since  1.3.0.
 *
 * @return array    The list of key migration sets.
 */
function ttfmake_customizer_get_key_migrations() {
	/**
	 * Sets are defined by the theme version they pertain to:
	 * $theme_version => array
	 *     $old => $new
	 */
	$migrations = array(
		'1.3.0' => array(
			'font-site-title'        => 'font-family-site-title',
			'font-header'            => array(
				'font-family-h1',
				'font-family-h2',
				'font-family-h3',
				'font-family-h4',
				'font-family-h5',
				'font-family-h6'
			),
			'font-body'              => 'font-family-body',
			'font-site-title-size'   => 'font-size-site-title',
			'font-site-tagline-size' => 'font-size-site-tagline',
			'font-nav-size'          => 'font-size-nav',
			'font-header-size'       => array(
				'font-size-h1',
				'font-size-h2',
				'font-size-h3',
				'font-size-h4',
				'font-size-h5',
				'font-size-h6'
			),
			'font-widget-size'       => 'font-size-widget',
			'font-body-size'         => 'font-size-body',
		),
	);

	return apply_filters( 'make_customizer_key_migrations', $migrations );
}
endif;

if ( ! function_exists( 'ttfmake_customizer_get_key_migration_callbacks' ) ) :
/**
 * Return an array of callbacks for a particular migration set.
 *
 * @since  1.3.0.
 *
 * @param  string    $version    The theme version to get the callbacks for
 * @return array                 An array containing any callbacks for the specified set
 */
function ttfmake_customizer_get_key_migration_callbacks( $version ) {
	/**
	 * $theme_version => array
	 *     $key => $callback
	 */
	$all_callbacks = array(
		'1.3.0' => array(
			'font-header-size' => 'ttfmake_customizer_header_sizes_callback',
		),
	);

	// Get the callbacks for the specified version
	$callbacks = array();
	if ( isset( $all_callbacks[$version] ) ) {
		$callbacks = $all_callbacks[$version];
	}

	return apply_filters( 'make_customizer_key_migration_callbacks', $callbacks, $version );
}
endif;

if ( ! function_exists( 'ttfmake_customizer_migrate_options' ) ) :
/**
 * Migrate old theme options to newer equivalents.
 *
 * This function parses the array of key migration sets from ttfmake_customizer_get_key_migrations(),
 * and compares the theme versions to an array of migration sets that have already been performed (if any).
 * For each migration set that hasn't been performed yet:
 *
 * 1. Check to see if any of the new keys already exist. Don't perform the migration if any do.
 * 2. Process each migration rule in the set, either with the specified callback, or by copying the
 *    old value over to each of the related new keys.
 *
 * Afterward, migration sets that were performed are stored in a theme mod called 'options-migrated' to
 * ensure that they won't be performed again.
 *
 * @since  1.3.0.
 *
 * @return void
 */
function ttfmake_customizer_migrate_options() {
	// Don't run migrations if WordPress version doesn't support panels
	if ( ! ttfmake_customizer_supports_panels() ) {
		return;
	}

	// Get the array of migration definitions
	$migrations = ttfmake_customizer_get_key_migrations();

	// Bail if all of the migrations have already been performed
	$migration_versions = array_keys( $migrations );
	$migrated           = get_theme_mod( 'options-migrated', array() );
	$missing_migrations = array_diff_key( $migration_versions, $migrated );

	if ( empty( $missing_migrations ) ) {
		return;
	}

	// Get array of all the theme mods for later use
	$theme_mods = get_theme_mods();

	// If the theme mods array is empty, this is a new installation,
	// no migration necessary
	if ( false === $theme_mods ) {
		set_theme_mod( 'options-migrated', $migration_versions );
		return;
	}

	// Run each migration set that hasn't been done yet
	foreach ( $missing_migrations as $version ) {
		// Compile new keys
		$new_keys = array();
		foreach ( $migrations[ $version ] as $new ) {
			$new_keys = array_merge( $new_keys, (array) $new );
		}

		// Test for new header keys in the theme mod array
		$diff         = array_diff_key( $new_keys, $theme_mods );
		$has_new_keys = ( count( $new_keys ) !== count( $diff ) );

		// Only run the migration if none of the new keys exist yet
		if ( ! $has_new_keys ) {
			// Check for special callbacks
			$callbacks = ttfmake_customizer_get_key_migration_callbacks( $version );

			// Process each migration rule
			foreach ( $migrations[ $version ] as $old => $new ) {
				// Get the old value, even if it's a default (the new key might not have the same default)
				$value = get_theme_mod( $old, ttfmake_get_default( $old ) );

				// Run the special callback for the option, if it exists
				if ( isset( $callbacks[ $old ] ) ) {
					call_user_func_array( $callbacks[ $old ], array( $value, $new ) );
				}
				// Otherwise set all the related new keys to the old key's value
				else {
					foreach ( (array) $new as $new_key ) {
						set_theme_mod( $new_key, $value );
					}
				}
			}
		}

		// Add the version to the array of completed migrations
		$migrated[] = $version;
	}

	// Update the array of completed migrations
	set_theme_mod( 'options-migrated', $migrated );
}
endif;

add_action( 'init', 'ttfmake_customizer_migrate_options' );

if ( ! function_exists( 'ttfmake_customizer_header_sizes_callback' ) ) :
/**
 * Convert the old header size value into separate sizes (H1-H6).
 *
 * @since  1.3.0.
 *
 * @param  int      $value       The old key value
 * @param  array    $new_keys    The new option keys
 * @return void
 */
function ttfmake_customizer_header_sizes_callback( $value, $new_keys ) {
	// Get the relative percentages
	$percent = ttfmake_font_get_relative_sizes();

	// Set the new font sizes
	foreach ( $new_keys as $key ) {
		$h = preg_replace( '/font-size-(h\d)/', '$1', $key );
		if ( $h ) {
			set_theme_mod( $key, ttfmake_get_relative_font_size( $value, $percent[$h] ) );
		}
	}
}
endif;

if ( ! function_exists( 'ttfmake_css_legacy_fonts' ) ) :
/**
 * Build the CSS rules for the custom fonts
 *
 * @since  1.0.0.
 *
 * @return void
 */
function ttfmake_css_legacy_fonts() {
	/**
	 * Font Families
	 */
	// Get and escape options
	$font_site_title       = get_theme_mod( 'font-site-title', ttfmake_get_default( 'font-site-title' ) );
	$font_site_title_stack = ttfmake_get_font_stack( $font_site_title );
	$font_header           = get_theme_mod( 'font-header', ttfmake_get_default( 'font-header' ) );
	$font_header_stack     = ttfmake_get_font_stack( $font_header );
	$font_body             = get_theme_mod( 'font-body', ttfmake_get_default( 'font-body' ) );
	$font_body_stack       = ttfmake_get_font_stack( $font_body );

	// Site Title Font
	if ( $font_site_title !== ttfmake_get_default( 'font-site-title' ) && '' !== $font_site_title_stack ) {
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.site-title', '.font-site-title' ),
			'declarations' => array(
				'font-family' => $font_site_title_stack
			)
		) );
	}

	// Header Font
	if ( $font_header !== ttfmake_get_default( 'font-header' ) && '' !== $font_header_stack ) {
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '.font-header' ),
			'declarations' => array(
				'font-family' => $font_header_stack
			)
		) );
	}

	// Body Font
	if ( $font_body !== ttfmake_get_default( 'font-body' ) && '' !== $font_body_stack ) {
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'body', '.font-body' ),
			'declarations' => array(
				'font-family' => $font_body_stack
			)
		) );
	}

	/**
	 * Font Sizes
	 */
	// Get and escape options
	$font_site_title_size = absint( get_theme_mod( 'font-site-title-size', ttfmake_get_default( 'font-site-title-size' ) ) );
	$font_site_tagline_size = absint( get_theme_mod( 'font-site-tagline-size', ttfmake_get_default( 'font-site-tagline-size' ) ) );
	$font_nav_size        = absint( get_theme_mod( 'font-nav-size', ttfmake_get_default( 'font-nav-size' ) ) );
	$font_header_size     = absint( get_theme_mod( 'font-header-size', ttfmake_get_default( 'font-header-size' ) ) );
	$font_widget_size     = absint( get_theme_mod( 'font-widget-size', ttfmake_get_default( 'font-widget-size' ) ) );
	$font_body_size       = absint( get_theme_mod( 'font-body-size', ttfmake_get_default( 'font-body-size' ) ) );

	// Relative font sizes
	$percent = ttfmake_font_get_relative_sizes();

	// Site Title Font Size
	if ( $font_site_title_size !== ttfmake_get_default( 'font-site-title-size' ) ) {
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.site-title', '.font-site-title' ),
			'declarations' => array(
				'font-size-px'  => $font_site_title_size . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( $font_site_title_size ) . 'rem'
			)
		) );
	}

	// Site Tagline Font Size
	if ( $font_site_tagline_size !== ttfmake_get_default( 'font-site-tagline-size' ) ) {
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.site-description', '.font-site-tagline' ),
			'declarations' => array(
				'font-size-px'  => $font_site_tagline_size . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( $font_site_tagline_size ) . 'rem'
			)
		) );
	}

	// Navigation Font Size
	if ( $font_nav_size !== ttfmake_get_default( 'font-nav-size' ) ) {
		// Top level
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.site-navigation .menu li a', '.font-nav' ),
			'declarations' => array(
				'font-size-px'  => $font_nav_size . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( $font_nav_size ) . 'rem'
			)
		) );

		// Sub menu items
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.site-navigation .menu .sub-menu li a', '.site-navigation .menu .children li a' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_nav_size, $percent['sub-menu'] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_nav_size, $percent['sub-menu'] ) ) . 'rem'
			),
			'media'        => 'screen and (min-width: 800px)'
		) );

		// Grandchild arrow position
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.site-navigation .menu .sub-menu .menu-item-has-children a:after', '.site-navigation .menu .children .menu-item-has-children a:after' ),
			'declarations' => array(
				'top' => ( $font_nav_size * 1.4 / 2 ) - 5 . 'px'
			),
			'media'        => 'screen and (min-width: 800px)'
		) );
	}

	// Header Font Sizes
	if ( $font_header_size !== ttfmake_get_default( 'font-header-size' ) ) {
		// h1
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h1', '.font-header' ),
			'declarations' => array(
				'font-size-px'  => $font_header_size . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( $font_header_size ) . 'rem'
			)
		) );

		// h2
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h2' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h2' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h2' ] ) ) . 'rem'
			)
		) );

		// h3
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h3', '.builder-text-content .widget-title' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h3' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h3' ] ) ) . 'rem'
			)
		) );

		// h4
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h4' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h4' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h4' ] ) ) . 'rem'
			)
		) );

		// h5
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h5' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h5' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h5' ] ) ) . 'rem'
			)
		) );

		// h6
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'h6' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h6' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_header_size, $percent[ 'h6' ] ) ) . 'rem'
			)
		) );

		// Post title with two sidebars
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.has-left-sidebar.has-right-sidebar .entry-title' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_header_size, $percent[ 'post-title' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_header_size, $percent[ 'post-title' ] ) ) . 'rem'
			),
			'media'        => 'screen and (min-width: 800px)'
		) );
	}

	// Widget Font Size
	if ( $font_widget_size !== ttfmake_get_default( 'font-widget-size' ) ) {
		// Widget body
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.widget', '.font-widget' ),
			'declarations' => array(
				'font-size-px'  => $font_widget_size . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( $font_widget_size ) . 'rem'
			)
		) );

		// Widget title
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.widget-title' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_widget_size, $percent[ 'widget-title' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_widget_size, $percent[ 'widget-title' ] ) ) . 'rem'
			)
		) );
	}

	// Body Font Size
	if ( $font_body_size !== ttfmake_get_default( 'font-body-size' ) ) {
		// body
		ttfmake_get_css()->add( array(
			'selectors'    => array( 'body', '.font-body', '.builder-text-content .widget' ),
			'declarations' => array(
				'font-size-px'  => $font_body_size . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( $font_body_size ) . 'rem'
			)
		) );

		// Comments
		ttfmake_get_css()->add( array(
			'selectors'    => array( '#comments' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_body_size, $percent[ 'comments' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_body_size, $percent[ 'comments' ] ) ) . 'rem'
			)
		) );

		// Comment date
		ttfmake_get_css()->add( array(
			'selectors'    => array( '.comment-date' ),
			'declarations' => array(
				'font-size-px'  => ttfmake_get_relative_font_size( $font_body_size, $percent[ 'comment-date' ] ) . 'px',
				'font-size-rem' => ttfmake_convert_px_to_rem( ttfmake_get_relative_font_size( $font_body_size, $percent[ 'comment-date' ] ) ) . 'rem'
			)
		) );
	}
}
endif;