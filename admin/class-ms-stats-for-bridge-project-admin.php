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

		$stm       = get_option( 'stm_lms_settings', array() );
		$primary   = ! empty( $stm['main_color'] ) ? sanitize_hex_color( $stm['main_color'] ) : '#385bce';
		$secondary = ! empty( $stm['secondary_color'] ) ? sanitize_hex_color( $stm['secondary_color'] ) : '#17d292';

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ms-stats-for-bridge-project-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'jquery-ui-style', 'https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.min.css', array(), '1.13.3' );
		wp_enqueue_style( 'ms-stats-datatables', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css', array(), '1.13.8' );
		wp_enqueue_style( 'ms-stats-datatables-resp', 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css', array(), '2.5.0' );

		wp_add_inline_style(
			$this->plugin_name,
			"
			.ms-stats-page .nav-tab-wrapper{background:#fff;border:1px solid #dcdcde;border-bottom:none;border-radius:8px 8px 0 0;padding:4px 8px 0;display:flex;flex-wrap:wrap;gap:2px;}
			.ms-stats-page .nav-tab{border:none;border-bottom:3px solid transparent;border-radius:4px 4px 0 0;background:transparent;color:#50575e;padding:10px 16px;margin:0;font-weight:500;font-size:13px;transition:color .15s,border-color .15s;text-decoration:none;}
			.ms-stats-page .nav-tab:hover{color:{$primary};background:rgba(56,91,206,.06);}
			.ms-stats-page .nav-tab-active{color:{$primary}!important;border-bottom-color:{$primary}!important;font-weight:600;}
			.ms-stats-tab-panel{background:#fff;border:1px solid #dcdcde;border-top:none;border-radius:0 0 8px 8px;padding:24px;min-height:200px;}
			.ms-stats-filter-bar{background:#f6f7f7;border:1px solid #dcdcde;border-radius:6px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
			.ms-stats-filter-bar label{font-weight:600;color:#1e1e1e;font-size:13px;}
			.ms-stats-filter-bar input[type=text]{width:120px;}
			.ms-stats-section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;}
			.ms-stats-section-header h2{margin:0;font-size:1.05em;font-weight:600;color:#1e1e1e;padding:0;}
			.ms-stats-export-buttons{display:flex;gap:8px;}
			.ms-stats-cards{display:flex;gap:20px;margin-bottom:24px;flex-wrap:wrap;}
			.ms-stats-card{background:#fff;border:1px solid #dcdcde;border-left:4px solid {$primary};border-radius:6px;padding:20px 28px;min-width:160px;flex:1;box-shadow:0 1px 3px rgba(0,0,0,.06);}
			.ms-stats-card-value{font-size:2.4em;font-weight:700;color:{$primary};line-height:1;}
			.ms-stats-card-label{color:#50575e;margin-top:8px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;}
			.ms-stats-tab-panel table.ms-stats-table thead th,.ms-stats-tab-panel table.ms-stats-table thead td{background:{$primary}!important;color:#fff!important;border-color:rgba(255,255,255,.15)!important;font-weight:600;}
			.ms-stats-tab-panel table.ms-stats-table thead .sorting::before,.ms-stats-tab-panel table.ms-stats-table thead .sorting::after,.ms-stats-tab-panel table.ms-stats-table thead .sorting_asc::before,.ms-stats-tab-panel table.ms-stats-table thead .sorting_asc::after,.ms-stats-tab-panel table.ms-stats-table thead .sorting_desc::before,.ms-stats-tab-panel table.ms-stats-table thead .sorting_desc::after{opacity:.5;}
			.ms-stats-tab-panel table.ms-stats-table tbody tr:hover td{background:rgba(56,91,206,.04)!important;}
			.ms-stats-tab-panel .dataTables_paginate .paginate_button.current,.ms-stats-tab-panel .dataTables_paginate .paginate_button.current:hover{background:{$primary}!important;border-color:{$primary}!important;color:#fff!important;border-radius:4px;}
			.ms-stats-tab-panel .dataTables_paginate .paginate_button:hover{background:rgba(56,91,206,.1)!important;border-color:{$primary}!important;color:{$primary}!important;border-radius:4px;}
			.ms-stats-tab-panel .dataTables_filter input{border:1px solid #c3c4c7;border-radius:4px;padding:5px 10px;margin-left:6px;}
			.ms-stats-tab-panel .dataTables_length select{border:1px solid #c3c4c7;border-radius:4px;padding:4px 8px;}
			.ms-stats-bar-wrap{display:flex;align-items:center;gap:8px;}
			.ms-stats-bar{background:#f0f0f0;border-radius:3px;width:80px;height:10px;overflow:hidden;flex-shrink:0;}
			.ms-stats-bar-fill{height:100%;border-radius:3px;}
			.ms-stats-bar-fill--blue{background:{$primary};}
			.ms-stats-bar-fill--green{background:{$secondary};}
			.button.button-pdf{background:#c0392b;border-color:#a93226;color:#fff;box-shadow:none;text-shadow:none;}
			.button.button-pdf:hover,.button.button-pdf:focus{background:#a93226;border-color:#922b21;color:#fff;}
			code.ms-cert-code{background:{$primary}18;color:{$primary};border:1px solid {$primary}33;padding:2px 8px;border-radius:4px;font-size:12px;}
			"
		);
	}

	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'ms-stats-for-bridge-project' ) === false ) {
			return;
		}

		$stm     = get_option( 'stm_lms_settings', array() );
		$primary = ! empty( $stm['main_color'] ) ? sanitize_hex_color( $stm['main_color'] ) : '#385bce';
		$logo    = ! empty( $stm['print_page_logo'] ) ? esc_url_raw( $stm['print_page_logo'] ) : '';

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'ms-stats-datatables', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.8', true );
		wp_enqueue_script( 'ms-stats-datatables-resp', 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js', array( 'ms-stats-datatables' ), '2.5.0', true );
		wp_enqueue_script( 'ms-stats-jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true );
		wp_enqueue_script( 'ms-stats-autotable', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js', array( 'ms-stats-jspdf' ), '3.8.2', true );
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/ms-stats-for-bridge-project-admin.js',
			array( 'jquery', 'jquery-ui-datepicker', 'ms-stats-datatables', 'ms-stats-autotable' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'msStatsConfig',
			array(
				'primaryColor' => $primary,
				'logo'         => $logo,
				'siteName'     => get_bloginfo( 'name' ),
				'pluginTitle'  => 'MS Stats — Bridge Project',
				'fontUrl'      => plugin_dir_url( __FILE__ ) . 'fonts/DejaVuSans.ttf',
			)
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
