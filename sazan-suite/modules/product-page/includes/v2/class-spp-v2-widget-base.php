<?php
/** Elementor base class for Product Page v2 section widgets. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class SPP_V2_Widget_Base extends \Elementor\Widget_Base {

	abstract protected function section_id();
	abstract protected function section_title();

	public function get_name() {
		return 'spp-v2-' . $this->section_id();
	}

	public function get_title() {
		return 'سازان V2 — ' . $this->section_title();
	}

	public function get_icon() {
		return 'eicon-single-product';
	}

	public function get_categories() {
		return array( 'sazan-product' );
	}

	public function get_keywords() {
		return array( 'sazan', 'سازان', 'دوره', 'محصول', 'woocommerce', 'course', 'product' );
	}

	public function get_style_depends() {
		return array( 'spp-v2-front' );
	}

	public function get_script_depends() {
		return array( 'spp-v2-front' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'spp_v2_source', array( 'label' => 'محتوا' ) );
		$this->add_control( 'spp_v2_notice', array(
			'type' => \Elementor\Controls_Manager::RAW_HTML,
			'raw' => 'محتوای این سکشن از متاباکس «تنظیمات صفحه دوره سازان» در محصول جاری خوانده می‌شود. عنوان، قیمت، تصویر شاخص، امتیاز و فرم خرید از WooCommerce می‌آیند.',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );
		$this->add_control( 'product_id', array(
			'label' => 'محصول پیش‌نمایش',
			'type' => \Elementor\Controls_Manager::SELECT2,
			'options' => self::product_options(),
			'label_block' => true,
			'description' => 'در قالب تک‌محصول خالی بماند تا محصول جاری به‌صورت پویا استفاده شود.',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'spp_v2_layout', array( 'label' => 'چیدمان', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'container_width', array(
			'label' => 'حداکثر عرض محتوا', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 880, 'max' => 1440 ) ),
			'default' => array( 'unit' => 'px', 'size' => 1240 ),
			'selectors' => array( '{{WRAPPER}} .spp-v2' => '--spp-v2-container: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'section_space', array(
			'label' => 'فاصله عمودی', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 0, 'max' => 180 ) ),
			'default' => array( 'unit' => 'px', 'size' => 82 ),
			'tablet_default' => array( 'unit' => 'px', 'size' => 64 ),
			'mobile_default' => array( 'unit' => 'px', 'size' => 48 ),
			'selectors' => array( '{{WRAPPER}} .spp-v2-section' => '--spp-v2-section-space: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'card_radius', array(
			'label' => 'گردی کارت‌ها', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default' => array( 'unit' => 'px', 'size' => 20 ),
			'selectors' => array( '{{WRAPPER}} .spp-v2' => '--spp-v2-radius-lg: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'sticky_offset', array(
			'label' => 'فاصله Sticky از بالای صفحه', 'type' => \Elementor\Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 0, 'max' => 180 ) ),
			'default' => array( 'unit' => 'px', 'size' => 0 ),
			'description' => 'اگر هدر سایت Fixed است، ارتفاع هدر را وارد کنید. در Canvas استاندارد مقدار صفر درست است.',
			'selectors' => array( '{{WRAPPER}} .spp-v2' => '--spp-v2-sticky-offset: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'spp_v2_palette', array( 'label' => 'رنگ‌ها', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$colors = array(
			'background' => array( 'پس‌زمینه', '#000014', '--spp-v2-bg' ),
			'surface_1' => array( 'سطح ۱', '#071523', '--spp-v2-surface-1' ),
			'surface_2' => array( 'سطح ۲', '#0D1B25', '--spp-v2-surface-2' ),
			'surface_3' => array( 'سطح ۳', '#0E2838', '--spp-v2-surface-3' ),
			'primary' => array( 'فیروزه‌ای اصلی', '#02E1F3', '--spp-v2-primary' ),
			'accent' => array( 'طلایی تأکیدی', '#F3B401', '--spp-v2-accent' ),
			'text' => array( 'متن اصلی', '#F4F8FA', '--spp-v2-text' ),
			'muted' => array( 'متن فرعی', '#9AA9B5', '--spp-v2-muted' ),
		);
		foreach ( $colors as $key => $config ) {
			$this->add_control( 'color_' . $key, array( 'label' => $config[0], 'type' => \Elementor\Controls_Manager::COLOR, 'default' => $config[1], 'selectors' => array( '{{WRAPPER}} .spp-v2' => $config[2] . ': {{VALUE}};' ) ) );
		}
		$this->end_controls_section();

		$this->start_controls_section( 'spp_v2_typography', array( 'label' => 'تایپوگرافی', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'heading_typography', 'label' => 'عنوان‌های سکشن', 'selector' => '{{WRAPPER}} .spp-v2-section-head h2, {{WRAPPER}} .spp-v2-section h2' ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'body_typography', 'label' => 'متن بدنه', 'selector' => '{{WRAPPER}} .spp-v2' ) );
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id = absint( isset( $settings['product_id'] ) ? $settings['product_id'] : 0 );
		if ( ! $post_id ) {
			$post_id = SPP_V2_Template::current_product_id();
		}
		$is_editor = isset( \Elementor\Plugin::$instance->editor ) && is_object( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
		$is_preview = isset( \Elementor\Plugin::$instance->preview ) && is_object( \Elementor\Plugin::$instance->preview ) && \Elementor\Plugin::$instance->preview->is_preview_mode();
		if ( ! $post_id && ( $is_editor || $is_preview ) ) {
			$products = array_keys( self::product_options() );
			$post_id = $products ? absint( $products[0] ) : 0;
		}
		echo SPP_V2_Renderer::render( $this->section_id(), $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	private static function product_options() {
		$options = array( '' => 'محصول جاری (پویا)' );
		if ( ! post_type_exists( 'product' ) ) {
			return $options;
		}
		$products = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private' ), 'numberposts' => 100, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids' ) );
		foreach ( $products as $product_id ) {
			$options[ $product_id ] = get_the_title( $product_id ) . ' (#' . $product_id . ')';
		}
		return $options;
	}
}
