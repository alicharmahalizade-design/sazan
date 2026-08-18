<?php
/**
 * ویجت‌های المنتور — یکی به‌ازای هر سکشن.
 *
 * برای سکشن بعدی: یک کلاس کوچک مثل زیر اضافه کن و نامش را در نقشه بگذار.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* سکشن ۱ — هیرو */
class SPP_Widget_Hero extends SPP_Widget_Base {
	protected function section_id() {
		return 'hero';
	}
}

/* سکشن ۲ — درباره دوره */
class SPP_Widget_About extends SPP_Widget_Base {

	protected function section_id() {
		return 'about';
	}

	protected function extra_controls() {

		$this->start_controls_section( 'spp_about_style', array(
			'label' => 'تصویر تزئینی',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'about_fig', array(
			'label'      => 'اندازه‌ی تصویر',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 80, 'max' => 340 ) ),
			'default'    => array(
				'unit' => 'px',
				'size' => 200,
			),
			'selectors'  => array( '{{WRAPPER}} .spp' => '--spp-about-fig: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}
}

/* سکشن ۳ — ویژگی‌ها و سرفصل‌ها */
class SPP_Widget_Curriculum extends SPP_Widget_Base {
	protected function section_id() {
		return 'curriculum';
	}
}

/* سکشن ۴ — معرفی مدرس */
class SPP_Widget_Instructor extends SPP_Widget_Base {

	protected function section_id() {
		return 'instructor';
	}

	protected function extra_controls() {

		$this->start_controls_section( 'spp_inst_style', array(
			'label' => 'عکس مدرس',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'inst_out', array(
			'label'       => 'بیرون‌زدگی از بالای کارت',
			'description' => 'مقدار صفر = عکس داخل کارت بماند.',
			'type'        => \Elementor\Controls_Manager::SLIDER,
			'size_units'  => array( 'px' ),
			'range'       => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'default'     => array(
				'unit' => 'px',
				'size' => 34,
			),
			'selectors'   => array( '{{WRAPPER}} .spp' => '--spp-inst-out: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'inst_fig', array(
			'label'      => 'عرض عکس',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 140, 'max' => 460 ) ),
			'default'    => array(
				'unit' => 'px',
				'size' => 272,
			),
			'selectors'  => array( '{{WRAPPER}} .spp' => '--spp-inst-fig: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}
}

/* سکشن ۵ — مهارت‌های پس از دوره */
class SPP_Widget_Skills extends SPP_Widget_Base {
	protected function section_id() {
		return 'skills';
	}
}

/* سکشن ۶ — نظرات دانشجویان */
class SPP_Widget_Testimonials extends SPP_Widget_Base {
	protected function section_id() {
		return 'testimonials';
	}
}

/* سکشن ۷ — سوالات متداول */
class SPP_Widget_FAQ extends SPP_Widget_Base {
	protected function section_id() {
		return 'faq';
	}
}

/**
 * نقشه‌ی سکشن => کلاس ویجت.
 *
 * @return array<string,string>
 */
function spp_elementor_widget_map() {
	return array(
		'hero'       => 'SPP_Widget_Hero',
		'about'      => 'SPP_Widget_About',
		'curriculum' => 'SPP_Widget_Curriculum',
		'instructor' => 'SPP_Widget_Instructor',
		'skills'       => 'SPP_Widget_Skills',
		'testimonials' => 'SPP_Widget_Testimonials',
		'faq'          => 'SPP_Widget_FAQ',
	);
}
