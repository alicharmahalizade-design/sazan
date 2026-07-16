<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * کارشناسانِ فروش به‌صورتِ موجودیتِ مستقلِ CRM (نه کاربرِ وردپرس).
 *
 * هر کارشناس فقط نام، موبایل (یکتا) و رمز دارد و از طریقِ «پورتال» با موبایل+رمز
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
		$hash = $pass_hash !== '' ? $pass_hash : ( (string) $pass !== '' ? wp_hash_password( (string) $pass ) : '' );
		$now  = current_time( 'mysql' );
		$wpdb->insert( self::table(), array(
			'name'       => $name,
			'mobile'     => $mobile,
			'pass_hash'  => $hash,
			'active'     => $active ? 1 : 0,
			'created_at' => $now,
			'updated_at' => $now,
		) );
		return array( 'ok' => true, 'msg' => 'کارشناس ساخته شد.', 'id' => (int) $wpdb->insert_id );
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
		return array( 'ok' => true, 'msg' => 'به‌روزرسانی شد.' );
	}

	public static function set_password( $id, $pass ) {
		global $wpdb;
		$pass = (string) $pass;
		if ( $pass === '' ) {
			return;
		}
		$wpdb->update( self::table(), array(
			'pass_hash' => wp_hash_password( $pass ), 'updated_at' => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
	}

	public static function set_active( $id, $active ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'active' => $active ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	/** حذفِ کارشناس. سرنخ‌های او بدونِ تخصیص می‌شوند (owner_id=0). */
	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		$wpdb->update( SZC_Contacts::table(), array( 'owner_id' => 0 ), array( 'owner_id' => self::to_owner( $id ) ) );
		$wpdb->delete( self::table(), array( 'id' => $id ) );
	}

	/* ==================== احراز هویت ==================== */

	public static function verify( $agent, $pass ) {
		if ( ! $agent || (int) $agent->active !== 1 || $agent->pass_hash === '' ) {
			return false;
		}
		return wp_check_password( (string) $pass, $agent->pass_hash, (int) $agent->id );
	}

	/** تطبیقِ موبایل+رمز. خروجی: رکوردِ کارشناس یا null. */
	public static function authenticate( $mobile, $pass ) {
		$agent = self::get_by_mobile( $mobile );
		return ( $agent && self::verify( $agent, $pass ) ) ? $agent : null;
	}
}
