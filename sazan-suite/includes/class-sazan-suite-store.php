<?php
/**
 * لایه‌ی خواندن/نوشتن تنظیمات سوئیت.
 *
 * کنترل پنل، تنظیماتِ هر چهار ماژول را یکجا نشان می‌دهد اما آن‌ها را در
 * «همان گزینه‌های قبلی» ذخیره می‌کند. به این ترتیب کد داخلی ماژول‌ها
 * (SZP_Settings، SZC_Settings، SZP_Eval، SPP_Settings و …) بدون هیچ تغییری
 * همان مقادیر همیشگی را می‌خواند و هیچ داده‌ای مهاجرت نمی‌خواهد.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Store {

	/** @var array<string,array> کش درون‌درخواستی مقادیر. */
	private static $cache = array();

	/**
	 * نگاشت «مخزن» → گزینه‌ی وردپرس.
	 *
	 * type=array  : یک گزینه‌ی آرایه‌ای (رایج‌ترین حالت).
	 * type=split  : هر کلید در یک گزینه‌ی مستقل ذخیره می‌شود.
	 *
	 * @return array<string,array>
	 */
	public static function map() {
		return array(
			'suite' => array(
				'type'   => 'array',
				'option' => 'sazan_suite_settings',
				'label'  => 'سوئیت سازان',
			),
			'core' => array(
				'type'     => 'array',
				'option'   => 'sazan_core_settings',
				'label'    => 'هسته سازان',
				'defaults' => array( 'Sazan\\Settings', 'defaults' ),
				'module'   => 'core',
			),
			// این سه ماژول متد defaults() جدا ندارند و پیش‌فرض‌ها داخل خودِ
			// settings() هستند؛ با «bare» آن را با گزینه‌ی خالی صدا می‌زنیم تا
			// پیش‌فرضِ خالص به‌دست بیاید (نه مقدارِ ذخیره‌شده‌ی فعلی).
			'quiz' => array(
				'type'     => 'array',
				'option'   => 'sazan_quiz_settings',
				'label'    => 'آزمون‌ساز',
				'defaults' => array( 'Sazan\\Quiz_Engine', 'settings' ),
				'bare'     => true,
				'module'   => 'core',
			),
			'consult' => array(
				'type'     => 'array',
				'option'   => 'sazan_consult_settings',
				'label'    => 'رزرو مشاوره',
				'defaults' => array( 'Sazan\\Consult_Engine', 'settings' ),
				'bare'     => true,
				'module'   => 'core',
			),
			'form' => array(
				'type'     => 'array',
				'option'   => 'sazan_form_settings',
				'label'    => 'فرم‌ساز',
				'defaults' => array( 'Sazan\\Form_Engine', 'settings' ),
				'bare'     => true,
				'module'   => 'core',
			),
			'panel_ui' => array(
				'type'     => 'array',
				'option'   => 'szp_panel_settings',
				'label'    => 'رابط پنل کاربری',
				'defaults' => array( 'SZP_Settings', 'defaults' ),
				'module'   => 'panel',
			),
			'eval' => array(
				'type'     => 'array',
				'option'   => 'szp_eval_settings',
				'label'    => 'ارزیابی و جلسات',
				'defaults' => array( 'SZP_Eval', 'defaults' ),
				'module'   => 'panel',
			),
			'ai' => array(
				'type'   => 'split',
				'label'  => 'هوش مصنوعی',
				'module' => 'panel',
				'keys'   => array(
					'key'   => 'szp_ai_key',
					'model' => 'szp_ai_model',
					'base'  => 'szp_ai_base',
				),
			),
			'crm' => array(
				'type'     => 'array',
				'option'   => 'szc_settings',
				'label'    => 'CRM تیم فروش',
				'defaults' => array( 'SZC_Settings', 'defaults' ),
				'module'   => 'crm',
			),
			'spp' => array(
				'type'     => 'array',
				'option'   => 'spp_global',
				'label'    => 'صفحه اختصاصی محصول',
				'defaults' => array( __CLASS__, 'spp_defaults' ),
				'module'   => 'product-page',
			),
		);
	}

	/** شناسنامه‌ی یک مخزن. */
	public static function meta( $store ) {
		$map = self::map();
		return isset( $map[ $store ] ) ? $map[ $store ] : null;
	}

	/** آیا مخزن به ماژولی وابسته است که هم‌اکنون بارگذاری شده؟ */
	public static function is_available( $store ) {
		$meta = self::meta( $store );
		if ( ! $meta ) {
			return false;
		}
		if ( empty( $meta['module'] ) ) {
			return true;
		}
		return Sazan_Suite_Modules::is_loaded( $meta['module'] );
	}

	/** پیش‌فرض‌های سراسری «صفحه اختصاصی محصول» از روی تعریف فیلدهای خود ماژول. */
	public static function spp_defaults() {

		if ( ! class_exists( 'SPP_Settings' ) ) {
			return array();
		}

		$out = array();

		foreach ( SPP_Settings::fields() as $key => $def ) {

			$type = $def['type'] ?? 'text';

			if ( in_array( $type, array( 'group', 'note' ), true ) ) {
				continue;
			}

			if ( 'repeater' === $type ) {
				$out[ $key ] = (array) ( $def['default'] ?? array() );
			} elseif ( 'image' === $type ) {
				// ماژول، شناسه‌ی تصویر را عددی نگه می‌دارد؛ «بدون تصویر» یعنی صفر.
				$out[ $key ] = absint( $def['default'] ?? 0 );
			} else {
				$out[ $key ] = (string) ( $def['default'] ?? '' );
			}
		}

		return $out;
	}

	/**
	 * پیش‌فرض‌های یک مخزن (در صورت در دسترس بودن کلاس ماژول).
	 *
	 * برای مخزن‌هایی که متد پیش‌فرضِ جدا ندارند و پیش‌فرض‌ها داخل خودِ خواننده‌ی
	 * تنظیمات‌اند (پرچم bare)، گزینه‌ی ذخیره‌شده موقتاً خالی در نظر گرفته می‌شود
	 * تا پیش‌فرضِ خالص برگردد؛ وگرنه «بازگردانی پیش‌فرض» کاری انجام نمی‌داد.
	 *
	 * @param string $store
	 * @return array
	 */
	public static function defaults( $store ) {

		$meta = self::meta( $store );

		if ( ! $meta || empty( $meta['defaults'] ) || ! is_callable( $meta['defaults'] ) ) {
			return array();
		}

		if ( empty( $meta['bare'] ) ) {
			$defaults = call_user_func( $meta['defaults'] );
			return is_array( $defaults ) ? $defaults : array();
		}

		$blank = static function () {
			return array();
		};

		add_filter( 'pre_option_' . $meta['option'], $blank, PHP_INT_MAX );
		$defaults = call_user_func( $meta['defaults'] );
		remove_filter( 'pre_option_' . $meta['option'], $blank, PHP_INT_MAX );

		return is_array( $defaults ) ? $defaults : array();
	}

	/**
	 * همه‌ی مقادیر یک مخزن (خام + پیش‌فرض‌های ماژول).
	 *
	 * @param string $store
	 * @return array
	 */
	public static function all( $store ) {

		if ( isset( self::$cache[ $store ] ) ) {
			return self::$cache[ $store ];
		}

		$meta = self::meta( $store );
		if ( ! $meta ) {
			return array();
		}

		if ( 'split' === $meta['type'] ) {
			$out = array();
			foreach ( $meta['keys'] as $key => $option ) {
				$out[ $key ] = get_option( $option, '' );
			}
			self::$cache[ $store ] = $out;
			return $out;
		}

		$saved = get_option( $meta['option'], array() );
		$saved = is_array( $saved ) ? $saved : array();
		$out   = array_merge( self::defaults( $store ), $saved );

		self::$cache[ $store ] = $out;
		return $out;
	}

	/**
	 * یک مقدار.
	 *
	 * @param string $store
	 * @param string $key
	 * @param mixed  $fallback
	 * @return mixed
	 */
	public static function get( $store, $key, $fallback = null ) {
		$all = self::all( $store );
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * نوشتن مجموعه‌ای از کلیدها در یک مخزن.
	 *
	 * فقط کلیدهای ارسالی بازنویسی می‌شوند؛ هر کلیدِ دیگری که در گزینه وجود
	 * دارد (حتی اگر کنترل پنل آن را نشناسد) دست‌نخورده باقی می‌ماند.
	 *
	 * @param string $store
	 * @param array  $values
	 * @return bool
	 */
	public static function update( $store, array $values ) {

		$meta = self::meta( $store );
		if ( ! $meta || ! $values ) {
			return false;
		}

		unset( self::$cache[ $store ] );

		if ( 'split' === $meta['type'] ) {
			foreach ( $values as $key => $value ) {
				if ( isset( $meta['keys'][ $key ] ) ) {
					update_option( $meta['keys'][ $key ], $value );
				}
			}
			return true;
		}

		$saved = get_option( $meta['option'], array() );
		$saved = is_array( $saved ) ? $saved : array();

		update_option( $meta['option'], array_merge( $saved, $values ) );

		return true;
	}

	/** بازگرداندن یک مخزن به پیش‌فرض کارخانه. */
	public static function reset( $store ) {

		$meta = self::meta( $store );
		if ( ! $meta ) {
			return false;
		}

		unset( self::$cache[ $store ] );

		if ( 'split' === $meta['type'] ) {
			foreach ( $meta['keys'] as $option ) {
				delete_option( $option );
			}
			return true;
		}

		delete_option( $meta['option'] );

		return true;
	}

	/** خالی‌کردن کش درون‌درخواستی. */
	public static function flush() {
		self::$cache = array();
	}

	/**
	 * خروجی کامل تنظیمات همه‌ی مخزن‌ها (برای پشتیبان‌گیری).
	 *
	 * @return array
	 */
	public static function export() {

		$out = array(
			'_meta' => array(
				'plugin'    => 'sazan-suite',
				'version'   => SAZAN_SUITE_VERSION,
				'exported'  => gmdate( 'c' ),
				'site'      => home_url( '/' ),
			),
			'modules' => Sazan_Suite_Modules::states(),
			'stores'  => array(),
		);

		foreach ( self::map() as $store => $meta ) {
			if ( 'split' === $meta['type'] ) {
				$values = array();
				foreach ( $meta['keys'] as $key => $option ) {
					$values[ $key ] = get_option( $option, '' );
				}
				$out['stores'][ $store ] = $values;
				continue;
			}

			$saved = get_option( $meta['option'], array() );
			$out['stores'][ $store ] = is_array( $saved ) ? $saved : array();
		}

		return $out;
	}

	/**
	 * بازگرداندن پشتیبان.
	 *
	 * @param array $data خروجیِ export().
	 * @return int تعداد مخزن‌های بازگردانی‌شده.
	 */
	public static function import( array $data ) {

		if ( empty( $data['stores'] ) || ! is_array( $data['stores'] ) ) {
			return 0;
		}

		$map   = self::map();
		$count = 0;

		foreach ( $data['stores'] as $store => $values ) {

			if ( ! isset( $map[ $store ] ) || ! is_array( $values ) ) {
				continue;
			}

			$meta = $map[ $store ];

			if ( 'split' === $meta['type'] ) {
				foreach ( $meta['keys'] as $key => $option ) {
					if ( array_key_exists( $key, $values ) ) {
						update_option( $option, is_scalar( $values[ $key ] ) ? (string) $values[ $key ] : '' );
					}
				}
				$count++;
				continue;
			}

			update_option( $meta['option'], $values );
			$count++;
		}

		if ( ! empty( $data['modules'] ) && is_array( $data['modules'] ) ) {
			Sazan_Suite_Modules::save_states( $data['modules'] );
		}

		self::flush();

		return $count;
	}
}
