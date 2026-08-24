<?php
/**
 * تنظیمات سراسری — چیزهایی که یک‌بار انتخاب می‌شوند و در همه‌ی محصولات
 * تکرار می‌شوند (مثل آواتار دانشجویان).
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Settings {

	const OPTION = 'spp_global';
	const SLUG   = 'spp-global';

	/** @var array|null کش درون‌درخواستی. */
	private static $cache = null;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_spp_save_global', array( __CLASS__, 'save' ) );

		// اگر گزینه از هر مسیری تغییر کرد، کش را بریز.
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'delete_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
	}

	/**
	 * خالی‌کردن کش.
	 */
	public static function flush() {
		self::$cache = null;
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	/**
	 * تعریف فیلدهای سراسری.
	 *
	 * @return array
	 */
	public static function fields() {
		return (array) apply_filters( 'spp_global_fields', array(

			'g_header' => array(
				'type'  => 'group',
				'label' => 'هدر صفحات دوره',
			),
			'header_logo' => array(
				'type'  => 'image',
				'label' => 'تصویر لوگو',
				'hint'  => 'لوگوی سایت را انتخاب کنید. اگر خالی باشد، نشان و نوشته SAZAN نمایش داده می‌شود.',
			),
			'header_logo_url' => array(
				'type'    => 'url',
				'label'   => 'لینک لوگو',
				'default' => '',
				'hint'    => 'اگر خالی باشد، لوگو به صفحه اصلی سایت متصل می‌شود.',
			),
			'header_menu' => array(
				'type'    => 'menu',
				'label'   => 'فهرست هدر',
				'default' => '0',
				'hint'    => 'یکی از فهرست‌های ساخته‌شده در نمایش ← فهرست‌ها را انتخاب کنید. حالت پیش‌فرض، لینک‌های داخلی صفحه دوره را نمایش می‌دهد.',
			),
			'header_button_text' => array(
				'type'    => 'text',
				'label'   => 'متن دکمه هدر',
				'default' => 'ورود / ثبت‌نام',
			),
			'header_button_url' => array(
				'type'    => 'url',
				'label'   => 'لینک دکمه هدر',
				'default' => '',
				'hint'    => 'اگر خالی باشد، دکمه به بخش ثبت‌نام همان دوره می‌رود.',
			),

			'g_students' => array(
				'type'  => 'group',
				'label' => 'دانشجویان (مشترک بین همه‌ی دوره‌ها)',
			),
			'avatars' => array(
				'type'      => 'repeater',
				'label'     => 'آواتار دانشجویان',
				'row_label' => 'آواتار',
				'max'       => 8,
				'inline'    => true,
				'hint'      => 'همین تصاویر به‌طور خودکار در سکشن هیروی <strong>همه‌ی</strong> دوره‌ها نمایش داده می‌شوند. اگر برای یک دوره‌ی خاص چیز دیگری می‌خواهی، در متاباکس همان محصول آواتار اضافه کن تا جای این‌ها را بگیرد.',
				'fields'    => array(
					'img' => array(
						'type'  => 'image',
						'label' => 'تصویر',
					),
				),
			),
			'students_text' => array(
				'type'    => 'text',
				'label'   => 'متن پیش‌فرض کنار تعداد',
				'default' => 'دانشجو این دوره را تهیه کرده‌اند',
				'hint'    => 'اگر در محصول خالی باشد، از این استفاده می‌شود.',
			),

			'g_about' => array(
				'type'  => 'group',
				'label' => 'سکشن ۲ — درباره دوره',
			),
			'about_image' => array(
				'type'  => 'image',
				'label' => 'تصویر تزئینی گوشه‌ی راست',
				'hint'  => 'یک‌بار انتخاب کن تا در سکشن ۲ <strong>همه‌ی</strong> دوره‌ها بیاید. برای یک دوره‌ی خاص می‌توانی در متاباکس همان محصول تصویر دیگری بگذاری.',
			),

			'g_inst' => array(
				'type'  => 'group',
				'label' => 'مدرس ثابت همه‌ی دوره‌ها',
			),
			'global_instructor_enabled' => array(
				'type'    => 'switch',
				'label'   => 'استفاده از این مدرس در همه‌ی دوره‌ها',
				'default' => '1',
				'hint'    => 'وقتی روشن باشد، اطلاعات تکمیل‌شده‌ی زیر در تمام صفحات دوره جایگزین مدرس محصول می‌شود.',
			),
			'inst_image' => array(
				'type'  => 'image',
				'label' => 'عکس مدرس',
				'hint'  => 'تصویر مربع یا پرتره با کیفیت مناسب؛ یک‌بار آپلود می‌شود و در همه‌ی دوره‌ها نمایش داده خواهد شد.',
			),
			'inst_name' => array(
				'type'  => 'text',
				'label' => 'نام مدرس',
			),
			'inst_role' => array(
				'type'  => 'text',
				'label' => 'عنوان و تخصص مدرس',
			),
			'inst_short_description' => array(
				'type'  => 'textarea',
				'label' => 'معرفی کوتاه مدرس',
			),
			'inst_bio' => array(
				'type'  => 'textarea',
				'label' => 'زندگی‌نامه یا معرفی کامل',
			),
			'inst_experience' => array(
				'type'  => 'text',
				'label' => 'نشان تجربه',
				'hint'  => 'عبارت کامل وارد کنید؛ نمونه: «۱۸ سال تجربه اجرایی».',
			),
			'inst_projects' => array(
				'type'  => 'text',
				'label' => 'نشان پروژه‌ها',
				'hint'  => 'عبارت کامل؛ نمونه: «بیش از ۱۲۰ پروژه مشاوره».',
			),
			'inst_teaching_hours' => array(
				'type'  => 'text',
				'label' => 'نشان سابقه آموزش',
				'hint'  => 'عبارت کامل؛ نمونه: «بیش از ۱۰۰۰ ساعت آموزش».',
			),
			'inst_linkedin' => array(
				'type'  => 'url',
				'label' => 'لینک LinkedIn',
			),
			'inst_website' => array(
				'type'  => 'url',
				'label' => 'وب‌سایت مدرس',
			),

			'g_certificate' => array(
				'type'  => 'group',
				'label' => 'گواهینامه ثابت همه‌ی دوره‌ها',
			),
			'certificate_enabled_all' => array(
				'type'  => 'switch',
				'label' => 'نمایش گواهینامه در همه‌ی دوره‌ها',
				'hint'  => 'اگر خاموش باشد، فقط دوره‌هایی که گواهینامه برایشان فعال شده این بخش را نمایش می‌دهند.',
			),
			'certificate_image' => array(
				'type'  => 'image',
				'label' => 'تصویر ثابت گواهینامه',
				'hint'  => 'تصویر اختصاصی شما در همه‌ی صفحات دارای گواهینامه استفاده می‌شود.',
			),
			'certificate_badge' => array(
				'type'    => 'text',
				'label'   => 'برچسب گواهینامه',
				'default' => 'گواهینامه پایان دوره',
			),
			'certificate_title' => array(
				'type'  => 'text',
				'label' => 'عنوان سکشن گواهینامه',
			),
			'certificate_description' => array(
				'type'  => 'textarea',
				'label' => 'توضیح گواهینامه',
			),
			'certificate_conditions' => array(
				'type'  => 'textarea',
				'label' => 'شرایط دریافت گواهینامه',
			),

			'g_faq' => array(
				'type'  => 'group',
				'label' => 'سکشن ۷ — سوالات متداول',
			),
			'faq_image' => array(
				'type'  => 'image',
				'label' => 'تصویر تزئینی',
				'hint'  => 'خالی بگذار تا علامت «؟» گرافیکیِ خودِ افزونه نمایش داده شود.',
			),
		) );
	}

	/**
	 * همه‌ی مقادیر سراسری (با پیش‌فرض‌ها).
	 *
	 * @return array
	 */
	public static function data() {

		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$saved  = get_option( self::OPTION, array() );
		$saved  = is_array( $saved ) ? $saved : array();
		$fields = self::fields();
		$out    = array();

		foreach ( $fields as $key => $def ) {
			$type = $def['type'] ?? 'text';

			if ( in_array( $type, array( 'group', 'note' ), true ) ) {
				continue;
			}

			$has = array_key_exists( $key, $saved );

			if ( 'repeater' === $type ) {
				$out[ $key ] = $has && is_array( $saved[ $key ] ) ? array_values( $saved[ $key ] ) : ( $has ? array() : ( $def['default'] ?? array() ) );
				continue;
			}

			if ( $has && ! is_array( $saved[ $key ] ) ) {
				$out[ $key ] = (string) $saved[ $key ];
			} else {
				$out[ $key ] = $has ? '' : (string) ( $def['default'] ?? '' );
			}
		}

		self::$cache = $out;
		return $out;
	}

	/* =====================================================================
	 * منو و صفحه
	 * =================================================================== */

	public static function menu() {

		$parent = post_type_exists( 'product' ) ? 'edit.php?post_type=product' : 'options-general.php';

		add_submenu_page(
			$parent,
			'صفحه اختصاصی محصول — تنظیمات سراسری',
			'صفحه اختصاصی محصول',
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'page' )
		);
	}

	public static function page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$data = self::data();
		?>
		<div class="wrap spp-wrap-admin" dir="rtl">
			<h1>صفحه اختصاصی محصول — تنظیمات سراسری</h1>

			<?php if ( isset( $_GET['spp-saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
			<?php endif; ?>

			<p class="description">
				این مقادیر یک‌بار انتخاب می‌شوند و در سکشن‌های همه‌ی محصولات به‌کار می‌روند.
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="spp_save_global">
				<?php wp_nonce_field( 'spp_save_global' ); ?>

				<div class="spp-mb spp-mb--settings">
					<div class="spp-mb__panels">
						<?php SPP_Fields::render( self::OPTION, self::fields(), $data ); ?>
					</div>
				</div>

				<?php submit_button( 'ذخیره تنظیمات' ); ?>
			</form>
		</div>
		<?php
	}

	/* =====================================================================
	 * ذخیره
	 * =================================================================== */

	public static function save() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		check_admin_referer( 'spp_save_global' );

		$raw   = isset( $_POST[ self::OPTION ] ) && is_array( $_POST[ self::OPTION ] ) ? $_POST[ self::OPTION ] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$clean = spp_sanitize_values( $raw, self::fields() );

		update_option( self::OPTION, $clean );
		self::flush();

		wp_safe_redirect( add_query_arg(
			array(
				'post_type' => 'product',
				'page'      => self::SLUG,
				'spp-saved' => 1,
			),
			admin_url( post_type_exists( 'product' ) ? 'edit.php' : 'options-general.php' )
		) );
		exit;
	}
}

/**
 * خواندن یک مقدار سراسری.
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function spp_global( $key, $default = '' ) {
	$data = SPP_Settings::data();
	return array_key_exists( $key, $data ) && '' !== $data[ $key ] ? $data[ $key ] : $default;
}

/**
 * شناسه‌ی تصویر با اولویت محصول و سپس تنظیمات سراسری.
 *
 * @param mixed  $local      مقدار فیلدِ همان محصول.
 * @param string $global_key کلید فیلد در تنظیمات سراسری.
 * @return int
 */
function spp_image_id( $local, $global_key ) {

	$id = (int) $local;

	if ( $id ) {
		return $id;
	}

	return (int) spp_global( $global_key, 0 );
}

/**
 * آواتارهای دانشجو — اولویت: خودِ محصول، بعد تنظیمات سراسری.
 *
 * @param array $product_rows
 * @return array
 */
function spp_avatars( $product_rows ) {

	$rows = is_array( $product_rows ) ? array_filter( $product_rows, function ( $r ) {
		return ! empty( $r['img'] );
	} ) : array();

	if ( ! empty( $rows ) ) {
		return array_values( $rows );
	}

	$global = spp_global( 'avatars', array() );

	return is_array( $global ) ? array_values( array_filter( $global, function ( $r ) {
		return ! empty( $r['img'] );
	} ) ) : array();
}
