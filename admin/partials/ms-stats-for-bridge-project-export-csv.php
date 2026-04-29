<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';

$date_from_raw = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
$date_to_raw   = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';

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

function ms_stats_locale_name( $code ) {
	$map = array(
		'en_US' => 'English (United States)',
		'en_GB' => 'English (United Kingdom)',
		'en'    => 'English',
		'cs_CZ' => 'Czech',
		'de_DE' => 'German',
		'de_DE_formal' => 'German (Formal)',
		'pl_PL' => 'Polish',
		'uk'    => 'Ukrainian',
		'el'    => 'Greek',
		'ar'    => 'Arabic',
		'fr_FR' => 'French',
		'es_ES' => 'Spanish (Spain)',
		'es_MX' => 'Spanish (Mexico)',
		'it_IT' => 'Italian',
		'pt_BR' => 'Portuguese (Brazil)',
		'pt_PT' => 'Portuguese (Portugal)',
		'nl_NL' => 'Dutch',
		'ro_RO' => 'Romanian',
		'ru_RU' => 'Russian',
		'bg_BG' => 'Bulgarian',
		'hr'    => 'Croatian',
		'hu_HU' => 'Hungarian',
		'sk_SK' => 'Slovak',
		'sl_SI' => 'Slovenian',
		'lt_LT' => 'Lithuanian',
		'lv'    => 'Latvian',
		'et'    => 'Estonian',
		'fi'    => 'Finnish',
		'sv_SE' => 'Swedish',
		'da_DK' => 'Danish',
		'nb_NO' => 'Norwegian',
		'tr_TR' => 'Turkish',
		'he_IL' => 'Hebrew',
		'fa_IR' => 'Persian',
		'zh_CN' => 'Chinese (Simplified)',
		'zh_TW' => 'Chinese (Traditional)',
		'ja'    => 'Japanese',
		'ko_KR' => 'Korean',
	);
	return $map[ $code ] ?? $code;
}

$filenames = array(
	'overview'          => 'ms-stats-overview',
	'countries'         => 'ms-stats-users-by-country',
	'language'          => 'ms-stats-enrollments-by-language',
	'progress'          => 'ms-stats-course-progress',
	'quizzes'           => 'ms-stats-quiz-completion',
	'logins'            => 'ms-stats-login-activity',
	'certificates'      => 'ms-stats-certificates',
	'user_certificates' => 'ms-stats-certificates-per-user',
);

$filename = ( $filenames[ $tab ] ?? 'ms-stats-export' ) . '-' . gmdate( 'Y-m-d' ) . '.csv';

header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$out = fopen( 'php://output', 'w' );

// Date range header row.
if ( $date_from_raw || $date_to_raw ) {
	$range = '';
	if ( $date_from_raw && $date_to_raw ) {
		$range = 'Date range: ' . $date_from_raw . ' to ' . $date_to_raw;
	} elseif ( $date_from_raw ) {
		$range = 'From: ' . $date_from_raw;
	} else {
		$range = 'To: ' . $date_to_raw;
	}
	fputcsv( $out, array( $range ) );
	fputcsv( $out, array() );
}

if ( 'overview' === $tab ) {

	$total_enrollments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
	$total_users       = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
	$total_courses     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT course_id) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore

	fputcsv( $out, array( 'Metric', 'Value' ) );
	fputcsv( $out, array( 'Total Enrollments', $total_enrollments ) );
	fputcsv( $out, array( 'Enrolled Users', $total_users ) );
	fputcsv( $out, array( 'Courses with Enrollments', $total_courses ) );

} elseif ( 'countries' === $tab ) {

	$meta_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID AS user_id, um.meta_value
			 FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->prefix}stm_lms_user_courses uc ON uc.user_id = u.ID
			 LEFT JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
			 WHERE 1=1 $date_where_unix
			 GROUP BY u.ID", // phpcs:ignore
			'masterstudy_personal_data'
		)
	);

	$country_counts = array();
	foreach ( $meta_rows as $row ) {
		$data    = maybe_unserialize( $row->meta_value );
		$country = ( is_array( $data ) && ! empty( $data['country'] ) ) ? trim( $data['country'] ) : 'Not specified';
		$country_counts[ $country ] = ( $country_counts[ $country ] ?? 0 ) + 1;
	}
	arsort( $country_counts );

	fputcsv( $out, array( 'Country', 'Users' ) );
	foreach ( $country_counts as $country => $count ) {
		fputcsv( $out, array( $country, $count ) );
	}

} elseif ( 'language' === $tab ) {

	$rows = $wpdb->get_results(
		"SELECT lng_code, COUNT(*) AS total
		 FROM {$wpdb->prefix}stm_lms_user_courses
		 WHERE 1=1 $date_where_unix
		 GROUP BY lng_code ORDER BY total DESC" // phpcs:ignore
	);

	fputcsv( $out, array( 'Language', 'Enrollments' ) );
	foreach ( $rows as $row ) {
		fputcsv( $out, array( ms_stats_locale_name( $row->lng_code ), $row->total ) );
	}

} elseif ( 'progress' === $tab ) {

	$rows = $wpdb->get_results(
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

	fputcsv( $out, array( 'Course', 'Enrolled', 'Avg Progress %', 'Fully Completed', 'Completion Rate %' ) );
	foreach ( $rows as $row ) {
		fputcsv( $out, array(
			$row->course_title ?: 'Course #' . $row->course_id,
			$row->enrolled,
			$row->avg_progress,
			$row->completed,
			$row->completion_rate ?? 0,
		) );
	}

} elseif ( 'quizzes' === $tab ) {

	$rows = $wpdb->get_results(
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

	fputcsv( $out, array( 'Course', 'Unique Quizzes', 'Users Attempted', 'Total Attempts', 'Passed', 'Pass Rate %' ) );
	foreach ( $rows as $row ) {
		fputcsv( $out, array(
			$row->course_title ?: 'Course #' . $row->course_id,
			$row->total_quizzes,
			$row->users_attempted,
			$row->total_attempts,
			$row->passed_attempts,
			$row->pass_rate ?? 0,
		) );
	}

} elseif ( 'logins' === $tab ) {

	$enrolled_users = $wpdb->get_col(
		"SELECT DISTINCT user_id FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" // phpcs:ignore
	);

	$login_counts = array();
	$user_data    = array();

	if ( ! empty( $enrolled_users ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $enrolled_users ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$session_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, meta_value FROM {$wpdb->usermeta}
				 WHERE meta_key = 'session_tokens' AND user_id IN ($placeholders)",
				$enrolled_users
			)
		);
		foreach ( $session_rows as $sr ) {
			$tokens                       = maybe_unserialize( $sr->meta_value );
			$login_counts[ $sr->user_id ] = is_array( $tokens ) ? count( $tokens ) : 0;
		}
		foreach ( $enrolled_users as $uid ) {
			if ( ! isset( $login_counts[ $uid ] ) ) {
				$login_counts[ $uid ] = 0;
			}
		}
		arsort( $login_counts );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, display_name, user_email FROM {$wpdb->users} WHERE ID IN ($placeholders)",
				$enrolled_users
			)
		);
		foreach ( $users as $u ) {
			$user_data[ $u->ID ] = $u;
		}
	}

	fputcsv( $out, array( 'User', 'Email', 'Active Sessions' ) );
	foreach ( $login_counts as $uid => $count ) {
		$u = $user_data[ $uid ] ?? null;
		fputcsv( $out, array(
			$u ? $u->display_name : 'User #' . $uid,
			$u ? $u->user_email : '',
			$count,
		) );
	}

} elseif ( 'certificates' === $tab ) {

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
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title FROM {$wpdb->posts} WHERE ID IN ($placeholders)",
				$course_ids
			)
		);
		foreach ( $posts as $post ) {
			$course_titles[ $post->ID ] = $post->post_title;
		}
	}

	fputcsv( $out, array( 'Course', 'Certificates Issued' ) );
	foreach ( $cert_rows as $row ) {
		$course_id = (int) str_replace( 'stm_lms_certificate_code_', '', $row->meta_key );
		fputcsv( $out, array(
			$course_titles[ $course_id ] ?? 'Course #' . $course_id,
			$row->issued,
		) );
	}

} elseif ( 'user_certificates' === $tab ) {

	$cert_user_id = isset( $_GET['cert_user_id'] ) ? (int) $_GET['cert_user_id'] : 0;
	$uc_where     = 'WHERE um.meta_key LIKE %s AND um.meta_value != %s';
	$uc_params    = array( 'stm_lms_certificate_code_%', '' );
	if ( $cert_user_id ) {
		$uc_where    .= ' AND um.user_id = %d';
		$uc_params[]  = $cert_user_id;
	}

	// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT
				u.display_name,
				u.user_email,
				REPLACE(um.meta_key, 'stm_lms_certificate_code_', '') AS course_id,
				p.post_title AS course_title,
				um.meta_value AS certificate_code
			 FROM {$wpdb->usermeta} um
			 INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
			 LEFT JOIN {$wpdb->posts} p
			 	ON p.ID = CAST(REPLACE(um.meta_key, 'stm_lms_certificate_code_', '') AS UNSIGNED)
			 $uc_where
			 ORDER BY u.display_name ASC, p.post_title ASC",
			$uc_params
		)
	);
	// phpcs:enable

	fputcsv( $out, array( 'User', 'Email', 'Course', 'Certificate Code' ) );
	foreach ( $rows as $row ) {
		fputcsv( $out, array(
			$row->display_name,
			$row->user_email,
			$row->course_title ?: 'Course #' . $row->course_id,
			$row->certificate_code,
		) );
	}
}

fclose( $out );
