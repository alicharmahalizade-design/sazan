<?php
/**
 * Independent Elementor widgets for the Sazan single-blog experience.
 *
 * @package Sazan\Widgets\SingleBlog
 */

namespace Sazan\Widgets\Single_Blog;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;
use Sazan\Base\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) { exit; }

function widget_classes() {
	return array(
		__NAMESPACE__ . '\\Article_Hero',
		__NAMESPACE__ . '\\Article_Content',
		__NAMESPACE__ . '\\Download_Card',
		__NAMESPACE__ . '\\Article_Outline',
		__NAMESPACE__ . '\\Tags_Share',
		__NAMESPACE__ . '\\Comments',
		__NAMESPACE__ . '\\Related_Posts',
		__NAMESPACE__ . '\\Author_Card',
		__NAMESPACE__ . '\\Table_Of_Contents',
		__NAMESPACE__ . '\\Side_CTA',
		__NAMESPACE__ . '\\Wide_CTA',
		__NAMESPACE__ . '\\Features_Strip',
		__NAMESPACE__ . '\\Footer',
	);
}

abstract class Base extends Widget_Base {

	public function get_categories() { return array( 'sazan-single-blog' ); }
	public function get_style_depends() { return array( 'sazan-single-blog' ); }
	public function get_script_depends() { return array( 'sazan-single-blog' ); }
	public function get_keywords() { return array( 'sazan', 'تک بلاگ', 'single post', 'مقاله', 'وبلاگ' ); }

	protected function post_id() {
		$id = get_queried_object_id();
		if ( $id && 'post' === get_post_type( $id ) ) { return (int) $id; }
		$id = get_the_ID();
		return $id && 'post' === get_post_type( $id ) ? (int) $id : 0;
	}

	protected function common_style_controls( $title_selector = '.sazan-sb-card-title' ) {
		$this->start_controls_section( 'sb_common_style', array( 'label' => 'استایل تک بلاگ', 'tab' => Controls_Manager::TAB_STYLE ) );
		$colors = array(
			'sb_accent' => array( 'رنگ فیروزه‌ای', '#10bceb', '--sb-accent' ),
			'sb_text'   => array( 'رنگ متن اصلی', '#f4f8ff', '--sb-text' ),
			'sb_muted'  => array( 'رنگ متن فرعی', '#8e9caf', '--sb-muted' ),
			'sb_card'   => array( 'پس‌زمینه کارت', '#081224', '--sb-card' ),
			'sb_border' => array( 'رنگ حاشیه', '#12314a', '--sb-border' ),
		);
		foreach ( $colors as $key => $def ) {
			$this->add_control( $key, array(
				'label' => $def[0], 'type' => Controls_Manager::COLOR, 'default' => $def[1],
				'selectors' => array( '{{WRAPPER}} .sazan-sb-widget' => $def[2] . ': {{VALUE}};' ),
			) );
		}
		$this->add_responsive_control( 'sb_radius', array(
			'label' => 'گردی گوشه‌ها', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 32 ) ),
			'default' => array( 'unit' => 'px', 'size' => 14 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-widget' => '--sb-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name' => 'sb_title_typography', 'label' => 'تایپوگرافی عنوان',
			'selector' => '{{WRAPPER}} ' . $title_selector,
		) );
		$this->end_controls_section();
	}

	protected function banner_url() {
		return SAZAN_CORE_URL . 'assets/img/sazan-consultant-banner.png';
	}

	protected function fallback_hero_url() {
		return SAZAN_CORE_URL . 'assets/img/sazan-blog-fallback.png';
	}

	protected function read_time( $id ) {
		$text  = wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) );
		$words = preg_split( '/[\s\x{200c}]+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
		return max( 1, (int) ceil( count( (array) $words ) / 190 ) );
	}

	protected function headings( $id = 0 ) {
		$html = $id ? (string) get_post_field( 'post_content', $id ) : '';
		$out  = array();
		if ( $html && preg_match_all( '/<h([2-4])[^>]*>(.*?)<\/h\1>/isu', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $index => $match ) {
				$title = trim( wp_strip_all_tags( $match[2] ) );
				if ( '' !== $title ) {
					$out[] = array( 'id' => 'sazan-article-heading-' . ( $index + 1 ), 'title' => $title, 'level' => (int) $match[1] );
				}
			}
		}
		if ( ! $out ) {
			$defaults = array( 'شناخت بازار و تحلیل رقبا', 'تعیین اهداف SMART', 'شناخت مخاطب هدف', 'تحلیل رقبا و تدوین پیام برند', 'انتخاب کانال‌ها و تاکتیک‌های بازاریابی', 'اندازه‌گیری و بهینه‌سازی مداوم' );
			foreach ( $defaults as $index => $title ) {
				$out[] = array( 'id' => 'sazan-article-heading-' . ( $index + 1 ), 'title' => $title, 'level' => 2 );
			}
		}
		return $out;
	}

	protected function add_heading_ids( $html ) {
		$index = 0;
		return preg_replace_callback( '/<h([2-4])([^>]*)>/isu', function ( $match ) use ( &$index ) {
			$index++;
			$attrs = preg_replace( '/\s+id=("|\').*?\1/isu', '', $match[2] );
			return '<h' . $match[1] . $attrs . ' id="sazan-article-heading-' . $index . '">';
		}, $html );
	}

	public static function icon( $name, $class = '' ) {
		$paths = array(
			'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/>',
			'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-5 3.5-7 8-7s7.2 2 8 7"/>',
			'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
			'quote' => '<path d="M7 10H3v6h6v-6c0-4 1-6 4-8M18 10h-4v6h6v-6c0-4 1-6 4-8"/>',
			'download' => '<path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 19h16"/>',
			'document' => '<path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/>',
			'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>',
			'tag' => '<path d="M20 13 13 20 3 10V3h7z"/><circle cx="7.5" cy="7.5" r="1"/>',
			'share' => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.4m-7.6 6.8 7.6 4.4"/>',
			'message' => '<path d="M4 4h16v12H8l-4 4z"/>',
			'phone' => '<path d="M7 3H4c-1 9 8 18 17 17v-3l-5-2-2 2c-3-1-6-4-7-7l2-2z"/>',
			'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
			'pin' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
			'book' => '<path d="M4 5c4-1 6 0 8 2v14c-2-2-4-3-8-2zm16 0c-4-1-6 0-8 2v14c2-2 4-3 8-2z"/>',
			'consult' => '<circle cx="12" cy="8" r="4"/><path d="M5 21c1-5 3-7 7-7s6 2 7 7M18 4l2-2m-1 6h3"/>',
			'learn' => '<path d="m3 8 9-5 9 5-9 5z"/><path d="M6 10v6c4 3 8 3 12 0v-6"/>',
		);
		$body = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['check'];
		return '<svg class="sazan-sb-icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
	}

	protected function panel_title( $title ) {
		return '<h2 class="sazan-sb-card-title"><span>' . esc_html( $title ) . '</span></h2>';
	}
}

class Article_Hero extends Base {
	public function get_name() { return 'sazan-sb-article-hero'; }
	public function get_title() { return 'تک بلاگ — سربرگ مقاله'; }
	public function get_icon() { return 'eicon-post-title'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'fallback_title', array( 'label' => 'عنوان پیش‌فرض', 'type' => Controls_Manager::TEXTAREA, 'default' => '۷ گام برای تدوین استراتژی بازاریابی مؤثر' ) );
		$this->add_control( 'badge', array( 'label' => 'برچسب بالای عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'استراتژی' ) );
		$this->add_control( 'show_breadcrumb', array( 'label' => 'نمایش مسیر راهنما', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'show_meta', array( 'label' => 'نمایش اطلاعات نوشته', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'fallback_image', array(
			'label'   => 'تصویر جایگزین',
			'type'    => Controls_Manager::MEDIA,
			'default' => array( 'url' => $this->fallback_hero_url() ),
		) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-article-title' );
	}
	protected function render() {
		$s     = $this->get_settings_for_display();
		$id    = $this->post_id();
		$title = $id ? get_the_title( $id ) : $s['fallback_title'];
		$cats  = $id ? get_the_category( $id ) : array();
		$cat   = $cats ? $cats[0] : null;
		$badge = $cat ? $cat->name : $s['badge'];
		$image_id = $id ? get_post_thumbnail_id( $id ) : 0;
		if ( ! $image_id && ! empty( $s['fallback_image']['id'] ) ) { $image_id = absint( $s['fallback_image']['id'] ); }
		$image = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
		if ( ! $image && ! empty( $s['fallback_image']['url'] ) ) { $image = $s['fallback_image']['url']; }
		echo '<header class="sazan-sb-widget sazan-sb-article-hero">';
		if ( 'yes' === $s['show_breadcrumb'] ) {
			echo '<nav class="sazan-sb-breadcrumb" aria-label="مسیر راهنما"><a href="' . esc_url( home_url( '/' ) ) . '">خانه</a><span>‹</span><a href="' . esc_url( get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ) ) . '">مقالات</a>';
			if ( $cat ) { echo '<span>‹</span><a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>'; }
			echo '<span aria-hidden="true">‹</span><span class="current" aria-current="page">' . esc_html( wp_trim_words( $title, 8 ) ) . '</span></nav>';
		}
		if ( $badge ) { echo '<span class="sazan-sb-badge">' . esc_html( $badge ) . '</span>'; }
		echo '<h1 class="sazan-sb-article-title">' . esc_html( $title ) . '</h1>';
		if ( 'yes' === $s['show_meta'] ) {
			$author_id = $id ? (int) get_post_field( 'post_author', $id ) : get_current_user_id();
			echo '<div class="sazan-sb-meta">';
			$author_name = get_the_author_meta( 'display_name', $author_id ) ?: 'سامان حسینی';
			echo '<span class="author">' . get_avatar( $author_id, 28, '', $author_name, array( 'extra_attr' => 'loading="lazy" decoding="async"' ) ) . 'نویسنده: ' . esc_html( $author_name ) . '</span>';
			echo '<time datetime="' . esc_attr( $id ? get_post_time( DATE_W3C, true, $id ) : '' ) . '">' . self::icon( 'calendar' ) . esc_html( $id ? get_the_date( '', $id ) : '۲۵ خرداد ۱۴۰۵' ) . '</time>';
			echo '<span>' . self::icon( 'clock' ) . esc_html( ( $id ? $this->read_time( $id ) : 8 ) . ' دقیقه مطالعه' ) . '</span>';
			if ( $id && class_exists( '\\Sazan\\Views_Counter' ) ) { echo '<span>' . self::icon( 'eye' ) . esc_html( \Sazan\Views_Counter::format( $id ) ) . '</span>'; }
			echo '</div>';
		}
		if ( $image ) {
			if ( $image_id ) {
				$alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
				$image_html = wp_get_attachment_image( $image_id, 'full', false, array(
					'alt'           => $alt ? $alt : $title,
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'decoding'      => 'async',
					'sizes'         => '(max-width: 767px) calc(100vw - 32px), (max-width: 1200px) 60vw, 836px',
				) );
			} else {
				$is_default = false !== strpos( $image, 'sazan-blog-fallback.png' );
				$image_html = '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $title ) . '"' . ( $is_default ? ' width="1881" height="836"' : '' ) . ' loading="eager" fetchpriority="high" decoding="async">';
			}
			echo '<figure class="sazan-sb-featured">' . $image_html . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<div class="sazan-sb-featured sazan-sb-featured-placeholder"><span></span><i></i></div>';
		}
		echo '</header>';
	}
}

class Article_Content extends Base {
	public function get_name() { return 'sazan-sb-article-content'; }
	public function get_title() { return 'تک بلاگ — محتوای مقاله'; }
	public function get_icon() { return 'eicon-post-content'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'source', array( 'label' => 'منبع', 'type' => Controls_Manager::SELECT, 'default' => 'post', 'options' => array( 'post' => 'محتوای نوشته جاری', 'manual' => 'محتوای دستی' ) ) );
		$this->add_control( 'manual', array( 'label' => 'محتوای دستی', 'type' => Controls_Manager::WYSIWYG, 'condition' => array( 'source' => 'manual' ) ) );
		$this->add_control( 'show_quote', array( 'label' => 'نمایش خلاصه نقل‌قولی', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'quote', array( 'label' => 'متن نقل‌قول جایگزین', 'type' => Controls_Manager::TEXTAREA, 'default' => 'استراتژی بازاریابی مؤثر نقشه راهی است که شما را از جایی که هستید به جایی که می‌خواهید برسید؛ بدون استراتژی، حتی بهترین تاکتیک‌ها هم پراکنده خواهند بود.' ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-article-body h2' );
	}
	private function demo_content() {
		return '<p>در دنیای رقابتی امروز داشتن یک استراتژی بازاریابی قوی دیگر یک انتخاب نیست؛ بلکه یک ضرورت است. یک استراتژی خوب به شما کمک می‌کند تا منابع خود را بهینه استفاده کنید، مشتریان هدف خود را بهتر بشناسید و در نهایت فروش و برند خود را رشد دهید.</p>
		<h2>۱. شناخت بازار و تحلیل رقبا</h2><div class="sazan-sb-demo-visual visual-analytics"></div><p>اولین قدم در تدوین استراتژی بازاریابی، شناخت دقیق بازار هدف و رقبا است. باید بدانید مشتریان شما چه کسانی هستند، چه نیازهایی دارند و چگونه این نیازها را برآورده می‌کنند.</p>
		<h2>۲. تعیین اهداف SMART</h2><div class="sazan-sb-demo-visual visual-target"></div><p>اهداف شما باید مشخص، قابل اندازه‌گیری، قابل دستیابی، مرتبط و زمان‌مند باشند. استفاده از اهداف SMART کمک می‌کند مسیر بازاریابی شفاف و قابل ارزیابی باشد.</p>
		<h2>۳. شناخت مخاطب هدف</h2><div class="sazan-sb-demo-visual visual-network"></div><p>بدون شناخت دقیق مخاطب هدف، هیچ استراتژی بازاریابی موفق نخواهد بود. پرسونای مشتری را بر اساس داده‌ها و الگوهای رفتاری تدوین کنید.</p>';
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = $this->post_id();
		if ( 'manual' === $s['source'] ) {
			$content = (string) $s['manual'];
		} else {
			$content = $id ? (string) get_post_field( 'post_content', $id ) : '';
			$content = $content ? apply_filters( 'the_content', $content ) : $this->demo_content();
		}
		if ( class_exists( '\\Sazan\\Single_Blog' ) ) {
			$content = \Sazan\Single_Blog::clean_legacy_shortcodes( $content );
		}
		$content = $this->add_heading_ids( $content );
		$quote   = $id ? get_the_excerpt( $id ) : '';
		if ( ! $quote ) { $quote = $s['quote']; }
		echo '<article class="sazan-sb-widget sazan-sb-article-content">';
		if ( 'yes' === $s['show_quote'] && $quote ) {
			echo '<blockquote class="sazan-sb-lead-quote">' . self::icon( 'quote' ) . '<p>' . esc_html( $quote ) . '</p></blockquote>';
		}
		echo '<div class="sazan-sb-article-body">' . wp_kses_post( $content ) . '</div>';
		echo '</article>';
	}
}

class Download_Card extends Base {
	public function get_name() { return 'sazan-sb-download'; }
	public function get_title() { return 'تک بلاگ — کارت دانلود'; }
	public function get_icon() { return 'eicon-download-button'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'source', array(
			'label'   => 'منبع اطلاعات',
			'type'    => Controls_Manager::SELECT,
			'default' => 'post_meta',
			'options' => array(
				'post_meta' => 'فیلدهای نوشته جاری (پیشنهادی)',
				'manual'    => 'تنظیمات دستی Elementor',
			),
		) );
		$this->add_control( 'post_meta_help', array(
			'type'      => Controls_Manager::RAW_HTML,
			'raw'       => 'فایل و متن کارت را در ویرایش نوشته، از جعبه «تنظیمات تک بلاگ سازان» وارد کنید. اگر فیلد فعال یا فایل انتخاب نشده باشد، کارت روی آن نوشته نمایش داده نمی‌شود.',
			'condition' => array( 'source' => 'post_meta' ),
		) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'راهنمای جامع تدوین استراتژی بازاریابی', 'condition' => array( 'source' => 'manual' ) ) );
		$this->add_control( 'description', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'default' => 'فایل PDF رایگان شامل چک‌لیست‌ها و قالب‌های کاربردی', 'condition' => array( 'source' => 'manual' ) ) );
		$this->add_control( 'button', array( 'label' => 'متن دکمه', 'type' => Controls_Manager::TEXT, 'default' => 'دانلود رایگان', 'condition' => array( 'source' => 'manual' ) ) );
		$this->add_control( 'file', array( 'label' => 'فایل یا لینک', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ), 'condition' => array( 'source' => 'manual' ) ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-download-copy h3' );
	}
	protected function render() {
		$s           = $this->get_settings_for_display();
		$title       = $s['title'];
		$description = $s['description'];
		$button      = $s['button'];
		$file        = is_array( $s['file'] ) ? $s['file'] : array( 'url' => '#' );
		$id          = $this->post_id();

		if ( 'post_meta' === $s['source'] && $id && class_exists( '\\Sazan\\Single_Blog_Meta' ) ) {
			$data = \Sazan\Single_Blog_Meta::download_data( $id );
			if ( ! $data['configured'] || ! $data['enabled'] || ! $data['url'] ) {
				return;
			}
			$title       = $data['title'] ? $data['title'] : 'راهنمای جامع تدوین استراتژی بازاریابی';
			$description = $data['description'] ? $data['description'] : 'فایل تکمیلی این مقاله را از کتابخانه رسانه وردپرس دانلود کنید.';
			$button      = $data['button'] ? $data['button'] : 'دانلود رایگان';
			$file        = array( 'url' => $data['url'] );
		}

		$this->add_link_attributes( 'download', $file );
		if ( ! empty( $file['url'] ) && '#' !== $file['url'] ) {
			$this->add_render_attribute( 'download', 'download', '' );
		}
		echo '<section class="sazan-sb-widget sazan-sb-download-card"><span class="sazan-sb-doc">' . self::icon( 'document' ) . '</span><div class="sazan-sb-download-copy"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $description ) . '</p></div><a class="sazan-sb-button" ' . $this->get_render_attribute_string( 'download' ) . '>' . self::icon( 'download' ) . esc_html( $button ) . '</a></section>';
	}
}

class Article_Outline extends Base {
	public function get_name() { return 'sazan-sb-outline'; }
	public function get_title() { return 'تک بلاگ — سرفصل‌های بازشو'; }
	public function get_icon() { return 'eicon-accordion'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'start', array( 'label' => 'شروع نمایش از سرفصل', 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 20 ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-outline summary' );
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$items = array_slice( $this->headings( $this->post_id() ), max( 0, (int) $s['start'] - 1 ) );
		if ( ! $items ) { return; }
		echo '<section class="sazan-sb-widget sazan-sb-outline">';
		foreach ( $items as $index => $item ) {
			echo '<details><summary><span>' . self::icon( 'check' ) . esc_html( ( $index + (int) $s['start'] ) . '. ' . $item['title'] ) . '</span><i aria-hidden="true"></i></summary><div>این بخش از متن اصلی مقاله خوانده می‌شود. برای مشاهده جزئیات کامل، به سرفصل متن مراجعه کنید.</div></details>';
		}
		echo '</section>';
	}
}

class Tags_Share extends Base {
	public function get_name() { return 'sazan-sb-tags-share'; }
	public function get_title() { return 'تک بلاگ — برچسب و اشتراک'; }
	public function get_icon() { return 'eicon-tags'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'fallback_tags', array( 'label' => 'برچسب‌های پیش‌فرض', 'type' => Controls_Manager::TEXT, 'default' => 'استراتژی بازاریابی، برنامه بازاریابی، کسب‌وکار، دیجیتال مارکتینگ، مدیریت' ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-share-label' );
	}
	protected function render() {
		$s    = $this->get_settings_for_display();
		$id   = $this->post_id();
		$tags = $id ? wp_get_post_tags( $id ) : array();
		if ( ! $tags ) {
			$tags = array_map( function ( $name ) { return (object) array( 'name' => trim( $name ), 'term_id' => 0 ); }, explode( '،', $s['fallback_tags'] ) );
		}
		$url   = $id ? get_permalink( $id ) : home_url( '/' );
		$title = $id ? get_the_title( $id ) : 'مقاله سازان';
		echo '<section class="sazan-sb-widget sazan-sb-tags-share"><div class="sazan-sb-tags"><b>' . self::icon( 'tag' ) . 'برچسب‌ها:</b>';
		foreach ( $tags as $tag ) { echo '<a href="' . esc_url( $tag->term_id ? get_tag_link( $tag ) : '#' ) . '">' . esc_html( $tag->name ) . '</a>'; }
		echo '</div><div class="sazan-sb-share"><span class="sazan-sb-share-label">اشتراک‌گذاری:</span>';
		$links = array(
			'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ),
			'telegram' => 'https://t.me/share/url?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ),
			'twitter' => 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ),
		);
		foreach ( $links as $name => $link ) { echo '<a aria-label="' . esc_attr( $name ) . '" href="' . esc_url( $link ) . '" target="_blank" rel="noopener"><span>' . esc_html( strtoupper( substr( $name, 0, 1 ) ) ) . '</span></a>'; }
		echo '<button type="button" class="sazan-sb-copy-link" data-copy="' . esc_url( $url ) . '" aria-label="کپی لینک">' . self::icon( 'share' ) . '</button></div></section>';
	}
}

class Comments extends Base {
	public function get_name() { return 'sazan-sb-comments'; }
	public function get_title() { return 'تک بلاگ — دیدگاه‌ها'; }
	public function get_icon() { return 'eicon-comments'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'دیدگاه‌ها' ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-comments-title' );
	}
	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = $this->post_id();
		$count = $id ? get_comments_number( $id ) : 3;
		echo '<section class="sazan-sb-widget sazan-sb-comments"><h2 class="sazan-sb-comments-title">' . esc_html( $s['title'] ) . ' <small>(' . esc_html( $count ) . ')</small></h2>';
		if ( $id ) {
			$comments = get_comments( array( 'post_id' => $id, 'status' => 'approve', 'number' => 3 ) );
			if ( $comments ) { echo '<ul class="sazan-sb-comment-list">'; wp_list_comments( array( 'style' => 'ul', 'avatar_size' => 38, 'short_ping' => true ), $comments ); echo '</ul>'; }
			if ( comments_open( $id ) ) {
				comment_form( array(
					'title_reply' => '', 'label_submit' => 'ارسال نظر',
					'comment_field' => '<p class="comment-form-comment"><label for="comment" class="screen-reader-text">متن دیدگاه</label><textarea id="comment" name="comment" rows="3" required placeholder="نظر خود را بنویسید..."></textarea></p>',
				), $id );
			}
		} else {
			echo '<form class="sazan-sb-demo-comment"><label class="screen-reader-text" for="sazan-demo-comment">متن دیدگاه</label><textarea id="sazan-demo-comment" placeholder="نظر خود را بنویسید..."></textarea><button type="button" class="sazan-sb-button">ارسال نظر</button></form>';
		}
		echo '</section>';
	}
}

class Related_Posts extends Base {
	public function get_name() { return 'sazan-sb-related'; }
	public function get_title() { return 'تک بلاگ — مطالب مرتبط'; }
	public function get_icon() { return 'eicon-post-list'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'مطالب مرتبط' ) );
		$this->add_control( 'count', array( 'label' => 'تعداد', 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 8 ) );
		$this->end_controls_section();
		$this->common_style_controls();
	}
	protected function render() {
		$s = $this->get_settings_for_display(); $id = $this->post_id();
		$args = array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => (int) $s['count'], 'post__not_in' => $id ? array( $id ) : array() );
		$cats = $id ? wp_get_post_categories( $id ) : array(); if ( $cats ) { $args['category__in'] = $cats; }
		$q = new \WP_Query( $args );
		echo '<aside class="sazan-sb-widget sazan-sb-card sazan-sb-related">' . $this->panel_title( $s['title'] ) . '<div class="sazan-sb-related-list">';
		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) { $q->the_post(); $thumb = get_the_post_thumbnail( get_the_ID(), 'thumbnail', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
				echo '<a class="sazan-sb-related-item" href="' . esc_url( get_permalink() ) . '"><span class="thumb">' . ( $thumb ? $thumb : self::icon( 'book' ) ) . '</span><span><b>' . esc_html( get_the_title() ) . '</b><small>' . self::icon( 'clock' ) . esc_html( max( 1, $this->read_time( get_the_ID() ) ) . ' دقیقه مطالعه' ) . '</small></span></a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			wp_reset_postdata();
		} else {
			foreach ( array( 'چگونه برند شخصی قدرتمند بسازیم؟', 'تحلیل بازار؛ راهنمای جامع کسب‌وکارها', '۱۰ اشتباه رایج در بازاریابی دیجیتال', 'نقشه پیگیری مشتری وفادار' ) as $index => $title ) {
				echo '<div class="sazan-sb-related-item sazan-sb-related-placeholder"><span class="thumb demo-thumb t' . ( $index + 1 ) . '" aria-hidden="true"></span><span><b>' . esc_html( $title ) . '</b><small>' . self::icon( 'clock' ) . esc_html( ( 10 + $index ) . ' دقیقه مطالعه' ) . '</small></span></div>';
			}
		}
		echo '</div></aside>';
	}
}

class Author_Card extends Base {
	public function get_name() { return 'sazan-sb-author'; }
	public function get_title() { return 'تک بلاگ — درباره نویسنده'; }
	public function get_icon() { return 'eicon-person'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'متن جایگزین' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان کارت', 'type' => Controls_Manager::TEXT, 'default' => 'درباره نویسنده' ) );
		$this->add_control( 'fallback_name', array( 'label' => 'نام', 'type' => Controls_Manager::TEXT, 'default' => 'سامان حسینی' ) );
		$this->add_control( 'fallback_role', array( 'label' => 'سمت', 'type' => Controls_Manager::TEXT, 'default' => 'مشاور و مدرس کسب‌وکار' ) );
		$this->add_control( 'fallback_bio', array( 'label' => 'زندگی‌نامه', 'type' => Controls_Manager::TEXTAREA, 'default' => 'بیش از ۱۰ سال تجربه در حوزه استراتژی کسب‌وکار و بازاریابی دیجیتال؛ مشاور بیش از ۲۰۰ کسب‌وکار در ایران.' ) );
		$this->add_control( 'image', array( 'label' => 'تصویر جایگزین', 'type' => Controls_Manager::MEDIA ) );
		$this->end_controls_section();
		$this->common_style_controls();
	}
	protected function render() {
		$s = $this->get_settings_for_display(); $id = $this->post_id(); $aid = $id ? (int) get_post_field( 'post_author', $id ) : 0;
		$name = $aid ? get_the_author_meta( 'display_name', $aid ) : $s['fallback_name'];
		$bio  = $aid ? get_the_author_meta( 'description', $aid ) : $s['fallback_bio']; if ( ! $bio ) { $bio = $s['fallback_bio']; }
		$avatar = ! empty( $s['image']['url'] ) ? '<img src="' . esc_url( $s['image']['url'] ) . '" alt="' . esc_attr( $name ) . '">' : get_avatar( $aid, 112, '', $name );
		echo '<aside class="sazan-sb-widget sazan-sb-card sazan-sb-author" aria-label="اطلاعات نویسنده">' . $this->panel_title( $s['title'] ) . '<div class="sazan-sb-author-avatar">' . $avatar . '</div><h3>' . esc_html( $name ) . '</h3><strong>' . esc_html( $s['fallback_role'] ) . '</strong><p>' . esc_html( $bio ) . '</p></aside>';
	}
}

class Table_Of_Contents extends Base {
	public function get_name() { return 'sazan-sb-toc'; }
	public function get_title() { return 'تک بلاگ — فهرست مطالب'; }
	public function get_icon() { return 'eicon-table-of-contents'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'دسته‌بندی مطالب' ) );
		$this->end_controls_section();
		$this->common_style_controls();
	}
	protected function render() {
		$s = $this->get_settings_for_display(); $items = $this->headings( $this->post_id() );
		echo '<nav class="sazan-sb-widget sazan-sb-card sazan-sb-toc" aria-label="فهرست مقاله">' . $this->panel_title( $s['title'] ) . '<ol>';
		foreach ( $items as $index => $item ) { echo '<li><a href="#' . esc_attr( $item['id'] ) . '"><span>' . esc_html( $index + 1 ) . '</span>' . esc_html( $item['title'] ) . '</a></li>'; }
		echo '</ol></nav>';
	}
}

class Side_CTA extends Base {
	public function get_name() { return 'sazan-sb-side-cta'; }
	public function get_title() { return 'تک بلاگ — CTA کناری'; }
	public function get_icon() { return 'eicon-call-to-action'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'نیاز به مشاوره دارید؟' ) );
		$this->add_control( 'text', array( 'label' => 'متن', 'type' => Controls_Manager::TEXTAREA, 'default' => 'از مشاوره تخصصی ما برای رشد کسب‌وکار خود استفاده کنید.' ) );
		$this->add_control( 'button', array( 'label' => 'متن دکمه', 'type' => Controls_Manager::TEXT, 'default' => 'مشاوره رایگان' ) );
		$this->add_control( 'link', array( 'label' => 'لینک', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'image', array( 'label' => 'تصویر پس‌زمینه جایگزین', 'type' => Controls_Manager::MEDIA ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-side-cta h2' );
	}
	protected function render() {
		$s = $this->get_settings_for_display(); $img = ! empty( $s['image']['url'] ) ? $s['image']['url'] : $this->banner_url(); $this->add_link_attributes( 'cta', $s['link'] );
		echo '<aside class="sazan-sb-widget sazan-sb-side-cta" style="--sb-cta-image:url(' . esc_url( $img ) . ')"><h2>' . esc_html( $s['title'] ) . '</h2><p>' . esc_html( $s['text'] ) . '</p><a class="sazan-sb-button" ' . $this->get_render_attribute_string( 'cta' ) . '>' . esc_html( $s['button'] ) . '</a></aside>';
	}
}

class Wide_CTA extends Base {
	public function get_name() { return 'sazan-sb-wide-cta'; }
	public function get_title() { return 'تک بلاگ — CTA سراسری'; }
	public function get_icon() { return 'eicon-call-to-action'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'برای دریافت مشاوره تخصصی با مشاوران سازان ارتباط شوید' ) );
		$this->add_control( 'text', array( 'label' => 'متن', 'type' => Controls_Manager::TEXTAREA, 'default' => 'تیم مشاوران ما آماده ارائه راهکارهای تخصصی متناسب با نیازهای کسب‌وکار شما هستند.' ) );
		$this->add_control( 'button', array( 'label' => 'متن دکمه', 'type' => Controls_Manager::TEXT, 'default' => 'مشاوره رایگان' ) );
		$this->add_control( 'link', array( 'label' => 'لینک', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'image', array( 'label' => 'تصویر بنر', 'type' => Controls_Manager::MEDIA ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-wide-cta h2' );
	}
	protected function render() {
		$s = $this->get_settings_for_display(); $img = ! empty( $s['image']['url'] ) ? $s['image']['url'] : $this->banner_url(); $this->add_link_attributes( 'cta', $s['link'] );
		echo '<section class="sazan-sb-widget sazan-sb-wide-cta" style="--sb-cta-image:url(' . esc_url( $img ) . ')"><div><h2>' . esc_html( $s['title'] ) . '</h2><p>' . esc_html( $s['text'] ) . '</p></div><a class="sazan-sb-button" ' . $this->get_render_attribute_string( 'cta' ) . '>' . esc_html( $s['button'] ) . '</a></section>';
	}
}

class Features_Strip extends Base {
	public function get_name() { return 'sazan-sb-features'; }
	public function get_title() { return 'تک بلاگ — نوار مزایا'; }
	public function get_icon() { return 'eicon-icon-box'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'آیتم‌ها' ) );
		$r = new Repeater();
		$r->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'راهکارهای جامع و معتبر' ) );
		$r->add_control( 'text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXT, 'default' => 'ارائه گواهینامه‌های قابل ترجمه' ) );
		$r->add_control( 'icon', array( 'label' => 'آیکن', 'type' => Controls_Manager::SELECT, 'default' => 'book', 'options' => array( 'book' => 'کتاب', 'learn' => 'آموزش', 'consult' => 'مشاوره', 'message' => 'محتوا' ) ) );
		$this->add_control( 'items', array( 'label' => 'مزایا', 'type' => Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ title }}}', 'default' => array(
			array( 'title' => 'گواهینامه معتبر', 'text' => 'ارائه گواهینامه قابل ترجمه', 'icon' => 'book' ),
			array( 'title' => 'محتوای به‌روز', 'text' => 'مقالات و آموزش‌های روز دنیا', 'icon' => 'message' ),
			array( 'title' => 'مشاوره تخصصی', 'text' => 'پشتیبانی و راهنمایی کسب‌وکار', 'icon' => 'consult' ),
			array( 'title' => 'آموزش‌های کاربردی', 'text' => 'بیش از ۲۰۰ دوره تخصصی', 'icon' => 'learn' ),
		) ) );
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-feature-copy b' );
	}
	protected function render() {
		$s = $this->get_settings_for_display(); echo '<section class="sazan-sb-widget sazan-sb-features">';
		foreach ( (array) $s['items'] as $item ) { echo '<div class="sazan-sb-feature"><span>' . self::icon( $item['icon'] ) . '</span><div class="sazan-sb-feature-copy"><b>' . esc_html( $item['title'] ) . '</b><small>' . esc_html( $item['text'] ) . '</small></div></div>'; }
		echo '</section>';
	}
}

class Footer extends Base {
	public function get_name() { return 'sazan-sb-footer'; }
	public function get_title() { return 'تک بلاگ — فوتر کامل'; }
	public function get_icon() { return 'eicon-footer'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوای اصلی' ) );
		$this->add_control( 'footer_aria_label', array( 'label' => 'برچسب دسترس‌پذیری فوتر', 'type' => Controls_Manager::TEXT, 'default' => 'اطلاعات تکمیلی وب‌سایت' ) );
		$this->add_control( 'contact_title', array( 'label' => 'عنوان اطلاعات تماس', 'type' => Controls_Manager::TEXT, 'default' => 'اطلاعات تماس' ) );
		$this->add_control( 'about_title', array( 'label' => 'عنوان درباره', 'type' => Controls_Manager::TEXT, 'default' => 'درباره سازان' ) );
		$this->add_control( 'about', array( 'label' => 'متن درباره', 'type' => Controls_Manager::TEXTAREA, 'default' => 'سازان، مرکز آموزش و مشاوره تخصصی کسب‌وکار است. با ما مسیر رشد حرفه‌ای و هوشمندانه کسب‌وکار خود را بسازید.' ) );
		$this->add_control( 'phone', array( 'label' => 'تلفن', 'type' => Controls_Manager::TEXT, 'default' => '۰۲۱-۹۱۰۰۴۴۲۹' ) );
		$this->add_control( 'email', array( 'label' => 'ایمیل', 'type' => Controls_Manager::TEXT, 'default' => 'info@sazan.com' ) );
		$this->add_control( 'address', array( 'label' => 'آدرس', 'type' => Controls_Manager::TEXTAREA, 'default' => 'تهران، خیابان ولیعصر، پلاک ۱۳۳' ) );
		$this->add_control( 'hours', array( 'label' => 'ساعات کاری', 'type' => Controls_Manager::TEXT, 'default' => 'شنبه تا پنجشنبه ۹ تا ۱۸' ) );
		$this->add_control( 'categories_title', array( 'label' => 'عنوان دسته‌بندی‌ها', 'type' => Controls_Manager::TEXT, 'default' => 'دسته‌بندی‌ها' ) );
		$this->add_control( 'categories_count', array( 'label' => 'تعداد دسته‌بندی‌ها', 'type' => Controls_Manager::NUMBER, 'default' => 5, 'min' => 0, 'max' => 20 ) );
		$this->add_control( 'quick_title', array( 'label' => 'عنوان دسترسی سریع', 'type' => Controls_Manager::TEXT, 'default' => 'دسترسی سریع' ) );
		$this->add_control( 'copyright', array( 'label' => 'کپی‌رایت', 'type' => Controls_Manager::TEXT, 'default' => '© ۱۴۰۵ تمامی حقوق این وب‌سایت متعلق به سازان است.' ) );
		$this->add_control( 'show_backtop', array( 'label' => 'نمایش بازگشت به بالا', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'quick_links_section', array( 'label' => 'لینک‌های دسترسی سریع' ) );
		$quick = new Repeater();
		$quick->add_control( 'label', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'عنوان لینک' ) );
		$quick->add_control( 'url', array( 'label' => 'لینک', 'type' => Controls_Manager::URL, 'placeholder' => 'https://example.com' ) );
		$this->add_control( 'quick_links', array( 'label' => 'لینک‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $quick->get_controls(), 'title_field' => '{{{ label }}}', 'default' => array(
			array( 'label' => 'صفحه اصلی', 'url' => array( 'url' => '/' ) ),
			array( 'label' => 'مقالات', 'url' => array( 'url' => '/blog/' ) ),
			array( 'label' => 'مشاوره کسب‌وکار', 'url' => array() ),
			array( 'label' => 'درباره ما', 'url' => array() ),
			array( 'label' => 'تماس با ما', 'url' => array() ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'social_section', array( 'label' => 'شبکه‌های اجتماعی' ) );
		$social = new Repeater();
		$social->add_control( 'label', array( 'label' => 'نام شبکه', 'type' => Controls_Manager::TEXT, 'default' => 'Instagram' ) );
		$social->add_control( 'icon', array( 'label' => 'آیکون شبکه', 'type' => Controls_Manager::ICONS, 'fa4compatibility' => 'icon_fallback', 'default' => array( 'value' => 'fab fa-instagram', 'library' => 'fa-brands' ) ) );
		$social->add_control( 'icon_fallback', array( 'label' => 'متن/نماد جایگزین', 'type' => Controls_Manager::TEXT, 'default' => '◎', 'description' => 'اگر آیکون انتخاب نشود نمایش داده می‌شود.' ) );
		$social->add_control( 'url', array( 'label' => 'لینک پروفایل', 'type' => Controls_Manager::URL, 'placeholder' => 'https://instagram.com/...' ) );
		$this->add_control( 'social_links', array( 'label' => 'شبکه‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $social->get_controls(), 'title_field' => '{{{ label }}}', 'default' => array(
			array( 'label' => 'LinkedIn', 'icon_fallback' => 'in', 'url' => array() ), array( 'label' => 'Telegram', 'icon_fallback' => 'tg', 'url' => array() ), array( 'label' => 'X', 'icon_fallback' => 'x', 'url' => array() ), array( 'label' => 'Instagram', 'icon_fallback' => '◎', 'url' => array() ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'legal_section', array( 'label' => 'لینک‌های حقوقی' ) );
		$legal = new Repeater();
		$legal->add_control( 'label', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'سیاست حریم خصوصی' ) );
		$legal->add_control( 'url', array( 'label' => 'لینک', 'type' => Controls_Manager::URL ) );
		$this->add_control( 'legal_links', array( 'label' => 'لینک‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $legal->get_controls(), 'title_field' => '{{{ label }}}', 'default' => array(
			array( 'label' => 'سیاست حریم خصوصی', 'url' => array() ), array( 'label' => 'قوانین و مقررات', 'url' => array() ), array( 'label' => 'سوالات متداول', 'url' => array() ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'footer_style', array( 'label' => 'استایل فوتر', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'footer_background', 'selector' => '{{WRAPPER}} .sazan-sb-footer' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'footer_border', 'selector' => '{{WRAPPER}} .sazan-sb-footer' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'footer_shadow', 'selector' => '{{WRAPPER}} .sazan-sb-footer' ) );
		$this->add_responsive_control( 'footer_padding', array( 'label' => 'فاصله داخلی', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em', '%' ), 'selectors' => array( '{{WRAPPER}} .sazan-sb-footer' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'columns_gap', array( 'label' => 'فاصله ستون‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'selectors' => array( '{{WRAPPER}} .sazan-sb-footer-grid' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'heading_color', array( 'label' => 'رنگ عنوان‌ها', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-footer h3' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'footer_heading_typography', 'label' => 'تایپوگرافی عنوان‌ها', 'selector' => '{{WRAPPER}} .sazan-sb-footer h3' ) );
		$this->add_control( 'body_color', array( 'label' => 'رنگ متن', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-footer p, {{WRAPPER}} .sazan-sb-footer li, {{WRAPPER}} .sazan-sb-footer-bottom' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'footer_body_typography', 'label' => 'تایپوگرافی متن', 'selector' => '{{WRAPPER}} .sazan-sb-footer p, {{WRAPPER}} .sazan-sb-footer li, {{WRAPPER}} .sazan-sb-footer-bottom' ) );
		$this->add_control( 'link_color', array( 'label' => 'رنگ لینک', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-footer a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'link_hover_color', array( 'label' => 'رنگ لینک در هاور/فوکوس', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-footer a:hover, {{WRAPPER}} .sazan-sb-footer a:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'contact_icons_style', array( 'label' => 'آیکون‌های اطلاعات تماس', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'contact_icon_gap', array(
			'label' => 'فاصله آیکون و متن', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default' => array( 'unit' => 'px', 'size' => 9 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-footer .contact li' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'contact_icon_box_size', array(
			'label' => 'اندازه کادر آیکون', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 16, 'max' => 80 ) ),
			'default' => array( 'unit' => 'px', 'size' => 28 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'contact_icon_size', array(
			'label' => 'اندازه خود آیکون', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 8, 'max' => 48 ) ),
			'default' => array( 'unit' => 'px', 'size' => 15 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon .sazan-sb-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'contact_icon_offset', array(
			'label' => 'جابجایی عمودی', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => -20, 'max' => 20 ) ),
			'default' => array( 'unit' => 'px', 'size' => 0 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'transform: translateY({{SIZE}}{{UNIT}});' ),
		) );
		$this->add_control( 'contact_icon_stroke', array(
			'label' => 'ضخامت خطوط آیکون', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0.5, 'max' => 4, 'step' => 0.1 ) ),
			'default' => array( 'unit' => 'px', 'size' => 1.7 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon .sazan-sb-icon' => 'stroke-width: {{SIZE}};' ),
		) );
		$this->add_control( 'contact_icon_radius', array(
			'label' => 'گردی کادر', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ),
			'default' => array( 'top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50, 'unit' => '%', 'isLinked' => true ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'contact_icon_border', 'selector' => '{{WRAPPER}} .sazan-sb-contact-icon' ) );
		$this->start_controls_tabs( 'contact_icon_state_tabs' );
		$this->start_controls_tab( 'contact_icon_normal', array( 'label' => 'عادی' ) );
		$this->add_control( 'contact_icon_color', array( 'label' => 'رنگ آیکون', 'type' => Controls_Manager::COLOR, 'default' => '#10bceb', 'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'contact_icon_background', array( 'label' => 'پس‌زمینه کادر', 'type' => Controls_Manager::COLOR, 'default' => 'rgba(16,188,235,0.08)', 'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'contact_icon_border_color', array( 'label' => 'رنگ حاشیه', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'contact_icon_shadow', 'selector' => '{{WRAPPER}} .sazan-sb-contact-icon' ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'contact_icon_hover', array( 'label' => 'هاور/فوکوس' ) );
		$this->add_control( 'contact_icon_hover_color', array( 'label' => 'رنگ آیکون', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .contact li:hover .sazan-sb-contact-icon, {{WRAPPER}} .contact li:focus-within .sazan-sb-contact-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'contact_icon_hover_background', array( 'label' => 'پس‌زمینه کادر', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .contact li:hover .sazan-sb-contact-icon, {{WRAPPER}} .contact li:focus-within .sazan-sb-contact-icon' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'contact_icon_hover_border_color', array( 'label' => 'رنگ حاشیه', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .contact li:hover .sazan-sb-contact-icon, {{WRAPPER}} .contact li:focus-within .sazan-sb-contact-icon' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'contact_icon_hover_shadow', 'selector' => '{{WRAPPER}} .contact li:hover .sazan-sb-contact-icon, {{WRAPPER}} .contact li:focus-within .sazan-sb-contact-icon' ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_control( 'contact_icon_transition', array(
			'label' => 'سرعت تغییر حالت (میلی‌ثانیه)', 'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1000, 'step' => 50, 'default' => 200,
			'selectors' => array( '{{WRAPPER}} .sazan-sb-contact-icon' => 'transition-duration: {{VALUE}}ms;' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'social_icons_style', array( 'label' => 'آیکون‌های شبکه‌های اجتماعی', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'social_icons_alignment', array(
			'label' => 'تراز آیکون‌ها', 'type' => Controls_Manager::CHOOSE, 'default' => 'flex-start',
			'options' => array(
				'flex-start' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'flex-end' => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-footer .sazan-sb-socials' => 'justify-content: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'social_icons_gap', array(
			'label' => 'فاصله بین آیکون‌ها', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
			'default' => array( 'unit' => 'px', 'size' => 9 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-socials' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'social_icon_box_size', array(
			'label' => 'اندازه کادر آیکون', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 28, 'max' => 96 ) ),
			'default' => array( 'unit' => 'px', 'size' => 38 ),
			'tablet_default' => array( 'unit' => 'px', 'size' => 44 ),
			'mobile_default' => array( 'unit' => 'px', 'size' => 44 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'social_icon_size', array(
			'label' => 'اندازه خود آیکون', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 8, 'max' => 52 ) ),
			'default' => array( 'unit' => 'px', 'size' => 15 ),
			'selectors' => array(
				'{{WRAPPER}} .sazan-sb-socials a' => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .sazan-sb-socials a svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );
		$this->add_control( 'social_icon_radius', array(
			'label' => 'گردی کادر', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ),
			'default' => array( 'top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50, 'unit' => '%', 'isLinked' => true ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'social_icon_border', 'selector' => '{{WRAPPER}} .sazan-sb-socials a' ) );
		$this->start_controls_tabs( 'social_icon_state_tabs' );
		$this->start_controls_tab( 'social_icon_normal', array( 'label' => 'عادی' ) );
		$this->add_control( 'social_icon_color', array( 'label' => 'رنگ آیکون', 'type' => Controls_Manager::COLOR, 'default' => '#b8c4d0', 'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'social_icon_background', array( 'label' => 'پس‌زمینه', 'type' => Controls_Manager::COLOR, 'default' => '#081224', 'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'social_icon_border_color', array( 'label' => 'رنگ حاشیه', 'type' => Controls_Manager::COLOR, 'default' => '#132337', 'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'social_icon_shadow', 'selector' => '{{WRAPPER}} .sazan-sb-socials a' ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'social_icon_hover', array( 'label' => 'هاور/فوکوس' ) );
		$this->add_control( 'social_icon_hover_color', array( 'label' => 'رنگ آیکون', 'type' => Controls_Manager::COLOR, 'default' => '#10bceb', 'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a:hover, {{WRAPPER}} .sazan-sb-socials a:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'social_icon_hover_background', array( 'label' => 'پس‌زمینه', 'type' => Controls_Manager::COLOR, 'default' => 'rgba(12,143,184,0.16)', 'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a:hover, {{WRAPPER}} .sazan-sb-socials a:focus-visible' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'social_icon_hover_border_color', array( 'label' => 'رنگ حاشیه', 'type' => Controls_Manager::COLOR, 'default' => '#10bceb', 'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a:hover, {{WRAPPER}} .sazan-sb-socials a:focus-visible' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'social_icon_hover_shadow', 'selector' => '{{WRAPPER}} .sazan-sb-socials a:hover, {{WRAPPER}} .sazan-sb-socials a:focus-visible' ) );
		$this->add_responsive_control( 'social_icon_hover_lift', array(
			'label' => 'حرکت رو به بالا', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 20 ) ),
			'default' => array( 'unit' => 'px', 'size' => 3 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a:hover, {{WRAPPER}} .sazan-sb-socials a:focus-visible' => 'transform: translateY(-{{SIZE}}{{UNIT}});' ),
		) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_control( 'social_icon_transition', array(
			'label' => 'سرعت تغییر حالت (میلی‌ثانیه)', 'type' => Controls_Manager::NUMBER,
			'min' => 0, 'max' => 1000, 'step' => 50, 'default' => 200,
			'selectors' => array( '{{WRAPPER}} .sazan-sb-socials a' => 'transition-duration: {{VALUE}}ms;' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'backtop_icon_style', array( 'label' => 'دکمه بازگشت به بالا', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'backtop_box_size', array(
			'label' => 'اندازه دکمه', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 36, 'max' => 90 ) ),
			'default' => array( 'unit' => 'px', 'size' => 48 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'backtop_icon_size', array(
			'label' => 'اندازه نماد', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 10, 'max' => 44 ) ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'font-size: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'backtop_right_offset', array(
			'label' => 'فاصله از راست', 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => -60, 'max' => 100 ) ),
			'default' => array( 'unit' => 'px', 'size' => -22 ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'right: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'backtop_radius', array(
			'label' => 'گردی دکمه', 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', '%' ),
			'default' => array( 'top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50, 'unit' => '%', 'isLinked' => true ),
			'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'backtop_border', 'selector' => '{{WRAPPER}} .sazan-sb-backtop' ) );
		$this->start_controls_tabs( 'backtop_state_tabs' );
		$this->start_controls_tab( 'backtop_normal', array( 'label' => 'عادی' ) );
		$this->add_control( 'backtop_color', array( 'label' => 'رنگ نماد', 'type' => Controls_Manager::COLOR, 'default' => '#10bceb', 'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'backtop_background', array( 'label' => 'پس‌زمینه', 'type' => Controls_Manager::COLOR, 'default' => '#061525', 'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'backtop_border_color', array( 'label' => 'رنگ حاشیه', 'type' => Controls_Manager::COLOR, 'default' => '#0b6d91', 'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'backtop_shadow', 'selector' => '{{WRAPPER}} .sazan-sb-backtop' ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'backtop_hover', array( 'label' => 'هاور/فوکوس' ) );
		$this->add_control( 'backtop_hover_color', array( 'label' => 'رنگ نماد', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop:hover, {{WRAPPER}} .sazan-sb-backtop:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'backtop_hover_background', array( 'label' => 'پس‌زمینه', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop:hover, {{WRAPPER}} .sazan-sb-backtop:focus-visible' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'backtop_hover_border_color', array( 'label' => 'رنگ حاشیه', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-sb-backtop:hover, {{WRAPPER}} .sazan-sb-backtop:focus-visible' => 'border-color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'backtop_hover_shadow', 'selector' => '{{WRAPPER}} .sazan-sb-backtop:hover, {{WRAPPER}} .sazan-sb-backtop:focus-visible' ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();
		$this->common_style_controls( '.sazan-sb-footer h3' );
	}

	private function render_links( $items, $attribute_prefix ) {
		foreach ( (array) $items as $index => $item ) {
			$label = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
			$url   = isset( $item['url']['url'] ) ? trim( (string) $item['url']['url'] ) : '';
			if ( '' === $label || '' === $url || '#' === $url ) { continue; }
			$key = $attribute_prefix . '-' . $index;
			$this->add_link_attributes( $key, $item['url'] );
			echo '<li><a ' . $this->get_render_attribute_string( $key ) . '>' . esc_html( $label ) . '</a></li>';
		}
	}
	protected function render() {
		$s = $this->get_settings_for_display();
		$cats = get_categories( array( 'number' => absint( $s['categories_count'] ), 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true ) );
		$phone_href = preg_replace( '/[^0-9+]/', '', strtr( (string) $s['phone'], array( '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9' ) ) );
		$email = sanitize_email( $s['email'] );
		echo '<footer class="sazan-sb-widget sazan-sb-footer" aria-label="' . esc_attr( $s['footer_aria_label'] ) . '"><div class="sazan-sb-footer-grid">';
		echo '<section aria-labelledby="sazan-footer-contact-' . esc_attr( $this->get_id() ) . '"><h3 id="sazan-footer-contact-' . esc_attr( $this->get_id() ) . '">' . esc_html( $s['contact_title'] ) . '</h3><address><ul class="contact">';
		if ( $phone_href ) { echo '<li><span class="sazan-sb-contact-icon">' . self::icon( 'phone' ) . '</span><a dir="ltr" href="tel:' . esc_attr( $phone_href ) . '" aria-label="تلفن: ' . esc_attr( $s['phone'] ) . '">' . esc_html( $s['phone'] ) . '</a></li>'; }
		if ( $email ) { echo '<li><span class="sazan-sb-contact-icon">' . self::icon( 'mail' ) . '</span><a dir="ltr" href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a></li>'; }
		if ( $s['address'] ) { echo '<li><span class="sazan-sb-contact-icon">' . self::icon( 'pin' ) . '</span><span>' . esc_html( $s['address'] ) . '</span></li>'; }
		if ( $s['hours'] ) { echo '<li><span class="sazan-sb-contact-icon">' . self::icon( 'clock' ) . '</span><span>' . esc_html( $s['hours'] ) . '</span></li>'; }
		echo '</ul></address></section>';
		echo '<nav aria-labelledby="sazan-footer-categories-' . esc_attr( $this->get_id() ) . '"><h3 id="sazan-footer-categories-' . esc_attr( $this->get_id() ) . '">' . esc_html( $s['categories_title'] ) . '</h3><ul>';
		if ( $cats ) { foreach ( $cats as $cat ) { echo '<li><a href="' . esc_url( get_category_link( $cat ) ) . '">' . esc_html( $cat->name ) . '<span class="screen-reader-text"> (' . esc_html( sprintf( '%d نوشته', $cat->count ) ) . ')</span></a></li>'; } }
		echo '</ul></nav>';
		echo '<nav aria-labelledby="sazan-footer-quick-' . esc_attr( $this->get_id() ) . '"><h3 id="sazan-footer-quick-' . esc_attr( $this->get_id() ) . '">' . esc_html( $s['quick_title'] ) . '</h3><ul>';
		$this->render_links( $s['quick_links'], 'quick-link' );
		echo '</ul></nav>';
		echo '<section class="about" aria-labelledby="sazan-footer-about-' . esc_attr( $this->get_id() ) . '"><h3 id="sazan-footer-about-' . esc_attr( $this->get_id() ) . '">' . esc_html( $s['about_title'] ) . '</h3><p>' . esc_html( $s['about'] ) . '</p><nav class="sazan-sb-socials" aria-label="شبکه‌های اجتماعی">';
		foreach ( (array) $s['social_links'] as $index => $item ) { $url = isset( $item['url']['url'] ) ? trim( (string) $item['url']['url'] ) : ''; if ( ! $url || '#' === $url ) { continue; } $key = 'social-' . $index; $this->add_link_attributes( $key, $item['url'] ); $icon = ! empty( $item['icon']['value'] ) ? Icons_Manager::render_icon( $item['icon'], array( 'aria-hidden' => 'true' ) ) : '<span aria-hidden="true">' . esc_html( isset( $item['icon_fallback'] ) ? $item['icon_fallback'] : ( isset( $item['text'] ) ? $item['text'] : '' ) ) . '</span>'; echo '<a ' . $this->get_render_attribute_string( $key ) . ' aria-label="' . esc_attr( $item['label'] ) . '" rel="me noopener noreferrer">' . $icon . '</a>'; }
		echo '</nav></section></div><div class="sazan-sb-footer-bottom"><span>' . esc_html( $s['copyright'] ) . '</span><nav aria-label="پیوندهای حقوقی"><ul>';
		$this->render_links( $s['legal_links'], 'legal-link' );
		echo '</ul></nav>';
		if ( 'yes' === $s['show_backtop'] ) { echo '<button type="button" class="sazan-sb-backtop" aria-label="بازگشت به ابتدای صفحه" title="بازگشت به بالا">⌃</button>'; }
		echo '</div></footer>';
	}
}
