<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** قالب‌های پیامک با متغیر (%name%، %link%، %mini%، %intro% و…). */
class SZC_Templates {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_templates';
	}

	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id ASC' );
	}

	/** فقط قالب‌هایی که برای ارسال امن، Template ID پترن دارند. */
	public static function all_patterned() {
		return array_values( array_filter( self::all(), static function ( $template ) {
			return trim( (string) $template->pattern_code ) !== '';
		} ) );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function create( $name, $body, $pattern_code = '' ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		$body = sanitize_textarea_field( $body );
		if ( $name === '' || $body === '' ) {
			return 0;
		}
		$now = current_time( 'mysql' );
		$wpdb->insert( self::table(), array(
			'name'         => $name,
			'body'         => $body,
			'pattern_code' => sanitize_text_field( $pattern_code ),
			'created_at'   => $now,
			'updated_at'   => $now,
		) );
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $name, $body, $pattern_code = '' ) {
		global $wpdb;
		$wpdb->update( self::table(), array(
			'name'         => sanitize_text_field( $name ),
			'body'         => sanitize_textarea_field( $body ),
			'pattern_code' => sanitize_text_field( $pattern_code ),
			'updated_at'   => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) );
	}

	/** جایگزینی متغیرها: %key% → مقدار. */
	public static function fill( $body, $vars ) {
		$rep = array();
		foreach ( (array) $vars as $k => $v ) {
			$rep[ '%' . $k . '%' ] = (string) $v;
		}
		return strtr( (string) $body, $rep );
	}

	/**
	 * مخاطبِ قالب‌های پیش‌فرض با نام خانوادگی خطاب شود.
	 *
	 * فقط متن‌های دست‌نخورده‌ی پیش‌فرض مهاجرت می‌شوند تا قالبی که مدیر شخصی‌سازی
	 * کرده است بازنویسی نشود. متغیرهای صریحِ %first% و %name% همچنان برای
	 * قالب‌های سفارشی با معنای قبلی در دسترس‌اند.
	 */
	public static function migrate_default_salutations_to_last_name() {
		global $wpdb;
		$changes = array(
			'تشکر ۱ — پس از تماس + مینی‌دوره' => array(
				'%first% عزیز، از وقتی که برای گفتگو گذاشتید سپاسگزاریم. این هم لینک مینی‌دوره‌ی رایگان ما: %mini%',
				'%last% عزیز، از وقتی که برای گفتگو گذاشتید سپاسگزاریم. این هم لینک مینی‌دوره‌ی رایگان ما: %mini%',
			),
			'تشکر ۲ — گرم و صمیمی' => array(
				'%first% جان، خوشحال شدیم که با شما صحبت کردیم. هر سؤالی داشتید ما در کنارتان هستیم. 🌟',
				'%last% جان، خوشحال شدیم که با شما صحبت کردیم. هر سؤالی داشتید ما در کنارتان هستیم. 🌟',
			),
			'تشکر ۳ — رسمی' => array(
				'%name% گرامی، از زمانی که در اختیار ما گذاشتید سپاسگزاریم. در صورت تمایل، اطلاعات تکمیلی خدمت شما ارسال می‌شود.',
				'%last% گرامی، از زمانی که در اختیار ما گذاشتید سپاسگزاریم. در صورت تمایل، اطلاعات تکمیلی خدمت شما ارسال می‌شود.',
			),
			'تشکر ۴ — دعوت به اقدام' => array(
				'%first% عزیز، ممنون از گفتگوی امروز. برای شروع می‌توانید از این لینک استفاده کنید: %mini%',
				'%last% عزیز، ممنون از گفتگوی امروز. برای شروع می‌توانید از این لینک استفاده کنید: %mini%',
			),
			'تشکر ۵ — پیگیری تماس ناموفق' => array(
				'%first% عزیز، تماس گرفتیم اما در دسترس نبودید. لطفاً زمان مناسب برای تماس مجدد را به ما اطلاع دهید. 🙏',
				'%last% عزیز، تماس گرفتیم اما در دسترس نبودید. لطفاً زمان مناسب برای تماس مجدد را به ما اطلاع دهید. 🙏',
			),
			'دعوت به جلسه‌ی معارفه' => array(
				'%first% عزیز، از شما برای شرکت در جلسه‌ی معارفه‌ی دوره دعوت می‌کنیم. جزئیات و ثبت‌نام: %intro%',
				'%last% عزیز، از شما برای شرکت در جلسه‌ی معارفه‌ی دوره دعوت می‌کنیم. جزئیات و ثبت‌نام: %intro%',
			),
		);
		foreach ( $changes as $name => $bodies ) {
			$wpdb->update(
				self::table(),
				array( 'body' => $bodies[1], 'updated_at' => current_time( 'mysql' ) ),
				array( 'name' => $name, 'body' => $bodies[0] )
			);
		}
	}

	/** قالب‌های نمونه‌ی اولیه (فقط اگر هیچ قالبی نباشد). */
	public static function seed_defaults() {
		global $wpdb;
		$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
		if ( $count > 0 ) {
			return;
		}
		self::create(
			'تشکر ۱ — پس از تماس + مینی‌دوره',
			'%last% عزیز، از وقتی که برای گفتگو گذاشتید سپاسگزاریم. این هم لینک مینی‌دوره‌ی رایگان ما: %mini%'
		);
		self::create(
			'تشکر ۲ — گرم و صمیمی',
			'%last% جان، خوشحال شدیم که با شما صحبت کردیم. هر سؤالی داشتید ما در کنارتان هستیم. 🌟'
		);
		self::create(
			'تشکر ۳ — رسمی',
			'%last% گرامی، از زمانی که در اختیار ما گذاشتید سپاسگزاریم. در صورت تمایل، اطلاعات تکمیلی خدمت شما ارسال می‌شود.'
		);
		self::create(
			'تشکر ۴ — دعوت به اقدام',
			'%last% عزیز، ممنون از گفتگوی امروز. برای شروع می‌توانید از این لینک استفاده کنید: %mini%'
		);
		self::create(
			'تشکر ۵ — پیگیری تماس ناموفق',
			'%last% عزیز، تماس گرفتیم اما در دسترس نبودید. لطفاً زمان مناسب برای تماس مجدد را به ما اطلاع دهید. 🙏'
		);
		self::create(
			'دعوت به جلسه‌ی معارفه',
			'%last% عزیز، از شما برای شرکت در جلسه‌ی معارفه‌ی دوره دعوت می‌کنیم. جزئیات و ثبت‌نام: %intro%'
		);
	}
}
