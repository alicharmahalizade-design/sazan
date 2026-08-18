<?php
/**
 * ویجت بنر دعوت به اقدام (CTA) — عنوان + توضیح + دکمه + تصویر شخصِ بیرون‌زده از بالا.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CTA extends Widget_Base {

	public function get_name() { return 'sazan-cta'; }
	public function get_title() { return esc_html__( 'بنر دعوت به اقدام سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-call-to-action'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'cta', 'بنر', 'دعوت', 'اقدام', 'banner' ); }

	protected function register_controls() {

		/* طرح */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (ساده)', 'sazan-core' ),
				'pro'     => esc_html__( 'درخشان / حرفه‌ای ✨', 'sazan-core' ),
				'lux'     => esc_html__( 'اولترا / میلیاردی 💎', 'sazan-core' ),
			),
		) );
		$this->add_control( 'img_side', array(
			'label' => esc_html__( 'سمت تصویر', 'sazan-core' ), 'type' => Controls_Manager::CHOOSE, 'default' => 'right',
			'options' => array(
				'right' => array( 'title' => esc_html__( 'راست', 'sazan-core' ), 'icon' => 'eicon-h-align-right' ),
				'left'  => array( 'title' => esc_html__( 'چپ', 'sazan-core' ), 'icon' => 'eicon-h-align-left' ),
			),
		) );
		$this->end_controls_section();

		/* محتوا */
		$this->start_controls_section( 'sec_content', array( 'label' => esc_html__( 'محتوا', 'sazan-core' ) ) );
		$this->add_control( 'title', array(
			'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 2,
			'default' => esc_html__( 'برای انتخاب مسیر مناسب، با مشاوران سازان در ارتباط باشید', 'sazan-core' ),
		) );
		$this->add_control( 'desc', array(
			'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 4,
			'default' => esc_html__( 'مجموعه ای غنی از کارشناسان و متخصصین مجرب در زمینه های فنی و تخصصی را به منظور ارائه خدمات و سرویس به سازمان ها و واحد های تولیدی و خدماتی فراهم آورده است', 'sazan-core' ),
		) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'بیشتر بدانید', 'sazan-core' ) ) );
		$this->add_control( 'link', array( 'label' => esc_html__( 'لینک دکمه', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'image', array( 'label' => esc_html__( 'تصویر شخص (PNG شفاف)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ) ) );
		$this->end_controls_section();

		/* تنظیم تصویر */
		$this->start_controls_section( 'sec_img', array( 'label' => esc_html__( 'تنظیم تصویر', 'sazan-core' ) ) );
		$this->add_responsive_control( 'img_h', array(
			'label' => esc_html__( 'ارتفاع تصویر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 140, 'max' => 460 ) ),
			'default' => array( 'unit' => 'px', 'size' => 300 ),
			'mobile_default' => array( 'unit' => 'px', 'size' => 200 ),
			'selectors' => array( '{{WRAPPER}} .sazan-cta' => '--sc-ph:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'img_w', array(
			'label' => esc_html__( 'عرض ستون تصویر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 120, 'max' => 360 ) ),
			'default' => array( 'unit' => 'px', 'size' => 220 ),
			'selectors' => array( '{{WRAPPER}} .sazan-cta' => '--sc-pw:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'img_nudge', array(
			'label' => esc_html__( 'جابه‌جایی افقی تصویر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => -120, 'max' => 120 ) ),
			'default' => array( 'unit' => 'px', 'size' => 0 ),
			'selectors' => array( '{{WRAPPER}} .sazan-cta' => '--sc-px:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'img_nudge_y', array(
			'label' => esc_html__( 'موقعیت عمودی تصویر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => -120, 'max' => 120 ) ),
			'default' => array( 'unit' => 'px', 'size' => 0 ),
			'description' => esc_html__( 'مثبت = بالاتر، منفی = پایین‌تر', 'sazan-core' ),
			'selectors' => array( '{{WRAPPER}} .sazan-cta' => '--sc-py:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'mobile_img', array(
			'label' => esc_html__( 'تصویر در موبایل', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'top',
			'options' => array(
				'top'    => esc_html__( 'بالا (پیش‌فرض)', 'sazan-core' ),
				'bottom' => esc_html__( 'پایین', 'sazan-core' ),
				'hide'   => esc_html__( 'مخفی', 'sazan-core' ),
			),
		) );
		$this->end_controls_section();

		$this->add_palette_controls();

		/* رنگ‌بندی بنر */
		$this->start_controls_section( 'sec_cta_style', array(
			'label' => esc_html__( 'رنگ‌بندی بنر', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'cta_bg', array(
			'label' => esc_html__( 'بک‌گراند کل بنر', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-inner' => 'background:{{VALUE}};' ),
		) );
		$this->add_control( 'cta_bd_color', array(
			'label' => esc_html__( 'رنگ حاشیه بنر', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-inner' => 'border-color:{{VALUE}}; border-style:solid;' ),
		) );
		$this->add_responsive_control( 'cta_bd_w', array(
			'label' => esc_html__( 'ضخامت حاشیه', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 8 ) ),
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-inner' => 'border-width:{{SIZE}}{{UNIT}}; border-style:solid;' ),
		) );
		$this->add_responsive_control( 'cta_radius', array(
			'label' => esc_html__( 'گردی گوشه بنر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-inner' => 'border-radius:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'cta_btn_heading', array(
			'label' => esc_html__( 'دکمه', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before',
		) );
		$this->add_control( 'cta_btn_bg', array(
			'label' => esc_html__( 'بک‌گراند دکمه', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-btn' => 'background:{{VALUE}};' ),
		) );
		$this->add_control( 'cta_btn_color', array(
			'label' => esc_html__( 'رنگ متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-btn' => 'color:{{VALUE}};' ),
		) );
		$this->add_control( 'cta_btn_bd', array(
			'label' => esc_html__( 'رنگ حاشیه دکمه', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .sazan-cta .sc-btn' => 'border-color:{{VALUE}}; border-style:solid;' ),
		) );
		$this->end_controls_section();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$skin = in_array( $s['skin'], array( 'pro', 'lux' ), true ) ? $s['skin'] : 'classic';
		$side = ( 'left' === $s['img_side'] ) ? 'left' : 'right';
		$mimg = in_array( $s['mobile_img'], array( 'bottom', 'hide' ), true ) ? $s['mobile_img'] : 'top';

		echo '<div class="sazan-sec sazan-cta skin-' . esc_attr( $skin ) . ' side-' . esc_attr( $side ) . ' m-img-' . esc_attr( $mimg ) . '">';
		echo '<div class="sc-inner">';

		/* تصویر */
		if ( ! empty( $s['image']['url'] ) ) {
			echo '<div class="sc-photo"><span class="sc-photo-glow" aria-hidden="true"></span><img src="' . esc_url( $s['image']['url'] ) . '" alt=""></div>';
		}

		/* متن */
		echo '<div class="sc-text">';
		if ( ! empty( $s['title'] ) ) {
			echo '<h2 class="sc-title">' . esc_html( $s['title'] ) . '</h2>';
		}
		if ( ! empty( $s['desc'] ) ) {
			echo '<p class="sc-desc">' . esc_html( $s['desc'] ) . '</p>';
		}
		echo '</div>';

		/* دکمه */
		if ( '' !== trim( (string) $s['btn_text'] ) ) {
			$href     = ! empty( $s['link']['url'] ) ? $s['link']['url'] : '#';
			$target   = ! empty( $s['link']['is_external'] ) ? ' target="_blank"' : '';
			$nofollow = ! empty( $s['link']['nofollow'] ) ? ' rel="nofollow"' : '';
			printf(
				'<a class="sc-btn" href="%1$s"%2$s%3$s>%4$s</a>',
				esc_url( $href ), $target, $nofollow, esc_html( $s['btn_text'] )
			);
		}

		echo '</div></div>';
	}
}
