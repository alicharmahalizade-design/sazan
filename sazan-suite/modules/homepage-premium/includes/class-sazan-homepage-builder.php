<?php
/** Preview, activate, restore and remove the generated Elementor homepage. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sazan_Homepage_Premium_Builder {
	const OPTION_PAGE          = 'sazan_premium_homepage_page_id';
	const OPTION_ACTIVE        = 'sazan_premium_homepage_active';
	const OPTION_PREVIOUS_SHOW = 'sazan_premium_homepage_previous_show_on_front';
	const OPTION_PREVIOUS_PAGE = 'sazan_premium_homepage_previous_page_on_front';
	const ADMIN_SLUG           = 'sazan-premium-homepage';
	const ACTION               = 'sazan_build_premium_homepage';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 35 );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_action' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function admin_menu() {
		add_submenu_page( 'sazan-suite', 'صفحه اصلی پریمیوم', 'صفحه اصلی پریمیوم', 'manage_options', self::ADMIN_SLUG, array( __CLASS__, 'admin_page' ) );
	}

	private static function page_id() {
		return absint( get_option( self::OPTION_PAGE, 0 ) );
	}

	private static function page_exists( $id = 0 ) {
		$id = $id ? absint( $id ) : self::page_id();
		return $id && 'page' === get_post_type( $id ) && 'trash' !== get_post_status( $id );
	}

	private static function is_active() {
		$id = self::page_id();
		return self::page_exists( $id )
			&& 'page' === (string) get_option( 'show_on_front', 'posts' )
			&& $id === absint( get_option( 'page_on_front', 0 ) );
	}

	public static function body_class( $classes ) {
		if ( is_page( self::page_id() ) ) {
			$classes[] = 'sazan-premium-homepage-page';
		}
		return $classes;
	}

	private static function action_form( $mode, $label, $class = 'button', $confirm = '' ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:0 0 8px 8px">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
			<input type="hidden" name="mode" value="<?php echo esc_attr( $mode ); ?>">
			<?php wp_nonce_field( self::ACTION ); ?>
			<button type="submit" class="<?php echo esc_attr( $class ); ?>"<?php echo $confirm ? ' onclick="return confirm(' . esc_attr( wp_json_encode( $confirm ) ) . ')"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	private static function notice_message( $status ) {
		$messages = array(
			'preview'    => 'پیش‌نمایش ساخته شد؛ هنوز هیچ تغییری در صفحه اصلی سایت اعمال نشده است.',
			'activated'  => 'صفحه پریمیوم فعال شد و نسخه قبلی برای بازگردانی ذخیره شد.',
			'deactivated'=> 'صفحه پریمیوم غیرفعال و صفحه اصلی قبلی با موفقیت بازگردانده شد.',
			'deleted'    => 'صفحه ساخته‌شده به زباله‌دان منتقل شد و صفحه اصلی قبلی بازگردانده شد.',
		);
		return isset( $messages[ $status ] ) ? $messages[ $status ] : '';
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$id          = self::page_id();
		$exists      = self::page_exists( $id );
		$active      = self::is_active();
		$edit_url    = $exists ? admin_url( 'post.php?post=' . $id . '&action=elementor' ) : '';
		$preview_url = $exists ? ( $active ? get_permalink( $id ) : get_preview_post_link( $id ) ) : '';
		$status      = isset( $_GET['homepage-status'] ) ? sanitize_key( wp_unslash( $_GET['homepage-status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$message     = self::notice_message( $status );
		$previous    = (string) get_option( self::OPTION_PREVIOUS_SHOW, 'posts' );
		$previous_id = absint( get_option( self::OPTION_PREVIOUS_PAGE, 0 ) );
		$previous_label = 'نمایش آخرین نوشته‌ها';
		if ( 'page' === $previous && $previous_id ) {
			$previous_label = get_the_title( $previous_id ) ? get_the_title( $previous_id ) . ' (#' . $previous_id . ')' : 'صفحه قبلی #' . $previous_id;
		}
		?>
		<div class="wrap" dir="rtl">
			<style>
				.sazan-home-builder{max-width:920px}.sazan-home-builder__status{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:20px 0;padding:20px 22px;border:1px solid #dcdcde;border-radius:14px;background:#fff}.sazan-home-builder__status strong{display:block;font-size:16px;margin-bottom:5px}.sazan-home-builder__dot{width:13px;height:13px;display:inline-block;margin-left:7px;border-radius:50%;background:#a7aaad;vertical-align:-1px}.sazan-home-builder__dot.is-active{background:#00a32a;box-shadow:0 0 0 5px rgba(0,163,42,.12)}.sazan-home-builder__card{padding:24px!important;border-radius:14px}.sazan-home-builder__actions{display:flex;flex-wrap:wrap;align-items:center;margin-top:18px}.sazan-home-builder__preview{font-size:16px;min-height:46px!important;padding:8px 20px!important}.sazan-home-builder__activate{min-height:46px!important;padding:8px 20px!important;background:#00a32a!important;border-color:#008a20!important}.sazan-home-builder__danger{color:#b32d2e!important;border-color:#d63638!important}.sazan-home-builder__hint{margin-top:18px;padding:13px 15px;border-right:4px solid #2271b1;background:#f0f6fc}.sazan-home-builder__steps{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.sazan-home-builder__steps span{padding:14px;border:1px solid #dcdcde;border-radius:12px;background:#fff}.sazan-home-builder__steps b{display:block;color:#2271b1;margin-bottom:4px}@media(max-width:782px){.sazan-home-builder__status{align-items:flex-start;flex-direction:column}.sazan-home-builder__steps{grid-template-columns:1fr}.sazan-home-builder__actions form,.sazan-home-builder__actions a{width:100%!important;margin:0 0 10px!important;text-align:center}.sazan-home-builder__actions button{width:100%}}
			</style>
			<div class="sazan-home-builder">
				<h1>صفحه اصلی پریمیوم سازان</h1>
				<p>ابتدا پیش‌نمایش را با ۹ ویجت مستقل بسازید؛ سکشن‌ها در Elementor قابل جابه‌جایی هستند و فقط پس از تأیید شما جایگزین صفحه اصلی فعلی می‌شوند.</p>

				<?php if ( $message ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
				<?php endif; ?>

				<div class="sazan-home-builder__status">
					<div><strong><span class="sazan-home-builder__dot <?php echo $active ? 'is-active' : ''; ?>"></span><?php echo $active ? 'صفحه پریمیوم فعال است' : ( $exists ? 'پیش‌نمایش آماده است؛ هنوز فعال نشده' : 'هنوز صفحه‌ای ساخته نشده است' ); ?></strong><span><?php echo $exists ? esc_html( get_the_title( $id ) . ' — شناسه ' . $id ) : 'برای شروع، دکمه ساخت پیش‌نمایش را بزنید.'; ?></span></div>
					<?php if ( $active ) : ?><span>نسخه بازگشت: <strong><?php echo esc_html( $previous_label ); ?></strong></span><?php endif; ?>
				</div>

				<div class="sazan-home-builder__steps" aria-label="مراحل انتشار">
					<span><b>۱. ساخت</b>۹ ویجت مستقل در حالت پیش‌نویس ساخته می‌شوند.</span>
					<span><b>۲. بررسی</b>پیش‌نمایش واقعی دسکتاپ و موبایل را می‌بینید.</span>
					<span><b>۳. فعال‌سازی</b>فقط با تأیید شما صفحه نخست تغییر می‌کند.</span>
				</div>

				<div class="card sazan-home-builder__card">
					<h2 style="margin-top:0">ساخت فوری و پیش‌نمایش</h2>
					<p><?php echo $active ? 'نسخه فعال را می‌توانید روی سایت ببینید یا با Elementor ویرایش کنید. برای بازسازی ایزوله، ابتدا آن را غیرفعال کنید تا صفحه قبلی بازگردد.' : 'ساخت یا بازسازی پیش‌نمایش، صفحه اصلی فعلی سایت را تغییر نمی‌دهد.'; ?></p>
					<div class="sazan-home-builder__actions">
						<?php if ( ! $active ) : ?>
							<?php self::action_form( 'preview', $exists ? 'بازسازی پیش‌نمایش' : 'ساخت پیش‌نمایش', 'button button-primary button-hero sazan-home-builder__preview' ); ?>
						<?php endif; ?>
						<?php if ( $exists ) : ?>
							<a class="button button-hero sazan-home-builder__preview" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener">مشاهده پیش‌نمایش</a>
							<a class="button button-hero" href="<?php echo esc_url( $edit_url ); ?>">ویرایش با Elementor</a>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( $exists ) : ?>
					<div class="card sazan-home-builder__card" style="margin-top:18px">
						<h2 style="margin-top:0">وضعیت نمایش روی سایت</h2>
						<div class="sazan-home-builder__actions">
							<?php if ( ! $active ) : ?>
								<?php self::action_form( 'activate', 'فعال‌سازی فوری روی سایت', 'button button-primary button-hero sazan-home-builder__activate', 'صفحه پریمیوم به‌عنوان صفحه اصلی سایت فعال شود؟' ); ?>
							<?php else : ?>
								<?php self::action_form( 'deactivate', 'غیرفعال‌سازی و بازگرداندن صفحه قبلی', 'button button-hero', 'صفحه پریمیوم غیرفعال و صفحه اصلی قبلی بازگردانده شود؟' ); ?>
							<?php endif; ?>
							<?php self::action_form( 'delete', 'حذف صفحه ساخته‌شده', 'button button-hero sazan-home-builder__danger', 'صفحه ساخته‌شده به زباله‌دان منتقل شود؟ در صورت فعال‌بودن، صفحه قبلی بازگردانده خواهد شد.' ); ?>
						</div>
						<p class="sazan-home-builder__hint">حذف به‌صورت امن انجام می‌شود و صفحه به زباله‌دان وردپرس می‌رود؛ بنابراین در صورت نیاز قابل بازیابی است.</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private static function elementor_data() {
		$widgets = array(
			'sazan-home-header',
			'sazan-home-hero',
			'sazan-home-services',
			'sazan-home-courses',
			'sazan-home-consultation',
			'sazan-home-about',
			'sazan-home-calendar',
			'sazan-home-articles',
			'sazan-home-footer',
		);
		$data = array();
		foreach ( $widgets as $index => $widget ) {
			$seed = substr( md5( $widget ), 0, 7 );
			$data[] = array(
				'id'       => substr( md5( 'section-' . $widget ), 0, 7 ),
				'elType'   => 'section',
				'isInner'  => false,
				'settings' => array( 'layout' => 'full_width', 'gap' => 'no', 'stretch_section' => 'section-stretched', 'css_classes' => 'sazan-homepage-elementor-section sazan-homepage-order-' . ( $index + 1 ) ),
				'elements' => array(
					array(
						'id'       => substr( md5( 'column-' . $widget ), 0, 7 ),
						'elType'   => 'column',
						'isInner'  => false,
						'settings' => array( '_column_size' => 100, 'css_classes' => 'sazan-homepage-elementor-column' ),
						'elements' => array(
							array( 'id' => $seed, 'elType' => 'widget', 'widgetType' => $widget, 'isInner' => false, 'settings' => array() ),
						),
					),
				),
			);
		}
		return $data;
	}

	private static function create_or_update_page( $status ) {
		$id = self::page_id();
		if ( ! self::page_exists( $id ) ) {
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => $status, 'post_title' => 'صفحه اصلی سازان', 'post_name' => 'sazan-premium-home' ) );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			wp_die( 'ساخت صفحه ناموفق بود.' );
		}

		wp_update_post( array( 'ID' => $id, 'post_status' => $status ) );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( self::elementor_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_option( self::OPTION_PAGE, $id );
		self::clear_elementor_cache();
		return $id;
	}

	private static function remember_previous_homepage( $id ) {
		if ( self::is_active() ) {
			return;
		}
		$current_show = (string) get_option( 'show_on_front', 'posts' );
		$current_page = absint( get_option( 'page_on_front', 0 ) );
		if ( 'page' === $current_show && $current_page === absint( $id ) ) {
			return;
		}
		update_option( self::OPTION_PREVIOUS_SHOW, $current_show );
		update_option( self::OPTION_PREVIOUS_PAGE, $current_page );
	}

	private static function restore_previous_homepage() {
		$show = (string) get_option( self::OPTION_PREVIOUS_SHOW, 'posts' );
		$page = absint( get_option( self::OPTION_PREVIOUS_PAGE, 0 ) );
		if ( 'page' === $show && ( ! $page || 'page' !== get_post_type( $page ) || 'publish' !== get_post_status( $page ) ) ) {
			$show = 'posts';
			$page = 0;
		}
		update_option( 'show_on_front', 'page' === $show ? 'page' : 'posts' );
		update_option( 'page_on_front', $page );
		update_option( self::OPTION_ACTIVE, '0' );
	}

	private static function clear_elementor_cache() {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	private static function redirect( $status ) {
		wp_safe_redirect( add_query_arg( array( 'page' => self::ADMIN_SLUG, 'homepage-status' => $status ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز است.' );
		}
		check_admin_referer( self::ACTION );
		if ( ! did_action( 'elementor/loaded' ) && ! defined( 'ELEMENTOR_VERSION' ) ) {
			wp_die( 'برای ساخت صفحه، Elementor باید فعال باشد.' );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'preview';
		$id   = self::page_id();

		switch ( $mode ) {
			case 'activate':
				$id = self::create_or_update_page( 'publish' );
				self::remember_previous_homepage( $id );
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $id );
				update_option( self::OPTION_ACTIVE, '1' );
				self::redirect( 'activated' );
				break;

			case 'deactivate':
				self::restore_previous_homepage();
				if ( self::page_exists( $id ) ) {
					wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
				}
				self::clear_elementor_cache();
				self::redirect( 'deactivated' );
				break;

			case 'delete':
				if ( self::is_active() ) {
					self::restore_previous_homepage();
				}
				if ( self::page_exists( $id ) ) {
					wp_trash_post( $id );
				}
				delete_option( self::OPTION_PAGE );
				update_option( self::OPTION_ACTIVE, '0' );
				self::clear_elementor_cache();
				self::redirect( 'deleted' );
				break;

			case 'preview':
			default:
				if ( ! self::is_active() ) {
					self::create_or_update_page( 'draft' );
				}
				self::redirect( 'preview' );
		}
	}
}
