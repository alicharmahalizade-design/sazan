<?php
/**
 * ویجت تقویم آموزشی (ردیفی).
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

class Calendar extends Widget_Base {

	public function get_name() { return 'sazan-calendar'; }
	public function get_title() { return esc_html__( 'تقویم آموزشی سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-calendar'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'calendar', 'تقویم', 'کلاس' ); }

	protected function register_controls() {

		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (ردیفی)', 'sazan-core' ),
				'pro'     => esc_html__( 'حرفه‌ای (کارت‌های شیشه‌ای)', 'sazan-core' ),
				'lux'     => esc_html__( 'ردیفی لاکچری / درخشان ✨', 'sazan-core' ),
			),
		) );
		$this->add_control( 'cols', array(
			'label' => esc_html__( 'تعداد ستون (طرح حرفه‌ای)', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => '3',
			'options' => array( '1' => '1', '2' => '2', '3' => '3' ),
			'condition' => array( 'skin' => 'pro' ),
			'selectors' => array( '{{WRAPPER}} .sazan-calendar.skin-pro .sazan-cal-grid' => 'grid-template-columns:repeat({{VALUE}},1fr);' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_section_header_controls( 'Calendar', esc_html__( 'تقویم آموزشی سازان', 'sazan-core' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'ردیف‌ها', 'sazan-core' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان کلاس', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'کلاس فن مذاکره', 'sazan-core' ) ) );
		$rep->add_control( 'tutor', array( 'label' => esc_html__( 'استاد', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'استاد عباس شانه سازان', 'sazan-core' ) ) );
		$rep->add_control( 'date', array( 'label' => esc_html__( 'تاریخ', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۲ اردیبهشت ۱۴۰۵', 'sazan-core' ) ) );
		$rep->add_control( 'status', array( 'label' => esc_html__( 'وضعیت', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'درحال ثبت نام', 'sazan-core' ) ) );
		$rep->add_control( 'btn', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ثبت نام', 'sazan-core' ) ) );
		$rep->add_control( 'action_type', array(
			'label'   => esc_html__( 'رفتار دکمه', 'sazan-core' ),
			'type'    => Controls_Manager::SELECT, 'default' => 'link',
			'options' => array(
				'link'  => esc_html__( 'لینک دلخواه', 'sazan-core' ),
				'popup' => esc_html__( 'باز کردن فرم (پاپ‌آپ)', 'sazan-core' ),
				'page'  => esc_html__( 'رفتن به صفحه‌ی فرم', 'sazan-core' ),
			),
		) );
		$rep->add_control( 'link', array(
			'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL,
			'default' => array( 'url' => '#' ),
			'condition' => array( 'action_type' => 'link' ),
		) );
		$rep->add_control( 'form_id', array(
			'label'       => esc_html__( 'انتخاب فرم', 'sazan-core' ),
			'type'        => Controls_Manager::SELECT2, 'options' => $this->forms_options(),
			'description' => esc_html__( 'فرم را در «فرم‌ساز سازان» بسازید.', 'sazan-core' ),
			'condition'   => array( 'action_type' => array( 'popup', 'page' ) ),
		) );
		$rep->add_control( 'form_page', array(
			'label' => esc_html__( 'صفحه‌ی حاوی فرم', 'sazan-core' ), 'type' => Controls_Manager::URL,
			'description' => esc_html__( 'صفحه‌ای که شورت‌کد فرم در آن قرار دارد.', 'sazan-core' ),
			'condition'   => array( 'action_type' => 'page' ),
		) );
		$this->add_control( 'items', array(
			'label' => esc_html__( 'ردیف‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array( array(), array(), array() ),
		) );
		$this->end_controls_section();

		$this->add_palette_controls();
	}

	/** فهرست فرم‌های ساخته‌شده برای انتخاب در ریپیتر. */
	private function forms_options() {
		$out = array( '' => esc_html__( '— انتخاب فرم —', 'sazan-core' ) );
		if ( class_exists( '\\Sazan\\Form_CPT' ) ) {
			$forms = get_posts( array(
				'post_type'   => \Sazan\Form_CPT::POST_TYPE,
				'numberposts' => -1,
				'post_status' => 'publish',
				'orderby'     => 'title',
				'order'       => 'ASC',
			) );
			foreach ( $forms as $f ) {
				$out[ $f->ID ] = $f->post_title ?: ( '#' . $f->ID );
			}
		}
		return $out;
	}

	/** ساخت دکمه‌ی ثبت‌نام بر اساس رفتار انتخابی (لینک/پاپ‌آپ/صفحه). */
	private function cal_button( $r, $base_class, $inner ) {
		$action = $r['action_type'] ?? 'link';

		if ( 'popup' === $action && ! empty( $r['form_id'] ) && class_exists( '\\Sazan\\Form_Engine' ) ) {
			\Sazan\Form_Engine::instance()->queue_modal( (int) $r['form_id'] );
			return sprintf(
				'<button type="button" class="%1$s szf-trigger" data-sz-form="%2$d">%3$s</button>',
				esc_attr( $base_class ), (int) $r['form_id'], $inner
			);
		}

		if ( 'page' === $action && ! empty( $r['form_page']['url'] ) ) {
			$open = ! empty( $r['form_page']['is_external'] ) ? ' target="_blank" rel="noopener"' : '';
			return sprintf( '<a class="%1$s" href="%2$s"%3$s>%4$s</a>', esc_attr( $base_class ), esc_url( $r['form_page']['url'] ), $open, $inner );
		}

		$href = ! empty( $r['link']['url'] ) ? esc_url( $r['link']['url'] ) : '#';
		$open = ! empty( $r['link']['is_external'] ) ? ' target="_blank" rel="noopener"' : '';
		return sprintf( '<a class="%1$s" href="%2$s"%3$s>%4$s</a>', esc_attr( $base_class ), $href, $open, $inner );
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$skin = in_array( $s['skin'], array( 'pro', 'lux' ), true ) ? $s['skin'] : 'classic';
		echo '<div class="sazan-sec sazan-calendar skin-' . esc_attr( $skin ) . '">';
		$this->render_section_header( $s );

		if ( 'pro' === $skin ) {
			$this->render_pro( $s );
		} elseif ( 'lux' === $skin ) {
			$this->render_lux( $s );
		} else {
			$this->render_classic( $s );
		}
		echo '</div>';
	}

	private function render_classic( $s ) {
		echo '<div class="sazan-cal-table">';
		foreach ( (array) $s['items'] as $r ) {
			$btn = $this->cal_button( $r, 'sazan-btn sazan-btn-orange sm', esc_html( $r['btn'] ) );
			printf(
				'<div class="sazan-cal-row">
					<h4>%1$s</h4>
					<div class="c">%2$s</div>
					<div class="c">%3$s</div>
					<div class="status">%4$s</div>
					%5$s
				</div>',
				esc_html( $r['title'] ),
				esc_html( $r['tutor'] ),
				esc_html( $r['date'] ),
				esc_html( $r['status'] ),
				$btn
			);
		}
		echo '</div>';
	}

	private function render_pro( $s ) {
		echo '<div class="sazan-cal-grid">';
		foreach ( (array) $s['items'] as $r ) {
			$inner = '<span>' . esc_html( $r['btn'] ) . '</span><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>';
			$btn   = $this->cal_button( $r, 'cc-btn', $inner );
			printf(
				'<article class="sazan-cal-card">
					<span class="cc-glow" aria-hidden="true"></span>
					<div class="cc-top">
						<span class="cc-date"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>%3$s</span>
						<span class="cc-status"><span class="cc-dot"></span>%4$s</span>
					</div>
					<h4 class="cc-title">%1$s</h4>
					<div class="cc-tutor"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>%2$s</div>
					%5$s
				</article>',
				esc_html( $r['title'] ),
				esc_html( $r['tutor'] ),
				esc_html( $r['date'] ),
				esc_html( $r['status'] ),
				$btn
			);
		}
		echo '</div>';
	}

	private function render_lux( $s ) {
		echo '<div class="sazan-cal-rail">';
		foreach ( (array) $s['items'] as $r ) {
			$inner = '<span>' . esc_html( $r['btn'] ) . '</span><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>';
			$btn   = $this->cal_button( $r, 'cl-btn', $inner );
			printf(
				'<article class="sazan-cal-lux">
					<span class="cl-glow" aria-hidden="true"></span>
					<span class="cl-bar" aria-hidden="true"></span>
					<div class="cl-date">
						<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
						<span>%3$s</span>
					</div>
					<div class="cl-main">
						<h4 class="cl-title">%1$s</h4>
						<div class="cl-tutor"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>%2$s</div>
					</div>
					<span class="cl-status"><span class="cl-dot"></span>%4$s</span>
					%5$s
				</article>',
				esc_html( $r['title'] ),
				esc_html( $r['tutor'] ),
				esc_html( $r['date'] ),
				esc_html( $r['status'] ),
				$btn
			);
		}
		echo '</div>';
	}
}
