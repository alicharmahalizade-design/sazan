<?php
/**
 * ویجت «نوار موبایل سازان» — نوار پایینِ ثابت و شیشه‌ای مخصوص موبایل
 * با آیکن‌های قابل‌تنظیم (خانه/جستجو/حساب/سبد/منو/تماس/واتساپ/بالا)،
 * نشان تعداد سبد، دکمه‌ی برجسته‌ی وسط، و نمایش هوشمند هنگام اسکرول.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mobile_Bar extends Widget_Base {

	public function get_name() { return 'sazan-mobile-bar'; }
	public function get_title() { return esc_html__( 'نوار موبایل سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-smartphone'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'mobile', 'موبایل', 'bottom', 'bar', 'نوار', 'tab', 'sticky', 'چسبان', 'glass', 'شیشه' ); }

	private function get_menus_options() {
		$menus = wp_get_nav_menus();
		if ( empty( $menus ) ) {
			return array( '0' => esc_html__( '— ابتدا یک منو بسازید —', 'sazan-core' ) );
		}
		$out = array( '0' => esc_html__( '— انتخاب منو —', 'sazan-core' ) );
		foreach ( $menus as $m ) { $out[ $m->slug ] = $m->name; }
		return $out;
	}

	private function type_options() {
		return array(
			'home'     => esc_html__( 'خانه', 'sazan-core' ),
			'link'     => esc_html__( 'لینک دلخواه', 'sazan-core' ),
			'search'   => esc_html__( 'جستجو', 'sazan-core' ),
			'menu'     => esc_html__( 'منو (کشویی)', 'sazan-core' ),
			'account'  => esc_html__( 'حساب کاربری', 'sazan-core' ),
			'cart'     => esc_html__( 'سبد خرید', 'sazan-core' ),
			'call'     => esc_html__( 'تماس', 'sazan-core' ),
			'whatsapp' => esc_html__( 'واتساپ', 'sazan-core' ),
			'scrolltop'=> esc_html__( 'برو بالا', 'sazan-core' ),
		);
	}

	protected function register_controls() {

		/* ---------- آیتم‌ها ---------- */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'آیتم‌های نوار', 'sazan-core' ) ) );

		$rep = new Repeater();
		$rep->add_control( 'item_type', array( 'label' => esc_html__( 'نوع', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'link', 'options' => $this->type_options() ) );
		$rep->add_control( 'item_label', array( 'label' => esc_html__( 'برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$rep->add_control( 'item_icon', array( 'label' => esc_html__( 'آیکن دلخواه (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$rep->add_control( 'item_url', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ),
			'condition' => array( 'item_type' => array( 'home', 'link', 'account', 'cart' ) ) ) );
		$rep->add_control( 'item_phone', array( 'label' => esc_html__( 'شماره (با کد کشور برای واتساپ)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '',
			'condition' => array( 'item_type' => array( 'call', 'whatsapp' ) ) ) );
		$rep->add_control( 'item_highlight', array( 'label' => esc_html__( 'دکمه‌ی برجسته‌ی وسط', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );

		$this->add_control( 'items', array(
			'type' => Controls_Manager::REPEATER, 'fields' => $rep->get_controls(),
			'title_field' => '{{{ item_label || item_type }}}',
			'default' => array(
				array( 'item_type' => 'home',    'item_label' => esc_html__( 'خانه', 'sazan-core' ) ),
				array( 'item_type' => 'search',  'item_label' => esc_html__( 'جستجو', 'sazan-core' ) ),
				array( 'item_type' => 'cart',    'item_label' => esc_html__( 'سبد', 'sazan-core' ), 'item_highlight' => 'yes' ),
				array( 'item_type' => 'account', 'item_label' => esc_html__( 'حساب', 'sazan-core' ) ),
				array( 'item_type' => 'menu',    'item_label' => esc_html__( 'منو', 'sazan-core' ) ),
			),
		) );

		$this->add_control( 'show_labels', array( 'label' => esc_html__( 'نمایش برچسب زیر آیکن‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'drawer_menu', array( 'label' => esc_html__( 'منوی آیتمِ «منو»', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'account_menu', array( 'label' => esc_html__( 'منوی آیتمِ «حساب»', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'account_link', array( 'label' => esc_html__( 'لینک ورود (اگر منوی حساب خالی بود)', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'search_placeholder', array( 'label' => esc_html__( 'راهنمای جستجو', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'جستجو…', 'sazan-core' ) ) );
		$this->add_control( 'search_source', array( 'label' => esc_html__( 'منبع جستجو', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'any',
			'options' => array( 'any' => esc_html__( 'همه', 'sazan-core' ), 'post' => esc_html__( 'نوشته‌ها', 'sazan-core' ), 'product' => esc_html__( 'محصولات', 'sazan-core' ), 'sazan_podcast' => esc_html__( 'پادکست‌ها', 'sazan-core' ) ) ) );
		$this->add_control( 'search_count', array( 'label' => esc_html__( 'تعداد نتایج', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 6 ) );
		$this->end_controls_section();

		/* ---------- رفتار ---------- */
		$this->start_controls_section( 'sec_behavior', array( 'label' => esc_html__( 'رفتار و نمایش', 'sazan-core' ) ) );
		$this->add_control( 'breakpoint', array(
			'label' => esc_html__( 'نمایش تا این عرض (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 480, 'max' => 1366 ) ), 'default' => array( 'size' => 1024 ),
			'description' => esc_html__( 'نوار فقط در عرض‌های کوچک‌تر از این مقدار (موبایل/تبلت) دیده می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'position', array( 'label' => esc_html__( 'محل نوار', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'bottom',
			'options' => array( 'bottom' => esc_html__( 'پایین صفحه', 'sazan-core' ), 'top' => esc_html__( 'بالای صفحه', 'sazan-core' ) ) ) );
		$this->add_control( 'floating', array( 'label' => esc_html__( 'حالت شناور (با فاصله از لبه)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->add_control( 'smart', array( 'label' => esc_html__( 'مخفی هنگام اسکرول به پایین', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->end_controls_section();

		/* ---------- پنل بازشو (Popup/Drawer) ---------- */
		$this->start_controls_section( 'sec_panel', array( 'label' => esc_html__( 'پنل بازشو (جستجو/سبد/حساب/منو)', 'sazan-core' ) ) );
		$this->add_control( 'open_mode', array(
			'label' => esc_html__( 'حالت بازشدن', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'popup',
			'options' => array(
				'popup' => esc_html__( 'پاپ‌آپ وسط صفحه', 'sazan-core' ),
				'sheet' => esc_html__( 'کشویی هم‌اندازه‌ی نوار', 'sazan-core' ),
			),
		) );
		$this->add_control( 'backdrop_dim', array(
			'label' => esc_html__( 'تیرگی پس‌زمینه هنگام باز شدن (%)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 90 ) ), 'default' => array( 'size' => 55 ),
		) );
		$this->add_control( 'panel_gap', array(
			'label' => esc_html__( 'فاصله‌ی پنل از نوار/وسط (بالاتر بیاید) px', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 300 ) ), 'default' => array( 'size' => 14 ),
			'description' => esc_html__( 'در حالت کشویی، پنل را از نوار بالاتر می‌برد؛ در حالت پاپ‌آپ، کمی بالاتر از مرکز می‌آورد.', 'sazan-core' ),
		) );
		$this->add_control( 'panel_maxh', array(
			'label' => esc_html__( 'حداکثر ارتفاع پنل (% صفحه)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 40, 'max' => 92 ) ), 'default' => array( 'size' => 78 ),
		) );
		$this->add_control( 'panel_blur', array(
			'label' => esc_html__( 'میزان بلور پنل (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 22 ),
		) );
		$this->end_controls_section();

		/* ---------- استایل ---------- */
		$this->start_controls_section( 'sec_style', array( 'label' => esc_html__( 'استایل', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'skin', array( 'label' => esc_html__( 'طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'glass',
			'options' => array( 'glass' => esc_html__( 'شیشه‌ای (بلوردار)', 'sazan-core' ), 'solid' => esc_html__( 'یک‌دست (تخت)', 'sazan-core' ) ) ) );
		$this->add_control( 'c_bg', array( 'label' => esc_html__( 'رنگ پس‌زمینه', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '' ) );
		$this->add_control( 'c_icon', array( 'label' => esc_html__( 'رنگ آیکن/متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#cfe9f5' ) );
		$this->add_control( 'c_active', array( 'label' => esc_html__( 'رنگ فعال/برجسته/نشان', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1' ) );
		$this->add_control( 'blur', array( 'label' => esc_html__( 'شدت بلور شیشه (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 20 ), 'condition' => array( 'skin' => 'glass' ) ) );
		$this->add_control( 'radius', array( 'label' => esc_html__( 'گردی گوشه‌ها (حالت شناور)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 36 ) ), 'default' => array( 'size' => 20 ) ) );
		$this->end_controls_section();
	}

	/* ===================== رندر ===================== */

	protected function render() {
		$s = $this->get_settings_for_display();
		$items = is_array( $s['items'] ) ? $s['items'] : array();

		$bp     = isset( $s['breakpoint']['size'] ) ? (int) $s['breakpoint']['size'] : 1024;
		$pos    = ( 'top' === $s['position'] ) ? 'top' : 'bottom';
		$float  = ( 'yes' === $s['floating'] ) ? '1' : '0';
		$smart  = ( 'yes' === $s['smart'] ) ? '1' : '0';
		$glass  = ( 'solid' === $s['skin'] ) ? false : true;
		$blur   = isset( $s['blur']['size'] ) ? (int) $s['blur']['size'] : 20;
		$radius = isset( $s['radius']['size'] ) ? (int) $s['radius']['size'] : 20;

		$vars  = '--mbar-icon:' . esc_attr( $s['c_icon'] ) . ';--mbar-active:' . esc_attr( $s['c_active'] ) . ';--mbar-radius:' . $radius . 'px;--mbar-blur:' . $blur . 'px;';
		if ( ! empty( $s['c_bg'] ) ) { $vars .= '--mbar-bg:' . esc_attr( $s['c_bg'] ) . ';'; }
		$dim = isset( $s['backdrop_dim']['size'] ) ? max( 0, min( 90, (int) $s['backdrop_dim']['size'] ) ) / 100 : 0.55;
		$vars .= '--mbar-dim:' . $dim . ';';
		$mode = ( 'sheet' === $s['open_mode'] ) ? 'sheet' : 'popup';

		$classes = 'sazan-header sazan-mbar mbar-' . $pos . ( $glass ? ' mbar-glass' : ' mbar-solid' ) . ( '1' === $float ? ' mbar-float' : '' ) . ' mbar-mode-' . $mode;
		$show_labels = ( 'yes' === $s['show_labels'] );
		$mbid = 'mb-' . $this->get_id();

		// ضدِ فلش: بالاتر از بریک‌پوینت همان لحظه مخفی شود
		echo '<style>@media(min-width:' . ( (int) $bp + 1 ) . 'px){[data-mb="' . esc_attr( $mbid ) . '"]{display:none!important}}</style>';

		printf(
			'<div class="%1$s" style="%2$s" data-breakpoint="%3$d" data-pos="%4$s" data-smart="%5$s" data-float="%6$s" data-mode="%7$s" data-mb="%8$s">',
			esc_attr( $classes ), $vars, $bp, esc_attr( $pos ), esc_attr( $smart ), esc_attr( $float ), esc_attr( $mode ), esc_attr( $mbid )
		);

		$panel_types = array(); // نوع‌هایی که پنل لازم دارند
		echo '<div class="sazan-mbar-row">';
		foreach ( $items as $it ) {
			$type  = isset( $it['item_type'] ) ? $it['item_type'] : 'link';
			$label = ( $show_labels && ! empty( $it['item_label'] ) ) ? '<span class="sazan-mbar-label">' . esc_html( $it['item_label'] ) . '</span>' : '';
			$hl    = ( 'yes' === ( isset( $it['item_highlight'] ) ? $it['item_highlight'] : '' ) ) ? ' sazan-mbar-item--highlight' : '';
			$icon  = $this->item_icon( $type, isset( $it['item_icon'] ) ? $it['item_icon'] : null );

			// آیتم‌هایی که پنل باز می‌کنند
			if ( in_array( $type, array( 'search', 'cart', 'account', 'menu' ), true ) ) {
				$panel_types[ $type ] = true;
				$badge = ( 'cart' === $type ) ? '<span class="sazan-mbar-badge">' . esc_html( $this->cart_count() ) . '</span>' : '';
				echo '<button type="button" class="sazan-mbar-item sazan-mbar-trigger' . $hl . '" data-open="' . esc_attr( $type ) . '"><span class="sazan-mbar-ic">' . $icon . $badge . '</span>' . $label . '</button>';
				continue;
			}
			if ( 'scrolltop' === $type ) {
				echo '<button type="button" class="sazan-mbar-item sazan-mbar-top' . $hl . '"><span class="sazan-mbar-ic">' . $icon . '</span>' . $label . '</button>';
				continue;
			}

			// انواع لینکی (مستقیم)
			if ( 'call' === $type ) {
				$href = 'tel:' . preg_replace( '/[^0-9+]/', '', (string) ( $it['item_phone'] ?? '' ) );
			} elseif ( 'whatsapp' === $type ) {
				$href = 'https://wa.me/' . preg_replace( '/[^0-9]/', '', (string) ( $it['item_phone'] ?? '' ) );
			} elseif ( 'home' === $type ) {
				$href = ! empty( $it['item_url']['url'] ) ? $it['item_url']['url'] : home_url( '/' );
			} else {
				$href = ! empty( $it['item_url']['url'] ) ? $it['item_url']['url'] : '#';
			}
			echo '<a class="sazan-mbar-item' . $hl . '" href="' . esc_url( $href ) . '" data-href="' . esc_url( $href ) . '"><span class="sazan-mbar-ic">' . $icon . '</span>' . $label . '</a>';
		}
		echo '</div>'; // row

		// بک‌گراندِ تیره + پنل‌ها
		if ( ! empty( $panel_types ) ) {
			$gap   = isset( $s['panel_gap']['size'] ) ? (int) $s['panel_gap']['size'] : 14;
			$maxh  = isset( $s['panel_maxh']['size'] ) ? (int) $s['panel_maxh']['size'] : 78;
			$pvars = '--mbar-radius:' . $radius . 'px;--mbar-active:' . esc_attr( $s['c_active'] ) . ';--mbar-maxh:' . $maxh . 'vh;--mbar-gap:' . $gap . 'px;';
			$pvars .= '--mbar-pblur:' . ( isset( $s['panel_blur']['size'] ) ? (int) $s['panel_blur']['size'] : 22 ) . 'px;';
			$pclasses = 'sazan-mbar-panels mbar-mode-' . $mode . ' mbar-pos-' . $pos . ( $glass ? ' mbar-glass' : ' mbar-solid' );

			echo '<div class="sazan-mbar-backdrop" aria-hidden="true" style="--mbar-dim:' . esc_attr( $dim ) . '" data-mb="' . esc_attr( $mbid ) . '"></div>';
			echo '<div class="' . esc_attr( $pclasses ) . '" style="' . $pvars . '" data-gap="' . esc_attr( $gap ) . '" data-mb="' . esc_attr( $mbid ) . '">';

			if ( isset( $panel_types['search'] ) ) {
				echo '<div class="sazan-mbar-panel" data-panel="search">';
				echo $this->panel_head( esc_html__( 'جستجو', 'sazan-core' ) );
				echo '<div class="sazan-mbar-panel-body"><div class="sazan-mbar-search" data-source="' . esc_attr( $s['search_source'] ) . '" data-count="' . esc_attr( (int) $s['search_count'] ) . '">';
				echo '<input type="search" class="sazan-mbar-search-input" placeholder="' . esc_attr( $s['search_placeholder'] ) . '" autocomplete="off">';
				echo '<div class="sazan-mbar-search-results"></div></div></div></div>';
			}
			if ( isset( $panel_types['cart'] ) ) {
				echo '<div class="sazan-mbar-panel" data-panel="cart">';
				echo $this->panel_head( esc_html__( 'سبد خرید', 'sazan-core' ) );
				echo '<div class="sazan-mbar-panel-body">';
				if ( function_exists( 'woocommerce_mini_cart' ) ) {
					echo '<div class="widget_shopping_cart_content">'; woocommerce_mini_cart(); echo '</div>';
				} else {
					echo '<p class="sazan-mbar-empty">' . esc_html__( 'سبد خرید شما خالی است.', 'sazan-core' ) . '</p>';
				}
				echo '</div></div>';
			}
			if ( isset( $panel_types['account'] ) ) {
				echo '<div class="sazan-mbar-panel" data-panel="account">';
				echo $this->panel_head( esc_html__( 'حساب کاربری', 'sazan-core' ) );
				echo '<div class="sazan-mbar-panel-body"><nav class="sazan-header__nav sazan-mbar-menu">';
				if ( ! empty( $s['account_menu'] ) && '0' !== $s['account_menu'] ) {
					wp_nav_menu( array( 'menu' => $s['account_menu'], 'container' => false, 'menu_class' => 'sazan-menu', 'fallback_cb' => false, 'echo' => true ) );
				} else {
					$al = ! empty( $s['account_link']['url'] ) ? $s['account_link']['url'] : wp_login_url();
					echo '<a class="sazan-mbar-loginlink" href="' . esc_url( $al ) . '">' . esc_html__( 'ورود / ثبت‌نام', 'sazan-core' ) . '</a>';
				}
				echo '</nav></div></div>';
			}
			if ( isset( $panel_types['menu'] ) ) {
				echo '<div class="sazan-mbar-panel" data-panel="menu">';
				echo $this->panel_head( esc_html__( 'منو', 'sazan-core' ) );
				echo '<div class="sazan-mbar-panel-body"><nav class="sazan-header__nav sazan-mbar-menu">';
				if ( ! empty( $s['drawer_menu'] ) && '0' !== $s['drawer_menu'] ) {
					wp_nav_menu( array( 'menu' => $s['drawer_menu'], 'container' => false, 'menu_class' => 'sazan-menu', 'fallback_cb' => false, 'echo' => true ) );
				} elseif ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					echo '<span class="sazan-header__menu-empty">' . esc_html__( 'منویی انتخاب نشده است', 'sazan-core' ) . '</span>';
				}
				echo '</nav></div></div>';
			}

			echo '</div>'; // panels
		}

		echo '</div>'; // mbar
	}

	private function panel_head( $title ) {
		return '<div class="sazan-mbar-panel-head"><span class="sazan-mbar-panel-title">' . esc_html( $title ) . '</span><button type="button" class="sazan-mbar-panel-close" aria-label="close">&times;</button></div>';
	}

	private function cart_count() {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();
			if ( $wc && isset( $wc->cart ) && $wc->cart ) { return (string) $wc->cart->get_cart_contents_count(); }
		}
		return '0';
	}

	private function item_icon( $type, $custom ) {
		if ( is_array( $custom ) && ! empty( $custom['value'] ) ) {
			ob_start();
			Icons_Manager::render_icon( $custom, array( 'aria-hidden' => 'true' ) );
			return ob_get_clean();
		}
		return $this->svg( $type );
	}

	private function svg( $name ) {
		$a = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
		switch ( $name ) {
			case 'home':      return $a . '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/></svg>';
			case 'search':    return $a . '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>';
			case 'menu':      return $a . '<path d="M3 6h18M3 12h18M3 18h18"/></svg>';
			case 'account':   return $a . '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>';
			case 'cart':      return $a . '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.3a1.5 1.5 0 0 0 1.5 1.2h8.2a1.5 1.5 0 0 0 1.5-1.2L22 7H6"/></svg>';
			case 'call':      return $a . '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.8a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>';
			case 'whatsapp':  return $a . '<path d="M20 12a8 8 0 0 1-11.8 7L4 20l1.1-4A8 8 0 1 1 20 12z"/><path d="M8.5 9.5c0 3 2 5 5 5"/></svg>';
			case 'scrolltop': return $a . '<path d="M12 19V5"/><path d="M6 11l6-6 6 6"/></svg>';
		}
		return $a . '<circle cx="12" cy="12" r="3"/></svg>';
	}
}
