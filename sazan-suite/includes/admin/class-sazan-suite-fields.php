<?php
/**
 * رندر کنترل‌های فرم کنترل پنل.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Fields {

	/** پیشوند نام همه‌ی ورودی‌ها در فرم. */
	const PREFIX = 'sazan_suite';

	/** @var int شمارنده برای ساخت شناسه‌ی یکتا. */
	private static $uid = 0;

	/* =====================================================================
	 * کمکی‌ها
	 * =================================================================== */

	/** نام ورودی HTML یک فیلد. */
	public static function name( $store, $key, $suffix = '' ) {
		return self::PREFIX . '[' . $store . '][' . $key . ']' . $suffix;
	}

	/** شناسه‌ی یکتای یک فیلد. */
	private static function id( $store, $key ) {
		return 'szs-' . sanitize_html_class( $store . '-' . $key );
	}

	/** متن قابل جست‌وجو برای فیلتر زنده. */
	private static function haystack( $key, array $def ) {
		return trim( $key . ' ' . ( $def['label'] ?? '' ) . ' ' . wp_strip_all_tags( $def['hint'] ?? '' ) );
	}

	/* =====================================================================
	 * رندر یک بخش
	 * =================================================================== */

	/**
	 * رندر همه‌ی فیلدهای یک بخش.
	 *
	 * @param string $section_id
	 * @param array  $section
	 * @param array  $values
	 */
	public static function render_section( $section_id, array $section, array $values ) {

		$columns = isset( $section['columns'] ) ? (int) $section['columns'] : 1;
		$store   = $section['store'];

		echo '<div class="szs-grid szs-grid--' . (int) $columns . '">';

		foreach ( $section['fields'] as $key => $def ) {
			$value = array_key_exists( $key, $values ) ? $values[ $key ] : ( $def['default'] ?? '' );
			self::render_field( $store, $key, $def, $value );
		}

		echo '</div>';
	}

	/**
	 * رندر یک فیلد به همراه برچسب و راهنما.
	 *
	 * @param string $store
	 * @param string $key
	 * @param array  $def
	 * @param mixed  $value
	 */
	public static function render_field( $store, $key, array $def, $value ) {

		$type  = $def['type'] ?? 'text';
		$id    = self::id( $store, $key );
		$full  = ! empty( $def['full'] ) || in_array( $type, array( 'css', 'repeater', 'kv_rows', 'kv_select', 'kv_textarea', 'permissions', 'users', 'lines' ), true );
		$class = 'szs-field szs-field--' . sanitize_html_class( $type ) . ( $full ? ' szs-field--full' : '' );

		if ( ! empty( $def['advanced'] ) ) {
			$class .= ' szs-field--advanced';
		}

		printf(
			'<div class="%1$s" data-search="%2$s" data-key="%3$s">',
			esc_attr( $class ),
			esc_attr( self::haystack( $key, $def ) ),
			esc_attr( $key )
		);

		if ( 'switch' === $type ) {
			self::control( $store, $key, $def, $value, $id );
		} else {
			printf(
				'<label class="szs-field__label" for="%1$s">%2$s%3$s</label>',
				esc_attr( $id ),
				esc_html( $def['label'] ?? $key ),
				! empty( $def['advanced'] ) ? '<span class="szs-badge">پیشرفته</span>' : ''
			);
			echo '<div class="szs-field__control">';
			self::control( $store, $key, $def, $value, $id );
			echo '</div>';
		}

		if ( ! empty( $def['hint'] ) ) {
			echo '<p class="szs-field__hint">' . wp_kses_post( $def['hint'] ) . '</p>';
		}

		echo '</div>';
	}

	/* =====================================================================
	 * کنترل‌ها
	 * =================================================================== */

	/**
	 * رندر خودِ کنترل ورودی.
	 *
	 * @param string $store
	 * @param string $key
	 * @param array  $def
	 * @param mixed  $value
	 * @param string $id
	 */
	private static function control( $store, $key, array $def, $value, $id ) {

		$type  = $def['type'] ?? 'text';
		$name  = self::name( $store, $key );
		$ltr   = ! empty( $def['ltr'] ) ? ' dir="ltr" spellcheck="false"' : '';
		$place = isset( $def['placeholder'] ) ? ' placeholder="' . esc_attr( $def['placeholder'] ) . '"' : '';

		switch ( $type ) {

			case 'switch':
				printf(
					'<label class="szs-switch" for="%1$s"><input type="hidden" name="%2$s" value="0"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="szs-switch__track"></span><span class="szs-switch__text">%4$s</span></label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( ! empty( $value ), true, false ),
					esc_html( $def['label'] ?? $key )
				);
				break;

			case 'textarea':
			case 'lines':
			case 'css':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="%3$d" class="szs-input szs-textarea%4$s"%5$s%6$s>%7$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					(int) ( $def['rows'] ?? ( 'css' === $type ? 10 : 3 ) ),
					'css' === $type ? ' szs-code' : '',
					$ltr, // phpcs:ignore WordPress.Security.EscapeOutput
					$place, // phpcs:ignore WordPress.Security.EscapeOutput
					esc_textarea( is_scalar( $value ) ? (string) $value : '' )
				);
				break;

			case 'number':
				printf(
					'<span class="szs-number"><input type="number" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="szs-input"> %7$s</span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? $value : '' ),
					esc_attr( $def['min'] ?? '' ),
					esc_attr( $def['max'] ?? '' ),
					esc_attr( $def['step'] ?? 1 ),
					isset( $def['unit'] ) ? '<span class="szs-unit">' . esc_html( $def['unit'] ) . '</span>' : ''
				);
				break;

			case 'color':
				printf(
					'<span class="szs-color"><input type="color" id="%1$s" value="%3$s" class="szs-color__swatch" data-sync="%1$s-hex"><input type="text" id="%1$s-hex" name="%2$s" value="%3$s" class="szs-input szs-color__hex" dir="ltr" maxlength="7"></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( self::hex( $value ) )
				);
				break;

			case 'select':
				$options = Sazan_Suite_Sanitizer::resolve_options( $def['options'] ?? array() );
				printf( '<select id="%1$s" name="%2$s" class="szs-input">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $options as $opt_value => $opt_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $opt_value ),
						selected( (string) $value, (string) $opt_value, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'page':
				wp_dropdown_pages( array(
					'name'              => $name,
					'id'                => $id,
					'selected'          => (int) $value,
					'show_option_none'  => '— انتخاب صفحه —',
					'option_none_value' => 0,
					'class'             => 'szs-input',
				) );
				break;

			case 'users':
				self::users_control( $name, $id, $value );
				break;

			case 'image':
				self::image_control( $name, $id, (int) $value );
				break;

			case 'permissions':
				self::permissions_control( $store, $key, $def, $value );
				break;

			case 'kv_rows':
				self::kv_rows_control( $store, $key, $def, $value );
				break;

			case 'kv_select':
				self::kv_select_control( $store, $key, $def, $value );
				break;

			case 'kv_textarea':
				self::kv_textarea_control( $store, $key, $def, $value );
				break;

			case 'repeater':
				self::repeater_control( $store, $key, $def, $value );
				break;

			case 'secret':
				printf(
					'<span class="szs-secret"><input type="password" id="%1$s" name="%2$s" value="%3$s" class="szs-input" dir="ltr" autocomplete="off" spellcheck="false"%4$s><button type="button" class="szs-secret__toggle" aria-label="نمایش یا پنهان‌کردن کلید"><span class="dashicons dashicons-visibility"></span></button></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? (string) $value : '' ),
					$place // phpcs:ignore WordPress.Security.EscapeOutput
				);
				break;

			case 'date':
				printf(
					'<input type="date" id="%1$s" name="%2$s" value="%3$s" class="szs-input" dir="ltr">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? (string) $value : '' )
				);
				break;

			case 'url':
			case 'text':
			default:
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="szs-input"%5$s%6$s>',
					'url' === $type ? 'url' : 'text',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? (string) $value : '' ),
					$ltr, // phpcs:ignore WordPress.Security.EscapeOutput
					$place // phpcs:ignore WordPress.Security.EscapeOutput
				);
				break;

			case 'link':
				printf(
					'<input type="text" inputmode="url" id="%1$s" name="%2$s" value="%3$s" class="szs-input"%4$s%5$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? (string) $value : '' ),
					$ltr, // phpcs:ignore WordPress.Security.EscapeOutput
					$place // phpcs:ignore WordPress.Security.EscapeOutput
				);
				break;
		}
	}

	/** نرمال‌سازی یک رنگ به شکل #rrggbb. */
	private static function hex( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		return preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? $value : '#000000';
	}

	/* =====================================================================
	 * کنترل‌های ترکیبی
	 * =================================================================== */

	/** انتخاب چند کاربر وردپرس. */
	private static function users_control( $name, $id, $value ) {

		$selected = array_map( 'absint', (array) $value );
		$users    = get_users( array(
			'fields'  => array( 'ID', 'display_name', 'user_login' ),
			'orderby' => 'display_name',
			'number'  => 500,
		) );

		printf( '<input type="hidden" name="%s[]" value="">', esc_attr( $name ) );
		printf( '<div class="szs-users" id="%s">', esc_attr( $id ) );
		printf(
			'<input type="search" class="szs-users__search szs-input" placeholder="%s">',
			esc_attr( 'جست‌وجوی کاربر…' )
		);
		echo '<div class="szs-users__list">';

		foreach ( $users as $user ) {
			printf(
				'<label class="szs-users__item" data-search="%1$s"><input type="checkbox" name="%2$s[]" value="%3$d" %4$s><span>%5$s</span><small>%6$s</small></label>',
				esc_attr( $user->display_name . ' ' . $user->user_login ),
				esc_attr( $name ),
				(int) $user->ID,
				checked( in_array( (int) $user->ID, $selected, true ), true, false ),
				esc_html( $user->display_name ),
				esc_html( $user->user_login )
			);
		}

		echo '</div></div>';
	}

	/** انتخاب تصویر از کتابخانه‌ی رسانه. */
	private static function image_control( $name, $id, $attachment_id ) {

		$src = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';

		printf( '<div class="szs-image" data-target="%s">', esc_attr( $id ) );
		printf(
			'<div class="szs-image__preview">%s</div>',
			$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '<span class="dashicons dashicons-format-image"></span>'
		);
		printf(
			'<input type="hidden" id="%1$s" name="%2$s" value="%3$d">',
			esc_attr( $id ),
			esc_attr( $name ),
			(int) $attachment_id
		);
		echo '<div class="szs-image__actions">';
		echo '<button type="button" class="button szs-image__pick">انتخاب تصویر</button> ';
		echo '<button type="button" class="button-link szs-image__clear">حذف</button>';
		echo '</div></div>';
	}

	/** جدول تیک مجوزها. */
	private static function permissions_control( $store, $key, array $def, $value ) {

		$options = Sazan_Suite_Sanitizer::resolve_options( $def['options'] ?? array() );
		$current = is_array( $value ) ? $value : array();

		if ( ! $options ) {
			echo '<p class="szs-empty">این ماژول هم‌اکنون بارگذاری نشده است.</p>';
			return;
		}

		echo '<div class="szs-perms">';

		foreach ( $options as $perm => $label ) {
			printf(
				'<label class="szs-perms__item"><input type="checkbox" name="%1$s" value="1" %2$s><span>%3$s</span></label>',
				esc_attr( self::name( $store, $key, '[' . $perm . ']' ) ),
				checked( ! empty( $current[ $perm ] ), true, false ),
				esc_html( $label )
			);
		}

		echo '</div>';
	}

	/** ردیف‌های «شناسه ⇒ برچسب» با امکان افزودن و حذف. */
	private static function kv_rows_control( $store, $key, array $def, $value ) {

		$rows = is_array( $value ) ? $value : array();
		$name = self::name( $store, $key );

		printf(
			'<div class="szs-rows" data-name="%s" data-template="kv">',
			esc_attr( $name )
		);

		echo '<div class="szs-rows__head">';
		echo '<span>' . esc_html( $def['key_label'] ?? 'شناسه' ) . '</span>';
		echo '<span>' . esc_html( $def['value_label'] ?? 'برچسب' ) . '</span>';
		echo '<span></span>';
		echo '</div>';

		echo '<div class="szs-rows__body">';

		$index = 0;
		foreach ( $rows as $row_key => $row_label ) {
			self::kv_row( $name, $index, (string) $row_key, (string) $row_label );
			$index++;
		}

		echo '</div>';
		echo '<button type="button" class="button szs-rows__add">افزودن ردیف</button>';
		echo '</div>';
	}

	/** یک ردیف از kv_rows. */
	private static function kv_row( $name, $index, $row_key, $row_label ) {
		printf(
			'<div class="szs-rows__row"><input type="text" class="szs-input" dir="ltr" name="%1$s[%2$d][key]" value="%3$s" placeholder="stage_key"><input type="text" class="szs-input" name="%1$s[%2$d][value]" value="%4$s"><button type="button" class="szs-rows__remove" aria-label="حذف ردیف"><span class="dashicons dashicons-no-alt"></span></button></div>',
			esc_attr( $name ),
			(int) $index,
			esc_attr( $row_key ),
			esc_attr( $row_label )
		);
	}

	/** نگاشت کلیدهای ثابت به یک انتخاب‌گر. */
	private static function kv_select_control( $store, $key, array $def, $value ) {

		$keys    = Sazan_Suite_Sanitizer::resolve_options( $def['keys'] ?? array() );
		$options = Sazan_Suite_Sanitizer::resolve_options( $def['options'] ?? array() );
		$current = is_array( $value ) ? $value : array();

		if ( ! $keys ) {
			echo '<p class="szs-empty">فهرستی برای نمایش وجود ندارد.</p>';
			return;
		}

		echo '<div class="szs-map">';

		foreach ( $keys as $map_key => $map_label ) {
			echo '<div class="szs-map__row">';
			echo '<span class="szs-map__label">' . esc_html( $map_label ) . '</span>';
			printf( '<select class="szs-input" name="%s">', esc_attr( self::name( $store, $key, '[' . $map_key . ']' ) ) );
			foreach ( $options as $opt_value => $opt_label ) {
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $opt_value ),
					selected( (string) ( $current[ $map_key ] ?? '' ), (string) $opt_value, false ),
					esc_html( $opt_label )
				);
			}
			echo '</select>';
			echo '</div>';
		}

		echo '</div>';
	}

	/** نگاشت کلیدهای ثابت به یک کادر متن. */
	private static function kv_textarea_control( $store, $key, array $def, $value ) {

		$keys    = Sazan_Suite_Sanitizer::resolve_options( $def['keys'] ?? array() );
		$current = is_array( $value ) ? $value : array();

		if ( ! $keys ) {
			echo '<p class="szs-empty">فهرستی برای نمایش وجود ندارد؛ ابتدا ماژول مربوطه را روشن کنید.</p>';
			return;
		}

		echo '<div class="szs-map szs-map--text">';

		foreach ( $keys as $map_key => $map_label ) {
			echo '<div class="szs-map__row szs-map__row--stack">';
			echo '<span class="szs-map__label">' . esc_html( $map_label ) . '</span>';
			printf(
				'<textarea class="szs-input szs-textarea" rows="2" name="%1$s">%2$s</textarea>',
				esc_attr( self::name( $store, $key, '[' . $map_key . ']' ) ),
				esc_textarea( (string) ( $current[ $map_key ] ?? '' ) )
			);
			echo '</div>';
		}

		echo '</div>';
	}

	/** ردیف‌های تکرارشونده با زیرفیلد. */
	private static function repeater_control( $store, $key, array $def, $value ) {

		$rows      = is_array( $value ) ? $value : array();
		$subfields = $def['fields'] ?? array();
		$keyed     = ! empty( $def['keyed'] );
		$name      = self::name( $store, $key );
		$max       = (int) ( $def['max'] ?? 0 );

		printf(
			'<div class="szs-rep" data-name="%1$s" data-max="%2$d" data-keyed="%3$d">',
			esc_attr( $name ),
			$max,
			$keyed ? 1 : 0
		);

		echo '<div class="szs-rep__body">';

		$index = 0;
		foreach ( $rows as $row_key => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			self::repeater_row( $name, $index, $def, $row, $keyed ? (string) $row_key : '' );
			$index++;
		}

		echo '</div>';

		// الگوی ردیف خالی برای افزودن با جاوااسکریپت.
		echo '<template class="szs-rep__tpl">';
		self::repeater_row( $name, 0, $def, array(), '', true );
		echo '</template>';

		printf(
			'<button type="button" class="button szs-rep__add">افزودن %s</button>',
			esc_html( $def['row_label'] ?? 'ردیف' )
		);

		echo '</div>';
	}

	/** یک ردیف از repeater. */
	private static function repeater_row( $name, $index, array $def, array $row, $row_key = '', $is_template = false ) {

		$subfields   = $def['fields'] ?? array();
		$keyed       = ! empty( $def['keyed'] );
		$index_token = $is_template ? '__i__' : (string) (int) $index;

		echo '<div class="szs-rep__row">';
		echo '<div class="szs-rep__grid">';

		if ( $keyed ) {
			printf(
				'<label class="szs-rep__field"><span>%1$s</span><input type="text" class="szs-input" dir="ltr" name="%2$s[%3$s][__key]" value="%4$s"></label>',
				esc_html( $def['key_label'] ?? 'شناسه' ),
				esc_attr( $name ),
				esc_attr( $index_token ),
				esc_attr( $row_key )
			);
		}

		foreach ( $subfields as $sub_key => $sub_def ) {

			$sub_name  = $name . '[' . $index_token . '][' . $sub_key . ']';
			$sub_value = $row[ $sub_key ] ?? '';
			$sub_type  = $sub_def['type'] ?? 'text';

			echo '<label class="szs-rep__field szs-rep__field--' . esc_attr( sanitize_html_class( $sub_type ) ) . '">';
			echo '<span>' . esc_html( $sub_def['label'] ?? $sub_key ) . '</span>';

			switch ( $sub_type ) {
				case 'textarea':
					printf(
						'<textarea class="szs-input szs-textarea" rows="%1$d" name="%2$s">%3$s</textarea>',
						(int) ( $sub_def['rows'] ?? 2 ),
						esc_attr( $sub_name ),
						esc_textarea( (string) $sub_value )
					);
					break;

				case 'color':
					printf(
						'<input type="color" class="szs-color__swatch" value="%1$s" data-sync-name="%2$s"><input type="text" class="szs-input" dir="ltr" name="%2$s" value="%1$s" maxlength="7">',
						esc_attr( self::hex( $sub_value ) ),
						esc_attr( $sub_name )
					);
					break;

				case 'image':
					self::image_control( $sub_name, 'szs-rep-' . md5( $sub_name ), (int) $sub_value );
					break;

				default:
					printf(
						'<input type="text" class="szs-input" name="%1$s" value="%2$s"%3$s>',
						esc_attr( $sub_name ),
						esc_attr( (string) $sub_value ),
						! empty( $sub_def['ltr'] ) ? ' dir="ltr"' : ''
					);
					break;
			}

			if ( ! empty( $sub_def['hint'] ) ) {
				echo '<em class="szs-rep__hint">' . wp_kses_post( $sub_def['hint'] ) . '</em>';
			}

			echo '</label>';
		}

		echo '</div>';
		echo '<button type="button" class="szs-rep__remove" aria-label="حذف ردیف"><span class="dashicons dashicons-no-alt"></span></button>';
		echo '</div>';
	}
}
