<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
