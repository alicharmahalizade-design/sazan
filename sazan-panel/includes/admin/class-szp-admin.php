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
			<h1>پنل سازان</h1>
			<p>برای نمایش دوره‌ها و جلسات کاربر در پنل کاربری، این شورت‌کد را در تب دلخواه قرار دهید:</p>
			<p style="font-size:16px"><code>[sazan_panel]</code></p>
			<hr>
			<h2>راهنمای سریع</h2>
			<ol>
				<li>یک <strong>دوره</strong> بسازید و شناسنامه، اعلانات و فایل‌ها را پر کنید.</li>
				<li>برای هر دوره چند <strong>جلسه</strong> بسازید و دوره مرتبط و تاریخ آن را مشخص کنید.</li>
				<li>در صورت نیاز در «<strong>گروه‌ها</strong>» گروه بسازید و کاربران را اضافه کنید.</li>
				<li>در صفحه ویرایش هر دوره، باکس «<strong>دسترسی</strong>» کاربران یا گروه‌های مجاز را انتخاب کنید.</li>
				<li>با «<strong>اتصال محصول ووکامرس</strong>» خرید محصول، دسترسی را خودکار می‌دهد.</li>
			</ol>
			<hr>
			<h2>شورت‌کدهای کمکی</h2>
			<p>نمایش دوره‌های کاربر به‌صورت شبکه‌ای یا کاروسل (مثلاً در داشبورد):</p>
			<p><code>[sazan_courses view="grid"]</code> &nbsp; یا &nbsp; <code>[sazan_courses view="carousel"]</code></p>
			<p>نمایش جلسات کاربر:</p>
			<p><code>[sazan_sessions view="carousel" count="8"]</code> &nbsp; (برای یک دوره خاص: <code>course="شناسه دوره"</code>)</p>
			<p class="description">اگر این شورت‌کدها در صفحه‌ای جدا از تب پنل هستند، با <code>panel="شناسه برگه پنل"</code> کارت‌ها را به تب پنل لینک کنید.</p>
		</div>
		<?php
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		$is_cpt = $screen && in_array( $screen->post_type, array( 'szp_course', 'szp_session' ), true );
		$is_szp = ( strpos( (string) $hook, 'sazan-panel' ) !== false ) || ( strpos( (string) $hook, 'szp-groups' ) !== false ) || $is_cpt;
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
