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
		wp_enqueue_style( 'ms-stats-datatables', 'https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css', array(), '2.0.8' );
		wp_enqueue_style( 'ms-stats-datatables-resp', 'https://cdn.datatables.net/responsive/3.0.2/css/responsive.dataTables.min.css', array(), '3.0.2' );

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
			.ms-stats-tab-panel .dt-container{border-radius:6px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);}
			.ms-stats-tab-panel table.ms-stats-table{border-collapse:collapse;width:100%!important;}
			.ms-stats-tab-panel table.ms-stats-table thead{display:table-header-group!important;visibility:visible!important;}
			.ms-stats-tab-panel table.ms-stats-table thead tr{display:table-row!important;}
			.ms-stats-tab-panel table.ms-stats-table thead th,.ms-stats-tab-panel table.ms-stats-table thead td{display:table-cell!important;visibility:visible!important;background:#f3f4f6!important;color:#111827!important;border-top:none!important;border-left:none!important;border-right:none!important;border-bottom:2px solid {$primary}!important;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.4px;vertical-align:bottom;}
			.ms-stats-tab-panel table.ms-stats-table thead th.dt-orderable-asc,.ms-stats-tab-panel table.ms-stats-table thead th.dt-orderable-desc,.ms-stats-tab-panel table.ms-stats-table thead th.dt-ordering-asc,.ms-stats-tab-panel table.ms-stats-table thead th.dt-ordering-desc{padding-right:26px;}
			.ms-stats-tab-panel table.ms-stats-table thead .dt-column-order{opacity:.5;}
			.ms-stats-tab-panel table.ms-stats-table thead th.dt-ordering-asc .dt-column-order,.ms-stats-tab-panel table.ms-stats-table thead th.dt-ordering-desc .dt-column-order{opacity:1;color:{$primary};}
			.ms-stats-tab-panel table.ms-stats-table tbody td{display:table-cell!important;padding:9px 14px!important;border-bottom:1px solid #f0f0f0!important;font-size:13px;}
			.ms-stats-tab-panel table.ms-stats-table tbody tr:nth-child(even) td{background:#f9fafb;}
			.ms-stats-tab-panel table.ms-stats-table tbody tr:hover td{background:{$primary}10!important;}
			.ms-stats-tab-panel .dt-layout-row{margin-bottom:12px;}
			.ms-stats-tab-panel .dt-search input{border:1px solid #c3c4c7;border-radius:6px;padding:6px 12px;font-size:13px;outline:none;transition:border-color .2s;}
			.ms-stats-tab-panel .dt-search input:focus{border-color:{$primary};}
			.ms-stats-tab-panel .dt-length select{border:1px solid #c3c4c7;border-radius:6px;padding:5px 10px;font-size:13px;}
			.ms-stats-tab-panel .dt-paging .dt-paging-button{border-radius:4px!important;border:1px solid #dcdcde!important;background:#fff!important;color:#50575e!important;padding:4px 10px!important;margin:0 2px!important;font-size:12px!important;cursor:pointer;}
			.ms-stats-tab-panel .dt-paging .dt-paging-button.current,.ms-stats-tab-panel .dt-paging .dt-paging-button.current:hover{background:{$primary}!important;border-color:{$primary}!important;color:#fff!important;}
			.ms-stats-tab-panel .dt-paging .dt-paging-button:hover{background:{$primary}18!important;border-color:{$primary}!important;color:{$primary}!important;}
			.ms-stats-tab-panel .dt-info{font-size:12px;color:#50575e;}
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

		// Logo: resolve URL → local file path → base64 data URI (avoids CORS entirely).
		$logo_data = '';
		$logo_w    = 0;
		$logo_h    = 0;
		$logo_fmt  = 'PNG';

		$logo_url = '';
		if ( ! empty( $stm['print_page_logo'] ) ) {
			$logo_url = esc_url_raw( $stm['print_page_logo'] );
		} else {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			if ( $custom_logo_id ) {
				$logo_src = wp_get_attachment_image_src( $custom_logo_id, 'medium' );
				if ( $logo_src ) {
					$logo_url = esc_url_raw( $logo_src[0] );
				}
			}
		}

		if ( $logo_url ) {
			// Skip SVG — jsPDF cannot render it.
			if ( ! preg_match( '/\.svg(\?.*)?$/i', $logo_url ) ) {
				$upload_dir    = wp_upload_dir();
				// Normalise protocol so http/https mismatch doesn't break str_replace.
				$logo_norm     = preg_replace( '#^https?://#i', '//', $logo_url );
				$base_norm     = preg_replace( '#^https?://#i', '//', $upload_dir['baseurl'] );
				$site_norm     = preg_replace( '#^https?://#i', '//', get_site_url() );
				$local_path    = str_replace(
					array( $base_norm, $site_norm ),
					array( $upload_dir['basedir'], rtrim( ABSPATH, '/\\' ) ),
					$logo_norm
				);

				// Strategy 1: read from local filesystem (fast, no HTTP).
				if ( file_exists( $local_path ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					$raw = file_get_contents( $local_path );
					if ( false !== $raw ) {
						$ext      = strtolower( pathinfo( $local_path, PATHINFO_EXTENSION ) );
						$mime_map = array( 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif' );
						$mime     = $mime_map[ $ext ] ?? 'image/jpeg';
						$logo_fmt = ( 'png' === $ext ) ? 'PNG' : ( ( 'webp' === $ext ) ? 'WEBP' : 'JPEG' );
						$logo_data = 'data:' . $mime . ';base64,' . base64_encode( $raw );
						$size      = @getimagesize( $local_path ); // phpcs:ignore
						if ( $size ) {
							$logo_w = (int) $size[0];
							$logo_h = (int) $size[1];
						}
					}
				}

				// Strategy 2: HTTP fetch fallback (CDN, external storage, path mismatch).
				if ( ! $logo_data ) {
					$response = wp_remote_get( $logo_url, array( 'timeout' => 8, 'sslverify' => false ) );
					if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
						$raw  = wp_remote_retrieve_body( $response );
						$mime = strtok( wp_remote_retrieve_header( $response, 'content-type' ), ';' );
						$mime = trim( $mime );
						$logo_fmt  = ( false !== strpos( $mime, 'png' ) ) ? 'PNG' : ( ( false !== strpos( $mime, 'webp' ) ) ? 'WEBP' : 'JPEG' );
						$logo_data = 'data:' . $mime . ';base64,' . base64_encode( $raw );
						$img_info  = @getimagesizefromstring( $raw ); // phpcs:ignore
						if ( $img_info ) {
							$logo_w = (int) $img_info[0];
							$logo_h = (int) $img_info[1];
						}
					}
				}
			}
		}

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'ms-stats-datatables', 'https://cdn.datatables.net/2.0.8/js/dataTables.min.js', array( 'jquery' ), '2.0.8', true );
		wp_enqueue_script( 'ms-stats-datatables-resp', 'https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js', array( 'ms-stats-datatables' ), '3.0.2', true );
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
				'logoData'     => $logo_data,
				'logoW'        => $logo_w,
				'logoH'        => $logo_h,
				'logoFmt'      => $logo_fmt,
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

	public function save_settings() {
		if ( ! isset( $_POST['_ms_stats_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_POST['_ms_stats_nonce'] ), 'ms_stats_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_POST['ms_stats_country_meta_key'] ) ) {
			update_option( 'ms_stats_country_meta_key', sanitize_key( $_POST['ms_stats_country_meta_key'] ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=ms-stats-for-bridge-project&tab=settings&saved=1' ) );
		exit;
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
