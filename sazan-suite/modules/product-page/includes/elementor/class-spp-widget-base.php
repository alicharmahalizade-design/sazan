<?php
/**
 * کلاس پایه‌ی ویجت‌های سکشن.
 *
 * ویجتِ فرزند فقط شناسه‌ی سکشن را می‌دهد؛ انتخاب محصول، پالت رنگ و رندر
 * یک‌جا اینجا انجام می‌شود.
 *
 * @package Sazan\ProductPage
 */

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class SPP_Widget_Base extends \Elementor\Widget_Base {

	/** شناسه‌ی سکشن در رجیستری. */
	abstract protected function section_id();

	public function get_name() {
		return 'spp-' . $this->section_id();
	}

	public function get_title() {
		$section = SPP_Sections::get( $this->section_id() );
		return $section ? $section->title() : $this->section_id();
	}

	public function get_icon() {
		$section = SPP_Sections::get( $this->section_id() );
		return $section ? $section->icon() : 'eicon-single-page';
	}

	public function get_categories() {
		return array( 'sazan-product' );
	}

	public function get_keywords() {
		return array( 'sazan', 'سازان', 'محصول', 'دوره', 'product', 'hero' );
	}

	public function get_style_depends() {
		return array( 'spp-front' );
	}

	public function get_script_depends() {
		return array( 'spp-front' );
	}

	/* =====================================================================
	 * کنترل‌ها
	 * =================================================================== */

	protected function register_controls() {

		$this->start_controls_section( 'spp_source', array(
			'label' => 'منبع محتوا',
		) );

		$this->add_control( 'spp_notice', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => 'متن‌های این سکشن از متاباکس «صفحه اختصاصی محصول» در صفحه‌ی ویرایش همان محصول خوانده می‌شود.',
			'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
		) );

		$this->add_control( 'product_id', array(
			'label'       => 'محصول',
			'type'        => Controls_Manager::SELECT2,
			'options'     => self::product_options(),
			'label_block' => true,
			'description' => 'خالی = محصولِ صفحه‌ی جاری.',
		) );

		$this->end_controls_section();

		$this->palette_controls();
		$this->extra_controls();
	}

	/**
	 * کنترل‌های اختصاصی هر سکشن — ویجت فرزند در صورت نیاز بازنویسی می‌کند.
	 */
	protected function extra_controls() {}

	/**
	 * پالت رنگ + اندازه‌ها روی متغیرهای CSS.
	 */
	protected function palette_controls() {

		$this->start_controls_section( 'spp_palette', array(
			'label' => 'پالت رنگ',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$map = array(
			'bg'     => array( 'پس‌زمینه‌ی سکشن', '#040b14', '--spp-bg' ),
			'card'   => array( 'کارت', '#0b1a2b', '--spp-card' ),
			'card2'  => array( 'کارت (روشن‌تر)', '#102338', '--spp-card-2' ),
			'border' => array( 'حاشیه', '#1b3346', '--spp-border' ),
			'text'   => array( 'متن اصلی', '#e9f1f7', '--spp-text' ),
			'text2'  => array( 'متن فرعی', '#b9cad6', '--spp-text-2' ),
			'muted'  => array( 'متن کم‌رنگ', '#8598a7', '--spp-muted' ),
			'cyan'   => array( 'فیروزه‌ای', '#2ec5ec', '--spp-cyan' ),
			'gold'   => array( 'طلایی', '#f2b950', '--spp-gold' ),
			'gold2'  => array( 'طلایی تیره', '#d9992c', '--spp-gold-2' ),
		);

		foreach ( $map as $key => $cfg ) {
			$this->add_control( 'pal_' . $key, array(
				'label'     => $cfg[0],
				'type'      => Controls_Manager::COLOR,
				'default'   => $cfg[1],
				'selectors' => array( '{{WRAPPER}} .spp' => $cfg[2] . ': {{VALUE}};' ),
			) );
		}

		$this->add_responsive_control( 'pal_wrap', array(
			'label'      => 'حداکثر عرض محتوا',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 600, 'max' => 1600 ) ),
			'default'    => array(
				'unit' => 'px',
				'size' => 1320,
			),
			'selectors'  => array( '{{WRAPPER}} .spp' => '--spp-wrap: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'pal_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default'    => array(
				'unit' => 'px',
				'size' => 22,
			),
			'selectors'  => array( '{{WRAPPER}} .spp' => '--spp-radius: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'pal_pad', array(
			'label'      => 'فاصله‌ی عمودی سکشن',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 160 ) ),
			'selectors'  => array( '{{WRAPPER}} .spp' => '--spp-sec-y: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'spp_transparent', array(
			'label'        => 'پس‌زمینه‌ی سکشن شفاف',
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'selectors'    => array( '{{WRAPPER}} .spp' => 'background: transparent;' ),
		) );

		$this->end_controls_section();
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	protected function render() {

		$settings = $this->get_settings_for_display();
		$id       = ! empty( $settings['product_id'] ) ? (int) $settings['product_id'] : 0;

		if ( ! $id ) {
			$id = (int) get_the_ID();
		}

		if ( ! $id ) {
			$this->editor_hint( 'یک محصول از بخش «منبع محتوا» انتخاب کنید.' );
			return;
		}

		$html = SPP_Sections::render( $this->section_id(), $id );

		if ( '' === trim( $html ) ) {
			$this->editor_hint( 'محتوایی برای این سکشن ثبت نشده است. به صفحه‌ی ویرایش محصول ← متاباکس «صفحه اختصاصی محصول» بروید.' );
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- در سکشن escape شده.
	}

	/**
	 * راهنما — فقط داخل ادیتور المنتور.
	 *
	 * @param string $msg
	 */
	private function editor_hint( $msg ) {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			printf(
				'<div style="padding:24px;border:1px dashed #9aa;border-radius:10px;text-align:center;font-family:Tahoma,sans-serif" dir="rtl">%s</div>',
				esc_html( $msg )
			);
		}
	}

	/**
	 * فهرست محصولات برای SELECT2.
	 *
	 * @return array<int,string>
	 */
	private static function product_options() {

		$types = class_exists( 'SPP_Metabox' ) ? SPP_Metabox::post_types() : array( 'product' );

		$posts = get_posts( array(
			'post_type'        => $types,
			'post_status'      => 'publish',
			'numberposts'      => 200,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		) );

		$out = array();
		foreach ( $posts as $p ) {
			$out[ $p->ID ] = $p->post_title;
		}

		return $out;
	}
}
