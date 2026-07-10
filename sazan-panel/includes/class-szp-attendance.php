<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * «حضور و غیاب» — ثبت حضور دانشجویان با اسکن کیوآرکد و نمایش زندهٔ تابلوی کلاس.
 *
 * جریان کار:
 *   - مدیر/مدرّب برای هر «جلسه» یک کیوآرکد چاپ می‌کند و دم درِ کلاس می‌گذارد.
 *   - دانشجو با اسکن، صفحهٔ ثبت حضور باز می‌شود؛ اگر واردشده باشد با یک لمس، در غیر این
 *     صورت با شمارهٔ موبایل، حضورش ثبت می‌شود (ساعت، روز و کلاس ذخیره می‌شود).
 *   - اگر بعد از ساعت شروعِ جلسه (به‌علاوهٔ ارفاق) بیاید، «اخطار» می‌گیرد.
 *   - اگر مجموع اخطارهایش در همهٔ جلسات از حد مجاز (پیش‌فرض ۳) بیشتر شود، «مشمول جریمه» می‌شود.
 *   - تابلوی کلاس (برای تلویزیون) هر چند ثانیه به‌روز می‌شود و افراد را در سه ستون
 *     «حضور به‌موقع / اخطاری / مشمول جریمه» نشان می‌دهد؛ کنار هر نام، مجموع اخطارهای او.
 */
class SZP_Attendance {

	const OPTION = 'szp_att_settings';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_attendance';
	}

	/* ==================== تنظیمات ==================== */

	public static function defaults() {
		return array(
			'grace_min'    => 0,   // دقایق ارفاق بعد از ساعت شروع که هنوز «به‌موقع» است
			'warn_limit'   => 3,   // بیش از این تعداد اخطار → مشمول جریمه
			'poll_sec'     => 6,   // فاصلهٔ به‌روزرسانی تابلو (ثانیه)
			'allow_guest'  => 1,   // ثبت حضور با موبایل برای کاربر واردنشده
			'checkin_page' => 0,   // شناسهٔ برگه‌ای که شورت‌کد [sazan_attendance] دارد
		);
	}

	public static function settings() {
		$s = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
	}

	public static function opt( $key ) {
		$s = self::settings();
		return isset( $s[ $key ] ) ? $s[ $key ] : null;
	}

	public static function grace_min()  { return max( 0, (int) self::opt( 'grace_min' ) ); }
	public static function warn_limit() { return max( 0, (int) self::opt( 'warn_limit' ) ); }
	public static function poll_sec()   { return max( 3, (int) self::opt( 'poll_sec' ) ); }
	public static function allow_guest() { return ! empty( self::opt( 'allow_guest' ) ); }

	/* ==================== توکن جلسه ==================== */

	/** توکن مخفی هر جلسه؛ در کیوآرکد و لینک تابلو استفاده می‌شود. در صورت نبود ساخته می‌شود. */
	public static function token( $session_id, $create = false ) {
		$session_id = (int) $session_id;
		$tok        = (string) get_post_meta( $session_id, '_szp_att_token', true );
		if ( $tok === '' && $create ) {
			$tok = wp_generate_password( 20, false, false );
			update_post_meta( $session_id, '_szp_att_token', $tok );
		}
		return $tok;
	}

	public static function token_valid( $session_id, $token ) {
		$real = self::token( $session_id, false );
		return $real !== '' && hash_equals( $real, (string) $token );
	}

	/** برگهٔ ثبت حضور (permalink) که کیوآرکد به آن اشاره می‌کند. */
	public static function checkin_page_url() {
		$pid = (int) self::opt( 'checkin_page' );
		if ( $pid && get_post_status( $pid ) ) {
			return get_permalink( $pid );
		}
		return home_url( '/' );
	}

	/** آدرس کامل ثبت حضور برای یک جلسه (داخل کیوآرکد). */
	public static function checkin_url( $session_id ) {
		$session_id = (int) $session_id;
		return add_query_arg( array(
			'att' => $session_id,
			'k'   => self::token( $session_id, true ),
		), self::checkin_page_url() );
	}

	/** آدرس تابلوی کلاس برای نمایش روی تلویزیون (بدون نیاز به ورود، با توکن). */
	public static function board_url( $session_id, $base = '' ) {
		$session_id = (int) $session_id;
		$base       = $base ? $base : self::checkin_page_url();
		return add_query_arg( array(
			'att_board' => $session_id,
			'k'         => self::token( $session_id, true ),
		), $base );
	}

	/* ==================== جلسه ==================== */

	/** timestamp ساعت شروع جلسه (۰ اگر تعیین نشده). */
	public static function session_start( $session_id ) {
		return szp_ts_from_datetime( get_post_meta( (int) $session_id, '_szp_datetime', true ) );
	}

	public static function course_title( $session_id ) {
		$cid = (int) get_post_meta( (int) $session_id, '_szp_course_id', true );
		return $cid ? get_the_title( $cid ) : '';
	}

	/** جلسه‌ای که امروز برگزار می‌شود (نزدیک‌ترین به الان) — برای انتخاب خودکار. */
	public static function today_session() {
		$q = new WP_Query( array(
			'post_type'      => 'szp_session',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_szp_datetime',
		) );
		$today = wp_date( 'Y-m-d' );
		$best  = 0;
		$bestd = PHP_INT_MAX;
		$now   = time();
		foreach ( $q->posts as $sid ) {
			$ts = self::session_start( $sid );
			if ( ! $ts ) {
				continue;
			}
			if ( wp_date( 'Y-m-d', $ts ) === $today ) {
				$d = abs( $now - $ts );
				if ( $d < $bestd ) {
					$bestd = $d;
					$best  = (int) $sid;
				}
			}
		}
		return $best;
	}

	/** فهرست جلسات (جدیدترین اول) برای انتخابگرها. */
	public static function sessions( $limit = 100 ) {
		$q = new WP_Query( array(
			'post_type'      => 'szp_session',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $limit,
			'no_found_rows'  => true,
			'meta_key'       => '_szp_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		) );
		return $q->posts;
	}

	/* ==================== ثبت حضور ==================== */

	/** وضعیت ورود بر اساس ساعت شروع: array( status, late_min ). */
	public static function compute_arrival( $session_id, $checkin_ts ) {
		$start = self::session_start( $session_id );
		if ( ! $start ) {
			return array( 'ontime', 0 );
		}
		$deadline = $start + self::grace_min() * 60;
		if ( $checkin_ts <= $deadline ) {
			return array( 'ontime', 0 );
		}
		$late = (int) ceil( ( $checkin_ts - $start ) / 60 );
		return array( 'late', max( 1, $late ) );
	}

	/** آیا این کاربر برای این جلسه قبلاً حضور خورده؟ */
	public static function has_checked_in( $session_id, $user_id ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . self::table() . ' WHERE session_id=%d AND user_id=%d',
			(int) $session_id, (int) $user_id ) );
	}

	/**
	 * ثبت حضور یک کاربر برای یک جلسه.
	 * خروجی: array( ok, status, late_min, already, warnings ).
	 */
	public static function record( $session_id, $user_id ) {
		global $wpdb;
		$session_id = (int) $session_id;
		$user_id    = (int) $user_id;
		if ( $session_id < 1 || $user_id < 1 ) {
			return array( 'ok' => false );
		}
		if ( self::has_checked_in( $session_id, $user_id ) ) {
			return array(
				'ok'       => true,
				'already'  => true,
				'status'   => self::user_status_in( $session_id, $user_id ),
				'warnings' => self::warnings( $user_id ),
			);
		}
		$now = time(); // زمان مطلق UTC — با ساعت شروع جلسه (که آن هم UTC است) هم‌مبنا.
		list( $status, $late ) = self::compute_arrival( $session_id, $now );
		$gmt = gmdate( 'Y-m-d H:i:s', $now ); // ذخیرهٔ GMT برای نمایش تایم‌زون‌درست.
		$wpdb->insert( self::table(), array(
			'session_id' => $session_id,
			'user_id'    => $user_id,
			'status'     => $status,
			'late_min'   => (int) $late,
			'checkin_at' => $gmt,
			'created_at' => $gmt,
		), array( '%d', '%d', '%s', '%d', '%s', '%s' ) );

		return array(
			'ok'       => true,
			'already'  => false,
			'status'   => $status,
			'late_min' => (int) $late,
			'warnings' => self::warnings( $user_id ),
		);
	}

	public static function user_status_in( $session_id, $user_id ) {
		global $wpdb;
		$st = $wpdb->get_var( $wpdb->prepare(
			'SELECT status FROM ' . self::table() . ' WHERE session_id=%d AND user_id=%d',
			(int) $session_id, (int) $user_id ) );
		return $st ? $st : '';
	}

	/** مجموع اخطارهای یک کاربر در همهٔ جلسات (تعداد حضورهای با تأخیر). */
	public static function warnings( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::table() . " WHERE user_id=%d AND status='late'",
			(int) $user_id ) );
	}

	public static function delete( $session_id, $user_id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'session_id' => (int) $session_id, 'user_id' => (int) $user_id ), array( '%d', '%d' ) );
	}

	/* ==================== دسته‌بندی و تابلو ==================== */

	/**
	 * دسته‌بندی نهایی یک ردیفِ حضور بر اساس وضعیت ورود و مجموع اخطارها:
	 *   ontime → به‌موقع | late & اخطار<=حد → اخطاری | late & اخطار>حد → مشمول جریمه
	 */
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
		);
	}

	/** ردیف‌های حضورِ یک جلسه به‌همراه دسته و مجموع اخطار هر کاربر (به‌ترتیب زمان ورود). */
	public static function session_rows( $session_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE session_id=%d ORDER BY checkin_at ASC, id ASC',
			(int) $session_id ) );
		$out = array();
		foreach ( $rows as $r ) {
			$uid  = (int) $r->user_id;
			$info = SZP_Groups::user_info( $uid );
			if ( ! $info ) {
				continue;
			}
			$warn = self::warnings( $uid );
			$ts   = strtotime( (string) $r->checkin_at . ' UTC' ); // checkin_at به‌صورت GMT ذخیره شده.
			$out[] = array(
				'user_id'  => $uid,
				'name'     => $info['name'],
				'mobile'   => $info['mobile'],
				'avatar'   => get_avatar_url( $uid, array( 'size' => 96 ) ),
				'status'   => (string) $r->status,
				'bucket'   => self::bucket( (string) $r->status, $warn ),
				'warnings' => $warn,
				'late_min' => (int) $r->late_min,
				'time'     => $ts ? szp_fa_digits( wp_date( 'H:i', $ts ) ) : '',
				'ts'       => $ts ? $ts : 0,
			);
		}
		return $out;
	}

	/** دادهٔ تابلو برای یک جلسه (سه ستون + متادیتا) — مصرف در AJAX و رندر اولیه. */
	public static function board_data( $session_id ) {
		$session_id = (int) $session_id;
		$cols       = array( 'ontime' => array(), 'warned' => array(), 'penalized' => array() );
		foreach ( self::session_rows( $session_id ) as $row ) {
			$cols[ $row['bucket'] ][] = $row;
		}
		$start = self::session_start( $session_id );
		return array(
			'session_id' => $session_id,
			'title'      => get_the_title( $session_id ),
			'course'     => self::course_title( $session_id ),
			'start_h'    => $start ? szp_fa_digits( wp_date( 'H:i', $start ) ) : '',
			'date'       => $start ? szp_format_datetime( $start ) : '',
			'limit'      => self::warn_limit(),
			'columns'    => $cols,
			'total'      => count( $cols['ontime'] ) + count( $cols['warned'] ) + count( $cols['penalized'] ),
		);
	}

	/* ==================== رندر: صفحهٔ ثبت حضور ==================== */

	/** شورت‌کد [sazan_attendance] — صفحه‌ای که کیوآرکد به آن اشاره می‌کند. */
	public static function render_checkin( $atts = array() ) {
		$session_id = isset( $_GET['att'] ) ? absint( $_GET['att'] ) : 0;
		$token      = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';

		// اگر روی همین برگه، تابلو درخواست شده باشد.
		if ( isset( $_GET['att_board'] ) ) {
			return self::render_board( array( 'session' => absint( $_GET['att_board'] ) ) );
		}

		if ( ! $session_id || get_post_type( $session_id ) !== 'szp_session' ) {
			return '<div class="szp"><div class="szp-empty">کیوآرکد نامعتبر است. لطفاً دوباره اسکن کنید.</div></div>';
		}
		if ( ! self::token_valid( $session_id, $token ) ) {
			return '<div class="szp"><div class="szp-empty">این کیوآرکد معتبر نیست یا منقضی شده است.</div></div>';
		}

		$title   = get_the_title( $session_id );
		$course  = self::course_title( $session_id );
		$start   = self::session_start( $session_id );
		$logged  = is_user_logged_in();
		$already = $logged ? self::has_checked_in( $session_id, get_current_user_id() ) : false;
		$name    = $logged ? wp_get_current_user()->display_name : '';

		ob_start(); ?>
		<div class="szp">
			<div class="szp-att-checkin" data-session="<?php echo esc_attr( $session_id ); ?>"
				data-token="<?php echo esc_attr( $token ); ?>"
				data-guest="<?php echo self::allow_guest() ? '1' : '0'; ?>"
				data-logged="<?php echo $logged ? '1' : '0'; ?>">
				<div class="szp-att-ci-card">
					<div class="szp-att-ci-head">
						<span class="szp-att-ci-ico">🎓</span>
						<h3 class="szp-att-ci-title">ثبت حضور</h3>
					</div>
					<div class="szp-att-ci-info">
						<div class="szp-att-ci-cls"><?php echo esc_html( $title ); ?></div>
						<?php if ( $course ) : ?><div class="szp-att-ci-course"><?php echo esc_html( $course ); ?></div><?php endif; ?>
						<?php if ( $start ) : ?><div class="szp-att-ci-time">ساعت شروع: <b><?php echo esc_html( szp_fa_digits( wp_date( 'H:i', $start ) ) ); ?></b></div><?php endif; ?>
					</div>

					<?php if ( $already ) : ?>
						<div class="szp-att-ci-done is-show">حضور شما قبلاً برای این جلسه ثبت شده است ✓</div>
					<?php elseif ( $logged ) : ?>
						<p class="szp-att-ci-hello">سلام <b><?php echo esc_html( $name ); ?></b>، برای ثبت حضور دکمهٔ زیر را بزنید.</p>
						<button type="button" class="szp-att-ci-btn" data-mode="self">ثبت حضور من</button>
					<?php else : ?>
						<?php if ( self::allow_guest() ) : ?>
							<p class="szp-att-ci-hello">برای ثبت حضور، شمارهٔ موبایل خود را وارد کنید.</p>
							<div class="szp-att-ci-form">
								<input type="text" class="szp-att-ci-mobile" inputmode="numeric" placeholder="شمارهٔ موبایل (مثلاً ۰۹۱۲...)">
								<input type="text" class="szp-att-ci-fname" placeholder="نام و نام خانوادگی (اگر بار اول است)">
								<button type="button" class="szp-att-ci-btn" data-mode="guest">ثبت حضور</button>
							</div>
						<?php else : ?>
							<p class="szp-att-ci-hello">برای ثبت حضور ابتدا وارد حساب کاربری خود شوید.</p>
							<a class="szp-att-ci-btn" href="<?php echo esc_url( wp_login_url( self::checkin_url( $session_id ) ) ); ?>">ورود</a>
						<?php endif; ?>
					<?php endif; ?>

					<div class="szp-att-ci-msg" aria-live="polite"></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== رندر: تابلوی کلاس ==================== */

	/** آیا بیننده اجازهٔ دیدن تابلو را دارد؟ (کاربر دارای دسترسی یا توکن معتبر). */
	public static function can_view_board( $session_id, $token = '' ) {
		if ( current_user_can( apply_filters( 'szp_att_board_cap', 'manage_options' ) ) ) {
			return true;
		}
		return self::token_valid( $session_id, (string) $token );
	}

	/** شورت‌کد [sazan_attendance_board] — تابلوی زندهٔ سه‌ستونی برای تلویزیون کلاس. */
	public static function render_board( $atts = array() ) {
		$a = wp_parse_args( $atts, array( 'session' => 0, 'title' => '' ) );

		$session_id = (int) $a['session'];
		if ( ! $session_id && isset( $_GET['att_board'] ) ) {
			$session_id = absint( $_GET['att_board'] );
		}
		if ( ! $session_id ) {
			$session_id = self::today_session();
		}
		$token = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : '';

		if ( ! $session_id ) {
			return '<div class="szp"><div class="szp-empty">جلسه‌ای برای نمایش تابلو یافت نشد. جلسهٔ امروز را تعریف کنید یا شناسهٔ جلسه را در شورت‌کد بدهید.</div></div>';
		}
		if ( ! self::can_view_board( $session_id, $token ) ) {
			return '<div class="szp"><div class="szp-empty">شما به تابلوی حضور و غیاب دسترسی ندارید.</div></div>';
		}

		$data = self::board_data( $session_id );
		$meta = self::statuses();

		ob_start(); ?>
		<div class="szp szp-att-fullscreen">
			<div class="szp-att-board" data-session="<?php echo esc_attr( $session_id ); ?>"
				data-token="<?php echo esc_attr( $token ); ?>"
				data-poll="<?php echo esc_attr( self::poll_sec() ); ?>"
				data-limit="<?php echo esc_attr( $data['limit'] ); ?>">

				<div class="szp-att-b-head">
					<div class="szp-att-b-titles">
						<h2 class="szp-att-b-cls"><?php echo esc_html( $data['title'] ); ?></h2>
						<div class="szp-att-b-sub">
							<?php if ( $data['course'] ) : ?><span><?php echo esc_html( $data['course'] ); ?></span><?php endif; ?>
							<?php if ( $data['start_h'] ) : ?><span>ساعت شروع: <b><?php echo esc_html( $data['start_h'] ); ?></b></span><?php endif; ?>
						</div>
					</div>
					<div class="szp-att-b-clock">
						<span class="szp-att-b-count" data-total><?php echo esc_html( szp_fa_digits( $data['total'] ) ); ?></span>
						<span class="szp-att-b-count-l">حاضر</span>
						<span class="szp-att-b-time" data-clock></span>
					</div>
				</div>

				<div class="szp-att-b-cols">
					<?php foreach ( array( 'ontime', 'warned', 'penalized' ) as $key ) :
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

	/** HTML ردیف‌های یک ستون (برای رندر اولیه و به‌روزرسانی زنده). */
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
		if ( ! empty( $r['time'] ) ) {
			$sub .= '<span class="szp-att-p-time">🕐 ' . esc_html( $r['time'] ) . '</span>';
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
			<span class="szp-att-p-warn <?php echo $warn > 0 ? 'has' : ''; ?>" title="مجموع اخطارها در تمام جلسات">
				<?php echo esc_html( szp_fa_digits( $warn ) ); ?> اخطار
			</span>
		</div>
		<?php
		return ob_get_clean();
	}
}
