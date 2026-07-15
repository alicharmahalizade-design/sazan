<?php
/**
 * ویجت لیستینگ دوره‌ها به همراه فیلتر.
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

class Courses extends Widget_Base {

	public function get_name() { return 'sazan-courses'; }
	public function get_title() { return esc_html__( 'دوره‌های سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-products'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'courses', 'دوره', 'فیلتر' ); }

	protected function register_controls() {

		/* سرتیتر */
		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_section_header_controls( 'Courses', esc_html__( 'دوره های درحال برگزاری', 'sazan-core' ) );
		$this->end_controls_section();

		/* طرح */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (تصویر بالا)', 'sazan-core' ),
				'pro'     => esc_html__( 'حرفه‌ای (پوستری/روی تصویر)', 'sazan-core' ),
				'lux'     => esc_html__( 'پوستری لاکچری / درخشان ✨', 'sazan-core' ),
			),
		) );
		$this->end_controls_section();

		/* فیلترها */
		$this->start_controls_section( 'sec_filters', array( 'label' => esc_html__( 'فیلترها', 'sazan-core' ) ) );
		$this->add_control( 'show_filters', array(
			'label' => esc_html__( 'نمایش فیلترها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		) );
		$frep = new Repeater();
		$frep->add_control( 'label', array( 'label' => esc_html__( 'برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'همه', 'sazan-core' ) ) );
		$frep->add_control( 'key', array( 'label' => esc_html__( 'کلید فیلتر', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'description' => esc_html__( 'برای «همه» خالی بگذارید.', 'sazan-core' ) ) );
		$frep->add_control( 'active', array( 'label' => esc_html__( 'فعال پیش‌فرض', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes' ) );
		$this->add_control( 'filters', array(
			'label' => esc_html__( 'فیلترها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $frep->get_controls(), 'title_field' => '{{{ label }}}',
			'default' => array(
				array( 'label' => 'همه', 'key' => '', 'active' => 'yes' ),
				array( 'label' => 'مدیریت', 'key' => 'modiriat', 'active' => '' ),
				array( 'label' => 'کارآفرینی', 'key' => 'karafarini', 'active' => '' ),
				array( 'label' => 'منابع انسانی', 'key' => 'hr', 'active' => '' ),
			),
		) );
		$this->end_controls_section();

		/* دوره‌ها */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'دوره‌ها', 'sazan-core' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دوره حکمرانی بر بازار', 'sazan-core' ) ) );
		$rep->add_control( 'desc', array( 'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'default' => esc_html__( 'در دنیای کسب و کار امروز که تغییر و تحول به سرعت در حال وقوع است، توانمندی‌های فروش و مدیریت فروش و...', 'sazan-core' ) ) );
		$rep->add_control( 'image', array( 'label' => esc_html__( 'تصویر', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$rep->add_control( 'ribbon', array( 'label' => esc_html__( 'برچسب گوشه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'حکمرانی بر بازار', 'sazan-core' ) ) );
		$rep->add_control( 'tutor', array( 'label' => esc_html__( 'استاد', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'استاد: عباس شانه سازان', 'sazan-core' ) ) );
		$rep->add_control( 'tag1', array( 'label' => esc_html__( 'تگ ۱', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'مدیریت', 'sazan-core' ) ) );
		$rep->add_control( 'tag2', array( 'label' => esc_html__( 'تگ ۲', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'حضوری', 'sazan-core' ) ) );
		$rep->add_control( 'start', array( 'label' => esc_html__( 'تاریخ شروع', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '۱۴۰۵/۰۲/۱۲' ) );
		$rep->add_control( 'cat', array( 'label' => esc_html__( 'کلید دسته (برای فیلتر)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'modiriat' ) );
		$rep->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );

		$this->add_control( 'items', array(
			'label' => esc_html__( 'دوره‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array(
				array( 'tag2' => 'حضوری', 'start' => '۱۴۰۵/۰۲/۱۲', 'cat' => 'modiriat' ),
				array( 'tag2' => 'آنلاین', 'start' => '۱۴۰۵/۰۲/۱۸', 'cat' => 'modiriat' ),
				array( 'tag2' => 'حضوری', 'start' => '۱۴۰۵/۰۲/۲۱', 'cat' => 'modiriat' ),
			),
		) );
		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '3', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array( '{{WRAPPER}} .sazan-course-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->end_controls_section();

		/* پالت رنگ مشترک */
		$this->add_palette_controls();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$skin = in_array( $s['skin'], array( 'pro', 'lux' ), true ) ? $s['skin'] : 'classic';
		echo '<div class="sazan-sec sazan-courses skin-' . esc_attr( $skin ) . '">';
		$this->render_section_header( $s );

		/* فیلترها */
		if ( 'yes' === $s['show_filters'] && ! empty( $s['filters'] ) ) {
			echo '<div class="sazan-filters">';
			foreach ( $s['filters'] as $f ) {
				$active = ( 'yes' === $f['active'] ) ? ' active' : '';
				printf(
					'<span class="sazan-chip%1$s" data-filter="%2$s">%3$s</span>',
					esc_attr( $active ),
					esc_attr( $f['key'] ),
					esc_html( $f['label'] )
				);
			}
			echo '</div>';
		}

		/* کارت‌ها */
		$is_carousel = ( 'lux' === $skin );
		if ( $is_carousel ) {
			echo '<div class="sazan-course-carousel" data-sz-cc>';
			echo '<button class="sz-cc-arrow prev" type="button" aria-label="' . esc_attr__( 'قبلی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg></button>';
			echo '<div class="sazan-cc-viewport">';
		} else {
			echo '<div class="sazan-course-grid">';
		}
		foreach ( (array) $s['items'] as $i ) {
			$img = ! empty( $i['image']['url'] ) ? '<img src="' . esc_url( $i['image']['url'] ) . '" alt="">' : '';
			$link_open = ''; $link_close = '';
			if ( ! empty( $i['link']['url'] ) ) {
				$link_open = '<a class="sazan-course-link" href="' . esc_url( $i['link']['url'] ) . '">';
				$link_close = '</a>';
			}
			$tags = '';
			foreach ( array( $i['tag1'], $i['tag2'] ) as $t ) {
				if ( '' !== trim( (string) $t ) ) { $tags .= '<span>' . esc_html( $t ) . '</span>'; }
			}
			printf(
				'<article class="sazan-course-card" data-cat="%1$s">%2$s
					<div class="sazan-course-media">%3$s<div class="ribbon">%4$s</div><div class="tutor">%5$s</div></div>
					<div class="sazan-course-body">
						<div class="sazan-course-tags">%6$s</div>
						<h3>%7$s</h3><p>%8$s</p>
						<div class="sazan-course-meta"><span>%9$s <b>%10$s</b></span><span>%11$s</span></div>
					</div>%12$s
				</article>',
				esc_attr( $i['cat'] ),
				$link_open,
				$img,
				esc_html( $i['ribbon'] ),
				esc_html( $i['tutor'] ),
				$tags,
				esc_html( $i['title'] ),
				esc_html( $i['desc'] ),
				esc_html__( 'تاریخ شروع:', 'sazan-core' ),
				esc_html( $i['start'] ),
				esc_html( $i['tutor'] ),
				$link_close
			);
		}
		if ( $is_carousel ) {
			echo '</div>'; // viewport
			echo '<button class="sz-cc-arrow next" type="button" aria-label="' . esc_attr__( 'بعدی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg></button>';
			echo '<div class="sazan-cc-dots" aria-hidden="true"></div>';
			echo '</div>'; // carousel
			echo '</div>'; // sazan-sec
		} else {
			echo '</div></div>';
		}
	}
}
