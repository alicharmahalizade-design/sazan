<?php
/**
 * Admin screen with the "build page" button.
 *
 * Creates a new page on the landing canvas template whose Elementor content is the
 * full landing made of the landing widgets, in the original order and with the
 * original content. The structure mirrors the static page:
 *
 *   header widget
 *   <main>  (container .hk-box.hk-main)
 *     <div class="opening-shell">  (container .hk-box.opening-shell)
 *       hero, logos, results
 *     diagnosis … final-cta
 *   footer widget, enrollment widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HKL_Page_Builder {

	const MENU_SLUG = 'hokmrani-landing';
	const ACTION    = 'hkl_build_page';
	const META      = '_hkl_landing_page';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle_build' ] );
		add_action( 'admin_post_hkl_save_settings', [ __CLASS__, 'handle_settings' ] );
	}

	public static function menu() {
		add_menu_page(
			'لندینگ حکمرانی بر بازار',
			'لندینگ حکمرانی',
			'edit_pages',
			self::MENU_SLUG,
			[ __CLASS__, 'render_page' ],
			'dashicons-layout',
			58
		);
		add_submenu_page( self::MENU_SLUG, 'ساخت صفحه لندینگ', 'ساخت صفحه', 'edit_pages', self::MENU_SLUG, [ __CLASS__, 'render_page' ] );
	}

	/* ------------------------------------------------------------------ */
	/* Elementor data                                                       */
	/* ------------------------------------------------------------------ */

	/** Are Flexbox containers available on this site? Otherwise sections/columns are used. */
	public static function use_containers() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		$experiments = \Elementor\Plugin::$instance->experiments ?? null;
		if ( ! $experiments || ! method_exists( $experiments, 'is_feature_active' ) ) {
			return false;
		}
		if ( method_exists( $experiments, 'get_features' ) && ! $experiments->get_features( 'container' ) ) {
			// The experiment no longer exists: containers are part of the core.
			return defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.6.0', '>=' );
		}
		return (bool) $experiments->is_feature_active( 'container' );
	}

	private static function widget( $slug ) {
		$class = HKL_Plugin::WIDGETS[ $slug ];
		return [
			'id'         => HKL_Widget_Base::random_id(),
			'elType'     => 'widget',
			'widgetType' => $class::widget_name(),
			'settings'   => $class::default_settings(),
			'elements'   => [],
		];
	}

	/**
	 * A structural box (Flexbox container or section > column).
	 *
	 * @param array  $children Child elements.
	 * @param string $classes  CSS classes.
	 * @param array  $extra    Extra settings (html_tag, _element_id).
	 * @param bool   $inner    Nested element.
	 * @param bool   $containers Use containers.
	 */
	private static function box( array $children, $classes, array $extra, $inner, $containers ) {
		if ( $containers ) {
			return [
				'id'       => HKL_Widget_Base::random_id(),
				'elType'   => 'container',
				'isInner'  => $inner,
				'settings' => array_merge(
					[
						'content_width' => 'full',
						'css_classes'   => $classes,
					],
					$extra
				),
				'elements' => $children,
			];
		}
		return [
			'id'       => HKL_Widget_Base::random_id(),
			'elType'   => 'section',
			'isInner'  => $inner,
			'settings' => array_merge(
				[
					'layout'      => 'full_width',
					'gap'         => 'no',
					'css_classes' => $classes,
				],
				$extra
			),
			'elements' => [
				[
					'id'       => HKL_Widget_Base::random_id(),
					'elType'   => 'column',
					'isInner'  => $inner,
					'settings' => [ '_column_size' => 100 ],
					'elements' => $children,
				],
			],
		];
	}

	/** The complete landing page as Elementor data. */
	public static function elementor_data() {
		HKL_Plugin::load_widget_classes();
		$c = self::use_containers();

		$opening = self::box(
			[ self::widget( 'hero' ), self::widget( 'logos' ), self::widget( 'results' ) ],
			'hk-box opening-shell',
			[],
			true,
			$c
		);

		$main_children = [ $opening ];
		foreach ( [ 'diagnosis', 'roadmap', 'program', 'chapters', 'stories', 'teachers', 'guarantee', 'investment', 'fit', 'final-cta' ] as $slug ) {
			$main_children[] = self::widget( $slug );
		}

		return [
			self::box( [ self::widget( 'header' ) ], 'hk-wrap', [], false, $c ),
			self::box( $main_children, 'hk-box hk-main', [ 'html_tag' => 'main', '_element_id' => 'main-content' ], false, $c ),
			self::box( [ self::widget( 'footer' ), self::widget( 'enrollment' ) ], 'hk-wrap', [], false, $c ),
		];
	}

	/* ------------------------------------------------------------------ */
	/* Build                                                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Create the landing page.
	 *
	 * @param string $title Page title.
	 * @return int|WP_Error Page ID.
	 */
	public static function build( $title ) {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return new WP_Error( 'hkl_no_elementor', 'افزونه المنتور فعال نیست.' );
		}

		$page_id = wp_insert_post(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => '',
			],
			true
		);
		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}

		update_post_meta( $page_id, '_wp_page_template', HKL_TEMPLATE );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, self::META, HKL_VERSION );

		$data     = self::elementor_data();
		$document = null;
		if ( isset( \Elementor\Plugin::$instance->documents ) ) {
			$document = \Elementor\Plugin::$instance->documents->get( $page_id, false );
		}
		$saved = false;
		if ( $document && method_exists( $document, 'save' ) ) {
			$saved = $document->save( [ 'elements' => $data ] );
		}
		if ( ! $saved ) {
			update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
			}
		}
		// Document::save() may reset the template from the (empty) page settings.
		update_post_meta( $page_id, '_wp_page_template', HKL_TEMPLATE );

		if ( isset( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return $page_id;
	}

	public static function handle_build() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'دسترسی کافی ندارید.' );
		}
		check_admin_referer( self::ACTION );

		$title = isset( $_POST['hkl_title'] ) ? sanitize_text_field( wp_unslash( $_POST['hkl_title'] ) ) : '';
		if ( '' === $title ) {
			$title = 'حکمرانی بر بازار';
		}
		$result = self::build( $title );
		$args   = [ 'page' => self::MENU_SLUG ];
		if ( is_wp_error( $result ) ) {
			$args['hkl_error'] = rawurlencode( $result->get_error_message() );
		} else {
			$args['hkl_built'] = $result;
			if ( ! empty( $_POST['hkl_front'] ) && current_user_can( 'manage_options' ) ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $result );
			}
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی کافی ندارید.' );
		}
		check_admin_referer( 'hkl_save_settings' );
		$email = isset( $_POST['hkl_lead_email'] ) ? sanitize_email( wp_unslash( $_POST['hkl_lead_email'] ) ) : '';
		update_option( HKL_Leads::EMAIL_OPTION, $email );
		wp_safe_redirect( add_query_arg( [ 'page' => self::MENU_SLUG, 'hkl_saved' => 1 ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Screen                                                               */
	/* ------------------------------------------------------------------ */

	private static function edit_link( $page_id ) {
		return add_query_arg( [ 'post' => $page_id, 'action' => 'elementor' ], admin_url( 'post.php' ) );
	}

	public static function render_page() {
		$elementor = class_exists( '\Elementor\Plugin' );
		$built     = isset( $_GET['hkl_built'] ) ? absint( $_GET['hkl_built'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error     = isset( $_GET['hkl_error'] ) ? sanitize_text_field( wp_unslash( $_GET['hkl_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pages     = get_posts(
			[
				'post_type'      => 'page',
				'post_status'    => [ 'publish', 'draft', 'private' ],
				'meta_key'       => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
				'posts_per_page' => 20,
			]
		);
		?>
		<div class="wrap" dir="rtl">
			<h1>لندینگ «حکمرانی بر بازار» برای المنتور</h1>

			<?php if ( $built ) : ?>
				<div class="notice notice-success"><p>
					صفحه با موفقیت ساخته شد.
					<a class="button button-primary" href="<?php echo esc_url( self::edit_link( $built ) ); ?>">ویرایش با المنتور</a>
					<a class="button" href="<?php echo esc_url( get_permalink( $built ) ); ?>" target="_blank" rel="noopener">مشاهده صفحه</a>
				</p></div>
			<?php endif; ?>
			<?php if ( $error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['hkl_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>
			<?php endif; ?>

			<?php if ( ! $elementor ) : ?>
				<div class="notice notice-warning"><p>برای ساخت صفحه ابتدا افزونه المنتور را نصب و فعال کنید.</p></div>
			<?php endif; ?>

			<div class="card" style="max-width:760px">
				<h2>ساخت صفحه با ویجت‌های المنتور</h2>
				<p>با زدن دکمه زیر یک برگه جدید ساخته می‌شود که تمام بخش‌های لندینگ را با ویجت‌های اختصاصی این افزونه (دسته «لندینگ حکمرانی بر بازار» در المنتور) و دقیقاً با همان محتوا و ظاهر نسخه استاتیک در خود دارد. بعد از ساخت، هر بخش را می‌توانید در المنتور ویرایش، جابه‌جا یا حذف کنید.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( self::ACTION ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="hkl_title">عنوان برگه</label></th>
							<td><input name="hkl_title" id="hkl_title" type="text" class="regular-text" value="حکمرانی بر بازار"></td>
						</tr>
						<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<tr>
							<th scope="row">صفحه اصلی</th>
							<td><label><input name="hkl_front" type="checkbox" value="1"> این برگه به‌عنوان صفحه اصلی سایت تنظیم شود</label></td>
						</tr>
						<?php endif; ?>
					</table>
					<?php submit_button( 'ساخت صفحه لندینگ', 'primary hero', 'submit', true, $elementor ? [] : [ 'disabled' => 'disabled' ] ); ?>
				</form>
				<p class="description">برگه با قالب «حکمرانی بر بازار — بوم کامل لندینگ» ساخته می‌شود؛ این قالب هدر، فوتر و استایل‌های پوسته را حذف می‌کند تا خروجی پیکسل‌به‌پیکسل با نسخه اصلی یکسان باشد.</p>
			</div>

			<?php if ( $pages ) : ?>
				<h2>برگه‌های ساخته‌شده</h2>
				<table class="widefat striped" style="max-width:760px">
					<tbody>
					<?php foreach ( $pages as $page ) : ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $page ) ); ?></td>
							<td style="text-align:left">
								<a class="button button-small" href="<?php echo esc_url( self::edit_link( $page->ID ) ); ?>">ویرایش با المنتور</a>
								<a class="button button-small" href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener">مشاهده</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<div class="card" style="max-width:760px">
				<h2>فرم پیش‌ثبت‌نام</h2>
				<p>درخواست‌های فرم پیش‌ثبت‌نام در منوی «درخواست‌ها» ذخیره می‌شوند. در صورت تمایل یک نشانی ایمیل برای دریافت اعلان وارد کنید.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'hkl_save_settings' ); ?>
					<input type="hidden" name="action" value="hkl_save_settings">
					<input name="hkl_lead_email" type="email" class="regular-text" dir="ltr" value="<?php echo esc_attr( get_option( HKL_Leads::EMAIL_OPTION, '' ) ); ?>" placeholder="info@example.com">
					<?php submit_button( 'ذخیره', 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
