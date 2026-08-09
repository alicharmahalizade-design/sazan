<?php
/**
 * Single-blog template manager and one-click Elementor template builder.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Single_Blog {

	const OPTION_TEMPLATE = 'sazan_single_blog_template_id';
	const OPTION_ENABLED  = 'sazan_single_blog_enabled';
	const OPTION_CLEAN_SHORTCODES = 'sazan_single_blog_clean_shortcodes';
	const OPTION_SHORTCODE_TAGS   = 'sazan_single_blog_legacy_shortcode_tags';
	const ADMIN_SLUG      = 'sazan-single-blog';
	const ACTION_CREATE   = 'sazan_single_blog_create';
	const ACTION_TOGGLE   = 'sazan_single_blog_toggle';
	const ACTION_CONTENT  = 'sazan_single_blog_content_settings';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 30 );
		add_action( 'admin_post_' . self::ACTION_CREATE, array( $this, 'handle_create' ) );
		add_action( 'admin_post_' . self::ACTION_TOGGLE, array( $this, 'handle_toggle' ) );
		add_action( 'admin_post_' . self::ACTION_CONTENT, array( $this, 'handle_content_settings' ) );
		add_filter( 'template_include', array( $this, 'single_template' ), 99 );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
	}

	public static function template_id() {
		return absint( get_option( self::OPTION_TEMPLATE, 0 ) );
	}

	public static function enabled() {
		return '1' === (string) get_option( self::OPTION_ENABLED, '0' );
	}

	public static function shortcode_cleanup_enabled() {
		return '1' === (string) get_option( self::OPTION_CLEAN_SHORTCODES, '1' );
	}

	public static function default_shortcode_tags() {
		return array( 'vc_row', 'vc_column', 'vc_column_text', 'vc_section', 'vc_row_inner', 'vc_column_inner', 'vc_empty_space' );
	}

	public static function legacy_shortcode_tags() {
		$stored = (string) get_option( self::OPTION_SHORTCODE_TAGS, implode( ',', self::default_shortcode_tags() ) );
		$tags   = preg_split( '/[\s,،]+/u', $stored, -1, PREG_SPLIT_NO_EMPTY );
		$tags   = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $tags ) ) ) );
		return apply_filters( 'sazan_single_blog_legacy_shortcode_tags', $tags );
	}

	/**
	 * Remove selected legacy shortcode tokens while preserving their inner HTML.
	 * Code/pre blocks are intentionally skipped so tutorial examples remain visible.
	 */
	public static function clean_legacy_shortcodes( $html ) {
		if ( ! self::shortcode_cleanup_enabled() || '' === trim( (string) $html ) ) {
			return $html;
		}

		$tags = self::legacy_shortcode_tags();
		if ( ! $tags ) {
			return $html;
		}

		$parts = preg_split( '~(<(?:pre|code)\b[^>]*>.*?</(?:pre|code)>)~isu', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $parts as $index => $part ) {
			if ( 1 === $index % 2 ) {
				continue;
			}
			foreach ( $tags as $tag ) {
				$quoted = preg_quote( $tag, '~' );
				$part   = preg_replace( '~\[\s*/?\s*' . $quoted . '(?:\s+[^\]]*)?/?\s*\]~iu', '', $part );
			}
			$part = preg_replace( '~<p>\s*(?:<br\s*/?>)?\s*</p>~iu', '', $part );
			$parts[ $index ] = $part;
		}

		return implode( '', $parts );
	}

	public static function is_active_request() {
		return self::enabled() && self::template_id() && is_singular( 'post' );
	}

	public function body_classes( $classes ) {
		if ( self::is_active_request() ) {
			$classes[] = 'sazan-single-blog-canvas';
			$classes[] = 'sazan-single-blog-dark';
		}
		return $classes;
	}

	public function single_template( $template ) {
		if ( ! self::is_active_request() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return $template;
		}
		$file = SAZAN_CORE_PATH . 'templates/single-blog-canvas.php';
		return file_exists( $file ) ? $file : $template;
	}

	public function admin_menu() {
		add_submenu_page(
			'sazan-suite',
			'قالب تک بلاگ',
			'تک بلاگ',
			'manage_options',
			self::ADMIN_SLUG,
			array( $this, 'admin_page' )
		);
	}

	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-core' ) );
		}
		$id       = self::template_id();
		$exists   = $id && 'page' === get_post_type( $id );
		$enabled  = self::enabled();
		$edit_url = $exists ? admin_url( 'post.php?post=' . $id . '&action=elementor' ) : '';
		$latest   = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
		$preview  = $latest ? get_permalink( $latest[0] ) : '';
		?>
		<div class="wrap szs-wrap" dir="rtl">
			<h1>قالب خودکار تک بلاگ سازان</h1>
			<p>این ابزار یک قالب Elementor بدون هدر، با ۱۳ ویجت مستقل و فوتر کامل می‌سازد و آن را روی همهٔ نوشته‌های تکی اعمال می‌کند.</p>
			<?php if ( isset( $_GET['created'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>قالب تک بلاگ ساخته و فعال شد.</p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['content-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>تنظیمات پاک‌سازی محتوای مقاله ذخیره شد.</p></div>
			<?php endif; ?>
			<div class="card" style="max-width:820px;padding:22px">
				<p><strong>وضعیت:</strong> <?php echo $enabled ? '<span style="color:#07883f">فعال</span>' : '<span style="color:#a15c00">غیرفعال</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<p><strong>قالب:</strong> <?php echo $exists ? esc_html( get_the_title( $id ) . ' (#' . $id . ')' ) : 'هنوز ساخته نشده'; ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:8px">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_CREATE ); ?>">
					<?php wp_nonce_field( self::ACTION_CREATE ); ?>
					<button class="button button-primary button-hero" type="submit"><?php echo $exists ? 'بازسازی قالب با چیدمان استاندارد' : 'ساخت خودکار قالب تک بلاگ'; ?></button>
				</form>
				<?php if ( $exists ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_TOGGLE ); ?>">
						<?php wp_nonce_field( self::ACTION_TOGGLE ); ?>
						<button class="button button-hero" type="submit"><?php echo $enabled ? 'غیرفعال‌کردن قالب' : 'فعال‌کردن قالب'; ?></button>
					</form>
					<p style="margin-top:18px">
						<a class="button" href="<?php echo esc_url( $edit_url ); ?>">ویرایش با Elementor</a>
						<?php if ( $preview ) : ?><a class="button" href="<?php echo esc_url( $preview ); ?>" target="_blank" rel="noopener">پیش‌نمایش روی آخرین نوشته</a><?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
			<div class="card" style="max-width:820px;padding:22px;margin-top:18px">
				<h2 style="margin-top:0">پاک‌سازی شورت‌کدهای قدیمی مقالات</h2>
				<p>شورت‌کدهای انتخاب‌شده از خروجی تک بلاگ مخفی می‌شوند، اما متن و تصاویر داخل شورت‌کدهای جفتی باقی می‌مانند. نمونه: <code>[vc_row]</code> و <code>[vc_column_text]</code>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_CONTENT ); ?>">
					<?php wp_nonce_field( self::ACTION_CONTENT ); ?>
					<label style="display:block;margin:14px 0">
						<input type="checkbox" name="clean_shortcodes" value="1" <?php checked( self::shortcode_cleanup_enabled() ); ?>>
						پاک‌سازی شورت‌کدهای قدیمی در تمام مقالات تک بلاگ
					</label>
					<label for="sazan-legacy-shortcodes"><strong>نام شورت‌کدها</strong></label>
					<textarea id="sazan-legacy-shortcodes" name="shortcode_tags" class="large-text code" rows="4" dir="ltr" style="margin-top:8px"><?php echo esc_textarea( implode( ', ', self::legacy_shortcode_tags() ) ); ?></textarea>
					<p class="description">نام‌ها را با ویرگول یا فاصله جدا کنید؛ بدون براکت بنویسید. شورت‌کدهای فعال دیگر دست‌نخورده باقی می‌مانند.</p>
					<?php submit_button( 'ذخیره تنظیمات پاک‌سازی', 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<p>نکته: بازسازی، چیدمان و تنظیمات ویجت‌های قالب را به نسخهٔ استاندارد برمی‌گرداند. محتوای نوشته‌ها دست‌نخورده می‌ماند.</p>
		</div>
		<?php
	}

	public function handle_create() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-core' ) );
		}
		check_admin_referer( self::ACTION_CREATE );
		if ( ! did_action( 'elementor/loaded' ) ) {
			wp_die( esc_html__( 'برای ساخت قالب، Elementor باید فعال باشد.', 'sazan-core' ) );
		}

		$id = self::template_id();
		if ( ! $id || 'page' !== get_post_type( $id ) ) {
			$id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'draft',
				'post_title'   => 'قالب خودکار تک بلاگ سازان',
				'post_content' => '',
			) );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			wp_die( esc_html__( 'ساخت قالب ناموفق بود.', 'sazan-core' ) );
		}

		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( self::elementor_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_option( self::OPTION_TEMPLATE, (int) $id );
		update_option( self::OPTION_ENABLED, '1' );
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		wp_safe_redirect( add_query_arg( array( 'page' => self::ADMIN_SLUG, 'created' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_toggle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-core' ) );
		}
		check_admin_referer( self::ACTION_TOGGLE );
		update_option( self::OPTION_ENABLED, self::enabled() ? '0' : '1' );
		wp_safe_redirect( add_query_arg( 'page', self::ADMIN_SLUG, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_content_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-core' ) );
		}
		check_admin_referer( self::ACTION_CONTENT );

		$enabled = isset( $_POST['clean_shortcodes'] ) ? '1' : '0';
		$raw     = isset( $_POST['shortcode_tags'] ) ? wp_unslash( $_POST['shortcode_tags'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$tags    = preg_split( '/[\s,،]+/u', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );
		$tags    = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) $tags ) ) ) );

		update_option( self::OPTION_CLEAN_SHORTCODES, $enabled );
		update_option( self::OPTION_SHORTCODE_TAGS, implode( ',', $tags ) );
		wp_safe_redirect( add_query_arg( array( 'page' => self::ADMIN_SLUG, 'content-saved' => 1 ), admin_url( 'admin.php' ) ) );
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

	private static function widget( $name, $settings = array() ) {
		return self::element( 'widget', $name, $settings, array(), $name );
	}

	private static function column( $size, $class, $widgets ) {
		return self::element( 'column', '', array( '_column_size' => $size, 'css_classes' => $class ), $widgets, $class );
	}

	private static function section( $class, $columns ) {
		return self::element( 'section', '', array(
			'layout'        => 'boxed',
			'content_width' => array( 'unit' => 'px', 'size' => 1200, 'sizes' => array() ),
			'gap'           => 'no',
			'css_classes'   => $class,
		), $columns, $class );
	}

	public static function elementor_data() {
		$main = array(
			self::widget( 'sazan-sb-article-hero' ),
			self::widget( 'sazan-sb-article-content' ),
			self::widget( 'sazan-sb-download' ),
			self::widget( 'sazan-sb-outline' ),
			self::widget( 'sazan-sb-tags-share' ),
			self::widget( 'sazan-sb-comments' ),
		);
		$side = array(
			self::widget( 'sazan-sb-related' ),
			self::widget( 'sazan-sb-author' ),
			self::widget( 'sazan-sb-toc' ),
			self::widget( 'sazan-sb-side-cta' ),
		);
		$wide = array(
			self::widget( 'sazan-sb-wide-cta' ),
			self::widget( 'sazan-sb-features' ),
			self::widget( 'sazan-sb-footer' ),
		);
		return array(
			self::section( 'sazan-sb-layout-section', array(
				self::column( 70, 'sazan-sb-main-column', $main ),
				self::column( 30, 'sazan-sb-side-column', $side ),
			) ),
			self::section( 'sazan-sb-wide-section', array( self::column( 100, 'sazan-sb-wide-column', $wide ) ) ),
		);
	}
}
