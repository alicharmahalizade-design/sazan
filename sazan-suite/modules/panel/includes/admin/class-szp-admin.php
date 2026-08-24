<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		add_menu_page( 'پنل سازان', 'پنل سازان', 'manage_options', 'sazan-panel',
			array( __CLASS__, 'dashboard' ), 'dashicons-welcome-learn-more', 26 );
		add_submenu_page( 'sazan-panel', 'داشبورد', 'داشبورد', 'manage_options', 'sazan-panel',
			array( __CLASS__, 'dashboard' ) );
		// CPTs (دوره‌ها / جلسات) attach via show_in_menu. Groups submenu in SZP_Groups_Page.
	}

	public static function dashboard() {
		?>
		<div class="wrap szp-admin-dash">
			<div class="szp-admin-welcome">
				<div>
					<span>مدیریت ساده دوره، رشد و کوچینگ</span>
					<h1>پنل سازان</h1>
					<p>از این صفحه فقط مسیر کارتان را انتخاب کنید. همه متن‌ها، رنگ‌ها، پیامک‌ها و تنظیمات هفتگی در «مرکز تنظیمات» یکجا قرار دارند.</p>
				</div>
				<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=sazan-suite' ) ); ?>">ورود به مرکز تنظیمات</a>
			</div>

			<h2 class="szp-admin-title">می‌خواهید چه کاری انجام دهید؟</h2>
			<div class="szp-admin-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=sazan-suite' ) ); ?>"><span class="dashicons dashicons-admin-settings"></span><b>تنظیم افزونه</b><small>متن، ظاهر، تقویم، دسترسی و پیامک</small></a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=szp_course' ) ); ?>"><span class="dashicons dashicons-welcome-learn-more"></span><b>دوره‌ها</b><small>ساخت و ویرایش دوره‌های آموزشی</small></a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=szp_session' ) ); ?>"><span class="dashicons dashicons-calendar-alt"></span><b>جلسات</b><small>محتوا، تمرین و زمان‌بندی جلسه</small></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-roles' ) ); ?>"><span class="dashicons dashicons-groups"></span><b>نقش‌ها و تیم‌ها</b><small>کوچ، مدیر سازمان و تیم فروش</small></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-eval' ) ); ?>"><span class="dashicons dashicons-chart-line"></span><b>گزارش‌های هفتگی</b><small>تارگت‌ها، نتیجه‌ها و خروجی اطلاعات</small></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-coaching' ) ); ?>"><span class="dashicons dashicons-businessperson"></span><b>کوچینگ</b><small>برنامه و گزارش دانشجویان</small></a>
			</div>

			<div class="szp-admin-guide">
				<div>
					<h2>راه‌اندازی در سه قدم</h2>
					<ol>
						<li><b>مرکز تنظیمات</b> را باز و تاریخ جلسه جاری، نقش‌ها و ظاهر را بررسی کنید.</li>
						<li>یک <b>دوره</b> و جلسات آن را بسازید؛ سپس کاربران یا گروه مجاز را انتخاب کنید.</li>
						<li>شورت‌کد <code>[sazan_growth]</code> را در برگه پنل کاربر قرار دهید.</li>
					</ol>
				</div>
				<div class="szp-admin-shortcodes">
					<h2>کدهای آماده نمایش</h2>
					<p><code>[sazan_growth]</code><span>رشد، ارزیابی و کوچینگ یکپارچه</span></p>
					<p><code>[sazan_panel]</code><span>دوره‌ها و جلسات اختصاصی</span></p>
					<p><code>[sazan_courses view="grid"]</code><span>فهرست شبکه‌ای دوره‌ها</span></p>
				</div>
			</div>
		</div>
		<?php
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		$is_cpt = $screen && in_array( $screen->post_type, array( 'szp_course', 'szp_session' ), true );
		$is_szp = ( strpos( (string) $hook, 'sazan-panel' ) !== false ) || ( strpos( (string) $hook, 'szp-groups' ) !== false ) || ( strpos( (string) $hook, 'szp-eval' ) !== false ) || $is_cpt;
		if ( ! $is_szp ) {
			return;
		}

		wp_enqueue_media();

		$css = SZP_DIR . 'assets/css/admin.css';
		$js  = SZP_DIR . 'assets/js/admin.js';
		wp_enqueue_style( 'szp-admin', SZP_URL . 'assets/css/admin.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
		wp_enqueue_script( 'szp-admin', SZP_URL . 'assets/js/admin.js', array( 'jquery' ), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
		wp_localize_script( 'szp-admin', 'SZP_ADMIN', array(
			'ajax'         => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'szp_admin' ),
			'media_title'  => 'انتخاب فایل',
			'media_button' => 'استفاده از این فایل',
		) );
	}
}
