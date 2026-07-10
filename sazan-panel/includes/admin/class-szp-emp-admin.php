<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** «حضور کارمندان» در پیشخوان: کیوآرکد ثابت چاپی، لینک تابلو، تنظیمات و گزارش امروز. */
class SZP_Emp_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 14 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_szp_emp_settings_save', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_szp_emp_manual', array( __CLASS__, 'manual_add' ) );
		add_action( 'admin_post_szp_emp_delete', array( __CLASS__, 'delete_row' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_print' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'حضور کارمندان', 'حضور کارمندان', 'manage_options', 'szp-emp-attendance', array( __CLASS__, 'render' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'szp-emp-attendance' ) === false ) {
			return;
		}
		$qr  = SZP_DIR . 'assets/js/qrcode-generator.js';
		$js  = SZP_DIR . 'assets/js/sazan-att.js';
		$css = SZP_DIR . 'assets/css/sazan-att.css';
		wp_enqueue_script( 'szp-qrcode', SZP_URL . 'assets/js/qrcode-generator.js', array(), file_exists( $qr ) ? filemtime( $qr ) : SZP_VERSION, true );
		wp_enqueue_script( 'szp-att', SZP_URL . 'assets/js/sazan-att.js', array( 'szp-qrcode' ), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
		wp_enqueue_style( 'szp-att', SZP_URL . 'assets/css/sazan-att.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
	}

	/* ==================== نمای چاپ ==================== */

	public static function maybe_print() {
		if ( empty( $_GET['page'] ) || $_GET['page'] !== 'szp-emp-attendance' || empty( $_GET['print'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$url = SZP_Emp_Attendance::checkin_url();
		$lib = file_get_contents( SZP_DIR . 'assets/js/qrcode-generator.js' );

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		?><!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>کیوآرکد حضور کارمندان</title>
<style>
	*{box-sizing:border-box;font-family:Tahoma,Vazirmatn,sans-serif}
	body{margin:0;padding:30px;text-align:center;color:#111}
	.wrap{max-width:520px;margin:0 auto;border:2px solid #111;border-radius:20px;padding:32px 24px}
	h1{font-size:26px;margin:0 0 14px}
	.qr{width:340px;max-width:100%;margin:0 auto;line-height:0}
	.qr img{width:100%;height:auto;image-rendering:pixelated}
	.hint{font-size:18px;font-weight:800;margin-top:18px}
	.sub{font-size:13px;color:#666;margin-top:8px;word-break:break-all;direction:ltr}
	.print-btn{margin:22px 0;padding:12px 26px;font-size:16px;border:0;border-radius:10px;background:#0f9fb3;color:#fff;cursor:pointer}
	@media print{.print-btn{display:none}.wrap{border-color:#000}}
</style></head><body>
	<button class="print-btn" onclick="window.print()">🖨️ چاپ</button>
	<div class="wrap">
		<h1>ثبت حضور و غیاب کارمندان</h1>
		<div class="qr" id="qr"></div>
		<div class="hint">برای ثبت ورود/خروج، این کیوآرکد را اسکن کنید</div>
		<div class="sub"><?php echo esc_html( $url ); ?></div>
	</div>
	<script><?php echo $lib; // phpcs:ignore WordPress.Security.EscapeOutput ?></script>
	<script>(function(){var qr=qrcode(0,'M');qr.addData(<?php echo wp_json_encode( $url ); ?>);qr.make();document.getElementById('qr').innerHTML=qr.createImgTag(10,4);})();</script>
</body></html>
		<?php
		exit;
	}

	/* ==================== صفحهٔ اصلی ==================== */

	public static function render() {
		$s = SZP_Emp_Attendance::settings();
		?>
		<div class="wrap szp-groups-wrap">
			<h1>حضور و غیاب کارمندان</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['msg'] ) ) ); ?></p></div>
			<?php endif; ?>

			<p class="description">یک کیوآرکد ثابت بسازید و دمِ درِ محل کار بچسبانید. کارمندان هر روز اسکن می‌کنند و ورودشان ثبت می‌شود (اسکن دوم = خروج). هرکس بعد از ساعت شروع کار (به‌علاوهٔ ارفاق) بیاید اخطار می‌گیرد و با عبور از حد مجاز مشمول جریمه می‌شود.</p>

			<?php self::render_panel(); ?>

			<hr style="margin:30px 0">
			<?php self::render_settings( $s ); ?>
		</div>
		<?php
	}

	protected static function render_panel() {
		$url       = SZP_Emp_Attendance::checkin_url();
		$board_url = SZP_Emp_Attendance::board_url();
		$data      = SZP_Emp_Attendance::board_data();
		$meta      = SZP_Emp_Attendance::statuses();
		$print_url = add_query_arg( array( 'page' => 'szp-emp-attendance', 'print' => 1 ), admin_url( 'admin.php' ) );
		?>
		<div style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start;margin-top:10px">
			<div style="flex:0 0 300px">
				<h2 style="margin-top:0">کیوآرکد ثابت کارمندان</h2>
				<div class="szp-att-qr" data-url="<?php echo esc_attr( $url ); ?>" data-size="6"></div>
				<p style="margin-top:12px"><a class="button button-primary" href="<?php echo esc_url( $print_url ); ?>" target="_blank">🖨️ چاپ کیوآرکد</a></p>
				<p class="description" style="word-break:break-all;direction:ltr"><?php echo esc_html( $url ); ?></p>
			</div>
			<div style="flex:1;min-width:320px">
				<h2 style="margin-top:0">تابلوی حضور (تلویزیون)</h2>
				<p>این لینک را روی نمایشگر محل کار باز کنید تا حضور کارمندان به‌صورت زنده در سه ستون (و در صورت تعیین گروه، غایبان) نمایش داده شود:</p>
				<p><a class="button" href="<?php echo esc_url( $board_url ); ?>" target="_blank">↗ باز کردن تابلوی زنده</a></p>
				<p class="description" style="word-break:break-all;direction:ltr"><?php echo esc_html( $board_url ); ?></p>
				<p class="description">همچنین شورت‌کد <code>[sazan_employee_board]</code> را می‌توانید در یک برگه قرار دهید.</p>
			</div>
		</div>

		<h2 style="margin-top:26px">حضور امروز
			<span style="font-size:13px;font-weight:400">(به‌موقع: <?php echo esc_html( szp_fa_digits( count( $data['columns']['ontime'] ) ) ); ?> |
			اخطاری: <?php echo esc_html( szp_fa_digits( count( $data['columns']['warned'] ) ) ); ?> |
			مشمول جریمه: <?php echo esc_html( szp_fa_digits( count( $data['columns']['penalized'] ) ) ); ?><?php echo $data['has_roster'] ? ' | غایب: ' . esc_html( szp_fa_digits( $data['absent'] ) ) : ''; ?>)</span>
		</h2>

		<?php $rows = SZP_Emp_Attendance::today_rows(); ?>
		<table class="widefat striped" style="max-width:980px">
			<thead><tr><th>نام</th><th>موبایل</th><th>ورود</th><th>خروج</th><th>وضعیت</th><th>دسته</th><th>مجموع اخطارها</th><th></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="8">هنوز کسی امروز حضور نزده است.</td></tr>
			<?php else : foreach ( $rows as $r ) :
				$bm = $meta[ $r['bucket'] ]; ?>
				<tr>
					<td><?php echo esc_html( $r['name'] ); ?></td>
					<td dir="ltr"><?php echo esc_html( szp_fa_digits( $r['mobile'] ) ); ?></td>
					<td><?php echo esc_html( $r['in'] ); ?><?php echo $r['status'] === 'late' && $r['late_min'] ? ' <small style="color:#c17d09">(' . esc_html( szp_fa_digits( $r['late_min'] ) ) . ' دقیقه تأخیر)</small>' : ''; ?></td>
					<td><?php echo $r['out'] ? esc_html( $r['out'] ) : '—'; ?></td>
					<td><?php echo $r['status'] === 'late' ? 'با تأخیر' : 'به‌موقع'; ?></td>
					<td><span style="color:<?php echo esc_attr( $bm['color'] ); ?>;font-weight:700"><?php echo esc_html( $bm['emoji'] . ' ' . $bm['label'] ); ?></span></td>
					<td><?php echo esc_html( szp_fa_digits( $r['warnings'] ) ); ?></td>
					<td><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'szp_emp_delete', 'uid' => $r['user_id'] ), admin_url( 'admin-post.php' ) ), 'szp_emp_delete' ) ); ?>" onclick="return confirm('حذف حضور امروزِ این فرد؟');" style="color:#b32d2e">حذف</a></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>

		<h3 style="margin-top:20px">ثبت دستی حضور امروز</h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
			<?php wp_nonce_field( 'szp_emp_manual' ); ?>
			<input type="hidden" name="action" value="szp_emp_manual">
			<input type="text" name="mobile" placeholder="موبایل کارمند" dir="ltr" required>
			<input type="text" name="name" placeholder="نام (اگر کاربر جدید است)">
			<button class="button button-secondary">ثبت حضور دستی</button>
			<span class="description">با ساعت فعلی ثبت می‌شود (اگر بعد از شروع کار باشد، اخطار می‌خورد).</span>
		</form>
		<?php
	}

	protected static function render_settings( $s ) {
		$pages  = get_pages();
		$groups = SZP_Groups::all();
		$days   = array( 6 => 'شنبه', 7 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه' );
		?>
		<h2>تنظیمات حضور کارمندان</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'szp_emp_settings' ); ?>
			<input type="hidden" name="action" value="szp_emp_settings_save">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label>ساعت شروع کار</label></th>
					<td><input type="time" name="work_start" value="<?php echo esc_attr( $s['work_start'] ); ?>"> <span class="description">مثلاً ۰۹:۰۰ — ورود بعد از این ساعت اخطار دارد.</span></td>
				</tr>
				<tr>
					<th scope="row"><label>ارفاق (دقیقه)</label></th>
					<td><input type="number" name="grace_min" min="0" value="<?php echo esc_attr( $s['grace_min'] ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row"><label>حد مجاز اخطار</label></th>
					<td><input type="number" name="warn_limit" min="0" value="<?php echo esc_attr( $s['warn_limit'] ); ?>" class="small-text">
						<p class="description">اگر مجموع اخطارهای یک نفر <b>بیشتر</b> از این عدد شود، «مشمول جریمه» می‌شود.</p></td>
				</tr>
				<tr>
					<th scope="row"><label>فاصلهٔ به‌روزرسانی تابلو (ثانیه)</label></th>
					<td><input type="number" name="poll_sec" min="3" value="<?php echo esc_attr( $s['poll_sec'] ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th scope="row">ثبت خروج</th>
					<td><label><input type="checkbox" name="allow_checkout" value="1" <?php checked( ! empty( $s['allow_checkout'] ) ); ?>> اسکن دوم در همان روز، «خروج» را ثبت کند</label></td>
				</tr>
				<tr>
					<th scope="row">ثبت با موبایل</th>
					<td><label><input type="checkbox" name="allow_guest" value="1" <?php checked( ! empty( $s['allow_guest'] ) ); ?>> اجازه بده کارمند واردنشده با شمارهٔ موبایل ثبت کند</label></td>
				</tr>
				<tr>
					<th scope="row"><label>گروه کارمندان</label></th>
					<td>
						<select name="roster_group">
							<option value="0">— بدون گروه —</option>
							<?php foreach ( $groups as $g ) : ?>
								<option value="<?php echo (int) $g->id; ?>" <?php selected( (int) $s['roster_group'], (int) $g->id ); ?>><?php echo esc_html( $g->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">اگر گروهی انتخاب شود، «غایبانِ امروز» (اعضایی که ثبت نکرده‌اند) روی تابلو نمایش داده می‌شوند.</p>
						<label style="display:block;margin-top:6px"><input type="checkbox" name="restrict" value="1" <?php checked( ! empty( $s['restrict'] ) ); ?>> فقط اعضای این گروه اجازهٔ ثبت داشته باشند</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label>روزهای کاری</label></th>
					<td>
						<?php foreach ( $days as $n => $label ) : ?>
							<label style="display:inline-block;margin-inline-end:12px"><input type="checkbox" name="workdays[]" value="<?php echo (int) $n; ?>" <?php checked( in_array( (int) $n, array_map( 'intval', (array) $s['workdays'] ), true ) ); ?>> <?php echo esc_html( $label ); ?></label>
						<?php endforeach; ?>
						<p class="description">در روزهای غیرکاری، کسی غایب حساب نمی‌شود.</p>
					</td>
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
						<p class="description">برگه‌ای که شورت‌کد <code>[sazan_employee_attendance]</code> در آن قرار دارد.</p>
					</td>
				</tr>
			</table>
			<p><button class="button button-primary">ذخیره تنظیمات</button></p>
		</form>
		<?php
	}

	/* ==================== ذخیره / عملیات ==================== */

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_emp_settings' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$p    = wp_unslash( $_POST );
		$cur  = SZP_Emp_Attendance::settings();
		$ws   = preg_match( '/^\d{1,2}:\d{2}$/', (string) ( $p['work_start'] ?? '' ) ) ? $p['work_start'] : '09:00';
		$days = isset( $p['workdays'] ) ? array_values( array_unique( array_map( 'intval', (array) $p['workdays'] ) ) ) : array();
		$days = array_values( array_filter( $days, function ( $n ) { return $n >= 1 && $n <= 7; } ) );
		$new  = array(
			'work_start'     => sanitize_text_field( $ws ),
			'grace_min'      => max( 0, absint( $p['grace_min'] ?? 0 ) ),
			'warn_limit'     => max( 0, absint( $p['warn_limit'] ?? 3 ) ),
			'poll_sec'       => max( 3, absint( $p['poll_sec'] ?? 6 ) ),
			'allow_guest'    => empty( $p['allow_guest'] ) ? 0 : 1,
			'allow_checkout' => empty( $p['allow_checkout'] ) ? 0 : 1,
			'roster_group'   => absint( $p['roster_group'] ?? 0 ),
			'restrict'       => empty( $p['restrict'] ) ? 0 : 1,
			'checkin_page'   => absint( $p['checkin_page'] ?? 0 ),
			'workdays'       => $days ? $days : SZP_Emp_Attendance::defaults()['workdays'],
		);
		update_option( SZP_Emp_Attendance::OPTION, array_merge( $cur, $new ) );
		self::redirect( 'تنظیمات ذخیره شد.' );
	}

	public static function manual_add() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_emp_manual' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$mobile = szp_normalize_mobile( wp_unslash( $_POST['mobile'] ?? '' ) );
		$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		if ( strlen( $mobile ) < 4 ) {
			self::redirect( 'شمارهٔ موبایل ناقص است.' );
		}
		$parts = preg_split( '/\s+/', trim( $name ), 2 );
		$res   = SZP_Groups::create_user_from_phone( $mobile, $parts[0] ?? '', $parts[1] ?? '' );
		if ( empty( $res['id'] ) ) {
			self::redirect( 'کاربر ساخته/یافت نشد.' );
		}
		$rec = SZP_Emp_Attendance::record( (int) $res['id'] );
		$msg = ( ! empty( $rec['action'] ) && $rec['action'] === 'already' ) ? 'این فرد امروز قبلاً حضور داشت.' : 'حضور دستی ثبت شد.';
		self::redirect( $msg );
	}

	public static function delete_row() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_emp_delete' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		SZP_Emp_Attendance::delete( absint( $_GET['uid'] ?? 0 ) );
		self::redirect( 'حضور حذف شد.' );
	}

	protected static function redirect( $msg ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-emp-attendance', 'msg' => $msg ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
