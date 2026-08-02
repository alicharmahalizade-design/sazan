<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** مدیریت چرخه‌ی اتاق گفتگو برای هر دوره. */
class SZP_Chat_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 12 );
		add_action( 'admin_post_szp_chat_admin', array( __CLASS__, 'handle' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'اتاق گفتگو', 'اتاق گفتگو', 'manage_options', 'szp-chat', array( __CLASS__, 'render' ) );
	}

	protected static function courses() {
		$q = new WP_Query( array(
			'post_type'      => 'szp_course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		return $q->posts;
	}

	public static function render() {
		$cid = isset( $_GET['course'] ) ? absint( $_GET['course'] ) : 0;
		echo '<div class="wrap szp-groups-wrap"><h1>اتاق گفتگو</h1>';
		if ( isset( $_GET['msg'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>انجام شد.</p></div>';
		}
		if ( $cid ) {
			self::course_view( $cid );
		} else {
			self::course_list();
		}
		echo '</div>';
	}

	protected static function course_list() {
		echo '<p>یک دوره را برای مدیریت اتاق گفتگوی آن انتخاب کنید.</p>';
		$courses = self::courses();
		if ( ! $courses ) {
			echo '<p>هنوز دوره‌ای ساخته نشده است.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>دوره</th><th>وضعیت اتاق</th><th>عملیات</th></tr></thead><tbody>';
		$labels = array(
			'pending'  => 'شروع نشده',
			'free'     => 'گفتگوی آزاد',
			'grouping' => 'گروه‌بندی',
			'final'    => 'نهایی شده',
		);
		foreach ( $courses as $c ) {
			$room   = SZP_Chat::get_room( $c );
			$status = $room ? $room->status : 'pending';
			$url    = add_query_arg( array( 'page' => 'szp-chat', 'course' => $c ), admin_url( 'admin.php' ) );
			printf( '<tr><td><strong>%s</strong></td><td>%s</td><td><a class="button button-primary" href="%s">مدیریت</a></td></tr>',
				esc_html( get_the_title( $c ) ), esc_html( $labels[ $status ] ?? $status ), esc_url( $url ) );
		}
		echo '</tbody></table>';
	}

	protected static function form_open( $action, $cid ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 10px">';
		wp_nonce_field( 'szp_chat_admin' );
		echo '<input type="hidden" name="action" value="szp_chat_admin">';
		echo '<input type="hidden" name="do" value="' . esc_attr( $action ) . '">';
		echo '<input type="hidden" name="course" value="' . (int) $cid . '">';
	}

	protected static function course_view( $cid ) {
		$room   = SZP_Chat::ensure_room( $cid );
		$status = $room->status;
		$back   = add_query_arg( 'page', 'szp-chat', admin_url( 'admin.php' ) );

		echo '<p><a class="button" href="' . esc_url( $back ) . '">‹ بازگشت به فهرست</a></p>';
		echo '<h2>دوره: ' . esc_html( get_the_title( $cid ) ) . '</h2>';

		$labels = array( 'pending' => 'شروع نشده', 'free' => 'گفتگوی آزاد باز', 'grouping' => 'گروه‌بندی توسط کاربران', 'final' => 'نهایی شده' );
		echo '<p>وضعیت فعلی: <strong>' . esc_html( $labels[ $status ] ?? $status ) . '</strong></p>';

		// 1) گفتگوی آزاد
		echo '<div class="card" style="max-width:640px;padding:14px 18px"><h3>۱) گفتگوی آزاد</h3>';
		self::form_open( 'start_free', $cid );
		echo '<p>مهلت پایان گفتگوی آزاد:</p>';
		echo '<input type="datetime-local" name="deadline" value="' . esc_attr( self::dt_local( $room->free_deadline ) ) . '" required>';
		echo ' <button class="button button-primary">' . ( $status === 'pending' ? 'شروع گفتگوی آزاد' : 'به‌روزرسانی مهلت' ) . '</button>';
		echo '</form></div>';

		// 2) بستن آزاد و ساخت گروه‌ها
		echo '<div class="card" style="max-width:640px;padding:14px 18px"><h3>۲) بستن آزاد و تعیین تعداد گروه‌ها</h3>';
		echo '<p class="description">با این کار گفتگوی آزاد آرشیو شده و گروه‌های خالی ساخته می‌شوند تا کاربران خودشان عضو شوند.</p>';
		self::form_open( 'make_groups', $cid );
		echo 'تعداد گروه: <input type="number" name="count" min="1" max="20" value="' . ( $room->group_count ? (int) $room->group_count : 4 ) . '" style="width:80px">';
		echo ' <button class="button button-primary" onclick="return confirm(\'گروه‌ها و عضویت‌های قبلی بازنشانی می‌شوند. ادامه؟\')">ساخت گروه‌ها</button>';
		echo '</form></div>';

		// 3) نهایی‌سازی + رای‌گیری
		if ( in_array( $status, array( 'grouping', 'final' ), true ) ) {
			echo '<div class="card" style="max-width:640px;padding:14px 18px"><h3>۳) نهایی‌سازی و شروع رای‌گیری سرگروه</h3>';
			self::form_open( 'finalize', $cid );
			echo '<p>مهلت پایان رای‌گیری:</p>';
			echo '<input type="datetime-local" name="vote_deadline" required>';
			echo ' <button class="button button-primary">' . ( $status === 'final' ? 'به‌روزرسانی مهلت رای‌گیری' : 'نهایی‌سازی و شروع رای‌گیری' ) . '</button>';
			echo '</form></div>';

			self::groups_box( $room, $cid );
		}

		// بازنشانی
		echo '<div class="card" style="max-width:640px;padding:14px 18px"><h3>بازنشانی</h3>';
		self::form_open( 'reset', $cid );
		echo '<button class="button button-link-delete" onclick="return confirm(\'کل اتاق گفتگوی این دوره پاک شود؟\')">بازنشانی کامل اتاق</button>';
		echo '</form></div>';
	}

	protected static function groups_box( $room, $cid ) {
		$groups = SZP_Chat::groups( $room->id );
		if ( ! $groups ) {
			return;
		}
		echo '<h3>گروه‌ها و سرگروه‌ها</h3>';
		echo '<table class="widefat striped" style="max-width:760px"><thead><tr><th>گروه</th><th>تعداد اعضا</th><th>وضعیت</th><th>سرگروه (تعیین دستی)</th></tr></thead><tbody>';
		foreach ( $groups as $g ) {
			$members = SZP_Chat::group_members( $g->id );
			$st      = ( $g->status === 'active' ) ? 'فعال' : 'در حال رای‌گیری';
			echo '<tr><td><strong>' . esc_html( $g->name ) . '</strong></td><td>' . count( $members ) . '</td><td>' . esc_html( $st ) . '</td><td>';
			self::form_open( 'set_leader', $cid );
			echo '<input type="hidden" name="group_id" value="' . (int) $g->id . '">';
			echo '<select name="leader_id"><option value="0">— انتخاب —</option>';
			foreach ( $members as $m ) {
				printf( '<option value="%d"%s>%s</option>', (int) $m->user_id,
					selected( (int) $g->leader_id, (int) $m->user_id, false ),
					esc_html( SZP_Chat::user_label( $m->user_id ) ) );
			}
			echo '</select> <button class="button">تعیین</button>';
			echo '</form></td></tr>';
		}
		echo '</tbody></table>';
	}

	protected static function dt_local( $mysql ) {
		if ( ! $mysql ) {
			return '';
		}
		$ts = szp_ts_from_datetime( $mysql );
		if ( ! $ts ) {
			return '';
		}
		$dt = new DateTime( '@' . $ts );
		$dt->setTimezone( wp_timezone() );
		return $dt->format( 'Y-m-d\TH:i' );
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_chat_admin' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$cid = absint( $_POST['course'] ?? 0 );
		$do  = sanitize_key( $_POST['do'] ?? '' );

		switch ( $do ) {
			case 'start_free':
				SZP_Chat::start_free( $cid, self::norm_dt( $_POST['deadline'] ?? '' ) );
				break;
			case 'make_groups':
				SZP_Chat::close_free_make_groups( $cid, absint( $_POST['count'] ?? 4 ) );
				break;
			case 'finalize':
				SZP_Chat::finalize( $cid, self::norm_dt( $_POST['vote_deadline'] ?? '' ) );
				break;
			case 'set_leader':
				SZP_Chat::set_leader( absint( $_POST['group_id'] ?? 0 ), absint( $_POST['leader_id'] ?? 0 ) );
				break;
			case 'reset':
				SZP_Chat::reset( $cid );
				break;
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-chat', 'course' => $cid, 'msg' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/** datetime-local → MySQL datetime (site tz). */
	protected static function norm_dt( $val ) {
		$val = trim( (string) $val );
		if ( $val === '' ) {
			return '';
		}
		return str_replace( 'T', ' ', $val ) . ':00';
	}
}
