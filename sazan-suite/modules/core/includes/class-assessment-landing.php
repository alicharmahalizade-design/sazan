<?php
/**
 * مدیریت ساخت یک‌جای صفحه‌ی فرود ارزیابی در Elementor.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assessment_Landing_Page {

	const OPTION_PAGE = 'sazan_assessment_landing_page_id';
	const ADMIN_SLUG  = 'sazan-assessment-landing';
	const ACTION       = 'sazan_assessment_landing_create';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 35 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_create' ) );
	}

	public static function page_id() {
		return absint( get_option( self::OPTION_PAGE, 0 ) );
	}

	public function admin_menu() {
		add_submenu_page( 'sazan-suite', 'لندینگ ارزیابی', 'لندینگ ارزیابی', 'manage_options', self::ADMIN_SLUG, array( $this, 'admin_page' ) );
	}

	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-core' ) );
		}
		$id       = self::page_id();
		$exists   = $id && 'page' === get_post_type( $id );
		$edit_url = $exists ? admin_url( 'post.php?post=' . $id . '&action=elementor' ) : '';
		$preview  = $exists ? get_preview_post_link( $id ) : '';
		$status   = $exists ? get_post_status( $id ) : '';
		?>
		<div class="wrap" dir="rtl">
			<h1>لندینگ ارزیابی هوشمند کسب‌وکار</h1>
			<p>این ابزار صفحه‌ی مطابق طرح ارزیابی را به‌صورت یک ویجت کامل Elementor می‌سازد. همه‌ی متن‌ها، کارت‌ها، آزمون مقصد، رنگ‌ها، فاصله‌ها، سئو و حالت موبایل از داخل Elementor قابل ویرایش هستند.</p>
			<?php if ( isset( $_GET['created'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>صفحه ساخته شد. حالا آن را در Elementor ویرایش و سپس منتشر کنید.</p></div>
			<?php endif; ?>
			<div class="card" style="max-width:850px;padding:24px">
				<p><strong>وضعیت صفحه:</strong> <?php echo $exists ? esc_html( 'draft' === $status ? 'پیش‌نویس' : $status ) : 'هنوز ساخته نشده'; ?></p>
				<?php if ( $exists ) : ?><p><strong>صفحه:</strong> <?php echo esc_html( get_the_title( $id ) . ' (#' . $id . ')' ); ?></p><?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<?php wp_nonce_field( self::ACTION ); ?>
					<button class="button button-primary button-hero" type="submit"><?php echo $exists ? 'بازسازی صفحه با چیدمان مرجع' : 'ساخت یک‌جای صفحه در Elementor'; ?></button>
				</form>
				<?php if ( $exists ) : ?>
					<p style="margin-top:18px"><a class="button" href="<?php echo esc_url( $edit_url ); ?>">ویرایش کامل با Elementor</a> <a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener">پیش‌نمایش صفحه</a></p>
				<?php endif; ?>
				<p class="description" style="margin-top:18px">ساخت یا بازسازی، محتوای آزمون‌ها و پاسخ‌های کاربران را تغییر نمی‌دهد؛ فقط ساختار همین صفحه را تنظیم می‌کند.</p>
			</div>
			<div class="card" style="max-width:850px;padding:24px;margin-top:18px">
				<h2 style="margin-top:0">ترتیب پیشنهادی استفاده</h2>
				<ol><li>ابتدا آزمون‌های خود را در «آزمون‌های سازان» بسازید.</li><li>صفحه را با دکمه‌ی بالا ایجاد کنید و ویجت را در Elementor باز کنید.</li><li>در تنظیمات ویجت، آزمون مقصد و لینک کارت‌ها را انتخاب کنید.</li><li>پس از کنترل نمایش موبایل، صفحه را منتشر کنید.</li></ol>
			</div>
		</div>
		<?php
	}

	public function handle_create() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-core' ) );
		}
		check_admin_referer( self::ACTION );
		if ( ! did_action( 'elementor/loaded' ) ) {
			wp_die( esc_html__( 'برای ساخت صفحه، Elementor باید فعال باشد.', 'sazan-core' ) );
		}

		$id = self::page_id();
		if ( ! $id || 'page' !== get_post_type( $id ) ) {
			$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'ارزیابی هوشمند کسب‌وکار', 'post_content' => '' ) );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			wp_die( esc_html__( 'ساخت صفحه ناموفق بود.', 'sazan-core' ) );
		}

		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( self::elementor_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_option( self::OPTION_PAGE, (int) $id );
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::ADMIN_SLUG, 'created' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function element( $type, $name = '', $settings = array(), $elements = array(), $seed = '' ) {
		$id = substr( md5( $type . '|' . $name . '|' . $seed ), 0, 7 );
		$out = array( 'id' => $id, 'elType' => $type, 'settings' => $settings, 'elements' => $elements, 'isInner' => false );
		if ( 'widget' === $type ) {
			$out['widgetType'] = $name;
		}
		return $out;
	}

	public static function elementor_data() {
		$quizzes = get_posts( array( 'post_type' => Quiz_CPT::POST_TYPE, 'numberposts' => 1, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'date', 'order' => 'DESC', 'fields' => 'ids' ) );
		$settings = $quizzes ? array( 'quiz_id' => absint( $quizzes[0] ) ) : array();
		$widget = self::element( 'widget', 'sazan-assessment-landing', $settings, array(), 'assessment-landing' );
		$column = self::element( 'column', '', array( '_column_size' => 100, 'css_classes' => 'sazan-assessment-page-column' ), array( $widget ), 'assessment-column' );
		return array( self::element( 'section', '', array( 'layout' => 'boxed', 'content_width' => array( 'unit' => 'px', 'size' => 1200, 'sizes' => array() ), 'gap' => 'no', 'css_classes' => 'sazan-assessment-page-section' ), array( $column ), 'assessment-section' ) );
	}
}
