<?php
/**
 * ویجت «سکشن پادکست سازان» (sazan-podcast-section).
 *
 * پنلِ آبیِ بزرگ با گریدِ کارت‌های افقیِ اپیزود (تصویرِ گوینده + عنوان + گوینده +
 * چیپِ مدت/تاریخ + دکمه‌ی پخش) و بلوکِ کناری (عنوان + میکروفون در دایره‌ی آبی +
 * دکمه‌ی «همه پادکست‌ها»). افکتِ هاور: کارتِ فعال شارپ و برجسته، بقیه بلور و کم‌رنگ.
 *
 * منبع: دستی (ریپیتر) یا پست‌تایپِ پادکست (sazan_podcast).
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Podcast_Section extends Widget_Base {

	public function get_name() { return 'sazan-podcast-section'; }
	public function get_title() { return esc_html__( 'سکشن پادکست سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-headphones'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'podcast', 'پادکست', 'اپیزود', 'صوت', 'section' ); }

	protected function register_controls() {

		/* ==================== محتوا: بلوکِ کناری ==================== */
		$this->start_controls_section( 'sec_side', array( 'label' => esc_html__( 'بلوکِ کناری (عنوان و میکروفون)', 'sazan-core' ) ) );
		$this->add_control( 'side_title', array( 'label' => esc_html__( 'عنوانِ بخش', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'پادکست‌ها', 'sazan-core' ) ) );
		$this->add_control( 'mic_image', array( 'label' => esc_html__( 'تصویرِ میکروفون (PNG شفاف)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'description' => esc_html__( 'روی دایره‌ی آبی می‌نشیند. خالی = آیکنِ پیش‌فرض.', 'sazan-core' ) ) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'متنِ دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'همه پادکست‌ها', 'sazan-core' ), 'separator' => 'before' ) );
		$this->add_control( 'btn_link', array( 'label' => esc_html__( 'لینکِ دکمه', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->end_controls_section();

		/* ==================== محتوا: منبع داده ==================== */
		$this->start_controls_section( 'sec_source', array( 'label' => esc_html__( 'منبع داده', 'sazan-core' ) ) );
		$this->add_control( 'source', array(
			'label' => esc_html__( 'نمایش از', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'manual',
			'options' => array(
				'manual' => esc_html__( 'دستی (ریپیتر)', 'sazan-core' ),
				'cpt'    => esc_html__( 'پست‌تایپِ پادکست (خودکار)', 'sazan-core' ),
			),
		) );
		$this->add_control( 'pod_count', array( 'label' => esc_html__( 'تعداد', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 24, 'condition' => array( 'source' => 'cpt' ) ) );
		$this->add_control( 'pod_order', array(
			'label' => esc_html__( 'ترتیب', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'date',
			'options' => array( 'date' => esc_html__( 'جدیدترین', 'sazan-core' ), 'title' => esc_html__( 'عنوان', 'sazan-core' ), 'rand' => esc_html__( 'تصادفی', 'sazan-core' ) ),
			'condition' => array( 'source' => 'cpt' ),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: اپیزودها (دستی) ==================== */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'اپیزودها (دستی)', 'sazan-core' ), 'condition' => array( 'source' => 'manual' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'برج مراقبت – قسمت سوم', 'sazan-core' ) ) );
		$rep->add_control( 'speaker', array( 'label' => esc_html__( 'گوینده', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'حسین طاهری', 'sazan-core' ) ) );
		$rep->add_control( 'duration', array( 'label' => esc_html__( 'مدت', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '۲۹:۱۷' ) );
		$rep->add_control( 'date', array( 'label' => esc_html__( 'تاریخ', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۱۷ فروردین ۱۴۰۵', 'sazan-core' ) ) );
		$rep->add_control( 'image', array( 'label' => esc_html__( 'تصویرِ گوینده', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$rep->add_control( 'link', array( 'label' => esc_html__( 'لینکِ اپیزود', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'items', array(
			'label' => esc_html__( 'اپیزودها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array(
				array( 'title' => 'برج مراقبت – قسمت سوم', 'duration' => '۲۹:۱۷', 'date' => '۱۷ فروردین ۱۴۰۵' ),
				array( 'title' => 'مدیریت یادگیری', 'duration' => '۱۰:۰۶', 'date' => '۲۰ فروردین ۱۴۰۵' ),
				array( 'title' => 'برج مراقبت – قسمت اول', 'duration' => '۲۹:۱۶', 'date' => '۱۷ فروردین ۱۴۰۵' ),
				array( 'title' => 'برج مراقبت – قسمت دوم', 'duration' => '۲۴:۰۸', 'date' => '۱۷ فروردین ۱۴۰۵' ),
				array( 'title' => 'شِکست – قسمت سوم', 'duration' => '۰۸:۰۵', 'date' => '۱۷ فروردین ۱۴۰۵' ),
				array( 'title' => 'شِکست – قسمت چهارم', 'duration' => '۰۶:۴۲', 'date' => '۱۷ فروردین ۱۴۰۵' ),
			),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: چیدمان ==================== */
		$this->start_controls_section( 'sec_layout', array( 'label' => esc_html__( 'چیدمان', 'sazan-core' ) ) );
		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستونِ کارت‌ها', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '2', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3' ),
			'selectors' => array( '{{WRAPPER}} .podsec-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->add_control( 'hover_blur', array(
			'label' => esc_html__( 'افکتِ هاور (بلورِ بقیه‌ی کارت‌ها)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
			'description' => esc_html__( 'با هاورِ یک کارت، بقیه بلور و کم‌رنگ می‌شوند و کارتِ فعال برجسته می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'side_pos', array(
			'label' => esc_html__( 'جایگاهِ بلوکِ کناری', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'start',
			'options' => array(
				'start' => esc_html__( 'سمتِ راست', 'sazan-core' ),
				'end'   => esc_html__( 'سمتِ چپ', 'sazan-core' ),
			),
		) );
		$this->end_controls_section();

		/* ==================== استایل: رنگ‌ها ==================== */
		$this->start_controls_section( 'sty_colors', array( 'label' => esc_html__( 'رنگ‌ها', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'c_panel1', array( 'label' => esc_html__( 'پنل آبی (بالا)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#1c4c8f', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-panel1: {{VALUE}};' ) ) );
		$this->add_control( 'c_panel2', array( 'label' => esc_html__( 'پنل آبی (پایین)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#163f78', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-panel2: {{VALUE}};' ) ) );
		$this->add_control( 'c_accent', array( 'label' => esc_html__( 'رنگِ برند (پخش/دایره)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#2f7be0', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-accent: {{VALUE}};' ) ) );
		$this->add_control( 'c_circle', array( 'label' => esc_html__( 'رنگِ دایره‌ی میکروفون', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#1573d6', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-circle: {{VALUE}};' ) ) );
		$this->add_control( 'c_card', array( 'label' => esc_html__( 'ته‌رنگِ کارت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.06)', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-card: {{VALUE}};' ) ) );
		$this->add_control( 'c_bd', array( 'label' => esc_html__( 'لبه‌ی کارت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.14)', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-bd: {{VALUE}};' ) ) );
		$this->add_control( 'c_text', array( 'label' => esc_html__( 'متنِ داخلِ پنل', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-text: {{VALUE}};' ) ) );
		$this->add_control( 'c_mut', array( 'label' => esc_html__( 'متنِ کم‌رنگِ پنل', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#b9cbe6', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-mut: {{VALUE}};' ) ) );
		$this->add_control( 'c_side', array( 'label' => esc_html__( 'رنگِ عنوان/دکمه‌ی کناری', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#123a72', 'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-side: {{VALUE}};' ) ) );
		$this->end_controls_section();

		/* ==================== استایل: ابعاد ==================== */
		$this->start_controls_section( 'sty_size', array( 'label' => esc_html__( 'ابعاد و تایپوگرافی', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'radius', array(
			'label' => esc_html__( 'گردیِ پنل', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 0, 'max' => 48 ) ), 'default' => array( 'unit' => 'px', 'size' => 30 ),
			'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'side_w', array(
			'label' => esc_html__( 'پهنای بلوکِ کناری', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 180, 'max' => 420 ) ), 'default' => array( 'unit' => 'px', 'size' => 280 ),
			'selectors' => array( '{{WRAPPER}} .sazan-podsec' => '--pods-sidew: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name' => 'tg_title', 'label' => esc_html__( 'تایپوگرافی عنوانِ اپیزود', 'sazan-core' ), 'selector' => '{{WRAPPER}} .podsec-card h3',
		) );
		$this->end_controls_section();
	}

	/** آیکنِ SVG. */
	private function icon( $which ) {
		switch ( $which ) {
			case 'play':  return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>';
			case 'clock': return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
			case 'cal':   return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>';
			case 'mic':   return '<svg viewBox="0 0 24 24" width="60%" height="60%" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2.5" width="6" height="12" rx="3"/><path d="M6 11a6 6 0 0 0 12 0M12 17v4M9 21h6"/></svg>';
			case 'arrow': return '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
		}
		return '';
	}

	/** رندرِ یک کارتِ اپیزود. */
	private function render_card( $d ) {
		$link = ! empty( $d['link'] ) ? $d['link'] : '';
		$tag  = ( $link && '#' !== $link ) ? 'a' : 'div';
		$href = ( 'a' === $tag ) ? ' href="' . esc_url( $link ) . '"' : '';

		echo '<' . $tag . ' class="podsec-card"' . $href . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '<span class="podsec-thumb">';
		if ( ! empty( $d['image'] ) ) {
			echo '<img src="' . esc_url( $d['image'] ) . '" alt="" loading="lazy">';
		} else {
			echo '<span class="podsec-thumb__ph">' . $this->icon( 'mic' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</span>';

		echo '<div class="podsec-body">';
		echo '<h3>' . esc_html( $d['title'] ) . '</h3>';
		if ( '' !== trim( (string) $d['speaker'] ) ) {
			echo '<span class="podsec-speaker">' . esc_html__( 'گوینده:', 'sazan-core' ) . ' ' . esc_html( $d['speaker'] ) . '</span>';
		}
		if ( '' !== trim( (string) $d['duration'] ) || '' !== trim( (string) $d['date'] ) ) {
			echo '<div class="podsec-chips">';
			if ( '' !== trim( (string) $d['duration'] ) ) {
				echo '<span class="podsec-chip">' . $this->icon( 'clock' ) . '<b>' . esc_html( $d['duration'] ) . '</b></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( '' !== trim( (string) $d['date'] ) ) {
				echo '<span class="podsec-chip">' . $this->icon( 'cal' ) . '<b>' . esc_html( $d['date'] ) . '</b></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
		}
		echo '</div>'; // body

		echo '<span class="podsec-play" aria-hidden="true">' . $this->icon( 'play' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</' . $tag . '>';
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$cards = array();
		if ( 'cpt' === ( $s['source'] ?? 'manual' ) && class_exists( '\WP_Query' ) ) {
			$q = new \WP_Query( array(
				'post_type'      => 'sazan_podcast',
				'post_status'    => 'publish',
				'posts_per_page' => ! empty( $s['pod_count'] ) ? (int) $s['pod_count'] : 6,
				'orderby'        => ( 'title' === $s['pod_order'] ) ? 'title' : ( ( 'rand' === $s['pod_order'] ) ? 'rand' : 'date' ),
				'order'          => ( 'title' === $s['pod_order'] ) ? 'ASC' : 'DESC',
				'no_found_rows'  => true,
			) );
			foreach ( $q->posts as $p ) {
				$id = $p->ID;
				$cards[] = array(
					'title'    => get_the_title( $id ),
					'speaker'  => (string) get_post_meta( $id, '_sazan_pod_speaker', true ),
					'duration' => (string) get_post_meta( $id, '_sazan_pod_duration', true ),
					'date'     => get_the_date( '', $id ),
					'image'    => (string) get_the_post_thumbnail_url( $id, 'medium' ),
					'link'     => get_permalink( $id ),
				);
			}
		} else {
			foreach ( (array) ( $s['items'] ?? array() ) as $it ) {
				$cards[] = array(
					'title'    => $it['title'] ?? '',
					'speaker'  => $it['speaker'] ?? '',
					'duration' => $it['duration'] ?? '',
					'date'     => $it['date'] ?? '',
					'image'    => ! empty( $it['image']['url'] ) ? $it['image']['url'] : '',
					'link'     => $it['link']['url'] ?? '',
				);
			}
		}

		$cls = 'sazan-podsec';
		if ( 'yes' === ( $s['hover_blur'] ?? 'yes' ) ) { $cls .= ' has-hoverblur'; }
		$cls .= ( 'start' === ( $s['side_pos'] ?? 'end' ) ) ? ' side-start' : ' side-end';

		echo '<div class="' . esc_attr( $cls ) . '">';
		echo '<div class="podsec-wrap">';

		/* پنلِ کارت‌ها */
		echo '<div class="podsec-panel"><div class="podsec-grid">';
		if ( empty( $cards ) ) {
			echo '<p class="podsec-empty">' . esc_html__( 'هنوز اپیزودی ثبت نشده است.', 'sazan-core' ) . '</p>';
		} else {
			foreach ( $cards as $c ) { $this->render_card( $c ); }
		}
		echo '</div></div>';

		/* بلوکِ کناری */
		echo '<aside class="podsec-side">';
		if ( ! empty( $s['side_title'] ) ) {
			echo '<h2 class="podsec-title">' . esc_html( $s['side_title'] ) . '</h2>';
		}
		echo '<div class="podsec-mic"><span class="podsec-mic__circle" aria-hidden="true"></span>';
		if ( ! empty( $s['mic_image']['url'] ) ) {
			echo '<img class="podsec-mic__img" src="' . esc_url( $s['mic_image']['url'] ) . '" alt="" loading="lazy">';
		} else {
			echo '<span class="podsec-mic__ph" aria-hidden="true">' . $this->icon( 'mic' ) . '</span>';
		}
		echo '</div>';
		if ( ! empty( $s['btn_text'] ) ) {
			$blink = $s['btn_link']['url'] ?? '';
			$btag  = ( $blink && '#' !== $blink ) ? 'a' : 'span';
			$bhref = ( 'a' === $btag ) ? ' href="' . esc_url( $blink ) . '"' . ( ! empty( $s['btn_link']['is_external'] ) ? ' target="_blank"' : '' ) : '';
			echo '<' . $btag . ' class="podsec-all"' . $bhref . '>' . esc_html( $s['btn_text'] ) . $this->icon( 'arrow' ) . '</' . $btag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</aside>';

		echo '</div>'; // wrap
		echo '</div>'; // podsec
	}
}
