<?php
/** Admin field renderer with nested repeaters for Product Page v2. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Fields {

	public static function render( $base, $fields, $data, $depth = 0 ) {
		foreach ( $fields as $key => $def ) {
			$value = isset( $data[ $key ] ) ? $data[ $key ] : $def['default'];
			$name  = $base . '[' . $key . ']';
			if ( 'repeater' === $def['type'] ) {
				self::repeater( $name, $def, $value, $depth );
			} else {
				self::field( $name, $key, $def, $value );
			}
		}
	}

	private static function field( $name, $key, $def, $value ) {
		$type      = $def['type'];
		$id        = 'spp-v2-' . substr( md5( $name ), 0, 12 );
		$condition = isset( $def['show_if'] ) ? $def['show_if'] : '';
		?>
		<div class="spp-v2-field spp-v2-field--<?php echo esc_attr( $type ); ?>"<?php echo $condition ? ' data-spp-v2-show-if="' . esc_attr( $condition ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?> data-field-key="<?php echo esc_attr( $key ); ?>">
			<label class="spp-v2-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $def['label'] ); ?></label>
			<div class="spp-v2-field__control">
				<?php
				switch ( $type ) {
					case 'textarea':
						echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="4">' . esc_textarea( $value ) . '</textarea>';
						break;
					case 'editor':
						echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="spp-v2-rich" rows="7">' . esc_textarea( $value ) . '</textarea>';
						break;
					case 'select':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
						foreach ( $def['options'] as $option => $label ) {
							printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
						}
						echo '</select>';
						break;
					case 'icon':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="spp-v2-icon-select">';
						foreach ( spp_icon_list() as $option => $label ) {
							printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
						}
						echo '</select>';
						break;
					case 'switch':
						printf( '<label class="spp-v2-switch"><input id="%1$s" type="checkbox" name="%2$s" value="1"%3$s><span aria-hidden="true"></span></label>', esc_attr( $id ), esc_attr( $name ), checked( $value, '1', false ) );
						break;
					case 'image':
						$attachment = absint( $value );
						$src = $attachment ? wp_get_attachment_image_url( $attachment, 'thumbnail' ) : '';
						?>
						<div class="spp-v2-media<?php echo $src ? ' has-image' : ''; ?>">
							<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $attachment ); ?>" class="spp-v2-media__id">
							<div class="spp-v2-media__preview"><?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" alt=""><?php endif; ?></div>
							<div><button type="button" class="button spp-v2-media__pick">انتخاب تصویر</button> <button type="button" class="button-link-delete spp-v2-media__remove">حذف</button></div>
						</div>
						<?php
						break;
					case 'video':
						$attachment = absint( $value );
						$src = $attachment ? wp_get_attachment_url( $attachment ) : '';
						?>
						<div class="spp-v2-video<?php echo $src ? ' has-video' : ''; ?>">
							<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $attachment ); ?>" class="spp-v2-video__id">
							<div class="spp-v2-video__preview"><?php if ( $src ) : ?><video src="<?php echo esc_url( $src ); ?>" controls preload="metadata"></video><?php endif; ?></div>
							<div><button type="button" class="button spp-v2-video__pick">انتخاب ویدیو</button> <button type="button" class="button-link-delete spp-v2-video__remove">حذف</button></div>
						</div>
						<?php
						break;
					case 'number':
						printf(
							'<input id="%1$s" type="number" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s">',
							esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( isset( $def['min'] ) ? $def['min'] : '' ), esc_attr( isset( $def['max'] ) ? $def['max'] : '' ), esc_attr( isset( $def['step'] ) ? $def['step'] : 'any' )
						);
						break;
					case 'datetime':
						echo '<input id="' . esc_attr( $id ) . '" type="datetime-local" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
						break;
					case 'url':
						// A course link may be a full URL or an in-page anchor such as
						// #curriculum. Native URL validation rejects anchors and silently
						// prevents the WooCommerce product form from being submitted.
						echo '<input id="' . esc_attr( $id ) . '" type="text" inputmode="url" dir="ltr" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="https:// یا #section">';
						break;
					default:
						echo '<input id="' . esc_attr( $id ) . '" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" dir="auto">';
				}
				if ( ! empty( $def['hint'] ) ) {
					echo '<p class="description">' . wp_kses_post( $def['hint'] ) . '</p>';
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function repeater( $name, $def, $rows, $depth ) {
		$rows  = is_array( $rows ) ? array_values( $rows ) : array();
		$token = '__spp_v2_' . (int) $depth . '__';
		?>
		<div class="spp-v2-repeater spp-v2-repeater--depth-<?php echo (int) $depth; ?>" data-spp-v2-repeater data-base="<?php echo esc_attr( $name ); ?>" data-max="<?php echo (int) $def['max']; ?>">
			<div class="spp-v2-repeater__header">
				<strong><?php echo esc_html( $def['label'] ); ?></strong>
				<button type="button" class="button button-secondary spp-v2-repeater__add">+ افزودن <?php echo esc_html( $def['row_label'] ); ?></button>
			</div>
			<div class="spp-v2-repeater__rows">
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php self::row( $name, $def, $index, is_array( $row ) ? $row : array(), $depth ); ?>
				<?php endforeach; ?>
			</div>
			<template class="spp-v2-repeater__template"><?php self::row( $name, $def, $token, array(), $depth ); ?></template>
		</div>
		<?php
	}

	private static function row( $name, $def, $index, $row, $depth ) {
		$prefix = $name . '[' . $index . ']';
		?>
		<article class="spp-v2-repeater__row" data-prefix="<?php echo esc_attr( $prefix ); ?>">
			<header class="spp-v2-repeater__rowbar">
				<button type="button" class="spp-v2-repeater__grip" aria-label="جابه‌جایی">⋮⋮</button>
				<strong class="spp-v2-repeater__rowtitle"><?php echo esc_html( $def['row_label'] ); ?></strong>
				<span class="spp-v2-repeater__actions">
					<button type="button" class="button-link spp-v2-repeater__duplicate">تکثیر</button>
					<button type="button" class="button-link spp-v2-repeater__collapse" aria-expanded="true">جمع کردن</button>
					<button type="button" class="button-link-delete spp-v2-repeater__remove">حذف</button>
				</span>
			</header>
			<div class="spp-v2-repeater__body">
				<?php self::render( $prefix, $def['fields'], $row, $depth + 1 ); ?>
			</div>
		</article>
		<?php
	}
}
