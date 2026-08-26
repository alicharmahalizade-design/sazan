<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

/**
 * Base for every landing widget.
 *
 * Subclasses implement content_controls() / style_controls() / output().
 * The helpers below generate the repetitive Elementor control blocks so each
 * widget stays readable: colors, typography, boxes, spacing and the shared
 * section heading (eyebrow / title / subtitle).
 */
abstract class SZL_Widget_Base extends \Elementor\Widget_Base {

	public function get_categories() {
		return array( 'sazan-landing' );
	}
	public function get_icon() {
		return 'eicon-single-page';
	}
	public function get_keywords() {
		return array( 'sazan', 'landing', 'حکمرانی', 'بازار', 'دوره', 'سازان', 'لندینگ' );
	}
	public function get_style_depends() {
		return array( 'szl-front' );
	}
	public function get_script_depends() {
		return array( 'szl-front' );
	}

	/** Default value of the block-level text alignment control. */
	protected $default_align = '';

	/** Default value of the section-heading alignment control. */
	protected $default_head_align = 'center';

	abstract protected function content_controls();
	abstract protected function style_controls();
	abstract protected function output( $s );

	protected function register_controls() {
		$this->content_controls();
		$this->style_controls();
		$this->palette_controls();
		$this->layout_controls();
	}

	public function render() {
		$s = $this->get_settings_for_display();
		echo '<div class="szl ' . esc_attr( $this->szl_root_class() ) . '">';
		$this->output( $s );
		echo '</div>';
	}

	/**
	 * Wrapper class for the widget root. Prefixed with szl-w- so it can never
	 * collide with the inner element classes (.szl-facts, .szl-hero, …).
	 */
	protected function szl_root_class() {
		return 'szl-w-' . str_replace( 'szl_', '', $this->get_name() );
	}

	/* ==================================================================
	   Shared control blocks
	   ================================================================== */

	/**
	 * Palette: writes CSS custom properties onto the widget wrapper, so every
	 * component in this widget re-colours from one place.
	 */
	protected function palette_controls() {
		$this->start_controls_section( 'szl_palette', array(
			'label' => 'پالت رنگ',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$vars = array(
			'primary'   => array( 'رنگ اصلی', '#00b6f1', '--szl-primary' ),
			'primary2'  => array( 'رنگ اصلی روشن', '#4ad1ff', '--szl-primary-2' ),
			'secondary' => array( 'رنگ مکمل', '#f7941d', '--szl-secondary' ),
			'text'      => array( 'رنگ متن', '#eaf2f8', '--szl-text' ),
			'heading'   => array( 'رنگ عنوان‌ها', '#ffffff', '--szl-heading' ),
			'muted'     => array( 'رنگ متن کم‌رنگ', '#93a4b3', '--szl-muted' ),
			'surface'   => array( 'رنگ سطح کارت‌ها', 'rgba(255,255,255,0.045)', '--szl-glass' ),
			'border'    => array( 'رنگ حاشیه', 'rgba(255,255,255,0.10)', '--szl-border' ),
		);

		foreach ( $vars as $key => $conf ) {
			$this->add_control( 'pal_' . $key, array(
				'label'     => $conf[0],
				'type'      => Controls_Manager::COLOR,
				'default'   => $conf[1],
				'selectors' => array( '{{WRAPPER}}' => $conf[2] . ': {{VALUE}};' ),
			) );
		}

		$this->add_control( 'pal_grad_hr', array( 'type' => Controls_Manager::DIVIDER ) );

		$this->add_control( 'pal_grad_a', array(
			'label'     => 'گرادیان — رنگ آغاز',
			'type'      => Controls_Manager::COLOR,
			'default'   => '#00b6f1',
			'selectors' => array( '{{WRAPPER}}' => '--szl-grad-a: {{VALUE}};' ),
		) );
		$this->add_control( 'pal_grad_b', array(
			'label'     => 'گرادیان — رنگ میانی',
			'type'      => Controls_Manager::COLOR,
			'default'   => '#4ad1ff',
			'selectors' => array( '{{WRAPPER}}' => '--szl-grad-b: {{VALUE}};' ),
		) );
		$this->add_control( 'pal_grad_c', array(
			'label'     => 'گرادیان — رنگ پایان',
			'type'      => Controls_Manager::COLOR,
			'default'   => '#f7941d',
			'selectors' => array( '{{WRAPPER}}' => '--szl-grad-c: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'pal_grad_angle', array(
			'label'      => 'زاویه گرادیان',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'deg' ),
			'range'      => array( 'deg' => array( 'min' => 0, 'max' => 360 ) ),
			'default'    => array( 'unit' => 'deg', 'size' => 135 ),
			'selectors'  => array( '{{WRAPPER}}' => '--szl-grad-angle: {{SIZE}}deg;' ),
		) );

		$this->end_controls_section();
	}

	/** Outer spacing + radius of the whole widget block. */
	protected function layout_controls() {
		$this->start_controls_section( 'szl_layout', array(
			'label' => 'چیدمان و فاصله',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_responsive_control( 'box_padding', array(
			'label'      => 'فاصله داخلی بخش',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .szl' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => 'box_bg',
			'label'    => 'پس‌زمینه بخش',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .szl',
		) );

		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => 'box_border',
			'label'    => 'حاشیه بخش',
			'selector' => '{{WRAPPER}} .szl',
		) );

		$this->add_responsive_control( 'box_radius', array(
			'label'      => 'گردی گوشه بخش',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} .szl' => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};overflow:hidden;',
			),
		) );

		$this->add_control( 'box_align', array(
			'label'     => 'تراز متن',
			'default'   => $this->default_align,
			'type'      => Controls_Manager::CHOOSE,
			'options'   => array(
				'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .szl' => 'text-align: {{VALUE}};' ),
		) );

		$this->add_control( 'box_dir', array(
			'label'        => 'جهت',
			'type'         => Controls_Manager::SELECT,
			'default'      => 'rtl',
			'options'      => array( 'rtl' => 'راست‌به‌چپ (فارسی)', 'ltr' => 'چپ‌به‌راست' ),
			'selectors'    => array( '{{WRAPPER}} .szl' => 'direction: {{VALUE}};' ),
		) );

		$this->end_controls_section();
	}

	/**
	 * Content controls for the shared section heading.
	 * $d = array( 'eyebrow' => .., 'title' => .., 'subtitle' => .. )
	 */
	protected function ctl_heading( $d = array() ) {
		$this->add_control( 'eyebrow', array(
			'label'       => 'برچسب بالای عنوان',
			'type'        => Controls_Manager::TEXT,
			'default'     => isset( $d['eyebrow'] ) ? $d['eyebrow'] : '',
			'label_block' => true,
		) );
		$this->add_control( 'title', array(
			'label'       => 'عنوان',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'default'     => isset( $d['title'] ) ? $d['title'] : '',
			'label_block' => true,
			'description' => 'برای رنگی‌شدن بخشی از عنوان، آن را داخل تگ &lt;span&gt; بگذارید.',
		) );
		$this->add_control( 'subtitle', array(
			'label'       => 'زیرعنوان',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'default'     => isset( $d['subtitle'] ) ? $d['subtitle'] : '',
			'label_block' => true,
		) );
		$this->add_control( 'title_tag', array(
			'label'   => 'تگ عنوان',
			'type'    => Controls_Manager::SELECT,
			'default' => 'h2',
			'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'div' ),
		) );
	}

	/** Matching style controls for ctl_heading(). */
	protected function sty_heading() {
		$this->start_controls_section( 'szl_sty_head', array(
			'label' => 'استایل سرتیتر',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->sty_text( 'eyebrow', 'برچسب', '.szl-eyebrow' );
		$this->add_control( 'eyebrow_bg', array(
			'label'     => 'پس‌زمینه برچسب',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-eyebrow' => 'background: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'eyebrow_pad', array(
			'label'      => 'فاصله داخلی برچسب',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-eyebrow' => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->add_control( 'hr_head_1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'title', 'عنوان', '.szl-sec-title' );
		$this->add_control( 'title_grad', array(
			'label'        => 'گرادیان روی &lt;span&gt; عنوان',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$this->add_control( 'hr_head_2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'subtitle', 'زیرعنوان', '.szl-sec-sub' );

		$this->add_control( 'head_align', array(
			'label'     => 'تراز سرتیتر',
			'type'      => Controls_Manager::CHOOSE,
			'default'   => $this->default_head_align,
			'options'   => array(
				'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .szl-sec-head' => 'text-align: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'head_gap', array(
			'label'      => 'فاصله سرتیتر تا محتوا',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'rem' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 160 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-sec-head' => 'margin-bottom:{{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	/**
	 * Colour + typography + optional margin for one text element.
	 * Call inside an open controls section.
	 */
	protected function sty_text( $key, $label, $sel, $args = array() ) {
		$this->add_control( $key . '_color', array(
			'label'     => $label . ' — رنگ',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel => 'color: {{VALUE}}; -webkit-text-fill-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => $key . '_typo',
			'label'    => $label . ' — تایپوگرافی',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		if ( ! empty( $args['margin'] ) ) {
			$this->add_responsive_control( $key . '_margin', array(
				'label'      => $label . ' — فاصله بیرونی',
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array(
					'{{WRAPPER}} ' . $sel => 'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			) );
		}
	}

	/** Background + border + radius + shadow + padding for a card-like element. */
	protected function sty_box( $key, $label, $sel, $args = array() ) {
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => $key . '_bg',
			'label'    => $label . ' — پس‌زمینه',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => $key . '_border',
			'label'    => $label . ' — حاشیه',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$this->add_responsive_control( $key . '_radius', array(
			'label'      => $label . ' — گردی گوشه',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} ' . $sel => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( $key . '_pad', array(
			'label'      => $label . ' — فاصله داخلی',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', 'rem' ),
			'selectors'  => array(
				'{{WRAPPER}} ' . $sel => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		if ( empty( $args['no_shadow'] ) ) {
			$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
				'name'     => $key . '_shadow',
				'label'    => $label . ' — سایه',
				'selector' => '{{WRAPPER}} ' . $sel,
			) );
		}
	}

	/** Responsive column count for a CSS grid. */
	protected function sty_columns( $key, $label, $sel, $default = 3, $max = 6 ) {
		$this->add_responsive_control( $key . '_cols', array(
			'label'          => $label,
			'type'           => Controls_Manager::SLIDER,
			'range'          => array( 'px' => array( 'min' => 1, 'max' => $max, 'step' => 1 ) ),
			'default'        => array( 'size' => $default ),
			'tablet_default' => array( 'size' => min( 2, $default ) ),
			'mobile_default' => array( 'size' => 1 ),
			'selectors'      => array(
				'{{WRAPPER}} ' . $sel => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
			),
		) );
		$this->add_responsive_control( $key . '_gap', array(
			'label'      => $label . ' — فاصله',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'rem' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'selectors'  => array( '{{WRAPPER}} ' . $sel => 'gap:{{SIZE}}{{UNIT}};' ),
		) );
	}

	/** Full button style block (normal + hover tabs). */
	protected function sty_button( $key, $label, $sel ) {
		$this->add_control( $key . '_head', array(
			'label'     => $label,
			'type'      => Controls_Manager::HEADING,
			'separator' => 'before',
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name'     => $key . '_typo',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$this->add_responsive_control( $key . '_pad', array(
			'label'      => 'فاصله داخلی',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array(
				'{{WRAPPER}} ' . $sel => 'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );
		$this->add_responsive_control( $key . '_radius', array(
			'label'      => 'گردی گوشه',
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array(
				'{{WRAPPER}} ' . $sel => 'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			),
		) );

		$this->start_controls_tabs( $key . '_tabs' );

		$this->start_controls_tab( $key . '_tab_n', array( 'label' => 'عادی' ) );
		$this->add_control( $key . '_color', array(
			'label'     => 'رنگ متن',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => $key . '_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array(
			'name'     => $key . '_border',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => $key . '_shadow',
			'selector' => '{{WRAPPER}} ' . $sel,
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( $key . '_tab_h', array( 'label' => 'هاور' ) );
		$this->add_control( $key . '_h_color', array(
			'label'     => 'رنگ متن',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel . ':hover' => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name'     => $key . '_h_bg',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} ' . $sel . ':hover',
		) );
		$this->add_control( $key . '_h_bcolor', array(
			'label'     => 'رنگ حاشیه',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $sel . ':hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name'     => $key . '_h_shadow',
			'selector' => '{{WRAPPER}} ' . $sel . ':hover',
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();
	}

	/* ==================================================================
	   Render helpers
	   ================================================================== */

	/** Section heading markup shared by most widgets. */
	protected function render_heading( $s ) {
		if ( empty( $s['eyebrow'] ) && empty( $s['title'] ) && empty( $s['subtitle'] ) ) {
			return;
		}
		$tag  = ! empty( $s['title_tag'] ) ? $s['title_tag'] : 'h2';
		$grad = ( ! isset( $s['title_grad'] ) || 'yes' === $s['title_grad'] ) ? ' szl-has-grad' : '';

		echo '<div class="szl-sec-head">';
		if ( ! empty( $s['eyebrow'] ) ) {
			echo '<span class="szl-eyebrow">' . esc_html( $s['eyebrow'] ) . '</span>';
		}
		if ( ! empty( $s['title'] ) ) {
			printf(
				'<%1$s class="szl-sec-title%2$s">%3$s</%1$s>',
				esc_attr( self::tag( $tag ) ),
				esc_attr( $grad ),
				self::kses( $s['title'] ) // phpcs:ignore WordPress.Security.EscapeOutput
			);
		}
		if ( ! empty( $s['subtitle'] ) ) {
			echo '<p class="szl-sec-sub">' . self::kses( $s['subtitle'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
	}

	/** Whitelist of inline tags allowed in every text control. */
	public static function kses( $html, $nl2br = true ) {
		$html = wp_kses( $html, array(
			'span'   => array( 'class' => array() ),
			'b'      => array( 'class' => array() ),
			'strong' => array( 'class' => array() ),
			'i'      => array( 'class' => array() ),
			'em'     => array( 'class' => array() ),
			'br'     => array(),
			'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array(), 'class' => array() ),
			'small'  => array( 'class' => array() ),
		) );
		// Line breaks typed into a textarea should survive into the markup.
		return $nl2br ? nl2br( $html, false ) : $html;
	}

	/** Only allow heading-ish tags from the tag selector. */
	public static function tag( $tag ) {
		$ok = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span' );
		return in_array( $tag, $ok, true ) ? $tag : 'h2';
	}

	/** Render an Elementor link control into href/target/rel attributes. */
	protected function link_attrs( $link ) {
		if ( empty( $link['url'] ) ) {
			return 'href="#"';
		}
		$out = 'href="' . esc_url( $link['url'] ) . '"';
		if ( ! empty( $link['is_external'] ) ) {
			$out .= ' target="_blank"';
		}
		if ( ! empty( $link['nofollow'] ) ) {
			$out .= ' rel="nofollow"';
		}
		return $out;
	}
}
