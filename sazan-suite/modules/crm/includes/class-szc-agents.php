<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * کارشناسانِ فروش به‌صورتِ موجودیتِ مستقلِ CRM (نه کاربرِ وردپرس).
 *
 * هر کارشناس نام و موبایل یکتا دارد و از طریقِ «پورتال» با کد یک‌بارمصرف یا رمز ثابت
 * وارد می‌شود (بدون ساختِ کاربرِ وردپرس). برای این‌که مالکیتِ سرنخ‌ها (owner_id)
 * با شناسه‌ی کاربرانِ وردپرس تداخل نکند، شناسه‌ی کارشناس با یک «آفست» به فضای
 * مالکیت نگاشت می‌شود: owner_id = OFFSET + agent_id. پس مقادیرِ کوچک‌ترِ owner_id
 * همچنان کاربرِ وردپرس (مدیر) و مقادیرِ ≥ OFFSET کارشناسِ CRM هستند.
 */
class SZC_Agents {

	/** آفستِ فضای‌نامِ مالکیت برای کارشناسان (بالاتر از هر شناسه‌ی واقعیِ کاربرِ وردپرس). */
	const OFFSET = 2000000000;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_agents';
	}

	/* ==================== نگاشتِ شناسه ↔ مالکیت ==================== */

	public static function to_owner( $agent_id ) {
		return self::OFFSET + (int) $agent_id;
	}

	public static function from_owner( $owner_id ) {
		$owner_id = (int) $owner_id;
		return $owner_id >= self::OFFSET ? $owner_id - self::OFFSET : 0;
	}

	public static function is_agent_owner( $owner_id ) {
		return (int) $owner_id >= self::OFFSET;
	}

	/* ==================== خواندن ==================== */

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function get_by_mobile( $mobile ) {
		global $wpdb;
		$mobile = szc_normalize_mobile( $mobile );
		if ( $mobile === '' ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE mobile=%s', $mobile ) );
	}

	/** همه‌ی کارشناسان (یا فقط فعال‌ها)، مرتب بر اساس نام. */
	public static function all( $only_active = false ) {
		global $wpdb;
		$where = $only_active ? ' WHERE active=1' : '';
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . $where . ' ORDER BY name ASC, id ASC' );
	}

	public static function active() {
		return self::all( true );
	}

	public static function count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
	}

	/** نامِ کارشناس از روی شناسه‌ی کارشناس. */
	public static function name( $id ) {
		$a = self::get( $id );
		return $a ? $a->name : '';
	}

	/** نامِ کارشناس از روی owner_id (≥ OFFSET). */
	public static function owner_name( $owner_id ) {
		return self::name( self::from_owner( $owner_id ) );
	}

	/** موبایلِ کارشناس از روی owner_id. */
	public static function owner_mobile( $owner_id ) {
		$a = self::get( self::from_owner( $owner_id ) );
		return $a ? szc_normalize_mobile( $a->mobile ) : '';
	}

	/** نگاشتِ [owner_id => name] برای فهرست‌های تخصیص (پیش‌فرض فقط فعال‌ها). */
	public static function assignable( $only_active = true ) {
		$out = array();
		foreach ( self::all( $only_active ) as $a ) {
			$out[ self::to_owner( $a->id ) ] = $a->name;
		}
		return $out;
	}

	/* ==================== نوشتن ==================== */

	/**
	 * ساختِ کارشناسِ تازه. خروجی: array( ok, msg, id ).
	 * $pass_hash!=='' یعنی هشِ آماده (برای مهاجرت) به‌جای رمزِ خام استفاده شود.
	 */
	public static function create( $name, $mobile, $pass, $active = 1, $pass_hash = '' ) {
		global $wpdb;
		$name   = sanitize_text_field( $name );
		$mobile = szc_normalize_mobile( $mobile );
		if ( $name === '' || $mobile === '' ) {
			return array( 'ok' => false, 'msg' => 'نام و موبایل الزامی است.' );
		}
		if ( ! szc_is_valid_mobile( $mobile ) ) {
			return array( 'ok' => false, 'msg' => 'شماره‌ی موبایل نامعتبر است (۰۹...).' );
		}
		if ( self::get_by_mobile( $mobile ) ) {
			return array( 'ok' => false, 'msg' => 'کارشناسی با این موبایل از قبل وجود دارد.' );
		}
		if ( $pass_hash === '' && (string) $pass !== '' ) {
			$valid = self::validate_password( $pass );
			if ( empty( $valid['ok'] ) ) {
				return $valid;
			}
		}
		// pass_hash هم رمز ورود ثابت را نگه می‌دارد و هم در امضای نشست‌های کارشناس نقش دارد.
		$hash = $pass_hash !== '' ? $pass_hash : ( (string) $pass !== '' ? self::hash_password( (string) $pass ) : self::hash_password( wp_generate_password( 48, true, true ) ) );
		$now  = current_time( 'mysql' );
		$wpdb->insert( self::table(), array(
			'name'       => $name,
			'mobile'     => $mobile,
			'pass_hash'  => $hash,
			'active'     => $active ? 1 : 0,
			'daily_answered_target' => 40,
			'created_at' => $now,
			'updated_at' => $now,
		) );
		$id = (int) $wpdb->insert_id;
		if ( $id && class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'create', 'agent', $id, 'کارشناس ایجاد شد' );
		}
		return array( 'ok' => true, 'msg' => 'کارشناس ساخته شد.', 'id' => $id );
	}

	public static function update( $id, $name, $mobile ) {
		global $wpdb;
		$name   = sanitize_text_field( $name );
		$mobile = szc_normalize_mobile( $mobile );
		if ( $name === '' || ! szc_is_valid_mobile( $mobile ) ) {
			return array( 'ok' => false, 'msg' => 'نام یا موبایل نامعتبر است.' );
		}
		$dup = self::get_by_mobile( $mobile );
		if ( $dup && (int) $dup->id !== (int) $id ) {
			return array( 'ok' => false, 'msg' => 'موبایل برای کارشناسِ دیگری ثبت شده است.' );
		}
		$wpdb->update( self::table(), array(
			'name' => $name, 'mobile' => $mobile, 'updated_at' => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'update', 'agent', $id, 'اطلاعات کارشناس ویرایش شد' );
		}
		return array( 'ok' => true, 'msg' => 'به‌روزرسانی شد.' );
	}

	public static function set_password( $id, $pass ) {
		global $wpdb;
		$pass = (string) $pass;
		if ( $pass === '' ) {
			return array( 'ok' => false, 'msg' => 'رمز خالی است.' );
		}
		$valid = self::validate_password( $pass );
		if ( empty( $valid['ok'] ) ) {
			return $valid;
		}
		$wpdb->update( self::table(), array(
			'pass_hash' => self::hash_password( $pass ),
			'updated_at' => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
		$wpdb->query( $wpdb->prepare(
			'UPDATE ' . self::table() . ' SET session_version=session_version+1 WHERE id=%d',
			(int) $id
		) );
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'password_change', 'agent', $id, 'رمز کارشناس تغییر کرد و نشست‌ها باطل شدند.' );
		}
		return array( 'ok' => true, 'msg' => 'رمز تغییر کرد.' );
	}

	public static function validate_password( $pass ) {
		$pass = (string) $pass;
		if ( strlen( $pass ) < 10 || ! preg_match( '/\p{L}/u', $pass ) || ! preg_match( '/[0-9۰-۹]/u', $pass ) ) {
			return array( 'ok' => false, 'msg' => 'رمز باید حداقل ۱۰ نویسه و شامل حداقل یک حرف و یک عدد باشد.' );
		}
		return array( 'ok' => true );
	}

	public static function revoke_sessions( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET session_version=session_version+1, updated_at=%s WHERE id=%d', current_time( 'mysql' ), (int) $id ) );
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'sessions_revoked', 'agent', $id, 'تمام نشست‌های کارشناس باطل شد.' );
		}
	}

	public static function record_login( $id, $ip ) {
		global $wpdb;
		$wpdb->update( self::table(), array(
			'last_login_at' => current_time( 'mysql' ),
			'last_login_ip' => substr( sanitize_text_field( $ip ), 0, 45 ),
		), array( 'id' => (int) $id ) );
	}

	/**
	 * هشِ رمزِ کارشناس با تابعِ نیتیوِ PHP (bcrypt).
	 * برخلافِ wp_check_password، به شناسه‌ی کاربرِ وردپرس وابسته نیست و
	 * از تداخلِ خطرناکِ شناسه‌ی کارشناس با کاربرِ وردپرس جلوگیری می‌کند.
	 */
	public static function hash_password( $pass ) {
		return password_hash( (string) $pass, PASSWORD_DEFAULT );
	}

	public static function set_active( $id, $active ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'active' => $active ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	public static function set_daily_answered_target( $id, $target ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'daily_answered_target' => max( 0, (int) $target ), 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $id )
		);
	}

	public static function set_daily_targets( $id, $answered, $followups, $conversions ) {
		global $wpdb;
		$wpdb->update( self::table(), array(
			'daily_answered_target'   => max( 0, (int) $answered ),
			'daily_followup_target'   => max( 0, (int) $followups ),
			'daily_conversion_target' => max( 0, (int) $conversions ),
			'updated_at'              => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
	}

	public static function permissions( $agent ) {
		$raw = is_object( $agent ) ? (string) ( $agent->permissions ?? '' ) : '';
		$out = json_decode( $raw, true );
		return is_array( $out ) ? $out : array();
	}

	public static function set_management_profile( $id, $capacity, $weight, $permissions = null ) {
		global $wpdb;
		$data = array(
			'capacity' => max( 0, (int) $capacity ),
			'distribution_weight' => max( 1, (int) $weight ),
			'updated_at' => current_time( 'mysql' ),
		);
		if ( is_array( $permissions ) ) {
			$clean = array();
			foreach ( SZC_Settings::permission_labels() as $key => $label ) {
				$clean[ $key ] = empty( $permissions[ $key ] ) ? 0 : 1;
			}
			$data['permissions'] = wp_json_encode( $clean );
		}
		$wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
	}

	/** حذفِ کارشناس. سرنخ‌های او بدونِ تخصیص می‌شوند (owner_id=0). */
	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'delete', 'agent', $id, 'کارشناس حذف شد و مخاطبان او بدون تخصیص شدند' );
		}
		$wpdb->update( SZC_Contacts::table(), array( 'owner_id' => 0 ), array( 'owner_id' => self::to_owner( $id ) ) );
		$wpdb->delete( self::table(), array( 'id' => $id ) );
	}

	/* ==================== احراز هویت ==================== */

	public static function verify( $agent, $pass ) {
		if ( ! $agent || (int) $agent->active !== 1 ) {
			return false;
		}
		$hash = (string) $agent->pass_hash;
		$pass = (string) $pass;
		if ( $hash === '' || $pass === '' ) {
			return false;
		}
		// هشِ نیتیوِ PHP (کارشناسانِ تازه).
		if ( $hash[0] === '$' && password_verify( $pass, $hash ) ) {
			return true;
		}
		// سازگاری با هشِ وردپرس (کارشناسانِ مهاجرت‌شده یا نسخه‌های قبلی) — بدونِ user_id.
		if ( function_exists( 'wp_check_password' ) && wp_check_password( $pass, $hash ) ) {
			return true;
		}
		return false;
	}

	/** تطبیق پایه موبایل+رمز؛ کنترل نرخ تلاش در SZC_Auth انجام می‌شود. */
	public static function authenticate( $mobile, $pass ) {
		$agent = self::get_by_mobile( $mobile );
		return ( $agent && self::verify( $agent, $pass ) ) ? $agent : null;
	}
}
