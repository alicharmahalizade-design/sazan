<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** ساختار دو لایه: آموزش/راهبری و کوچینگ/اجرا. */
class SZL_W_Layers extends SZL_Widget_Base {

	public function get_name() { return 'szl_layers'; }
	public function get_title() { return 'لندینگ: ساختار لایه‌ای'; }
	public function get_icon() { return 'eicon-parallax'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow' => 'TWO-LAYER STRUCTURE',
			'title'   => 'ساختار دو لایه «حکمرانی بر بازار»',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_layers', array( 'label' => 'لایه‌ها' ) );

		$r = new Repeater();
		$r->add_control( 'badge', array( 'label' => 'برچسب لایه', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'title', array( 'label' => 'عنوان لایه', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'lead', array( 'label' => 'خط معرفی', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'label_block' => true ) );
		$r->add_control( 'intro', array( 'label' => 'متن قبل از لیست', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'items', array(
			'label'       => 'موارد (هر مورد در یک خط)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 8,
			'label_block' => true,
		) );
		$r->add_control( 'style', array(
			'label'   => 'نمایش موارد',
			'type'    => Controls_Manager::SELECT,
			'default' => 'check',
			'options' => array( 'check' => 'لیست تیک‌دار', 'steps' => 'مراحل شماره‌دار' ),
		) );
		$r->add_control( 'accent', array(
			'label'   => 'رنگ تأکید لایه',
			'type'    => Controls_Manager::SELECT,
			'default' => 'primary',
			'options' => array( 'primary' => 'رنگ اصلی', 'secondary' => 'رنگ مکمل' ),
		) );

		$this->add_control( 'layers', array(
			'label'       => 'لایه‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ badge }}} — {{{ title }}}',
			'default'     => array(
				array(
					'badge'  => 'لایه اول',
					'title'  => 'آموزش و راهبری',
					'lead'   => '۱۴ جلسه حکمرانی بر بازار<br><span>با تدریس و راهبری <b>دکتر شانه‌سازان</b></span>',
					'intro'  => 'برای توسعه:',
					'items'  => "نگرش مدیریتی\nمزیت رقابتی\nهدف‌گذاری فروش\nارتباط حرفه‌ای\nمشتری‌شناسی\nمذاکره\nخدمات مشتری\nو مهارت‌های موردنیاز برای هدایت بهتر کسب‌وکار",
					'style'  => 'check',
					'accent' => 'primary',
				),
				array(
					'badge'  => 'لایه دوم',
					'title'  => 'کوچینگ و اجرا',
					'lead'   => '۱۴ جلسه خصوصی<br><span>با کوچ <b>دکتر ویسی</b> و منتور <b>مونا کمایی</b></span>',
					'intro'  => 'برای طی‌کردن این مسیر:',
					'items'  => "اسکن کسب‌وکار\nکشف باگ‌ها\nتعیین اولویت‌ها\nاصلاح\nاقدام\nپیاده‌سازی",
					'style'  => 'steps',
					'accent' => 'secondary',
				),
			),
		) );

		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_layer', array(
			'label' => 'استایل لایه‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'lay', 'تعداد ستون', '.szl-layers', 2, 4 );
		$this->sty_box( 'lay', 'کارت لایه', '.szl-layer' );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'badge', 'برچسب', '.szl-layer-badge' );
		$this->sty_box( 'badge', 'برچسب', '.szl-layer-badge', array( 'no_shadow' => true ) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'laytitle', 'عنوان لایه', '.szl-layer-title', array( 'margin' => true ) );
		$this->sty_text( 'laylead', 'خط معرفی', '.szl-layer-lead', array( 'margin' => true ) );
		$this->sty_text( 'layintro', 'متن قبل از لیست', '.szl-layer-intro' );
		$this->add_control( 'hr3', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'layitem', 'موارد', '.szl-layer li' );
		$this->sty_box( 'step', 'کادر مرحله', '.szl-mini-flow li', array( 'no_shadow' => true ) );
		$this->sty_text( 'stepn', 'شماره مرحله', '.szl-mini-n' );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( empty( $s['layers'] ) ) {
			return;
		}

		echo '<div class="szl-layers">';
		foreach ( $s['layers'] as $l ) {
			$acc = 'secondary' === $l['accent'] ? 'b' : 'a';
			echo '<article class="szl-card szl-topline szl-layer szl-acc-' . esc_attr( $acc ) . '">';

			if ( ! empty( $l['badge'] ) ) {
				echo '<span class="szl-layer-badge">' . esc_html( $l['badge'] ) . '</span>';
			}
			if ( ! empty( $l['title'] ) ) {
				echo '<h3 class="szl-layer-title">' . self::kses( $l['title'] ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $l['lead'] ) ) {
				echo '<p class="szl-layer-lead">' . self::kses( $l['lead'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $l['intro'] ) ) {
				echo '<p class="szl-layer-intro">' . self::kses( $l['intro'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}

			if ( ! empty( $l['items'] ) ) {
				$lines = array_filter( array_map( 'trim', preg_split( '/\R/u', $l['items'] ) ), 'strlen' );
				if ( 'steps' === $l['style'] ) {
					echo '<ol class="szl-mini-flow">';
					$i = 1;
					foreach ( $lines as $li ) {
						$n = str_replace( array( '0','1','2','3','4','5','6','7','8','9' ), array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ), (string) $i );
						echo '<li><span class="szl-mini-n">' . esc_html( $n ) . '</span>' . self::kses( $li ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
						$i++;
					}
					echo '</ol>';
				} else {
					echo '<ul class="szl-list szl-mk-check">';
					foreach ( $lines as $li ) {
						echo '<li>' . self::kses( $li ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo '</ul>';
				}
			}

			echo '</article>';
		}
		echo '</div>';
	}
}
