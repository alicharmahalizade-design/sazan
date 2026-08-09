<?php
/**
 * ویجت وبلاگ / مقالات — منبع دستی یا نوشته‌های وردپرس.
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

class Blog extends Widget_Base {

	public function get_name() { return 'sazan-blog'; }
	public function get_title() { return esc_html__( 'وبلاگ سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-post-list'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'blog', 'وبلاگ', 'مقاله', 'posts' ); }

	protected function register_controls() {

		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_section_header_controls( 'Blog', esc_html__( 'مقالات مرکز دانش سازان', 'sazan-core' ) );
		$this->end_controls_section();

		/* طرح */
		$this->start_controls_section( 'sec_skin', array( 'label' => esc_html__( 'طرح نمایش', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'انتخاب طرح', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (ساده)', 'sazan-core' ),
				'lux'     => esc_html__( 'لاکچری / میلیاردی ✨', 'sazan-core' ),
			),
		) );
		$this->end_controls_section();

		/* منبع */
		$this->start_controls_section( 'sec_source', array( 'label' => esc_html__( 'منبع داده', 'sazan-core' ) ) );
		$this->add_control( 'source', array(
			'label' => esc_html__( 'نمایش از', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'posts',
			'options' => array(
				'posts'  => esc_html__( 'نوشته‌های وردپرس', 'sazan-core' ),
				'manual' => esc_html__( 'دستی (ریپیتر)', 'sazan-core' ),
			),
		) );
		$this->add_control( 'posts_count', array( 'label' => esc_html__( 'تعداد', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 24, 'condition' => array( 'source' => 'posts' ) ) );
		$this->add_control( 'posts_cat', array( 'label' => esc_html__( 'نامک دسته (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'description' => esc_html__( 'برای فیلتر دسته، نامک (slug) را وارد کنید.', 'sazan-core' ), 'condition' => array( 'source' => 'posts' ) ) );
		$this->add_control( 'views_meta', array( 'label' => esc_html__( 'کلید متای بازدید (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'description' => esc_html__( 'خالی = استفاده از شمارنده‌ی داخلی افزونه. یا کلید متای افزونه‌ی دیگر را وارد کنید.', 'sazan-core' ), 'condition' => array( 'source' => 'posts' ) ) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'مطالعه', 'sazan-core' ), 'condition' => array( 'source' => 'posts' ) ) );
		$this->end_controls_section();

		/* ریپیتر دستی */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'مقالات (دستی)', 'sazan-core' ), 'condition' => array( 'source' => 'manual' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'خلاصه جلسه شانزدهم مدیریت', 'sazan-core' ) ) );
		$rep->add_control( 'desc', array( 'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'default' => esc_html__( 'روز جهانی بدون کیسه‌های پلاستیکی...', 'sazan-core' ) ) );
		$rep->add_control( 'image', array( 'label' => esc_html__( 'تصویر', 'sazan-core' ), 'type' => Controls_Manager::MEDIA ) );
		$rep->add_control( 'tag', array( 'label' => esc_html__( 'برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'خلاصه کلاس', 'sazan-core' ) ) );
		$rep->add_control( 'views', array( 'label' => esc_html__( 'بازدید', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۲۷۵ بازدید', 'sazan-core' ) ) );
		$rep->add_control( 'date', array( 'label' => esc_html__( 'تاریخ', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۲۱ خرداد ۱۴۰۳', 'sazan-core' ) ) );
		$rep->add_control( 'btn', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'مطالعه', 'sazan-core' ) ) );
		$rep->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'items', array(
			'label' => esc_html__( 'مقالات', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array( array(), array(), array(), array() ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'sec_layout', array( 'label' => esc_html__( 'چیدمان', 'sazan-core' ) ) );
		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '4', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array( '{{WRAPPER}} .sazan-blog-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->end_controls_section();

		$this->add_palette_controls();
	}

	private function date_svg() {
		return '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
	}

	private function render_card( $d ) {
		$img  = $d['image'] ? '<img src="' . esc_url( $d['image'] ) . '" alt="">' : '';
		$href = $d['link'] ? esc_url( $d['link'] ) : '#';
		echo '<article class="sazan-blog-card">';
		echo '<div class="sazan-blog-media">' . $img;
		if ( '' !== trim( (string) $d['tag'] ) ) { echo '<span class="b-tag">' . esc_html( $d['tag'] ) . '</span>'; }
		if ( '' !== trim( (string) $d['views'] ) ) { echo '<span class="b-views">' . esc_html( $d['views'] ) . '</span>'; }
		echo '</div>';
		echo '<div class="sazan-blog-body"><h4>' . esc_html( $d['title'] ) . '</h4>';
		if ( '' !== trim( (string) $d['desc'] ) ) { echo '<p>' . esc_html( $d['desc'] ) . '</p>'; }
		echo '<div class="sazan-blog-foot">';
		echo '<span class="date">' . $this->date_svg() . esc_html( $d['date'] ) . '</span>';
		echo '<a class="sazan-btn sazan-btn-orange sm" href="' . $href . '">' . esc_html( $d['btn'] ) . '</a>';
		echo '</div></div></article>';
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$skin = ( 'lux' === $s['skin'] ) ? 'lux' : 'classic';
		echo '<div class="sazan-sec sazan-blog skin-' . esc_attr( $skin ) . '">';
		$this->render_section_header( $s );
		echo '<div class="sazan-blog-grid">';

		if ( 'posts' === $s['source'] ) {
			$args = array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $s['posts_count'],
			);
			if ( ! empty( $s['posts_cat'] ) ) { $args['category_name'] = sanitize_title( $s['posts_cat'] ); }
			$q = new \WP_Query( $args );
			if ( $q->have_posts() ) {
				while ( $q->have_posts() ) {
					$q->the_post();
					$id   = get_the_ID();
					$cats = get_the_category();
					$tag  = ! empty( $cats ) ? $cats[0]->name : '';
					$views = '';
					if ( ! empty( $s['views_meta'] ) ) {
						$v = get_post_meta( $id, sanitize_key( $s['views_meta'] ), true );
						if ( '' !== $v && null !== $v ) { $views = $v . ' ' . esc_html__( 'بازدید', 'sazan-core' ); }
					} elseif ( class_exists( '\\Sazan\\Views_Counter' ) ) {
						$views = \Sazan\Views_Counter::format( $id );
					}
					$this->render_card( array(
						'title' => get_the_title(),
						'desc'  => wp_trim_words( get_the_excerpt(), 14, '…' ),
						'image' => get_the_post_thumbnail_url( $id, 'medium' ),
						'tag'   => $tag,
						'views' => $views,
						'date'  => get_the_date(),
						'btn'   => $s['btn_text'],
						'link'  => get_permalink(),
					) );
				}
				wp_reset_postdata();
			} else {
				echo '<p class="sazan-empty">' . esc_html__( 'نوشته‌ای یافت نشد.', 'sazan-core' ) . '</p>';
			}
		} else {
			foreach ( (array) $s['items'] as $it ) {
				$this->render_card( array(
					'title' => $it['title'],
					'desc'  => $it['desc'],
					'image' => ! empty( $it['image']['url'] ) ? $it['image']['url'] : '',
					'tag'   => $it['tag'],
					'views' => $it['views'],
					'date'  => $it['date'],
					'btn'   => $it['btn'],
					'link'  => ! empty( $it['link']['url'] ) ? $it['link']['url'] : '#',
				) );
			}
		}

		echo '</div></div>';
	}
}
