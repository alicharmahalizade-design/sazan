<?php
/**
 * رندرِ فرمِ فیلدها — مشترک بین متاباکسِ محصول و صفحه‌ی تنظیمات سراسری.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Fields {

	/**
	 * چاپ همه‌ی فیلدهای یک مجموعه.
	 *
	 * @param string $base نامِ پایه، مثل spp[hero] یا spp_global.
	 * @param array  $fields
	 * @param array  $data
	 */
	public static function render( $base, $fields, $data ) {

		foreach ( $fields as $key => $def ) {
			$type = $def['type'] ?? 'text';

			if ( 'group' === $type ) {
				printf( '<h4 class="spp-mb__group">%s</h4>', esc_html( $def['label'] ) );
				continue;
			}

			if ( 'note' === $type ) {
				printf( '<p class="spp-mb__note">%s</p>', wp_kses_post( $def['label'] ) );
				continue;
			}

			if ( 'repeater' === $type ) {
				self::repeater( $base . '[' . $key . ']', $def, $data[ $key ] ?? array() );
				continue;
			}

			self::field( $base . '[' . $key . ']', $def, $data[ $key ] ?? '' );
		}
	}

	/**
	 * یک فیلد ساده.
	 *
	 * @param string $name
	 * @param array  $def
	 * @param mixed  $value
	 */
	public static function field( $name, $def, $value ) {

		$type  = $def['type'] ?? 'text';
		$id    = 'spp-' . md5( $name );
		$class = $def['class'] ?? '';
		?>
		<div class="spp-f <?php echo esc_attr( $class ); ?> spp-f--<?php echo esc_attr( $type ); ?>">
			<label class="spp-f__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def['label'] ?? '' ); ?></label>

			<div class="spp-f__ctrl">
				<?php
				switch ( $type ) {

					case 'textarea':
						printf(
							'<textarea id="%1$s" name="%2$s" rows="2">%3$s</textarea>',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_textarea( $value )
						);
						break;

					case 'select':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
						foreach ( ( $def['options'] ?? array() ) as $k => $lbl ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $k ),
								selected( $value, $k, false ),
								esc_html( $lbl )
							);
						}
						echo '</select>';
						break;

					case 'icon':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="spp-f__icon">';
						foreach ( spp_icon_list() as $k => $lbl ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $k ),
								selected( $value, $k, false ),
								esc_html( $lbl )
							);
						}
						echo '</select>';
						break;

					case 'switch':
						printf(
							'<label class="spp-f__switch"><input type="checkbox" name="%1$s" value="1"%2$s><span></span></label>',
							esc_attr( $name ),
							checked( $value, '1', false )
						);
						break;

					case 'image':
						$aid = (int) $value;
						$src = $aid ? wp_get_attachment_image_url( $aid, 'thumbnail' ) : '';
						?>
						<div class="spp-media<?php echo $src ? ' has-img' : ''; ?>">
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $aid ); ?>" class="spp-media__id">
							<span class="spp-media__prev"><?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" alt=""><?php endif; ?></span>
							<button type="button" class="button spp-media__pick">انتخاب تصویر</button>
							<button type="button" class="button-link spp-media__del">حذف</button>
						</div>
						<?php
						break;

					case 'number':
						printf(
							'<input type="number" step="any" id="%1$s" name="%2$s" value="%3$s">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $value )
						);
						break;

					default:
						printf(
							'<input type="text" id="%1$s" name="%2$s" value="%3$s" dir="auto">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $value )
						);
				}

				if ( ! empty( $def['hint'] ) ) {
					printf( '<p class="spp-f__hint">%s</p>', wp_kses_post( $def['hint'] ) );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * ریپیتر.
	 *
	 * @param string $name
	 * @param array  $def
	 * @param array  $rows
	 */
	public static function repeater( $name, $def, $rows ) {

		$rows  = is_array( $rows ) ? array_values( $rows ) : array();
		$label = $def['row_label'] ?? 'ردیف';
		$inline = ! empty( $def['inline'] ) ? ' spp-rep--inline' : '';
		?>
		<div class="spp-rep<?php echo esc_attr( $inline ); ?>" data-spp-rep data-max="<?php echo (int) ( $def['max'] ?? 20 ); ?>">
			<div class="spp-rep__head">
				<span class="spp-rep__title"><?php echo esc_html( $def['label'] ?? '' ); ?></span>
				<button type="button" class="button button-secondary spp-rep__add">+ افزودن <?php echo esc_html( $label ); ?></button>
			</div>

			<?php if ( ! empty( $def['hint'] ) ) : ?>
				<p class="spp-rep__hint"><?php echo wp_kses_post( $def['hint'] ); ?></p>
			<?php endif; ?>

			<div class="spp-rep__rows">
				<?php foreach ( $rows as $i => $row ) : ?>
					<?php self::row( $name, $def, (int) $i, is_array( $row ) ? $row : array() ); ?>
				<?php endforeach; ?>
			</div>

			<script type="text/html" class="spp-rep__tpl">
				<?php self::row( $name, $def, '__i__', array() ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * یک ردیف ریپیتر.
	 *
	 * @param string     $name
	 * @param array      $def
	 * @param int|string $index
	 * @param array      $row
	 */
	public static function row( $name, $def, $index, $row ) {
		?>
		<div class="spp-rep__row">
			<span class="spp-rep__grip" aria-hidden="true">⋮⋮</span>
			<div class="spp-rep__cells">
				<?php
				foreach ( ( $def['fields'] ?? array() ) as $key => $sub ) {
					$val = $row[ $key ] ?? ( $sub['default'] ?? '' );
					self::field( $name . '[' . $index . '][' . $key . ']', $sub, $val );
				}
				?>
			</div>
			<button type="button" class="spp-rep__del" title="حذف ردیف">×</button>
		</div>
		<?php
	}
}
