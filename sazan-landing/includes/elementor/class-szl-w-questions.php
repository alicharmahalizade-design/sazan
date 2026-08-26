<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** گرید سؤال‌های تصمیم‌گیری + بلوک سؤال محوری. */
class SZL_W_Questions extends SZL_Widget_Base {

	public function get_name() { return 'szl_questions'; }
	public function get_title() { return 'لندینگ: گرید سؤال‌ها'; }
	public function get_icon() { return 'eicon-help-o'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow'  => 'THE BIG QUESTION',
			'title'    => 'سؤال بزرگ بسیاری از صاحبان کسب‌وکار',
			'subtitle' => 'هر روز صدها تصمیم در یک کسب‌وکار گرفته می‌شود',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_items', array( 'label' => 'سؤال‌ها' ) );
		$r = new Repeater();
		$r->add_control( 'text', array(
			'label'       => 'سؤال',
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
		) );
		$qs = array(
			'قیمت را تغییر بدهیم یا نه؟',
			'چطور از رقبا متمایز شویم؟',
			'چرا فروش به عدد موردنظر نمی‌رسد؟',
			'چرا فروشنده‌ها نتیجه نمی‌گیرند؟',
			'چطور مشتری را متقاعد کنیم؟',
			'کدام بخش سازمان جلوی رشد را گرفته؟',
			'روی چه چیزی سرمایه‌گذاری کنیم؟',
			'چه چیزی را تغییر بدهیم؟',
		);
		$this->add_control( 'items', array(
			'label'       => 'سؤال‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array_map( function ( $t ) { return array( 'text' => $t ); }, $qs ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_pivot', array( 'label' => 'بلوک سؤال محوری' ) );
		$this->add_control( 'pivot_pre', array(
			'label'       => 'متن بالا',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'و مدیر وسط تمام این سؤال‌ها باید تصمیم بگیرد. اما یک سؤال مهم‌تر وجود دارد:',
		) );
		$this->add_control( 'pivot_q', array(
			'label'       => 'سؤال محوری',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
			'default'     => 'آیا شما کسب‌وکارتان را هدایت می‌کنید<br><span>یا اتفاقات روزمره کسب‌وکار شما را هدایت می‌کنند؟</span>',
		) );
		$this->add_control( 'pivot_post', array(
			'label'       => 'متن پایین',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'اینجاست که تفاوت میان «داشتن یک کسب‌وکار» و «حکمرانی بر بازار» آغاز می‌شود.',
		) );
		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_grid', array(
			'label' => 'استایل سؤال‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'q', 'تعداد ستون', '.szl-qgrid', 4, 6 );
		$this->sty_text( 'q', 'متن سؤال', '.szl-qgrid li' );
		$this->sty_box( 'q', 'کادر سؤال', '.szl-qgrid li', array( 'no_shadow' => true ) );
		$this->add_control( 'q_hover', array(
			'label'     => 'رنگ حاشیه در هاور',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-qgrid li:hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_pivot', array(
			'label' => 'استایل بلوک محوری',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_box( 'pivot', 'کادر', '.szl-pivot' );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'ppre', 'متن بالا', '.szl-pivot-pre' );
		$this->sty_text( 'pq', 'سؤال محوری', '.szl-pivot-q' );
		$this->add_control( 'pq_grad', array(
			'label'        => 'گرادیان روی &lt;span&gt;',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->sty_text( 'ppost', 'متن پایین', '.szl-pivot-post' );
		$this->add_responsive_control( 'pivot_maxw', array(
			'label'      => 'حداکثر عرض',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 300, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-pivot' => 'max-width:{{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( ! empty( $s['items'] ) ) {
			echo '<ul class="szl-qgrid">';
			foreach ( $s['items'] as $q ) {
				if ( empty( $q['text'] ) ) {
					continue;
				}
				echo '<li>' . self::kses( $q['text'] ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</ul>';
		}

		if ( empty( $s['pivot_pre'] ) && empty( $s['pivot_q'] ) && empty( $s['pivot_post'] ) ) {
			return;
		}
		$grad = 'yes' === ( $s['pq_grad'] ?? 'yes' ) ? ' szl-has-grad' : '';

		echo '<div class="szl-pivot">';
		if ( ! empty( $s['pivot_pre'] ) ) {
			echo '<p class="szl-pivot-pre">' . self::kses( $s['pivot_pre'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( ! empty( $s['pivot_q'] ) ) {
			echo '<p class="szl-pivot-q' . esc_attr( $grad ) . '">' . self::kses( $s['pivot_q'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( ! empty( $s['pivot_post'] ) ) {
			echo '<p class="szl-pivot-post">' . self::kses( $s['pivot_post'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div>';
	}
}
