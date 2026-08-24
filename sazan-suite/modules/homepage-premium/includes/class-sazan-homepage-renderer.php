<?php
/**
 * Exact reference renderer shared by Elementor and shortcode output.
 *
 * @package Sazan\HomepagePremium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sazan_Homepage_Premium_Renderer {

	private static $body_cache = null;

	public static function defaults() {
		return array(
			'logo'                => array( 'url' => SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/logo-sazan.png' ),
			'logo_link'           => home_url( '/' ),
			'menu_id'             => 0,
			'account_text'        => 'ورود / عضویت',
			'account_url'         => home_url( '/my-account/' ),
			'hero_title'          => 'دوره حکمرانی بر بازار',
			'hero_summary'        => 'یاد بگیرید چطور با تصمیم‌های دقیق، مذاکره مؤثر و شناخت بازار، رشد کسب‌وکار را آگاهانه هدایت کنید.',
			'hero_image'          => array( 'url' => SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/حکمرانی-بر-بازار.webp' ),
			'primary_text'        => 'مشاهده و ثبت‌نام',
			'primary_url'         => '#courses',
			'services_title'      => 'مسیر مناسب رشد خود را انتخاب کنید',
			'courses_title'       => 'دوره‌های در حال ثبت‌نام',
			'consultation_title'  => 'مسیر درست کسب‌وکارتان را با اطمینان انتخاب کنید',
			'about_title'         => 'سازان؛ همراه رشد واقعی کسب‌وکار',
			'calendar_title'      => 'تقویم آموزشی سازان',
			'articles_title'      => 'بینش‌های کاربردی برای رشد کسب‌وکار',
			'footer_title'        => 'برای حرکت بعدی کسب‌وکارت آماده‌ای؟',
		);
	}

	private static function nav_markup( $menu_id, $class, $label ) {
		$items = $menu_id ? wp_get_nav_menu_items( absint( $menu_id ) ) : array();
		if ( ! $items || is_wp_error( $items ) ) {
			$links = array(
				'#consulting' => 'مشاوره کسب و کار',
				'#articles'   => 'مقالات',
				'#courses'    => 'دوره‌ها',
				'#calendar'   => 'ارزیابی',
				'#about'      => 'سازان',
			);
			$html = '';
			foreach ( $links as $url => $title ) {
				$html .= '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';
			}
			return '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( $label ) . '">' . $html . '</nav>';
		}

		$html = '';
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent !== 0 ) {
				continue;
			}
			$html .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
		}
		return '<nav class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( $label ) . '">' . $html . '</nav>';
	}

	private static function footer_nav_markup( $menu_id, $heading, $label, $fallback ) {
		$items = $menu_id ? wp_get_nav_menu_items( absint( $menu_id ) ) : array();
		$links = '';
		if ( $items && ! is_wp_error( $items ) ) {
			foreach ( $items as $item ) {
				if ( 0 === (int) $item->menu_item_parent ) { $links .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>'; }
			}
		} else {
			foreach ( $fallback as $title => $url ) { $links .= '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'; }
		}
		return '<nav aria-label="' . esc_attr( $label ) . '"><h2>' . esc_html( $heading ) . '</h2>' . $links . '</nav>';
	}

	private static function body() {
		if ( null !== self::$body_cache ) {
			return self::$body_cache;
		}
		$file     = SAZAN_HOMEPAGE_PREMIUM_PATH . 'templates/homepage.html';
		$document = file_exists( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		self::$body_cache = ( $document && preg_match( '~<body[^>]*>(.*)</body>~isu', $document, $match ) ) ? $match[1] : '';
		return self::$body_cache;
	}

	private static function between( $html, $start, $end = '' ) {
		$from = '' === $start ? 0 : strpos( $html, $start );
		if ( false === $from ) {
			return '';
		}
		$to = '' === $end ? strlen( $html ) : strpos( $html, $end, $from + strlen( $start ) );
		if ( false === $to ) {
			$to = strlen( $html );
		}
		return substr( $html, $from, $to - $from );
	}

	private static function fragment( $name ) {
		$body = self::body();
		$markers = array(
			'hero'         => '<section class="hero-section"',
			'services'     => '<section class="services-section',
			'courses'      => '<section class="courses-section',
			'consultation' => '<section class="consultation-banner',
			'about'        => '<section class="about-section"',
			'calendar'     => '<section class="calendar-section',
			'articles'     => '<section class="articles-section',
			'footer'       => '<footer class="site-footer',
		);

		switch ( $name ) {
			case 'header':
				$html = self::between( $body, '', '<main id="main">' );
				$html = str_replace( 'href="#main"', 'href="#top"', $html );
				break;
			case 'hero':
				$html = self::between( $body, $markers['hero'], $markers['services'] );
				break;
			case 'services':
				$html = self::between( $body, $markers['services'], $markers['courses'] );
				break;
			case 'courses':
				$html = self::between( $body, $markers['courses'], $markers['consultation'] );
				break;
			case 'consultation':
				$html = self::between( $body, $markers['consultation'], $markers['about'] );
				break;
			case 'about':
				$html = self::between( $body, $markers['about'], $markers['calendar'] );
				break;
			case 'calendar':
				$html = self::between( $body, $markers['calendar'], $markers['articles'] );
				break;
			case 'articles':
				$html = self::between( $body, $markers['articles'], '</main>' );
				break;
			case 'footer':
				$html = self::between( $body, $markers['footer'] );
				break;
			default:
				$html = '';
		}
		return str_replace( 'assets/images/', SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/', $html );
	}

	private static function url_value( $value, $fallback = '#' ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['url'] ) ? $value['url'] : '';
		}
		return $value ? $value : $fallback;
	}

	private static function link_attrs( $link, $class = '' ) {
		$url = self::url_value( $link );
		$attrs = ' href="' . esc_url( $url ) . '"';
		if ( $class ) {
			$attrs .= ' class="' . esc_attr( $class ) . '"';
		}
		if ( is_array( $link ) && ! empty( $link['is_external'] ) ) {
			$attrs .= ' target="_blank"';
		}
		if ( is_array( $link ) && ! empty( $link['nofollow'] ) ) {
			$attrs .= ' rel="nofollow"';
		}
		return $attrs;
	}

	private static function replace_element_inner( $html, $open_marker, $content, $tag = 'div' ) {
		$open = strpos( $html, $open_marker );
		if ( false === $open ) {
			return $html;
		}
		$start = strpos( $html, '>', $open );
		if ( false === $start ) {
			return $html;
		}
		$start++;
		$tail = substr( $html, $start );
		preg_match_all( '~</?' . preg_quote( $tag, '~' ) . '\\b[^>]*>~iu', $tail, $matches, PREG_OFFSET_CAPTURE );
		$depth = 1;
		foreach ( $matches[0] as $match ) {
			$is_close = 0 === strpos( $match[0], '</' );
			$depth += $is_close ? -1 : 1;
			if ( 0 === $depth ) {
				$end = $start + $match[1];
				return substr( $html, 0, $start ) . $content . substr( $html, $end );
			}
		}
		return $html;
	}

	private static function heading_tag( $html, $tag, $selector_text = '' ) {
		$allowed = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		$tag = in_array( $tag, $allowed, true ) ? $tag : 'h2';
		if ( $selector_text ) {
			$quoted = preg_quote( $selector_text, '~' );
			return preg_replace( '~<h[1-6]([^>]*)>' . $quoted . '</h[1-6]>~u', '<' . $tag . '$1>' . $selector_text . '</' . $tag . '>', $html, 1 );
		}
		return $html;
	}

	/** دریافت آخرین نوشته‌های منتشرشده برای ویجت داینامیک مقالات. */
	private static function latest_articles( $settings ) {
		if ( ! class_exists( 'WP_Query' ) ) {
			return null;
		}
		$allowed_orderby = array( 'date', 'modified', 'title', 'comment_count', 'rand' );
		$orderby = in_array( $settings['orderby'] ?? 'date', $allowed_orderby, true ) ? $settings['orderby'] : 'date';
		$order = 'ASC' === strtoupper( $settings['order'] ?? 'DESC' ) ? 'ASC' : 'DESC';
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 12, absint( $settings['posts_per_page'] ?? 4 ) ) ),
			'orderby'             => $orderby,
			'order'               => $order,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( ! empty( $settings['category_id'] ) ) {
			$args['cat'] = absint( $settings['category_id'] );
		}

		$query = new \WP_Query( $args );
		$articles = array();
		$excerpt_words = max( 8, min( 50, absint( $settings['excerpt_words'] ?? 18 ) ) );
		foreach ( $query->posts as $post ) {
			setup_postdata( $post );
			$post_id = (int) $post->ID;
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			$image_data = $thumbnail_id ? wp_get_attachment_image_src( $thumbnail_id, 'medium_large' ) : false;
			$image_url = $image_data ? $image_data[0] : SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/5-زنگ-خطر-برای-کسب-و-کار-300x167.webp';
			$image_alt = $thumbnail_id ? get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) : '';
			$categories = get_the_category( $post_id );
			$excerpt = get_the_excerpt( $post_id );
			if ( ! $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $excerpt_words, '…' );
			} else {
				$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), $excerpt_words, '…' );
			}
			$articles[] = array(
				'image'        => array( 'url' => $image_url ),
				'image_alt'    => $image_alt ? $image_alt : get_the_title( $post_id ),
				'image_width'  => $image_data ? (int) $image_data[1] : 300,
				'image_height' => $image_data ? (int) $image_data[2] : 167,
				'image_srcset' => $thumbnail_id ? wp_get_attachment_image_srcset( $thumbnail_id, 'medium_large' ) : '',
				'image_sizes'  => $thumbnail_id ? wp_get_attachment_image_sizes( $thumbnail_id, 'medium_large' ) : '',
				'category'     => $categories ? $categories[0]->name : '',
				'views'        => $settings['dynamic_badge'] ?? 'مقاله جدید',
				'title'        => get_the_title( $post_id ),
				'excerpt'      => $excerpt,
				'date'         => get_the_date( '', $post_id ),
				'button_text'  => $settings['dynamic_button_text'] ?? 'مطالعه مقاله',
				'link'         => array( 'url' => get_permalink( $post_id ) ),
			);
		}
		wp_reset_postdata();
		return $articles;
	}

	private static function wrap( $name, $html, $settings ) {
		$id = ! empty( $settings['section_id'] ) ? sanitize_html_class( $settings['section_id'] ) : '';
		if ( $id ) {
			$html = preg_replace_callback( '~<(section|header|footer)\\b([^>]*)>~iu', function( $match ) use ( $id ) {
				$attrs = preg_replace( '~\\s+id=("[^"]*"|\'[^\']*\')~iu', '', $match[2] );
				return '<' . $match[1] . $attrs . ' id="' . esc_attr( $id ) . '">';
			}, $html, 1 );
		}
		$label = ! empty( $settings['aria_label'] ) ? ' aria-label="' . esc_attr( $settings['aria_label'] ) . '"' : '';
		$schema = '';
		if ( ! empty( $settings['schema_type'] ) ) {
			$allowed = array( 'WebPageElement', 'ItemList', 'Course', 'Organization' );
			if ( in_array( $settings['schema_type'], $allowed, true ) ) {
				$schema = ' itemscope itemtype="https://schema.org/' . esc_attr( $settings['schema_type'] ) . '"';
			}
		}
		return '<div class="sazan-premium-section-widget sazan-premium-' . esc_attr( $name ) . '-widget"' . $label . $schema . ' dir="rtl">' . $html . '</div>';
	}

	public static function section( $name, $settings = array() ) {
		$settings = (array) $settings;
		$html = self::fragment( $name );
		if ( ! $html ) {
			return '';
		}

		switch ( $name ) {
			case 'header':
				$logo = ! empty( $settings['logo']['url'] ) ? $settings['logo']['url'] : self::defaults()['logo']['url'];
				$html = preg_replace( '~(<a class="brand" href=")[^"]*("[^>]*>\s*<img src=")[^"]*~isu', '$1' . esc_url( self::url_value( $settings['logo_link'] ?? home_url( '/' ) ) ) . '$2' . esc_url( $logo ), $html, 1 );
				$html = preg_replace( '~(<a class="brand"[^>]*>\s*<img[^>]*\salt=")[^"]*~isu', '$1' . esc_attr( $settings['logo_alt'] ?? 'سازان' ), $html, 1 );
				$html = preg_replace( '~<nav class="desktop-nav".*?</nav>~isu', self::nav_markup( $settings['menu_id'] ?? 0, 'desktop-nav', $settings['menu_aria'] ?? 'منوی اصلی' ), $html, 1 );
				$html = preg_replace( '~<nav class="mobile-nav".*?</nav>~isu', self::nav_markup( $settings['menu_id'] ?? 0, 'mobile-nav', $settings['menu_aria'] ?? 'منوی موبایل' ), $html, 1 );
				$html = preg_replace( '~<a class="account-link"[^>]*>.*?</a>~isu', '<a' . self::link_attrs( $settings['account_link'] ?? '#', 'account-link' ) . '><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.7-3.7 3.1-5.5 6.5-5.5s5.8 1.8 6.5 5.5"/></svg><span>' . esc_html( $settings['account_text'] ?? 'ورود / عضویت' ) . '</span></a>', $html, 1 );
				$html = str_replace( 'جستجو در سازان', esc_html( $settings['search_label'] ?? 'جستجو در سازان' ), $html );
				$html = str_replace( 'placeholder="جستجو کنید..."', 'placeholder="' . esc_attr( $settings['search_placeholder'] ?? 'جستجو کنید...' ) . '"', $html );
				$html = str_replace( '<button type="submit">جستجو</button>', '<button type="submit">' . esc_html( $settings['search_button'] ?? 'جستجو' ) . '</button>', $html );
				break;

			case 'hero':
				$slides = ! empty( $settings['slides'] ) && is_array( $settings['slides'] ) ? $settings['slides'] : array();
				if ( $slides ) {
					$first = $slides[0];
					$image = ! empty( $first['image']['url'] ) ? $first['image']['url'] : self::defaults()['hero_image']['url'];
					$html = preg_replace( '~(<img class="hero-character" src=")[^"]*(" alt=")[^"]*~isu', '$1' . esc_url( $image ) . '$2' . esc_attr( $first['image_alt'] ?? $first['title'] ?? '' ), $html, 1 );
					$html = preg_replace( '~<h1>.*?</h1>~isu', '<' . esc_attr( $settings['heading_tag'] ?? 'h1' ) . '>' . esc_html( $first['title'] ?? '' ) . '</' . esc_attr( $settings['heading_tag'] ?? 'h1' ) . '>', $html, 1 );
					$html = preg_replace( '~<p class="hero-summary">.*?</p>~isu', '<p class="hero-summary">' . esc_html( $first['summary'] ?? '' ) . '</p>', $html, 1 );
					$html = preg_replace( '~<span class="hero-ribbon">.*?</span>~isu', '<span class="hero-ribbon">' . esc_html( $first['ribbon'] ?? '' ) . '</span>', $html, 1 );
					$primary = '<a' . self::link_attrs( $first['primary_link'] ?? '#courses', 'primary-button' ) . '>' . esc_html( $first['primary_text'] ?? 'مشاهده و ثبت‌نام' ) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></a>';
					$secondary = '<a' . self::link_attrs( $first['secondary_link'] ?? '#courses', 'secondary-button' ) . '><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>' . esc_html( $first['secondary_text'] ?? 'سرفصل‌های دوره' ) . '</a>';
					$html = preg_replace( '~<div class="hero-buttons">.*?</div>~isu', '<div class="hero-buttons">' . $primary . $secondary . '</div>', $html, 1 );
					$slide_data = array();
					foreach ( $slides as $slide ) {
						$slide_data[] = array(
							'title' => $slide['title'] ?? '', 'image' => ! empty( $slide['image']['url'] ) ? $slide['image']['url'] : '', 'alt' => $slide['image_alt'] ?? '', 'ribbon' => $slide['ribbon'] ?? '', 'summary' => $slide['summary'] ?? '',
							'primaryText' => $slide['primary_text'] ?? '', 'primaryUrl' => self::url_value( $slide['primary_link'] ?? '#' ), 'secondaryText' => $slide['secondary_text'] ?? '', 'secondaryUrl' => self::url_value( $slide['secondary_link'] ?? '#' ),
						);
					}
					$digits = array( '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9' );
					$days = absint( strtr( (string) ( $settings['days'] ?? '38' ), $digits ) ); $hours = absint( strtr( (string) ( $settings['hours'] ?? '21' ), $digits ) ); $minutes = absint( strtr( (string) ( $settings['minutes'] ?? '41' ), $digits ) ); $seconds = absint( strtr( (string) ( $settings['seconds'] ?? '30' ), $digits ) );
					$countdown = ( ( ( $days * 24 ) + $hours ) * 60 + $minutes ) * 60 + $seconds;
					$html = preg_replace( '~(<div class="hero-stage")~u', '$1 data-assets-url="' . esc_url( SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/' ) . '" data-countdown-seconds="' . esc_attr( $countdown ) . '" data-slides="' . esc_attr( wp_json_encode( $slide_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . '"', $html, 1 );
					$dots = '';
					foreach ( $slides as $index => $slide ) {
						$dots .= '<button' . ( 0 === $index ? ' class="active" aria-current="true"' : '' ) . ' type="button" aria-label="اسلاید ' . esc_attr( $index + 1 ) . '"></button>';
					}
					$html = self::replace_element_inner( $html, '<div class="hero-dots"', $dots );
				}
				$html = str_replace( 'مسیر آموزشی ویژه مدیران آینده', esc_html( $settings['eyebrow'] ?? 'مسیر آموزشی ویژه مدیران آینده' ), $html );
				$html = str_replace( '<b>۴.۹ از ۵</b><small>امتیاز هنرجویان</small>', '<b>' . esc_html( $settings['rating_value'] ?? '۴.۹ از ۵' ) . '</b><small>' . esc_html( $settings['rating_label'] ?? 'امتیاز هنرجویان' ) . '</small>', $html );
				$html = str_replace( '<b>+۲,۰۰۰</b><small>هنرجوی این مسیر</small>', '<b>' . esc_html( $settings['learners_value'] ?? '+۲,۰۰۰' ) . '</b><small>' . esc_html( $settings['learners_label'] ?? 'هنرجوی این مسیر' ) . '</small>', $html );
				if ( ! empty( $settings['features'] ) ) {
					$features = '';
					foreach ( $settings['features'] as $feature ) { $features .= '<span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg><b>' . esc_html( $feature['title'] ?? '' ) . '</b><small>' . esc_html( $feature['subtitle'] ?? '' ) . '</small></span>'; }
					$html = self::replace_element_inner( $html, '<div class="feature-chips"', $features );
				}
				$html = str_replace( '<b>۳۸</b><small>روز</small>', '<b>' . esc_html( $settings['days'] ?? '۳۸' ) . '</b><small>' . esc_html( $settings['days_label'] ?? 'روز' ) . '</small>', $html );
				$html = str_replace( '<b>۲۱</b><small>ساعت</small>', '<b>' . esc_html( $settings['hours'] ?? '۲۱' ) . '</b><small>' . esc_html( $settings['hours_label'] ?? 'ساعت' ) . '</small>', $html );
				$html = str_replace( '<b>۴۱</b><small>دقیقه</small>', '<b>' . esc_html( $settings['minutes'] ?? '۴۱' ) . '</b><small>' . esc_html( $settings['minutes_label'] ?? 'دقیقه' ) . '</small>', $html );
				$html = str_replace( '<b>۳۰</b><small>ثانیه</small>', '<b>' . esc_html( $settings['seconds'] ?? '۳۰' ) . '</b><small>' . esc_html( $settings['seconds_label'] ?? 'ثانیه' ) . '</small>', $html );
				$html = str_replace( '<span>ظرفیت دوره</span><strong>فقط ۵ صندلی باقی‌مانده</strong>', '<span>' . esc_html( $settings['capacity_label'] ?? 'ظرفیت دوره' ) . '</span><strong>' . esc_html( $settings['capacity_text'] ?? 'فقط ۵ صندلی باقی‌مانده' ) . '</strong>', $html );
				if ( ! empty( $settings['teacher_image']['url'] ) ) { $html = preg_replace( '~(<span class="tutor-avatar"><img src=")[^"]*(" alt=")[^"]*~isu', '$1' . esc_url( $settings['teacher_image']['url'] ) . '$2' . esc_attr( $settings['teacher_alt'] ?? '' ), $html, 1 ); }
				$html = str_replace( '<small>مدرس دوره</small><strong>عباس شانه‌سازان</strong><em>مدیریت و بازاریابی</em>', '<small>' . esc_html( $settings['teacher_label'] ?? 'مدرس دوره' ) . '</small><strong>' . esc_html( $settings['teacher_name'] ?? 'عباس شانه‌سازان' ) . '</strong><em>' . esc_html( $settings['teacher_role'] ?? 'مدیریت و بازاریابی' ) . '</em>', $html );
				$html = str_replace( 'ضمانت کیفیت آموزش', esc_html( $settings['proof_1'] ?? 'ضمانت کیفیت آموزش' ), $html );
				$html = str_replace( 'دسترسی همیشگی', esc_html( $settings['proof_2'] ?? 'دسترسی همیشگی' ), $html );
				$html = str_replace( 'پشتیبانی واقعی', esc_html( $settings['proof_3'] ?? 'پشتیبانی واقعی' ), $html );
				break;

			case 'services':
				$html = str_replace( 'SAZAN ECOSYSTEM', esc_html( $settings['kicker'] ?? 'SAZAN ECOSYSTEM' ), $html );
				$title = $settings['title'] ?? 'مسیر مناسب رشد خود را انتخاب کنید';
				$html = str_replace( 'مسیر مناسب رشد خود را انتخاب کنید', esc_html( $title ), $html );
				$html = str_replace( 'مشاوره، آموزش یا محتوای تخصصی؛ بر اساس نیاز امروزتان شروع کنید.', esc_html( $settings['subtitle'] ?? '' ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				$html = str_replace( 'نقطه شروع پیشنهادی', esc_html( $settings['start_label'] ?? 'نقطه شروع پیشنهادی' ), $html );
				$html = str_replace( 'نمی‌دانید کدام مسیر برای شما مناسب‌تر است؟', esc_html( $settings['start_title'] ?? 'نمی‌دانید کدام مسیر برای شما مناسب‌تر است؟' ), $html );
				$html = preg_replace( '~<a href="#calendar">\s*<span>دریافت ارزیابی اولیه</span>~isu', '<a' . self::link_attrs( $settings['start_link'] ?? '#calendar' ) . '><span>' . esc_html( $settings['start_link_text'] ?? 'دریافت ارزیابی اولیه' ) . '</span>', $html, 1 );
				if ( ! empty( $settings['items'] ) ) {
					$cards = '';
					foreach ( $settings['items'] as $index => $item ) {
						$cards .= '<a' . self::link_attrs( $item['link'] ?? '#', 'service-card is-active' ) . '><span class="service-card-head"><span class="service-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M8 12h8M12 8v8"/></svg></span><span class="service-index" aria-hidden="true">' . esc_html( sprintf( '%02d', $index + 1 ) ) . '</span></span><span class="service-fit">' . esc_html( $item['eyebrow'] ?? '' ) . '</span><h3>' . esc_html( $item['title'] ?? '' ) . '</h3><p>' . esc_html( $item['description'] ?? '' ) . '</p><span class="service-link">' . esc_html( $item['link_text'] ?? '' ) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></span></a>';
					}
					$html = self::replace_element_inner( $html, '<div class="services-grid"', $cards );
				}
				$html = str_replace( 'در مسیر توسعه', esc_html( $settings['future_label'] ?? 'در مسیر توسعه' ), $html );
				$html = str_replace( 'خدمات تکمیلی سازان به‌زودی در دسترس قرار می‌گیرند.', esc_html( $settings['future_description'] ?? 'خدمات تکمیلی سازان به‌زودی در دسترس قرار می‌گیرند.' ), $html );
				$html = str_replace( '<b>رشد فردی</b>', '<b>' . esc_html( $settings['future_1'] ?? 'رشد فردی' ) . '</b>', $html );
				$html = str_replace( '<b>منابع انسانی</b>', '<b>' . esc_html( $settings['future_2'] ?? 'منابع انسانی' ) . '</b>', $html );
				$html = str_replace( '<b>پشتیبانی و همراهی</b>', '<b>' . esc_html( $settings['future_3'] ?? 'پشتیبانی و همراهی' ) . '</b>', $html );
				$html = str_replace( '<small>به‌زودی</small>', '<small>' . esc_html( $settings['future_status'] ?? 'به‌زودی' ) . '</small>', $html );
				break;

			case 'courses':
				$html = str_replace( '>COURSES</span>', '>' . esc_html( $settings['kicker'] ?? 'COURSES' ) . '</span>', $html );
				$title = $settings['title'] ?? 'دوره‌های در حال ثبت‌نام';
				$html = str_replace( 'دوره‌های در حال ثبت‌نام', esc_html( $title ), $html );
				$html = str_replace( 'مهارت‌های کاربردی برای فروش، مذاکره و مدیریت مالی.', esc_html( $settings['subtitle'] ?? '' ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				if ( isset( $settings['courses'] ) && is_array( $settings['courses'] ) ) {
					$cards = '';
					foreach ( $settings['courses'] as $course ) {
						$image = ! empty( $course['image']['url'] ) ? $course['image']['url'] : '';
						$cards .= '<a' . self::link_attrs( $course['link'] ?? '#', 'course-card' ) . ' data-category="' . esc_attr( $course['category_slug'] ?? 'all' ) . '"><span class="course-media"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $course['image_alt'] ?? $course['title'] ?? '' ) . '" loading="lazy" decoding="async"><span class="course-status"><i></i>' . esc_html( $course['status'] ?? 'ثبت‌نام باز' ) . '</span></span><span class="course-body"><span class="course-tags"><span>' . esc_html( $course['category'] ?? '' ) . '</span><span>' . esc_html( $course['mode'] ?? '' ) . '</span></span><h3>' . esc_html( $course['title'] ?? '' ) . '</h3><span class="course-meta"><span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg><b>' . esc_html( $course['date'] ?? '' ) . '</b></span><span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><b>' . esc_html( $course['instructor'] ?? '' ) . '</b></span></span><span class="course-cta">' . esc_html( $course['button_text'] ?? 'جزئیات و ثبت‌نام' ) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></span></span></a>';
					}
					$html = self::replace_element_inner( $html, '<div class="course-grid"', $cards );
					$html = preg_replace( '~<span class="course-count".*?</span>~isu', '<span class="course-count" aria-live="polite">' . esc_html( count( $settings['courses'] ) ) . ' ' . esc_html( $settings['count_suffix'] ?? 'دوره فعال' ) . '</span>', $html, 1 );
					$filters = '<button class="active" type="button" data-filter="all">' . esc_html( $settings['all_filter_text'] ?? 'همه' ) . '</button>';
					$seen = array();
					foreach ( $settings['courses'] as $course ) {
						$slug = sanitize_html_class( $course['category_slug'] ?? '' );
						if ( $slug && ! isset( $seen[ $slug ] ) ) { $seen[ $slug ] = true; $filters .= '<button type="button" data-filter="' . esc_attr( $slug ) . '">' . esc_html( $course['category'] ?? $slug ) . '</button>'; }
					}
					$html = self::replace_element_inner( $html, '<div class="course-filters"', $filters );
				}
				break;

			case 'consultation':
				$html = str_replace( 'همراه شما برای یک تصمیم مطمئن', esc_html( $settings['kicker'] ?? 'همراه شما برای یک تصمیم مطمئن' ), $html );
				$title = $settings['title'] ?? 'مسیر درست کسب‌وکارتان را با اطمینان انتخاب کنید';
				$html = str_replace( 'مسیر درست کسب‌وکارتان را <em>با اطمینان انتخاب کنید</em>', esc_html( $title ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				$html = str_replace( 'مسئله کسب‌وکار شما را بررسی می‌کنیم و متناسب با شرایط واقعی، نقطه شروع و مسیر اقدام را پیشنهاد می‌دهیم.', esc_html( $settings['description'] ?? '' ), $html );
				$html = preg_replace( '~<a href="#consulting">دریافت مشاوره اولیه(.*?)</a>~isu', '<a' . self::link_attrs( $settings['button_link'] ?? '#consulting' ) . '>' . esc_html( $settings['button_text'] ?? 'دریافت مشاوره اولیه' ) . '$1</a>', $html, 1 );
				$html = str_replace( 'ارزیابی اولیه', esc_html( $settings['proof_1'] ?? 'ارزیابی اولیه' ), $html );
				$html = str_replace( 'پاسخ‌گویی سریع', esc_html( $settings['proof_2'] ?? 'پاسخ‌گویی سریع' ), $html );
				$html = str_replace( 'برای شروع آماده‌اید؟', esc_html( $settings['action_label'] ?? 'برای شروع آماده‌اید؟' ), $html );
				$html = str_replace( 'بدون تعهد و متناسب با نیاز شما', esc_html( $settings['note'] ?? 'بدون تعهد و متناسب با نیاز شما' ), $html );
				if ( ! empty( $settings['image']['url'] ) ) { $html = preg_replace( '~(<div class="consultation-portrait".*?<img src=")[^"]*~isu', '$1' . esc_url( $settings['image']['url'] ), $html, 1 ); }
				break;

			case 'about':
				$html = str_replace( 'SAZAN ACADEMY', esc_html( $settings['kicker'] ?? 'SAZAN ACADEMY' ), $html );
				$title = $settings['title'] ?? 'سازان؛ همراه رشد واقعی کسب‌وکار';
				$html = str_replace( 'سازان؛ همراه رشد واقعی کسب‌وکار', esc_html( $title ), $html );
				$html = str_replace( 'ما آموزش، مشاوره و توسعه منابع انسانی را به راهکارهایی قابل اجرا تبدیل می‌کنیم؛ راهکارهایی که از مسئله واقعی شما شروع می‌شوند و به نتیجه‌ای قابل اندازه‌گیری می‌رسند.', esc_html( $settings['description'] ?? '' ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				$html = preg_replace( '~<a class="about-link".*?</a>~isu', '<a' . self::link_attrs( $settings['button_link'] ?? '#consulting', 'about-link' ) . '>' . esc_html( $settings['button_text'] ?? 'آشنایی با مسیرهای همکاری' ) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></a>', $html, 1 );
				if ( ! empty( $settings['values'] ) ) {
					$values = ''; foreach ( $settings['values'] as $value ) { $values .= '<div><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><span><b>' . esc_html( $value['title'] ?? '' ) . '</b><small>' . esc_html( $value['description'] ?? '' ) . '</small></span></div>'; }
					$html = self::replace_element_inner( $html, '<div class="about-values"', $values );
				}
				if ( ! empty( $settings['stats'] ) ) {
					$stats = ''; foreach ( $settings['stats'] as $index => $stat ) { $stats .= '<article' . ( 0 === $index ? ' class="about-stat-featured"' : '' ) . '><span class="about-stat-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg></span><div><b>' . esc_html( $stat['number'] ?? '' ) . '</b><strong>' . esc_html( $stat['title'] ?? '' ) . '</strong><small>' . esc_html( $stat['description'] ?? '' ) . '</small></div></article>'; }
					$html = self::replace_element_inner( $html, '<div class="about-stats"', $stats );
				}
				break;

			case 'calendar':
				$html = str_replace( '>CALENDAR</span>', '>' . esc_html( $settings['kicker'] ?? 'CALENDAR' ) . '</span>', $html );
				$title = $settings['title'] ?? 'تقویم آموزشی سازان';
				$html = str_replace( 'تقویم آموزشی سازان', esc_html( $title ), $html );
				$html = str_replace( 'برنامه دوره‌های پیش‌ رو را ببینید و پیش از تکمیل ظرفیت ثبت‌نام کنید.', esc_html( $settings['subtitle'] ?? '' ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				if ( isset( $settings['events'] ) && is_array( $settings['events'] ) ) {
					$items = '';
					foreach ( $settings['events'] as $event ) {
						$items .= '<article class="calendar-item"><time class="course-date" datetime="' . esc_attr( $event['datetime'] ?? '' ) . '"><span class="date-day">' . esc_html( $event['day'] ?? '' ) . '</span><span><b>' . esc_html( $event['month'] ?? '' ) . '</b><small>' . esc_html( $event['year'] ?? '' ) . '</small></span><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg></time><div class="calendar-content"><div class="calendar-labels"><span class="enrolling"><i></i>' . esc_html( $event['status'] ?? '' ) . '</span><span class="course-mode">' . esc_html( $event['mode'] ?? '' ) . '</span></div><h3>' . esc_html( $event['title'] ?? '' ) . '</h3><div class="calendar-meta"><span>' . esc_html( $event['instructor'] ?? '' ) . '</span><span>' . esc_html( $event['duration'] ?? '' ) . '</span></div></div><a' . self::link_attrs( $event['link'] ?? '#' ) . '>' . esc_html( $event['button_text'] ?? 'مشاهده و ثبت‌نام' ) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></a></article>';
					}
					$html = self::replace_element_inner( $html, '<div class="calendar-list"', $items );
					$html = preg_replace( '~<div class="calendar-summary".*?</div>~isu', '<div class="calendar-summary" aria-label="' . esc_attr( $settings['summary_label'] ?? 'برنامه فعال' ) . '"><strong>' . esc_html( count( $settings['events'] ) ) . '</strong><span>' . esc_html( $settings['summary_label'] ?? 'برنامه فعال' ) . '<small>' . esc_html( $settings['summary_note'] ?? 'ثبت‌نام در حال انجام' ) . '</small></span></div>', $html, 1 );
				}
				break;

			case 'articles':
				$html = str_replace( 'مجله سازان', esc_html( $settings['kicker'] ?? 'مجله سازان' ), $html );
				$title = $settings['title'] ?? 'بینش‌های کاربردی برای رشد کسب‌وکار';
				$html = str_replace( 'بینش‌های کاربردی برای رشد کسب‌وکار', esc_html( $title ), $html );
				$html = str_replace( 'مطالب کوتاه و قابل‌اجرا برای تصمیم‌های بهتر مدیریتی', esc_html( $settings['subtitle'] ?? '' ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				$html = preg_replace( '~<a class="articles-all".*?</a>~isu', '<a' . self::link_attrs( $settings['all_link'] ?? '#articles', 'articles-all' ) . '>' . esc_html( $settings['all_text'] ?? 'مشاهده همه مقالات' ) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></a>', $html, 1 );
				$articles = isset( $settings['articles'] ) && is_array( $settings['articles'] ) ? $settings['articles'] : array();
				if ( 'manual' !== ( $settings['source'] ?? 'latest' ) ) {
					$latest = self::latest_articles( $settings );
					if ( null !== $latest ) {
						$articles = $latest;
					}
				}
				if ( is_array( $articles ) ) {
					$cards = '';
					foreach ( $articles as $article ) {
						$image = ! empty( $article['image']['url'] ) ? $article['image']['url'] : '';
						$image_attrs = '';
						if ( ! empty( $article['image_width'] ) && ! empty( $article['image_height'] ) ) { $image_attrs .= ' width="' . esc_attr( $article['image_width'] ) . '" height="' . esc_attr( $article['image_height'] ) . '"'; }
						if ( ! empty( $article['image_srcset'] ) ) { $image_attrs .= ' srcset="' . esc_attr( $article['image_srcset'] ) . '"'; }
						if ( ! empty( $article['image_sizes'] ) ) { $image_attrs .= ' sizes="' . esc_attr( $article['image_sizes'] ) . '"'; }
						$cards .= '<article class="article-card"><div class="article-media"><img src="' . esc_url( $image ) . '" alt="' . esc_attr( $article['image_alt'] ?? $article['title'] ?? '' ) . '"' . $image_attrs . ' loading="lazy" decoding="async"><span>' . esc_html( $article['category'] ?? '' ) . '</span><b>' . esc_html( $article['views'] ?? '' ) . '</b></div><div class="article-body"><h3>' . esc_html( $article['title'] ?? '' ) . '</h3><p>' . esc_html( $article['excerpt'] ?? '' ) . '</p><footer><time>' . esc_html( $article['date'] ?? '' ) . '</time><a' . self::link_attrs( $article['link'] ?? '#' ) . '>' . esc_html( $article['button_text'] ?? 'مطالعه' ) . '</a></footer></div></article>';
					}
					if ( ! $cards ) {
						$cards = '<p class="articles-empty" role="status">' . esc_html( $settings['empty_message'] ?? 'هنوز مقاله‌ای منتشر نشده است.' ) . '</p>';
					}
					$html = self::replace_element_inner( $html, '<div class="articles-grid"', $cards );
				}
				break;

			case 'footer':
				$html = str_replace( 'NEXT MOVE', esc_html( $settings['kicker'] ?? 'NEXT MOVE' ), $html );
				$title = $settings['title'] ?? 'برای حرکت بعدی کسب‌وکارت آماده‌ای؟';
				$html = str_replace( 'برای حرکت بعدی کسب‌وکارت آماده‌ای؟', esc_html( $title ), $html );
				$html = self::heading_tag( $html, $settings['heading_tag'] ?? 'h2', esc_html( $title ) );
				$html = str_replace( 'یک گفت‌وگوی کوتاه می‌تواند مسیر درست را شفاف کند.', esc_html( $settings['description'] ?? '' ), $html );
				$html = preg_replace( '~<div class="footer-cta.*?<a .*?</a>~isu', '$0', $html, 1 );
				$html = preg_replace( '~<div class="footer-cta(.*?)<a href="#consulting">درخواست مشاوره(.*?)</a>~isu', '<div class="footer-cta$1<a' . self::link_attrs( $settings['button_link'] ?? '#consulting' ) . '>' . esc_html( $settings['button_text'] ?? 'درخواست مشاوره' ) . '$2</a>', $html, 1 );
				$phone = $settings['phone'] ?? '';
				$email = $settings['email'] ?? '';
				$html = preg_replace( '~<a href="tel:[^"]*">.*?</a>~isu', '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>', $html, 1 );
				$html = preg_replace( '~<a href="mailto:[^"]*">.*?</a>~isu', '<a href="mailto:' . esc_attr( sanitize_email( $email ) ) . '">' . esc_html( $email ) . '</a>', $html, 1 );
				$html = str_replace( 'اهواز، کیان آباد، نبش، خیابان سپهری، روبروی پمپ بنزین، ساختمان آسمان، طبقه ۶ واحد ۱۲', esc_html( $settings['address'] ?? '' ), $html );
				$html = str_replace( '<h2>اطلاعات تماس</h2>', '<h2>' . esc_html( $settings['contact_heading'] ?? 'اطلاعات تماس' ) . '</h2>', $html );
				$html = str_replace( 'شنبه تا پنجشنبه ۹ تا ۲۱', esc_html( $settings['hours'] ?? 'شنبه تا پنجشنبه ۹ تا ۲۱' ), $html );
				$html = str_replace( '© ۱۴۰۵ تمامی حقوق این وب‌سایت متعلق به سازان است.', esc_html( $settings['copyright'] ?? '' ), $html );
				$html = str_replace( '<section class="footer-about"><h2>درباره سازان</h2><p>سازان، مرکز آموزش و مشاوره تخصصی کسب‌وکار است. با ما مسیر رشد حرفه‌ای و هوشمندانه کسب‌وکار خود را بسازید.</p></section>', '<section class="footer-about"><h2>' . esc_html( $settings['about_heading'] ?? 'درباره سازان' ) . '</h2><p>' . esc_html( $settings['about_text'] ?? '' ) . '</p></section>', $html );
				$category_nav = self::footer_nav_markup( $settings['category_menu_id'] ?? 0, $settings['category_heading'] ?? 'دسته‌بندی‌ها', 'دسته‌بندی‌های فوتر', array( 'مقالات' => '#articles', 'فروش' => '#articles', 'کسب و کار' => '#articles', 'برندسازی' => '#articles' ) );
				$quick_nav = self::footer_nav_markup( $settings['quick_menu_id'] ?? 0, $settings['quick_heading'] ?? 'دسترسی سریع', 'دسترسی سریع', array( 'صفحه اصلی' => '#top', 'مقالات' => '#articles', 'مشاوره کسب‌وکار' => '#consulting' ) );
				$html = preg_replace( '~<nav aria-label="دسته‌بندی‌های فوتر">.*?</nav>~isu', $category_nav, $html, 1 );
				$html = preg_replace( '~<nav aria-label="دسترسی سریع">.*?</nav>~isu', $quick_nav, $html, 1 );
				break;
		}

		return self::wrap( $name, $html, $settings );
	}

	public static function html( $settings = array() ) {
		$settings = wp_parse_args( (array) $settings, self::defaults() );
		$html = self::body();
		if ( ! $html ) {
			return '<div class="sazan-premium-homepage-error">قالب صفحه اصلی در دسترس نیست.</div>';
		}
		$html = str_replace( 'assets/images/', SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/', $html );

		$logo = ! empty( $settings['logo']['url'] ) ? $settings['logo']['url'] : self::defaults()['logo']['url'];
		$hero = ! empty( $settings['hero_image']['url'] ) ? $settings['hero_image']['url'] : self::defaults()['hero_image']['url'];
		$html = preg_replace( '~(<a class="brand" href=")[^"]*("[^>]*>\s*<img src=")[^"]*~isu', '$1' . esc_url( $settings['logo_link'] ) . '$2' . esc_url( $logo ), $html, 1 );
		$html = preg_replace( '~(<img class="hero-character" src=")[^"]*~isu', '$1' . esc_url( $hero ), $html, 1 );
		$html = preg_replace( '~<nav class="desktop-nav".*?</nav>~isu', self::nav_markup( $settings['menu_id'], 'desktop-nav', 'منوی اصلی' ), $html, 1 );
		$html = preg_replace( '~<nav class="mobile-nav".*?</nav>~isu', self::nav_markup( $settings['menu_id'], 'mobile-nav', 'منوی موبایل' ), $html, 1 );
		$html = preg_replace( '~(<a class="account-link" href=")[^"]*~isu', '$1' . esc_url( $settings['account_url'] ), $html, 1 );
		$html = preg_replace( '~(<a class="account-link"[^>]*>.*?<span>).*?(</span>)~isu', '$1' . esc_html( $settings['account_text'] ) . '$2', $html, 1 );
		$html = preg_replace( '~(<a class="primary-button" href=")[^"]*("[^>]*>).*?(<svg)~isu', '$1' . esc_url( $settings['primary_url'] ) . '$2' . esc_html( $settings['primary_text'] ) . ' $3', $html, 1 );

		$replacements = array(
			'دوره حکمرانی بر بازار'                         => esc_html( $settings['hero_title'] ),
			'یاد بگیرید چطور با تصمیم‌های دقیق، مذاکره مؤثر و شناخت بازار، رشد کسب‌وکار را آگاهانه هدایت کنید.' => esc_html( $settings['hero_summary'] ),
			'مسیر مناسب رشد خود را انتخاب کنید'             => esc_html( $settings['services_title'] ),
			'دوره‌های در حال ثبت‌نام'                       => esc_html( $settings['courses_title'] ),
			'مسیر درست کسب‌وکارتان را با اطمینان انتخاب کنید' => esc_html( $settings['consultation_title'] ),
			'سازان؛ همراه رشد واقعی کسب‌وکار'               => esc_html( $settings['about_title'] ),
			'تقویم آموزشی سازان'                            => esc_html( $settings['calendar_title'] ),
			'بینش‌های کاربردی برای رشد کسب‌وکار'             => esc_html( $settings['articles_title'] ),
			'برای حرکت بعدی کسب‌وکارت آماده‌ای؟'             => esc_html( $settings['footer_title'] ),
		);
		$html = strtr( $html, $replacements );

		return '<div class="sazan-premium-homepage" dir="rtl" data-assets-url="' . esc_url( SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/' ) . '">' . $html . '</div>';
	}
}
