<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** مسیر اجرایی: مراحل شماره‌دار + بلوک «فاصله میان دانستن و اجرا». */
class SZL_W_Flow extends SZL_Widget_Base {

	public function get_name() { return 'szl_flow'; }
	public function get_title() { return 'لندینگ: مسیر اجرایی'; }
	public function get_icon() { return 'eicon-flow'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow' => 'EXECUTION PATH',
			'title'   => 'مسیر اجرایی دوره',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_steps', array( 'label' => 'مراحل' ) );

		$r = new Repeater();
		$r->add_control( 'text', array(
			'label'       => 'عنوان مرحله',
			'type'        => Controls_Manager::TEXT,
			'default'     => 'اسکن کسب‌وکار',
			'label_block' => true,
		) );

		$steps = array( 'اسکن کسب‌وکار', 'کشف باگ‌ها', 'تعیین اولویت‌ها', 'اصلاح', 'اقدام', 'پیاده‌سازی' );
		$this->add_control( 'steps', array(
			'label'       => 'مراحل',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array_map( function ( $t ) { return array( 'text' => $t ); }, $steps ),
		) );

		$this->add_control( 'numbers', array(
			'label'        => 'نمایش شماره مراحل',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
			'description'  => 'شماره‌ها ترتیب واقعی مسیر را نشان می‌دهند؛ اگر محتوای شما ترتیبی نیست خاموشش کنید.',
		) );
		$this->add_control( 'digits', array(
			'label'     => 'نوع ارقام',
			'type'      => Controls_Manager::SELECT,
			'default'   => 'fa',
			'options'   => array( 'fa' => 'فارسی (۱۲۳)', 'en' => 'لاتین (123)' ),
			'condition' => array( 'numbers' => 'yes' ),
		) );
		$this->add_control( 'connector', array(
			'label'        => 'خط اتصال مراحل',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'c_note', array( 'label' => 'یادداشت پایانی' ) );
		$this->add_control( 'note', array(
			'label'       => 'متن',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 5,
			'label_block' => true,
			'default'     => 'قرار نیست بعد از هر جلسه با یک دفتر پر از نکته به محل کار برگردید و ندانید از کجا باید شروع کنید. آموخته‌های دوره با مسائل واقعی کسب‌وکار شما روبه‌رو می‌شوند تا فاصله میان این دو جمله کمتر شود:',
		) );
		$this->add_control( 'gap_a', array(
			'label'   => 'جمله اول',
			'type'    => Controls_Manager::TEXT,
			'default' => '«فهمیدم باید چه کار کنم»',
		) );
		$this->add_control( 'gap_b', array(
			'label'   => 'جمله دوم',
			'type'    => Controls_Manager::TEXT,
			'default' => '«انجامش دادم»',
		) );
		$this->add_control( 'gap_arrow', array(
			'label'   => 'نشانه میانی',
			'type'    => Controls_Manager::TEXT,
			'default' => '←',
		) );
		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_steps', array(
			'label' => 'استایل مراحل',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'step', 'تعداد ستون', '.szl-flow', 6, 8 );

		$this->add_responsive_control( 'bullet_size', array(
			'label'      => 'اندازه دایره شماره',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 28, 'max' => 96 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-flow-n' => 'width:{{SIZE}}px;height:{{SIZE}}px;',
				'{{WRAPPER}} .szl-flow::before' => 'top:calc({{SIZE}}px / 2 + 6px);',
			),
		) );
		$this->sty_text( 'bullet', 'شماره', '.szl-flow-n' );
		$this->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
			'name'     => 'bullet_bg',
			'label'    => 'دایره — پس‌زمینه',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .szl-flow-n',
		) );
		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => 'bullet_border',
			'label'    => 'دایره — حاشیه',
			'selector' => '{{WRAPPER}} .szl-flow-n',
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'steptxt', 'عنوان مرحله', '.szl-flow-t', array( 'margin' => true ) );
		$this->add_control( 'conn_color', array(
			'label'     => 'رنگ خط اتصال',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-flow::before' => 'background: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_note', array(
			'label' => 'استایل یادداشت',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'note', 'متن یادداشت', '.szl-note p', array( 'margin' => true ) );
		$this->add_responsive_control( 'note_maxw', array(
			'label'      => 'حداکثر عرض',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 300, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-note' => 'max-width:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'gapa', 'جمله اول', '.szl-gap-a' );
		$this->sty_box( 'gapa', 'جمله اول', '.szl-gap-a', array( 'no_shadow' => true ) );
		$this->sty_text( 'gapb', 'جمله دوم', '.szl-gap-b' );
		$this->sty_box( 'gapb', 'جمله دوم', '.szl-gap-b', array( 'no_shadow' => true ) );
		$this->sty_text( 'gaparrow', 'نشانه میانی', '.szl-gap-arrow' );
		$this->end_controls_section();
	}

	private function fa_num( $n, $mode ) {
		if ( 'fa' !== $mode ) {
			return (string) $n;
		}
		return str_replace( array( '0','1','2','3','4','5','6','7','8','9' ), array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ), (string) $n );
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( ! empty( $s['steps'] ) ) {
			$conn = 'yes' === ( $s['connector'] ?? 'yes' ) ? ' szl-has-conn' : '';
			$nums = 'yes' === ( $s['numbers'] ?? 'yes' );
			echo '<ol class="szl-flow' . esc_attr( $conn ) . '">';
			$i = 1;
			foreach ( $s['steps'] as $st ) {
				if ( empty( $st['text'] ) ) {
					continue;
				}
				echo '<li>';
				if ( $nums ) {
					echo '<span class="szl-flow-n">' . esc_html( $this->fa_num( $i, $s['digits'] ?? 'fa' ) ) . '</span>';
				}
				echo '<span class="szl-flow-t">' . self::kses( $st['text'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</li>';
				$i++;
			}
			echo '</ol>';
		}

		$has_gap = ! empty( $s['gap_a'] ) || ! empty( $s['gap_b'] );
		if ( empty( $s['note'] ) && ! $has_gap ) {
			return;
		}

		echo '<div class="szl-note">';
		if ( ! empty( $s['note'] ) ) {
			echo '<p>' . self::kses( $s['note'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( $has_gap ) {
			echo '<div class="szl-gap-pair">';
			if ( ! empty( $s['gap_a'] ) ) {
				echo '<span class="szl-gap-a">' . esc_html( $s['gap_a'] ) . '</span>';
			}
			if ( ! empty( $s['gap_arrow'] ) ) {
				echo '<span class="szl-gap-arrow" aria-hidden="true">' . esc_html( $s['gap_arrow'] ) . '</span>';
			}
			if ( ! empty( $s['gap_b'] ) ) {
				echo '<span class="szl-gap-b">' . esc_html( $s['gap_b'] ) . '</span>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
}
