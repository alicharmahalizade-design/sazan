<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** بلوک «تفاوت اصلی» + کارت‌های مدرس، کوچ و منتور. */
class SZL_W_People extends SZL_Widget_Base {

	protected $default_align = 'center';

	public function get_name() { return 'szl_people'; }
	public function get_title() { return 'لندینگ: تیم دوره'; }
	public function get_icon() { return 'eicon-person'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر و متن' ) );
		$this->ctl_heading( array(
			'eyebrow' => 'THE REAL DIFFERENCE',
			'title'   => 'اما تفاوت اصلی «حکمرانی بر بازار» اینجاست',
		) );
		$this->add_control( 'intro', array(
			'label'       => 'متن بالای نقل‌قول',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'دانستن اینکه باید چه کاری انجام دهید یک چیز است، اما دانستن اینکه:',
		) );
		$this->add_control( 'quote', array(
			'label'       => 'نقل‌قول',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => '«در کسب‌وکار خودِ من دقیقاً چه چیزی باید تغییر کند؟»',
		) );
		$this->add_control( 'after', array(
			'label'       => 'متن زیر نقل‌قول',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'چیز دیگری است. به همین دلیل مسیر شما با پایان کلاس تمام نمی‌شود.',
		) );
		$this->add_control( 'outro', array(
			'label'       => 'متن پایانی',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'اینجا دیگر درباره یک شرکت فرضی یا مثال آموزشی صحبت نمی‌کنیم؛ <b><span>موضوع اصلی کسب‌وکار خودِ شماست.</span></b>',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_people', array( 'label' => 'افراد' ) );

		$r = new Repeater();
		$r->add_control( 'photo', array(
			'label' => 'تصویر (اختیاری)',
			'type'  => Controls_Manager::MEDIA,
		) );
		$r->add_control( 'role', array( 'label' => 'نقش', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'name', array( 'label' => 'نام', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'meta', array( 'label' => 'توضیح کوتاه', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );

		$this->add_control( 'people', array(
			'label'       => 'افراد',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ role }}} — {{{ name }}}',
			'default'     => array(
				array( 'role' => 'تدریس و راهبری', 'name' => 'دکتر شانه‌سازان', 'meta' => '۱۴ جلسه آموزش و راهبری' ),
				array( 'role' => 'کوچ', 'name' => 'دکتر ویسی', 'meta' => 'جلسات خصوصی کوچینگ' ),
				array( 'role' => 'منتور', 'name' => 'مونا کمایی', 'meta' => 'همراهی در مسیر اجرا' ),
			),
		) );

		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_box', array(
			'label' => 'استایل کادر و متن',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_box( 'diff', 'کادر بیرونی', '.szl-diff' );
		$this->add_responsive_control( 'diff_maxw', array(
			'label'      => 'حداکثر عرض',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 400, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-diff' => 'max-width:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'body', 'متن‌ها', '.szl-diff > p' );
		$this->sty_text( 'quote', 'نقل‌قول', '.szl-quote' );
		$this->sty_box( 'quote', 'نقل‌قول', '.szl-quote', array( 'no_shadow' => true ) );
		$this->add_control( 'outro_grad', array(
			'label'        => 'گرادیان روی &lt;span&gt; متن پایانی',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_people', array(
			'label' => 'استایل کارت افراد',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'ppl', 'تعداد ستون', '.szl-people', 3, 6 );
		$this->sty_box( 'ppl', 'کارت', '.szl-person', array( 'no_shadow' => true ) );
		$this->add_responsive_control( 'photo_size', array(
			'label'      => 'اندازه تصویر',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 40, 'max' => 220 ) ),
			'default'    => array( 'size' => 92 ),
			'selectors'  => array( '{{WRAPPER}} .szl-person img' => 'width:{{SIZE}}px;height:{{SIZE}}px;' ),
		) );
		$this->add_control( 'photo_radius', array(
			'label'      => 'گردی تصویر',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'default'    => array( 'unit' => '%', 'size' => 50 ),
			'selectors'  => array( '{{WRAPPER}} .szl-person img' => 'border-radius:{{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'prole', 'نقش', '.szl-person-role' );
		$this->sty_text( 'pname', 'نام', '.szl-person-name' );
		$this->sty_text( 'pmeta', 'توضیح', '.szl-person-meta' );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		echo '<div class="szl-card szl-topline szl-diff">';
		$this->render_heading( $s );

		foreach ( array( 'intro' ) as $k ) {
			if ( ! empty( $s[ $k ] ) ) {
				echo '<p>' . self::kses( $s[ $k ] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		if ( ! empty( $s['quote'] ) ) {
			echo '<blockquote class="szl-quote szl-quote-lg">' . self::kses( $s['quote'] ) . '</blockquote>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( ! empty( $s['after'] ) ) {
			echo '<p>' . self::kses( $s['after'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		if ( ! empty( $s['people'] ) ) {
			echo '<div class="szl-people">';
			foreach ( $s['people'] as $p ) {
				echo '<div class="szl-person">';
				if ( ! empty( $p['photo']['url'] ) ) {
					printf(
						'<img src="%s" alt="%s" loading="lazy">',
						esc_url( $p['photo']['url'] ),
						esc_attr( $p['name'] ?? '' )
					);
				}
				if ( ! empty( $p['role'] ) ) {
					echo '<span class="szl-person-role">' . esc_html( $p['role'] ) . '</span>';
				}
				if ( ! empty( $p['name'] ) ) {
					echo '<b class="szl-person-name">' . esc_html( $p['name'] ) . '</b>';
				}
				if ( ! empty( $p['meta'] ) ) {
					echo '<span class="szl-person-meta">' . esc_html( $p['meta'] ) . '</span>';
				}
				echo '</div>';
			}
			echo '</div>';
		}

		if ( ! empty( $s['outro'] ) ) {
			$grad = 'yes' === ( $s['outro_grad'] ?? 'yes' ) ? ' szl-has-grad' : '';
			echo '<p class="szl-mb0' . esc_attr( $grad ) . '">' . self::kses( $s['outro'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		echo '</div>';
	}
}
