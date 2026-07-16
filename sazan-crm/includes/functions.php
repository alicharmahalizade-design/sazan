<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * کتابخانه‌ی آیکن‌های SVG (خطی، ۲۴×۲۴، هم‌رنگِ متن).
 *
 * جایگزینِ ایموجی‌ها در پورتالِ فرانت‌اند. هر آیکن با رنگِ جاری (currentColor)
 * ترسیم می‌شود و مقیاس‌پذیر است، پس در هر اندازه و در دارک‌مود درست دیده می‌شود.
 *
 * @param string $name  نام آیکن.
 * @param string $class کلاس‌های افزوده (اختیاری).
 * @return string مارک‌آپِ SVG آماده‌ی چاپ.
 */
function szc_icon( $name, $class = '' ) {
	static $lib = null;
	if ( null === $lib ) {
		$lib = array(
			'home'         => '<path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
			'phone'        => '<path d="M22 16.9v2.6a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2 3.7 2 2 0 0 1 4 1.5h2.7a2 2 0 0 1 2 1.7c.13.9.35 1.8.66 2.6a2 2 0 0 1-.45 2.1L7.6 9.1a16 16 0 0 0 6 6l1.1-1.3a2 2 0 0 1 2.1-.45c.85.31 1.72.53 2.6.66a2 2 0 0 1 1.7 2z"/>',
			'users'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.85"/><path d="M16 3.1a4 4 0 0 1 0 7.75"/>',
			'user'         => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
			'user-plus'    => '<path d="M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
			'plus'         => '<path d="M12 5v14M5 12h14"/>',
			'clock'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
			'calendar'     => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
			'bell'         => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
			'chart'        => '<path d="M3 3v17a1 1 0 0 0 1 1h17"/><rect x="7" y="12" width="3" height="5" rx="1"/><rect x="12.5" y="8" width="3" height="9" rx="1"/><rect x="18" y="5" width="3" height="12" rx="1"/>',
			'idcard'       => '<rect x="2" y="4.5" width="20" height="15" rx="2.5"/><circle cx="8" cy="11" r="2.5"/><path d="M4.5 16.5a3.5 3.5 0 0 1 7 0M14.5 9.5h4M14.5 13h3"/>',
			'search'       => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
			'settings'     => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
			'mail'         => '<rect x="2" y="4.5" width="20" height="15" rx="2.5"/><path d="M3 6.5l9 6 9-6"/>',
			'send'         => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>',
			'target'       => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/>',
			'check'        => '<path d="M20 6 9 17l-5-5"/>',
			'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="M22 4 12 14l-3-3"/>',
			'sparkles'     => '<path d="M12 3l1.9 5.6L19.5 10l-5.6 1.4L12 17l-1.9-5.6L4.5 10l5.6-1.4z"/><path d="M19 15l.7 2 .8-2 .5.7M5 5l.5 1.5L7 7l-1.5.5"/>',
			'folder'       => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
			'edit'         => '<path d="M11 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-6"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/>',
			'trash'        => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M10 11v6M14 11v6"/>',
			'smartphone'   => '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
			'briefcase'    => '<rect x="2" y="7.5" width="20" height="12.5" rx="2.5"/><path d="M16 20V6a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v14M2 12.5h20"/>',
			'map-pin'      => '<path d="M20 10.5c0 6-8 11.5-8 11.5s-8-5.5-8-11.5a8 8 0 0 1 16 0z"/><circle cx="12" cy="10.5" r="2.8"/>',
			'file-text'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/>',
			'repeat'       => '<path d="M17 2l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
			'history'      => '<path d="M3 3v5h5"/><path d="M3.5 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7.5v5l3.5 2"/>',
			'shuffle'      => '<path d="M16 3h5v5"/><path d="M4 20 21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6M4 4l5 5"/>',
			'star'         => '<path d="M12 3l2.7 5.5 6 .9-4.3 4.2 1 6-5.4-2.8L6.6 19.6l1-6L3.3 9.4l6-.9z"/>',
			'grip'         => '<circle cx="9" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="9" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="9" cy="18" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="6" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="15" cy="18" r="1.4" fill="currentColor" stroke="none"/>',
			'chevron-left' => '<path d="M15 18l-6-6 6-6"/>',
			'chevron-right'=> '<path d="M9 18l6-6-6-6"/>',
			'log-out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
			'tag'          => '<path d="M20.6 13.4 12 22l-9-9V3h10l8.6 8.6a1.8 1.8 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.4" fill="currentColor" stroke="none"/>',
			'x'            => '<path d="M18 6 6 18M6 6l12 12"/>',
			'ban'          => '<circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/>',
			'save'         => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
			'sun'          => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
			'moon'         => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
			'whatsapp'     => '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3z"/><path d="M8.5 8.2c.2-.5.4-.5.6-.5h.5c.2 0 .4 0 .6.5l.7 1.6c.1.2 0 .4-.1.5l-.5.6c-.1.1-.2.3-.1.5.3.6 1.4 1.8 2.4 2.2.2.1.4.1.5-.1l.5-.6c.2-.2.3-.2.5-.1l1.5.7c.2.1.3.3.3.4 0 .8-.6 1.5-1.3 1.6-.6.1-1.3.2-3-.6-2.3-1-3.7-3.4-3.8-3.6-.1-.2-.9-1.2-.9-2.3 0-1 .5-1.5.7-1.7z"/>',
			'telegram'     => '<path d="M21.5 4.3 2.9 11.5c-.8.3-.8 1.4 0 1.7l4.6 1.5 1.8 5.4c.2.6 1 .8 1.4.3l2.5-2.6 4.6 3.4c.5.4 1.2.1 1.4-.5l3.3-15c.2-.9-.6-1.6-1.5-1.4z"/><path d="M8 14.4 17 8l-6.5 7.2"/>',
			'merge'        => '<path d="M6 3v6a6 6 0 0 0 6 6h6"/><path d="M15 12l3 3-3 3M6 21v-6"/>',
			'bale'         => '<path d="M21 4 3 11l5 2 2 5 3-4 5 4 3-14z"/><path d="M8 13l9-6-6.5 7"/>',
			'rubika'       => '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.2A9 9 0 1 0 12 3z"/><path d="M8.5 12.5a3.5 3.5 0 0 0 7 0v-1"/>',
		);
	}
	$path = isset( $lib[ $name ] ) ? $lib[ $name ] : $lib['check'];
	$cls  = 'szc-ico szc-ico-' . preg_replace( '/[^a-z0-9\-]/', '', (string) $name ) . ( $class ? ' ' . $class : '' );
	return '<svg class="' . esc_attr( $cls ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

/** Convert Latin digits in a string to Persian digits. */
function szc_fa_digits( $str ) {
	return strtr( (string) $str, array(
		'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
		'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
	) );
}

/** Convert Persian/Arabic digits to Latin digits. */
function szc_latin_digits( $str ) {
	return strtr( (string) $str, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

/**
 * Normalize an Iranian mobile number to canonical 09xxxxxxxxx form when possible.
 * Accepts +98/0098/98/9xxxxxxxxx variants. Returns digits-only string (may be non-standard).
 */
function szc_normalize_mobile( $raw ) {
	$d = preg_replace( '/\D+/', '', szc_latin_digits( (string) $raw ) );
	if ( $d === '' ) {
		return '';
	}
	// 0098xxxxxxxxxx → drop 00.
	if ( strpos( $d, '0098' ) === 0 ) {
		$d = substr( $d, 2 );
	}
	// 98xxxxxxxxxx (12 digits starting 989) → 0xxxxxxxxxx.
	if ( strlen( $d ) === 12 && strpos( $d, '989' ) === 0 ) {
		$d = '0' . substr( $d, 2 );
	}
	// 9xxxxxxxxx (10 digits, no leading zero) → 09xxxxxxxxx.
	if ( strlen( $d ) === 10 && $d[0] === '9' ) {
		$d = '0' . $d;
	}
	return $d;
}

/** Basic validity check for an Iranian mobile (09xxxxxxxxx). */
function szc_is_valid_mobile( $mobile ) {
	return (bool) preg_match( '/^09\d{9}$/', (string) $mobile );
}

/** Best-effort mobile number for a WordPress user from common meta keys. */
function szc_user_mobile( $user_id ) {
	foreach ( array( 'billing_phone', 'mobile', 'phone', 'digits_phone', 'user_mobile', 'mobile_number' ) as $key ) {
		$val = get_user_meta( (int) $user_id, $key, true );
		if ( is_string( $val ) && trim( $val ) !== '' ) {
			return szc_normalize_mobile( $val );
		}
	}
	return '';
}

/** Convert a datetime-local / "Y-m-d H:i" value (site timezone) to a UTC timestamp. */
function szc_ts_from_datetime( $value ) {
	$value = trim( (string) $value );
	if ( $value === '' ) {
		return 0;
	}
	$value = str_replace( 'T', ' ', $value );
	try {
		$dt = new DateTime( $value, wp_timezone() );
		return $dt->getTimestamp();
	} catch ( Exception $e ) {
		$t = strtotime( $value );
		return $t ? $t : 0;
	}
}

/** Gregorian -> Jalali. Returns array( jy, jm, jd ). Self-contained. */
function szc_g2j( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + intval( ( $gy2 + 3 ) / 4 ) - intval( ( $gy2 + 99 ) / 100 )
		+ intval( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * intval( $days / 12053 ) );
	$days  %= 12053;
	$jy    += 4 * intval( $days / 1461 );
	$days  %= 1461;
	if ( $days > 365 ) {
		$jy   += intval( ( $days - 1 ) / 365 );
		$days  = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + intval( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + intval( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/** Jalali date (+ optional time) string with Persian digits from a UTC timestamp. */
function szc_format_datetime( $ts, $with_time = true ) {
	if ( ! $ts ) {
		return '';
	}
	$dt = new DateTime( '@' . (int) $ts );
	$dt->setTimezone( wp_timezone() );
	list( $jy, $jm, $jd ) = szc_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
	$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	$out = $jd . ' ' . $months[ $jm ] . ' ' . $jy;
	if ( $with_time ) {
		$out .= ' - ' . $dt->format( 'H:i' );
	}
	return szc_fa_digits( $out );
}

/** Jalali datetime from a MySQL datetime string (site timezone stored). */
function szc_format_mysql( $mysql, $with_time = true ) {
	if ( empty( $mysql ) || $mysql === '0000-00-00 00:00:00' ) {
		return '';
	}
	$ts = strtotime( get_gmt_from_date( $mysql ) . ' UTC' );
	return $ts ? szc_format_datetime( $ts, $with_time ) : '';
}

/** Relative "x minutes/hours ago" in Persian for a MySQL datetime. */
function szc_time_ago( $mysql ) {
	if ( empty( $mysql ) ) {
		return '';
	}
	$ts = strtotime( get_gmt_from_date( $mysql ) . ' UTC' );
	if ( ! $ts ) {
		return '';
	}
	$diff = time() - $ts;
	if ( $diff < 60 ) {
		return 'همین حالا';
	}
	if ( $diff < HOUR_IN_SECONDS ) {
		return szc_fa_digits( floor( $diff / 60 ) ) . ' دقیقه پیش';
	}
	if ( $diff < DAY_IN_SECONDS ) {
		return szc_fa_digits( floor( $diff / HOUR_IN_SECONDS ) ) . ' ساعت پیش';
	}
	if ( $diff < 30 * DAY_IN_SECONDS ) {
		return szc_fa_digits( floor( $diff / DAY_IN_SECONDS ) ) . ' روز پیش';
	}
	return szc_format_datetime( $ts, false );
}
