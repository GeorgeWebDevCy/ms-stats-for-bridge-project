<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.georgenicolaou.me
 * @since      1.0.0
 *
 * @package    Ms_Stats_For_Bridge_Project
 * @subpackage Ms_Stats_For_Bridge_Project/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ms_Stats_For_Bridge_Project
 * @subpackage Ms_Stats_For_Bridge_Project/admin
 * @author     George Nicolaou <orionas.elite@gmail.com>
 */
class Ms_Stats_For_Bridge_Project_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ms-stats-for-bridge-project' ) === false ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ms-stats-for-bridge-project-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'jquery-ui-style', 'https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.min.css', array(), '1.13.3' );
	}

	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ms-stats-for-bridge-project' ) === false ) {
			return;
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ms-stats-for-bridge-project-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_add_inline_script(
			'jquery-ui-datepicker',
			"jQuery(function($){
				$('.ms-stats-datepicker').datepicker({
					dateFormat:  'dd/mm/yy',
					changeMonth: true,
					changeYear:  true,
					onSelect: function(dateText, inst) {
						var altField = $(this).data('alt-field');
						var d = new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay);
						var ymd = d.getFullYear() + '-' +
							String(d.getMonth()+1).padStart(2,'0') + '-' +
							String(d.getDate()).padStart(2,'0');
						$(altField).val(ymd);
					}
				});
			});"
		);
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'MS Stats', 'ms-stats-for-bridge-project' ),
			__( 'MS Stats', 'ms-stats-for-bridge-project' ),
			'manage_options',
			'ms-stats-for-bridge-project',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-chart-bar',
			26
		);
	}

	public function display_plugin_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/ms-stats-for-bridge-project-admin-display.php';
	}

	public function handle_csv_export() {
		if ( ! isset( $_GET['page'] ) || 'ms-stats-for-bridge-project' !== $_GET['page'] ) {
			return;
		}
		if ( empty( $_GET['export'] ) || 'csv' !== $_GET['export'] ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'ms_stats_export' ) ) {
			wp_die( 'Invalid nonce' );
		}
		require_once plugin_dir_path( __FILE__ ) . 'partials/ms-stats-for-bridge-project-export-csv.php';
		exit;
	}

}
