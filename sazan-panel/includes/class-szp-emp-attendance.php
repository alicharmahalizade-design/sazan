<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * «حضور و غیاب کارمندان» — ثبت روزانهٔ ورود/خروج کارمندان با اسکن یک کیوآرکد ثابت.
 *
 * تفاوت با حضور دانشجویان: این‌جا مبنا «روز» است نه «جلسه». یک کیوآرکد ثابت دمِ درِ
 * محل کار می‌گذارید؛ کارمند هر روز اسکن می‌کند و ورودش ثبت می‌شود (اسکن دوم = خروج).
 * ساعت شروعِ کار را مدیر تعریف می‌کند؛ هرکس بعد از آن (به‌علاوهٔ ارفاق) بیاید «اخطار»
 * می‌گیرد و با عبور مجموع اخطارها از حد مجاز «مشمول جریمه» می‌شود. اگر «گروه کارمندان»
 * تعیین شود، غایبانِ امروز هم روی تابلو نمایش داده می‌شوند.
 */
class SZP_Emp_Attendance {

	const OPTION    = 'szp_emp_settings';
	const TOKEN_OPT = 'szp_emp_token';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_emp_attendance';
	}

	/* ==================== تنظیمات ==================== */

	public static function defaults() {
		return array(
			'work_start'     => '09:00', // ساعت شروع کار (وقت سایت)
			'grace_min'      => 0,
			'warn_limit'     => 3,
			'poll_sec'       => 6,
			'allow_guest'    => 1,       // ثبت با موبایل برای واردنشده
			'allow_checkout' => 1,       // اسکن دوم = ثبت خروج
			'roster_group'   => 0,       // گروه کارمندان (برای تشخیص غایبین)
			'restrict'       => 0,       // فقط اعضای گروه کارمندان اجازهٔ ثبت دارند
			'checkin_page'   => 0,       // برگهٔ [sazan_employee_attendance]
			'workdays'       => array( 6, 7, 1, 2, 3 ), // شنبه تا چهارشنبه (N: دوشنبه=1..یکشنبه=7)
		);
	}

	public static function settings() {
		$s = get_option( self::OPTION, array() );
		$s = wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
		if ( ! is_array( $s['workdays'] ) ) {
			$s['workdays'] = self::defaults()['workdays'];
		}
		return $s;
	}

	public static function opt( $key ) {
		$s = self::settings();
		return isset( $s[ $key ] ) ? $s[ $key ] : null;
	}

	public static function grace_min()      { return max( 0, (int) self::opt( 'grace_min' ) ); }
	public static function warn_limit()     { return max( 0, (int) self::opt( 'warn_limit' ) ); }
	public static function poll_sec()       { return max( 3, (int) self::opt( 'poll_sec' ) ); }
	public static function allow_guest()    { return ! empty( self::opt( 'allow_guest' ) ); }
	public static function allow_checkout() { return ! empty( self::opt( 'allow_checkout' ) ); }
	public static function roster_group()   { return (int) self::opt( 'roster_group' ); }
	public static function is_restricted()  { return ! empty( self::opt( 'restrict' ) ); }
	public static function work_start()     { return preg_match( '/^\d{1,2}:\d{2}$/', (string) self::opt( 'work_start' ) ) ? (string) self::opt( 'work_start' ) : '09:00'; }

	/* ==================== توکن ثابت ==================== */

	public static function token( $create = false ) {
		$tok = (string) get_option( self::TOKEN_OPT, '' );
		if ( $tok === '' && $create ) {
			$tok = wp_generate_password( 20, false, false );
			update_option( self::TOKEN_OPT, $tok );
		}
		return $tok;
	}

	public static function token_valid( $token ) {
		$real = self::token( false );
		return $real !== '' && hash_equals( $real, (string) $token );
	}

	public static function checkin_page_url() {
		$pid = (int) self::opt( 'checkin_page' );
		if ( $pid && get_post_status( $pid ) ) {
			return get_permalink( $pid );
		}
		return home_url( '/' );
	}

	public static function checkin_url() {
		return add_query_arg( array( 'k' => self::token( true ) ), self::checkin_page_url() );
	}

	public static function board_url( $base = '' ) {
		$base = $base ? $base : self::checkin_page_url();
		return add_query_arg( array( 'emp_board' => 1, 'k' => self::token( true ) ), $base );
	}

	/* ==================== روز و ساعت ==================== */

	/** تاریخ امروزِ سایت (Y-m-d) برای کلید روز. */
	public static function today() {
		return wp_date( 'Y-m-d' );
	}

	/** timestamp مطلق (UTC) شروع کارِ امروز = تاریخ امروز + ساعت شروع، در تایم‌زون سایت. */
	public static function start_ts( $date = '' ) {
		$date = $date ? $date : self::today();
		return szp_ts_from_datetime( $date . ' ' . self::work_start() );
	}

	public static function is_workday( $date = '' ) {
		$ts = $date ? strtotime( $date . ' 12:00:00' ) : time();
		$n  = (int) wp_date( 'N', $ts );
		return in_array( $n, array_map( 'intval', (array) self::opt( 'workdays' ) ), true );
	}

	/* ==================== ثبت ==================== */

	public static function compute_arrival( $checkin_ts, $date = '' ) {
		$start = self::start_ts( $date );
		if ( ! $start ) {
			return array( 'ontime', 0 );
		}
		$deadline = $start + self::grace_min() * 60;
		if ( $checkin_ts <= $deadline ) {
			return array( 'ontime', 0 );
		}
		return array( 'late', max( 1, (int) ceil( ( $checkin_ts - $start ) / 60 ) ) );
	}

	public static function row_today( $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d AND work_date=%s',
			(int) $user_id, self::today() ) );
	}

	/** مجموع اخطارهای یک کارمند در همهٔ روزها. */
	public static function warnings( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::table() . " WHERE user_id=%d AND status='late'",
			(int) $user_id ) );
	}

	public static function is_roster_member( $user_id ) {
		$gid = self::roster_group();
		if ( ! $gid ) {
			return true; // بدون گروه، همه مجازند.
		}
		return in_array( (int) $user_id, array_map( 'intval', SZP_Groups::members( $gid ) ), true );
	}

	/**
	 * ثبت ورود (یا خروج در اسکن دوم) برای یک کارمند در امروز.
	 * خروجی: array( ok, action[checkin|checkout|already], status?, late_min?, warnings ).
	 */
	public static function record( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id < 1 ) {
			return array( 'ok' => false, 'msg' => 'کاربر نامعتبر است.' );
		}
		if ( self::is_restricted() && ! self::is_roster_member( $user_id ) ) {
			return array( 'ok' => false, 'msg' => 'شما در فهرست کارمندان نیستید.' );
		}

		$now = time();
		$gmt = gmdate( 'Y-m-d H:i:s', $now );
		$row = self::row_today( $user_id );

		if ( $row ) {
			// اسکن دوم → ثبت خروج (اگر فعال و هنوز ثبت نشده باشد).
			if ( self::allow_checkout() && empty( $row->checkout_at ) ) {
				$wpdb->update( self::table(), array( 'checkout_at' => $gmt ),
					array( 'id' => (int) $row->id ), array( '%s' ), array( '%d' ) );
				return array(
					'ok'       => true,
					'action'   => 'checkout',
					'status'   => (string) $row->status,
					'warnings' => self::warnings( $user_id ),
				);
			}
			return array(
				'ok'       => true,
				'action'   => 'already',
				'status'   => (string) $row->status,
				'warnings' => self::warnings( $user_id ),
			);
		}

		list( $status, $late ) = self::compute_arrival( $now );
		$wpdb->insert( self::table(), array(
			'user_id'    => $user_id,
			'work_date'  => self::today(),
			'status'     => $status,
			'late_min'   => (int) $late,
			'checkin_at' => $gmt,
			'created_at' => $gmt,
		), array( '%d', '%s', '%s', '%d', '%s', '%s' ) );

		return array(
			'ok'       => true,
			'action'   => 'checkin',
			'status'   => $status,
			'late_min' => (int) $late,
			'warnings' => self::warnings( $user_id ),
		);
	}

	public static function delete( $user_id, $date = '' ) {
		global $wpdb;
		$date = $date ? $date : self::today();
		$wpdb->delete( self::table(), array( 'user_id' => (int) $user_id, 'work_date' => $date ), array( '%d', '%s' ) );
	}

	/* ==================== دسته‌بندی و تابلو ==================== */

	public static function bucket( $status, $warnings ) {
		if ( $status !== 'late' ) {
			return 'ontime';
		}
		return ( $warnings > self::warn_limit() ) ? 'penalized' : 'warned';
	}

	public static function statuses() {
		return array(
			'ontime'    => array( 'label' => 'حضور به‌موقع', 'color' => '#16a34a', 'emoji' => '✅' ),
			'warned'    => array( 'label' => 'اخطاری',       'color' => '#f59e0b', 'emoji' => '⚠️' ),
			'penalized' => array( 'label' => 'مشمول جریمه',  'color' => '#ef4444', 'emoji' => '⛔' ),
			'absent'    => array( 'label' => 'غایب',         'color' => '#64748b', 'emoji' => '🚫' ),
		);
	}

	/** رکوردهای امروز (به‌ترتیب ورود). */
	public static function today_rows() {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE work_date=%s ORDER BY checkin_at ASC, id ASC',
			self::today() ) );
		$out = array();
		foreach ( $rows as $r ) {
			$uid  = (int) $r->user_id;
			$info = SZP_Groups::user_info( $uid );
			if ( ! $info ) {
				continue;
			}
			$warn   = self::warnings( $uid );
			$in_ts  = strtotime( (string) $r->checkin_at . ' UTC' );
			$out_ts = $r->checkout_at ? strtotime( (string) $r->checkout_at . ' UTC' ) : 0;
			$out[]  = array(
				'user_id'  => $uid,
				'name'     => $info['name'],
				'mobile'   => $info['mobile'],
				'avatar'   => get_avatar_url( $uid, array( 'size' => 96 ) ),
				'status'   => (string) $r->status,
				'bucket'   => self::bucket( (string) $r->status, $warn ),
				'warnings' => $warn,
				'late_min' => (int) $r->late_min,
				'in'       => $in_ts ? szp_fa_digits( wp_date( 'H:i', $in_ts ) ) : '',
				'out'      => $out_ts ? szp_fa_digits( wp_date( 'H:i', $out_ts ) ) : '',
			);
		}
		return $out;
	}

	/** فهرست غایبان امروز (اعضای گروه کارمندان که هنوز ثبت نکرده‌اند). خالی اگر گروهی تعیین نشده باشد. */
	public static function absent_rows( $present_ids = array() ) {
		$gid = self::roster_group();
		if ( ! $gid ) {
			return array();
		}
		$present = array_flip( array_map( 'intval', $present_ids ) );
		$out     = array();
		foreach ( SZP_Groups::members( $gid ) as $uid ) {
			$uid = (int) $uid;
			if ( isset( $present[ $uid ] ) ) {
				continue;
			}
			$info = SZP_Groups::user_info( $uid );
			if ( ! $info ) {
				continue;
			}
			$out[] = array(
				'user_id'  => $uid,
				'name'     => $info['name'],
				'mobile'   => $info['mobile'],
				'avatar'   => get_avatar_url( $uid, array( 'size' => 96 ) ),
				'status'   => 'absent',
				'bucket'   => 'absent',
				'warnings' => self::warnings( $uid ),
				'late_min' => 0,
				'in'       => '',
				'out'      => '',
			);
		}
		return $out;
	}

	public static function board_data() {
		$cols = array( 'ontime' => array(), 'warned' => array(), 'penalized' => array(), 'absent' => array() );
		$present = array();
		foreach ( self::today_rows() as $row ) {
			$cols[ $row['bucket'] ][] = $row;
			$present[] = $row['user_id'];
		}
		$cols['absent'] = self::absent_rows( $present );
		$start = self::start_ts();
		return array(
			'date'      => szp_fa_digits( self::today() ),
			'jdate'     => $start ? szp_format_datetime( $start ) : '',
			'start_h'   => szp_fa_digits( self::work_start() ),
			'limit'     => self::warn_limit(),
			'has_roster' => self::roster_group() > 0,
			'columns'   => $cols,
			'present'   => count( $present ),
			'absent'    => count( $cols['absent'] ),
		);
	}

	/* ==================== رندر: صفحهٔ ثبت ==================== */

	public static function render_checkin( $atts = array() ) {
		if ( isset( $_GET['emp_board'] ) ) {
			return self::render_board();
		}
		$token = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';
		if ( ! self::token_valid( $token ) ) {
			return '<div class="szp"><div class="szp-empty">این کیوآرکد معتبر نیست. لطفاً دوباره اسکن کنید.</div></div>';
		}

		$logged   = is_user_logged_in();
		$row      = $logged ? self::row_today( get_current_user_id() ) : null;
		$name     = $logged ? wp_get_current_user()->display_name : '';
		$can_out  = $row && self::allow_checkout() && empty( $row->checkout_at );
		$done     = $row && ( ! self::allow_checkout() || ! empty( $row->checkout_at ) );

		ob_start(); ?>
		<div class="szp">
			<div class="szp-att-checkin" data-action="szp_emp_checkin" data-session="0"
				data-token="<?php echo esc_attr( $token ); ?>"
				data-guest="<?php echo self::allow_guest() ? '1' : '0'; ?>"
				data-logged="<?php echo $logged ? '1' : '0'; ?>">
				<div class="szp-att-ci-card">
					<div class="szp-att-ci-head">
						<span class="szp-att-ci-ico">🏢</span>
						<h3 class="szp-att-ci-title">حضور و غیاب کارمندان</h3>
					</div>
					<div class="szp-att-ci-info">
						<div class="szp-att-ci-cls"><?php echo esc_html( szp_format_datetime( self::start_ts() ) ); ?></div>
						<div class="szp-att-ci-time">ساعت شروع کار: <b><?php echo esc_html( szp_fa_digits( self::work_start() ) ); ?></b></div>
					</div>

					<?php if ( $done ) : ?>
						<div class="szp-att-ci-done is-show">حضور شما برای امروز ثبت شده است ✓<?php echo $row->checkout_at ? ' — خروج نیز ثبت شد.' : ''; ?></div>
					<?php elseif ( $logged ) : ?>
						<p class="szp-att-ci-hello">سلام <b><?php echo esc_html( $name ); ?></b>،
							<?php echo $can_out ? 'برای ثبت خروج دکمهٔ زیر را بزنید.' : 'برای ثبت ورود دکمهٔ زیر را بزنید.'; ?></p>
						<button type="button" class="szp-att-ci-btn" data-mode="self"><?php echo $can_out ? 'ثبت خروج' : 'ثبت ورود من'; ?></button>
					<?php elseif ( self::allow_guest() ) : ?>
						<p class="szp-att-ci-hello">شمارهٔ موبایل خود را وارد کنید (اسکن دوم = ثبت خروج).</p>
						<div class="szp-att-ci-form">
							<input type="text" class="szp-att-ci-mobile" inputmode="numeric" placeholder="شمارهٔ موبایل (مثلاً ۰۹۱۲...)">
							<input type="text" class="szp-att-ci-fname" placeholder="نام و نام خانوادگی (اگر بار اول است)">
							<button type="button" class="szp-att-ci-btn" data-mode="guest">ثبت</button>
						</div>
					<?php else : ?>
						<p class="szp-att-ci-hello">برای ثبت حضور ابتدا وارد حساب کاربری خود شوید.</p>
						<a class="szp-att-ci-btn" href="<?php echo esc_url( wp_login_url( self::checkin_url() ) ); ?>">ورود</a>
					<?php endif; ?>

					<div class="szp-att-ci-msg" aria-live="polite"></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== رندر: تابلو ==================== */

	public static function can_view_board( $token = '' ) {
		if ( current_user_can( apply_filters( 'szp_emp_board_cap', 'manage_options' ) ) ) {
			return true;
		}
		return self::token_valid( (string) $token );
	}

	public static function render_board( $atts = array() ) {
		$a     = wp_parse_args( $atts, array( 'title' => '' ) );
		$token = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';
		if ( ! self::can_view_board( $token ) ) {
			return '<div class="szp"><div class="szp-empty">شما به تابلوی حضور کارمندان دسترسی ندارید.</div></div>';
		}

		$data  = self::board_data();
		$meta  = self::statuses();
		$title = $a['title'] !== '' ? $a['title'] : 'حضور و غیاب کارمندان';
		$keys  = array( 'ontime', 'warned', 'penalized' );
		if ( $data['has_roster'] ) {
			$keys[] = 'absent';
		}

		ob_start(); ?>
		<div class="szp szp-att-fullscreen">
			<div class="szp-att-board szp-emp-board" data-session="0" data-board-action="szp_emp_board"
				data-token="<?php echo esc_attr( $token ); ?>"
				data-poll="<?php echo esc_attr( self::poll_sec() ); ?>"
				data-limit="<?php echo esc_attr( $data['limit'] ); ?>">

				<div class="szp-att-b-head">
					<div class="szp-att-b-titles">
						<h2 class="szp-att-b-cls"><?php echo esc_html( $title ); ?></h2>
						<div class="szp-att-b-sub">
							<span><?php echo esc_html( $data['jdate'] ); ?></span>
							<span>شروع کار: <b><?php echo esc_html( $data['start_h'] ); ?></b></span>
						</div>
					</div>
					<div class="szp-att-b-clock">
						<span class="szp-att-b-count" data-total><?php echo esc_html( szp_fa_digits( $data['present'] ) ); ?></span>
						<span class="szp-att-b-count-l">حاضر</span>
						<span class="szp-att-b-time" data-clock></span>
					</div>
				</div>

				<div class="szp-att-b-cols szp-emp-cols-<?php echo count( $keys ); ?>">
					<?php foreach ( $keys as $key ) :
						$m = $meta[ $key ]; ?>
						<div class="szp-att-col szp-att-col-<?php echo esc_attr( $key ); ?>" data-col="<?php echo esc_attr( $key ); ?>" style="--c:<?php echo esc_attr( $m['color'] ); ?>">
							<div class="szp-att-col-head">
								<span class="szp-att-col-ico"><?php echo esc_html( $m['emoji'] ); ?></span>
								<span class="szp-att-col-title"><?php echo esc_html( $m['label'] ); ?></span>
								<span class="szp-att-col-n" data-n><?php echo esc_html( szp_fa_digits( count( $data['columns'][ $key ] ) ) ); ?></span>
							</div>
							<div class="szp-att-col-list" data-list>
								<?php echo self::rows_html( $data['columns'][ $key ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function rows_html( $rows ) {
		if ( ! $rows ) {
			return '<div class="szp-att-empty">—</div>';
		}
		$out = '';
		foreach ( $rows as $r ) {
			$out .= self::row_html( $r );
		}
		return $out;
	}

	public static function row_html( $r ) {
		$warn = (int) $r['warnings'];
		$sub  = '';
		if ( ! empty( $r['in'] ) ) {
			$sub .= '<span class="szp-att-p-time">🟢 ورود ' . esc_html( $r['in'] ) . '</span>';
		}
		if ( ! empty( $r['out'] ) ) {
			$sub .= '<span class="szp-att-p-time">🔴 خروج ' . esc_html( $r['out'] ) . '</span>';
		}
		if ( $r['status'] === 'late' && ! empty( $r['late_min'] ) ) {
			$sub .= '<span class="szp-att-p-late">' . esc_html( szp_fa_digits( $r['late_min'] ) ) . ' دقیقه تأخیر</span>';
		}
		ob_start(); ?>
		<div class="szp-att-person" data-uid="<?php echo esc_attr( $r['user_id'] ); ?>">
			<?php if ( ! empty( $r['avatar'] ) ) : ?>
				<span class="szp-att-p-av" style="background-image:url('<?php echo esc_url( $r['avatar'] ); ?>')"></span>
			<?php endif; ?>
			<span class="szp-att-p-main">
				<span class="szp-att-p-name"><?php echo esc_html( $r['name'] ); ?></span>
				<?php if ( $sub ) : ?><span class="szp-att-p-sub"><?php echo $sub; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?>
			</span>
			<span class="szp-att-p-warn <?php echo $warn > 0 ? 'has' : ''; ?>" title="مجموع اخطارها">
				<?php echo esc_html( szp_fa_digits( $warn ) ); ?> اخطار
			</span>
		</div>
		<?php
		return ob_get_clean();
	}
}
