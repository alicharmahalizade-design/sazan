<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** دو ستون مقایسه‌ای (تلاش بیشتر / مسئله عمیق‌تر) + جمله بزرگ پایانی. */
class SZL_W_Compare extends SZL_Widget_Base {

	public function get_name() { return 'szl_compare'; }
	public function get_title() { return 'لندینگ: دو ستون مقایسه'; }
	public function get_icon() { return 'eicon-column'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow'  => 'WHY GROWTH STALLS',
			'title'    => 'چرا بعضی کسب‌وکارها رشد می‌کنند و بعضی درجا می‌زنند؟',
			'subtitle' => 'گاهی مشکل کمبود تلاش نیست',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_cols', array( 'label' => 'ستون‌ها' ) );

		$r = new Repeater();
		$r->add_control( 'title', array(
			'label'       => 'عنوان ستون',
			'type'        => Controls_Manager::TEXT,
			'label_block' => true,
		) );
		$r->add_control( 'items', array(
			'label'       => 'موارد (هر مورد در یک خط)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 8,
			'label_block' => true,
		) );
		$r->add_control( 'marker', array(
			'label'   => 'نشانه ابتدای موارد',
			'type'    => Controls_Manager::SELECT,
			'default' => 'dash',
			'options' => array(
				'dash'  => 'خط تیره —',
				'arrow' => 'پیکان ◂',
				'check' => 'تیک ✓',
				'cross' => 'ضربدر ✕',
				'dot'   => 'نقطه •',
				'none'  => 'بدون نشانه',
			),
		) );
		$r->add_control( 'foot', array(
			'label'       => 'جمله پایانی ستون',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
		) );
		$r->add_control( 'accent', array(
			'label'        => 'کادر تأکیدی',
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		) );

		$this->add_control( 'cols', array(
			'label'       => 'ستون‌ها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array(
					'title'  => 'تلاش بیشتر می‌شود…',
					'items'  => "مدیر بیشتر کار می‌کند\nفروشنده بیشتر تماس می‌گیرد\nجلسات بیشتر می‌شود\nفشار روی تیم افزایش پیدا می‌کند",
					'marker' => 'dash',
					'foot'   => 'اما نتیجه متناسب با این تلاش رشد نمی‌کند.',
				),
				array(
					'title'  => 'چون گاهی مسئله در جایی عمیق‌تر است',
					'items'  => "در نوع نگاه مدیر\nدر نبود مزیت رقابتی\nدر هدف‌گذاری اشتباه\nدر شناخت ناقص مشتری\nدر نحوه مذاکره\nدر فرآیند فروش\nدر ساختار سازمان\nیا در فاصله میان دانستن و اجرا",
					'marker' => 'arrow',
					'accent' => 'yes',
				),
			),
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'c_big', array( 'label' => 'جمله پایانی' ) );
		$this->add_control( 'bigline', array(
			'label'       => 'متن',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
			'default'     => 'در «حکمرانی بر بازار» قرار نیست فقط چند تکنیک جدید به کسب‌وکارتان اضافه کنید؛ <b><span>قرار است کسب‌وکارتان را دقیق‌تر ببینید.</span></b>',
		) );
		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_cols', array(
			'label' => 'استایل ستون‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'col', 'تعداد ستون', '.szl-compare', 2, 4 );
		$this->sty_box( 'col', 'کادر ستون', '.szl-compare .szl-card' );
		$this->add_control( 'accent_border', array(
			'label'     => 'رنگ حاشیه کادر تأکیدی',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-card-accent' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'coltitle', 'عنوان ستون', '.szl-card-h', array( 'margin' => true ) );
		$this->sty_text( 'colitem', 'موارد', '.szl-list li' );
		$this->add_control( 'marker_color', array(
			'label'     => 'رنگ نشانه',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-list li::before' => 'color: {{VALUE}};' ),
		) );
		$this->sty_text( 'colfoot', 'جمله پایانی ستون', '.szl-card-foot' );
		$this->end_controls_section();

		$this->start_controls_section( 's_big', array(
			'label' => 'استایل جمله پایانی',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'big', 'متن', '.szl-bigline', array( 'margin' => true ) );
		$this->add_control( 'big_grad', array(
			'label'        => 'گرادیان روی &lt;span&gt;',
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_responsive_control( 'big_maxw', array(
			'label'      => 'حداکثر عرض',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 300, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-bigline' => 'max-width:{{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( ! empty( $s['cols'] ) ) {
			echo '<div class="szl-compare">';
			foreach ( $s['cols'] as $c ) {
				$accent = 'yes' === ( $c['accent'] ?? '' ) ? ' szl-card-accent' : '';
				echo '<div class="szl-card szl-topline' . esc_attr( $accent ) . '">';
				if ( ! empty( $c['title'] ) ) {
					echo '<h3 class="szl-card-h">' . self::kses( $c['title'] ) . '</h3>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				if ( ! empty( $c['items'] ) ) {
					echo '<ul class="szl-list szl-mk-' . esc_attr( $c['marker'] ?: 'dash' ) . '">';
					foreach ( preg_split( '/\R/u', $c['items'] ) as $li ) {
						$li = trim( $li );
						if ( '' !== $li ) {
							echo '<li>' . self::kses( $li ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
						}
					}
					echo '</ul>';
				}
				if ( ! empty( $c['foot'] ) ) {
					echo '<p class="szl-card-foot">' . self::kses( $c['foot'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo '</div>';
			}
			echo '</div>';
		}

		if ( ! empty( $s['bigline'] ) ) {
			$grad = 'yes' === ( $s['big_grad'] ?? 'yes' ) ? ' szl-has-grad' : '';
			echo '<p class="szl-bigline' . esc_attr( $grad ) . '">' . self::kses( $s['bigline'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}
