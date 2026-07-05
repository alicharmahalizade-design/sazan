<?php
/**
 * مینی‌کارتِ سفارشیِ سازان.
 *
 * به‌جای فراخوانی woocommerce_mini_cart() (که قالبِ آن توسط پوسته
 * بازنویسی می‌شود و خروجی غیرقابل‌پیش‌بینی می‌دهد) مارک‌آپِ ثابت و
 * تمیزِ خودمان را تولید می‌کنیم تا استایلِ افزونه کامل اعمال شود.
 * با ثبتِ fragment، پس از افزودن/حذفِ ایجکسی هم همین مارک‌آپ به‌روز می‌شود.
 *
 * @package Sazan
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mini_Cart {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// به‌روزرسانیِ ایجکسیِ محتوای مینی‌کارت با مارک‌آپِ ما.
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragment' ) );
	}

	public function cart_fragment( $fragments ) {
		ob_start();
		echo '<div class="widget_shopping_cart_content">';
		self::render();
		echo '</div>';
		$fragments['div.widget_shopping_cart_content'] = ob_get_clean();
		return $fragments;
	}

	/**
	 * رندرِ محتوای مینی‌کارت (داخلِ .widget_shopping_cart_content صدا زده می‌شود).
	 */
	public static function render() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			echo '<p class="sazan-header__cart-empty">' . esc_html__( 'سبد خرید شما خالی است.', 'sazan-core' ) . '</p>';
			return;
		}

		$cart  = WC()->cart;
		$items = $cart->get_cart();

		if ( empty( $items ) ) {
			echo '<p class="sazan-header__cart-empty">' . esc_html__( 'سبد خرید شما خالی است.', 'sazan-core' ) . '</p>';
			return;
		}

		echo '<ul class="sazan-mc">';
		foreach ( $items as $key => $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;
			if ( ! $product || ! $product->exists() || (int) $item['quantity'] <= 0 ) {
				continue;
			}
			if ( ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) {
				continue;
			}

			$name      = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $item, $key );
			$permalink = $product->is_visible() ? $product->get_permalink( $item ) : '';
			$thumb     = $product->get_image( array( 56, 56 ) );
			$qty       = (int) $item['quantity'];
			$line      = $cart->get_product_subtotal( $product, $qty );
			$remove    = wc_get_cart_remove_url( $key );

			echo '<li class="sazan-mc__item">';

			printf(
				'<a href="%1$s" class="sazan-mc__remove remove remove_from_cart_button" aria-label="%2$s" data-product_id="%3$s" data-cart_item_key="%4$s" data-product_sku="%5$s">&times;</a>',
				esc_url( $remove ),
				esc_attr__( 'حذف از سبد', 'sazan-core' ),
				esc_attr( $product->get_id() ),
				esc_attr( $key ),
				esc_attr( $product->get_sku() )
			);

			$tag   = $permalink ? 'a' : 'span';
			$hattr = $permalink ? ' href="' . esc_url( $permalink ) . '"' : '';
			echo '<' . $tag . ' class="sazan-mc__link"' . $hattr . '>';
			echo '<span class="sazan-mc__thumb">' . $thumb . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<span class="sazan-mc__body">';
			echo '<span class="sazan-mc__title">' . wp_kses_post( $name ) . '</span>';
			echo '<span class="sazan-mc__meta">';
			echo '<span class="sazan-mc__qty">' . sprintf( esc_html__( 'تعداد: %d', 'sazan-core' ), $qty ) . '</span>';
			echo '<span class="sazan-mc__price">' . wp_kses_post( $line ) . '</span>';
			echo '</span>';
			echo '</span>';
			echo '</' . $tag . '>';

			echo '</li>';
		}
		echo '</ul>';

		echo '<div class="sazan-mc__foot">';
		echo '<div class="sazan-mc__total"><span>' . esc_html__( 'مجموع', 'sazan-core' ) . '</span><span class="sazan-mc__total-val">' . wp_kses_post( $cart->get_cart_subtotal() ) . '</span></div>';
		echo '<div class="sazan-mc__btns">';
		printf( '<a href="%1$s" class="sazan-mc__btn sazan-mc__btn--ghost">%2$s</a>', esc_url( wc_get_cart_url() ), esc_html__( 'سبد خرید', 'sazan-core' ) );
		printf( '<a href="%1$s" class="sazan-mc__btn sazan-mc__btn--cta checkout">%2$s</a>', esc_url( wc_get_checkout_url() ), esc_html__( 'ثبت سفارش', 'sazan-core' ) );
		echo '</div>';
		echo '</div>';
	}
}
