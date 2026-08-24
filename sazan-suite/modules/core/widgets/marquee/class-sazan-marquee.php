<?php
/**
 * ویجت نوار چرخان (Marquee) — چند آیتمی با لینک.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Marquee extends Widget_Base {

	public function get_name() { return 'sazan-marquee'; }
	public function get_title() { return esc_html__( 'نوار چرخان سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-animation-text'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'marquee', 'نوار', 'چرخان', 'متن' ); }

	protected function register_controls() {

		$this->start_controls_section( 'sec_content', array( 'label' => esc_html__( 'محتوا', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'طرح نمایش', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (نوار ساده)', 'sazan-core' ),
				'pro'     => esc_html__( 'حرفه‌ای (چیپ‌های شیشه‌ای)', 'sazan-core' ),
			),
		) );
		$rep = new Repeater();
		$rep->add_control( 'text', array( 'label' => esc_html__( 'متن', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دوره حکمرانی بر بازار', 'sazan-core' ) ) );
		$rep->add_control( 'link', array( 'label' => esc_html__( 'لینک (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'items', array(
			'label' => esc_html__( 'آیتم‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ text }}}',
			'default' => array(
				array( 'text' => 'دوره حکمرانی بر بازار' ),
				array( 'text' => 'کارگاه فروش حرفه‌ای' ),
				array( 'text' => 'وبینار رایگان کارآفرینی' ),
			),
		) );
		$this->add_control( 'separator', array( 'label' => esc_html__( 'جداکننده', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '✦' ) );
		$this->add_control( 'repeat', array( 'label' => esc_html__( 'تکرار لیست (برای پر شدن عرض)', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 12 ) );
		$this->add_control( 'direction', array(
			'label' => esc_html__( 'جهت حرکت', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'rev',
			'options' => array( '' => esc_html__( 'به راست', 'sazan-core' ), 'rev' => esc_html__( 'به چپ', 'sazan-core' ) ),
		) );
		$this->add_control( 'speed', array(
			'label' => esc_html__( 'مدت یک دور (ثانیه)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 5, 'max' => 80 ) ), 'default' => array( 'size' => 26 ),
			'selectors' => array( '{{WRAPPER}} .sazan-track' => 'animation-duration: {{SIZE}}s;' ),
		) );
		$this->add_control( 'pause_hover', array( 'label' => esc_html__( 'توقف روی هاور', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'prefix_class' => 'sazan-mq-pause-' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'sec_style', array( 'label' => esc_html__( 'استایل', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name' => 'mq_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-marquee',
			'fields_options' => array(
				'background'     => array( 'default' => 'gradient' ),
				'color'          => array( 'default' => '#0c1c28' ),
				'color_b'        => array( 'default' => '#10222f' ),
				'gradient_angle' => array( 'default' => array( 'unit' => 'deg', 'size' => 120 ) ),
			),
		) );
		$this->add_control( 'mq_color', array( 'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-track span, {{WRAPPER}} .sazan-track a' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'mq_typo', 'selector' => '{{WRAPPER}} .sazan-track span, {{WRAPPER}} .sazan-track a' ) );
		$this->add_responsive_control( 'mq_gap', array(
			'label' => esc_html__( 'فاصله بین آیتم‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 10, 'max' => 120 ) ), 'default' => array( 'size' => 46 ),
			'selectors' => array( '{{WRAPPER}}' => '--sz-mq-gap: {{SIZE}}px;' ),
		) );
		$this->add_responsive_control( 'mq_pad', array(
			'label' => esc_html__( 'فاصله عمودی', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 11 ),
			'selectors' => array( '{{WRAPPER}} .sazan-track' => 'padding-top: {{SIZE}}px; padding-bottom: {{SIZE}}px;' ),
		) );

		$this->add_control( 'h_pro', array( 'label' => esc_html__( 'طرح حرفه‌ای (چیپ)', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => array( 'skin' => 'pro' ) ) );
		$this->add_control( 'chip_bg', array( 'label' => esc_html__( 'پس‌زمینه چیپ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.06)', 'selectors' => array( '{{WRAPPER}} .skin-pro .sazan-mq-item' => 'background: {{VALUE}};' ), 'condition' => array( 'skin' => 'pro' ) ) );
		$this->add_control( 'chip_border', array( 'label' => esc_html__( 'رنگ حاشیه چیپ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.14)', 'selectors' => array( '{{WRAPPER}} .skin-pro .sazan-mq-item' => 'border-color: {{VALUE}};' ), 'condition' => array( 'skin' => 'pro' ) ) );
		$this->add_control( 'dot_color', array( 'label' => esc_html__( 'رنگ نقطه‌ی درخشان', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .skin-pro .sazan-mq-item::before' => 'background: {{VALUE}}; box-shadow:0 0 0 4px rgba(0,182,241,.18), 0 0 12px {{VALUE}};' ), 'condition' => array( 'skin' => 'pro' ) ) );
		$this->add_responsive_control( 'chip_radius', array( 'label' => esc_html__( 'گردی چیپ', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 999 ) ), 'default' => array( 'size' => 999 ), 'selectors' => array( '{{WRAPPER}} .skin-pro .sazan-mq-item' => 'border-radius: {{SIZE}}px;' ), 'condition' => array( 'skin' => 'pro' ) ) );
		$this->add_control( 'edge_fade', array( 'label' => esc_html__( 'محو شدن لبه‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'prefix_class' => 'sazan-mq-fade-', 'condition' => array( 'skin' => 'pro' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$rev  = ( 'rev' === $s['direction'] ) ? ' rev' : '';
		$skin = ( 'pro' === $s['skin'] ) ? ' skin-pro' : ' skin-classic';
		$rep  = max( 1, (int) $s['repeat'] );
		$sep  = $s['separator'];

		$one = '';
		for ( $r = 0; $r < $rep; $r++ ) {
			foreach ( (array) $s['items'] as $it ) {
				$txt = isset( $it['text'] ) ? $it['text'] : '';
				if ( '' === trim( (string) $txt ) ) { continue; }
				$url = ! empty( $it['link']['url'] ) ? $it['link']['url'] : '';
				if ( $url ) {
					$one .= '<a class="sazan-mq-item" data-sep="' . esc_attr( $sep ) . '" href="' . esc_url( $url ) . '">' . esc_html( $txt ) . '</a>';
				} else {
					$one .= '<span class="sazan-mq-item" data-sep="' . esc_attr( $sep ) . '">' . esc_html( $txt ) . '</span>';
				}
			}
		}

		// CSS بحرانی اینلاین فقط یک‌بار در صفحه (جلوگیری از دیرآمدن/FOUC)
		static $crit = false;
		if ( ! $crit ) {
			$crit = true;
			echo '<style id="sazan-mq-critical">'
				. '.sazan-marquee{overflow:hidden;width:100%}'
				. '.sazan-marquee .sazan-track{display:flex;flex-wrap:nowrap;white-space:nowrap;width:max-content;animation:sazan-slide 26s linear infinite;will-change:transform;direction:ltr}'
				. '.sazan-marquee .sazan-mq-group{display:flex;align-items:center;flex:0 0 auto;gap:var(--sz-mq-gap,46px);padding-inline-start:var(--sz-mq-gap,46px)}'
				. '.sazan-marquee .sazan-mq-item{direction:rtl}'
				. '.sazan-marquee.rev .sazan-track{animation-direction:reverse}'
				. '.sazan-mq-pause-yes .sazan-marquee:hover .sazan-track{animation-play-state:paused}'
				. '@keyframes sazan-slide{from{transform:translateX(0)}to{transform:translateX(-50%)}}'
				. '</style>';
		}

		$speed = isset( $s['speed']['size'] ) ? (float) $s['speed']['size'] : 26;

		// دو گروهِ کاملاً همسان ⇒ translateX(-50%) دقیقاً عرض یک گروه ⇒ حلقهٔ پیوسته بی‌درز
		printf(
			'<div class="sazan-marquee%1$s%2$s" data-sz-speed="%4$s"><div class="sazan-track"><div class="sazan-mq-group">%3$s</div><div class="sazan-mq-group" aria-hidden="true">%3$s</div></div></div>',
			esc_attr( $rev ),
			esc_attr( $skin ),
			$one,
			esc_attr( $speed )
		);
	}
}
