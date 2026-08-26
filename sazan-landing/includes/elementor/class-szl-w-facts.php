<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** کارت‌های «مدت دوره» و «نوع دوره» با بلوک‌های داخلی و نقل‌قول. */
class SZL_W_Facts extends SZL_Widget_Base {

	public function get_name() { return 'szl_facts'; }
	public function get_title() { return 'لندینگ: کارت‌های مشخصات دوره'; }
	public function get_icon() { return 'eicon-info-box'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر (اختیاری)' ) );
		$this->ctl_heading();
		$this->end_controls_section();

		$this->start_controls_section( 'c_cards', array( 'label' => 'کارت‌ها' ) );

		$r = new Repeater();
		$r->add_control( 'tag', array(
			'label'   => 'برچسب کارت',
			'type'    => Controls_Manager::TEXT,
			'default' => 'مــــدت دوره',
		) );
		$r->add_control( 'title', array(
			'label'       => 'عنوان کارت',
			'type'        => Controls_Manager::TEXT,
			'default'     => '۱۴ هفته آموزش و اجرا',
			'label_block' => true,
		) );
		$r->add_control( 'chips', array(
			'label'       => 'تراشه‌ها (با کاما جدا کنید)',
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
			'description' => 'مثال: آموزش, راهبری, کوچینگ, منتورینگ',
		) );
		$r->add_control( 'intro', array(
			'label'       => 'متن ابتدای کارت',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
		) );
		$r->add_control( 'blocks', array(
			'label'       => 'بلوک‌های داخلی',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 8,
			'label_block' => true,
			'description' => 'هر بلوک در یک خط، با فرمت:<br><code>عنوان | افراد | توضیح</code><br>بخش‌های خالی را می‌توانید جا بگذارید.',
			'default'     => "۱۴ جلسه آموزش و راهبری | با تدریس و راهبری <b>دکتر شانه‌سازان</b> | برای توسعه نگرش مدیریتی، خلق مزیت رقابتی، هدف‌گذاری فروش، شناخت مشتری، مذاکره حرفه‌ای و طراحی خدمات مشتری.\n۱۴ جلسه خصوصی کوچینگ و منتورینگ | کوچ: <b>دکتر ویسی</b> — منتور: <b>مونا کمایی</b> | برای اسکن کسب‌وکار، شناسایی گلوگاه‌ها، اصلاح باگ‌های مدیریتی و فروش و تبدیل آموزش‌ها به اقدام واقعی در کسب‌وکار شما.",
		) );
		$r->add_control( 'quote', array(
			'label'       => 'نقل‌قول',
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
		) );
		$r->add_control( 'outro', array(
			'label'       => 'متن پایان کارت',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
		) );
		$r->add_control( 'accent', array(
			'label'   => 'رنگ نقطه بلوک‌ها',
			'type'    => Controls_Manager::SELECT,
			'default' => 'mixed',
			'options' => array(
				'mixed'     => 'یک‌درمیان (اصلی/مکمل)',
				'primary'   => 'همه رنگ اصلی',
				'secondary' => 'همه رنگ مکمل',
			),
		) );

		$this->add_control( 'cards', array(
			'label'       => 'کارت‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ tag }}}',
			'default'     => array(
				array(
					'tag'    => 'مــــدت دوره',
					'title'  => '۱۴ هفته آموزش و اجرا',
					'blocks' => "۱۴ جلسه آموزش و راهبری | با تدریس و راهبری <b>دکتر شانه‌سازان</b> | برای توسعه نگرش مدیریتی، خلق مزیت رقابتی، هدف‌گذاری فروش، شناخت مشتری، مذاکره حرفه‌ای و طراحی خدمات مشتری.\n۱۴ جلسه خصوصی کوچینگ و منتورینگ | کوچ: <b>دکتر ویسی</b> — منتور: <b>مونا کمایی</b> | برای اسکن کسب‌وکار، شناسایی گلوگاه‌ها، اصلاح باگ‌های مدیریتی و فروش و تبدیل آموزش‌ها به اقدام واقعی در کسب‌وکار شما.",
				),
				array(
					'tag'    => 'نــوع دوره',
					'title'  => 'آموزش + راهبری + کوچینگ + منتورینگ',
					'chips'  => 'آموزش, راهبری, کوچینگ, منتورینگ',
					'intro'  => 'اینجا قرار نیست فقط درباره کسب‌وکار صحبت کنیم؛ قرار است هم‌زمان با یادگیری، روی <b>خودِ کسب‌وکار شما</b> کار شود. هر چیزی که در جلسات آموزشی یاد می‌گیرید با یک سؤال مهم روبه‌رو می‌شود:',
					'blocks' => '',
					'quote'  => '«این را در کسب‌وکار من چطور اجرا کنیم؟»',
					'outro'  => 'و دقیقاً از همین‌جا کوچینگ و منتورینگ وارد مسیر می‌شود.',
				),
			),
		) );

		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_card', array(
			'label' => 'استایل کارت‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'card', 'تعداد ستون', '.szl-facts', 2, 4 );
		$this->sty_box( 'card', 'کارت', '.szl-card' );
		$this->add_control( 'card_topline', array(
			'label'        => 'خط رنگی بالای کارت',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_txt', array(
			'label' => 'استایل متن کارت',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'ftag', 'برچسب کارت', '.szl-fact-tag', array( 'margin' => true ) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'ftitle', 'عنوان کارت', '.szl-fact-title', array( 'margin' => true ) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'bhead', 'عنوان بلوک', '.szl-fb-head' );
		$this->sty_text( 'bwho', 'خط افراد', '.szl-fb-who' );
		$this->sty_text( 'btext', 'متن بلوک', '.szl-fb-text' );
		$this->add_control( 'hr3', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_box( 'block', 'کادر بلوک', '.szl-fb', array( 'no_shadow' => true ) );
		$this->end_controls_section();

		$this->start_controls_section( 's_extra', array(
			'label' => 'تراشه و نقل‌قول',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'chip', 'تراشه', '.szl-chip' );
		$this->sty_box( 'chip', 'تراشه', '.szl-chip', array( 'no_shadow' => true ) );
		$this->add_control( 'hr4', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'quote', 'نقل‌قول', '.szl-quote' );
		$this->sty_box( 'quote', 'نقل‌قول', '.szl-quote', array( 'no_shadow' => true ) );
		$this->add_control( 'quote_bar', array(
			'label'     => 'رنگ نوار کنار نقل‌قول',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-quote' => 'border-inline-start-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( empty( $s['cards'] ) ) {
			return;
		}
		$topline = 'yes' === ( $s['card_topline'] ?? 'yes' ) ? ' szl-topline' : '';

		echo '<div class="szl-facts">';
		foreach ( $s['cards'] as $c ) {
			echo '<article class="szl-card' . esc_attr( $topline ) . '">';

			if ( ! empty( $c['tag'] ) ) {
				echo '<span class="szl-fact-tag">' . esc_html( $c['tag'] ) . '</span>';
			}
			if ( ! empty( $c['title'] ) ) {
				echo '<h3 class="szl-fact-title">' . self::kses( $c['title'] ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $c['chips'] ) ) {
				echo '<div class="szl-chips">';
				foreach ( array_filter( array_map( 'trim', explode( ',', $c['chips'] ) ) ) as $chip ) {
					echo '<span class="szl-chip">' . esc_html( $chip ) . '</span>';
				}
				echo '</div>';
			}
			if ( ! empty( $c['intro'] ) ) {
				echo '<p class="szl-fb-text">' . self::kses( $c['intro'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}

			if ( ! empty( $c['blocks'] ) ) {
				$i = 0;
				foreach ( preg_split( '/\R/u', $c['blocks'] ) as $line ) {
					$line = trim( $line );
					if ( '' === $line ) {
						continue;
					}
					$parts = array_map( 'trim', explode( '|', $line ) );
					$dot   = 'secondary' === $c['accent'] ? 'b' : ( 'primary' === $c['accent'] ? 'a' : ( $i % 2 ? 'b' : 'a' ) );
					echo '<div class="szl-fb">';
					if ( ! empty( $parts[0] ) ) {
						echo '<h4 class="szl-fb-head"><span class="szl-dot szl-dot-' . esc_attr( $dot ) . '"></span>' . self::kses( $parts[0] ) . '</h4>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					if ( ! empty( $parts[1] ) ) {
						echo '<p class="szl-fb-who">' . self::kses( $parts[1] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					if ( ! empty( $parts[2] ) ) {
						echo '<p class="szl-fb-text">' . self::kses( $parts[2] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo '</div>';
					$i++;
				}
			}

			if ( ! empty( $c['quote'] ) ) {
				echo '<blockquote class="szl-quote">' . self::kses( $c['quote'] ) . '</blockquote>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $c['outro'] ) ) {
				echo '<p class="szl-fb-text szl-mb0">' . self::kses( $c['outro'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}

			echo '</article>';
		}
		echo '</div>';
	}
}
