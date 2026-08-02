<?php
/**
 * ویجت فروشگاه سازان — نمایش محصولات ووکامرس با طرح لاکچری (پوستری/کاروسل).
 * از همان استایل «دوره‌ها — طرح لاکچری» استفاده می‌کند.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shop extends Widget_Base {

	public function get_name() { return 'sazan-shop'; }
	public function get_title() { return esc_html__( 'فروشگاه سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-cart-medium'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'shop', 'فروشگاه', 'محصول', 'woocommerce', 'ووکامرس' ); }

	protected function register_controls() {

		/* سرتیتر */
		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_section_header_controls( 'Shop', esc_html__( 'فروشگاه سازان', 'sazan-core' ) );
		$this->end_controls_section();

		/* منبع محصولات */
		$this->start_controls_section( 'sec_src', array( 'label' => esc_html__( 'محصولات', 'sazan-core' ) ) );

		$this->add_control( 'layout', array(
			'label' => esc_html__( 'چیدمان', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'grid',
			'options' => array(
				'grid'     => esc_html__( 'گرید (برای آرشیو)', 'sazan-core' ),
				'carousel' => esc_html__( 'کاروسل', 'sazan-core' ),
			),
		) );
		$this->add_control( 'source', array(
			'label' => esc_html__( 'منبع', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'archive',
			'options' => array(
				'archive' => esc_html__( 'محصولات همین صفحه‌ی آرشیو', 'sazan-core' ),
				'custom'  => esc_html__( 'انتخاب دستی (با تنظیمات زیر)', 'sazan-core' ),
			),
			'description' => esc_html__( 'حالت «آرشیو» محصولات فروشگاه/دسته/برچسبِ همین صفحه را با صفحه‌بندی نشان می‌دهد. اگر صفحه آرشیو نباشد، خودکار به حالت دستی برمی‌گردد.', 'sazan-core' ),
		) );

		$this->add_control( 'count', array(
			'label' => esc_html__( 'تعداد محصول', 'sazan-core' ), 'type' => Controls_Manager::NUMBER,
			'default' => 8, 'min' => 1, 'max' => 30,
			'condition' => array( 'source' => 'custom' ),
		) );
		$this->add_control( 'orderby', array(
			'label' => esc_html__( 'ترتیب', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'date',
			'options' => array(
				'date'       => esc_html__( 'جدیدترین', 'sazan-core' ),
				'popularity' => esc_html__( 'پرفروش‌ترین', 'sazan-core' ),
				'rating'     => esc_html__( 'بیشترین امتیاز', 'sazan-core' ),
				'price'      => esc_html__( 'ارزان‌ترین', 'sazan-core' ),
				'price-desc' => esc_html__( 'گران‌ترین', 'sazan-core' ),
				'rand'       => esc_html__( 'تصادفی', 'sazan-core' ),
			),
			'condition' => array( 'source' => 'custom' ),
		) );
		$this->add_control( 'cats', array(
			'label'       => esc_html__( 'دسته‌های محصول', 'sazan-core' ),
			'type'        => Controls_Manager::SELECT2, 'multiple' => true,
			'options'     => $this->get_cat_options(),
			'description' => esc_html__( 'خالی = همه‌ی دسته‌ها.', 'sazan-core' ),
			'condition'   => array( 'source' => 'custom' ),
		) );
		$this->add_control( 'on_sale', array(
			'label' => esc_html__( 'فقط محصولات تخفیف‌دار', 'sazan-core' ),
			'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes',
			'condition' => array( 'source' => 'custom' ),
		) );
		$this->add_control( 'show_filters', array(
			'label' => esc_html__( 'نمایش فیلتر دسته‌ها', 'sazan-core' ),
			'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes',
		) );
		$this->add_control( 'buy_text', array(
			'label' => esc_html__( 'متن دکمه خرید', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'افزودن به سبد', 'sazan-core' ),
		) );

		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '3', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array(
				'{{WRAPPER}} .sazan-course-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);',
				'{{WRAPPER}} .sazan-cc-viewport' => '--sz-cc-cols: {{VALUE}};',
			),
		) );
		$this->end_controls_section();

		$this->add_palette_controls();
	}

	private function get_cat_options() {
		$out = array();
		if ( ! taxonomy_exists( 'product_cat' ) ) { return $out; }
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
		if ( is_wp_error( $terms ) ) { return $out; }
		foreach ( $terms as $t ) { $out[ $t->slug ] = $t->name; }
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="sazan-sec" style="padding:24px;text-align:center;color:#a7bcc8">' .
					esc_html__( 'برای این ویجت باید افزونه‌ی ووکامرس فعال باشد.', 'sazan-core' ) . '</div>';
			}
			return;
		}

		$args = array(
			'status'   => 'publish',
			'limit'    => max( 1, (int) $s['count'] ),
			'orderby'  => 'date',
			'order'    => 'DESC',
		);
		switch ( $s['orderby'] ) {
			case 'price':       $args['orderby'] = 'price'; $args['order'] = 'ASC'; break;
			case 'price-desc':  $args['orderby'] = 'price'; $args['order'] = 'DESC'; break;
			case 'popularity':  $args['orderby'] = 'popularity'; break;
			case 'rating':      $args['orderby'] = 'rating'; break;
			case 'rand':        $args['orderby'] = 'rand'; break;
		}
		if ( ! empty( $s['cats'] ) ) { $args['category'] = (array) $s['cats']; }
		if ( 'yes' === $s['on_sale'] ) { $args['include'] = wc_get_product_ids_on_sale(); }

		// منبع: محصولات همین صفحه‌ی آرشیو (با احترام به کوئری و صفحه‌بندی قالب)
		$use_archive = ( 'archive' === ( $s['source'] ?? 'archive' ) ) && $this->is_product_archive();
		$products    = array();
		$paginate    = false;

		if ( $use_archive ) {
			global $wp_query;
			foreach ( (array) $wp_query->posts as $post_obj ) {
				$prod = wc_get_product( $post_obj );
				if ( $prod ) { $products[] = $prod; }
			}
			$paginate = ( $wp_query->max_num_pages > 1 );
		} else {
			$products = wc_get_products( $args );
		}

		$layout    = ( 'carousel' === ( $s['layout'] ?? 'grid' ) ) ? 'carousel' : 'grid';
		$skin_cls  = ( 'carousel' === $layout ) ? ' skin-lux' : ' skin-lux is-grid';

		echo '<div class="sazan-sec sazan-courses sazan-shop' . esc_attr( $skin_cls ) . '">';
		$this->render_section_header( $s );

		if ( empty( $products ) ) {
			echo '<p style="color:var(--sz-text2);padding:18px">' . esc_html__( 'محصولی برای نمایش یافت نشد.', 'sazan-core' ) . '</p></div>';
			return;
		}

		/* فیلتر دسته‌ها (از روی محصولات نمایش‌داده‌شده) */
		if ( 'yes' === $s['show_filters'] ) {
			$chips = array();
			foreach ( $products as $p ) {
				foreach ( wp_get_post_terms( $p->get_id(), 'product_cat' ) as $t ) {
					$chips[ $t->slug ] = $t->name;
				}
			}
			if ( $chips ) {
				echo '<div class="sazan-filters"><span class="sazan-chip active" data-filter="">' . esc_html__( 'همه', 'sazan-core' ) . '</span>';
				foreach ( $chips as $slug => $name ) {
					printf( '<span class="sazan-chip" data-filter="%s">%s</span>', esc_attr( $slug ), esc_html( $name ) );
				}
				echo '</div>';
			}
		}

		/* ظرف کارت‌ها: گرید یا کاروسل */
		$is_carousel = ( 'carousel' === $layout );
		if ( $is_carousel ) {
			echo '<div class="sazan-course-carousel" data-sz-cc>';
			echo '<button class="sz-cc-arrow prev" type="button" aria-label="' . esc_attr__( 'قبلی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg></button>';
			echo '<div class="sazan-cc-viewport">';
		} else {
			echo '<div class="sazan-course-grid">';
		}

		$buy = $s['buy_text'] ?: esc_html__( 'افزودن به سبد', 'sazan-core' );

		foreach ( $products as $p ) {
			$id    = $p->get_id();
			$terms = wp_get_post_terms( $id, 'product_cat' );
			$cat   = ! empty( $terms ) ? $terms[0]->slug : '';
			$tags  = '';
			foreach ( array_slice( $terms, 0, 2 ) as $t ) { $tags .= '<span>' . esc_html( $t->name ) . '</span>'; }

			$img    = wp_get_attachment_image_url( $p->get_image_id(), 'large' );
			$img    = $img ? '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $p->get_name() ) . '">' : ( function_exists( 'wc_placeholder_img_src' ) ? '<img src="' . esc_url( wc_placeholder_img_src( 'large' ) ) . '" alt="">' : '' );
			$ribbon = $p->is_on_sale() ? esc_html__( 'تخفیف ویژه', 'sazan-core' ) : ( ! empty( $terms ) ? esc_html( $terms[0]->name ) : esc_html__( 'محصول', 'sazan-core' ) );
			$short  = wp_strip_all_tags( $p->get_short_description() ?: $p->get_description() );

			$buy_url = $p->add_to_cart_url();
			$buy_lbl = $p->is_purchasable() && $p->is_in_stock() ? $buy : esc_html__( 'مشاهده محصول', 'sazan-core' );
			if ( ! ( $p->is_purchasable() && $p->is_in_stock() ) ) { $buy_url = get_permalink( $id ); }

			printf(
				'<article class="sazan-course-card" data-cat="%1$s">
					<a class="sazan-course-link" href="%2$s">
						<div class="sazan-course-media">%3$s<div class="ribbon">%4$s</div><div class="tutor">%5$s</div></div>
						<div class="sazan-course-body">
							<div class="sazan-course-tags">%6$s</div>
							<h3>%7$s</h3><p>%8$s</p>
							<div class="sazan-course-meta"><span class="sz-shop-price">%5$s</span></div>
						</div>
					</a>
					<a class="sazan-shop-buy" href="%9$s" data-quantity="1" data-product_id="%10$d" rel="nofollow">%11$s</a>
				</article>',
				esc_attr( $cat ),
				esc_url( get_permalink( $id ) ),
				$img,
				$ribbon,
				wp_kses_post( $p->get_price_html() ),
				$tags,
				esc_html( $p->get_name() ),
				esc_html( wp_trim_words( $short, 18 ) ),
				esc_url( $buy_url ),
				$id,
				esc_html( $buy_lbl )
			);
		}

		if ( $is_carousel ) {
			echo '</div>'; // viewport
			echo '<button class="sz-cc-arrow next" type="button" aria-label="' . esc_attr__( 'بعدی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg></button>';
			echo '<div class="sazan-cc-dots" aria-hidden="true"></div>';
			echo '</div>'; // carousel
		} else {
			echo '</div>'; // grid
			if ( $paginate && function_exists( 'woocommerce_pagination' ) ) {
				echo '<div class="sazan-shop-pagination">';
				woocommerce_pagination();
				echo '</div>';
			}
		}
		echo '</div>'; // sazan-sec
	}

	/** آیا صفحه‌ی فعلی، آرشیو محصولات ووکامرس است؟ */
	private function is_product_archive() {
		if ( function_exists( 'is_shop' ) && is_shop() ) { return true; }
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) { return true; }
		if ( is_post_type_archive( 'product' ) ) { return true; }
		// پیش‌نمایش قالب آرشیو در المنتور
		if ( function_exists( 'is_tax' ) && ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) ) { return true; }
		return false;
	}
}
