<?php
/**
 * ویجت «درباره ما» — تصویر گرد + متن + کپسول‌های آمار.
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

class About extends Widget_Base {

	public function get_name() { return 'sazan-about'; }
	public function get_title() { return esc_html__( 'درباره سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-info-circle-o'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'about', 'درباره', 'آمار', 'stats' ); }

	protected function register_controls() {

		/* طرح */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'pro',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (ساده)', 'sazan-core' ),
				'pro'     => esc_html__( 'درخشان / میلیاردی ✨', 'sazan-core' ),
				'lux'     => esc_html__( 'پریمیوم / پنل شیشه‌ای 💎', 'sazan-core' ),
			),
		) );
		$this->add_control( 'reverse', array(
			'label' => esc_html__( 'جابه‌جایی تصویر و آمار', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'description' => esc_html__( 'تصویر سمت چپ، آمار سمت راست', 'sazan-core' ),
		) );
		$this->end_controls_section();

		/* محتوا */
		$this->start_controls_section( 'sec_content', array( 'label' => esc_html__( 'محتوا', 'sazan-core' ) ) );
		$this->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'درباره سازان', 'sazan-core' ) ) );
		$this->add_control( 'en', array( 'label' => esc_html__( 'برچسب انگلیسی', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'SAZAN ABOUT' ) );
		$this->add_control( 'icon', array( 'label' => esc_html__( 'آیکن کنار عنوان', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$this->add_control( 'desc', array(
			'label' => esc_html__( 'متن توضیح', 'sazan-core' ), 'type' => Controls_Manager::WYSIWYG,
			'default' => esc_html__( 'گروه آموزشی سازان به منظور گسترش آموزش های تخصصی و کاربردی و ارائه خدمات پژوهشی و مشاوره ای در راستای چشم انداز خود که خوزستان باید قطب آموزش کشور باشد مجموعه ای غنی از کارشناسان و متخصصین مجرب در زمینه های فنی و تخصصی را به منظور ارائه خدمات و سرویس به سازمان ها و واحدهای تولیدی و خدماتی فراهم آورده است.', 'sazan-core' ),
		) );
		$this->add_control( 'image', array( 'label' => esc_html__( 'تصویر', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ) ) );
		$this->add_control( 'img_shape', array(
			'label' => esc_html__( 'شکل تصویر', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'circle',
			'options' => array( 'circle' => esc_html__( 'دایره', 'sazan-core' ), 'rounded' => esc_html__( 'گرد گوشه', 'sazan-core' ) ),
		) );
		$this->end_controls_section();

		/* آمار */
		$this->start_controls_section( 'sec_stats', array( 'label' => esc_html__( 'آمار (کپسول‌ها)', 'sazan-core' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'num', array( 'label' => esc_html__( 'عدد', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '+۱۸' ) );
		$rep->add_control( 'label', array( 'label' => esc_html__( 'برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'سابقه آموزشی', 'sazan-core' ) ) );
		$this->add_control( 'stats', array(
			'label' => esc_html__( 'آمار', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ num }}} — {{{ label }}}',
			'default' => array(
				array( 'num' => '+۱۸', 'label' => 'سابقه آموزشی' ),
				array( 'num' => '+۲۰', 'label' => 'سابقه مشاوره' ),
				array( 'num' => '+۳۰', 'label' => 'آموزش تخصصی' ),
			),
		) );
		$this->end_controls_section();

		/* استایل کپسول‌های آمار */
		$this->start_controls_section( 'sec_stats_style', array(
			'label' => esc_html__( 'استایل کپسول‌های آمار', 'sazan-core' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'stat_align', array(
			'label'     => esc_html__( 'چینش عمودی محتوا', 'sazan-core' ),
			'type'      => Controls_Manager::SELECT,
			'default'   => 'center',
			'options'   => array(
				'center'        => esc_html__( 'وسط', 'sazan-core' ),
				'flex-start'    => esc_html__( 'بالا', 'sazan-core' ),
				'flex-end'      => esc_html__( 'پایین', 'sazan-core' ),
				'space-between' => esc_html__( 'کشیده (بالا و پایین)', 'sazan-core' ),
			),
			'selectors' => array( '{{WRAPPER}} .sa-stat' => 'justify-content: {{VALUE}};' ),
		) );

		$this->add_responsive_control( 'stat_h', array(
			'label'      => esc_html__( 'ارتفاع کپسول', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 100, 'max' => 400 ) ),
			'default'    => array( 'unit' => 'px', 'size' => 210 ),
			'selectors'  => array( '{{WRAPPER}} .sa-stat' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'stat_w', array(
			'label'      => esc_html__( 'عرض کپسول', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 60, 'max' => 220 ) ),
			'selectors'  => array( '{{WRAPPER}} .sa-stat' => 'width: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'stat_pad', array(
			'label'      => esc_html__( 'فاصله داخلی کپسول', 'sazan-core' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} .sa-stat' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'stat_gap', array(
			'label'      => esc_html__( 'فاصله بین کپسول‌ها', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .sa-stats' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_responsive_control( 'stat_inner_gap', array(
			'label'      => esc_html__( 'فاصله عدد تا برچسب', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .sa-stat' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );

		$this->end_controls_section();

		$this->add_palette_controls();
	}

	private function render_head( $s ) {
		echo '<div class="sa-head">';
		echo '<div class="sa-head-top">';
		echo '<span class="sa-icon">';
		if ( ! empty( $s['icon']['value'] ) ) {
			Icons_Manager::render_icon( $s['icon'], array( 'aria-hidden' => 'true' ) );
		} else {
			echo '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M5 21c0-4 3.5-6 7-6s7 2 7 6"/></svg>';
		}
		echo '</span>';
		if ( ! empty( $s['title'] ) ) {
			echo '<h2 class="sa-title">' . esc_html( $s['title'] ) . '</h2>';
		}
		echo '</div>';
		if ( ! empty( $s['en'] ) ) {
			echo '<div class="sa-en">' . esc_html( $s['en'] ) . '</div>';
		}
		echo '</div>';
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$skin  = in_array( $s['skin'], array( 'classic', 'lux' ), true ) ? $s['skin'] : 'pro';
		$rev   = ( 'yes' === $s['reverse'] ) ? ' is-reverse' : '';
		$shape = ( 'rounded' === $s['img_shape'] ) ? ' shape-rounded' : '';

		echo '<div class="sazan-sec sazan-about skin-' . esc_attr( $skin ) . esc_attr( $rev ) . '">';
		echo '<div class="sa-inner">';

		/* تصویر */
		if ( ! empty( $s['image']['url'] ) ) {
			echo '<div class="sa-photo' . esc_attr( $shape ) . '"><span class="sa-photo-ring" aria-hidden="true"></span><img src="' . esc_url( $s['image']['url'] ) . '" alt=""></div>';
		}

		/* متن */
		echo '<div class="sa-content">';
		$this->render_head( $s );
		if ( ! empty( $s['desc'] ) ) {
			echo '<div class="sa-desc">' . wp_kses_post( $s['desc'] ) . '</div>';
		}
		echo '</div>';

		/* آمار */
		if ( ! empty( $s['stats'] ) ) {
			echo '<div class="sa-stats">';
			foreach ( (array) $s['stats'] as $st ) {
				printf(
					'<div class="sa-stat"><span class="sa-stat-num">%1$s</span><span class="sa-stat-label">%2$s</span></div>',
					esc_html( $st['num'] ),
					esc_html( $st['label'] )
				);
			}
			echo '</div>';
		}

		echo '</div></div>';
	}
}
