<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Admin views: submitted assignments + survey results. */
class SZP_Reports {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 12 );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'تکالیف ارسالی', 'تکالیف ارسالی', 'manage_options', 'szp-submissions', array( __CLASS__, 'submissions' ) );
		add_submenu_page( 'sazan-panel', 'نتایج نظرسنجی', 'نتایج نظرسنجی', 'manage_options', 'szp-surveys', array( __CLASS__, 'surveys' ) );
	}

	/* ---------------- submissions ---------------- */

	public static function submissions() {
		$session = isset( $_GET['session'] ) ? absint( $_GET['session'] ) : 0;
		$rows    = SZP_Data::recent_submissions( $session, 300 );

		echo '<div class="wrap"><h1>تکالیف ارسالی</h1>';

		// session filter
		$sessions = get_posts( array( 'post_type' => 'szp_session', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		echo '<form method="get" style="margin:12px 0"><input type="hidden" name="page" value="szp-submissions">';
		echo '<select name="session" onchange="this.form.submit()"><option value="0">همه جلسات</option>';
		foreach ( $sessions as $sp ) {
			printf( '<option value="%1$d" %3$s>%2$s</option>', (int) $sp->ID, esc_html( $sp->post_title ), selected( $session, $sp->ID, false ) );
		}
		echo '</select></form>';

		if ( ! $rows ) {
			echo '<p>پاسخی ثبت نشده است.</p></div>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr><th>جلسه</th><th>کاربر</th><th>پاسخ</th><th>فایل</th><th>تاریخ</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$u    = get_userdata( $r->user_id );
			$file = $r->file_id ? wp_get_attachment_url( $r->file_id ) : '';
			$txt  = wp_trim_words( wp_strip_all_tags( (string) $r->content ), 30, '…' );
			printf(
				'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td></tr>',
				esc_html( get_the_title( $r->session_id ) ),
				esc_html( $u ? $u->display_name : ( 'کاربر #' . (int) $r->user_id ) ),
				esc_html( $txt ),
				$file ? '<a href="' . esc_url( $file ) . '" target="_blank" rel="noopener">دانلود</a>' : '—',
				esc_html( szp_format_datetime( strtotime( $r->updated_at ) ) )
			);
		}
		echo '</tbody></table></div>';
	}

	/* ---------------- survey results ---------------- */

	public static function surveys() {
		echo '<div class="wrap"><h1>نتایج نظرسنجی</h1>';

		echo '<h2>نظرسنجی دوره‌ها</h2>';
		$courses = get_posts( array( 'post_type' => 'szp_course', 'post_status' => 'publish', 'numberposts' => -1 ) );
		self::survey_block( $courses, 'course', '_szp_course_survey' );

		echo '<h2 style="margin-top:24px">نظرسنجی جلسات</h2>';
		$sessions = get_posts( array( 'post_type' => 'szp_session', 'post_status' => 'publish', 'numberposts' => -1 ) );
		self::survey_block( $sessions, 'session', '_szp_survey' );

		echo '</div>';
	}

	protected static function survey_block( $posts, $context, $meta_key ) {
		$any = false;
		foreach ( $posts as $p ) {
			$questions = array_values( array_filter( (array) get_post_meta( $p->ID, $meta_key, true ), 'strlen' ) );
			if ( ! $questions ) {
				continue;
			}
			$any = true;
			$res = SZP_Data::survey_results( $context, $p->ID, count( $questions ) );
			printf( '<h3>%1$s <span style="font-weight:400;color:#666">(%2$s پاسخ)</span></h3>',
				esc_html( $p->post_title ), esc_html( szp_fa_digits( $res['responses'] ) ) );
			echo '<table class="widefat striped" style="max-width:760px"><thead><tr><th>سوال</th><th>میانگین امتیاز (از ۵)</th></tr></thead><tbody>';
			foreach ( $questions as $i => $q ) {
				printf( '<tr><td>%1$s</td><td>%2$s</td></tr>',
					esc_html( $q ),
					esc_html( szp_fa_digits( $res['avg'][ $i ] ) ) );
			}
			echo '</tbody></table>';
		}
		if ( ! $any ) {
			echo '<p>موردی با سوال نظرسنجی یافت نشد.</p>';
		}
	}
}
