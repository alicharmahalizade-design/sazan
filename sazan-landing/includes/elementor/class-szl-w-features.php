<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Elementor\Controls_Manager;
use Elementor\Repeater;

/** گرید محورهای کاری (جلسات خصوصی) با آیکون. */
class SZL_W_Features extends SZL_Widget_Base {

	public function get_name() { return 'szl_features'; }
	public function get_title() { return 'لندینگ: گرید محورها'; }
	public function get_icon() { return 'eicon-icon-box'; }

	protected function content_controls() {
		$this->start_controls_section( 'c_head', array( 'label' => 'سرتیتر' ) );
		$this->ctl_heading( array(
			'eyebrow'  => 'BUSINESS SCAN',
			'title'    => 'در جلسات خصوصی روی چه چیزهایی کار می‌شود؟',
			'subtitle' => '',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_tags', array( 'label' => 'تراشه‌های بالای گرید' ) );
		$this->add_control( 'tags', array(
			'label'       => 'تراشه‌ها (با کاما جدا کنید)',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'label_block' => true,
			'default'     => 'فروش؟, فروشندگان؟, ساختار؟, مدیریت؟, فرآیندها؟, یا حتی خود مدیر؟',
		) );
		$this->add_control( 'tags_note', array(
			'label'       => 'متن زیر تراشه‌ها',
			'type'        => Controls_Manager::TEXTAREA,
			'rows'        => 3,
			'label_block' => true,
			'default'     => 'در جلسات خصوصی، کسب‌وکار بررسی می‌شود تا گلوگاه‌ها و باگ‌های سازمانی و مدیریتی بهتر شناسایی شوند. سپس متناسب با <b>اهداف دوره</b> و <b>اهداف اختصاصی مدیر</b>، مسیر اصلاح و اقدام جلو می‌رود.',
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_items', array( 'label' => 'محورها' ) );

		$r = new Repeater();
		$r->add_control( 'icon', array(
			'label'   => 'آیکون',
			'type'    => Controls_Manager::ICONS,
			'default' => array( 'value' => 'eicon-cog', 'library' => 'elementor-icons' ),
		) );
		$r->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'label_block' => true ) );

		$seed = array(
			array( 'eicon-cog', 'اصلاح زیرساخت‌های سازمانی', 'شناسایی نقاطی که ساختار کسب‌وکار را کند کرده‌اند و کار روی اصلاح آن‌ها.' ),
			array( 'eicon-preview-medium', 'شناسایی و اصلاح باگ‌های مدیریتی', 'بررسی رفتارها و تصمیم‌هایی که ممکن است بدون آنکه متوجه باشید مانع رشد سازمان شده باشند.' ),
			array( 'eicon-chart-line', 'شناسایی باگ‌های فروش', 'بررسی نقاط ضعف در مسیر فروش و پیدا کردن موانعی که اجازه نمی‌دهند ظرفیت واقعی فروش آزاد شود.' ),
			array( 'eicon-users', 'بررسی مسائل فروشندگان', 'شناسایی مشکلات عملکردی تیم فروش و بررسی مسیرهای اصلاح.' ),
			array( 'eicon-target', 'هم‌راستا کردن اقدامات با اهداف مدیر', 'تا فعالیت‌های روزمره سازمان در خدمت اهداف اصلی کسب‌وکار قرار بگیرند.' ),
			array( 'eicon-handshake', 'همراهی در مسیر اجرا', 'منتور در مسیر اقدامات و پیاده‌سازی آموخته‌ها همراه شما خواهد بود.' ),
		);
		$this->add_control( 'items', array(
			'label'       => 'محورها',
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $r->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array_map( function ( $x ) {
				return array(
					'icon'  => array( 'value' => $x[0], 'library' => 'elementor-icons' ),
					'title' => $x[1],
					'text'  => $x[2],
				);
			}, $seed ),
		) );

		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->sty_heading();

		$this->start_controls_section( 's_tags', array(
			'label' => 'استایل تراشه‌ها',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_text( 'tag', 'تراشه', '.szl-scan-tags li' );
		$this->sty_box( 'tag', 'تراشه', '.szl-scan-tags li', array( 'no_shadow' => true ) );
		$this->sty_text( 'tagnote', 'متن زیر تراشه‌ها', '.szl-scan p', array( 'margin' => true ) );
		$this->end_controls_section();

		$this->start_controls_section( 's_items', array(
			'label' => 'استایل کارت محور',
			'tab'   => Controls_Manager::TAB_STYLE,
		) );
		$this->sty_columns( 'feat', 'تعداد ستون', '.szl-features', 3, 6 );
		$this->sty_box( 'feat', 'کارت', '.szl-feature' );
		$this->add_control( 'hr1', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->add_responsive_control( 'ico_box', array(
			'label'      => 'اندازه کادر آیکون',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 28, 'max' => 110 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-feat-ico' => 'width:{{SIZE}}px;height:{{SIZE}}px;' ),
		) );
		$this->add_responsive_control( 'ico_size', array(
			'label'      => 'اندازه آیکون',
			'type'       => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 60 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-feat-ico i'   => 'font-size:{{SIZE}}px;',
				'{{WRAPPER}} .szl-feat-ico svg' => 'width:{{SIZE}}px;height:{{SIZE}}px;',
			),
		) );
		$this->add_control( 'ico_color', array(
			'label'     => 'رنگ آیکون',
			'type'      => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .szl-feat-ico i'   => 'color: {{VALUE}};',
				'{{WRAPPER}} .szl-feat-ico svg' => 'fill: {{VALUE}};',
			),
		) );
		$this->sty_box( 'ico', 'کادر آیکون', '.szl-feat-ico', array( 'no_shadow' => true ) );
		$this->add_control( 'hr2', array( 'type' => Controls_Manager::DIVIDER ) );
		$this->sty_text( 'feattitle', 'عنوان', '.szl-feature h4', array( 'margin' => true ) );
		$this->sty_text( 'feattext', 'توضیح', '.szl-feature p' );
		$this->end_controls_section();
	}

	protected function output( $s ) {
		$this->render_heading( $s );

		if ( ! empty( $s['tags'] ) || ! empty( $s['tags_note'] ) ) {
			echo '<div class="szl-scan">';
			if ( ! empty( $s['tags'] ) ) {
				echo '<ul class="szl-scan-tags">';
				foreach ( array_filter( array_map( 'trim', explode( ',', $s['tags'] ) ) ) as $t ) {
					echo '<li>' . esc_html( $t ) . '</li>';
				}
				echo '</ul>';
			}
			if ( ! empty( $s['tags_note'] ) ) {
				echo '<p>' . self::kses( $s['tags_note'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div>';
		}

		if ( empty( $s['items'] ) ) {
			return;
		}

		echo '<div class="szl-features">';
		foreach ( $s['items'] as $f ) {
			echo '<article class="szl-card szl-topline szl-feature">';
			if ( ! empty( $f['icon']['value'] ) ) {
				echo '<span class="szl-feat-ico">';
				\Elementor\Icons_Manager::render_icon( $f['icon'], array( 'aria-hidden' => 'true' ) );
				echo '</span>';
			}
			if ( ! empty( $f['title'] ) ) {
				echo '<h4>' . self::kses( $f['title'] ) . '</h4>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( ! empty( $f['text'] ) ) {
				echo '<p>' . self::kses( $f['text'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</article>';
		}
		echo '</div>';
	}
}
