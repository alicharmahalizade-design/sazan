<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** «حضور و غیاب» در پیشخوان: تولید کیوآرکد چاپی، لینک تابلوی تلویزیون، تنظیمات و گزارش هر جلسه. */
class SZP_Att_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 13 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_szp_att_settings_save', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_szp_att_manual', array( __CLASS__, 'manual_add' ) );
		add_action( 'admin_post_szp_att_delete', array( __CLASS__, 'delete_row' ) );
		// نمای چاپ کیوآرکد باید پیش از رندر پیشخوان اجرا شود.
		add_action( 'admin_init', array( __CLASS__, 'maybe_print' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'حضور و غیاب', 'حضور و غیاب', 'manage_options', 'szp-attendance', array( __CLASS__, 'render' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'szp-attendance' ) === false ) {
			return;
		}
		$qr = SZP_DIR . 'assets/js/qrcode-generator.js';
		$js = SZP_DIR . 'assets/js/sazan-att.js';
		$css = SZP_DIR . 'assets/css/sazan-att.css';
		wp_enqueue_script( 'szp-qrcode', SZP_URL . 'assets/js/qrcode-generator.js', array(), file_exists( $qr ) ? filemtime( $qr ) : SZP_VERSION, true );
		wp_enqueue_script( 'szp-att', SZP_URL . 'assets/js/sazan-att.js', array( 'szp-qrcode' ), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
		wp_enqueue_style( 'szp-att', SZP_URL . 'assets/css/sazan-att.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
	}

	protected static function current_sid() {
		$sid = isset( $_GET['sid'] ) ? absint( $_GET['sid'] ) : 0;
		if ( ! $sid ) {
			$sid = SZP_Attendance::today_session();
		}
		return $sid;
	}

	/* ==================== نمای چاپ ==================== */

	public static function maybe_print() {
		if ( empty( $_GET['page'] ) || $_GET['page'] !== 'szp-attendance' || empty( $_GET['print'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$sid = isset( $_GET['sid'] ) ? absint( $_GET['sid'] ) : 0;
		if ( ! $sid || get_post_type( $sid ) !== 'szp_session' ) {
			wp_die( 'جلسه نامعتبر است.' );
		}
		$url    = SZP_Attendance::checkin_url( $sid );
		$title  = get_the_title( $sid );
		$course = SZP_Attendance::course_title( $sid );
		$start  = SZP_Attendance::session_start( $sid );
		$lib    = file_get_contents( SZP_DIR . 'assets/js/qrcode-generator.js' );

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		?><!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>کیوآرکد حضور — <?php echo esc_html( $title ); ?></title>
<style>
	*{box-sizing:border-box;font-family:Tahoma,Vazirmatn,sans-serif}
	body{margin:0;padding:30px;text-align:center;color:#111}
	.wrap{max-width:520px;margin:0 auto;border:2px solid #111;border-radius:20px;padding:32px 24px}
	h1{font-size:26px;margin:0 0 6px}
	.course{font-size:16px;color:#444;margin-bottom:2px}
	.time{font-size:16px;color:#444;margin-bottom:18px}
	.qr{width:340px;max-width:100%;margin:0 auto;line-height:0}
	.qr img{width:100%;height:auto;image-rendering:pixelated}
	.hint{font-size:18px;font-weight:800;margin-top:18px}
	.sub{font-size:13px;color:#666;margin-top:8px;word-break:break-all;direction:ltr}
	.print-btn{margin:22px 0;padding:12px 26px;font-size:16px;border:0;border-radius:10px;background:#0f9fb3;color:#fff;cursor:pointer}
	@media print{.print-btn{display:none}.wrap{border-color:#000}}
</style></head><body>
	<button class="print-btn" onclick="window.print()">🖨️ چاپ</button>
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( $course ) : ?><div class="course"><?php echo esc_html( $course ); ?></div><?php endif; ?>
		<?php if ( $start ) : ?><div class="time">ساعت شروع کلاس: <?php echo esc_html( szp_fa_digits( wp_date( 'H:i', $start ) ) ); ?></div><?php endif; ?>
		<div class="qr" id="qr"></div>
		<div class="hint">برای ثبت حضور، این کیوآرکد را اسکن کنید</div>
		<div class="sub"><?php echo esc_html( $url ); ?></div>
	</div>
	<script><?php echo $lib; // phpcs:ignore WordPress.Security.EscapeOutput ?></script>
	<script>
		(function(){
			var qr=qrcode(0,'M');qr.addData(<?php echo wp_json_encode( $url ); ?>);qr.make();
			document.getElementById('qr').innerHTML=qr.createImgTag(10,4);
		})();
	</script>
</body></html>
		<?php
		exit;
	}

	/* ==================== صفحهٔ اصلی ==================== */

	public static function render() {
		$sid      = self::current_sid();
		$sessions = SZP_Attendance::sessions( 200 );
		$s        = SZP_Attendance::settings();
		?>
		<div class="wrap szp-groups-wrap">
			<h1>حضور و غیاب</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['msg'] ) ) ); ?></p></div>
			<?php endif; ?>

			<p class="description">برای هر جلسه یک کیوآرکد بسازید و دم درِ کلاس بچسبانید. دانشجو با اسکن، حضورش (ساعت، روز و کلاس) ثبت می‌شود. تابلوی کلاس را روی تلویزیون باز کنید تا حضورها به‌صورت زنده نمایش داده شود.</p>

			<form method="get" style="margin:14px 0">
				<input type="hidden" name="page" value="szp-attendance">
				<label style="font-weight:700">انتخاب جلسه: </label>
				<select name="sid" onchange="this.form.submit()" style="min-width:320px">
					<option value="0">— انتخاب جلسه —</option>
					<?php foreach ( $sessions as $ses ) :
						$st = SZP_Attendance::session_start( $ses->ID );
						$lbl = $ses->post_title . ( $st ? ' — ' . szp_format_datetime( $st ) : '' );
						?>
						<option value="<?php echo esc_attr( $ses->ID ); ?>" <?php selected( $sid, $ses->ID ); ?>><?php echo esc_html( $lbl ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>

			<?php if ( $sid && get_post_type( $sid ) === 'szp_session' ) : self::render_session_panel( $sid ); endif; ?>

			<hr style="margin:30px 0">
			<?php self::render_settings( $s ); ?>
		</div>
		<?php
	}

	protected static function render_session_panel( $sid ) {
		$url       = SZP_Attendance::checkin_url( $sid );
		$board_url = SZP_Attendance::board_url( $sid );
		$data      = SZP_Attendance::board_data( $sid );
		$meta      = SZP_Attendance::statuses();
		$print_url = add_query_arg( array( 'page' => 'szp-attendance', 'sid' => $sid, 'print' => 1 ), admin_url( 'admin.php' ) );
		?>
		<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start;margin-top:10px">
			<div style="flex:0 0 300px">
				<h2 style="margin-top:0">کیوآرکد این جلسه</h2>
				<div class="szp-att-qr" data-url="<?php echo esc_attr( $url ); ?>" data-size="6"></div>
				<p style="margin-top:12px">
					<a class="button button-primary" href="<?php echo esc_url( $print_url ); ?>" target="_blank">🖨️ چاپ کیوآرکد</a>
				</p>
				<p class="description" style="word-break:break-all;direction:ltr"><?php echo esc_html( $url ); ?></p>
			</div>

			<div style="flex:1;min-width:320px">
				<h2 style="margin-top:0">تابلوی کلاس (تلویزیون)</h2>
				<p>این لینک را روی مرورگر تلویزیون/نمایشگر کلاس باز کنید تا حضورها به‌صورت زنده و در سه ستون نمایش داده شود:</p>
				<p><a class="button" href="<?php echo esc_url( $board_url ); ?>" target="_blank">↗ باز کردن تابلوی زنده</a></p>
				<p class="description" style="word-break:break-all;direction:ltr"><?php echo esc_html( $board_url ); ?></p>
				<p class="description">همچنین می‌توانید شورت‌کد <code>[sazan_attendance_board session="<?php echo (int) $sid; ?>"]</code> را در یک برگه قرار دهید.</p>
			</div>
		</div>

		<h2 style="margin-top:26px">حاضران این جلسه
			<span style="font-size:13px;font-weight:400">(به‌موقع: <?php echo esc_html( szp_fa_digits( count( $data['columns']['ontime'] ) ) ); ?> |
			اخطاری: <?php echo esc_html( szp_fa_digits( count( $data['columns']['warned'] ) ) ); ?> |
			مشمول جریمه: <?php echo esc_html( szp_fa_digits( count( $data['columns']['penalized'] ) ) ); ?>)</span>
		</h2>

		<?php $rows = SZP_Attendance::session_rows( $sid ); ?>
		<table class="widefat striped" style="max-width:900px">
			<thead><tr>
				<th>نام</th><th>موبایل</th><th>ساعت ثبت</th><th>وضعیت ورود</th><th>دسته</th><th>مجموع اخطارها</th><th></th>
			</tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="7">هنوز کسی برای این جلسه حضور نزده است.</td></tr>
			<?php else : foreach ( $rows as $r ) :
				$bm = $meta[ $r['bucket'] ]; ?>
				<tr>
					<td><?php echo esc_html( $r['name'] ); ?></td>
					<td dir="ltr"><?php echo esc_html( szp_fa_digits( $r['mobile'] ) ); ?></td>
					<td><?php echo esc_html( $r['time'] ); ?><?php echo $r['status'] === 'late' && $r['late_min'] ? ' <small style="color:#c17d09">(' . esc_html( szp_fa_digits( $r['late_min'] ) ) . ' دقیقه تأخیر)</small>' : ''; ?></td>
					<td><?php echo $r['status'] === 'late' ? 'با تأخیر' : 'به‌موقع'; ?></td>
					<td><span style="color:<?php echo esc_attr( $bm['color'] ); ?>;font-weight:700"><?php echo esc_html( $bm['emoji'] . ' ' . $bm['label'] ); ?></span></td>
					<td><?php echo esc_html( szp_fa_digits( $r['warnings'] ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'szp_att_delete', 'sid' => $sid, 'uid' => $r['user_id'] ), admin_url( 'admin-post.php' ) ), 'szp_att_delete' ) ); ?>"
							onclick="return confirm('حذف حضور این فرد؟');" style="color:#b32d2e">حذف</a>
					</td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>

		<h3 style="margin-top:20px">ثبت دستی حضور</h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
			<?php wp_nonce_field( 'szp_att_manual' ); ?>
			<input type="hidden" name="action" value="szp_att_manual">
			<input type="hidden" name="sid" value="<?php echo (int) $sid; ?>">
			<input type="text" name="mobile" placeholder="موبایل دانشجو" dir="ltr" required>
			<input type="text" name="name" placeholder="نام (اگر کاربر جدید است)">
			<button class="button button-secondary">ثبت حضور دستی</button>
			<span class="description">حضور با ساعت فعلی ثبت می‌شود (اگر بعد از شروع کلاس باشد، اخطار می‌خورد).</span>
		</form>
		<?php
	}

	protected static function render_settings( $s ) {
		$pages = get_pages();
		?>
		<h2>تنظیمات حضور و غیاب</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'szp_att_settings' ); ?>
			<input type="hidden" name="action" value="szp_att_settings_save">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label>ارفاق (دقیقه)</label></th>
					<td><input type="number" name="grace_min" min="0" value="<?php echo esc_attr( $s['grace_min'] ); ?>" class="small-text">
						<p class="description">تا این دقیقه بعد از ساعت شروع کلاس، حضور هنوز «به‌موقع» حساب می‌شود. پیش‌فرض ۰.</p></td>
				</tr>
				<tr>
					<th scope="row"><label>حد مجاز اخطار</label></th>
					<td><input type="number" name="warn_limit" min="0" value="<?php echo esc_attr( $s['warn_limit'] ); ?>" class="small-text">
						<p class="description">اگر مجموع اخطارهای یک نفر <b>بیشتر</b> از این عدد شود، «مشمول جریمه» می‌شود. پیش‌فرض ۳.</p></td>
				</tr>
				<tr>
					<th scope="row"><label>فاصلهٔ به‌روزرسانی تابلو (ثانیه)</label></th>
					<td><input type="number" name="poll_sec" min="3" value="<?php echo esc_attr( $s['poll_sec'] ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row">ثبت با موبایل</th>
					<td><label><input type="checkbox" name="allow_guest" value="1" <?php checked( ! empty( $s['allow_guest'] ) ); ?>> اجازه بده دانشجوی واردنشده با شمارهٔ موبایل حضور بزند (توصیه‌شده برای اسکن دمِ در)</label></td>
				</tr>
				<tr>
					<th scope="row"><label>برگهٔ ثبت حضور</label></th>
					<td>
						<select name="checkin_page">
							<option value="0">— انتخاب برگه —</option>
							<?php foreach ( $pages as $p ) : ?>
								<option value="<?php echo (int) $p->ID; ?>" <?php selected( (int) $s['checkin_page'], (int) $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">برگه‌ای که شورت‌کد <code>[sazan_attendance]</code> در آن قرار دارد. کیوآرکد به این برگه اشاره می‌کند.</p>
					</td>
				</tr>
			</table>
			<p><button class="button button-primary">ذخیره تنظیمات</button></p>
		</form>
		<?php
	}

	/* ==================== ذخیره / عملیات ==================== */

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_att_settings' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$p   = wp_unslash( $_POST );
		$cur = SZP_Attendance::settings();
		$new = array(
			'grace_min'    => max( 0, absint( $p['grace_min'] ?? 0 ) ),
			'warn_limit'   => max( 0, absint( $p['warn_limit'] ?? 3 ) ),
			'poll_sec'     => max( 3, absint( $p['poll_sec'] ?? 6 ) ),
			'allow_guest'  => empty( $p['allow_guest'] ) ? 0 : 1,
			'checkin_page' => absint( $p['checkin_page'] ?? 0 ),
		);
		update_option( SZP_Attendance::OPTION, array_merge( $cur, $new ) );
		self::redirect( 'تنظیمات ذخیره شد.' );
	}

	public static function manual_add() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_att_manual' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$sid    = absint( $_POST['sid'] ?? 0 );
		$mobile = szp_normalize_mobile( wp_unslash( $_POST['mobile'] ?? '' ) );
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		if ( ! $sid || strlen( $mobile ) < 4 ) {
			self::redirect( 'اطلاعات ناقص است.', $sid );
		}
		$parts = preg_split( '/\s+/', trim( $name ), 2 );
		$res   = SZP_Groups::create_user_from_phone( $mobile, $parts[0] ?? '', $parts[1] ?? '' );
		if ( empty( $res['id'] ) ) {
			self::redirect( 'کاربر ساخته/یافت نشد.', $sid );
		}
		$rec = SZP_Attendance::record( $sid, (int) $res['id'] );
		$msg = ! empty( $rec['already'] ) ? 'این فرد قبلاً حضور داشت.' : 'حضور دستی ثبت شد.';
		self::redirect( $msg, $sid );
	}

	public static function delete_row() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_att_delete' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$sid = absint( $_GET['sid'] ?? 0 );
		$uid = absint( $_GET['uid'] ?? 0 );
		SZP_Attendance::delete( $sid, $uid );
		self::redirect( 'حضور حذف شد.', $sid );
	}

	protected static function redirect( $msg, $sid = 0 ) {
		$args = array( 'page' => 'szp-attendance', 'msg' => $msg );
		if ( $sid ) {
			$args['sid'] = $sid;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
