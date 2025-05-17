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
require_once plugin_dir_path(__FILE__) . '../updater.php';
require_once plugin_dir_path(__FILE__) . 'update_transient.php';


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

// Auto Install Plugins

add_action('after_switch_theme', 'auto_install_plugins_after_theme_activation');
add_action('admin_init', 'auto_install_plugins_manual_trigger');

function auto_install_plugins_manual_trigger()
{
	if (isset($_GET['install_plugins']) && $_GET['install_plugins'] == '1' && current_user_can('install_plugins')) {
		auto_install_plugins_after_theme_activation();
	}
}

function auto_install_plugins_after_theme_activation()
{
	error_log('Auto-install-plugins: Script started.');

	 
	if (!current_user_can('install_plugins')) {
		error_log('Auto-install-plugins: User lacks permission to install plugins.');
		return;
	}

 
	$plugins_to_install = array(
		'contact-form-7' => 'contact-form-7/wp-contact-form-7.php',
		'jetpack' => 'jetpack/jetpack.php'
		 
	);

	 
	$required_files = array(
		ABSPATH . 'wp-admin/includes/file.php',
		ABSPATH . 'wp-admin/includes/plugin-install.php',
		ABSPATH . 'wp-admin/includes/class-wp-upgrader.php',
		ABSPATH . 'wp-admin/includes/plugin.php',
	);

	foreach ($required_files as $file) {
		if (!file_exists($file)) {
			error_log('Auto-install-plugins: Missing file: ' . $file);
			return;
		}
		require_once $file;
		error_log('Auto-install-plugins: Loaded file: ' . $file);
	}

 
	if (!WP_Filesystem()) {
		error_log('Auto-install-plugins: Failed to initialize filesystem.');
		return;
	}
	error_log('Auto-install-plugins: Filesystem initialized.');

	global $wp_filesystem;
	if (!$wp_filesystem->is_writable(WP_PLUGIN_DIR)) {
		error_log('Auto-install-plugins: Plugin directory not writable: ' . WP_PLUGIN_DIR);
		return;
	}

	foreach ($plugins_to_install as $slug => $plugin_file) {
		error_log('Auto-install-plugins: Processing plugin: ' . $slug);

	 
		if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
			error_log('Auto-install-plugins: Plugin already installed: ' . $slug);
			if (!is_plugin_active($plugin_file)) {
				$activate_result = activate_plugin($plugin_file);
				if (is_wp_error($activate_result)) {
					error_log('Auto-install-plugins: Activation failed for ' . $slug . ': ' . $activate_result->get_error_message());
				} else {
					error_log('Auto-install-plugins: Activated plugin: ' . $slug);
				}
			}
			continue;
		}

		 
		$api = plugins_api('plugin_information', array(
			'slug' => $slug,
			'fields' => array('sections' => false),
		));

		if (is_wp_error($api)) {
			error_log('Auto-install-plugins: Failed to fetch info for ' . $slug . ': ' . $api->get_error_message());
			continue;
		}
		error_log('Auto-install-plugins: Fetched info for ' . $slug);

		 
		$upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
		$install_result = $upgrader->install($api->download_link);

		if (is_wp_error($install_result)) {
			error_log('Auto-install-plugins: Installation failed for ' . $slug . ': ' . $install_result->get_error_message());
			continue;
		} elseif ($install_result === false) {
			error_log('Auto-install-plugins: Installation failed for ' . $slug);
			continue;
		}
		error_log('Auto-install-plugins: Installed plugin: ' . $slug);

		 
		$activate_result = activate_plugin($plugin_file);
		if (is_wp_error($activate_result)) {
			error_log('Auto-install-plugins: Activation failed for ' . $slug . ': ' . $activate_result->get_error_message());
		} else {
			error_log('Auto-install-plugins: Activated plugin: ' . $slug);
		}
	}
	error_log('Auto-install-plugins: Script completed.');
}



 
add_action('admin_notices', 'auto_install_plugins_admin_notice');

function auto_install_plugins_admin_notice()
{
	if (!current_user_can('install_plugins')) {
		return;
	}
?>
	<div class="notice notice-info is-dismissible">
		<p>Auto-install-plugins: Check wp-content/debug.log for details. To test, visit <a href="?install_plugins=1">?install_plugins=1</a> as an admin.</p>
	</div>
<?php
}

