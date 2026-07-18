<?php
/**
 * ویجت پادکست — دو طرح (کلاسیک/حرفه‌ای)، منبع دستی یا پست‌تایپ،
 * پخش‌کننده‌ی واقعی با نوار پیشرفت و اکولایزر دقیق.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Podcast extends Widget_Base {

	public function get_name() { return 'sazan-podcast'; }
	public function get_title() { return esc_html__( 'پادکست سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-headphones'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'podcast', 'پادکست', 'صوت', 'player' ); }

	protected function register_controls() {

		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_section_header_controls( 'Podcast', esc_html__( 'پادکست های سازان', 'sazan-core' ) );
		$this->end_controls_section();

		/* طرح */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (کارت عمودی)', 'sazan-core' ),
				'pro'     => esc_html__( 'حرفه‌ای (پلیر افقی)', 'sazan-core' ),
				'lux'     => esc_html__( 'پوستری لاکچری / درخشان ✨', 'sazan-core' ),
			),
		) );
		$this->add_control( 'skin_hint', array(
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => esc_html__( 'برای طرح حرفه‌ای، تعداد ستون ۲ یا ۳ پیشنهاد می‌شود.', 'sazan-core' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->end_controls_section();

		/* منبع داده */
		$this->start_controls_section( 'sec_source', array( 'label' => esc_html__( 'منبع داده', 'sazan-core' ) ) );
		$this->add_control( 'source', array(
			'label' => esc_html__( 'نمایش از', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'cpt',
			'options' => array(
				'cpt'    => esc_html__( 'پست‌تایپ پادکست', 'sazan-core' ),
				'manual' => esc_html__( 'دستی (ریپیتر)', 'sazan-core' ),
			),
		) );
		$this->add_control( 'pod_count', array( 'label' => esc_html__( 'تعداد', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 24, 'condition' => array( 'source' => 'cpt' ) ) );
		$this->add_control( 'pod_order', array(
			'label' => esc_html__( 'ترتیب', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'date',
			'options' => array( 'date' => esc_html__( 'جدیدترین', 'sazan-core' ), 'title' => esc_html__( 'عنوان', 'sazan-core' ), 'rand' => esc_html__( 'تصادفی', 'sazan-core' ) ),
			'condition' => array( 'source' => 'cpt' ),
		) );
		$this->end_controls_section();

		/* ریپیتر دستی */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'اپیزودها (دستی)', 'sazan-core' ), 'condition' => array( 'source' => 'manual' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'اپیزود قسمت ۳', 'sazan-core' ) ) );
		$rep->add_control( 'desc', array( 'label' => esc_html__( 'چکیده', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'default' => esc_html__( 'این اپیزود درباره سیطره بر مشتری از...', 'sazan-core' ) ) );
		$rep->add_control( 'image', array( 'label' => esc_html__( 'تصویر (کاور)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$rep->add_control( 'audio_local', array( 'label' => esc_html__( 'فایل محلی (آپلود)', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'media_type' => 'audio' ) );
		$rep->add_control( 'audio_ext', array( 'label' => esc_html__( 'لینک خارجی', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ) ) );
		$rep->add_control( 'duration', array( 'label' => esc_html__( 'مدت زمان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$rep->add_control( 'speaker', array( 'label' => esc_html__( 'سخنران', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$this->add_control( 'items', array(
			'label' => esc_html__( 'اپیزودها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array( array(), array(), array(), array() ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'sec_layout', array( 'label' => esc_html__( 'چیدمان', 'sazan-core' ) ) );
		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '4', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array( '{{WRAPPER}} .sazan-pod-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->add_control( 'show_meta', array( 'label' => esc_html__( 'نمایش سخنران/مدت', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'show_progress', array( 'label' => esc_html__( 'نمایش نوار پیشرفت', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->end_controls_section();

		$this->add_palette_controls();
	}

	private function wave_bars( $seed = 0 ) {
		$out = '';
		for ( $i = 0; $i < 32; $i++ ) {
			$h = 22 + ( ( $seed * 7 + $i * 13 ) % 64 );
			$out .= '<i style="height:' . (int) $h . '%"></i>';
		}
		return $out;
	}

	private function play_svg() {
		return '<svg class="ico-play" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>'
			. '<svg class="ico-pause" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>';
	}

	private function dl_svg() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/></svg>';
	}

	private function render_card( $d, $seed, $show_meta, $show_progress ) {
		$audio = $d['audio'];
		$img   = $d['image'];
		echo '<div class="sazan-pod-card' . ( $audio ? '' : ' no-audio' ) . '">';

		// کاور (طرح حرفه‌ای)
		echo '<div class="sazan-pod-cover">';
		if ( $img ) { echo '<img src="' . esc_url( $img ) . '" alt="">'; }
		else { echo '<span class="sazan-pod-cover-ph"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a5 5 0 0 0-5 5v4a5 5 0 0 0 10 0V8a5 5 0 0 0-5-5zM5 12a7 7 0 0 0 14 0M12 19v3"/></svg></span>'; }
		if ( $audio ) {
			echo '<button type="button" class="sazan-pod-play pl-cover" aria-label="play">' . $this->play_svg() . '</button>';
		}
		echo '</div>';

		// متن و کنترل‌ها
		echo '<div class="sazan-pod-main">';
		echo '<div class="sazan-pod-head">';
		if ( $audio ) {
			echo '<button type="button" class="sazan-pod-play pl-top" aria-label="play">' . $this->play_svg() . '</button>';
			echo '<a class="dl" href="' . esc_url( $audio ) . '" download aria-label="download">' . $this->dl_svg() . '</a>';
		} else {
			echo '<span class="sazan-pod-play pl-top">' . $this->play_svg() . '</span>';
		}
		echo '</div>';

		echo '<h4>' . esc_html( $d['title'] ) . '</h4>';
		if ( '' !== trim( (string) $d['desc'] ) ) { echo '<p>' . esc_html( $d['desc'] ) . '</p>'; }

		echo '<div class="sazan-wave">' . $this->wave_bars( $seed ) . '</div>';

		if ( $show_progress ) {
			echo '<div class="sazan-pod-progress">';
			echo '<div class="sazan-pod-bar"><i class="fill"></i></div>';
			echo '<div class="sazan-pod-time"><span class="cur">۰:۰۰</span><span class="dur">' . esc_html( $d['duration'] ) . '</span></div>';
			echo '</div>';
		}

		if ( $show_meta && ( $d['speaker'] || $d['duration'] ) ) {
			echo '<div class="sazan-pod-meta">';
			if ( $d['speaker'] ) { echo '<span class="sp">' . esc_html( $d['speaker'] ) . '</span>'; }
			if ( $d['duration'] ) { echo '<span class="du">' . esc_html( $d['duration'] ) . '</span>'; }
			echo '</div>';
		}
		echo '</div>'; // main

		if ( $audio ) {
			echo '<audio class="sazan-pod-audio" src="' . esc_url( $audio ) . '" preload="metadata"></audio>';
		}
		echo '</div>'; // card
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$skin = in_array( $s['skin'], array( 'pro', 'lux' ), true ) ? $s['skin'] : 'classic';
		$sm   = ( 'yes' === $s['show_meta'] );
		$sp   = ( 'yes' === $s['show_progress'] );

		echo '<div class="sazan-sec sazan-podcast skin-' . esc_attr( $skin ) . '">';
		$this->render_section_header( $s );
		echo '<div class="sazan-pod-grid">';

		if ( 'cpt' === $s['source'] ) {
			$q = new \WP_Query( array(
				'post_type'      => 'sazan_podcast',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $s['pod_count'],
				'orderby'        => ( 'title' === $s['pod_order'] ) ? 'title' : ( ( 'rand' === $s['pod_order'] ) ? 'rand' : 'date' ),
				'order'          => ( 'title' === $s['pod_order'] ) ? 'ASC' : 'DESC',
			) );
			if ( $q->have_posts() ) {
				$i = 0;
				while ( $q->have_posts() ) {
					$q->the_post();
					$id    = get_the_ID();
					$local = get_post_meta( $id, '_sazan_pod_local', true );
					$ext   = get_post_meta( $id, '_sazan_pod_external', true );
					$this->render_card( array(
						'title'    => get_the_title(),
						'desc'     => get_the_excerpt(),
						'image'    => get_the_post_thumbnail_url( $id, 'medium' ),
						'audio'    => $local ? $local : $ext,
						'duration' => get_post_meta( $id, '_sazan_pod_duration', true ),
						'speaker'  => get_post_meta( $id, '_sazan_pod_speaker', true ),
					), $i, $sm, $sp );
					$i++;
				}
				wp_reset_postdata();
			} else {
				echo '<p class="sazan-empty">' . esc_html__( 'هنوز پادکستی ثبت نشده است. از منوی «پادکست‌ها» اضافه کنید.', 'sazan-core' ) . '</p>';
			}
		} else {
			$i = 0;
			foreach ( (array) $s['items'] as $it ) {
				$local = ! empty( $it['audio_local']['url'] ) ? $it['audio_local']['url'] : '';
				$ext   = ! empty( $it['audio_ext']['url'] ) ? $it['audio_ext']['url'] : '';
				$this->render_card( array(
					'title'    => $it['title'],
					'desc'     => $it['desc'],
					'image'    => ! empty( $it['image']['url'] ) ? $it['image']['url'] : '',
					'audio'    => $local ? $local : $ext,
					'duration' => $it['duration'],
					'speaker'  => $it['speaker'],
				), $i, $sm, $sp );
				$i++;
			}
		}

		echo '</div></div>';
	}
}
