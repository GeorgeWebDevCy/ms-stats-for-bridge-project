<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
$page_slug  = 'ms-stats-for-bridge-project';

// Date range — stored/passed as Y-m-d, converted to unix timestamps for queries.
$date_from_raw  = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
$date_to_raw    = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
// Display values in dd/mm/yyyy for the datepicker text inputs.
$display_from   = $date_from_raw ? date( 'd/m/Y', strtotime( $date_from_raw ) ) : '';
$display_to     = $date_to_raw ? date( 'd/m/Y', strtotime( $date_to_raw ) ) : '';

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

function ms_stats_export_url( $tab ) {
	$extra = '';
	if ( ! empty( $_GET['date_from'] ) ) {
		$extra .= '&date_from=' . rawurlencode( sanitize_text_field( $_GET['date_from'] ) );
	}
	if ( ! empty( $_GET['date_to'] ) ) {
		$extra .= '&date_to=' . rawurlencode( sanitize_text_field( $_GET['date_to'] ) );
	}
	return wp_nonce_url(
		admin_url( 'admin.php?page=ms-stats-for-bridge-project&tab=' . rawurlencode( $tab ) . '&export=csv' . $extra ),
		'ms_stats_export'
	);
}

$tabs = array(
	'overview'     => __( 'Overview', 'ms-stats-for-bridge-project' ),
	'countries'    => __( 'Users by Country', 'ms-stats-for-bridge-project' ),
	'language'     => __( 'Enrollments by Language', 'ms-stats-for-bridge-project' ),
	'progress'     => __( 'Course Progress', 'ms-stats-for-bridge-project' ),
	'quizzes'      => __( 'Quiz Completion', 'ms-stats-for-bridge-project' ),
	'logins'       => __( 'Login Activity', 'ms-stats-for-bridge-project' ),
	'certificates' => __( 'Certificates', 'ms-stats-for-bridge-project' ),
);

?>
<div class="wrap">
	<h1><?php esc_html_e( 'MS LMS Stats — Bridge Project', 'ms-stats-for-bridge-project' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug . '&tab=' . $tab_key ) ); ?>"
			   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="tab-content" style="margin-top:20px;">

		<?php
		// Date range filter form — shown on every tab.
		$base_url = admin_url( 'admin.php?page=' . $page_slug . '&tab=' . $active_tab );
		?>
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
			<input type="hidden" name="tab"  value="<?php echo esc_attr( $active_tab ); ?>">
			<label for="ms_date_from_display" style="font-weight:600;"><?php esc_html_e( 'From', 'ms-stats-for-bridge-project' ); ?></label>
			<input type="text" id="ms_date_from_display" class="ms-stats-datepicker" placeholder="dd/mm/yyyy" autocomplete="off" data-alt-field="#ms_date_from" value="<?php echo esc_attr( $display_from ); ?>" style="width:110px;border:1px solid #c3c4c7;border-radius:3px;padding:4px 8px;">
			<input type="hidden" id="ms_date_from" name="date_from" value="<?php echo esc_attr( $date_from_raw ); ?>">
			<label for="ms_date_to_display" style="font-weight:600;"><?php esc_html_e( 'To', 'ms-stats-for-bridge-project' ); ?></label>
			<input type="text" id="ms_date_to_display" class="ms-stats-datepicker" placeholder="dd/mm/yyyy" autocomplete="off" data-alt-field="#ms_date_to" value="<?php echo esc_attr( $display_to ); ?>" style="width:110px;border:1px solid #c3c4c7;border-radius:3px;padding:4px 8px;">
			<input type="hidden" id="ms_date_to" name="date_to" value="<?php echo esc_attr( $date_to_raw ); ?>">
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'ms-stats-for-bridge-project' ); ?></button>
			<?php if ( $date_from_raw || $date_to_raw ) : ?>
				<a href="<?php echo esc_url( $base_url ); ?>" class="button button-link"><?php esc_html_e( 'Clear', 'ms-stats-for-bridge-project' ); ?></a>
			<?php endif; ?>
			<?php if ( $date_from_raw || $date_to_raw ) : ?>
				<em style="color:#50575e;">
					<?php
					if ( $date_from_raw && $date_to_raw ) {
						printf(
							/* translators: 1: from date, 2: to date */
							esc_html__( 'Showing: %1$s — %2$s', 'ms-stats-for-bridge-project' ),
							esc_html( date_i18n( $wp_date_format, $ts_from ) ),
							esc_html( date_i18n( $wp_date_format, $ts_to ) )
						);
					} elseif ( $date_from_raw ) {
						printf( esc_html__( 'From: %s', 'ms-stats-for-bridge-project' ), esc_html( date_i18n( $wp_date_format, $ts_from ) ) );
					} else {
						printf( esc_html__( 'To: %s', 'ms-stats-for-bridge-project' ), esc_html( date_i18n( $wp_date_format, $ts_to ) ) );
					}
					?>
				</em>
			<?php endif; ?>
		</form>

		<?php if ( 'overview' === $active_tab ) : ?>

			<?php
			$total_enrollments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
			$total_users       = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
			$total_courses     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT course_id) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 $date_where_unix" ); // phpcs:ignore
			?>
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'overview' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<div style="display:flex;gap:20px;margin-bottom:30px;flex-wrap:wrap;">
				<?php foreach ( array(
					array( 'label' => __( 'Total Enrollments', 'ms-stats-for-bridge-project' ),       'value' => $total_enrollments ),
					array( 'label' => __( 'Enrolled Users', 'ms-stats-for-bridge-project' ),          'value' => $total_users ),
					array( 'label' => __( 'Courses with Enrollments', 'ms-stats-for-bridge-project' ), 'value' => $total_courses ),
				) as $stat ) : ?>
					<div style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:20px 30px;text-align:center;min-width:160px;">
						<div style="font-size:2.5em;font-weight:700;color:#2271b1;"><?php echo esc_html( $stat['value'] ); ?></div>
						<div style="color:#50575e;margin-top:6px;"><?php echo esc_html( $stat['label'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

		<?php elseif ( 'countries' === $active_tab ) : ?>

			<?php
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
				$country = ( is_array( $data ) && ! empty( $data['country'] ) ) ? trim( $data['country'] ) : __( 'Not specified', 'ms-stats-for-bridge-project' );
				$country_counts[ $country ] = ( $country_counts[ $country ] ?? 0 ) + 1;
			}
			arsort( $country_counts );
			?>
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'countries' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<h2><?php esc_html_e( 'Total Users per Country', 'ms-stats-for-bridge-project' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Country', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Users', 'ms-stats-for-bridge-project' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $country_counts ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No data.', 'ms-stats-for-bridge-project' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $country_counts as $country => $count ) : ?>
							<tr>
								<td><?php echo esc_html( $country ); ?></td>
								<td><?php echo esc_html( $count ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
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
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'language' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<h2><?php esc_html_e( 'Total Enrollments per Language', 'ms-stats-for-bridge-project' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Language Code', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Enrollments', 'ms-stats-for-bridge-project' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $lang_rows ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No data.', 'ms-stats-for-bridge-project' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $lang_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->lng_code ); ?></td>
								<td><?php echo esc_html( $row->total ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
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
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'progress' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<h2><?php esc_html_e( 'Course Completion %', 'ms-stats-for-bridge-project' ); ?></h2>
			<p style="color:#50575e;"><?php esc_html_e( 'progress_percent includes quiz grades as weighted by MasterStudy LMS.', 'ms-stats-for-bridge-project' ); ?></p>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Course', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Enrolled', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Avg Progress', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Fully Done', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:200px;"><?php esc_html_e( 'Completion Rate', 'ms-stats-for-bridge-project' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $progress_rows ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No data.', 'ms-stats-for-bridge-project' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $progress_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->course_title ?: 'Course #' . $row->course_id ); ?></td>
								<td><?php echo esc_html( $row->enrolled ); ?></td>
								<td><?php echo esc_html( $row->avg_progress ); ?>%</td>
								<td><?php echo esc_html( $row->completed ); ?></td>
								<td>
									<div style="display:flex;align-items:center;gap:8px;">
										<div style="background:#f0f0f0;border-radius:3px;width:80px;height:10px;overflow:hidden;flex-shrink:0;">
											<div style="background:#2271b1;height:100%;width:<?php echo esc_attr( min( 100, (float) $row->completion_rate ) ); ?>%;"></div>
										</div>
										<?php echo esc_html( $row->completion_rate ?? 0 ); ?>%
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
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
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'quizzes' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<h2><?php esc_html_e( 'Quiz Completion per Course', 'ms-stats-for-bridge-project' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Course', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:100px;"><?php esc_html_e( 'Quizzes', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:120px;"><?php esc_html_e( 'Users Attempted', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:110px;"><?php esc_html_e( 'Total Attempts', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:80px;"><?php esc_html_e( 'Passed', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:180px;"><?php esc_html_e( 'Pass Rate', 'ms-stats-for-bridge-project' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $quiz_rows ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No data.', 'ms-stats-for-bridge-project' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $quiz_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->course_title ?: 'Course #' . $row->course_id ); ?></td>
								<td><?php echo esc_html( $row->total_quizzes ); ?></td>
								<td><?php echo esc_html( $row->users_attempted ); ?></td>
								<td><?php echo esc_html( $row->total_attempts ); ?></td>
								<td><?php echo esc_html( $row->passed_attempts ); ?></td>
								<td>
									<div style="display:flex;align-items:center;gap:8px;">
										<div style="background:#f0f0f0;border-radius:3px;width:80px;height:10px;overflow:hidden;flex-shrink:0;">
											<div style="background:#00a32a;height:100%;width:<?php echo esc_attr( min( 100, (float) $row->pass_rate ) ); ?>%;"></div>
										</div>
										<?php echo esc_html( $row->pass_rate ?? 0 ); ?>%
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

		<?php elseif ( 'logins' === $active_tab ) : ?>

			<?php
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
			?>
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'logins' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<h2><?php esc_html_e( 'Login Sessions per Enrolled User', 'ms-stats-for-bridge-project' ); ?></h2>
			<p style="color:#50575e;"><?php esc_html_e( 'Count of active/recent login sessions stored by WordPress (session_tokens). Date filter applies to enrollment start date.', 'ms-stats-for-bridge-project' ); ?></p>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User', 'ms-stats-for-bridge-project' ); ?></th>
						<th><?php esc_html_e( 'Email', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:140px;"><?php esc_html_e( 'Active Sessions', 'ms-stats-for-bridge-project' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $login_counts ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No data.', 'ms-stats-for-bridge-project' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $login_counts as $uid => $count ) :
							$u = $user_data[ $uid ] ?? null;
							?>
							<tr>
								<td><?php echo esc_html( $u ? $u->display_name : 'User #' . $uid ); ?></td>
								<td><?php echo esc_html( $u ? $u->user_email : '—' ); ?></td>
								<td><?php echo esc_html( $count ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
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
			?>
			<p><a href="<?php echo esc_url( ms_stats_export_url( 'certificates' ) ); ?>" class="button button-secondary">&#11015; Export to CSV</a></p>
			<h2><?php esc_html_e( 'Certificates Issued per Course', 'ms-stats-for-bridge-project' ); ?></h2>
			<p style="color:#50575e;"><?php esc_html_e( 'Certificate records have no date column — date filter does not apply to this report.', 'ms-stats-for-bridge-project' ); ?></p>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Course', 'ms-stats-for-bridge-project' ); ?></th>
						<th style="width:180px;"><?php esc_html_e( 'Certificates Issued', 'ms-stats-for-bridge-project' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $cert_rows ) ) : ?>
						<tr><td colspan="2"><?php esc_html_e( 'No data.', 'ms-stats-for-bridge-project' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $cert_rows as $row ) :
							$course_id = (int) str_replace( 'stm_lms_certificate_code_', '', $row->meta_key );
							$title     = $course_titles[ $course_id ] ?? 'Course #' . $course_id;
							?>
							<tr>
								<td><?php echo esc_html( $title ); ?></td>
								<td><?php echo esc_html( $row->issued ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

		<?php endif; ?>

	</div><!-- .tab-content -->
</div><!-- .wrap -->
