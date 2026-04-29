<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.georgenicolaou.me
 * @since      1.0.0
 *
 * @package    Ms_Stats_For_Bridge_Project
 * @subpackage Ms_Stats_For_Bridge_Project/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Ms_Stats_For_Bridge_Project
 * @subpackage Ms_Stats_For_Bridge_Project/includes
 * @author     George Nicolaou <orionas.elite@gmail.com>
 */
class Ms_Stats_For_Bridge_Project_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ms-stats',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
