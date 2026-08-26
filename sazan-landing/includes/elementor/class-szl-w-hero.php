<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** هیرو: برچسب، عنوان، شعار، توضیح، دکمه‌ها و آمار دوره. */
class SZL_W_Hero extends SZL_Widget_Base {

	protected $default_align = 'center';

	public function get_name() { return 'szl_hero'; }
	public function get_title() { return 'لندینگ: هیرو'; }
	public function get_icon() { return 'eicon-header'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_main', array( 'label' => 'متن‌ها' ) );

		$this->ctl_heading( array(
			'eyebrow' => 'MARKET GOVERNANCE PROGRAM',
			'title'   => "دوره جامع\n<span>حکمرانی بر بازار</span>",
		) );
		$this->update_control( 'title_tag', array( 'default' => 'h1' ) );

		$this->add_control( 'lead', array(
			'label'       => 'شعار اصلی',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
			'default'     => 'بعضی‌ها در بازار کار می‌کنند؛<br>بعضی‌ها <b>قواعد بازی بازارشان</b> را می‌سازند',
		) );
		$this->add_control( 'desc', array(
			'label'       => 'توضیح',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 4,
			'label_block' => true,
			'default'     => 'ابزارها، نگرش‌ها و استراتژی‌هایی برای اینکه بازار، مشتری، فروش و سازمانتان را دقیق‌تر ببینید، بهتر تصمیم بگیرید و آگاهانه‌تر کسب‌وکارتان را هدایت کنید.',
		) );

		$this->end_controls_section();

		/* ── buttons ── */
		$this->start_controls_section( 'c_btns', array( 'label' => 'دکمه‌ها' ) );

		$r = new Repeater();
		$r->add_control( 'text', array(
			'label'       => 'متن دکمه',
			'type'        => Controls_Manager::TEXT,
			'default'     => 'ثبت درخواست حضور در دوره',
			'label_block' => true,
		) );
		$r->add_control( 'link', array(
			'label'       => 'لینک',
			'type'        => Controls_Manager::URL,
			'default'     => array( 'url' => '#szl-form' ),
			'label_block' => true,
		) );
		$r->add_control( 'variant', array(
			'label'   => 'نوع دکمه',
			'type'    => Controls_Manager::SELECT,
			'default' => 'primary',
			'options' => array( 'primary' => 'اصلی (گرادیانی)', 'ghost' => 'ثانویه (شیشه‌ای)' ),
		) );

		$this->add_control( 'buttons', array(
			'label'       => 'دکمه‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array(
				array( 'text' => 'ثبت درخواست حضور در دوره', 'variant' => 'primary', 'link' => array( 'url' => '#szl-form' ) ),
				array( 'text' => 'ساختار دوره را ببینید', 'variant' => 'ghost', 'link' => array( 'url' => '#szl-structure' ) ),
			),
		) );

		$this->end_controls_section();

		/* ── stats ── */
		$this->start_controls_section( 'c_stats', array( 'label' => 'آمار دوره' ) );

		$r2 = new Repeater();
		$r2->add_control( 'value', array(
			'label'   => 'عدد یا متن',
			'type'    => Controls_Manager::TEXT,
			'default' => '۱۴',
		) );
		$r2->add_control( 'label', array(
			'label'       => 'برچسب',
			'type'        => Controls_Manager::TEXT,
			'default'     => 'هفته آموزش و اجرا',
			'label_block' => true,
		) );
		$r2->add_control( 'animate', array(
			'label'        => 'انیمیشن شمارش',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
			'description'  => 'فقط برای مقادیر عددی کار می‌کند.',
		) );

		$this->add_control( 'stats', array(
			'label'       => 'آمار',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r2->get_controls(),
			'title_field' => '{{{ value }}} — {{{ label }}}',
			'default'     => array(
				array( 'value' => '۱۴', 'label' => 'هفته آموزش و اجرا', 'animate' => 'yes' ),
				array( 'value' => '۱۴', 'label' => 'جلسه آموزش و راهبری', 'animate' => 'yes' ),
				array( 'value' => '۱۴', 'label' => 'جلسه خصوصی کوچینگ', 'animate' => 'yes' ),
				array( 'value' => 'سه‌شنبه‌ها', 'label' => 'هر هفته', 'animate' => '' ),
			),
		) );

		$this->end_controls_section();

		/* ── background effects ── */
		$this->start_controls_section( 'c_fx', array( 'label' => 'جلوه‌های پس‌زمینه' ) );
		$this->add_control( 'fx_glow', array(
			'label'        => 'هاله رنگی',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'fx_grid', array(
			'label'        => 'شبکه خطوط',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_text', array(
			'label' => 'استایل شعار و توضیح',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'lead', 'شعار', '.szl-hero-lead', array( 'margin' => true ) );
		$this->add_control( 'hr_t', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'desc', 'توضیح', '.szl-hero-desc', array( 'margin' => true ) );
		$this->add_responsive_control( 'desc_maxw', array(
			'label'      => 'حداکثر عرض توضیح',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 300, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-hero-desc' => 'max-width:{{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_btns', array(
			'label' => 'استایل دکمه‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->add_responsive_control( 'btn_align', array(
			'label'     => 'تراز دکمه‌ها',
			'type'      => Controls_Manager::CHOOSE,
			'default'   => 'center',
			'options'   => array(
				'flex-start' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center'     => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .szl-hero-actions' => 'justify-content: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'btn_gap', array(
			'label'      => 'فاصله بین دکمه‌ها',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-hero-actions' => 'gap:{{SIZE}}{{UNIT}};' ),
		) );
		$this->sty_button( 'btnp', 'دکمه اصلی', '.szl-btn-primary' );
		$this->sty_button( 'btng', 'دکمه ثانویه', '.szl-btn-ghost' );
		$this->end_controls_section();

		$this->start_controls_section( 's_stats', array(
			'label' => 'استایل آمار',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'stat', 'تعداد ستون آمار', '.szl-stats', 4, 6 );
		$this->sty_box( 'stat', 'کارت آمار', '.szl-stats li' );
		$this->add_control( 'hr_s', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'statv', 'عدد', '.szl-stats b' );
		$this->add_control( 'statv_grad', array(
			'label'        => 'گرادیان روی عدد',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->sty_text( 'statl', 'برچسب', '.szl-stats span' );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		echo '<div class="szl-hero">';

		if ( 'yes' === ( $s['fx_glow'] ?? '' ) ) {
			echo '<span class="szl-hero-glow" aria-hidden="true"></span>';
		}
		if ( 'yes' === ( $s['fx_grid'] ?? '' ) ) {
			echo '<span class="szl-hero-grid" aria-hidden="true"></span>';
		}

		echo '<div class="szl-hero-inner">';
		$this->render_heading( $s );

		if ( ! empty( $s['lead'] ) ) {
			echo '<p class="szl-hero-lead">' . self::kses( $s['lead'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( ! empty( $s['desc'] ) ) {
			echo '<p class="szl-hero-desc">' . self::kses( $s['desc'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		if ( ! empty( $s['buttons'] ) ) {
			echo '<div class="szl-hero-actions">';
			foreach ( $s['buttons'] as $b ) {
				if ( empty( $b['text'] ) ) {
					continue;
				}
				printf(
					'<a class="szl-btn szl-btn-%1$s" %2$s>%3$s</a>',
					esc_attr( 'ghost' === $b['variant'] ? 'ghost' : 'primary' ),
					$this->link_attrs( $b['link'] ?? array() ), // phpcs:ignore WordPress.Security.EscapeOutput
					esc_html( $b['text'] )
				);
			}
			echo '</div>';
		}

		if ( ! empty( $s['stats'] ) ) {
			echo '<ul class="szl-stats">';
			foreach ( $s['stats'] as $st ) {
				$num  = 'yes' === ( $st['animate'] ?? '' );
				$attr = $num ? ' data-szl-count="' . esc_attr( $st['value'] ) . '"' : '';
				echo '<li>';
				echo '<b class="' . ( $num ? 'szl-num' : 'szl-num szl-num-text' ) . '"' . $attr . '>' . esc_html( $st['value'] ) . '</b>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<span>' . esc_html( $st['label'] ) . '</span>';
				echo '</li>';
			}
			echo '</ul>';
		}

		echo '</div></div>';
	}
}
