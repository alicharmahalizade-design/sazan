<?php
/**
 * پاک‌سازی ورودی‌های کنترل پنل بر اساس نوع هر فیلد در شِما.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Sanitizer {

	/**
	 * فهرست گزینه‌های یک فیلد (ثابت یا از طریق callable).
	 *
	 * @param mixed $source
	 * @return array
	 */
	public static function resolve_options( $source ) {

		if ( is_callable( $source ) ) {
			$source = call_user_func( $source );
		}

		return is_array( $source ) ? $source : array();
	}

	/**
	 * پاک‌سازی همه‌ی فیلدهای یک بخش.
	 *
	 * فیلدی که در ورودی نیامده باشد اصلاً برنگردانده می‌شود تا مقدار فعلی‌اش
	 * در پایگاه داده دست‌نخورده بماند (به‌جز کلیدهایی مثل چک‌باکس که نبودشان
	 * خودش یک مقدار معنادار است).
	 *
	 * @param array $fields  تعریف فیلدهای بخش.
	 * @param array $raw     ورودی خام همان بخش.
	 * @param array $current مقادیر فعلی (برای بازگشت امن هنگام ورودی نامعتبر).
	 * @return array
	 */
	public static function section( array $fields, array $raw, array $current = array() ) {

		$out = array();

		foreach ( $fields as $key => $def ) {

			$type    = isset( $def['type'] ) ? $def['type'] : 'text';
			$present = array_key_exists( $key, $raw );

			// نبودِ چک‌باکس/جدول مجوزها یعنی «خاموش»، نه «تغییر نکرده».
			if ( ! $present && ! in_array( $type, array( 'switch', 'permissions', 'repeater', 'kv_rows', 'users' ), true ) ) {
				continue;
			}

			$value   = $present ? $raw[ $key ] : null;
			$fallback = array_key_exists( $key, $current ) ? $current[ $key ] : null;

			$out[ $key ] = self::field( $def, $value, $fallback );
		}

		return $out;
	}

	/**
	 * پاک‌سازی یک فیلد.
	 *
	 * @param array $def      تعریف فیلد.
	 * @param mixed $value    مقدار خام.
	 * @param mixed $fallback مقدار فعلی برای بازگشت در صورت نامعتبر بودن.
	 * @return mixed
	 */
	public static function field( array $def, $value, $fallback = null ) {

		$type = isset( $def['type'] ) ? $def['type'] : 'text';

		switch ( $type ) {

			case 'switch':
				return empty( $value ) ? 0 : 1;

			case 'number':
				$number = is_numeric( $value ) ? (int) $value : (int) $fallback;
				if ( isset( $def['min'] ) ) {
					$number = max( (int) $def['min'], $number );
				}
				if ( isset( $def['max'] ) ) {
					$number = min( (int) $def['max'], $number );
				}
				return $number;

			case 'color':
				$color = sanitize_hex_color( is_string( $value ) ? $value : '' );
				return $color ? $color : (string) $fallback;

			case 'select':
				$options = self::resolve_options( isset( $def['options'] ) ? $def['options'] : array() );
				$value   = is_scalar( $value ) ? (string) $value : '';
				if ( array_key_exists( $value, $options ) ) {
					// کلیدهای عددی باید عددی برگردند.
					return self::match_key_type( $value, $options );
				}
				return $fallback;

			case 'date':
				$value = is_string( $value ) ? trim( $value ) : '';
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';

			case 'url':
				return esc_url_raw( is_string( $value ) ? trim( $value ) : '' );

			case 'link':
				$value = is_string( $value ) ? trim( $value ) : '';
				if ( preg_match( '/^#[A-Za-z][A-Za-z0-9_:.-]*$/', $value ) ) {
					return $value;
				}
				return esc_url_raw( $value );

			case 'page':
			case 'image':
				return absint( $value );

			case 'css':
				return wp_strip_all_tags( is_string( $value ) ? $value : '' );

			case 'lines':
			case 'textarea':
				return sanitize_textarea_field( is_string( $value ) ? $value : '' );

			case 'secret':
				return trim( sanitize_text_field( is_string( $value ) ? $value : '' ) );

			case 'users':
				$ids = is_array( $value ) ? $value : array();
				$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
				return $ids;

			case 'permissions':
				$options = self::resolve_options( isset( $def['options'] ) ? $def['options'] : array() );
				$input   = is_array( $value ) ? $value : array();
				$out     = array();
				foreach ( array_keys( $options ) as $perm ) {
					$out[ $perm ] = empty( $input[ $perm ] ) ? 0 : 1;
				}
				return $out;

			case 'kv_rows':
				return self::kv_rows( $value );

			case 'kv_select':
				$keys    = self::resolve_options( isset( $def['keys'] ) ? $def['keys'] : array() );
				$options = self::resolve_options( isset( $def['options'] ) ? $def['options'] : array() );
				$input   = is_array( $value ) ? $value : array();
				$out     = array();
				foreach ( array_keys( $keys ) as $k ) {
					$picked = isset( $input[ $k ] ) ? (string) $input[ $k ] : '';
					if ( '' === $picked || ! array_key_exists( $picked, $options ) ) {
						continue;
					}
					$picked = self::match_key_type( $picked, $options );
					if ( $picked ) {
						$out[ $k ] = $picked;
					}
				}
				return $out;

			case 'kv_textarea':
				$keys  = self::resolve_options( isset( $def['keys'] ) ? $def['keys'] : array() );
				$input = is_array( $value ) ? $value : array();
				$out   = array();
				foreach ( array_keys( $keys ) as $k ) {
					if ( isset( $input[ $k ] ) ) {
						$out[ $k ] = sanitize_textarea_field( (string) $input[ $k ] );
					}
				}
				return $out;

			case 'repeater':
				return self::repeater( $def, $value );

			case 'text':
			default:
				return sanitize_text_field( is_string( $value ) ? $value : '' );
		}
	}

	/**
	 * کلیدهای عددیِ آرایه‌ی گزینه‌ها باید به‌صورت عدد ذخیره شوند.
	 *
	 * @param string $value
	 * @param array  $options
	 * @return int|string
	 */
	private static function match_key_type( $value, array $options ) {

		foreach ( array_keys( $options ) as $key ) {
			if ( (string) $key === (string) $value ) {
				return $key;
			}
		}

		return $value;
	}

	/**
	 * ردیف‌های «شناسه ⇒ برچسب» (مثل مراحل قیف فروش).
	 *
	 * ترتیب ردیف‌ها حفظ می‌شود چون در قیف فروش معنا دارد.
	 *
	 * @param mixed $value
	 * @return array
	 */
	private static function kv_rows( $value ) {

		$rows = is_array( $value ) ? $value : array();
		$out  = array();

		foreach ( $rows as $row ) {

			if ( ! is_array( $row ) ) {
				continue;
			}

			$key   = sanitize_key( $row['key'] ?? '' );
			$label = sanitize_text_field( $row['value'] ?? '' );

			if ( '' === $key || '' === $label ) {
				continue;
			}

			$out[ $key ] = $label;
		}

		return $out;
	}

	/**
	 * ردیف‌های تکرارشونده.
	 *
	 * @param array $def
	 * @param mixed $value
	 * @return array
	 */
	private static function repeater( array $def, $value ) {

		$rows      = is_array( $value ) ? $value : array();
		$subfields = isset( $def['fields'] ) && is_array( $def['fields'] ) ? $def['fields'] : array();
		$keyed     = ! empty( $def['keyed'] );
		$max       = isset( $def['max'] ) ? (int) $def['max'] : 0;
		$out       = array();

		foreach ( $rows as $row ) {

			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			$empty = true;

			foreach ( $subfields as $sub_key => $sub_def ) {
				$clean[ $sub_key ] = self::field( $sub_def, $row[ $sub_key ] ?? null );
				if ( '' !== $clean[ $sub_key ] && 0 !== $clean[ $sub_key ] && array() !== $clean[ $sub_key ] ) {
					$empty = false;
				}
			}

			if ( $empty ) {
				continue;
			}

			if ( $keyed ) {
				$row_key = sanitize_key( $row['__key'] ?? '' );
				if ( '' === $row_key ) {
					continue;
				}
				$out[ $row_key ] = $clean;
			} else {
				$out[] = $clean;
			}

			if ( $max > 0 && count( $out ) >= $max ) {
				break;
			}
		}

		return $out;
	}
}
