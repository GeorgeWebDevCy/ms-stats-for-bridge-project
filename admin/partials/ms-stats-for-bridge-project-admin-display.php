<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
$page_slug  = 'ms-stats';

$date_from_raw = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
$date_to_raw   = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
$display_from  = $date_from_raw ? date( 'd/m/Y', strtotime( $date_from_raw ) ) : '';
$display_to    = $date_to_raw ? date( 'd/m/Y', strtotime( $date_to_raw ) ) : '';

$ts_from = $date_from_raw ? strtotime( $date_from_raw . ' 00:00:00' ) : 0;
$ts_to   = $date_to_raw ? strtotime( $date_to_raw . ' 23:59:59' ) : 0;

$date_where_unix = '';
if ( $ts_from && $ts_to ) {
	$date_where_unix = $wpdb->prepare( ' AND start_time BETWEEN %d AND %d ', $ts_from, $ts_to );
} elseif ( $ts_from ) {
	$date_where_unix = $wpdb->prepare( ' AND start_time >= %d ', $ts_from );
} elseif ( $ts_to ) {
	$date_where_unix = $wpdb->prepare( ' AND start_time <= %d ', $ts_to );
}

$date_where_datetime = '';
if ( $ts_from && $ts_to ) {
	$date_where_datetime = $wpdb->prepare( ' AND created_at BETWEEN %s AND %s ', gmdate( 'Y-m-d 00:00:00', $ts_from ), gmdate( 'Y-m-d 23:59:59', $ts_to ) );
} elseif ( $ts_from ) {
	$date_where_datetime = $wpdb->prepare( ' AND created_at >= %s ', gmdate( 'Y-m-d 00:00:00', $ts_from ) );
} elseif ( $ts_to ) {
	$date_where_datetime = $wpdb->prepare( ' AND created_at <= %s ', gmdate( 'Y-m-d 23:59:59', $ts_to ) );
}

$cert_user_id = isset( $_GET['cert_user_id'] ) ? (int) $_GET['cert_user_id'] : 0;

// Brand colours for inline bar styles (avoids CSS interpolation issues).
$_stm          = get_option( 'stm_lms_settings', array() );
$ms_bar_blue   = sanitize_hex_color( $_stm['main_color'] ?? '' ) ?: '#385bce';
$ms_bar_green  = sanitize_hex_color( $_stm['secondary_color'] ?? '' ) ?: '#17d292';

$cert_users = array();
if ( 'user_certificates' === $active_tab ) {
	$cert_users = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT DISTINCT u.ID, u.display_name
			 FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
			 WHERE um.meta_key LIKE %s AND um.meta_value != ''
			 ORDER BY u.display_name ASC",
			'stm_lms_certificate_code_%'
		)
	);
}

function ms_stats_locale_name( $code ) {
	$map = array(
		'en_US' => 'English (United States)', 'en_GB' => 'English (United Kingdom)', 'en' => 'English',
		'cs_CZ' => 'Czech', 'de_DE' => 'German', 'de_DE_formal' => 'German (Formal)',
		'pl_PL' => 'Polish', 'uk' => 'Ukrainian', 'el' => 'Greek', 'ar' => 'Arabic',
		'fr_FR' => 'French', 'es_ES' => 'Spanish (Spain)', 'es_MX' => 'Spanish (Mexico)',
		'it_IT' => 'Italian', 'pt_BR' => 'Portuguese (Brazil)', 'pt_PT' => 'Portuguese (Portugal)',
		'nl_NL' => 'Dutch', 'ro_RO' => 'Romanian', 'ru_RU' => 'Russian', 'bg_BG' => 'Bulgarian',
		'hr' => 'Croatian', 'hu_HU' => 'Hungarian', 'sk_SK' => 'Slovak', 'sl_SI' => 'Slovenian',
		'lt_LT' => 'Lithuanian', 'lv' => 'Latvian', 'et' => 'Estonian', 'fi' => 'Finnish',
		'sv_SE' => 'Swedish', 'da_DK' => 'Danish', 'nb_NO' => 'Norwegian',
		'tr_TR' => 'Turkish', 'he_IL' => 'Hebrew', 'fa_IR' => 'Persian',
		'zh_CN' => 'Chinese (Simplified)', 'zh_TW' => 'Chinese (Traditional)',
		'ja' => 'Japanese', 'ko_KR' => 'Korean',
	);
	return $map[ $code ] ?? $code;
}

function ms_stats_export_url( $tab ) {
	$extra = '';
	if ( ! empty( $_GET['date_from'] ) ) {
		$extra .= '&date_from=' . rawurlencode( sanitize_text_field( $_GET['date_from'] ) );
	}
	if ( ! empty( $_GET['date_to'] ) ) {
		$extra .= '&date_to=' . rawurlencode( sanitize_text_field( $_GET['date_to'] ) );
	}
	if ( ! empty( $_GET['cert_user_id'] ) ) {
		$extra .= '&cert_user_id=' . (int) $_GET['cert_user_id'];
	}
	return wp_nonce_url(
		admin_url( 'admin.php?page=ms-stats&tab=' . rawurlencode( $tab ) . '&export=csv' . $extra ),
		'ms_stats_export'
	);
}

function ms_stats_pdf_btn( $table_id, $title ) {
	printf(
		'<button class="button button-pdf ms-stats-export-pdf" data-table="%s" data-title="%s">&#128196; PDF</button>',
		esc_attr( $table_id ),
		esc_attr( $title )
	);
}

function ms_stats_section_header( $title, $table_id, $csv_tab ) {
	echo '<div class="ms-stats-section-header">';
	echo '<h2>' . esc_html( $title ) . '</h2>';
	echo '<div class="ms-stats-export-buttons">';
	echo '<a href="' . esc_url( ms_stats_export_url( $csv_tab ) ) . '" class="button button-secondary">&#11015; CSV</a>';
	ms_stats_pdf_btn( $table_id, $title );
	echo '</div></div>';
}

$tabs = array(
	'overview'          => __( 'Overview', 'ms-stats' ),
	'countries'         => __( 'Users by Country', 'ms-stats' ),
	'language'          => __( 'Enrollments by Language', 'ms-stats' ),
	'progress'          => __( 'Course Progress', 'ms-stats' ),
	'quizzes'           => __( 'Quiz Completion', 'ms-stats' ),
	'certificates'      => __( 'Certificates', 'ms-stats' ),
	'user_certificates' => __( 'Certificates per User', 'ms-stats' ),
	'settings'          => __( 'Settings', 'ms-stats' ),
);

$base_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=' . $active_tab );

?>
<div class="wrap ms-stats-page">
	<h1><?php echo esc_html( 'MS LMS Stats — ' . get_option( 'ms_stats_report_label', get_bloginfo( 'name' ) ) ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=' . $tab_key ) ); ?>"
			   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="ms-stats-tab-panel">

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ms-stats-filter-bar">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
			<input type="hidden" name="tab"  value="<?php echo esc_attr( $active_tab ); ?>">
			<label for="ms_date_from_display"><?php esc_html_e( 'From', 'ms-stats' ); ?></label>
			<input type="text" id="ms_date_from_display" class="ms-stats-datepicker" placeholder="dd/mm/yyyy" autocomplete="off" data-alt-field="#ms_date_from" value="<?php echo esc_attr( $display_from ); ?>">
			<input type="hidden" id="ms_date_from" name="date_from" value="<?php echo esc_attr( $date_from_raw ); ?>">
			<label for="ms_date_to_display"><?php esc_html_e( 'To', 'ms-stats' ); ?></label>
			<input type="text" id="ms_date_to_display" class="ms-stats-datepicker" placeholder="dd/mm/yyyy" autocomplete="off" data-alt-field="#ms_date_to" value="<?php echo esc_attr( $display_to ); ?>">
			<input type="hidden" id="ms_date_to" name="date_to" value="<?php echo esc_attr( $date_to_raw ); ?>">
			<?php if ( 'user_certificates' === $active_tab ) : ?>
				<label for="ms_cert_user"><?php esc_html_e( 'User', 'ms-stats' ); ?></label>
				<select id="ms_cert_user" name="cert_user_id" style="border:1px solid #c3c4c7;border-radius:3px;padding:4px 8px;">
					<option value=""><?php esc_html_e( '— All —', 'ms-stats' ); ?></option>
					<?php foreach ( $cert_users as $cu ) : ?>
						<option value="<?php echo esc_attr( $cu->ID ); ?>" <?php selected( $cert_user_id, $cu->ID ); ?>><?php echo esc_html( $cu->display_name ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ms-stats' ); ?></button>
			<?php if ( $date_from_raw || $date_to_raw || $cert_user_id ) : ?>
				<a href="<?php echo esc_url( $base_url ); ?>" class="button button-link"><?php esc_html_e( 'Clear', 'ms-stats' ); ?></a>
			<?php endif; ?>
		</form>

		<?php if ( 'overview' === $active_tab ) : ?>

			<?php
			$total_enrollments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
			$total_users       = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
			$total_courses     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT course_id) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore

			// Total published courses in the default site language.
			$icl_table = $wpdb->prefix . 'icl_translations';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $icl_table ) ) ) {
				// WPML active: count courses that are the original/default language version.
				$total_site_courses = (int) $wpdb->get_var(
					"SELECT COUNT(DISTINCT p.ID)
					 FROM {$wpdb->posts} p
					 INNER JOIN {$icl_table} t
					        ON t.element_id = p.ID
					        AND t.element_type = 'post_stm-courses'
					        AND t.source_language_code IS NULL
					 WHERE p.post_type = 'stm-courses'
					   AND p.post_status = 'publish'"
				);
			} else {
				$total_site_courses = (int) $wpdb->get_var(
					"SELECT COUNT(ID) FROM {$wpdb->posts}
					 WHERE post_type = 'stm-courses' AND post_status = 'publish'"
				);
			}
			?>
			<div class="ms-stats-section-header">
				<h2><?php esc_html_e( 'Enrollment Overview', 'ms-stats' ); ?></h2>
				<div class="ms-stats-export-buttons">
					<a href="<?php echo esc_url( ms_stats_export_url( 'overview' ) ); ?>" class="button button-secondary">&#11015; CSV</a>
					<?php ms_stats_pdf_btn( 'ms-stats-table-overview', __( 'Enrollment Overview', 'ms-stats' ) ); ?>
				</div>
			</div>
			<div class="ms-stats-cards">
				<?php foreach ( array(
					array( 'label' => __( 'Total Enrollments', 'ms-stats' ),       'value' => $total_enrollments ),
					array( 'label' => __( 'Enrolled Users', 'ms-stats' ),          'value' => $total_users ),
					array( 'label' => __( 'Courses with Enrollments', 'ms-stats' ), 'value' => $total_courses ),
					array( 'label' => __( 'Total Courses (default language)', 'ms-stats' ), 'value' => $total_site_courses ),
				) as $stat ) : ?>
					<div class="ms-stats-card">
						<div class="ms-stats-card-value"><?php echo esc_html( $stat['value'] ); ?></div>
						<div class="ms-stats-card-label"><?php echo esc_html( $stat['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
			<table id="ms-stats-table-overview" class="ms-stats-table widefat" style="display:none;">
				<thead><tr><th><?php esc_html_e( 'Metric', 'ms-stats' ); ?></th><th><?php esc_html_e( 'Value', 'ms-stats' ); ?></th></tr></thead>
				<tbody>
					<tr><td><?php esc_html_e( 'Total Enrollments', 'ms-stats' ); ?></td><td><?php echo esc_html( $total_enrollments ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Enrolled Users', 'ms-stats' ); ?></td><td><?php echo esc_html( $total_users ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Courses with Enrollments', 'ms-stats' ); ?></td><td><?php echo esc_html( $total_courses ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Total Courses (default language)', 'ms-stats' ); ?></td><td><?php echo esc_html( $total_site_courses ); ?></td></tr>
				</tbody>
			</table>

		<?php elseif ( 'countries' === $active_tab ) : ?>

			<?php
			$country_meta_key = ms_stats_resolve_country_meta_key( get_option( 'ms_stats_country_meta_key', 'ms-country' ) );
			$country_rows     = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT LOWER(TRIM(um.meta_value)) AS country_val,
					        COUNT(DISTINCT uc.user_id) AS total
					 FROM {$wpdb->prefix}stm_lms_user_courses uc
					 INNER JOIN {$wpdb->usermeta} um
					        ON um.user_id = uc.user_id AND um.meta_key = %s
					 WHERE um.meta_value IS NOT NULL
					   AND TRIM(um.meta_value) != ''
					   $date_where_unix
					 GROUP BY country_val
					 ORDER BY total DESC", // phpcs:ignore
					$country_meta_key
				)
			);
			?>
			<?php ms_stats_section_header( __( 'Users by Country', 'ms-stats' ), 'ms-stats-table-countries', 'countries' ); ?>
			<table id="ms-stats-table-countries" class="ms-stats-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Country', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Users', 'ms-stats' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $country_rows ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No data.', 'ms-stats' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $country_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( mb_convert_case( $row->country_val, MB_CASE_TITLE, 'UTF-8' ) ); ?></td>
								<td><?php echo esc_html( $row->total ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<tfoot><tr>
					<th><?php esc_html_e( 'Total', 'ms-stats' ); ?></th>
					<th data-sum-col="1"><?php echo esc_html( array_sum( array_map( fn( $r ) => (int) $r->total, $country_rows ) ) ); ?></th>
				</tr></tfoot>
			</table>

		<?php elseif ( 'language' === $active_tab ) : ?>

			<?php
			$lang_rows = $wpdb->get_results(
				"SELECT lng_code, COUNT(*) AS total
				 FROM {$wpdb->prefix}stm_lms_user_courses
				 WHERE 1=1 $date_where_unix
				 GROUP BY lng_code ORDER BY total DESC" // phpcs:ignore
			);
			?>
			<?php ms_stats_section_header( __( 'Enrollments by Language', 'ms-stats' ), 'ms-stats-table-language', 'language' ); ?>
			<table id="ms-stats-table-language" class="ms-stats-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Language', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Enrollments', 'ms-stats' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $lang_rows ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No data.', 'ms-stats' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $lang_rows as $row ) : ?>
							<tr><td><?php echo esc_html( ms_stats_locale_name( $row->lng_code ) ); ?></td><td><?php echo esc_html( $row->total ); ?></td></tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<tfoot><tr>
					<th><?php esc_html_e( 'Total', 'ms-stats' ); ?></th>
					<th data-sum-col="1"><?php echo esc_html( array_sum( array_map( fn( $r ) => (int) $r->total, $lang_rows ) ) ); ?></th>
				</tr></tfoot>
			</table>

		<?php elseif ( 'progress' === $active_tab ) : ?>

			<?php
			$progress_rows = $wpdb->get_results(
				"SELECT
					uc.course_id,
					p.post_title AS course_title,
					COUNT(DISTINCT uc.user_id) AS enrolled,
					ROUND(AVG(uc.progress_percent), 1) AS avg_progress,
					SUM(CASE WHEN uc.progress_percent = 100 THEN 1 ELSE 0 END) AS completed,
					ROUND(
						SUM(CASE WHEN uc.progress_percent = 100 THEN 1 ELSE 0 END) * 100.0
						/ NULLIF(COUNT(DISTINCT uc.user_id), 0),
					1) AS completion_rate
				 FROM {$wpdb->prefix}stm_lms_user_courses uc
				 LEFT JOIN {$wpdb->posts} p ON p.ID = uc.course_id
				 WHERE 1=1 $date_where_unix
				 GROUP BY uc.course_id, p.post_title
				 ORDER BY completion_rate DESC" // phpcs:ignore
			);
			?>
			<?php ms_stats_section_header( __( 'Course Completion %', 'ms-stats' ), 'ms-stats-table-progress', 'progress' ); ?>
			<table id="ms-stats-table-progress" class="ms-stats-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Course', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Enrolled', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Avg Progress', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Fully Done', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Completion Rate', 'ms-stats' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $progress_rows ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No data.', 'ms-stats' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $progress_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->course_title ?: 'Course #' . $row->course_id ); ?></td>
								<td><?php echo esc_html( $row->enrolled ); ?></td>
								<td><?php echo esc_html( $row->avg_progress ); ?>%</td>
								<td><?php echo esc_html( $row->completed ); ?></td>
								<td data-pdf-val="<?php echo esc_attr( $row->completion_rate ?? 0 ); ?>%">
									<div class="ms-stats-bar-wrap">
										<div class="ms-stats-bar"><div class="ms-stats-bar-fill" style="width:<?php echo esc_attr( min( 100, (float) $row->completion_rate ) ); ?>%;background:<?php echo esc_attr( $ms_bar_blue ); ?>"></div></div>
										<?php echo esc_html( $row->completion_rate ?? 0 ); ?>%
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<?php if ( ! empty( $progress_rows ) ) :
					$p_enrolled  = array_sum( array_map( fn( $r ) => (int) $r->enrolled, $progress_rows ) );
					$p_completed = array_sum( array_map( fn( $r ) => (int) $r->completed, $progress_rows ) );
					$p_avg_prog  = count( $progress_rows ) ? round( array_sum( array_map( fn( $r ) => (float) $r->avg_progress, $progress_rows ) ) / count( $progress_rows ), 1 ) : 0;
					$p_comp_rate = $p_enrolled > 0 ? round( $p_completed / $p_enrolled * 100, 1 ) : 0;
				?>
				<tfoot><tr>
					<th><?php esc_html_e( 'Total', 'ms-stats' ); ?></th>
					<th data-sum-col="1"><?php echo esc_html( $p_enrolled ); ?></th>
					<th data-avg-col="2"><?php echo esc_html( $p_avg_prog ); ?>%</th>
					<th data-sum-col="3"><?php echo esc_html( $p_completed ); ?></th>
					<th data-rate-cols="3/1"><?php echo esc_html( $p_comp_rate ); ?>%</th>
				</tr></tfoot>
				<?php endif; ?>
			</table>

		<?php elseif ( 'quizzes' === $active_tab ) : ?>

			<?php
			$quiz_rows = $wpdb->get_results(
				"SELECT
					uq.course_id,
					p.post_title AS course_title,
					COUNT(DISTINCT uq.quiz_id) AS total_quizzes,
					COUNT(DISTINCT uq.user_id) AS users_attempted,
					COUNT(*) AS total_attempts,
					SUM(CASE WHEN uq.status = 'passed' THEN 1 ELSE 0 END) AS passed_attempts,
					ROUND(
						SUM(CASE WHEN uq.status = 'passed' THEN 1 ELSE 0 END) * 100.0
						/ NULLIF(COUNT(*), 0),
					1) AS pass_rate
				 FROM {$wpdb->prefix}stm_lms_user_quizzes uq
				 LEFT JOIN {$wpdb->posts} p ON p.ID = uq.course_id
				 WHERE 1=1 $date_where_datetime
				 GROUP BY uq.course_id, p.post_title
				 ORDER BY pass_rate DESC" // phpcs:ignore
			);
			?>
			<?php ms_stats_section_header( __( 'Quiz Completion per Course', 'ms-stats' ), 'ms-stats-table-quizzes', 'quizzes' ); ?>
			<table id="ms-stats-table-quizzes" class="ms-stats-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Course', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Quizzes', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Users Attempted', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Total Attempts', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Passed', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Pass Rate', 'ms-stats' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $quiz_rows ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No data.', 'ms-stats' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $quiz_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->course_title ?: 'Course #' . $row->course_id ); ?></td>
								<td><?php echo esc_html( $row->total_quizzes ); ?></td>
								<td><?php echo esc_html( $row->users_attempted ); ?></td>
								<td><?php echo esc_html( $row->total_attempts ); ?></td>
								<td><?php echo esc_html( $row->passed_attempts ); ?></td>
								<td data-pdf-val="<?php echo esc_attr( $row->pass_rate ?? 0 ); ?>%">
									<div class="ms-stats-bar-wrap">
										<div class="ms-stats-bar"><div class="ms-stats-bar-fill" style="width:<?php echo esc_attr( min( 100, (float) $row->pass_rate ) ); ?>%;background:<?php echo esc_attr( $ms_bar_green ); ?>"></div></div>
										<?php echo esc_html( $row->pass_rate ?? 0 ); ?>%
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<?php if ( ! empty( $quiz_rows ) ) :
					$q_quizzes   = array_sum( array_map( fn( $r ) => (int) $r->total_quizzes, $quiz_rows ) );
					$q_attempted = array_sum( array_map( fn( $r ) => (int) $r->users_attempted, $quiz_rows ) );
					$q_total     = array_sum( array_map( fn( $r ) => (int) $r->total_attempts, $quiz_rows ) );
					$q_passed    = array_sum( array_map( fn( $r ) => (int) $r->passed_attempts, $quiz_rows ) );
					$q_rate      = $q_total > 0 ? round( $q_passed / $q_total * 100, 1 ) : 0;
				?>
				<tfoot><tr>
					<th><?php esc_html_e( 'Total', 'ms-stats' ); ?></th>
					<th data-sum-col="1"><?php echo esc_html( $q_quizzes ); ?></th>
					<th data-sum-col="2"><?php echo esc_html( $q_attempted ); ?></th>
					<th data-sum-col="3"><?php echo esc_html( $q_total ); ?></th>
					<th data-sum-col="4"><?php echo esc_html( $q_passed ); ?></th>
					<th data-rate-cols="4/3"><?php echo esc_html( $q_rate ); ?>%</th>
				</tr></tfoot>
				<?php endif; ?>
			</table>

		<?php elseif ( 'certificates' === $active_tab ) : ?>

			<?php
			$cert_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, COUNT(*) AS issued
					 FROM {$wpdb->usermeta}
					 WHERE meta_key LIKE %s AND meta_value != ''
					 GROUP BY meta_key ORDER BY issued DESC",
					'stm_lms_certificate_code_%'
				)
			);
			$course_ids = array_map( function( $r ) {
				return (int) str_replace( 'stm_lms_certificate_code_', '', $r->meta_key );
			}, $cert_rows );
			$course_titles = array();
			if ( ! empty( $course_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ($placeholders)", $course_ids ) );
				foreach ( $posts as $post ) { $course_titles[ $post->ID ] = $post->post_title; }
			}
			?>
			<?php ms_stats_section_header( __( 'Certificates Issued per Course', 'ms-stats' ), 'ms-stats-table-certificates', 'certificates' ); ?>
			<table id="ms-stats-table-certificates" class="ms-stats-table widefat">
				<thead><tr>
					<th><?php esc_html_e( 'Course', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Certificates Issued', 'ms-stats' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $cert_rows ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No data.', 'ms-stats' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $cert_rows as $row ) :
							$cid   = (int) str_replace( 'stm_lms_certificate_code_', '', $row->meta_key );
							$title = $course_titles[ $cid ] ?? 'Course #' . $cid;
							?>
							<tr><td><?php echo esc_html( $title ); ?></td><td><?php echo esc_html( $row->issued ); ?></td></tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<tfoot><tr>
					<th><?php esc_html_e( 'Total', 'ms-stats' ); ?></th>
					<th data-sum-col="1"><?php echo esc_html( array_sum( array_map( fn( $r ) => (int) $r->issued, $cert_rows ) ) ); ?></th>
				</tr></tfoot>
			</table>

		<?php elseif ( 'user_certificates' === $active_tab ) : ?>

			<?php
			$uc_where  = 'WHERE um.meta_key LIKE %s AND um.meta_value != %s';
			$uc_params = array( 'stm_lms_certificate_code_%', '' );
			if ( $cert_user_id ) {
				$uc_where   .= ' AND um.user_id = %d';
				$uc_params[] = $cert_user_id;
			}
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$user_cert_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT u.ID AS user_id, u.display_name, u.user_email,
					 REPLACE(um.meta_key, 'stm_lms_certificate_code_', '') AS course_id,
					 p.post_title AS course_title, um.meta_value AS certificate_code
					 FROM {$wpdb->usermeta} um
					 INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
					 LEFT JOIN {$wpdb->posts} p ON p.ID = CAST(REPLACE(um.meta_key, 'stm_lms_certificate_code_', '') AS UNSIGNED)
					 $uc_where ORDER BY u.display_name ASC, p.post_title ASC",
					$uc_params
				)
			);
			// phpcs:enable
			$grouped_certs = array();
			foreach ( $user_cert_rows as $row ) { $grouped_certs[ $row->user_id ][] = $row; }
			?>
			<div class="ms-stats-section-header">
				<h2><?php esc_html_e( 'Certificates per User', 'ms-stats' ); ?></h2>
				<div class="ms-stats-export-buttons">
					<a href="<?php echo esc_url( ms_stats_export_url( 'user_certificates' ) ); ?>" class="button button-secondary">&#11015; CSV</a>
					<?php ms_stats_pdf_btn( 'ms-stats-table-usercerts', __( 'Certificates per User', 'ms-stats' ) ); ?>
				</div>
			</div>
			<p style="color:#50575e;margin-top:-8px;margin-bottom:14px;font-size:12px;"><?php esc_html_e( 'Date filter does not apply — no date column on certificate records.', 'ms-stats' ); ?></p>
			<table id="ms-stats-table-usercerts" class="widefat fixed striped">
				<thead><tr>
					<th style="width:160px;"><?php esc_html_e( 'User', 'ms-stats' ); ?></th>
					<th style="width:200px;"><?php esc_html_e( 'Email', 'ms-stats' ); ?></th>
					<th><?php esc_html_e( 'Course', 'ms-stats' ); ?></th>
					<th style="width:180px;"><?php esc_html_e( 'Certificate Code', 'ms-stats' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $grouped_certs ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No certificates found.', 'ms-stats' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $grouped_certs as $uid => $rows ) :
							$count = count( $rows ); $first = true;
							foreach ( $rows as $row ) : ?>
								<tr>
									<?php if ( $first ) : ?>
										<td rowspan="<?php echo esc_attr( $count ); ?>" style="vertical-align:top;border-right:2px solid #dcdcde;">
											<span style="font-weight:600;"><?php echo esc_html( $row->display_name ); ?></span>
											<br><small style="color:#6b7280;font-weight:400;"><?php echo esc_html( $count ); ?> <?php echo esc_html( $count === 1 ? __( 'certificate', 'ms-stats' ) : __( 'certificates', 'ms-stats' ) ); ?></small>
										</td>
										<td rowspan="<?php echo esc_attr( $count ); ?>" style="vertical-align:top;border-right:2px solid #dcdcde;"><?php echo esc_html( $row->user_email ); ?></td>
										<?php $first = false; ?>
									<?php endif; ?>
									<td><?php echo esc_html( $row->course_title ?: 'Course #' . $row->course_id ); ?></td>
									<td><code class="ms-cert-code"><?php echo esc_html( $row->certificate_code ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

		<?php elseif ( 'settings' === $active_tab ) : ?>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'ms-stats' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Plugin Settings', 'ms-stats' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=settings' ) ); ?>">
				<?php wp_nonce_field( 'ms_stats_save_settings', '_ms_stats_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="ms_stats_report_label"><?php esc_html_e( 'Report Label', 'ms-stats' ); ?></label>
						</th>
						<td>
							<input type="text" id="ms_stats_report_label" name="ms_stats_report_label"
							       value="<?php echo esc_attr( get_option( 'ms_stats_report_label', get_bloginfo( 'name' ) ) ); ?>"
							       class="regular-text">
							<p class="description">
								<?php esc_html_e( 'Shown in the admin page title and PDF footer. Defaults to the site name.', 'ms-stats' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="ms_stats_pdf_logo_url"><?php esc_html_e( 'PDF Logo URL', 'ms-stats' ); ?></label>
						</th>
						<td>
							<input type="url" id="ms_stats_pdf_logo_url" name="ms_stats_pdf_logo_url"
							       value="<?php echo esc_attr( get_option( 'ms_stats_pdf_logo_url', '' ) ); ?>"
							       class="large-text" placeholder="https://example.com/logo.png">
							<p class="description">
								<?php esc_html_e( 'PNG or JPEG URL used in PDF exports. Takes priority over the site logo. Copy the URL from your Media Library.', 'ms-stats' ); ?>
							</p>
							<?php
							$current_pdf_logo = get_option( 'ms_stats_pdf_logo_url', '' );
							if ( $current_pdf_logo ) :
								?>
								<p style="margin-top:8px;">
									<img src="<?php echo esc_url( $current_pdf_logo ); ?>" alt="" style="max-height:60px;max-width:200px;border:1px solid #dcdcde;border-radius:4px;padding:4px;background:#fff;">
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="ms_stats_country_meta_key"><?php esc_html_e( 'Country meta key', 'ms-stats' ); ?></label>
						</th>
						<td>
							<input type="text" id="ms_stats_country_meta_key" name="ms_stats_country_meta_key"
							       value="<?php echo esc_attr( get_option( 'ms_stats_country_meta_key', 'ms-country' ) ); ?>"
							       class="regular-text" placeholder="ms-country">
							<p class="description">
								<?php esc_html_e( 'Enter the Field ID from MasterStudy Forms Editor (e.g. ms-country). The plugin resolves it to the real meta key automatically. You can also enter the raw meta key directly.', 'ms-stats' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'ms-stats' ) ); ?>
			</form>

		<?php endif; ?>

	</div><!-- .ms-stats-tab-panel -->
</div><!-- .wrap -->
