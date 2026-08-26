<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** لیست تیک‌دار — «این دوره مناسب شماست اگر…» یا جمع‌بندی دوره. */
class SZL_W_Checklist extends SZL_Widget_Base {

	public function get_name() { return 'szl_checklist'; }
	public function get_title() { return 'لندینگ: لیست تیک‌دار'; }
	public function get_icon() { return 'eicon-checkbox' ; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow' => 'IS IT FOR YOU?',
			'title'   => 'این دوره برای شما مناسب است اگر:',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_items', array( 'label' => 'موارد' ) );

		$r = new Repeater();
		$r->add_control( 'text', array(
			'label'       => 'متن',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
		) );

		$seed = array(
			'صاحب کسب‌وکاری هستید و احساس می‌کنید ظرفیت مجموعه شما بیشتر از نتایجی است که امروز می‌گیرید.',
			'می‌خواهید بازار و مشتری خود را عمیق‌تر بشناسید و تصمیم‌های مدیریتی دقیق‌تری بگیرید.',
			'می‌خواهید برای کسب‌وکارتان یک مزیت رقابتی واقعی و قابل دفاع بسازید.',
			'فروش دارید اما می‌خواهید آن را هدفمندتر و حرفه‌ای‌تر هدایت کنید.',
			'می‌خواهید تیم فروش شما بهتر مشتری را بشناسد و حرفه‌ای‌تر مذاکره کند.',
			'احساس می‌کنید در سازمان یا فرآیند فروش شما باگ‌هایی وجود دارد اما محل دقیق آن‌ها را نمی‌دانید.',
			'از دوره‌هایی که فقط اطلاعات می‌دهند خسته شده‌اید و می‌خواهید آموزش را وارد کسب‌وکار واقعی خودتان کنید.',
			'و اگر به‌عنوان مدیر می‌خواهید به‌جای درگیرشدن دائمی با اتفاقات روزمره، کسب‌وکارتان را آگاهانه‌تر هدایت کنید.',
		);
		$this->add_control( 'items', array(
			'label'       => 'موارد',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array_map( function ( $t ) { return array( 'text' => $t ); }, $seed ),
		) );

		$this->add_control( 'mark', array(
			'label'   => 'نشانه',
			'type'    => Controls_Manager::SELECT,
			'default' => 'check',
			'options' => array( 'check' => 'تیک ✓', 'arrow' => 'پیکان ←', 'dot' => 'نقطه •', 'num' => 'شماره' ),
		) );
		$this->add_control( 'boxed', array(
			'label'        => 'قرار گرفتن داخل یک کارت',
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'description'  => 'برای حالت «جمع‌بندی دوره» مناسب است.',
		) );

		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_items', array(
			'label' => 'استایل موارد',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'fit', 'تعداد ستون', '.szl-fit', 2, 4 );
		$this->sty_box( 'fit', 'کادر مورد', '.szl-fit li', array( 'no_shadow' => true ) );
		$this->sty_text( 'fit', 'متن', '.szl-fit li' );
		$this->add_control( 'fit_hover', array(
			'label'     => 'رنگ حاشیه در هاور',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-fit li:hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->add_responsive_control( 'mark_size', array(
			'label'      => 'اندازه نشانه',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 14, 'max' => 48 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-fit-mark' => 'width:{{SIZE}}px;height:{{SIZE}}px;' ),
		) );
		$this->sty_text( 'mark', 'نشانه', '.szl-fit-mark' );
		$this->add_group_control( \Elementor\Group_Control_Background::get_type(), array(
			'name'     => 'mark_bg',
			'label'    => 'پس‌زمینه نشانه',
			'types'    => array( 'classic', 'gradient' ),
			'selector' => '{{WRAPPER}} .szl-fit-mark',
		) );
		$this->add_control( 'mark_radius', array(
			'label'      => 'گردی نشانه',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'default'    => array( 'unit' => '%', 'size' => 50 ),
			'selectors'  => array( '{{WRAPPER}} .szl-fit-mark' => 'border-radius:{{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 's_wrap', array(
			'label'     => 'استایل کارت دربرگیرنده',
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => array( 'boxed' => 'yes' ),
		) );
		$this->sty_box( 'wrap', 'کارت', '.szl-summary' );
		$this->add_responsive_control( 'wrap_maxw', array(
			'label'      => 'حداکثر عرض',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 400, 'max' => 1200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-summary' => 'max-width:{{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$boxed = 'yes' === ( $s['boxed'] ?? '' );

		if ( $boxed ) {
			echo '<div class="szl-card szl-topline szl-summary">';
		}
		$this->render_heading( $s );

		if ( ! empty( $s['items'] ) ) {
			$mark = $s['mark'] ?: 'check';
			$glyph = array( 'check' => '✓', 'arrow' => '←', 'dot' => '•' );
			echo '<ul class="szl-fit szl-fit-' . esc_attr( $mark ) . '">';
			$i = 1;
			foreach ( $s['items'] as $it ) {
				if ( empty( $it['text'] ) ) {
					continue;
				}
				$g = 'num' === $mark
					? str_replace( array( '0','1','2','3','4','5','6','7','8','9' ), array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ), (string) $i )
					: $glyph[ $mark ];
				echo '<li><span class="szl-fit-mark" aria-hidden="true">' . esc_html( $g ) . '</span>';
				echo '<span class="szl-fit-txt">' . self::kses( $it['text'] ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
				$i++;
			}
			echo '</ul>';
		}

		if ( $boxed ) {
			echo '</div>';
		}
	}
}
