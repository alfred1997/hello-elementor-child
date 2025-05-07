<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '1.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );



function get_latest_theme_version_from_git() {
    $git_repo_url = 'https://github.com/javidm777/child_theme.git';

    // دستور Git برای گرفتن آخرین تگ
    $latest_tag = shell_exec("git ls-remote --tags $git_repo_url | awk '{print \$2}' | grep -v '{}' | sort -V | tail -n 1");

    return trim($latest_tag);
}

function check_theme_updates() {
    $current_version = wp_get_theme()->get('Version');
    $latest_version = get_latest_theme_version_from_git();

    if (version_compare($current_version, $latest_version, '<')) {
        add_action('admin_notices', function () use ($latest_version) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            printf(__('A new version (%s) of the theme is available. <a href="%s">Update now</a>.'), $latest_version, admin_url('update-theme.php?theme=your-theme-folder'));
            echo '</p></div>';
        });
    }
}

add_action('admin_init', 'check_theme_updates');


if (version_compare($GLOBALS['wp_version'], '5.0-beta', '>')) {
  add_filter('use_block_editor_for_post_type', '__return_false', 100);
} else {
  add_filter('gutenberg_can_edit_post_type', '__return_false');
}
add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
add_filter( 'use_widgets_block_editor', '__return_false' );