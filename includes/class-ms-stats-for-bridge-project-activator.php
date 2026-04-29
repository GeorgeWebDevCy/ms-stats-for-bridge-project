<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.georgenicolaou.me
 * @since      1.0.0
 *
 * @package    Ms_Stats_For_Bridge_Project
 * @subpackage Ms_Stats_For_Bridge_Project/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ms_Stats_For_Bridge_Project
 * @subpackage Ms_Stats_For_Bridge_Project/includes
 * @author     George Nicolaou <orionas.elite@gmail.com>
 */
class Ms_Stats_For_Bridge_Project_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'MS Stats for Bridge Project requires the MasterStudy LMS plugin to be installed and activated.', 'ms-stats' ),
				esc_html__( 'Plugin Activation Error', 'ms-stats' ),
				array( 'back_link' => true )
			);
		}
	}

}
