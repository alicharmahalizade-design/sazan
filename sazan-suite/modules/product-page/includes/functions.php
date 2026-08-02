<?php
/**
 * توابع کمکی: خواندن/ذخیره‌ی فیلدها، اعداد فارسی، قیمت، و کتابخانه‌ی آیکن.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * داده‌ها
 * ===================================================================== */

/**
 * کلید متای یک سکشن.
 *
 * @param string $section
 * @return string
 */
function spp_meta_key( $section ) {
	return '_spp_' . sanitize_key( $section );
}

/**
 * تمام داده‌های یک سکشن برای یک محصول (با اعمالِ پیش‌فرض‌ها).
 *
 * @param int    $post_id
 * @param string $section
 * @return array
 */
function spp_get_section_data( $post_id, $section ) {
	$saved = get_post_meta( (int) $post_id, spp_meta_key( $section ), true );
	$saved = is_array( $saved ) ? $saved : array();

	$fields = SPP_Sections::fields( $section );
	$out    = array();

	foreach ( $fields as $key => $def ) {
		$type = $def['type'] ?? 'text';

		if ( 'group' === $type ) {
			continue; // فقط تیتر بخش در فرم است، داده ندارد.
		}

		// پیش‌فرض فقط وقتی اعمال می‌شود که کلید هرگز ذخیره نشده باشد؛ در غیر این
		// صورت مقدارِ خالی هم محترم است (کاربر عمداً پاکش کرده).
		$has = array_key_exists( $key, $saved );

		if ( 'repeater' === $type ) {
			$rows        = $has && is_array( $saved[ $key ] ) ? array_values( $saved[ $key ] ) : ( $has ? array() : ( $def['default'] ?? array() ) );
			$out[ $key ] = $rows;
			continue;
		}

		if ( $has && ! is_array( $saved[ $key ] ) ) {
			$out[ $key ] = (string) $saved[ $key ];
		} else {
			$out[ $key ] = $has ? '' : (string) ( $def['default'] ?? '' );
		}
	}

	return $out;
}

/**
 * پاک‌سازی مقادیرِ ارسالی یک سکشن بر اساس تعریف فیلدها.
 *
 * @param array $raw
 * @param array $fields
 * @return array
 */
function spp_sanitize_values( $raw, $fields ) {
	$clean = array();

	foreach ( $fields as $key => $def ) {
		$type = $def['type'] ?? 'text';

		if ( in_array( $type, array( 'group', 'note' ), true ) ) {
			continue;
		}

		if ( 'repeater' === $type ) {
			$rows      = isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ? $raw[ $key ] : array();
			$sub       = $def['fields'] ?? array();
			$collected = array();

			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$item  = spp_sanitize_values( $row, $sub );
				$empty = true;

				// آیکن/سلکت همیشه مقدار پیش‌فرض دارند، پس در تشخیصِ «ردیف خالی» به حساب نمی‌آیند.
				foreach ( $item as $k => $v ) {
					$t = $sub[ $k ]['type'] ?? 'text';
					if ( in_array( $t, array( 'icon', 'select', 'switch' ), true ) ) {
						continue;
					}
					if ( '' !== $v && '0' !== $v ) {
						$empty = false;
						break;
					}
				}
				if ( ! $empty ) {
					$collected[] = $item;
				}
			}

			$clean[ $key ] = $collected;
			continue;
		}

		$value = $raw[ $key ] ?? '';
		if ( is_array( $value ) ) {
			$value = '';
		}

		switch ( $type ) {
			case 'textarea':
				$clean[ $key ] = sanitize_textarea_field( wp_unslash( $value ) );
				break;
			case 'url':
				$clean[ $key ] = esc_url_raw( trim( wp_unslash( $value ) ) );
				break;
			case 'number':
				$clean[ $key ] = '' === $value ? '' : (string) floatval( $value );
				break;
			case 'image':
				$clean[ $key ] = (string) absint( $value );
				break;
			case 'switch':
				$clean[ $key ] = $value ? '1' : '';
				break;
			case 'select':
			case 'icon':
				$options       = 'icon' === $type ? array_keys( spp_icon_list() ) : array_keys( $def['options'] ?? array() );
				$value         = sanitize_key( $value );
				$clean[ $key ] = in_array( $value, $options, true ) ? $value : ( $def['default'] ?? '' );
				break;
			case 'color':
				$clean[ $key ] = sanitize_hex_color( $value ) ?: '';
				break;
			default:
				$clean[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}
	}

	return $clean;
}

/* =========================================================================
 * اعداد و قیمت
 * ===================================================================== */

/**
 * تبدیل ارقام لاتین به فارسی.
 *
 * @param string|int|float $value
 * @return string
 */
function spp_fa_num( $value ) {
	$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	return str_replace( $en, $fa, (string) $value );
}

/**
 * شکستن قیمت به «عدد» و «واحد» — مثل ۸۵ + میلیون تومان.
 *
 * @param float  $amount
 * @param string $currency
 * @return array{num:string,unit:string}
 */
function spp_price_parts( $amount, $currency = 'تومان' ) {
	$amount = (float) $amount;

	if ( $amount <= 0 ) {
		return array(
			'num'  => '',
			'unit' => '',
		);
	}

	if ( $amount >= 1000000 ) {
		$m   = $amount / 1000000;
		$num = ( abs( $m - round( $m ) ) < 0.005 ) ? round( $m ) : round( $m, 1 );
		return array(
			'num'  => spp_fa_num( str_replace( '.', '٫', (string) $num ) ),
			'unit' => 'میلیون ' . $currency,
		);
	}

	if ( $amount >= 1000 ) {
		$k   = $amount / 1000;
		$num = ( abs( $k - round( $k ) ) < 0.005 ) ? round( $k ) : round( $k, 1 );
		return array(
			'num'  => spp_fa_num( str_replace( '.', '٫', (string) $num ) ),
			'unit' => 'هزار ' . $currency,
		);
	}

	return array(
		'num'  => spp_fa_num( number_format_i18n( $amount ) ),
		'unit' => $currency,
	);
}

/**
 * قیمت‌های محصول ووکامرس؛ اگر ووکامرس نبود، آرایه‌ی خالی.
 *
 * @param int $post_id
 * @return array{sale:float,regular:float,off:int}
 */
function spp_product_prices( $post_id ) {
	$out = array(
		'sale'    => 0.0,
		'regular' => 0.0,
		'off'     => 0,
	);

	if ( ! function_exists( 'wc_get_product' ) ) {
		return $out;
	}

	$product = wc_get_product( $post_id );
	if ( ! $product ) {
		return $out;
	}

	$out['sale']    = (float) $product->get_price();
	$out['regular'] = (float) $product->get_regular_price();

	if ( $out['regular'] > 0 && $out['sale'] > 0 && $out['regular'] > $out['sale'] ) {
		$out['off'] = (int) round( ( ( $out['regular'] - $out['sale'] ) / $out['regular'] ) * 100 );
	}

	return $out;
}

/**
 * لینک افزودن به سبد خرید (یا لینک محصول اگر ووکامرس نبود).
 *
 * @param int $post_id
 * @return string
 */
function spp_cart_link( $post_id ) {
	if ( function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post_id );
		if ( $product ) {
			return $product->add_to_cart_url();
		}
	}
	return (string) get_permalink( $post_id );
}

/* =========================================================================
 * آیکن‌ها
 * ===================================================================== */

/**
 * فهرست آیکن‌های موجود: کلید => برچسب فارسی.
 *
 * @return array<string,string>
 */
function spp_icon_list() {
	return array(
		'user'        => 'مدرس / کاربر',
		'clock'       => 'ساعت / مدت',
		'level'       => 'سطح دوره',
		'monitor'     => 'نوع برگزاری',
		'video'       => 'ویدیو',
		'support'     => 'پشتیبانی',
		'teacher'     => 'استاد',
		'content'     => 'محتوا',
		'project'     => 'پروژه',
		'certificate' => 'مدرک',
		'update'      => 'به‌روزرسانی',
		'community'   => 'انجمن / گروه',
		'download'    => 'دانلود',
		'infinity'    => 'دسترسی نامحدود',
		'shield'      => 'ضمانت',
		'gift'        => 'هدیه',
		'star'        => 'ستاره',
		'check'       => 'تیک',
		'cart'        => 'سبد خرید',
		'list'        => 'سرفصل‌ها',
		'fire'        => 'پیشنهاد ویژه',
		'search'      => 'تحلیل / ذره‌بین',
		'handshake'   => 'مذاکره / دست دادن',
		'chart'       => 'رشد / نمودار',
		'settings'    => 'مدیریت / چرخ‌دنده',
		'target'      => 'هدف',
		'bulb'        => 'ایده',
	);
}

/**
 * چاپ SVG یک آیکن (stroke-based، رنگ از currentColor).
 *
 * @param string $name
 * @param int    $size
 * @return string
 */
function spp_icon( $name, $size = 20 ) {
	$p = array(
		'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'level'       => '<path d="M4 20V14"/><path d="M10 20V9"/><path d="M16 20v-7"/><path d="M22 20V4"/>',
		'monitor'     => '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
		'video'       => '<rect x="2" y="5" width="14" height="14" rx="3"/><path d="m16 11 6-3.5v9L16 13z"/>',
		'support'     => '<path d="M4 15v-3a8 8 0 0 1 16 0v3"/><path d="M4 15a2 2 0 0 1 2-2h1v6H6a2 2 0 0 1-2-2z"/><path d="M20 15a2 2 0 0 0-2-2h-1v6h1a2 2 0 0 0 2-2z"/><path d="M17 19a3 3 0 0 1-3 3h-2"/>',
		'teacher'     => '<path d="m12 3 10 5-10 5L2 8z"/><path d="M6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>',
		'content'     => '<path d="M4 5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M15 3v5h5"/><path d="M8 13h8M8 17h5"/>',
		'project'     => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
		'certificate' => '<circle cx="12" cy="9" r="5.5"/><path d="m8.5 14-1 7 4.5-2.5L16.5 21l-1-7"/>',
		'update'      => '<path d="M21 12a9 9 0 1 1-3.2-6.9"/><path d="M21 4v5h-5"/>',
		'community'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 5.2a3.5 3.5 0 0 1 0 6.6"/><path d="M18.5 20a6.4 6.4 0 0 0-2-4.6"/>',
		'download'    => '<path d="M12 3v12"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 20h16"/>',
		'infinity'    => '<path d="M6.5 15.5a3.5 3.5 0 1 1 0-7c2.6 0 3.3 3.5 5.5 3.5s2.9-3.5 5.5-3.5a3.5 3.5 0 1 1 0 7c-2.6 0-3.3-3.5-5.5-3.5S9.1 15.5 6.5 15.5z"/>',
		'shield'      => '<path d="M12 3l8 3v6c0 4.6-3.2 8.3-8 9.6C7.2 20.3 4 16.6 4 12V6z"/><path d="m9 12 2 2 4-4"/>',
		'gift'        => '<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 13h18M12 8v13"/><path d="M12 8S10.5 3 8 4.5 12 8 12 8zM12 8s1.5-5 4-3.5S12 8 12 8z"/>',
		'star'        => '<path d="m12 3.5 2.7 5.6 6.1.8-4.4 4.3 1.1 6.1L12 17.4l-5.5 2.9 1.1-6.1L3.2 9.9l6.1-.8z"/>',
		'check'       => '<path d="m4.5 12.5 5 5 10-11"/>',
		'cart'        => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2.5 3.5h2.2l2.6 11.2h11.4l2.3-8.2H6"/>',
		'list'        => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
		'fire'        => '<path d="M12 22c3.9 0 6.5-2.6 6.5-6 0-4.5-4.5-6.5-4.5-10.5-2 1-3 2.8-3 4.5C11 8 9 7 9 4.5 7 6.5 5.5 9 5.5 12c0 3.4 2.6 10 6.5 10z"/>',
		'search'      => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
		'handshake'   => '<path d="m7 12.5 3-3 3.5 3.5a2 2 0 0 0 2.8 0l1.2-1.2"/><path d="M2.5 10 7 5.5l3.5 3.5"/><path d="M13.5 6.5 17 3l4.5 4.5-3.5 3.5"/><path d="m6.5 12.5 3.7 3.7a2 2 0 0 0 2.8 0"/>',
		'chart'       => '<path d="M4 20V9M9.5 20V4M15 20v-7M20.5 20v-11"/>',
		'settings'    => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M18.7 5.3l-2.1 2.1M7.4 16.6l-2.1 2.1"/>',
		'target'      => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/>',
		'bulb'        => '<path d="M9 18h6"/><path d="M10 21.5h4"/><path d="M12 2.5a6.5 6.5 0 0 1 3.9 11.7V18h-7.8v-3.8A6.5 6.5 0 0 1 12 2.5z"/>',
	);

	$name = isset( $p[ $name ] ) ? $name : 'check';
	$size = (int) $size;

	return sprintf(
		'<svg class="spp-i spp-i--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		$size,
		$p[ $name ]
	);
}

/**
 * تبدیل متن چندپاراگرافی به <p>؛ خط خالی = پاراگراف جدید، خط تنها = <br>.
 *
 * @param string $text
 * @return string
 */
function spp_paragraphs( $text ) {

	$text = trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $text ) );

	if ( '' === $text ) {
		return '';
	}

	$out = '';
	foreach ( preg_split( '/\n\s*\n/', $text ) as $chunk ) {
		$chunk = trim( $chunk );
		if ( '' === $chunk ) {
			continue;
		}
		$out .= '<p>' . nl2br( esc_html( $chunk ) ) . '</p>';
	}

	return $out;
}

/**
 * برجسته‌کردن یک عبارت داخل عنوان.
 *
 * @param string $title
 * @param string $needle
 * @return string
 */
function spp_highlight( $title, $needle ) {
	$title = esc_html( $title );

	if ( '' === trim( (string) $needle ) ) {
		return $title;
	}

	$needle = esc_html( trim( $needle ) );
	$pos    = mb_strpos( $title, $needle );

	if ( false === $pos ) {
		return $title;
	}

	return mb_substr( $title, 0, $pos )
		. '<span class="spp-hl">' . $needle . '</span>'
		. mb_substr( $title, $pos + mb_strlen( $needle ) );
}
