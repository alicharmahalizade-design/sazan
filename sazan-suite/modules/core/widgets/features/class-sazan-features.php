<?php
/**
 * ویجت کارت‌های خدمات/ویژگی (آیکن سه‌بعدی + عنوان + توضیح + دکمه).
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Features extends Widget_Base {

	public function get_name() { return 'sazan-features'; }
	public function get_title() { return esc_html__( 'کارت‌های خدمات سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-icon-box'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'features', 'services', 'خدمات', 'کارت', 'باکس' ); }

	protected function register_controls() {

		/* سرتیتر (اختیاری) */
		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_section_header_controls( 'Services', esc_html__( 'خدمات سازان', 'sazan-core' ) );
		// پیش‌فرض سرتیتر خاموش (طرح تصویر بدون سرتیتر است)
		$this->update_control( 'show_head', array( 'default' => '' ) );
		$this->end_controls_section();

		/* طرح نمایش */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'pro',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (ساده)', 'sazan-core' ),
				'pro'     => esc_html__( 'درخشان / حرفه‌ای ✨', 'sazan-core' ),
			),
		) );
		$this->end_controls_section();

		/* کارت‌ها */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'کارت‌ها', 'sazan-core' ) ) );

		$rep = new Repeater();
		$rep->add_control( 'icon', array( 'label' => esc_html__( 'آیکن (تصویر)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'عنوان کارت', 'sazan-core' ) ) );
		$rep->add_control( 'desc', array( 'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'default' => esc_html__( 'توضیح کوتاه درباره این خدمت یا ویژگی.', 'sazan-core' ) ) );
		$rep->add_control( 'btn_text', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'بیشتر بدانید', 'sazan-core' ) ) );
		$rep->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );

		$this->add_control( 'items', array(
			'label' => esc_html__( 'کارت‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array(
				array( 'title' => 'مرکز دانش سازان', 'desc' => 'دسترسی به مقالات، ویدیو ها، پادکست ها و منابع آموزشی', 'btn_text' => 'بیشتر بدانید' ),
				array( 'title' => 'مشاوره کسب و کار', 'desc' => 'دریافت مشاوره تخصصی برای رشد کسب وکار', 'btn_text' => 'بیشتر بدانید' ),
				array( 'title' => 'دوره های آموزشی', 'desc' => 'شرکت در دوره ها و کارگاه های تخصصی سازان', 'btn_text' => 'بیشتر بدانید' ),
			),
		) );

		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '3', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array( '{{WRAPPER}} .sazan-features-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->add_responsive_control( 'gap', array(
			'label' => esc_html__( 'فاصله بین کارت‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'default' => array( 'unit' => 'px', 'size' => 24 ),
			'selectors' => array( '{{WRAPPER}} .sazan-features-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'icon_size', array(
			'label' => esc_html__( 'اندازه آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 40, 'max' => 160 ) ),
			'default' => array( 'unit' => 'px', 'size' => 92 ),
			'tablet_default' => array( 'unit' => 'px', 'size' => 78 ),
			'mobile_default' => array( 'unit' => 'px', 'size' => 70 ),
			'selectors' => array( '{{WRAPPER}} .sazan-feature-icon' => 'width:{{SIZE}}{{UNIT}}; height:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'icon_h', array(
			'label' => esc_html__( 'موقعیت افقی آیکن', 'sazan-core' ), 'type' => Controls_Manager::CHOOSE,
			'default' => 'left',
			'options' => array(
				'right'  => array( 'title' => esc_html__( 'راست', 'sazan-core' ), 'icon' => 'eicon-h-align-right' ),
				'center' => array( 'title' => esc_html__( 'وسط', 'sazan-core' ), 'icon' => 'eicon-h-align-center' ),
				'left'   => array( 'title' => esc_html__( 'چپ', 'sazan-core' ), 'icon' => 'eicon-h-align-left' ),
			),
			'selectors_dictionary' => array(
				'right'  => 'right:var(--sz-fi-edge,32px); left:auto; --sz-fi-tx:0;',
				'center' => 'left:50%; right:auto; --sz-fi-tx:-50%;',
				'left'   => 'left:var(--sz-fi-edge,32px); right:auto; --sz-fi-tx:0;',
			),
			'selectors' => array( '{{WRAPPER}} .sazan-feature-icon' => '{{VALUE}}' ),
		) );
		$this->add_responsive_control( 'icon_x', array(
			'label' => esc_html__( 'فاصله افقی از لبه', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'default' => array( 'unit' => 'px', 'size' => 32 ),
			'condition' => array( 'icon_h!' => 'center' ),
			'selectors' => array( '{{WRAPPER}} .sazan-feature-icon' => '--sz-fi-edge:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'icon_y', array(
			'label' => esc_html__( 'موقعیت عمودی آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( '%', 'px' ),
			'range' => array( '%' => array( 'min' => -90, 'max' => 60 ), 'px' => array( 'min' => -110, 'max' => 60 ) ),
			'default' => array( 'unit' => '%', 'size' => -50 ),
			'description' => esc_html__( 'مقدار منفی = بیرون‌زدگی به سمت بالا', 'sazan-core' ),
			'selectors' => array( '{{WRAPPER}} .sazan-feature-icon' => '--sz-fi-y:{{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* پالت رنگ مشترک */
		$this->add_palette_controls();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$skin = ( 'classic' === $s['skin'] ) ? 'classic' : 'pro';
		echo '<div class="sazan-sec sazan-features skin-' . esc_attr( $skin ) . '">';
		$this->render_section_header( $s );

		echo '<div class="sazan-features-grid">';
		foreach ( (array) $s['items'] as $i ) {
			$icon = ! empty( $i['icon']['url'] )
				? '<span class="sazan-feature-icon"><img src="' . esc_url( $i['icon']['url'] ) . '" alt=""></span>'
				: '';

			$btn = '';
			if ( '' !== trim( (string) $i['btn_text'] ) ) {
				$href   = ! empty( $i['link']['url'] ) ? $i['link']['url'] : '#';
				$target = ! empty( $i['link']['is_external'] ) ? ' target="_blank"' : '';
				$nofollow = ! empty( $i['link']['nofollow'] ) ? ' rel="nofollow"' : '';
				$btn = sprintf(
					'<a class="sazan-feature-btn" href="%1$s"%2$s%3$s>%4$s</a>',
					esc_url( $href ), $target, $nofollow, esc_html( $i['btn_text'] )
				);
			}

			printf(
				'<article class="sazan-feature-card">%1$s<h3>%2$s</h3><p>%3$s</p>%4$s</article>',
				$icon,
				esc_html( $i['title'] ),
				esc_html( $i['desc'] ),
				$btn
			);
		}
		echo '</div></div>';
	}
}
