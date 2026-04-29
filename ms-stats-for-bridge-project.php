<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.georgenicolaou.me
 * @since             1.0.0
 * @package           Ms_Stats_For_Bridge_Project
 *
 * @wordpress-plugin
 * Plugin Name:       MS Stats For Bridge Project
 * Plugin URI:        https://www.georgenicolaou.me/plugins/ms-stats-for-bridge-project
 * Description:       This plugin create a menu in admin with stats and reports for Master Study
 * Version:           3.6.0
 * Author:            George Nicolaou
 * Author URI:        https://www.georgenicolaou.me/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ms-stats-for-bridge-project
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$ms_stats_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/GeorgeWebDevCy/ms-stats-for-bridge-project',
	__FILE__,
	'ms-stats-for-bridge-project'
);
$ms_stats_update_checker->setBranch( 'main' );

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MS_STATS_FOR_BRIDGE_PROJECT_VERSION', '3.6.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ms-stats-for-bridge-project-activator.php
 */
function activate_ms_stats_for_bridge_project() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ms-stats-for-bridge-project-activator.php';
	Ms_Stats_For_Bridge_Project_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ms-stats-for-bridge-project-deactivator.php
 */
function deactivate_ms_stats_for_bridge_project() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ms-stats-for-bridge-project-deactivator.php';
	Ms_Stats_For_Bridge_Project_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ms_stats_for_bridge_project' );
register_deactivation_hook( __FILE__, 'deactivate_ms_stats_for_bridge_project' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ms-stats-for-bridge-project.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function ms_stats_masterstudy_missing_notice() {
	echo '<div class="notice notice-error"><p><strong>MS Stats for Bridge Project</strong> requires the MasterStudy LMS plugin to be installed and activated.</p></div>';
}

function run_ms_stats_for_bridge_project() {
	if ( ! defined( 'MS_LMS_VERSION' ) ) {
		add_action( 'admin_notices', 'ms_stats_masterstudy_missing_notice' );
		return;
	}

	$plugin = new Ms_Stats_For_Bridge_Project();
	$plugin->run();
}
/**
 * Resolve a MasterStudy profile form field slug (e.g. "ms-country") to the
 * actual usermeta key (e.g. "aqdnfaayngf"). MasterStudy auto-generates a
 * random hash as the real meta key; the Field ID the admin sees is just a slug.
 * Falls back to the input unchanged if no match is found.
 *
 * @param string $input Field ID/slug or raw meta key.
 * @return string Resolved meta key.
 */
function ms_stats_resolve_country_meta_key( $input ) {
	$input = trim( (string) $input );
	if ( '' === $input ) {
		return $input;
	}

	$forms_raw = get_option( 'stm_lms_form_builder_forms' );
	$forms     = maybe_unserialize( $forms_raw );

	if ( ! is_array( $forms ) ) {
		return $input;
	}

	foreach ( $forms as $form ) {
		if ( empty( $form['slug'] ) || 'profile_form' !== $form['slug'] ) {
			continue;
		}
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			continue;
		}
		foreach ( $form['fields'] as $field ) {
			// Match by slug (Field ID the admin sees) OR by the generated id.
			$slug = $field['slug'] ?? '';
			$id   = $field['id'] ?? '';
			if ( $input === $slug || $input === $id ) {
				return $id !== '' ? $id : $input;
			}
		}
	}

	return $input;
}

run_ms_stats_for_bridge_project();
