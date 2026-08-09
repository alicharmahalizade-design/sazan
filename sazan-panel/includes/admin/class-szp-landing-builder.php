<?php
/**
 * ساخت خودکار صفحه «ارزیابی کسب‌وکار» با المنتور (تک‌کلیکی).
 *
 * @package Sazan_Panel
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Landing_Builder {

	const OPTION_PAGE = 'szp_landing_page_id';
	const SLUG        = 'sazan-landing-builder';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
	}

	public static function menu() {
		add_submenu_page(
			'sazan-panel',
			'ساخت صفحه ارزیابی',
			'ساخت صفحه ارزیابی',
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'page' )
		);
	}

	/** ترتیب ویجت‌های صفحه؛ تنظیمات خالی یعنی مقادیر پیش‌فرض هر ویجت اعمال می‌شود. */
	protected static function sections() {
		return array(
			array( 'szl_header',   'هدر سایت' ),
			array( 'szl_hero',     'هیرو ارزیابی' ),
			array( 'szl_stats',    'نوار آمار' ),
			array( 'szl_tests',    'آزمون‌ها + فیلتر' ),
			array( 'szl_featured', 'ارزیابی جامع' ),
			array( 'szl_steps',    'چگونه کار می‌کند' ),
			array( 'szl_report',   'نمونه گزارش تحلیلی' ),
			array( 'szl_faq',      'سوالات متداول' ),
			array( 'szl_footer',   'فوتر سایت' ),
		);
	}

	/** شناسه یکتای المنتور (۷ کاراکتر hex). */
	protected static function uid() {
		return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
	}

	/** ساخت ساختار داده‌ی المنتور. */
	protected static function elementor_data() {
		$data = array();

		foreach ( self::sections() as $i => $sec ) {
			list( $widget, $label ) = $sec;

			$data[] = array(
				'id'       => self::uid(),
				'elType'   => 'section',
				'settings' => array(
					'structure'       => '10',
					'gap'             => 'no',
					'padding'         => array(
						'unit'     => 'px',
						'top'      => ( 0 === $i ) ? '24' : '0',
						'right'    => '0',
						'bottom'   => '18',
						'left'     => '0',
						'isLinked' => false,
					),
					'_title'          => 'سازان – ' . $label,
					'content_width'   => array( 'unit' => 'px', 'size' => 1200 ),
				),
				'elements' => array(
					array(
						'id'       => self::uid(),
						'elType'   => 'column',
						'settings' => array( '_column_size' => 100, '_inline_size' => null ),
						'elements' => array(
							array(
								'id'         => self::uid(),
								'elType'     => 'widget',
								'widgetType' => $widget,
								'settings'   => new stdClass(), // پیش‌فرض‌های خود ویجت اعمال می‌شوند.
								'elements'   => array(),
							),
						),
						'isInner'  => false,
					),
				),
				'isInner'  => false,
			);
		}

		return $data;
	}

	/** ایجاد یا بازنویسی صفحه. */
	protected static function build( $mode, $title ) {
		$existing = (int) get_option( self::OPTION_PAGE, 0 );
		$page_id  = 0;

		if ( 'overwrite' === $mode && $existing && get_post( $existing ) ) {
			$page_id = $existing;
			wp_update_post( array( 'ID' => $page_id, 'post_title' => $title, 'post_status' => 'publish' ) );
		} else {
			$page_id = wp_insert_post( array(
				'post_title'   => $title,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			) );
		}

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			return new WP_Error( 'szp_build', 'ساخت صفحه ناموفق بود.' );
		}

		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_wp_page_template', 'elementor_canvas' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
		}

		// پس‌زمینه تیره صفحه، هم‌رنگ طرح.
		update_post_meta( $page_id, '_elementor_page_settings', array(
			'background_background' => 'classic',
			'background_color'      => '#070e1a',
		) );

		// داده‌ی المنتور باید به‌صورت رشته JSON و اسلش‌دار ذخیره شود.
		$json = wp_json_encode( self::elementor_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		update_post_meta( $page_id, '_elementor_data', wp_slash( $json ) );

		update_option( self::OPTION_PAGE, $page_id );

		// پاک کردن کش CSS المنتور تا استایل‌ها بازتولید شوند.
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		return $page_id;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$notice   = '';
		$built_id = 0;

		if ( isset( $_POST['szp_build_landing'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			check_admin_referer( 'szp_build_landing' );

			if ( ! did_action( 'elementor/loaded' ) ) {
				$notice = '<div class="notice notice-error"><p>افزونه المنتور فعال نیست. ابتدا المنتور را فعال کنید.</p></div>';
			} else {
				$mode   = ( isset( $_POST['mode'] ) && 'overwrite' === $_POST['mode'] ) ? 'overwrite' : 'new';
				$title  = sanitize_text_field( wp_unslash( $_POST['page_title'] ?? '' ) );
				$title  = $title ? $title : 'ارزیابی کسب‌وکار';
				$result = self::build( $mode, $title );

				if ( is_wp_error( $result ) ) {
					$notice = '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
				} else {
					$built_id = (int) $result;
					$notice   = '<div class="notice notice-success"><p>صفحه با موفقیت ساخته شد.</p></div>';
				}
			}
		}

		$existing = (int) get_option( self::OPTION_PAGE, 0 );
		$has_page = $existing && get_post( $existing );
		$show_id  = $built_id ? $built_id : ( $has_page ? $existing : 0 );
		?>
		<div class="wrap" dir="rtl">
			<h1>ساخت صفحه «ارزیابی کسب‌وکار»</h1>
			<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<p>با یک کلیک، یک برگه‌ی المنتوری ساخته می‌شود که همه‌ی سکشن‌های طرح را به‌ترتیب و با
				تنظیمات پیش‌فرض در خود دارد: هدر، هیرو، نوار آمار، آزمون‌ها با فیلتر، ارزیابی جامع،
				مراحل، نمونه گزارش تحلیلی، سوالات متداول و فوتر.</p>
			<p>پس از ساخت، صفحه را با المنتور باز کنید و متن‌ها، لینک‌ها و تصاویر را از پنل تنظیمات هر ویجت تغییر دهید.</p>

			<form method="post">
				<?php wp_nonce_field( 'szp_build_landing' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="szp-page-title">عنوان صفحه</label></th>
						<td><input name="page_title" id="szp-page-title" type="text" class="regular-text" value="ارزیابی کسب‌وکار" /></td>
					</tr>
					<?php if ( $has_page ) : ?>
					<tr>
						<th scope="row">حالت ساخت</th>
						<td>
							<label><input type="radio" name="mode" value="new" checked /> ساخت یک صفحه‌ی تازه</label><br />
							<label><input type="radio" name="mode" value="overwrite" /> بازنویسی صفحه‌ی قبلی
								(<a href="<?php echo esc_url( get_permalink( $existing ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $existing ) ); ?></a>)
							</label>
							<p class="description">بازنویسی، چیدمان فعلی آن صفحه را با چیدمان پیش‌فرض جایگزین می‌کند.</p>
						</td>
					</tr>
					<?php endif; ?>
				</table>

				<p>
					<button type="submit" name="szp_build_landing" value="1" class="button button-primary button-hero">
						ساخت صفحه با المنتور
					</button>
				</p>
			</form>

			<?php if ( $show_id ) : ?>
				<hr />
				<p>
					<a class="button" href="<?php echo esc_url( get_permalink( $show_id ) ); ?>" target="_blank">مشاهده صفحه</a>
					<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?post=' . $show_id . '&action=elementor' ) ); ?>" target="_blank">ویرایش با المنتور</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}
}
