<?php
/**
 * ویجت «نوار چسبان سازان» — ردیف دومِ هدر (دسته‌بندی/منو/سرچ/حساب/سبد)
 * که به‌صورت مستقل ساخته می‌شود و فقط هنگام اسکرول ظاهر می‌گردد.
 *
 * از کلاس‌های .sazan-header__* استفاده می‌کند تا استایل و تعاملات
 * (دراپ‌داون دسته‌بندی، جستجوی ایجکسی، مینی‌کارت) مثل هدر اصلی کار کنند.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sticky_Bar extends Widget_Base {

	public function get_name() { return 'sazan-sticky-bar'; }
	public function get_title() { return esc_html__( 'نوار چسبان سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-anchor'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'sticky', 'چسبان', 'bar', 'نوار', 'header', 'هدر', 'scroll', 'اسکرول' ); }

	private function get_menus_options() {
		$menus = wp_get_nav_menus();
		if ( empty( $menus ) ) {
			return array( '0' => esc_html__( '— ابتدا یک منو در «نمایش > منوها» بسازید —', 'sazan-core' ) );
		}
		$out = array( '0' => esc_html__( '— انتخاب منو —', 'sazan-core' ) );
		foreach ( $menus as $m ) { $out[ $m->slug ] = $m->name; }
		return $out;
	}

	protected function register_controls() {

		/* ---------- محتوا ---------- */
		$this->start_controls_section( 'sec_content', array( 'label' => esc_html__( 'محتوای نوار', 'sazan-core' ) ) );

		$this->add_control( 'cats_show', array( 'label' => esc_html__( 'نمایش دکمه دسته‌بندی‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'cats_text', array( 'label' => esc_html__( 'متن دسته‌بندی‌ها', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دسته‌بندی‌ها', 'sazan-core' ), 'condition' => array( 'cats_show' => 'yes' ) ) );
		$this->add_control( 'cats_menu', array( 'label' => esc_html__( 'منوی دسته‌بندی‌ها', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0', 'condition' => array( 'cats_show' => 'yes' ) ) );
		$this->add_control( 'cats_accordion', array( 'label' => esc_html__( 'حالت آکاردئونی (زیرمنوها با کلیک باز شوند)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'cats_show' => 'yes' ) ) );

		$this->add_control( 'nav_menu', array( 'label' => esc_html__( 'منوی اصلی', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0', 'separator' => 'before' ) );

		$this->add_control( 'search_show', array( 'label' => esc_html__( 'نمایش جستجو', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'search_placeholder', array( 'label' => esc_html__( 'متن راهنمای جستجو', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'جستجو…', 'sazan-core' ), 'condition' => array( 'search_show' => 'yes' ) ) );
		$this->add_control( 'search_source', array( 'label' => esc_html__( 'منبع جستجو', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'any', 'condition' => array( 'search_show' => 'yes' ),
			'options' => array(
				'any'           => esc_html__( 'همه', 'sazan-core' ),
				'post'          => esc_html__( 'نوشته‌ها', 'sazan-core' ),
				'product'       => esc_html__( 'محصولات', 'sazan-core' ),
				'sazan_podcast' => esc_html__( 'پادکست‌ها', 'sazan-core' ),
			),
		) );
		$this->add_control( 'search_count', array( 'label' => esc_html__( 'تعداد نتایج', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 6, 'condition' => array( 'search_show' => 'yes' ) ) );

		$this->add_control( 'acc_show', array( 'label' => esc_html__( 'نمایش حساب کاربری', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'acc_text', array( 'label' => esc_html__( 'متن (کاربر مهمان)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ورود / ثبت‌نام', 'sazan-core' ), 'condition' => array( 'acc_show' => 'yes' ) ) );
		$this->add_control( 'acc_greeting', array( 'label' => esc_html__( 'متن خوش‌آمد (کاربر واردشده)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'سلام', 'sazan-core' ), 'condition' => array( 'acc_show' => 'yes' ) ) );
		$this->add_control( 'acc_link', array( 'label' => esc_html__( 'لینک ورود/حساب', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ), 'condition' => array( 'acc_show' => 'yes' ) ) );
		$this->add_control( 'acc_menu', array( 'label' => esc_html__( 'منوی بازشوی حساب', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0', 'condition' => array( 'acc_show' => 'yes' ) ) );
		$this->add_control( 'acc_trigger', array( 'label' => esc_html__( 'باز شدن منوی حساب', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'hover', 'options' => array( 'hover' => esc_html__( 'با هاور', 'sazan-core' ), 'click' => esc_html__( 'با کلیک', 'sazan-core' ) ), 'condition' => array( 'acc_show' => 'yes' ) ) );

		$this->add_control( 'cart_show', array( 'label' => esc_html__( 'نمایش سبد خرید', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'cart_link', array( 'label' => esc_html__( 'لینک سبد', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ), 'condition' => array( 'cart_show' => 'yes' ) ) );
		$this->add_control( 'cart_show_count', array( 'label' => esc_html__( 'نمایش تعداد', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'cart_show' => 'yes' ) ) );
		$this->add_control( 'cart_count', array( 'label' => esc_html__( 'تعداد دستی (اگر ووکامرس نبود)', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 0, 'condition' => array( 'cart_show' => 'yes' ) ) );
		$this->add_control( 'cart_dropdown', array( 'label' => esc_html__( 'نمایش مینی‌کارت بازشو', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'cart_show' => 'yes' ) ) );
		$this->add_control( 'cart_trigger', array( 'label' => esc_html__( 'باز شدن مینی‌کارت', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'hover', 'options' => array( 'hover' => esc_html__( 'با هاور', 'sazan-core' ), 'click' => esc_html__( 'با کلیک', 'sazan-core' ) ), 'condition' => array( 'cart_show' => 'yes' ) ) );

		$this->end_controls_section();

		/* ---------- رفتار نمایش ---------- */
		$this->start_controls_section( 'sec_behavior', array( 'label' => esc_html__( 'رفتار نمایش', 'sazan-core' ) ) );
		$this->add_control( 'reveal', array(
			'label' => esc_html__( 'ظاهر شدن بعد از اسکرول (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 1500 ) ), 'default' => array( 'size' => 300 ),
			'description' => esc_html__( 'وقتی کاربر بیشتر از این مقدار اسکرول کند، نوار ظاهر می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'smart', array(
			'label' => esc_html__( 'مخفی هنگام اسکرول به پایین', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => '',
			'description' => esc_html__( 'با اسکرول به پایین پنهان و با اسکرول به بالا ظاهر می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'position', array(
			'label' => esc_html__( 'محل نوار', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'top',
			'options' => array( 'top' => esc_html__( 'بالای صفحه', 'sazan-core' ), 'bottom' => esc_html__( 'پایین صفحه', 'sazan-core' ) ),
		) );
		$this->add_control( 'offset', array(
			'label' => esc_html__( 'فاصله از لبه (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 200 ) ), 'default' => array( 'size' => 0 ),
		) );
		$this->end_controls_section();

		/* ---------- استایل ---------- */
		$this->start_controls_section( 'sec_style', array( 'label' => esc_html__( 'استایل', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'طرح نوار', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (تمام‌عرض)', 'sazan-core' ),
				'glass'   => esc_html__( 'شیشه‌ای (Glass / شناور بلوردار)', 'sazan-core' ),
			),
		) );
		$this->add_control( 'radius', array(
			'label' => esc_html__( 'گردی گوشه‌ها (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 18 ),
		) );
		$this->add_control( 'bar_bg', array( 'label' => esc_html__( 'رنگ پس‌زمینه (طرح کلاسیک)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '' ) );
		$this->add_control( 'bar_width', array(
			'label' => esc_html__( 'حداکثر عرض محتوا (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 600, 'max' => 1920 ) ), 'default' => array( 'size' => 1320 ),
		) );
		$this->add_control( 'bar_scale', array(
			'label' => esc_html__( 'اندازه‌ی کلی نوار (٪) — کوچک‌تر کردن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( '%' => array( 'min' => 60, 'max' => 100 ) ), 'default' => array( 'unit' => '%', 'size' => 100 ),
			'description' => esc_html__( 'برای کوچک‌تر کردن کل نوار چسبان مقدار را کم کنید (فقط طرح شیشه‌ای).', 'sazan-core' ),
			'selectors' => array( '{{WRAPPER}} .sazan-sbar' => '--sbar-scale: calc({{SIZE}} / 100);' ),
		) );
		$this->add_control( 'bar_pad', array(
			'label' => esc_html__( 'فاصله داخلی عمودی (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 10 ),
		) );
		$this->add_control( 'sbar_blur', array(
			'label' => esc_html__( 'میزان بلور (طرح شیشه‌ای و بازشوها) px', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 22 ),
			'selectors' => array(
				'{{WRAPPER}} .sazan-sbar' => '--sz-blur: {{SIZE}}px;',
			),
		) );

		$this->add_control( 'h_icons', array( 'label' => esc_html__( 'آیکن‌ها', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'ic_cat', array( 'label' => esc_html__( 'آیکن دسته‌بندی', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$this->add_control( 'ic_search', array( 'label' => esc_html__( 'آیکن جستجو', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$this->add_control( 'ic_acc', array( 'label' => esc_html__( 'آیکن حساب', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$this->add_control( 'ic_cart', array( 'label' => esc_html__( 'آیکن سبد', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$icon_box = '{{WRAPPER}} .sazan-header__search-toggle, {{WRAPPER}} .sazan-header__acc-icon, {{WRAPPER}} .sazan-header__cart-icon';
		$this->add_control( 'ic_bg', array( 'label' => esc_html__( 'پس‌زمینه آیکن‌ها', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $icon_box => 'background: {{VALUE}} !important;' ) ) );
		$this->add_control( 'ic_color', array( 'label' => esc_html__( 'رنگ آیکن‌ها', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( $icon_box . ', {{WRAPPER}} .sazan-header__cat-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'ic_size', array( 'label' => esc_html__( 'قطر آیکن‌ها (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 28, 'max' => 70 ) ), 'selectors' => array( $icon_box => 'width: {{SIZE}}px; height: {{SIZE}}px;' ) ) );
		$this->add_control( 'ic_radius', array( 'label' => esc_html__( 'گردی آیکن‌ها (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ), 'default' => array( 'size' => 50 ), 'selectors' => array( $icon_box => 'border-radius: {{SIZE}}px;' ) ) );
		$this->add_control( 'h_cartdd', array( 'label' => esc_html__( 'مینی‌کارت بازشو', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'cartdd_color', array( 'label' => esc_html__( 'رنگ متن مینی‌کارت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8f0f4', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-dropdown' => '--cartdd-color: {{VALUE}};' ) ) );
		$this->add_control( 'cartdd_price', array( 'label' => esc_html__( 'رنگ قیمت مینی‌کارت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-dropdown' => '--cartdd-price: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	/* ===================== رندر ===================== */

	protected function render() {
		$s = $this->get_settings_for_display();

		$reveal = isset( $s['reveal']['size'] ) ? (int) $s['reveal']['size'] : 300;
		$offset = isset( $s['offset']['size'] ) ? (int) $s['offset']['size'] : 0;
		$smart  = ( 'yes' === $s['smart'] ) ? '1' : '0';
		$pos    = ( 'bottom' === $s['position'] ) ? 'bottom' : 'top';
		$width  = isset( $s['bar_width']['size'] ) ? (int) $s['bar_width']['size'] : 1320;
		$bg     = ! empty( $s['bar_bg'] ) ? $s['bar_bg'] : '';
		$skin   = ( 'glass' === $s['skin'] ) ? 'glass' : 'classic';
		$radius = isset( $s['radius']['size'] ) ? (int) $s['radius']['size'] : 18;
		$pad    = isset( $s['bar_pad']['size'] ) ? (int) $s['bar_pad']['size'] : 10;
		$blur   = isset( $s['sbar_blur']['size'] ) ? max( 0, (int) $s['sbar_blur']['size'] ) : 22;
		$scale  = isset( $s['bar_scale']['size'] ) ? max( 60, min( 100, (int) $s['bar_scale']['size'] ) ) : 100;
		$ccolor = ! empty( $s['cartdd_color'] ) ? $s['cartdd_color'] : '#e8f0f4';
		$cprice = ! empty( $s['cartdd_price'] ) ? $s['cartdd_price'] : '#00b6f1';
		// چون JS نوار را به <body> منتقل می‌کند، متغیرها را inline می‌گذاریم تا حفظ شوند
		$style  = sprintf( '--sz-blur:%dpx;--sbar-scale:%s;--cartdd-color:%s;--cartdd-price:%s;', $blur, number_format( $scale / 100, 3 ), $ccolor, $cprice );

		printf(
			'<div class="sazan-header sazan-sbar skin-%1$s" data-reveal="%2$d" data-smart="%3$s" data-pos="%4$s" data-offset="%5$d" data-width="%6$d" data-bg="%7$s" data-radius="%8$d" data-pad="%9$d" style="%10$s">',
			esc_attr( $skin ), $reveal, esc_attr( $smart ), esc_attr( $pos ), $offset, $width, esc_attr( $bg ), $radius, $pad, esc_attr( $style )
		);

		echo '<div class="sazan-header__bottom"><div class="sazan-header__bottom-inner">';

		// دسته‌بندی‌ها
		if ( 'yes' === $s['cats_show'] ) {
			echo '<div class="sazan-header__categories"><button type="button" class="sazan-header__cat-btn">';
			echo '<span class="sazan-header__cat-icon">' . $this->picon( $s, 'ic_cat', 'grid' ) . '</span>';
			echo '<span class="sazan-header__cat-text">' . esc_html( $s['cats_text'] ) . '</span>';
			echo '<span class="sazan-header__cat-chev">' . $this->svg( 'chev' ) . '</span>';
			echo '</button>';
			echo '<div class="sazan-header__cat-dropdown' . ( 'yes' === $s['cats_accordion'] ? ' sazan-cat-accordion' : '' ) . '">'; $this->render_menu( $s['cats_menu'] ); echo '</div>';
			echo '</div>';
		}

		// منوی اصلی
		echo '<nav class="sazan-header__nav">'; $this->render_menu( $s['nav_menu'] ); echo '</nav>';

		// اکشن‌ها
		echo '<div class="sazan-header__actions">';
		$this->render_search( $s );
		$this->render_account( $s );
		$this->render_cart( $s );
		echo '</div>';

		echo '</div></div>'; // bottom
		echo '</div>'; // sbar
	}

	private function render_menu( $slug ) {
		if ( empty( $slug ) || '0' === $slug ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<span class="sazan-header__menu-empty">' . esc_html__( 'منویی انتخاب نشده است', 'sazan-core' ) . '</span>';
			}
			return;
		}
		wp_nav_menu( array( 'menu' => $slug, 'container' => false, 'menu_class' => 'sazan-menu', 'fallback_cb' => false, 'echo' => true ) );
	}

	private function render_search( $s ) {
		if ( 'yes' !== $s['search_show'] ) { return; }
		echo '<div class="sazan-header__search">';
		echo '<button type="button" class="sazan-header__search-toggle" aria-label="search">' . $this->picon( $s, 'ic_search', 'search' ) . '</button>';
		echo '<div class="sazan-header__search-panel" data-source="' . esc_attr( $s['search_source'] ) . '" data-count="' . esc_attr( (int) $s['search_count'] ) . '">';
		echo '<form class="sazan-header__search-form" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">';
		echo '<input type="search" name="s" class="sazan-header__search-input" placeholder="' . esc_attr( $s['search_placeholder'] ) . '" autocomplete="off">';
		echo '</form>';
		echo '<div class="sazan-header__search-results"></div>';
		echo '</div></div>';
	}

	private function render_account( $s ) {
		if ( 'yes' !== $s['acc_show'] ) { return; }
		$trigger = ( 'click' === $s['acc_trigger'] ) ? 'click' : 'hover';

		if ( is_user_logged_in() ) {
			$u    = wp_get_current_user();
			$name = $u->display_name ? $u->display_name : $u->user_login;
			$href = ! empty( $s['acc_link']['url'] ) ? $s['acc_link']['url'] : '#';
			echo '<div class="sazan-header__account-wrap" data-trigger="' . esc_attr( $trigger ) . '">';
			echo '<a class="sazan-header__account" href="' . esc_url( $href ) . '">';
			echo '<span class="sazan-header__acc-text">' . esc_html( trim( $s['acc_greeting'] . ' ' . $name ) ) . '</span>';
			echo '<span class="sazan-header__acc-icon">' . $this->picon( $s, 'ic_acc', 'user' ) . '</span>';
			echo '</a>';
			if ( ! empty( $s['acc_menu'] ) && '0' !== $s['acc_menu'] ) {
				echo '<div class="sazan-header__acc-dropdown">'; $this->render_menu( $s['acc_menu'] ); echo '</div>';
			}
			echo '</div>';
		} else {
			$href = ! empty( $s['acc_link']['url'] ) ? $s['acc_link']['url'] : '#';
			echo '<a class="sazan-header__account" href="' . esc_url( $href ) . '">';
			echo '<span class="sazan-header__acc-text">' . esc_html( $s['acc_text'] ) . '</span>';
			echo '<span class="sazan-header__acc-icon">' . $this->picon( $s, 'ic_acc', 'user' ) . '</span>';
			echo '</a>';
		}
	}

	private function render_cart( $s ) {
		if ( 'yes' !== $s['cart_show'] ) { return; }
		$href    = ! empty( $s['cart_link']['url'] ) ? $s['cart_link']['url'] : '#';
		$count   = $this->cart_count( (int) $s['cart_count'] );
		$dd      = ( 'yes' === $s['cart_dropdown'] );
		$trigger = ( 'click' === $s['cart_trigger'] ) ? 'click' : 'hover';

		echo '<div class="sazan-header__cart-wrap" data-trigger="' . esc_attr( $trigger ) . '">';
		echo '<a class="sazan-header__cart" href="' . esc_url( $href ) . '">';
		if ( 'yes' === $s['cart_show_count'] ) { echo '<span class="sazan-header__cart-count">' . esc_html( $count ) . '</span>'; }
		echo '<span class="sazan-header__cart-icon">' . $this->picon( $s, 'ic_cart', 'cart' ) . '</span>';
		echo '</a>';
		if ( $dd ) {
			echo '<div class="sazan-header__cart-dropdown">';
			if ( function_exists( 'woocommerce_mini_cart' ) ) {
				echo '<div class="widget_shopping_cart_content">';
				\Sazan\Mini_Cart::render();
				echo '</div>';
			} else {
				echo '<p class="sazan-header__cart-empty">' . esc_html__( 'سبد خرید شما خالی است.', 'sazan-core' ) . '</p>';
			}
			echo '</div>';
		}
		echo '</div>';
	}

	private function cart_count( $fallback ) {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();
			if ( $wc && isset( $wc->cart ) && $wc->cart ) {
				return (string) $wc->cart->get_cart_contents_count();
			}
		}
		return (string) max( 0, (int) $fallback );
	}

	private function picon( $s, $key, $fallback ) {
		if ( ! empty( $s[ $key ]['value'] ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $s[ $key ], array( 'aria-hidden' => 'true' ) );
			return ob_get_clean();
		}
		return $this->svg( $fallback );
	}

	private function svg( $name ) {
		switch ( $name ) {
			case 'search':
				return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>';
			case 'user':
				return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>';
			case 'cart':
				return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="20" r="1.6"/><circle cx="18" cy="20" r="1.6"/><path d="M2 3h3l2.4 12.3a1.5 1.5 0 0 0 1.5 1.2h8.2a1.5 1.5 0 0 0 1.5-1.2L22 7H6"/></svg>';
			case 'grid':
				return '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>';
			case 'chev':
				return '<svg class="sazan-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
		}
		return '';
	}
}
