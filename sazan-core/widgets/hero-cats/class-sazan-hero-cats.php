<?php
/**
 * ویجت «هیرو قاب‌دار سازان» (sazan-hero-cats).
 *
 * الهام‌گرفته از طرح فروشگاهی برند AEG:
 *   - اسلایدر تصویری داخل یک قاب ارگانیک با بریدگی (notch) برای سرتیتر و برچسب.
 *   - چیپ‌های دسته‌بندی شناور روی گوشه‌ی بالا-چپ تصویر.
 *   - سرتیتر بالا-راست داخل بریدگی سفید قاب.
 *   - واترمارک برند در مرکز + نشان دایره‌ای «اسکرول» با متن چرخان.
 *   - برچسب پایین قاب («پربازدیدترین دسته‌بندی‌ها»).
 *   - ردیف کارت‌های دسته‌بندی زیر قاب (آیکن + عنوان).
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

class Hero_Cats extends Widget_Base {

	public function get_name() { return 'sazan-hero-cats'; }
	public function get_title() { return esc_html__( 'هیرو قاب‌دار سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-image-box'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'hero', 'هیرو', 'دسته', 'دسته بندی', 'category', 'slider', 'اسلایدر', 'بنر', 'frame', 'قاب' ); }

	protected function register_controls() {

		/* ---------------- قاب و تصویر ---------------- */
		$this->start_controls_section( 'sec_frame', array( 'label' => esc_html__( 'قاب و تصویر', 'sazan-core' ) ) );

		$this->add_control( 'surround_bg', array(
			'label'   => esc_html__( 'رنگ پس‌زمینه اطراف قاب', 'sazan-core' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#ffffff',
			'description' => esc_html__( 'رنگ بریدگی‌های سفید (سرتیتر و برچسب) هم از این رنگ گرفته می‌شود.', 'sazan-core' ),
			'selectors' => array( '{{WRAPPER}} .sazan-heroframe' => '--szhf-bg: {{VALUE}};' ),
		) );
		$this->add_control( 'accent', array(
			'label'   => esc_html__( 'رنگ نارنجی برند', 'sazan-core' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#e8770e',
			'selectors' => array( '{{WRAPPER}} .sazan-heroframe' => '--szhf-accent: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'frame_radius', array(
			'label'      => esc_html__( 'گردی گوشه‌ی قاب', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 10, 'max' => 80 ) ),
			'default'    => array( 'unit' => 'px', 'size' => 46 ),
			'selectors'  => array( '{{WRAPPER}} .sazan-heroframe' => '--szhf-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'frame_h', array(
			'label'      => esc_html__( 'ارتفاع قاب', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array( 'px' => array( 'min' => 340, 'max' => 900 ), 'vh' => array( 'min' => 40, 'max' => 95 ) ),
			'default'        => array( 'unit' => 'px', 'size' => 620 ),
			'tablet_default' => array( 'unit' => 'px', 'size' => 480 ),
			'mobile_default' => array( 'unit' => 'px', 'size' => 360 ),
			'selectors'  => array( '{{WRAPPER}} .szhf-frame' => '--szhf-h: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'img_fit', array(
			'label'   => esc_html__( 'نحوه‌ی نمایش تصویر', 'sazan-core' ),
			'type'    => Controls_Manager::SELECT, 'default' => 'cover',
			'options' => array(
				'cover'   => esc_html__( 'پرکردن کامل (Cover)', 'sazan-core' ),
				'contain' => esc_html__( 'نمایش کامل تصویر (Contain)', 'sazan-core' ),
			),
			'selectors' => array( '{{WRAPPER}} .szhf-slide img' => 'object-fit: {{VALUE}};' ),
		) );
		$this->add_control( 'overlay', array(
			'label'   => esc_html__( 'رنگ روکش روی تصویر', 'sazan-core' ),
			'type'    => Controls_Manager::COLOR,
			'default' => 'rgba(24,15,8,0.42)',
			'description' => esc_html__( 'برای خواناتر شدن متن روی تصویر یک لایه‌ی تیره/گرم اضافه می‌کند.', 'sazan-core' ),
			'selectors' => array( '{{WRAPPER}} .szhf-overlay' => 'background: linear-gradient(180deg, rgba(232,119,14,.10), {{VALUE}});' ),
		) );
		$this->end_controls_section();

		/* ---------------- اسلایدها ---------------- */
		$this->start_controls_section( 'sec_slides', array( 'label' => esc_html__( 'تصاویر اسلایدر', 'sazan-core' ) ) );
		$rs = new Repeater();
		$rs->add_control( 'image', array(
			'label'   => esc_html__( 'تصویر', 'sazan-core' ),
			'type'    => Controls_Manager::MEDIA,
			'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
		) );
		$rs->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'slides', array(
			'label'       => esc_html__( 'تصاویر', 'sazan-core' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rs->get_controls(),
			'title_field' => esc_html__( 'اسلاید', 'sazan-core' ),
			'default'     => array( array(), array(), array() ),
		) );
		$this->end_controls_section();

		/* ---------------- سرتیتر (بالا-راست) ---------------- */
		$this->start_controls_section( 'sec_heading', array( 'label' => esc_html__( 'سرتیتر (بالا-راست)', 'sazan-core' ) ) );
		$this->add_control( 'show_heading', array( 'label' => esc_html__( 'نمایش سرتیتر', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'h_line1', array( 'label' => esc_html__( 'خط اول', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'وقتی کیفیت مهمه', 'sazan-core' ), 'condition' => array( 'show_heading' => 'yes' ) ) );
		$this->add_control( 'h_brand', array( 'label' => esc_html__( 'واژه‌ی نارنجی خط دوم', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'سازان', 'sazan-core' ), 'condition' => array( 'show_heading' => 'yes' ) ) );
		$this->add_control( 'h_line2', array( 'label' => esc_html__( 'ادامه‌ی خط دوم', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'انتخاب توئه ...', 'sazan-core' ), 'condition' => array( 'show_heading' => 'yes' ) ) );
		$this->add_control( 'h_color', array(
			'label' => esc_html__( 'رنگ متن سرتیتر', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#2a3640',
			'condition' => array( 'show_heading' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .szhf-heading h3' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'hl_bg', array(
			'label' => esc_html__( 'رنگ پس‌زمینه‌ی هایلایت خط دوم', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#fbe7d6',
			'condition' => array( 'show_heading' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .szhf-hl' => 'background: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ---------------- چیپ‌های دسته‌بندی (روی تصویر) ---------------- */
		$this->start_controls_section( 'sec_chips', array( 'label' => esc_html__( 'چیپ‌های روی تصویر', 'sazan-core' ) ) );
		$this->add_control( 'show_chips', array( 'label' => esc_html__( 'نمایش چیپ‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$rc = new Repeater();
		$rc->add_control( 'text', array( 'label' => esc_html__( 'متن', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ساخت و ساز', 'sazan-core' ) ) );
		$rc->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'chips', array(
			'label' => esc_html__( 'چیپ‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rc->get_controls(), 'title_field' => '{{{ text }}}',
			'condition' => array( 'show_chips' => 'yes' ),
			'default' => array(
				array( 'text' => 'برندسازی شخصی' ), array( 'text' => 'فروش و مذاکره' ), array( 'text' => 'بیزنس' ), array( 'text' => 'سخنرانی' ),
				array( 'text' => 'مارکتینگ' ), array( 'text' => 'کوچینگ' ), array( 'text' => 'رهبری' ), array( 'text' => 'ذهنیت' ),
			),
		) );
		$this->end_controls_section();

		/* ---------------- نشان مرکزی ---------------- */
		$this->start_controls_section( 'sec_center', array( 'label' => esc_html__( 'نشان مرکزی', 'sazan-core' ) ) );
		$this->add_control( 'show_watermark', array( 'label' => esc_html__( 'نمایش واترمارک برند', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'watermark', array( 'label' => esc_html__( 'متن واترمارک', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'SAZAN', 'condition' => array( 'show_watermark' => 'yes' ) ) );
		$this->add_control( 'show_scroll', array( 'label' => esc_html__( 'نمایش نشان اسکرول', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'ring_text', array( 'label' => esc_html__( 'متن چرخان دور نشان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'SAZAN ACADEMY ~ SAZAN ACADEMY ~ ', 'condition' => array( 'show_scroll' => 'yes' ), 'description' => esc_html__( 'ترجیحاً انگلیسی/لاتین تا دور دایره درست بچرخد.', 'sazan-core' ) ) );
		$this->add_control( 'scroll_target', array( 'label' => esc_html__( 'مقصد اسکرول (لینک یا #id)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'placeholder' => '#products', 'condition' => array( 'show_scroll' => 'yes' ), 'description' => esc_html__( 'با کلیک روی نشان، صفحه به این بخش اسکرول می‌شود.', 'sazan-core' ) ) );
		$this->end_controls_section();

		/* ---------------- برچسب پایین قاب ---------------- */
		$this->start_controls_section( 'sec_tab', array( 'label' => esc_html__( 'برچسب پایین قاب', 'sazan-core' ) ) );
		$this->add_control( 'show_tab', array( 'label' => esc_html__( 'نمایش برچسب', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'tab_text', array( 'label' => esc_html__( 'متن برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'پربازدیدترین دسته بندی ها', 'sazan-core' ), 'condition' => array( 'show_tab' => 'yes' ) ) );
		$this->end_controls_section();

		/* ---------------- دسته‌بندی‌ها (زیر قاب) ---------------- */
		$this->start_controls_section( 'sec_cats', array( 'label' => esc_html__( 'دسته‌بندی‌ها (زیر قاب)', 'sazan-core' ) ) );
		$rcat = new Repeater();
		$rcat->add_control( 'icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-paint-roller', 'library' => 'fa-solid' ) ) );
		$rcat->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دسته بندی', 'sazan-core' ) ) );
		$rcat->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'cats', array(
			'label' => esc_html__( 'دسته‌بندی‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rcat->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array(
				array( 'title' => 'برندسازی',      'icon' => array( 'value' => 'fas fa-bullhorn', 'library' => 'fa-solid' ) ),
				array( 'title' => 'فروش و مذاکره', 'icon' => array( 'value' => 'fas fa-handshake', 'library' => 'fa-solid' ) ),
				array( 'title' => 'بیزنس',          'icon' => array( 'value' => 'fas fa-briefcase', 'library' => 'fa-solid' ) ),
				array( 'title' => 'سخنرانی',        'icon' => array( 'value' => 'fas fa-microphone', 'library' => 'fa-solid' ) ),
				array( 'title' => 'مارکتینگ',       'icon' => array( 'value' => 'fas fa-chart-line', 'library' => 'fa-solid' ) ),
				array( 'title' => 'رهبری',          'icon' => array( 'value' => 'fas fa-users', 'library' => 'fa-solid' ) ),
				array( 'title' => 'ذهنیت',          'icon' => array( 'value' => 'fas fa-brain', 'library' => 'fa-solid' ) ),
			),
		) );
		$this->add_responsive_control( 'cat_cols', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::NUMBER,
			'default' => 7, 'tablet_default' => 4, 'mobile_default' => 3, 'min' => 2, 'max' => 8,
			'selectors' => array( '{{WRAPPER}} .szhf-cats' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->add_responsive_control( 'cat_gap', array(
			'label' => esc_html__( 'فاصله بین کارت‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 6, 'max' => 40 ) ),
			'default' => array( 'unit' => 'px', 'size' => 18 ),
			'selectors' => array( '{{WRAPPER}} .szhf-cats' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ---------------- تنظیمات اسلایدر ---------------- */
		$this->start_controls_section( 'sec_slider', array( 'label' => esc_html__( 'تنظیمات اسلایدر', 'sazan-core' ) ) );
		$this->add_control( 'autoplay', array( 'label' => esc_html__( 'پخش خودکار', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'autoplay_ms', array(
			'label' => esc_html__( 'مدت هر اسلاید (ثانیه)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 's' ), 'range' => array( 's' => array( 'min' => 2, 'max' => 15, 'step' => 0.5 ) ),
			'default' => array( 'unit' => 's', 'size' => 5 ), 'condition' => array( 'autoplay' => 'yes' ),
		) );
		$this->add_control( 'show_dots', array( 'label' => esc_html__( 'نمایش نقطه‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->end_controls_section();
	}

	/** ساخت تگ باز/بسته‌ی لینک؛ اگر لینک خالی بود از تگ ساده استفاده می‌شود. */
	private function link_open( $link, $cls, $fallback_tag = 'div' ) {
		$url = ! empty( $link['url'] ) ? $link['url'] : '';
		if ( '' === $url || '#' === $url ) {
			return array( '<' . $fallback_tag . ' class="' . esc_attr( $cls ) . '">', '</' . $fallback_tag . '>' );
		}
		$target   = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
		$nofollow = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';
		return array( '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '"' . $target . $nofollow . '>', '</' . ( $fallback_tag === 'span' ? 'span' : 'a' ) . '>' );
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$uid = 'szhf-' . $this->get_id();

		$slides = array_values( array_filter( (array) ( $s['slides'] ?? array() ), function( $sl ) {
			return ! empty( $sl['image']['url'] );
		} ) );
		$auto  = ( 'yes' === $s['autoplay'] ) ? '1' : '0';
		$ms    = ! empty( $s['autoplay_ms']['size'] ) ? (float) $s['autoplay_ms']['size'] : 5;
		$ms    = max( 2, $ms ) * 1000;
		$multi = count( $slides ) > 1;

		echo '<div class="sazan-heroframe">';
		echo '<div class="szhf-stage">';
		echo '<div class="szhf-frame">';

		/* ---- اسلایدر تصویری ---- */
		echo '<div class="szhf-slider" data-autoplay="' . esc_attr( $auto ) . '" data-speed="' . esc_attr( (int) $ms ) . '">';
		echo '<div class="szhf-viewport"><div class="szhf-track">';
		if ( empty( $slides ) ) {
			echo '<div class="szhf-slide"><div class="szhf-empty">' . esc_html__( 'تصویر', 'sazan-core' ) . '</div></div>';
		} else {
			foreach ( $slides as $sl ) {
				echo '<div class="szhf-slide">';
				list( $open, $end ) = $this->link_open( $sl['link'] ?? array(), 'szhf-slide-in' );
				echo $open;
				echo '<img src="' . esc_url( $sl['image']['url'] ) . '" alt="" loading="lazy">';
				echo $end;
				echo '</div>';
			}
		}
		echo '</div></div>'; // track + viewport
		echo '<div class="szhf-overlay" aria-hidden="true"></div>';

		if ( 'yes' === $s['show_dots'] && $multi ) {
			echo '<div class="szhf-dots">';
			foreach ( $slides as $i => $sl ) {
				echo '<button type="button" class="szhf-dot' . ( 0 === $i ? ' active' : '' ) . '" aria-label="' . esc_attr( $i + 1 ) . '"></button>';
			}
			echo '</div>';
		}
		echo '</div>'; // slider

		/* ---- چیپ‌های روی تصویر (بالا-چپ) ---- */
		if ( 'yes' === $s['show_chips'] && ! empty( $s['chips'] ) ) {
			echo '<div class="szhf-chips">';
			foreach ( (array) $s['chips'] as $c ) {
				list( $open, $end ) = $this->link_open( $c['link'] ?? array(), 'szhf-chip' );
				echo $open . esc_html( $c['text'] ?? '' ) . $end;
			}
			echo '</div>';
		}

		/* ---- نشان مرکزی: واترمارک + اسکرول ---- */
		if ( 'yes' === $s['show_watermark'] || 'yes' === $s['show_scroll'] ) {
			echo '<div class="szhf-center" aria-hidden="false">';
			if ( 'yes' === $s['show_watermark'] && ! empty( $s['watermark'] ) ) {
				echo '<div class="szhf-watermark">' . esc_html( $s['watermark'] ) . '</div>';
			}
			if ( 'yes' === $s['show_scroll'] ) {
				$target = trim( (string) ( $s['scroll_target'] ?? '' ) );
				$ring   = (string) ( $s['ring_text'] ?? '' );
				$path_id = $uid . '-ring';
				$tag = $target ? 'a' : 'button';
				$attr = $target ? ' href="' . esc_url( $target ) . '" data-szhf-scroll="1"' : ' type="button"';
				echo '<' . $tag . ' class="szhf-scroll"' . $attr . ' aria-label="' . esc_attr__( 'اسکرول به پایین', 'sazan-core' ) . '">';
				if ( '' !== trim( $ring ) ) {
					echo '<svg class="szhf-ring" viewBox="0 0 120 120">';
					echo '<defs><path id="' . esc_attr( $path_id ) . '" d="M60,60 m-47,0 a47,47 0 1,1 94,0 a47,47 0 1,1 -94,0"/></defs>';
					echo '<circle cx="60" cy="60" r="55"/>';
					echo '<text><textPath href="#' . esc_attr( $path_id ) . '" xlink:href="#' . esc_attr( $path_id ) . '">' . esc_html( $ring ) . '</textPath></text>';
					echo '</svg>';
				}
				echo '<span class="szhf-scroll-disc"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M6 13l6 6 6-6"/></svg></span>';
				echo '</' . $tag . '>';
			}
			echo '</div>';
		}

		/* ---- سرتیتر بالا-راست (بریدگی سفید) ---- */
		if ( 'yes' === $s['show_heading'] ) {
			echo '<div class="szhf-heading">';
			if ( ! empty( $s['h_line1'] ) ) {
				echo '<h3>' . esc_html( $s['h_line1'] ) . '</h3>';
			}
			if ( ! empty( $s['h_brand'] ) || ! empty( $s['h_line2'] ) ) {
				echo '<div class="szhf-hl">';
				if ( ! empty( $s['h_brand'] ) ) { echo '<b>' . esc_html( $s['h_brand'] ) . '</b> '; }
				echo esc_html( $s['h_line2'] ?? '' );
				echo '</div>';
			}
			echo '</div>';
		}

		/* ---- برچسب پایین قاب ---- */
		if ( 'yes' === $s['show_tab'] && ! empty( $s['tab_text'] ) ) {
			echo '<div class="szhf-tab"><span>' . esc_html( $s['tab_text'] ) . '</span></div>';
		}

		echo '</div>'; // frame
		echo '</div>'; // stage

		/* ---- ردیف دسته‌بندی‌ها ---- */
		if ( ! empty( $s['cats'] ) ) {
			echo '<div class="szhf-cats">';
			foreach ( (array) $s['cats'] as $cat ) {
				list( $open, $end ) = $this->link_open( $cat['link'] ?? array(), 'szhf-cat' );
				echo $open;
				echo '<span class="szhf-cat-ic">';
				if ( ! empty( $cat['icon']['value'] ) ) {
					Icons_Manager::render_icon( $cat['icon'], array( 'aria-hidden' => 'true' ) );
				}
				echo '</span>';
				echo '<span class="szhf-cat-tx">' . esc_html( $cat['title'] ?? '' ) . '</span>';
				echo $end;
			}
			echo '</div>';
		}

		echo '</div>'; // sazan-heroframe
	}
}
