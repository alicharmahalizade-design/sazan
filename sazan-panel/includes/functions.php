<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Load a template with theme override support.
 * Theme can override by placing a file at: yourtheme/sazan-panel/{$name}
 */
function szp_locate_template( $name, $args = array() ) {
	$theme = locate_template( array( 'sazan-panel/' . $name ) );
	$file  = $theme ? $theme : SZP_DIR . 'templates/' . $name;
	if ( ! file_exists( $file ) ) {
		return '';
	}
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // phpcs:ignore
	}
	ob_start();
	include $file;
	return ob_get_clean();
}

/** Published WooCommerce products as [id => title]. Empty if WC inactive. */
function szp_get_wc_products() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}
	$items = array();
	$q = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $q->posts as $pid ) {
		$items[ $pid ] = get_the_title( $pid );
	}
	return $items;
}

/** Convert a datetime-local value (site timezone) to a UTC timestamp. */
function szp_ts_from_datetime( $value ) {
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

/** Convert Persian/Latin digits to Persian digits. */
function szp_fa_digits( $str ) {
	return strtr( (string) $str, array(
		'0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
		'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
	) );
}

/** Convert Persian/Arabic digits in a string to Latin digits. */
function szp_latin_digits( $str ) {
	return strtr( (string) $str, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

/** Normalize a phone number to Latin digits only (drops spaces, dashes, etc.). */
function szp_normalize_mobile( $raw ) {
	$digits = preg_replace( '/\D+/', '', szp_latin_digits( (string) $raw ) );
	return (string) $digits;
}

/** Parse a financial amount entered by the user (Persian/Latin digits, separators) into a float. */
function szp_parse_amount( $raw ) {
	$s = preg_replace( '/[^\d.]/', '', szp_latin_digits( (string) $raw ) );
	if ( $s === '' || $s === '.' ) {
		return 0.0;
	}
	return (float) $s;
}

/** Format a number with thousands separators and Persian digits, with an optional currency label. */
function szp_money( $n, $currency = '' ) {
	$n   = (float) $n;
	$str = szp_fa_digits( number_format( $n, 0, '.', '،' ) );
	return $currency !== '' ? $str . ' ' . $currency : $str;
}

/** Gregorian -> Jalali. Returns array( jy, jm, jd ). Self-contained, no external dependency. */
function szp_g2j( $gy, $gm, $gd ) {
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

/** Jalali date + time string with Persian digits (stored Gregorian, shown Jalali). */
function szp_format_datetime( $ts ) {
	if ( ! $ts ) {
		return '';
	}
	$dt = new DateTime( '@' . $ts );
	$dt->setTimezone( wp_timezone() );
	list( $jy, $jm, $jd ) = szp_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
	$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	$out = $jd . ' ' . $months[ $jm ] . ' ' . $jy . ' - ' . $dt->format( 'H:i' );
	return szp_fa_digits( $out );
}

/** Countdown widget markup. JS fills the values. */
function szp_countdown_html( $ts, $label = 'تا شروع جلسه' ) {
	if ( ! $ts ) {
		return '';
	}
	ob_start(); ?>
	<div class="szp-countdown" data-deadline="<?php echo esc_attr( $ts * 1000 ); ?>">
		<span class="szp-cd-label"><?php echo esc_html( $label ); ?></span>
		<div class="szp-cd-units">
			<span class="szp-cd-box"><b data-d>۰</b><i>روز</i></span>
			<span class="szp-cd-box"><b data-h>۰</b><i>ساعت</i></span>
			<span class="szp-cd-box"><b data-m>۰</b><i>دقیقه</i></span>
			<span class="szp-cd-box"><b data-s>۰</b><i>ثانیه</i></span>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
