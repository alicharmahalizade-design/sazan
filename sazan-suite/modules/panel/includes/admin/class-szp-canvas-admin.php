<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * مدیریت ادمین بوم طراحی خدمت:
 *   - تنظیم کلید/مدل هوش مصنوعی گپ جی‌پی‌تی
 *   - مرور بوم کاربران، تشخیص موارد تکراری با هوش مصنوعی، حذف و ذخیره
 *   - خروجی ZIP از همه بوم‌ها
 */
class SZP_Canvas_Admin {

	const PAGE = 'szp-canvas';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 13 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_szp_ai_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_szp_canvas_export', array( __CLASS__, 'export_zip' ) );
		add_action( 'wp_ajax_szp_canvas_ai_dupes', array( __CLASS__, 'ajax_ai_dupes' ) );
		add_action( 'wp_ajax_szp_canvas_admin_save', array( __CLASS__, 'ajax_save' ) );
		add_action( 'wp_ajax_szp_ai_test', array( __CLASS__, 'ajax_test' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'بوم طراحی خدمت', 'بوم طراحی خدمت', 'manage_options',
			self::PAGE, array( __CLASS__, 'page' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, self::PAGE ) === false ) {
			return;
		}
		$css = SZP_DIR . 'assets/css/sazan-canvas-admin.css';
		$js  = SZP_DIR . 'assets/js/sazan-canvas-admin.js';
		wp_enqueue_style( 'szp-canvas-admin', SZP_URL . 'assets/css/sazan-canvas-admin.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
		wp_enqueue_script( 'szp-canvas-admin', SZP_URL . 'assets/js/sazan-canvas-admin.js', array(), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
		wp_localize_script( 'szp-canvas-admin', 'SZP_CV_ADMIN', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szp_admin' ),
		) );
	}

	/* ==================== تنظیمات هوش مصنوعی ==================== */

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'عدم دسترسی.' ); }
		check_admin_referer( 'szp_ai_settings' );

		update_option( SZP_AI::OPT_KEY, sanitize_text_field( wp_unslash( $_POST['ai_key'] ?? '' ) ) );
		update_option( SZP_AI::OPT_MODEL, sanitize_text_field( wp_unslash( $_POST['ai_model'] ?? '' ) ) );
		update_option( SZP_AI::OPT_BASE, esc_url_raw( wp_unslash( $_POST['ai_base'] ?? '' ) ) );

		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ==================== خروجی ZIP ==================== */

	public static function export_zip() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'عدم دسترسی.' ); }
		check_admin_referer( 'szp_canvas_export' );

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( 'افزونه‌ی ZipArchive روی سرور فعال نیست؛ خروجی ZIP ممکن نیست.' );
		}

		$rows     = SZP_Canvas::all_rows();
		$sections = SZP_Canvas::sections();

		$tmp = wp_tempnam( 'szp-canvas' );
		$zip = new ZipArchive();
		if ( $zip->open( $tmp, ZipArchive::OVERWRITE ) !== true ) {
			wp_die( 'خطا در ساخت فایل ZIP.' );
		}

		// CSV تجمیعی (با BOM برای نمایش درست فارسی در اکسل).
		$csv = "\xEF\xBB\xBF" . "canvas_key,user_id,user,section,item\n";
		foreach ( $rows as $r ) {
			$data = SZP_Canvas::normalize( json_decode( (string) $r->data, true ) ?: array() );
			$u    = get_userdata( $r->user_id );
			$name = $u ? $u->display_name : ( 'user-' . (int) $r->user_id );

			// فایل JSON جدا برای هر بوم کاربر.
			$zip->addFromString(
				'json/canvas-' . $r->canvas_key . '-user-' . (int) $r->user_id . '.json',
				wp_json_encode( array( 'canvas_key' => $r->canvas_key, 'user_id' => (int) $r->user_id, 'user' => $name, 'data' => $data ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
			);

			foreach ( $sections as $skey => $sec ) {
				foreach ( $data[ $skey ] as $item ) {
					$csv .= self::csv_cell( $r->canvas_key ) . ',' . (int) $r->user_id . ',' . self::csv_cell( $name ) . ','
						. self::csv_cell( $sec['title'] ) . ',' . self::csv_cell( $item ) . "\n";
				}
			}
		}
		$zip->addFromString( 'summary.csv', $csv );
		$zip->close();

		$content = file_get_contents( $tmp );
		@unlink( $tmp );

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="sazan-canvas-export-' . gmdate( 'Ymd-His' ) . '.zip"' );
		header( 'Content-Length: ' . strlen( $content ) );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	/** فرار از مقادیر CSV (نقل‌قول دوتایی). */
	protected static function csv_cell( $v ) {
		return '"' . str_replace( '"', '""', (string) $v ) . '"';
	}

	/* ==================== AJAX ==================== */

	protected static function guard() {
		check_ajax_referer( 'szp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی.' ), 403 );
		}
	}

	/**
	 * تشخیص موارد تکراری با هوش مصنوعی.
	 * موارد زندهٔ صفحه ارسال می‌شوند (آرایه items با اندیس = موقعیت در DOM) تا
	 * نگاشت بازگشتی مستقل از حالت ذخیره/ویرایش، دقیق بماند.
	 * خروجی: groups = آرایه‌ای از گروه‌ها؛ هر گروه آرایه‌ای از همان اندیس‌هاست.
	 */
	public static function ajax_ai_dupes() {
		self::guard();
		if ( ! SZP_AI::is_configured() ) {
			wp_send_json_error( array( 'msg' => 'ابتدا کلید API هوش مصنوعی را تنظیم کنید.' ) );
		}
		$in   = isset( $_POST['items'] ) ? (array) wp_unslash( $_POST['items'] ) : array();
		$flat = array();
		foreach ( $in as $i => $text ) {
			$text = trim( sanitize_textarea_field( (string) $text ) );
			if ( $text !== '' ) {
				$flat[ (int) $i ] = $text; // حفظ کلید موقعیت برای نگاشت بازگشتی
			}
		}
		if ( count( $flat ) < 2 ) {
			wp_send_json_success( array( 'groups' => array(), 'msg' => 'موردی برای مقایسه وجود ندارد.' ) );
		}

		$groups = SZP_AI::find_duplicate_groups( $flat );
		if ( is_wp_error( $groups ) ) {
			wp_send_json_error( array( 'msg' => $groups->get_error_message() ) );
		}
		$msg = $groups ? ( 'تعداد ' . count( $groups ) . ' گروه تکراری یافت شد.' ) : 'مورد تکراری معناداری یافت نشد.';
		wp_send_json_success( array( 'groups' => $groups, 'msg' => $msg ) );
	}

	/** تست اتصال API با مقادیر واردشده (حتی پیش از ذخیره). */
	public static function ajax_test() {
		self::guard();
		$key   = sanitize_text_field( wp_unslash( $_POST['ai_key'] ?? '' ) );
		$model = sanitize_text_field( wp_unslash( $_POST['ai_model'] ?? '' ) );
		$base  = esc_url_raw( wp_unslash( $_POST['ai_base'] ?? '' ) );
		// اگر کلید خالی بود، از مقدار ذخیره‌شده استفاده کن.
		if ( $key === '' ) { $key = SZP_AI::key(); }
		if ( $model === '' ) { $model = SZP_AI::model(); }
		if ( $base === '' ) { $base = SZP_AI::base(); }

		$res = SZP_AI::test_connection( $key, $model, $base );
		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'msg' => $res->get_error_message() ) );
		}
		wp_send_json_success( array( 'msg' => 'اتصال موفق بود ✓ (مدل: ' . $model . ')' ) );
	}

	/** ذخیره موارد ویرایش‌شده یک بوم توسط مدیر. */
	public static function ajax_save() {
		self::guard();
		$key = isset( $_POST['canvas'] ) ? sanitize_key( wp_unslash( $_POST['canvas'] ) ) : SZP_Canvas::KEY;
		$uid = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $uid ) {
			wp_send_json_error( array( 'msg' => 'کاربر نامعتبر.' ) );
		}
		$in   = isset( $_POST['items'] ) ? (array) wp_unslash( $_POST['items'] ) : array();
		$data = array();
		foreach ( array_keys( SZP_Canvas::sections() ) as $skey ) {
			$list  = ( isset( $in[ $skey ] ) && is_array( $in[ $skey ] ) ) ? $in[ $skey ] : array();
			$clean = array();
			foreach ( $list as $text ) {
				$text = trim( sanitize_textarea_field( (string) $text ) );
				if ( $text !== '' ) { $clean[] = $text; }
			}
			$data[ $skey ] = $clean;
		}
		SZP_Canvas::save_data( $key, $uid, $data );
		wp_send_json_success( array( 'msg' => 'تغییرات ذخیره شد.' ) );
	}

	/* ==================== صفحه ==================== */

	public static function page() {
		$rows = SZP_Canvas::all_rows();

		$sel_key = isset( $_GET['canvas'] ) ? sanitize_key( wp_unslash( $_GET['canvas'] ) ) : '';
		$sel_uid = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

		echo '<div class="wrap szp-cva">';
		echo '<h1>بوم طراحی خدمت</h1>';

		if ( ! empty( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>';
		}

		self::settings_box();
		self::toolbar( $rows );

		if ( $sel_key && $sel_uid ) {
			self::canvas_editor( $sel_key, $sel_uid );
		} else {
			self::canvas_list( $rows, $sel_key, $sel_uid );
		}

		echo '</div>';
	}

	protected static function settings_box() {
		$configured = SZP_AI::is_configured();
		echo '<div class="szp-cva-card">';
		echo '<h2>تنظیمات هوش مصنوعی (گپ جی‌پی‌تی)</h2>';
		echo '<p class="description">برای تشخیص خودکار موارد تکراری، کلید API گپ جی‌پی‌تی را وارد کنید. کلید فقط روی سرور نگهداری و استفاده می‌شود.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'szp_ai_settings' );
		echo '<input type="hidden" name="action" value="szp_ai_settings">';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="szp-ai-key">کلید API</label></th><td>';
		printf( '<input type="password" id="szp-ai-key" name="ai_key" class="regular-text" autocomplete="off" value="%s" placeholder="%s">',
			esc_attr( SZP_AI::key() ), $configured ? '' : 'YOUR_GAPGPT_API_KEY' );
		echo $configured ? ' <span class="szp-cva-ok">✓ تنظیم شده</span>' : '';
		echo '</td></tr>';
		echo '<tr><th><label for="szp-ai-model">مدل</label></th><td>';
		printf( '<input type="text" id="szp-ai-model" name="ai_model" class="regular-text" value="%s" placeholder="gpt-4o">', esc_attr( SZP_AI::model() ) );
		echo '<p class="description">نمونه‌ها: <code>gpt-4o</code> ، <code>gemini-2.5-pro</code> ، <code>claude-3-5-sonnet</code></p>';
		echo '</td></tr>';
		echo '<tr><th><label for="szp-ai-base">آدرس پایه API</label></th><td>';
		printf( '<input type="text" id="szp-ai-base" name="ai_base" class="regular-text" value="%s" placeholder="https://api.gapgpt.app/v1">', esc_attr( SZP_AI::base() ) );
		echo '</td></tr>';
		echo '</tbody></table>';
		echo '<p class="szp-cva-settings-actions">';
		submit_button( 'ذخیره تنظیمات', 'primary', 'submit', false );
		echo ' <button type="button" class="button szp-ai-test">🔌 تست اتصال</button>';
		echo ' <span class="szp-ai-test-msg" aria-live="polite"></span>';
		echo '</p>';
		echo '</form></div>';
	}

	protected static function toolbar( $rows ) {
		echo '<div class="szp-cva-toolbar">';
		// خروجی ZIP
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
		wp_nonce_field( 'szp_canvas_export' );
		echo '<input type="hidden" name="action" value="szp_canvas_export">';
		printf( '<button type="submit" class="button button-secondary"%s>⬇ خروجی ZIP همه بوم‌ها</button>',
			$rows ? '' : ' disabled' );
		echo '</form>';
		echo ' <span class="description">شامل JSON هر کاربر + فایل CSV تجمیعی.</span>';
		echo '</div>';
	}

	protected static function canvas_list( $rows, $sel_key, $sel_uid ) {
		echo '<div class="szp-cva-card"><h2>بوم‌های ثبت‌شده</h2>';
		if ( ! $rows ) {
			echo '<p>هنوز هیچ کاربری بوم ثبت نکرده است.</p></div>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>کاربر</th><th>کلید بوم</th><th>تعداد موارد</th><th>آخرین تغییر</th><th>مدیریت</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$data  = SZP_Canvas::normalize( json_decode( (string) $r->data, true ) ?: array() );
			$count = 0;
			foreach ( $data as $items ) { $count += count( $items ); }
			$u   = get_userdata( $r->user_id );
			$url = add_query_arg( array( 'page' => self::PAGE, 'canvas' => $r->canvas_key, 'user_id' => (int) $r->user_id ), admin_url( 'admin.php' ) );
			printf( '<tr><td>%1$s</td><td><code>%2$s</code></td><td>%3$s</td><td>%4$s</td><td><a class="button button-small" href="%5$s">مدیریت و تشخیص تکراری ›</a></td></tr>',
				esc_html( $u ? $u->display_name : ( 'کاربر #' . (int) $r->user_id ) ),
				esc_html( $r->canvas_key ),
				esc_html( szp_fa_digits( $count ) ),
				esc_html( $r->updated_at ? szp_format_datetime( strtotime( $r->updated_at ) ) : '—' ),
				esc_url( $url )
			);
		}
		echo '</tbody></table></div>';
	}

	protected static function canvas_editor( $key, $uid ) {
		$data     = SZP_Canvas::get_data( $key, $uid );
		$sections = SZP_Canvas::sections();
		$u        = get_userdata( $uid );
		$back     = add_query_arg( array( 'page' => self::PAGE ), admin_url( 'admin.php' ) );

		echo '<div class="szp-cva-card szp-cva-editor" data-canvas="' . esc_attr( $key ) . '" data-user="' . (int) $uid . '">';
		echo '<p><a href="' . esc_url( $back ) . '">‹ بازگشت به فهرست</a></p>';
		printf( '<h2>بوم «%1$s» — %2$s</h2>', esc_html( $key ), esc_html( $u ? $u->display_name : ( 'کاربر #' . $uid ) ) );

		echo '<div class="szp-cva-actions">';
		echo '<button type="button" class="button button-primary szp-cva-ai">🤖 تشخیص موارد تکراری با هوش مصنوعی</button> ';
		echo '<button type="button" class="button szp-cva-del-sel">🗑 حذف موارد انتخاب‌شده</button> ';
		echo '<button type="button" class="button szp-cva-save">💾 ذخیره تغییرات</button> ';
		echo '<span class="szp-cva-msg" aria-live="polite"></span>';
		echo '</div>';

		echo '<div class="szp-cva-grid">';
		foreach ( $sections as $skey => $sec ) {
			echo '<section class="szp-cva-sec" data-section="' . esc_attr( $skey ) . '">';
			printf( '<h3><span class="szp-cva-tag">%1$s</span> %2$s</h3>', esc_html( $sec['tag'] ), esc_html( $sec['title'] ) );
			echo '<ul class="szp-cva-items">';
			foreach ( $data[ $skey ] as $idx => $item ) {
				printf(
					'<li class="szp-cva-item" data-section="%1$s" data-index="%2$d"><label class="szp-cva-pick"><input type="checkbox" class="szp-cva-cb"></label><span class="szp-cva-badge"></span><span class="szp-cva-text" contenteditable="true">%3$s</span></li>',
					esc_attr( $skey ), (int) $idx, esc_html( $item )
				);
			}
			echo '</ul>';
			if ( ! $data[ $skey ] ) {
				echo '<p class="szp-cva-empty">موردی ثبت نشده.</p>';
			}
			echo '</section>';
		}
		echo '</div>';
		echo '<p class="description">پس از تشخیص، موارد هم‌گروه با یک رنگ مشخص می‌شوند و همه‌ی تکراری‌ها به‌جز اولین مورد هر گروه به‌صورت خودکار تیک می‌خورند تا سریع حذف کنید. متن هر مورد قابل ویرایش است.</p>';
		echo '</div>';
	}
}
