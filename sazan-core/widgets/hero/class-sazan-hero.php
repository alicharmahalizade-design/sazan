<?php
/**
 * ویجت «اسلایدر هیرو سازان» — سه طرح قابل سوییچ.
 *
 *   طرح ۱ (personal): برند فردی (سبک حسین طاهری) — پرتره کناری با کولاژ و
 *                     حلقه‌های رادار، نام روی عکس، چیپ‌های ویژگی، نشان دوره،
 *                     و پیک اسلایدهای کناری.
 *   طرح ۲ (academy) : آکادمی/دوره — نشان اعتماد، امتیاز، چیدمان وسط‌چین.
 *   طرح ۳ (fusion)  : تلفیقی — پرتره + چیپ‌ها + امتیاز + آمار.
 *
 * هر سه طرح از متغیرهای پالت --sz-* استفاده می‌کنند تا با تم سازان هماهنگ بمانند.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hero extends Widget_Base {

	public function get_name() { return 'sazan-hero'; }
	public function get_title() { return esc_html__( 'اسلایدر هیرو سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-slides'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'hero', 'هیرو', 'slider', 'اسلایدر', 'بنر', 'banner' ); }

	protected function register_controls() {

		/* ---------------- طرح نمایش ---------------- */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label'   => esc_html__( 'انتخاب طرح', 'sazan-core' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'personal',
			'options' => array(
				'personal' => esc_html__( 'طرح ۱ — برند فردی (حسین‌طاهری) ✨', 'sazan-core' ),
				'academy'  => esc_html__( 'طرح ۲ — آکادمی / دوره 🎓', 'sazan-core' ),
				'fusion'   => esc_html__( 'طرح ۳ — تلفیقی 💎', 'sazan-core' ),
				'minimal'  => esc_html__( 'طرح ۴ — مینیمال / خلوت 🕊️', 'sazan-core' ),
			),
			'description' => esc_html__( 'هر سه طرح از یک محتوا استفاده می‌کنند؛ فقط چیدمان و ظاهر تغییر می‌کند.', 'sazan-core' ),
		) );
		$this->add_control( 'media_side', array(
			'label'   => esc_html__( 'سمت تصویر', 'sazan-core' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'left',
			'options' => array(
				'left'  => esc_html__( 'چپ (متن سمت راست)', 'sazan-core' ),
				'right' => esc_html__( 'راست (متن سمت چپ)', 'sazan-core' ),
			),
		) );
		$this->add_responsive_control( 'min_h', array(
			'label'      => esc_html__( 'حداقل ارتفاع', 'sazan-core' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ),
			'range'      => array( 'px' => array( 'min' => 320, 'max' => 900 ), 'vh' => array( 'min' => 40, 'max' => 100 ) ),
			'default'        => array( 'unit' => 'px', 'size' => 520 ),
			'tablet_default' => array( 'unit' => 'px', 'size' => 460 ),
			'mobile_default' => array( 'unit' => 'px', 'size' => 0 ),
			'selectors'  => array( '{{WRAPPER}} .sazan-hero' => '--sz-hero-minh: {{SIZE}}{{UNIT}};' ),
		) );

		$this->add_control( 'fixed_note', array(
			'type'      => Controls_Manager::RAW_HTML,
			'raw'       => esc_html__( 'در طرح ۲ (آکادمی) این تصویر ثابت می‌ماند و فقط متن اسلایدها تغییر می‌کند.', 'sazan-core' ),
			'condition' => array( 'skin' => 'academy' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'fixed_image', array(
			'label'     => esc_html__( 'تصویر ثابت (طرح ۲)', 'sazan-core' ),
			'type'      => Controls_Manager::MEDIA,
			'condition' => array( 'skin' => 'academy' ),
		) );
		$this->add_control( 'fixed_frame', array(
			'label'     => esc_html__( 'قاب گوشه‌ای دور تصویر', 'sazan-core' ),
			'type'      => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
			'condition' => array( 'skin' => 'academy' ),
		) );
		$this->end_controls_section();

		/* ---------------- اسلایدها ---------------- */
		$this->start_controls_section( 'sec_slides', array( 'label' => esc_html__( 'اسلایدها', 'sazan-core' ) ) );

		$rep = new Repeater();
		$rep->add_control( 'eyebrow', array( 'label' => esc_html__( 'برچسب بالای عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT ) );
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => esc_html__( 'ثبت‌نام دوره بیزنس ۳۶۰ درجه', 'sazan-core' ) ) );
		$rep->add_control( 'title_hl', array( 'label' => esc_html__( 'خط دوم عنوان (هایلایت رنگی)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'با محدودیت پذیرش ادامه دارد', 'sazan-core' ) ) );
		$rep->add_control( 'subtitle', array( 'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3 ) );
		$rep->add_control( 'image', array( 'label' => esc_html__( 'تصویر (پرتره/شاخص)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ) ) );
		$rep->add_control( 'person_name', array( 'label' => esc_html__( 'نام روی عکس', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'حسین طاهری', 'sazan-core' ) ) );
		$rep->add_control( 'person_role', array( 'label' => esc_html__( 'عنوان زیر نام', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'پژوهشگر اقتصاد رفتاری و کارآفرینی', 'sazan-core' ) ) );
		$rep->add_control( 'badge_image', array( 'label' => esc_html__( 'نشان دوره (لوگو/بلوک کنار متن)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$rep->add_control( 'b1_text', array( 'label' => esc_html__( 'دکمه اصلی — متن', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ثبت‌نام دوره', 'sazan-core' ) ) );
		$rep->add_control( 'b1_link', array( 'label' => esc_html__( 'دکمه اصلی — لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$rep->add_control( 'b2_text', array( 'label' => esc_html__( 'دکمه دوم — متن', 'sazan-core' ), 'type' => Controls_Manager::TEXT ) );
		$rep->add_control( 'b2_link', array( 'label' => esc_html__( 'دکمه دوم — لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );

		$this->add_control( 'slides', array(
			'label'       => esc_html__( 'اسلایدها', 'sazan-core' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array( 'title' => 'ثبت‌نام دوره بیزنس ۳۶۰ درجه', 'title_hl' => 'با محدودیت پذیرش ادامه دارد' ),
				array(
					'title'    => 'یاد بگیر، تمرین کن،',
					'title_hl' => 'به نتیجه برس',
					'eyebrow'  => 'دوره‌های تخصصی سازان',
					'b1_text'  => 'مشاهده دوره‌ها',
				),
			),
		) );
		$this->end_controls_section();

		/* ---------------- چیپ‌های ویژگی ---------------- */
		$this->start_controls_section( 'sec_chips', array( 'label' => esc_html__( 'چیپ‌های ویژگی', 'sazan-core' ) ) );
		$this->add_control( 'show_chips', array( 'label' => esc_html__( 'نمایش چیپ‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$rc = new Repeater();
		$rc->add_control( 'icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS ) );
		$rc->add_control( 'l1', array( 'label' => esc_html__( 'خط اول', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۱۲ جلسه', 'sazan-core' ) ) );
		$rc->add_control( 'l2', array( 'label' => esc_html__( 'خط دوم', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۹۶ ساعت', 'sazan-core' ) ) );
		$this->add_control( 'chips', array(
			'label' => esc_html__( 'چیپ‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rc->get_controls(), 'title_field' => '{{{ l1 }}} {{{ l2 }}}',
			'condition' => array( 'show_chips' => 'yes' ),
			'default' => array(
				array( 'l1' => 'محتوای پشتیبان', 'l2' => 'وبینارهای مرور' ),
				array( 'l1' => '۱۲ جلسه', 'l2' => '۹۶ ساعت' ),
				array( 'l1' => '۹٪ پیش‌پرداخت', 'l2' => 'تسهیلات ۵ ماهه' ),
				array( 'l1' => 'Action Plan', 'l2' => 'هفته به هفته' ),
			),
		) );
		$this->end_controls_section();

		/* ---------------- نشان و امتیاز ---------------- */
		$this->start_controls_section( 'sec_trust', array( 'label' => esc_html__( 'نشان و امتیاز', 'sazan-core' ) ) );
		$this->add_control( 'show_badge', array( 'label' => esc_html__( 'نمایش نشان بالای عنوان', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->add_control( 'badge_text', array( 'label' => esc_html__( 'متن نشان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'آکادمی رسمی سازان', 'sazan-core' ), 'condition' => array( 'show_badge' => 'yes' ) ) );

		$this->add_control( 'show_rating', array( 'label' => esc_html__( 'نمایش امتیاز', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '', 'separator' => 'before' ) );
		$this->add_control( 'rating_stars', array(
			'label' => esc_html__( 'تعداد ستاره', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => '5',
			'options' => array( '5' => '۵', '4' => '۴', '3' => '۳' ), 'condition' => array( 'show_rating' => 'yes' ),
		) );
		$this->add_control( 'rating_text', array( 'label' => esc_html__( 'متن کنار امتیاز', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'رضایت +۲٬۰۰۰ هنرجو', 'sazan-core' ), 'condition' => array( 'show_rating' => 'yes' ) ) );
		$this->end_controls_section();

		/* ---------------- آمار ---------------- */
		$this->start_controls_section( 'sec_stats', array( 'label' => esc_html__( 'آمار (نوار پایین)', 'sazan-core' ) ) );
		$this->add_control( 'show_stats', array( 'label' => esc_html__( 'نمایش نوار آمار', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$rs = new Repeater();
		$rs->add_control( 'num', array( 'label' => esc_html__( 'عدد', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '+۱۸' ) );
		$rs->add_control( 'label', array( 'label' => esc_html__( 'برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'سال تجربه', 'sazan-core' ) ) );
		$this->add_control( 'stats', array(
			'label' => esc_html__( 'آمار', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rs->get_controls(), 'title_field' => '{{{ num }}} — {{{ label }}}',
			'condition' => array( 'show_stats' => 'yes' ),
			'default' => array(
				array( 'num' => '+۱۸', 'label' => 'سال تجربه' ),
				array( 'num' => '+۵۰', 'label' => 'دوره تخصصی' ),
				array( 'num' => '+۲٬۰۰۰', 'label' => 'هنرجو' ),
				array( 'num' => '٪۹۸', 'label' => 'رضایت' ),
			),
		) );
		$this->end_controls_section();

		/* ---------------- تنظیمات اسلایدر ---------------- */
		$this->start_controls_section( 'sec_slider', array( 'label' => esc_html__( 'تنظیمات اسلایدر', 'sazan-core' ) ) );
		$this->add_control( 'peek', array( 'label' => esc_html__( 'نمایش لبه اسلایدهای کناری (پیک)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'description' => esc_html__( 'قبل و بعد اسلاید فعال کمی دیده می‌شود.', 'sazan-core' ) ) );
		$this->add_control( 'autoplay', array( 'label' => esc_html__( 'پخش خودکار', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'autoplay_ms', array(
			'label' => esc_html__( 'مدت هر اسلاید (ثانیه)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 's' ), 'range' => array( 's' => array( 'min' => 2, 'max' => 15, 'step' => 0.5 ) ),
			'default' => array( 'unit' => 's', 'size' => 6 ), 'condition' => array( 'autoplay' => 'yes' ),
		) );
		$this->add_control( 'effect', array(
			'label' => esc_html__( 'جلوه گذار', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'slide',
			'options' => array( 'slide' => esc_html__( 'کشویی', 'sazan-core' ), 'fade' => esc_html__( 'محو (فید)', 'sazan-core' ) ),
			'description' => esc_html__( 'حالت «محو» با پیک ناسازگار است و پیک را غیرفعال می‌کند.', 'sazan-core' ),
		) );
		$this->add_control( 'show_arrows', array( 'label' => esc_html__( 'نمایش فلش‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'show_dots', array( 'label' => esc_html__( 'نمایش نقطه‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->end_controls_section();

		/* پالت رنگ مشترک سازان */
		$this->add_palette_controls();
	}

	/** ساخت یک دکمه از فیلدهای repeater. */
	private function btn( $text, $link, $cls ) {
		$text = trim( (string) $text );
		if ( '' === $text ) { return ''; }
		$href     = ! empty( $link['url'] ) ? $link['url'] : '#';
		$target   = ! empty( $link['is_external'] ) ? ' target="_blank"' : '';
		$nofollow = ! empty( $link['nofollow'] ) ? ' rel="nofollow"' : '';
		return sprintf( '<a class="sazan-btn %1$s" href="%2$s"%3$s%4$s>%5$s</a>', esc_attr( $cls ), esc_url( $href ), $target, $nofollow, esc_html( $text ) );
	}

	private function render_stars( $n ) {
		$n = max( 3, min( 5, (int) $n ) );
		$out = '';
		for ( $i = 0; $i < 5; $i++ ) {
			$out .= '<svg class="sz-star' . ( $i < $n ? ' on' : '' ) . '" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 15l-5.2 2.6 1-5.8L1.5 7.7l5.9-.9z"/></svg>';
		}
		return $out;
	}

	/** شماره‌ی دو رقمی با ارقام فارسی (۰۱، ۰۲ ...). */
	private function fa_index( $n ) {
		$s = str_pad( (string) (int) $n, 2, '0', STR_PAD_LEFT );
		return strtr( $s, array( '0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹' ) );
	}

	/** ردیف چیپ‌های ویژگی (مشترک بین اسلایدها). */
	private function render_chips( $s ) {
		if ( 'yes' !== $s['show_chips'] || empty( $s['chips'] ) ) { return; }
		echo '<div class="sz-hero-chips">';
		foreach ( (array) $s['chips'] as $c ) {
			echo '<div class="sz-hero-chip">';
			if ( ! empty( $c['icon']['value'] ) ) {
				echo '<span class="sz-chip-ic">';
				Icons_Manager::render_icon( $c['icon'], array( 'aria-hidden' => 'true' ) );
				echo '</span>';
			}
			echo '<span class="sz-chip-tx">';
			if ( ! empty( $c['l1'] ) ) { echo '<b>' . esc_html( $c['l1'] ) . '</b>'; }
			if ( ! empty( $c['l2'] ) ) { echo '<i>' . esc_html( $c['l2'] ) . '</i>'; }
			echo '</span></div>';
		}
		echo '</div>';
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$skin = in_array( $s['skin'], array( 'personal', 'academy', 'fusion', 'minimal' ), true ) ? $s['skin'] : 'personal';
		$side = ( 'right' === $s['media_side'] ) ? ' media-right' : '';
		$fade = ( 'fade' === $s['effect'] );
		$peek = ( ! $fade && 'yes' === $s['peek'] ) ? ' is-peek' : '';
		$eff  = $fade ? ' is-fade' : '';

		$auto = ( 'yes' === $s['autoplay'] ) ? '1' : '0';
		$ms   = ! empty( $s['autoplay_ms']['size'] ) ? (float) $s['autoplay_ms']['size'] : 6;
		$ms   = max( 2, $ms ) * 1000;

		$slides    = (array) ( $s['slides'] ?? array() );
		if ( empty( $slides ) ) { return; }
		$multi     = count( $slides ) > 1;
		$isAcademy = ( 'academy' === $skin );
		$isMinimal = ( 'minimal' === $skin );
		$autoCls   = ( 'yes' === $s['autoplay'] ) ? ' is-auto' : '';

		// در طرح ۲ اگر پیک روشن باشد آن را خاموش می‌کنیم (تصویر ثابت است).
		if ( $isAcademy ) { $peek = ''; }

		echo '<div class="sazan-sec sazan-hero skin-' . esc_attr( $skin ) . esc_attr( $side . $eff . $peek . $autoCls ) . '"'
			. ' data-autoplay="' . esc_attr( $auto ) . '" data-speed="' . esc_attr( (int) $ms ) . '"'
			. ' data-effect="' . esc_attr( $fade ? 'fade' : 'slide' ) . '"'
			. ' data-peek="' . esc_attr( '' !== $peek ? '1' : '0' ) . '">';

		echo '<div class="sz-hero-stage">';

		/* طرح ۲: تصویر ثابت (بیرون از تراک، اسلاید نمی‌شود) */
		if ( $isAcademy ) {
			$fixed = ! empty( $s['fixed_image']['url'] ) ? $s['fixed_image']['url']
				: ( ! empty( $slides[0]['image']['url'] ) ? $slides[0]['image']['url'] : '' );
			if ( $fixed ) {
				$frame = ( 'yes' === $s['fixed_frame'] ) ? ' has-frame' : '';
				echo '<div class="sz-hero-fixed' . esc_attr( $frame ) . '">';
				echo '<span class="sz-hero-glow" aria-hidden="true"></span>';
				if ( 'yes' === $s['fixed_frame'] ) { echo '<span class="sz-hero-frame" aria-hidden="true"></span>'; }
				echo '<img src="' . esc_url( $fixed ) . '" alt="" loading="lazy"></div>';
			}
		}

		echo '<div class="sz-hero-viewport"><div class="sz-hero-track">';

		foreach ( $slides as $i => $sl ) {
			echo '<div class="sz-hero-slide"><div class="sz-hero-inner">';

			/* رسانه‌ی داخل اسلاید (طرح ۱ و ۳): پرتره + رادار + نام روی عکس */
			if ( ! $isAcademy && ! empty( $sl['image']['url'] ) ) {
				echo '<div class="sz-hero-media">';
				echo '<span class="sz-hero-radar" aria-hidden="true"></span>';
				echo '<span class="sz-hero-glow" aria-hidden="true"></span>';
				echo '<div class="sz-hero-photo"><img src="' . esc_url( $sl['image']['url'] ) . '" alt="" loading="lazy">';
				if ( ! empty( $sl['person_name'] ) || ! empty( $sl['person_role'] ) ) {
					echo '<div class="sz-hero-person">';
					if ( ! empty( $sl['person_name'] ) ) { echo '<b>' . esc_html( $sl['person_name'] ) . '</b>'; }
					if ( ! empty( $sl['person_role'] ) ) { echo '<span>' . esc_html( $sl['person_role'] ) . '</span>'; }
					echo '</div>';
				}
				echo '</div></div>';
			}

			/* متن */
			echo '<div class="sz-hero-body">';

			/* طرح ۴: شماره‌ی بزرگ توخالی به‌عنوان امضای بصری */
			if ( $isMinimal ) {
				echo '<span class="sz-hero-index" aria-hidden="true">' . esc_html( $this->fa_index( $i + 1 ) ) . '</span>';
			}

			if ( ! $isMinimal && ! empty( $sl['badge_image']['url'] ) ) {
				echo '<div class="sz-hero-badgeimg"><img src="' . esc_url( $sl['badge_image']['url'] ) . '" alt="" loading="lazy"></div>';
			}

			if ( 'yes' === $s['show_badge'] && ! empty( $s['badge_text'] ) ) {
				echo '<span class="sz-hero-badge"><i aria-hidden="true"></i>' . esc_html( $s['badge_text'] ) . '</span>';
			}

			if ( ! empty( $sl['eyebrow'] ) ) {
				echo '<div class="sz-hero-eyebrow">' . esc_html( $sl['eyebrow'] ) . '</div>';
			}

			if ( ! empty( $sl['title'] ) || ! empty( $sl['title_hl'] ) ) {
				echo '<h2 class="sz-hero-title">' . esc_html( $sl['title'] );
				if ( ! empty( $sl['title_hl'] ) ) {
					echo '<span class="hl">' . esc_html( $sl['title_hl'] ) . '</span>';
				}
				echo '</h2>';
			}

			if ( ! empty( $sl['subtitle'] ) ) {
				echo '<p class="sz-hero-sub">' . esc_html( $sl['subtitle'] ) . '</p>';
			}

			if ( ! $isMinimal ) { $this->render_chips( $s ); }

			$b1 = $this->btn( $sl['b1_text'] ?? '', $sl['b1_link'] ?? array(), 'sazan-btn-orange' );
			$b2 = $this->btn( $sl['b2_text'] ?? '', $sl['b2_link'] ?? array(), 'sz-hero-ghost' );
			if ( $b1 || $b2 ) {
				echo '<div class="sz-hero-actions">' . $b1 . $b2 . '</div>';
			}

			if ( 'yes' === $s['show_rating'] ) {
				echo '<div class="sz-hero-rating"><span class="sz-stars">' . $this->render_stars( $s['rating_stars'] ) . '</span>';
				if ( ! empty( $s['rating_text'] ) ) {
					echo '<span class="sz-rating-text">' . esc_html( $s['rating_text'] ) . '</span>';
				}
				echo '</div>';
			}

			echo '</div>'; // body
			echo '</div></div>'; // inner + slide
		}

		echo '</div></div>'; // track + viewport

		/* فلش‌ها */
		if ( 'yes' === $s['show_arrows'] && $multi ) {
			echo '<div class="sz-hero-nav">';
			echo '<button type="button" class="sz-hero-arrow prev" aria-label="' . esc_attr__( 'قبلی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>';
			echo '<button type="button" class="sz-hero-arrow next" aria-label="' . esc_attr__( 'بعدی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>';
			echo '</div>';
		}

		echo '</div>'; // stage

		/* نقطه‌ها */
		if ( 'yes' === $s['show_dots'] && $multi ) {
			echo '<div class="sz-hero-dots">';
			foreach ( $slides as $i => $sl ) {
				echo '<button type="button" class="sz-hero-dot' . ( 0 === $i ? ' active' : '' ) . '" aria-label="' . esc_attr( $i + 1 ) . '"></button>';
			}
			echo '</div>';
		}

		/* نوار آمار */
		if ( 'yes' === $s['show_stats'] && ! empty( $s['stats'] ) ) {
			echo '<div class="sz-hero-stats">';
			foreach ( (array) $s['stats'] as $st ) {
				printf(
					'<div class="sz-hero-stat"><span class="n">%1$s</span><span class="l">%2$s</span></div>',
					esc_html( $st['num'] ), esc_html( $st['label'] )
				);
			}
			echo '</div>';
		}

		echo '</div>'; // sazan-hero
	}
}
