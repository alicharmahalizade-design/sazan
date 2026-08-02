<?php
/**
 * ویجت «هدر موبایل سازان» — نوار بالای شیشه‌ای مخصوص موبایل با سه جایگاهِ
 * قابل‌جابه‌جایی (راست/وسط/چپ) و کشوی حرفه‌ایِ بلور از سمت راست/چپ.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mobile_Header extends Widget_Base {

	public function get_name() { return 'sazan-mobile-header'; }
	public function get_title() { return esc_html__( 'هدر موبایل سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-header'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'mobile', 'موبایل', 'header', 'هدر', 'drawer', 'کشو', 'menu', 'منو', 'glass', 'شیشه' ); }

	private function get_menus_options() {
		$menus = wp_get_nav_menus();
		if ( empty( $menus ) ) { return array( '0' => esc_html__( '— ابتدا یک منو بسازید —', 'sazan-core' ) ); }
		$out = array( '0' => esc_html__( '— انتخاب منو —', 'sazan-core' ) );
		foreach ( $menus as $m ) { $out[ $m->slug ] = $m->name; }
		return $out;
	}

	private function slot_options() {
		return array(
			'menu'    => esc_html__( 'دکمه منو', 'sazan-core' ),
			'logo'    => esc_html__( 'لوگو', 'sazan-core' ),
			'contact' => esc_html__( 'آیکن تماس', 'sazan-core' ),
			'search'  => esc_html__( 'جستجو', 'sazan-core' ),
			'cart'    => esc_html__( 'سبد خرید', 'sazan-core' ),
			'account' => esc_html__( 'حساب کاربری', 'sazan-core' ),
			'none'    => esc_html__( 'خالی', 'sazan-core' ),
		);
	}

	protected function register_controls() {

		/* ---------- چیدمان (قابل جابه‌جایی) ---------- */
		$this->start_controls_section( 'sec_layout', array( 'label' => esc_html__( 'چیدمان نوار (جابه‌جایی)', 'sazan-core' ) ) );
		$this->add_control( 'slot_right',  array( 'label' => esc_html__( 'سمت راست', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'menu', 'options' => $this->slot_options() ) );
		$this->add_control( 'slot_center', array( 'label' => esc_html__( 'وسط', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'logo', 'options' => $this->slot_options() ) );
		$this->add_control( 'slot_left',   array( 'label' => esc_html__( 'سمت چپ', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'contact', 'options' => $this->slot_options() ) );
		$this->end_controls_section();

		/* ---------- عناصر ---------- */
		$this->start_controls_section( 'sec_elements', array( 'label' => esc_html__( 'عناصر', 'sazan-core' ) ) );
		$this->add_control( 'logo_image', array( 'label' => esc_html__( 'تصویر لوگو', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$this->add_control( 'logo_text', array( 'label' => esc_html__( 'متن لوگو (اگر تصویر نبود)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'SAZAN' ) );
		$this->add_control( 'logo_link', array( 'label' => esc_html__( 'لینک لوگو', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'contact_phone', array( 'label' => esc_html__( 'شماره تماس', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'separator' => 'before' ) );
		$this->add_control( 'cart_link', array( 'label' => esc_html__( 'لینک سبد', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'account_link', array( 'label' => esc_html__( 'لینک حساب', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->end_controls_section();

		/* ---------- کشوی منو ---------- */
		$this->start_controls_section( 'sec_drawer', array( 'label' => esc_html__( 'کشوی منو (Drawer)', 'sazan-core' ) ) );
		$this->add_control( 'drawer_side', array( 'label' => esc_html__( 'باز شدن از سمت', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'left', 'options' => array( 'right' => esc_html__( 'راست', 'sazan-core' ), 'left' => esc_html__( 'چپ', 'sazan-core' ) ) ) );
		$this->add_control( 'drawer_menu', array( 'label' => esc_html__( 'منوی کشو', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'drawer_accordion', array( 'label' => esc_html__( 'حالت آکاردئونی (زیرمنوها با کلیک باز شوند)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'drawer_show_search', array( 'label' => esc_html__( 'نمایش جستجو در کشو', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'search_placeholder', array( 'label' => esc_html__( 'راهنمای جستجو', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'جستجو…', 'sazan-core' ), 'condition' => array( 'drawer_show_search' => 'yes' ) ) );
		$this->add_control( 'search_source', array( 'label' => esc_html__( 'منبع جستجو', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'any', 'condition' => array( 'drawer_show_search' => 'yes' ),
			'options' => array( 'any' => esc_html__( 'همه', 'sazan-core' ), 'post' => esc_html__( 'نوشته‌ها', 'sazan-core' ), 'product' => esc_html__( 'محصولات', 'sazan-core' ), 'sazan_podcast' => esc_html__( 'پادکست‌ها', 'sazan-core' ) ) ) );
		$this->add_control( 'search_count', array( 'label' => esc_html__( 'تعداد نتایج', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 6, 'condition' => array( 'drawer_show_search' => 'yes' ) ) );
		$this->add_control( 'drawer_show_contact', array( 'label' => esc_html__( 'نمایش دکمه تماس در کشو', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->end_controls_section();

		/* ---------- رفتار ---------- */
		$this->start_controls_section( 'sec_behavior', array( 'label' => esc_html__( 'رفتار و نمایش', 'sazan-core' ) ) );
		$this->add_control( 'sticky', array( 'label' => esc_html__( 'چسبان بالای صفحه', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'breakpoint', array( 'label' => esc_html__( 'نمایش تا این عرض (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 480, 'max' => 1366 ) ), 'default' => array( 'size' => 1024 ),
			'description' => esc_html__( 'این هدر فقط در موبایل/تبلت (کوچک‌تر از این عرض) دیده می‌شود.', 'sazan-core' ) ) );
		$this->end_controls_section();

		/* ---------- استایل نوار ---------- */
		$this->start_controls_section( 'sec_style_bar', array( 'label' => esc_html__( 'استایل نوار', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'skin', array( 'label' => esc_html__( 'طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'glass', 'options' => array( 'glass' => esc_html__( 'شیشه‌ای (بلوردار)', 'sazan-core' ), 'solid' => esc_html__( 'یک‌دست', 'sazan-core' ) ) ) );
		$this->add_control( 'bar_bg', array( 'label' => esc_html__( 'رنگ پس‌زمینه نوار', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '' ) );
		$this->add_control( 'bar_blur', array( 'label' => esc_html__( 'شدت بلور نوار (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 18 ), 'condition' => array( 'skin' => 'glass' ) ) );
		$this->add_control( 'bar_height', array( 'label' => esc_html__( 'ارتفاع نوار (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 48, 'max' => 110 ) ), 'default' => array( 'size' => 64 ) ) );
		$this->add_control( 'icon_color', array( 'label' => esc_html__( 'رنگ آیکن‌ها', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#eaf6fc' ) );
		$this->add_control( 'accent', array( 'label' => esc_html__( 'رنگ تأکید', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1' ) );
		$this->add_control( 'logo_height', array( 'label' => esc_html__( 'ارتفاع لوگو (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 18, 'max' => 70 ) ), 'default' => array( 'size' => 34 ) ) );
		$this->end_controls_section();

		/* ---------- استایل کشو ---------- */
		$this->start_controls_section( 'sec_style_drawer', array( 'label' => esc_html__( 'استایل کشو', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'drawer_width', array( 'label' => esc_html__( 'عرض کشو (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 220, 'max' => 520 ) ), 'default' => array( 'size' => 320 ) ) );
		$this->add_control( 'drawer_height', array( 'label' => esc_html__( 'ارتفاع کشو (% صفحه)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 40, 'max' => 100 ) ), 'default' => array( 'size' => 100 ) ) );
		$this->add_control( 'drawer_radius', array( 'label' => esc_html__( 'گردی گوشه‌های کشو (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 0 ) ) );
		$this->add_control( 'drawer_bg', array( 'label' => esc_html__( 'رنگ پس‌زمینه کشو', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '' ) );
		$this->add_control( 'drawer_blur', array( 'label' => esc_html__( 'شدت بلور کشو (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 22 ) ) );
		$this->add_control( 'backdrop_dim', array( 'label' => esc_html__( 'تیرگی پس‌زمینه (%)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 90 ) ), 'default' => array( 'size' => 55 ) ) );
		$this->end_controls_section();
	}

	/* ===================== رندر ===================== */

	protected function render() {
		$s = $this->get_settings_for_display();

		$bp      = isset( $s['breakpoint']['size'] ) ? (int) $s['breakpoint']['size'] : 1024;
		$sticky  = '1'; // چسبانی اجباری و همیشه‌روشن (مستقل از سوییچ، تا کشِ کنترل‌های المنتور مشکلی ایجاد نکند)
		$glass   = ( 'solid' === $s['skin'] ) ? false : true;
		$side    = ( 'left' === $s['drawer_side'] ) ? 'left' : 'right';
		$dim     = isset( $s['backdrop_dim']['size'] ) ? max( 0, min( 90, (int) $s['backdrop_dim']['size'] ) ) / 100 : 0.55;

		$bh   = isset( $s['bar_height']['size'] ) ? (int) $s['bar_height']['size'] : 64;
		$bblur= isset( $s['bar_blur']['size'] ) ? (int) $s['bar_blur']['size'] : 18;
		$lh   = isset( $s['logo_height']['size'] ) ? (int) $s['logo_height']['size'] : 34;
		$dw   = isset( $s['drawer_width']['size'] ) ? (int) $s['drawer_width']['size'] : 320;
		$dhh  = isset( $s['drawer_height']['size'] ) ? (int) $s['drawer_height']['size'] : 100;
		$drad = isset( $s['drawer_radius']['size'] ) ? (int) $s['drawer_radius']['size'] : 0;
		$dblur= isset( $s['drawer_blur']['size'] ) ? (int) $s['drawer_blur']['size'] : 22;

		$barvars  = '--mh-icon:' . esc_attr( $s['icon_color'] ) . ';--mh-accent:' . esc_attr( $s['accent'] ) . ';--mh-h:' . $bh . 'px;--mh-blur:' . $bblur . 'px;--mh-logo-h:' . $lh . 'px;';
		if ( ! empty( $s['bar_bg'] ) ) { $barvars .= '--mh-bg:' . esc_attr( $s['bar_bg'] ) . ';'; }

		// متغیرهای کشو را روی خودِ کشو می‌گذاریم تا بعد از انتقال به لایه‌ی برش هم درست بماند
		$dvars  = '--mh-icon:' . esc_attr( $s['icon_color'] ) . ';--mh-accent:' . esc_attr( $s['accent'] ) . ';--mh-dw:' . $dw . 'px;--mh-dh:' . $dhh . 'vh;--mh-drad:' . $drad . 'px;--mh-dblur:' . $dblur . 'px;';
		if ( ! empty( $s['drawer_bg'] ) ) { $dvars .= '--mh-dbg:' . esc_attr( $s['drawer_bg'] ) . ';'; }

		$classes = 'sazan-header sazan-mhead' . ( $glass ? ' mhead-glass' : ' mhead-solid' ) . ( '1' === $sticky ? ' mhead-sticky' : '' );
		$mid     = 'mh-' . $this->get_id();

		// CSS حیاتیِ این‌لاین: از همان اولین رنگ‌آمیزی اعمال می‌شود (حتی قبل از لود فایل CSS)،
		// تا کشو بیرون نزند، اسکرول افقی نسازد و فلش ندهد.
		$crit  = 'html,body{overflow-x:clip!important}';
		$crit .= '@media(min-width:' . ( (int) $bp + 1 ) . 'px){[data-mh="' . $mid . '"]{display:none!important}}';
		$crit .= '.sazan-mhead-drawer[data-mh="' . $mid . '"]:not(.is-open){position:fixed;top:0;height:100vh;visibility:hidden;opacity:0}';
		$crit .= '.sazan-mhead-drawer.mhead-side-left[data-mh="' . $mid . '"]:not(.is-open){left:0;transform:translateX(-100%)}';
		$crit .= '.sazan-mhead-drawer.mhead-side-right[data-mh="' . $mid . '"]:not(.is-open){right:0;transform:translateX(100%)}';
		$crit .= '.sazan-mhead-backdrop[data-mh="' . $mid . '"]:not(.is-open){position:fixed;inset:0;opacity:0;visibility:hidden}';
		echo '<style>' . $crit . '</style>';

		printf(
			'<div class="%1$s" style="%2$s" data-breakpoint="%3$d" data-sticky="%4$s" data-side="%5$s" data-mh="%6$s">',
			esc_attr( $classes ), $barvars, $bp, esc_attr( $sticky ), esc_attr( $side ), esc_attr( $mid )
		);

		echo '<div class="sazan-mhead-bar">';
		echo '<div class="sazan-mhead-slot slot-right">' . $this->slot( $s['slot_right'], $s ) . '</div>';
		echo '<div class="sazan-mhead-slot slot-center">' . $this->slot( $s['slot_center'], $s ) . '</div>';
		echo '<div class="sazan-mhead-slot slot-left">' . $this->slot( $s['slot_left'], $s ) . '</div>';
		echo '</div>';

		// بک‌گراند + کشو
		echo '<div class="sazan-mhead-backdrop" aria-hidden="true" data-mh="' . esc_attr( $mid ) . '" style="--mh-dim:' . esc_attr( $dim ) . '"></div>';
		echo '<aside class="sazan-mhead-drawer mhead-side-' . esc_attr( $side ) . '" data-mh="' . esc_attr( $mid ) . '" style="' . $dvars . '">';
		echo '<div class="sazan-mhead-drawer-head">';
		echo '<span class="sazan-mhead-drawer-logo">' . $this->logo_markup( $s ) . '</span>';
		echo '<button type="button" class="sazan-mhead-close" aria-label="close">&times;</button>';
		echo '</div>';

		if ( 'yes' === $s['drawer_show_search'] ) {
			echo '<div class="sazan-mhead-search" data-source="' . esc_attr( $s['search_source'] ) . '" data-count="' . esc_attr( (int) $s['search_count'] ) . '">';
			echo '<input type="search" class="sazan-mhead-search-input" placeholder="' . esc_attr( $s['search_placeholder'] ) . '" autocomplete="off">';
			echo '<div class="sazan-mhead-search-results"></div></div>';
		}

		$acc = ( 'yes' === $s['drawer_accordion'] ) ? '1' : '0';
		echo '<nav class="sazan-mhead-menu' . ( '1' === $acc ? ' mhead-accordion' : '' ) . '" data-accordion="' . esc_attr( $acc ) . '">';
		if ( ! empty( $s['drawer_menu'] ) && '0' !== $s['drawer_menu'] ) {
			wp_nav_menu( array( 'menu' => $s['drawer_menu'], 'container' => false, 'menu_class' => 'sazan-menu', 'fallback_cb' => false, 'echo' => true ) );
		} elseif ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			echo '<span class="sazan-header__menu-empty">' . esc_html__( 'منویی برای کشو انتخاب نشده است', 'sazan-core' ) . '</span>';
		}
		echo '</nav>';

		if ( 'yes' === $s['drawer_show_contact'] && ! empty( $s['contact_phone'] ) ) {
			$tel = preg_replace( '/[^0-9+]/', '', (string) $s['contact_phone'] );
			echo '<a class="sazan-mhead-callbtn" href="tel:' . esc_attr( $tel ) . '">' . $this->svg( 'phone' ) . '<span>' . esc_html( $s['contact_phone'] ) . '</span></a>';
		}

		echo '</aside>';
		echo '</div>'; // mhead
	}

	private function slot( $type, $s ) {
		switch ( $type ) {
			case 'menu':
				return '<button type="button" class="sazan-mhead-btn sazan-mhead-toggle" data-drawer="open" aria-label="menu">' . $this->svg( 'menu' ) . '</button>';
			case 'search':
				return '<button type="button" class="sazan-mhead-btn sazan-mhead-toggle" data-drawer="open" data-focus="1" aria-label="search">' . $this->svg( 'search' ) . '</button>';
			case 'logo':
				return '<span class="sazan-mhead-logo">' . $this->logo_markup( $s ) . '</span>';
			case 'contact':
				$tel = preg_replace( '/[^0-9+]/', '', (string) $s['contact_phone'] );
				return '<a class="sazan-mhead-btn" href="tel:' . esc_attr( $tel ) . '" aria-label="call">' . $this->svg( 'phone' ) . '</a>';
			case 'cart':
				$href = ! empty( $s['cart_link']['url'] ) ? $s['cart_link']['url'] : '#';
				$cc   = (int) $this->cart_count();
				$bdg  = ( $cc > 0 ) ? '<span class="sazan-mhead-badge">' . esc_html( $cc ) . '</span>' : '';
				return '<a class="sazan-mhead-btn" href="' . esc_url( $href ) . '" aria-label="cart"><span class="sazan-mhead-ic">' . $this->svg( 'cart' ) . $bdg . '</span></a>';
			case 'account':
				$href = ! empty( $s['account_link']['url'] ) ? $s['account_link']['url'] : '#';
				return '<a class="sazan-mhead-btn" href="' . esc_url( $href ) . '" aria-label="account">' . $this->svg( 'user' ) . '</a>';
		}
		return '<span class="sazan-mhead-empty"></span>';
	}

	private function logo_markup( $s ) {
		$href = ! empty( $s['logo_link']['url'] ) ? $s['logo_link']['url'] : home_url( '/' );
		$inner = ! empty( $s['logo_image']['url'] )
			? '<img src="' . esc_url( $s['logo_image']['url'] ) . '" alt="' . esc_attr( $s['logo_text'] ) . '">'
			: '<span class="sazan-mhead-logo-text">' . esc_html( $s['logo_text'] ) . '</span>';
		return '<a href="' . esc_url( $href ) . '">' . $inner . '</a>';
	}

	private function cart_count() {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();
			if ( $wc && isset( $wc->cart ) && $wc->cart ) { return (string) $wc->cart->get_cart_contents_count(); }
		}
		return '0';
	}

	private function svg( $name ) {
		$a = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
		switch ( $name ) {
			case 'menu':   return $a . '<path d="M4 7h16M4 12h16M4 17h16"/></svg>';
			case 'search': return $a . '<circle cx="11" cy="11" r="6.5"/><path d="M20.5 20.5l-4.2-4.2"/></svg>';
			case 'phone':  return $a . '<path d="M21.5 16.4v2.6a2 2 0 0 1-2.2 2 19.6 19.6 0 0 1-8.5-3 19.3 19.3 0 0 1-6-6 19.6 19.6 0 0 1-3-8.6A2 2 0 0 1 3.8 1.5h2.6a2 2 0 0 1 2 1.7c.1.9.4 1.7.7 2.5a2 2 0 0 1-.5 2.1L7.5 9a16 16 0 0 0 6 6l1.2-1.1a2 2 0 0 1 2.1-.5c.8.3 1.6.5 2.5.7a2 2 0 0 1 1.7 2z"/></svg>';
			case 'user':   return $a . '<circle cx="12" cy="8" r="3.6"/><path d="M5 20c.6-3.6 3.4-5.6 7-5.6S18.4 16.4 19 20"/></svg>';
			case 'cart':   return $a . '<circle cx="9.5" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/><path d="M2.5 3.5H5l2.2 11a1.4 1.4 0 0 0 1.4 1.1h8a1.4 1.4 0 0 0 1.4-1.1L21 7.5H6"/></svg>';
		}
		return $a . '</svg>';
	}
}
