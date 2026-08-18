<?php
/** Idempotent one-click Elementor single-product template manager. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Template {

	const OPTION_TEMPLATE = 'sazan_single_product_template_id';
	const OPTION_ENABLED  = 'sazan_single_product_template_enabled';
	const ADMIN_SLUG      = 'sazan-single-product-template';
	const ACTION_CREATE   = 'sazan_single_product_template_create';
	const ACTION_TOGGLE   = 'sazan_single_product_template_toggle';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
		add_action( 'admin_post_' . self::ACTION_CREATE, array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_' . self::ACTION_TOGGLE, array( __CLASS__, 'handle_toggle' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function template_id() {
		return absint( get_option( self::OPTION_TEMPLATE, 0 ) );
	}

	public static function enabled() {
		return '1' === (string) get_option( self::OPTION_ENABLED, '0' );
	}

	public static function is_active_request() {
		if ( ! self::enabled() || ! self::template_id() || ! class_exists( '\\Elementor\\Plugin' ) || ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}
		$product_id = self::current_product_id();
		return (bool) apply_filters( 'spp_v2_template_matches_product', $product_id > 0, $product_id );
	}

	public static function current_product_id() {
		$id = function_exists( 'is_product' ) && is_product() ? absint( get_queried_object_id() ) : 0;
		if ( $id && 'product' === get_post_type( $id ) ) {
			return $id;
		}
		global $product;
		if ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
			return absint( $product->get_id() );
		}
		$id = absint( get_the_ID() );
		return $id && 'product' === get_post_type( $id ) ? $id : 0;
	}

	public static function template_include( $template ) {
		if ( ! self::is_active_request() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $template;
		}
		$file = SPP_PATH . 'templates/single-product-v2-canvas.php';
		return file_exists( $file ) ? $file : $template;
	}

	public static function body_class( $classes ) {
		if ( self::is_active_request() ) {
			$classes[] = 'spp-v2-canvas';
			$classes[] = 'spp-v2-dark';
			$classes[] = 'spp-v3-premium';
		}
		return $classes;
	}

	public static function admin_menu() {
		$parent = post_type_exists( 'product' ) ? 'edit.php?post_type=product' : 'sazan-suite';
		add_submenu_page( $parent, 'قالب تک محصول سازان', 'قالب تک محصول سازان', 'manage_options', self::ADMIN_SLUG, array( __CLASS__, 'admin_page' ) );
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-product-page' ) );
		}
		$id = self::template_id();
		$exists = $id && in_array( get_post_type( $id ), array( 'elementor_library', 'page' ), true );
		$enabled = self::enabled();
		$edit_url = $exists ? admin_url( 'post.php?post=' . $id . '&action=elementor' ) : '';
		$products = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) );
		$preview = $products ? get_permalink( $products[0] ) : '';
		$pro = defined( 'ELEMENTOR_PRO_VERSION' );
		?>
		<div class="wrap" dir="rtl">
			<h1>قالب تک محصول سازان</h1>
			<p>این ابزار قالب یکپارچه و ایزوله V3 سازان را با داده‌های پویای WooCommerce می‌سازد و روی تمام محصولات اعمال می‌کند؛ استایل قالب سایت روی آن اثر نمی‌گذارد.</p>
			<?php if ( isset( $_GET['created'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="notice notice-success is-dismissible"><p>قالب تک محصول ساخته، بازسازی و فعال شد.</p></div><?php endif; ?>
			<div class="card" style="max-width:900px;padding:24px;border-top:4px solid #02c6fe">
				<table class="widefat striped" style="margin-bottom:20px"><tbody>
					<tr><th style="width:220px">وضعیت</th><td><?php echo $exists ? ( $enabled ? '<strong style="color:#087f42">ساخته شده و فعال</strong>' : '<strong style="color:#a15c00">ساخته شده و غیرفعال</strong>' ) : '<strong>ساخته نشده</strong>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td></tr>
					<tr><th>قالب</th><td><?php echo $exists ? esc_html( get_the_title( $id ) . ' (#' . $id . ')' ) : '—'; ?></td></tr>
					<tr><th>Elementor Pro</th><td><?php echo $pro ? 'فعال؛ قابلیت‌های Pro قابل استفاده‌اند.' : 'غیرفعال؛ fallback داخلی سازان فعال است و صفحه بدون Pro کار می‌کند.'; ?></td></tr>
					<tr><th>دامنه اعمال</th><td>همه محصولات (قابل توسعه با فیلتر <code>spp_v2_template_matches_product</code>)</td></tr>
				</tbody></table>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:8px">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_CREATE ); ?>"><?php wp_nonce_field( self::ACTION_CREATE ); ?>
					<button class="button button-primary button-hero" type="submit"><?php echo $exists ? 'بازسازی قالب تک محصول' : 'ساخت یکجای صفحه تک محصول'; ?></button>
				</form>
				<?php if ( $exists ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block"><input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_TOGGLE ); ?>"><?php wp_nonce_field( self::ACTION_TOGGLE ); ?><button class="button button-hero" type="submit"><?php echo $enabled ? 'غیرفعال کردن قالب' : 'فعال کردن قالب'; ?></button></form>
				<p style="margin-top:18px"><a class="button" href="<?php echo esc_url( $edit_url ); ?>">ویرایش با Elementor</a><?php if ( $preview ) : ?> <a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $preview ); ?>">پیش‌نمایش محصول</a><?php endif; ?></p><?php endif; ?>
			</div>
			<div class="card" style="max-width:900px;padding:24px;margin-top:18px"><h2 style="margin-top:0">روش استفاده</h2><ol><li>قالب را با دکمه بالا بسازید.</li><li>هر محصول را ویرایش و متاباکس «تنظیمات صفحه دوره سازان» را تکمیل کنید.</li><li>چیدمان عمومی را با Elementor ویرایش کنید؛ محصول جاری در فرانت به‌صورت پویا جایگزین می‌شود.</li></ol><p class="description">بازسازی فقط ساختار قالب Elementor را به حالت استاندارد برمی‌گرداند و به اطلاعات محصول، سفارش‌ها، نظرات یا متای دوره دست نمی‌زند.</p></div>
		</div>
		<?php
	}

	public static function handle_create() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-product-page' ) );
		}
		check_admin_referer( self::ACTION_CREATE );
		if ( ! did_action( 'elementor/loaded' ) ) {
			wp_die( esc_html__( 'برای ساخت قالب، Elementor باید فعال باشد.', 'sazan-product-page' ) );
		}

		$id = self::template_id();
		if ( ! $id || ! in_array( get_post_type( $id ), array( 'elementor_library', 'page' ), true ) ) {
			$post_type = post_type_exists( 'elementor_library' ) ? 'elementor_library' : 'page';
			$id = wp_insert_post( array( 'post_type' => $post_type, 'post_status' => 'publish', 'post_title' => 'قالب تک محصول سازان — نسل ۲', 'post_content' => '' ) );
		}
		if ( ! $id || is_wp_error( $id ) ) {
			wp_die( esc_html__( 'ساخت قالب ناموفق بود.', 'sazan-product-page' ) );
		}

		wp_update_post( array( 'ID' => $id, 'post_title' => 'قالب تک محصول سازان — نسل ۲', 'post_status' => 'publish' ) );
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'page' );
		update_post_meta( $id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $id, '_elementor_page_settings', array( 'hide_title' => 'yes', 'background_background' => 'classic', 'background_color' => '#000014' ) );
		update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( self::elementor_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		update_post_meta( $id, '_spp_product_page_layout', 'v3-premium' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
		}
		update_option( self::OPTION_TEMPLATE, absint( $id ) );
		update_option( self::OPTION_ENABLED, '1' );
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		wp_safe_redirect( add_query_arg( array( 'post_type' => 'product', 'page' => self::ADMIN_SLUG, 'created' => 1 ), admin_url( 'edit.php' ) ) );
		exit;
	}

	public static function handle_toggle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز است.', 'sazan-product-page' ) );
		}
		check_admin_referer( self::ACTION_TOGGLE );
		update_option( self::OPTION_ENABLED, self::enabled() ? '0' : '1' );
		wp_safe_redirect( add_query_arg( array( 'post_type' => 'product', 'page' => self::ADMIN_SLUG ), admin_url( 'edit.php' ) ) );
		exit;
	}

	private static function element( $type, $name = '', $settings = array(), $elements = array(), $seed = '' ) {
		$out = array( 'id' => substr( md5( $type . '|' . $name . '|' . $seed ), 0, 7 ), 'elType' => $type, 'settings' => $settings, 'elements' => $elements, 'isInner' => false );
		if ( 'widget' === $type ) { $out['widgetType'] = $name; }
		return $out;
	}

	public static function elementor_data() {
		$widget = self::element( 'widget', 'shortcode', array( 'shortcode' => '[spp_v3_product_page]' ), array(), 'spp-v3-product-page' );
		$column = self::element( 'column', '', array( '_column_size' => 100, 'css_classes' => 'spp-v2-template-column spp-v3-template-column' ), array( $widget ), 'column-spp-v3-product-page' );
		return array( self::element( 'section', '', array(
			'layout' => 'full_width',
			'stretch_section' => 'section-stretched',
			'gap' => 'no',
			'css_classes' => 'spp-v2-template-section spp-v3-template-section',
			'_title' => 'سازان — صفحه کامل محصول V3',
			'padding' => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
		), array( $column ), 'section-spp-v3-product-page' ) );
	}
}
