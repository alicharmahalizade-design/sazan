<?php
/**
 * کلاس پایه‌ی ویجت‌های سازان.
 *
 * همه‌ی ویجت‌ها از این کلاس ارث‌بری می‌کنند و به متدهای کمکی
 * برای افزودن گروه‌های استایل پرتکرار (پس‌زمینه، حاشیه، فاصله،
 * سایه و ...) دسترسی دارند تا کد تکراری نوشته نشود.
 *
 * @package Sazan\Base
 */

namespace Sazan\Base;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Sazan\\Base\\Widget_Base' ) ) :

abstract class Widget_Base extends \Elementor\Widget_Base {

	/**
	 * دسته‌بندی پیش‌فرض همه‌ی ویجت‌های سازان.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'sazan' );
	}

	/**
	 * کلیدواژه‌های جستجو (قابل بازنویسی در هر ویجت).
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'sazan', 'سازان' );
	}

	/* ---------------------------------------------------------------------
	 * متدهای کمکی استایل — در بخش style هر ویجت صدا زده می‌شوند.
	 * پارامتر $selector انتخابگر CSS هدف داخل قالب ویجت است.
	 * ------------------------------------------------------------------- */

	/**
	 * گروه کنترل پس‌زمینه (رنگ/گرادینت/تصویر).
	 *
	 * @param string $name     شناسه‌ی یکتا برای کنترل.
	 * @param string $selector انتخابگر CSS.
	 * @param string $label    برچسب نمایشی.
	 */
	protected function sazan_add_background( $name, $selector, $label = null ) {
		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => $name,
				'label'    => $label ?: esc_html__( 'پس‌زمینه', 'sazan-core' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
	}

	/**
	 * گروه کنترل حاشیه (نوع/ضخامت/رنگ) + گردی گوشه.
	 *
	 * @param string $name
	 * @param string $selector
	 */
	protected function sazan_add_border( $name, $selector ) {
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => $name . '_border',
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);

		$this->add_responsive_control(
			$name . '_radius',
			array(
				'label'      => esc_html__( 'گردی گوشه‌ها', 'sazan-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} ' . $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
	}

	/**
	 * گروه کنترل سایه‌ی جعبه.
	 *
	 * @param string $name
	 * @param string $selector
	 */
	protected function sazan_add_box_shadow( $name, $selector ) {
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => $name . '_shadow',
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
	}

	/**
	 * کنترل تایپوگرافی.
	 *
	 * @param string $name
	 * @param string $selector
	 */
	protected function sazan_add_typography( $name, $selector ) {
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $name . '_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
	}

	/**
	 * کنترل رنگ متن.
	 *
	 * @param string $name
	 * @param string $selector
	 * @param string $label
	 */
	protected function sazan_add_color( $name, $selector, $label = null ) {
		$this->add_control(
			$name . '_color',
			array(
				'label'     => $label ?: esc_html__( 'رنگ', 'sazan-core' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} ' . $selector => 'color: {{VALUE}};',
				),
			)
		);
	}

	/**
	 * کنترل padding ریسپانسیو.
	 *
	 * @param string $name
	 * @param string $selector
	 */
	protected function sazan_add_padding( $name, $selector ) {
		$this->add_responsive_control(
			$name . '_padding',
			array(
				'label'      => esc_html__( 'فاصله داخلی', 'sazan-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
	}

	/**
	 * کنترل margin ریسپانسیو.
	 *
	 * @param string $name
	 * @param string $selector
	 */
	protected function sazan_add_margin( $name, $selector ) {
		$this->add_responsive_control(
			$name . '_margin',
			array(
				'label'      => esc_html__( 'فاصله بیرونی', 'sazan-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} ' . $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
	}

	/**
	 * کنترل ترازبندی (راست/وسط/چپ).
	 *
	 * @param string $name
	 * @param string $selector
	 */
	protected function sazan_add_alignment( $name, $selector ) {
		$this->add_responsive_control(
			$name . '_align',
			array(
				'label'     => esc_html__( 'چینش', 'sazan-core' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'right'  => array(
						'title' => esc_html__( 'راست', 'sazan-core' ),
						'icon'  => 'eicon-text-align-right',
					),
					'center' => array(
						'title' => esc_html__( 'وسط', 'sazan-core' ),
						'icon'  => 'eicon-text-align-center',
					),
					'left'   => array(
						'title' => esc_html__( 'چپ', 'sazan-core' ),
						'icon'  => 'eicon-text-align-left',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} ' . $selector => 'text-align: {{VALUE}};',
				),
			)
		);
	}

	/* =====================================================================
	 * کمک‌متدهای مشترک بخش‌های دارک‌مود (دوره‌ها، تقویم، پادکست، وبلاگ)
	 * =================================================================== */

	protected function add_section_header_controls( $en_default, $title_default ) {
		$this->add_control( 'show_head', array(
			'label' => esc_html__( 'نمایش سرتیتر', 'sazan-core' ),
			'type'  => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
		) );
		$this->add_control( 'head_en', array(
			'label' => esc_html__( 'برچسب انگلیسی', 'sazan-core' ),
			'type'  => Controls_Manager::TEXT, 'default' => $en_default,
			'condition' => array( 'show_head' => 'yes' ),
		) );
		$this->add_control( 'head_title', array(
			'label' => esc_html__( 'عنوان', 'sazan-core' ),
			'type'  => Controls_Manager::TEXT, 'default' => $title_default,
			'condition' => array( 'show_head' => 'yes' ),
		) );
	}

	protected function render_section_header( $s ) {
		if ( empty( $s['show_head'] ) || 'yes' !== $s['show_head'] ) {
			return;
		}
		echo '<div class="sazan-sec-head">';
		if ( ! empty( $s['head_en'] ) ) {
			echo '<div class="sazan-sec-en">' . esc_html( $s['head_en'] ) . '</div>';
		}
		if ( ! empty( $s['head_title'] ) ) {
			echo '<h2 class="sazan-sec-title">' . esc_html( $s['head_title'] ) . '</h2>';
		}
		echo '</div>';
	}

	protected function add_palette_controls() {
		$this->start_controls_section( 'sec_palette', array(
			'label' => esc_html__( 'پالت رنگ', 'sazan-core' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$map = array(
			'accent'  => array( esc_html__( 'نارنجی (روشن)', 'sazan-core' ), '#f7941d', '--sz-accent' ),
			'accent2' => array( esc_html__( 'نارنجی (تیره)', 'sazan-core' ), '#d97f12', '--sz-accent2' ),
			'cyan'    => array( esc_html__( 'فیروزه‌ای', 'sazan-core' ), '#00b6f1', '--sz-cyan' ),
			'card'    => array( esc_html__( 'پس‌زمینه کارت', 'sazan-core' ), '#13242f', '--sz-card' ),
			'border'  => array( esc_html__( 'حاشیه', 'sazan-core' ), '#1d3744', '--sz-border' ),
			'text'    => array( esc_html__( 'متن اصلی', 'sazan-core' ), '#e8f0f4', '--sz-text' ),
			'text2'   => array( esc_html__( 'متن فرعی', 'sazan-core' ), '#a7bcc8', '--sz-text2' ),
			'muted'   => array( esc_html__( 'متن کم‌رنگ', 'sazan-core' ), '#7d94a1', '--sz-muted' ),
		);
		foreach ( $map as $key => $cfg ) {
			$this->add_control( 'pal_' . $key, array(
				'label'     => $cfg[0],
				'type'      => Controls_Manager::COLOR,
				'default'   => $cfg[1],
				'selectors' => array( '{{WRAPPER}} .sazan-sec' => $cfg[2] . ': {{VALUE}};' ),
			) );
		}

		$this->add_responsive_control( 'pal_radius', array(
			'label' => esc_html__( 'گردی گوشه‌ها', 'sazan-core' ),
			'type'  => Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default' => array( 'unit' => 'px', 'size' => 16 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sec' => '--sz-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name' => 'pal_title_typo',
			'label' => esc_html__( 'تایپوگرافی عنوان بخش', 'sazan-core' ),
			'selector' => '{{WRAPPER}} .sazan-sec-title',
		) );

		$this->end_controls_section();
	}
}

endif;
