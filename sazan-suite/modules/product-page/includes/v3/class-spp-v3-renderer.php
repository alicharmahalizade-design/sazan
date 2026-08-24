<?php
/** Premium V3 full-page renderer matching the approved product-page preview. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V3_Renderer {

	public static function render( $post_id = 0 ) {
		$post_id = absint( $post_id ) ?: SPP_V2_Template::current_product_id();
		$product = $post_id && function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
		if ( ! $product ) {
			return '';
		}
		$data = SPP_V2_Data::get( $post_id );
		$review_items = array_values( array_filter( SPP_V2_Renderer::reviews_data( $post_id, $data ), static function ( $item ) {
			return empty( $item['is_sample'] ) && ! empty( $item['name'] ) && ! empty( $item['text'] );
		} ) );
		$has_review_target = true;
		ob_start();
		echo '<div id="spp-v3-app" class="spp-v3-app" dir="rtl">';
		self::decorations();
		self::header( $product, $data, $has_review_target );
		self::hero( $post_id, $product, $data );
		self::course_nav( $product, $data, $has_review_target );
		self::experience( $product, $data );
		self::transformation( $product, $data );
		self::curriculum( $product, $data );
		self::certificate( $product, $data );
		self::instructor( $data );
		self::reviews( $post_id, $product, $data, $review_items );
		self::enrollment( $product, $data );
		self::faq( $product, $data );
		self::final_cta( $product, $data );
		self::footer( $product, $data );
		echo '</div>';
		return (string) ob_get_clean();
	}

	private static function decorations() {
		echo '<div class="noise" aria-hidden="true"></div><div class="grid-bg" aria-hidden="true"></div>';
	}

	private static function title_parts( $title ) {
		$title = trim( wp_strip_all_tags( $title ) );
		$needle = ' بر ';
		$position = mb_strpos( $title, $needle );
		if ( false !== $position ) {
			return array( mb_substr( $title, 0, $position ), mb_substr( $title, $position + 1 ) );
		}
		$words = preg_split( '/\s+/u', $title );
		if ( count( $words ) < 2 ) {
			return array( $title, '' );
		}
		$cut = max( 1, (int) ceil( count( $words ) / 2 ) );
		return array( implode( ' ', array_slice( $words, 0, $cut ) ), implode( ' ', array_slice( $words, $cut ) ) );
	}

	private static function plain( $value, $fallback = '' ) {
		$value = trim( wp_strip_all_tags( (string) $value ) );
		return '' !== $value ? $value : $fallback;
	}

	private static function svg( $name ) {
		$icons = array(
			'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'list' => '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 13h5M8 17h3"/>',
			'refresh' => '<path d="M8 8a6 6 0 0 1 10 2l2-2v6h-6l2-2a4 4 0 1 0 0 5"/><path d="M16 16a6 6 0 0 1-10-2l-2 2v-6h6l-2 2a4 4 0 1 0 0-5"/>',
			'star' => '<path d="M12 3l2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
			'headset' => '<path d="M4 13v-2a8 8 0 0 1 16 0v2"/><path d="M4 13h3v6H5a2 2 0 0 1-2-2v-2a2 2 0 0 1 1-2zm16 0h-3v6h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-1-2z"/>',
			'bookmark' => '<path d="M6 4h12v16l-6-4-6 4z"/>',
			'shield' => '<path d="M12 3 20 6v5c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-5"/>',
			'arrow' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
		);
		$body = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['star'];
		return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $body . '</svg>';
	}

	private static function header( $product, $data, $has_review_target ) {
		$logo_id    = absint( spp_global( 'header_logo', 0 ) );
		$logo_url   = spp_global( 'header_logo_url', home_url( '/' ) );
		$menu_id    = absint( spp_global( 'header_menu', 0 ) );
		$button_text = self::plain( spp_global( 'header_button_text', 'ورود / ثبت‌نام' ), 'ورود / ثبت‌نام' );
		$button_url  = spp_global( 'header_button_url', '#enroll' );
		?>
		<header class="site-header"><div class="container header-shell">
			<a href="<?php echo esc_url( $logo_url ); ?>" class="logo" aria-label="سازان؛ صفحه اصلی"><?php if ( $logo_id ) { echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'header-logo-image', 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager', 'decoding' => 'async' ) ); } else { ?><span class="logo-mark">S</span><span>SAZAN</span><?php } ?></a>
			<nav class="header-nav" aria-label="ناوبری اصلی"><?php if ( $menu_id && wp_get_nav_menu_object( $menu_id ) ) { echo wp_nav_menu( array( 'menu' => $menu_id, 'container' => false, 'menu_class' => 'header-menu-list', 'items_wrap' => '<ul class="header-menu-list">%3$s</ul>', 'fallback_cb' => false, 'depth' => 1, 'echo' => false ) ); } else { ?><a href="#experience">تجربه دوره</a><a href="#curriculum">سرفصل‌ها</a><a href="#instructor">مدرس</a><?php if ( $has_review_target ) : ?><a href="#reviews">تجربه دانشجویان</a><?php endif; ?><?php } ?></nav>
			<a class="account" href="<?php echo esc_url( $button_url ); ?>"><i></i> <?php echo esc_html( $button_text ); ?></a>
		</div></header>
		<?php
	}

	private static function breadcrumb( $post_id, $product ) {
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		?><nav class="seo-breadcrumb" aria-label="مسیر راهنما"><ol><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a></li><li aria-hidden="true">‹</li><li><a href="<?php echo esc_url( $shop_url ); ?>">دوره‌ها</a></li><li aria-hidden="true">‹</li><li aria-current="page"><?php echo esc_html( $product->get_name() ); ?></li></ol></nav><?php
	}

	private static function hero_image( $product, $data ) {
		$image_id = ! empty( $data['intro']['hero_image'] ) ? absint( $data['intro']['hero_image'] ) : absint( $product->get_image_id() );
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'full', false, array( 'class' => 'hero-media-image', 'alt' => $product->get_name(), 'loading' => 'eager', 'decoding' => 'async', 'fetchpriority' => 'high' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		?><div class="hero-media-placeholder" aria-label="تصویر معرفی دوره"><span>SAZAN</span><strong><?php echo esc_html( $product->get_name() ); ?></strong></div><?php
	}

	private static function hero( $post_id, $product, $data ) {
		$intro = $data['intro'];
		list( $title, $outline ) = self::title_parts( $product->get_name() );
		$lead = self::plain( $intro['short_description'], self::plain( get_post_field( 'post_excerpt', $post_id ), 'پیش از ثبت‌نام، ببینید در ' . $product->get_name() . ' چه می‌آموزید، چگونه تمرین می‌کنید و به چه نتیجه‌ای می‌رسید.' ) );
		$rating = '' !== $intro['manual_rating'] ? (float) $intro['manual_rating'] : (float) $product->get_average_rating();
		$reviews = (int) $product->get_review_count();
		$students = '1' === $intro['show_students'] && ! empty( $intro['student_count'] ) ? (int) $intro['student_count'] : 0;
		$seats = ! empty( $intro['remaining_seats'] ) ? (int) $intro['remaining_seats'] : 0;
		$facts = array_values( array_filter( $data['facts']['items'], function ( $item ) { return ! empty( $item['title'] ) || ! empty( $item['value'] ); } ) );
		$facts = array_slice( $facts, 0, 3 );
		$micro = array();
		if ( '1' === $data['guarantee']['enabled'] ) {
			$micro[] = self::plain( $data['guarantee']['duration'], self::plain( $data['guarantee']['title'], 'دارای شرایط ضمانت' ) );
		}
		if ( '1' === $data['certificate']['enabled'] ) {
			$micro[] = self::plain( $data['certificate']['badge'], 'گواهینامه پایان دوره' );
		}
		if ( ! empty( $data['seo']['course_level'] ) ) {
			$micro[] = 'سطح ' . self::plain( $data['seo']['course_level'] );
		}
		?>
		<section class="hero" id="top" aria-labelledby="course-title"><div class="container hero-layout">
			<div class="hero-copy reveal">
				<?php self::breadcrumb( $post_id, $product ); ?>
				<div class="hero-identity"><span class="hero-identity__mark" aria-hidden="true"></span><span class="hero-identity__copy"><?php echo esc_html( self::plain( $intro['eyebrow'], 'آکادمی سازان — ' . $product->get_name() ) ); ?></span><?php if ( $seats > 0 ) : ?><span class="hero-identity__seats"><?php echo esc_html( number_format_i18n( $seats ) ); ?> صندلی باقی مانده</span><?php endif; ?></div>
				<h1 id="course-title"><span><?php echo esc_html( $title ); ?></span><?php if ( $outline ) : ?><span class="outline"><?php echo esc_html( $outline ); ?></span><?php endif; ?></h1>
				<p class="hero-lead"><?php echo esc_html( $lead ); ?></p>
				<?php if ( $rating > 0 || $reviews > 0 || $students > 0 ) : ?><div class="hero-proof"><?php if ( $rating > 0 ) : ?><span class="stars" aria-label="امتیاز <?php echo esc_attr( $rating ); ?> از ۵">★★★★★</span><strong><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?> از ۵</strong><?php endif; ?><?php if ( $reviews > 0 ) : ?><span><?php echo esc_html( number_format_i18n( $reviews ) ); ?> نظر ثبت‌شده</span><?php endif; ?><?php if ( $students > 0 ) : ?><span><?php echo esc_html( number_format_i18n( $students ) ); ?> شرکت‌کننده</span><?php endif; ?></div><?php endif; ?>
				<div class="hero-actions"><a class="btn btn-primary" href="#enroll"><?php echo esc_html( self::plain( $intro['primary_cta'], 'ثبت‌نام در دوره' ) ); ?> <span>←</span></a><a class="btn btn-ghost" href="#curriculum">مشاهده سرفصل‌ها</a></div>
				<?php if ( $micro ) : ?><div class="hero-micro"><?php foreach ( $micro as $item ) : ?><span><?php echo esc_html( $item ); ?></span><?php endforeach; ?></div><?php endif; ?>
			</div>
			<div class="hero-media-card reveal" aria-label="رسانه معرفی دوره"><div class="hero-media-frame">
				<?php self::hero_image( $product, $data ); ?>
				<?php if ( ! empty( $intro['preview_video'] ) ) : ?><button class="media-play" type="button" data-spp-v3-video="<?php echo esc_url( $intro['preview_video'] ); ?>" aria-label="پخش ویدیوی معرفی"><span>▶</span></button><?php endif; ?>
				<div class="media-badge"><i></i><span>معرفی دوره</span><b><?php echo ! empty( $intro['preview_video'] ) ? 'ویدیوی معرفی' : 'تصویر دوره'; ?></b></div><div class="media-caption"><span><?php echo esc_html( $product->get_name() ); ?></span><strong><?php echo esc_html( self::plain( $intro['subtitle'], 'از اولین درس تا نتیجه نهایی' ) ); ?></strong></div>
			</div><?php if ( $facts ) : ?><div class="media-meta" aria-label="اطلاعات دوره">
				<?php foreach ( $facts as $index => $fact ) : $icons = array( 'clock', 'list', 'refresh' ); ?><span class="media-meta__item"><i class="media-meta__icon" aria-hidden="true"><?php echo self::svg( $icons[ $index ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></i><span class="media-meta__copy"><b><?php echo esc_html( $fact['value'] ); ?></b><small><?php echo esc_html( $fact['title'] ); ?></small></span></span><?php endforeach; ?>
			</div><?php endif; ?></div>
		</div><div class="hero-scroll"><i></i> برای دیدن مسیر دوره ادامه دهید</div></section>
		<?php
	}

	private static function course_nav( $product, $data, $has_review_target ) {
		$has_instructor = ! empty( $data['instructors']['items'][0]['name'] );
		?>
		<nav class="course-nav" aria-label="ناوبری دوره"><div class="container course-nav-inner">
			<a class="active" href="#experience"><span class="mobile-nav-icon"><?php echo self::svg( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="nav-label--desktop">چرا این دوره؟</span><span class="nav-label--mobile">معرفی</span></a>
			<a href="#transformation"><span class="mobile-nav-icon"><?php echo self::svg( 'refresh' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="nav-label--desktop">تحول</span><span class="nav-label--mobile">تحول</span></a>
			<a href="#curriculum"><span class="mobile-nav-icon"><?php echo self::svg( 'list' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="nav-label--desktop">مسیر یادگیری</span><span class="nav-label--mobile">مسیر</span></a>
			<?php if ( $has_instructor ) : ?><a href="#instructor"><span class="mobile-nav-icon"><?php echo self::svg( 'headset' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="nav-label--desktop">مدرس</span><span class="nav-label--mobile">مدرس</span></a><?php endif; ?>
			<?php if ( $has_review_target ) : ?><a href="#reviews"><span class="mobile-nav-icon"><?php echo self::svg( 'bookmark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span class="nav-label--desktop">نظرات</span><span class="nav-label--mobile">نظرات</span></a><?php endif; ?>
			<a class="nav-cta" href="#enroll">ثبت‌نام · <?php echo wp_kses_post( $product->get_price_html() ); ?></a>
		</div><span class="mobile-scroll-progress" aria-hidden="true"><i></i></span></nav>
		<?php
	}

	private static function default_benefits( $product, $data ) {
		$outcomes = preg_split( '/\r\n|\r|\n/u', (string) $data['seo']['teaches'] );
		$outcomes = array_values( array_filter( array_map( 'trim', $outcomes ) ) );
		if ( $outcomes ) {
			return array_map( function ( $outcome ) { return array( 'icon' => 'star', 'title' => $outcome, 'description' => 'این توانایی را قدم‌به‌قدم تمرین می‌کنید تا در موقعیت واقعی از آن استفاده کنید.' ); }, array_slice( $outcomes, 0, 3 ) );
		}
		return array(
			array( 'icon' => 'star', 'title' => 'یادگیری قدم‌به‌قدم', 'description' => 'از پایه تا اجرای واقعی، هر قدم در جای درست خود قرار گرفته است.' ),
			array( 'icon' => 'list', 'title' => 'تمرین برای دنیای واقعی', 'description' => 'آموخته‌ها را روی موقعیت‌های کاری تمرین می‌کنید تا فقط در حد دانستن نماند.' ),
			array( 'icon' => 'bookmark', 'title' => 'نتیجه قابل استفاده', 'description' => 'در پایان، ابزار و مهارتی دارید که می‌توانید همان روز در کارتان به کار بگیرید.' ),
		);
	}

	private static function experience( $product, $data ) {
		$about = $data['about'];
		$starting_points = array_values( array_filter( array_map( function ( $item ) { return self::plain( $item['text'] ?? '' ); }, $about['before_items'] ?? array() ) ) );
		$ending_points = array_values( array_filter( array_map( function ( $item ) { return self::plain( $item['text'] ?? '' ); }, $about['after_items'] ?? array() ) ) );
		if ( count( $starting_points ) < 3 ) {
			$starting_points = array(
				'در شروع هنوز مسئله اصلی و نقطه تمرکز شما در ' . $product->get_name() . ' دقیق تعریف نشده است.',
				'میان اطلاعات و راه‌حل‌های پراکنده، معیار روشنی برای انتخاب اقدام بعدی ندارید.',
				'دانسته‌ها به تمرین منظم و نتیجه‌ای که بتوانید آن را بسنجید تبدیل نشده‌اند.',
			);
		}
		if ( count( $ending_points ) < 3 ) {
			$outcomes = preg_split( '/\r\n|\r|\n/u', (string) $data['seo']['teaches'] );
			$ending_points = array_values( array_filter( array_map( 'trim', $outcomes ) ) );
		}
		$starting_points = array_slice( $starting_points, 0, 3 );
		$ending_points = array_slice( $ending_points, 0, 3 );
		?>
		<section class="manifesto" id="experience"><div class="container why-shell">
			<header class="why-head reveal"><span class="why-label"><?php echo esc_html( self::plain( $about['eyebrow'], 'درباره دوره' ) ); ?></span><h2><?php echo esc_html( self::plain( $about['title'], 'برای انتخاب آگاهانه ' . $product->get_name() . '؛' ) ); ?><span><?php echo esc_html( self::plain( $about['highlight'], 'مسیر و خروجی‌ها را دقیق ببینید' ) ); ?></span></h2><p><?php echo esc_html( self::plain( $about['lead'], 'از چالش‌هایی که امروز دارید تا توانایی‌هایی که بعد از دوره به دست می‌آورید، مسیر تغییر را یک‌جا ببینید.' ) ); ?></p></header>
			<div class="why-compare reveal"><article class="why-card why-card--now"><span class="why-card__tag">پیش از شروع</span><h3><?php echo esc_html( self::plain( $about['before_title'] ?? '', 'سه مسئله‌ای که ممکن است امروز تجربه کنید' ) ); ?></h3><ul><?php foreach ( $starting_points as $point ) : ?><li><?php echo esc_html( $point ); ?></li><?php endforeach; ?></ul></article><span class="why-arrow" aria-hidden="true">←</span><article class="why-card why-card--after"><span class="why-card__tag">پس از پایان مسیر</span><h3><?php echo esc_html( self::plain( $about['after_title'] ?? '', 'سه تغییری که پس از پایان مسیر به دست می‌آورید' ) ); ?></h3><ul><?php foreach ( $ending_points as $point ) : ?><li><?php echo esc_html( $point ); ?></li><?php endforeach; ?></ul></article></div>
		</div></section>
		<?php
	}

	private static function transformation( $product, $data ) {
		$d = $data['benefits'];
		$items = array_values( array_filter( $d['items'], function ( $item ) { return ! empty( $item['title'] ); } ) );
		$items = $items ? array_slice( $items, 0, 3 ) : self::default_benefits( $product, $data );
		$icons = array( 'star', 'headset', 'bookmark' );
		?>
		<section class="transformation" id="transformation"><div class="container"><header class="transform-head reveal"><span class="kicker"><?php echo esc_html( self::plain( $d['eyebrow'], 'مزیت‌های دوره' ) ); ?></span><h2><?php echo esc_html( self::plain( $d['title'], 'چرا ' . $product->get_name() . ' را انتخاب کنید؟' ) ); ?></h2><p>این دوره کمک می‌کند آموخته‌ها را به تصمیم‌های بهتر، اقدام دقیق‌تر و نتیجه‌ای قابل‌مشاهده تبدیل کنید.</p></header><div class="transform-cards reveal">
			<?php foreach ( $items as $index => $item ) : ?><article class="transform-card"><span class="transform-icon" aria-hidden="true"><?php echo self::svg( $icons[ $index ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php echo esc_html( $item['title'] ); ?></h3><p><?php echo esc_html( $item['description'] ); ?></p><span class="transform-result">دستاورد شما</span></article><?php endforeach; ?>
		</div></div></section>
		<?php
	}

	private static function default_groups( $product, $data ) {
		$outcomes = preg_split( '/\r\n|\r|\n/u', (string) $data['seo']['teaches'] );
		$outcomes = array_values( array_filter( array_map( 'trim', $outcomes ) ) );
		$groups = array();
		foreach ( array_slice( $outcomes, 0, 6 ) as $outcome ) {
			$groups[] = array( 'title' => $outcome, 'description' => 'گامی برای رسیدن به ' . $outcome, 'course_count' => '۱ بخش', 'duration' => '', 'output' => $outcome, 'chapters' => array( array( 'title' => $outcome, 'description' => 'در این بخش، مسیر رسیدن به این مهارت را قدم‌به‌قدم یاد می‌گیرید و تمرین می‌کنید.', 'duration' => '', 'lessons' => array() ) ) );
		}
		return $groups;
	}

	private static function curriculum( $product, $data ) {
		$d = $data['curriculum'];
		$groups = array_values( array_filter( $d['groups'], function ( $group ) { return ! empty( $group['title'] ); } ) );
		$groups = $groups ? $groups : self::default_groups( $product, $data );
		if ( ! $groups ) { return; }
		$chapters = 0; $lessons = 0;
		foreach ( $groups as $group ) { $chapters += count( isset( $group['chapters'] ) ? $group['chapters'] : array() ); foreach ( isset( $group['chapters'] ) ? $group['chapters'] : array() as $chapter ) { $lessons += count( isset( $chapter['lessons'] ) ? $chapter['lessons'] : array() ); } }
		?>
		<section class="curriculum" id="curriculum"><div class="container"><div class="section-head reveal"><div class="label"><?php echo esc_html( self::plain( $d['eyebrow'], 'سرفصل‌های دوره' ) ); ?></div><div><h2><?php echo esc_html( self::plain( $d['title'], 'نقشه کامل یادگیری ' . $product->get_name() ) ); ?></h2><p><?php echo esc_html( self::plain( $d['description'], 'ببینید این دوره چگونه شما را از مفاهیم پایه به تمرین و اجرای واقعی می‌رساند.' ) ); ?></p><div class="curriculum-overview"><div class="curriculum-overview__item"><strong><?php echo esc_html( number_format_i18n( count( $groups ) ) ); ?></strong><span>مرحله یادگیری</span></div><?php if ( $chapters > 0 ) : ?><div class="curriculum-overview__item"><strong><?php echo esc_html( number_format_i18n( $chapters ) ); ?></strong><span>فصل آموزشی</span></div><?php endif; ?><?php if ( $lessons > 0 ) : ?><div class="curriculum-overview__item"><strong><?php echo esc_html( number_format_i18n( $lessons ) ); ?></strong><span>جلسه آموزشی</span></div><?php endif; ?></div></div></div>
		<div class="curriculum-shell reveal"><aside class="curriculum-side"><header><small>نقشه راه دوره</small><strong><?php echo esc_html( $product->get_name() ); ?></strong></header>
		<?php foreach ( $groups as $index => $group ) : $output = self::plain( isset( $group['output'] ) ? $group['output'] : '', self::plain( $group['description'], $group['title'] ) ); ?><button class="path-btn<?php echo 0 === $index ? ' active' : ''; ?>" data-panel="spp-v3-panel-<?php echo (int) $index; ?>"><span class="path-btn__index"><?php echo esc_html( number_format_i18n( $index + 1, 0 ) ); ?></span><span class="path-btn__copy"><b><?php echo esc_html( $group['title'] ); ?></b><small><?php echo esc_html( $group['description'] ); ?></small><em>خروجی: <?php echo esc_html( $output ); ?></em></span></button><?php endforeach; ?>
		</aside><div class="curriculum-main">
		<?php foreach ( $groups as $g_index => $group ) : $group_chapters = isset( $group['chapters'] ) ? $group['chapters'] : array(); $group_output = self::plain( isset( $group['output'] ) ? $group['output'] : '', self::plain( $group['description'], $group['title'] ) ); ?><div class="panel<?php echo 0 === $g_index ? ' active' : ''; ?>" id="spp-v3-panel-<?php echo (int) $g_index; ?>"><div class="mission-hero"><div class="mission-copy"><small class="kicker">مرحله <?php echo esc_html( number_format_i18n( $g_index + 1 ) ); ?></small><h3><?php echo esc_html( $group['title'] ); ?></h3><p><?php echo esc_html( $group['description'] ); ?></p></div><aside class="mission-outcome"><span>خروجی این مرحله</span><strong><?php echo esc_html( $group_output ); ?></strong><small>آنچه در پایان به دست می‌آورید</small></aside></div><div class="mission-toolbar"><span><?php echo esc_html( self::plain( $group['course_count'], count( $group_chapters ) . ' فصل' ) ); ?></span><?php if ( ! empty( $group['duration'] ) ) : ?><span><?php echo esc_html( $group['duration'] ); ?></span><?php endif; ?><span>بخشی از مسیر <?php echo esc_html( $product->get_name() ); ?></span></div><div class="chapter-list">
		<?php foreach ( $group_chapters as $c_index => $chapter ) : $chapter_lessons = isset( $chapter['lessons'] ) ? $chapter['lessons'] : array(); ?><article class="chapter<?php echo 0 === $c_index ? ' open' : ''; ?>"><button class="chapter-toggle" type="button"><span class="n"><?php echo esc_html( number_format_i18n( $c_index + 1 ) ); ?></span><span><strong><?php echo esc_html( $chapter['title'] ); ?></strong><small><?php echo esc_html( $chapter['description'] ); ?></small></span><span class="count"><?php echo esc_html( self::plain( $chapter['duration'], count( $chapter_lessons ) . ' جلسه' ) ); ?></span><i class="plus"></i></button><div class="chapter-body"><?php foreach ( $chapter_lessons as $lesson ) : ?><div class="lesson"><?php echo esc_html( $lesson['title'] ); ?><?php if ( ! empty( $lesson['duration'] ) ) : ?><time><?php echo esc_html( $lesson['duration'] ); ?></time><?php endif; ?><?php if ( ! empty( $lesson['preview'] ) && ! empty( $lesson['preview_url'] ) ) : ?><a href="<?php echo esc_url( $lesson['preview_url'] ); ?>">پیش‌نمایش</a><?php endif; ?></div><?php endforeach; ?></div></article><?php endforeach; ?>
		</div></div><?php endforeach; ?>
		</div></div></div></section>
		<?php
	}

	private static function certificate( $product, $data ) {
		$d = $data['certificate'];
		if ( '1' !== $d['enabled'] ) { return; }
		$title = self::plain( $d['title'], 'گواهینامه پایان ' . $product->get_name() );
		$description = self::plain( $d['description'], 'با کامل‌کردن مسیر آموزشی و شرایط ارزیابی، گواهینامه پایان دوره سازان را دریافت می‌کنید؛ نشانی از مهارتی که برای آن وقت گذاشته‌اید و تمرین کرده‌اید.' );
		?>
		<section class="certificate" id="certificate"><div class="container"><div class="certificate-shell reveal"><div class="certificate-stage" aria-label="پیش‌نمایش گواهینامه پایان دوره سازان"><?php if ( ! empty( $d['image'] ) ) { echo wp_get_attachment_image( absint( $d['image'] ), 'large', false, array( 'class' => 'certificate-image', 'alt' => $title, 'loading' => 'lazy', 'decoding' => 'async' ) ); } else { ?><article class="certificate-paper"><div class="certificate-brand"><i>S</i> SAZAN ACADEMY</div><span class="certificate-paper__label">CERTIFICATE OF COMPLETION</span><h3>گواهینامه پایان دوره <?php echo esc_html( $product->get_name() ); ?></h3><p>مسیر را کامل کنید و گواهینامه مهارت خود را بگیرید.</p><strong class="certificate-name">نام دانشجو</strong><div class="certificate-line"></div><span class="certificate-seal">SAZAN<br>VERIFIED</span></article><?php } ?></div><div class="certificate-copy"><span class="kicker"><?php echo esc_html( self::plain( $d['badge'], 'گواهینامه پایان دوره' ) ); ?></span><h2><?php echo esc_html( $title ); ?></h2><div class="certificate-richtext"><?php echo wp_kses_post( wpautop( $description ) ); ?></div><?php if ( ! empty( $d['conditions'] ) ) : ?><div class="certificate-points"><div class="certificate-point"><span class="certificate-point__icon"><?php echo self::svg( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b>برای دریافت گواهینامه</b><small><?php echo esc_html( $d['conditions'] ); ?></small></div></div></div><?php endif; ?></div></div></div></section>
		<?php
	}

	private static function instructor( $data ) {
		$section = $data['instructors'];
		$items = array_values( array_filter( $section['items'], function ( $item ) { return ! empty( $item['name'] ); } ) );
		if ( ! $items ) { return; }
		$item = $items[0];
		?>
		<section class="instructor" id="instructor"><div class="container"><article class="instructor-shell reveal"><div class="instructor-avatar" aria-label="تصویر <?php echo esc_attr( $item['name'] ); ?>"><?php if ( ! empty( $item['image'] ) ) { echo wp_get_attachment_image( absint( $item['image'] ), 'medium_large', false, array( 'class' => 'instructor-avatar__image', 'alt' => $item['name'], 'loading' => 'lazy', 'decoding' => 'async' ) ); } else { echo esc_html( mb_substr( $item['name'], 0, 1 ) ); } ?></div><div class="instructor-copy"><h2><?php echo esc_html( $item['name'] ); ?></h2><?php if ( ! empty( $item['role'] ) ) : ?><span class="instructor-role">مدرس دوره · <?php echo esc_html( $item['role'] ); ?></span><?php endif; ?><?php $bio = self::plain( isset( $item['short_description'] ) ? $item['short_description'] : '', self::plain( isset( $item['bio'] ) ? $item['bio'] : '' ) ); if ( $bio ) : ?><p><?php echo esc_html( $bio ); ?></p><?php endif; ?><div class="instructor-meta"><?php if ( ! empty( $item['experience'] ) ) : ?><span><?php echo esc_html( $item['experience'] ); ?></span><?php endif; ?><?php if ( ! empty( $item['projects'] ) ) : ?><span><?php echo esc_html( $item['projects'] ); ?></span><?php endif; ?><?php if ( ! empty( $item['teaching_hours'] ) ) : ?><span><?php echo esc_html( $item['teaching_hours'] ); ?></span><?php endif; ?></div></div></article></div></section>
		<?php
	}

	private static function reviews( $post_id, $product, $data, $items = array() ) {
		$d = $data['reviews'];
		$items = array_values( array_filter( $items, function ( $item ) {
			return empty( $item['is_sample'] ) && ! empty( $item['name'] ) && ! empty( $item['text'] );
		} ) );
		$can_submit = true;
		if ( ! $items ) {
			?>
			<section class="review-entry review-entry--solo" id="reviews"><div class="container"><button class="review-submit-trigger review-submit-trigger--solo reveal" type="button" data-spp-v3-review-open><span class="review-submit-trigger__icon" aria-hidden="true"><?php echo self::svg( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><b>تجربه شما از این دوره</b><small>اگر در این دوره شرکت کرده‌اید، تجربه‌تان می‌تواند به انتخاب دیگران کمک کند.</small></span><i aria-hidden="true">←</i></button></div></section>
			<?php
			self::review_form( $post_id, $product );
			return;
		}
		$rating = (float) $product->get_average_rating();
		if ( $rating <= 0 && $items ) {
			$ratings = array_values( array_filter( array_map( function ( $item ) { return isset( $item['rating'] ) ? (float) $item['rating'] : 0; }, $items ) ) );
			$rating = $ratings ? array_sum( $ratings ) / count( $ratings ) : 0;
		}
		$count = count( $items );
		$loop = array_merge( $items, $items );
		$media = array_values( array_filter( $items, function ( $item ) { return ! empty( $item['video_url'] ) || ! empty( $item['avatar'] ) || ! empty( $item['avatar_url'] ); } ) );
		?>
		<section class="reviews" id="reviews"><div class="container"><div class="reviews-top reveal"><header class="reviews-head"><span class="kicker"><?php echo esc_html( self::plain( $d['eyebrow'], 'تجربه شرکت‌کنندگان' ) ); ?></span><h2><?php echo esc_html( self::plain( $d['title'], 'تجربه شرکت‌کنندگان ' . $product->get_name() ) ); ?></h2><p>بخوانید شرکت‌کنندگان این دوره چه تجربه‌ای داشته‌اند و آموخته‌هایشان را چگونه به کار گرفته‌اند.</p><?php if ( $can_submit ) : ?><button class="review-submit-trigger" type="button" data-spp-v3-review-open><span class="review-submit-trigger__icon" aria-hidden="true"><?php echo self::svg( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span>ثبت تجربه شما</span></button><?php endif; ?></header><?php if ( $count > 0 && $rating > 0 ) : ?><aside class="reviews-score"><strong><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></strong><div><span>★★★★★</span><small>از نگاه <?php echo esc_html( number_format_i18n( $count ) ); ?> شرکت‌کننده</small></div></aside><?php endif; ?></div></div><div class="reviews-marquee reveal" aria-label="نظر شرکت‌کنندگان"><div class="reviews-track">
		<?php foreach ( $loop as $index => $item ) : ?><article class="review-card"<?php echo $index >= count( $items ) ? ' aria-hidden="true"' : ''; ?>><header><span class="review-card__avatar"><?php if ( ! empty( $item['avatar'] ) ) { echo wp_get_attachment_image( absint( $item['avatar'] ), 'thumbnail', false, array( 'alt' => $item['name'], 'loading' => 'lazy' ) ); } elseif ( ! empty( $item['avatar_url'] ) ) { ?><img src="<?php echo esc_url( $item['avatar_url'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" loading="lazy" decoding="async"><?php } else { echo esc_html( mb_substr( $item['name'], 0, 1 ) ); } ?></span><div><h3><?php echo esc_html( $item['name'] ); ?></h3><small><?php echo esc_html( trim( ( $item['job'] ?? '' ) . ( ! empty( $item['company'] ) ? '، ' . $item['company'] : '' ), '، ' ) ); ?></small></div></header><blockquote><?php echo esc_html( $item['text'] ); ?></blockquote><?php if ( ! empty( $item['verified'] ) ) : ?><footer>خرید تأییدشده</footer><?php endif; ?></article><?php endforeach; ?>
		</div></div><?php if ( $media ) : ?><div class="container"><div class="reviews-media reveal"><header class="reviews-media__head"><div><h3>روایت تصویری شرکت‌کنندگان</h3><p>تجربه‌های واقعی را از زبان خود شرکت‌کنندگان ببینید.</p></div></header><div class="reviews-gallery">
		<?php foreach ( array_slice( $media, 0, 3 ) as $item ) : ?><article class="review-media<?php echo empty( $item['video_url'] ) ? ' review-media--image' : ''; ?>"<?php if ( ! empty( $item['video_url'] ) ) : ?> data-spp-v3-video="<?php echo esc_url( $item['video_url'] ); ?>"<?php endif; ?>><?php if ( ! empty( $item['avatar'] ) ) { echo wp_get_attachment_image( absint( $item['avatar'] ), 'medium_large', false, array( 'alt' => $item['name'], 'loading' => 'lazy' ) ); } elseif ( ! empty( $item['avatar_url'] ) ) { ?><img src="<?php echo esc_url( $item['avatar_url'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" loading="lazy" decoding="async"><?php } ?><span class="review-media__play" aria-hidden="true"><?php echo empty( $item['video_url'] ) ? '▧' : '▶'; ?></span><div class="review-media__copy"><b><?php echo esc_html( $item['name'] ); ?></b><small><?php echo esc_html( $item['job'] ?? '' ); ?></small></div></article><?php endforeach; ?>
		</div></div></div><?php endif; ?></section>
		<?php
		if ( $can_submit ) {
			self::review_form( $post_id, $product );
		}
	}

	private static function review_form( $post_id, $product ) {
		$user           = wp_get_current_user();
		$is_logged_in   = $user && $user->exists();
		$name           = $is_logged_in ? $user->display_name : '';
		$email          = $is_logged_in ? $user->user_email : '';
		$email_required = (bool) get_option( 'require_name_email' );
		$title_id       = 'spp-review-title-' . absint( $post_id );
		$description_id = 'spp-review-description-' . absint( $post_id );
		?>
		<div class="review-modal" hidden data-spp-v3-review-modal>
			<div class="review-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $title_id ); ?>" aria-describedby="<?php echo esc_attr( $description_id ); ?>" tabindex="-1">
				<button class="review-dialog__close" type="button" data-spp-v3-review-close aria-label="بستن فرم ثبت نظر">×</button>
				<header class="review-dialog__head"><span class="kicker">تجربه شما</span><h2 id="<?php echo esc_attr( $title_id ); ?>">نظر شما درباره <?php echo esc_html( $product->get_name() ); ?></h2><p id="<?php echo esc_attr( $description_id ); ?>">صادقانه بنویسید این دوره چه کمکی به شما کرد. پس از بررسی، تجربه‌تان با دیگران به اشتراک گذاشته می‌شود.</p></header>
				<form class="review-form" data-spp-v3-review-form novalidate>
					<div class="review-form__grid"><label><span>نام و نام خانوادگی <b aria-hidden="true">*</b></span><input type="text" name="name" value="<?php echo esc_attr( $name ); ?>" autocomplete="name" required></label><label><span>ایمیل<?php if ( $email_required ) : ?> <b aria-hidden="true">*</b><?php endif; ?></span><input type="email" name="email" value="<?php echo esc_attr( $email ); ?>" autocomplete="email"<?php echo $email_required ? ' required' : ''; ?>></label></div>
					<label><span>سمت یا حوزه فعالیت <small>(اختیاری)</small></span><input type="text" name="role" autocomplete="organization-title"></label>
					<fieldset class="review-rating"><legend>امتیاز شما <b aria-hidden="true">*</b></legend><div role="radiogroup" aria-label="امتیاز از پنج"><label><input type="radio" name="rating" value="1" required><span aria-hidden="true">★</span><em>۱</em></label><label><input type="radio" name="rating" value="2"><span aria-hidden="true">★</span><em>۲</em></label><label><input type="radio" name="rating" value="3"><span aria-hidden="true">★</span><em>۳</em></label><label><input type="radio" name="rating" value="4"><span aria-hidden="true">★</span><em>۴</em></label><label><input type="radio" name="rating" value="5"><span aria-hidden="true">★</span><em>۵</em></label></div></fieldset>
					<label><span>متن تجربه شما <b aria-hidden="true">*</b></span><textarea name="text" rows="5" minlength="10" required placeholder="چه چیزی در این دوره برای شما مفید بود؟"></textarea></label>
					<label class="review-form__hp" aria-hidden="true">این فیلد را خالی بگذارید<input type="text" name="spp_hp" tabindex="-1" autocomplete="off"></label>
					<input type="hidden" name="product" value="<?php echo esc_attr( $post_id ); ?>">
					<p class="review-form__status" data-spp-v3-review-status aria-live="polite"></p>
					<button class="review-form__submit" type="submit">ارسال نظر برای تأیید <span aria-hidden="true">←</span></button>
				</form>
			</div>
		</div>
		<?php
	}

	private static function discount( $product ) {
		$regular = (float) $product->get_regular_price(); $sale = (float) $product->get_sale_price();
		return $regular > 0 && $sale > 0 && $sale < $regular ? (int) round( ( ( $regular - $sale ) / $regular ) * 100 ) : 0;
	}

	private static function enrollment( $product, $data ) {
		$d = $data['enrollment']; $discount = self::discount( $product ); $purchase_url = $product->add_to_cart_url();
		$guarantee = $data['guarantee'];
		$card = SPP_V3_Enroll::card_details( $product, $data );
		$manual = SPP_V3_Enroll::is_active( $product, $data );
		$summary_items = array();
		foreach ( $data['facts']['items'] as $fact ) {
			$summary = trim( self::plain( $fact['title'] ) . ( ! empty( $fact['value'] ) ? ': ' . self::plain( $fact['value'] ) : '' ), ': ' );
			if ( $summary ) { $summary_items[] = $summary; }
		}
		foreach ( $data['benefits']['items'] as $benefit ) {
			if ( ! empty( $benefit['title'] ) ) { $summary_items[] = self::plain( $benefit['title'] ); }
		}
		if ( '1' === $data['certificate']['enabled'] ) { $summary_items[] = self::plain( $data['certificate']['badge'], 'گواهینامه پایان دوره' ); }
		$summary_items = array_slice( array_values( array_unique( array_filter( $summary_items ) ) ), 0, 5 );
		?>
		<section class="enrollment" id="enroll"><div class="container"><header class="enroll-head reveal"><span class="kicker"><?php echo esc_html( self::plain( $d['eyebrow'], 'ثبت‌نام' ) ); ?></span><h2><?php echo esc_html( self::plain( $d['title'], 'ثبت‌نام در ' . $product->get_name() ) ); ?></h2><p>روش پرداخت مناسب خود را انتخاب کنید و مسیر یادگیری‌تان را همین امروز آغاز کنید.</p></header><?php if ( '1' === $guarantee['enabled'] ) : ?><div class="enroll-guarantee reveal"><span class="enroll-guarantee__icon"><?php echo self::svg( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b><?php echo esc_html( self::plain( $guarantee['title'], self::plain( $guarantee['duration'], 'شرایط ضمانت دوره' ) ) ); ?></b><small><?php echo esc_html( self::plain( $guarantee['description'], 'با خیال آسوده تصمیم بگیرید؛ شرایط ضمانت پیش از خرید شفاف است.' ) ); ?></small></div></div><?php endif; ?><div class="enroll-layout reveal"><div class="enroll-main"><div class="enroll-tabs" role="tablist" aria-label="روش‌های پرداخت"><button class="enroll-tab active" type="button" role="tab" aria-selected="true" data-enroll-target="pay-cash">پرداخت نقدی</button><?php if ( '1' === $d['installment_enabled'] ) : ?><button class="enroll-tab" type="button" role="tab" aria-selected="false" data-enroll-target="pay-installment">پرداخت اقساطی</button><?php endif; ?><?php if ( '1' === $d['group_enabled'] ) : ?><button class="enroll-tab" type="button" role="tab" aria-selected="false" data-enroll-target="pay-team">خرید گروهی</button><?php endif; ?></div>
		<article class="enroll-panel active" id="pay-cash"><span class="enroll-status"><?php echo $product->is_in_stock() ? 'ثبت‌نام باز است' : 'ظرفیت تکمیل شده'; ?></span><h3>پرداخت کامل<?php echo $discount ? ' با ' . esc_html( number_format_i18n( $discount ) ) . '٪ تخفیف' : ''; ?></h3><p>هزینه دوره را یک‌جا پرداخت کنید و ثبت‌نامتان را بدون مرحله اضافه کامل کنید.</p><div class="enroll-values"><div class="enroll-value"><b><?php echo wp_kses_post( $product->get_price_html() ); ?></b><small>مبلغ ثبت‌نام</small></div><div class="enroll-value"><b><?php echo $manual ? 'کارت به کارت' : 'پرداخت امن'; ?></b><small><?php echo $manual ? 'ثبت رسید واریزی' : 'درگاه بانکی'; ?></small></div><div class="enroll-value"><b>شروع سریع</b><small><?php echo $manual ? 'پس از تأیید رسید' : 'پس از تکمیل خرید'; ?></small></div></div><div class="enroll-action-row"><?php echo self::enroll_cta( 'enroll-cta', self::plain( $d['cash_cta'], 'ثبت‌نام و پرداخت' ), $purchase_url, $product, $manual ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div></article>
		<?php if ( '1' === $d['installment_enabled'] ) : ?><article class="enroll-panel" id="pay-installment"><span class="enroll-status">پرداخت منعطف</span><h3><?php echo esc_html( self::plain( $d['installment_count'], '۳' ) ); ?> مرحله پرداخت، بدون فشار نقدینگی</h3><p><?php echo esc_html( $d['installment_description'] ); ?></p><div class="enroll-values"><div class="enroll-value"><b><?php echo esc_html( $d['installment_downpayment'] ); ?></b><small>پیش‌پرداخت</small></div><div class="enroll-value"><b><?php echo esc_html( $d['installment_amount'] ); ?></b><small>مبلغ هر قسط</small></div><div class="enroll-value"><b><?php echo esc_html( $d['installment_interval'] ); ?></b><small>فاصله اقساط</small></div></div><div class="enroll-action-row"><a class="enroll-cta" href="<?php echo esc_url( $d['installment_url'] ); ?>"><?php echo esc_html( $d['installment_cta'] ); ?> ←</a></div></article><?php endif; ?>
		<?php if ( '1' === $d['group_enabled'] ) : ?><article class="enroll-panel" id="pay-team"><span class="enroll-status">ویژه تیم‌ها</span><h3>هم‌مسیر شوید، یک زبان تصمیم بسازید</h3><p><?php echo esc_html( $d['group_description'] ); ?></p><div class="enroll-values"><div class="enroll-value"><b><?php echo esc_html( $d['group_min'] ); ?> نفر به بالا</b><small>تعرفه اختصاصی تیمی</small></div><div class="enroll-value"><b><?php echo esc_html( $d['group_price'] ); ?></b><small>قیمت هر نفر</small></div><div class="enroll-value"><b>هماهنگی سریع</b><small>مشاوره پیش از خرید</small></div></div><div class="enroll-action-row"><a class="enroll-cta" href="<?php echo esc_url( $d['group_url'] ); ?>"><?php echo esc_html( $d['group_cta'] ); ?> ←</a></div></article><?php endif; ?>
		</div><aside class="enroll-summary"><span class="enroll-summary__eyebrow">آنچه با ثبت‌نام دریافت می‌کنید</span><div class="enroll-summary__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div><?php if ( $discount ) : ?><span class="enroll-discount"><?php echo esc_html( number_format_i18n( $discount ) ); ?>٪ تخفیف ویژه</span><?php endif; ?><?php if ( $summary_items ) : ?><ul class="enroll-list"><?php foreach ( $summary_items as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul><?php endif; ?><?php echo self::enroll_cta( 'btn btn-primary', self::plain( $data['intro']['primary_cta'], 'ثبت‌نام در دوره' ), $purchase_url, $product, $manual ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ( ! $manual ) : ?><small class="enroll-summary__note">پرداخت امن انجام می‌شود و رسید خرید در اختیار شما قرار می‌گیرد.</small><?php endif; ?></aside></div></div></section><?php if ( $manual ) { self::enroll_modal( $product, $card ); } ?>
		<?php
	}

	/**
	 * دکمه‌ی ثبت‌نام؛ در حالت کارت‌به‌کارت پاپ‌آپ را باز می‌کند و در غیر این
	 * صورت همان مسیر همیشگی سبد خرید را نگه می‌دارد.
	 *
	 * @param string     $class  کلاس ظاهری دکمه.
	 * @param string     $label  متن دکمه.
	 * @param string     $url    نشانی افزودن به سبد خرید.
	 * @param WC_Product $product
	 * @param bool       $manual فعال بودن ثبت‌نام کارت‌به‌کارت.
	 * @return string
	 */
	private static function enroll_cta( $class, $label, $url, $product, $manual ) {
		$text = esc_html( $label ) . ' ←';

		if ( $manual ) {
			return '<button type="button" class="' . esc_attr( $class ) . '" data-spp-v3-enroll-open data-product_id="' . esc_attr( $product->get_id() ) . '">' . $text . '</button>';
		}

		return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '" data-product_id="' . esc_attr( $product->get_id() ) . '">' . $text . '</a>';
	}

	/**
	 * پاپ‌آپ ثبت‌نام: نمایش شماره کارت و دریافت رسید واریزی و مشخصات کاربر.
	 *
	 * @param WC_Product $product
	 * @param array      $card اطلاعات کارت و متن‌ها.
	 */
	private static function enroll_modal( $product, $card ) {
		$post_id     = $product->get_id();
		$user        = wp_get_current_user();
		$is_member   = $user && $user->exists();
		$first_name  = $is_member ? $user->first_name : '';
		$last_name   = $is_member ? $user->last_name : '';
		$title_id    = 'spp-enroll-title-' . absint( $post_id );
		?>
		<div class="review-modal enroll-modal" hidden data-spp-v3-enroll-modal>
			<div class="review-dialog enroll-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $title_id ); ?>" tabindex="-1">
				<button class="review-dialog__close" type="button" data-spp-v3-enroll-close aria-label="بستن فرم ثبت‌نام">×</button>
				<header class="enroll-dialog__head"><h2 id="<?php echo esc_attr( $title_id ); ?>">ثبت‌نام دوره</h2><?php if ( '' !== $card['note'] ) : ?><p><?php echo esc_html( $card['note'] ); ?></p><?php endif; ?></header>

				<div class="enroll-card">
					<?php if ( '' !== $card['number'] ) : ?><div class="enroll-card__row"><span>شماره کارت</span><strong class="enroll-card__number" dir="ltr"><?php echo esc_html( SPP_V3_Enroll::group( $card['number'] ) ); ?></strong><button type="button" class="enroll-card__copy" data-spp-v3-copy="<?php echo esc_attr( $card['number'] ); ?>" aria-label="کپی شماره کارت">کپی</button></div><?php endif; ?>
					<?php if ( '' !== $card['iban'] ) : ?><div class="enroll-card__row enroll-card__row--iban"><span>شماره شبا</span><strong class="enroll-card__number enroll-card__number--iban" dir="ltr"><?php echo esc_html( SPP_V3_Enroll::group( $card['iban'] ) ); ?></strong><button type="button" class="enroll-card__copy" data-spp-v3-copy="<?php echo esc_attr( $card['iban'] ); ?>" aria-label="کپی شماره شبا">کپی</button></div><?php endif; ?>
					<?php if ( '' !== $card['holder'] ) : ?><div class="enroll-card__row"><span>به نام</span><strong><?php echo esc_html( $card['holder'] ); ?></strong></div><?php endif; ?>
					<?php if ( '' !== $card['bank'] ) : ?><div class="enroll-card__row"><span>بانک</span><strong><?php echo esc_html( $card['bank'] ); ?></strong></div><?php endif; ?>
					<?php if ( '' !== $card['amount'] ) : ?><div class="enroll-card__row"><span>مبلغ قابل واریز</span><strong><?php echo esc_html( $card['amount'] ); ?></strong></div><?php endif; ?>
				</div>

				<form class="review-form enroll-form" data-spp-v3-enroll-form novalidate>
					<div class="review-form__grid">
						<label><span>نام <b aria-hidden="true">*</b></span><input type="text" name="first_name" value="<?php echo esc_attr( $first_name ); ?>" autocomplete="given-name" required></label>
						<label><span>نام خانوادگی <b aria-hidden="true">*</b></span><input type="text" name="last_name" value="<?php echo esc_attr( $last_name ); ?>" autocomplete="family-name" required></label>
					</div>
					<div class="review-form__grid">
						<label><span>شماره موبایل <b aria-hidden="true">*</b></span><input type="tel" name="phone" inputmode="numeric" dir="ltr" placeholder="09123456789" autocomplete="tel" required></label>
						<label><span>توضیح <small>(اختیاری)</small></span><input type="text" name="note"></label>
					</div>
					<div class="enroll-upload">
						<span class="enroll-upload__label">تصویر رسید واریزی <b aria-hidden="true">*</b></span>
						<label class="enroll-upload__drop">
							<input type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" required data-spp-v3-enroll-file>
							<span class="enroll-upload__icon" aria-hidden="true">↑</span>
							<span class="enroll-upload__text" data-spp-v3-enroll-filename>فایل رسید را انتخاب کنید</span>
							<small>JPG، PNG، WEBP یا PDF تا <?php echo esc_html( SPP_V3_Enroll::max_size_label() ); ?></small>
						</label>
					</div>
					<label class="review-form__hp" aria-hidden="true">این فیلد را خالی بگذارید<input type="text" name="spp_hp" tabindex="-1" autocomplete="off"></label>
					<input type="hidden" name="product" value="<?php echo esc_attr( $post_id ); ?>">
					<p class="review-form__status" data-spp-v3-enroll-status aria-live="polite"></p>
					<button class="review-form__submit" type="submit">تکمیل ثبت‌نام <span aria-hidden="true">←</span></button>
				</form>
			</div>
		</div>
		<?php
	}

	private static function faq( $product, $data ) {
		$d = $data['faq']; $items = array_values( array_filter( $d['items'], function ( $item ) { return ! empty( $item['question'] ); } ) );
		if ( ! $items ) {
			$items = array(
				array( 'question' => 'بعد از ثبت‌نام چه اتفاقی می‌افتد؟', 'answer' => 'پس از پرداخت، رسید خرید و وضعیت ثبت‌نام ' . $product->get_name() . ' را در حساب کاربری‌تان می‌بینید.' ),
				array( 'question' => 'برای شروع این دوره چه پیش‌نیازی لازم است؟', 'answer' => self::plain( $data['seo']['prerequisites'], 'برای شروع به پیش‌نیاز ویژه‌ای احتیاج ندارید؛ کافی است برای یادگیری و تمرین زمان بگذارید.' ) ),
			);
			if ( '1' === $data['certificate']['enabled'] ) { $items[] = array( 'question' => 'چطور گواهینامه دوره را دریافت می‌کنم؟', 'answer' => self::plain( $data['certificate']['conditions'], 'پس از کامل‌کردن مسیر آموزشی و ارزیابی پایانی، گواهینامه پایان دوره برای شما صادر می‌شود.' ) ); }
			if ( '1' === $data['enrollment']['installment_enabled'] ) { $items[] = array( 'question' => 'می‌توانم هزینه دوره را اقساطی پرداخت کنم؟', 'answer' => self::plain( $data['enrollment']['installment_description'], 'بله؛ می‌توانید تعداد اقساط، مبلغ هر قسط و زمان پرداخت‌ها را پیش از ثبت‌نام ببینید.' ) ); }
		}
		?>
		<section class="faq" id="faq"><div class="container faq-grid"><div class="faq-intro reveal"><span class="kicker"><?php echo esc_html( self::plain( $d['eyebrow'], 'پیش از تصمیم' ) ); ?></span><h2><?php echo esc_html( self::plain( $d['title'], 'پاسخ سؤال‌هایی که پیش از ثبت‌نام دارید.' ) ); ?></h2><p>هر چیزی که برای یک انتخاب مطمئن لازم دارید، کوتاه و روشن اینجاست.</p></div><div class="faq-list reveal"><?php foreach ( $items as $index => $item ) : ?><article class="faq-item<?php echo 0 === $index ? ' open' : ''; ?>"><button type="button" aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>"><span><?php echo esc_html( $item['question'] ); ?></span><i class="plus"></i></button><div class="faq-answer"><?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?></div></article><?php endforeach; ?></div></div></section>
		<?php
	}

	private static function final_cta( $product, $data ) {
		?><section class="final-cta"><div class="container"><div class="final-shell reveal"><span class="kicker">گام بعدی شما</span><h2>برای شروع <?php echo esc_html( $product->get_name() ); ?> آماده‌اید؟</h2><p>اگر این مسیر همان چیزی است که برای رشد و نتیجه بهتر نیاز دارید، همین امروز اولین قدم را بردارید.</p><div class="final-actions"><a href="#enroll" class="btn btn-primary"><?php echo esc_html( self::plain( $data['intro']['primary_cta'], 'ثبت‌نام در دوره' ) ); ?> ←</a><a href="#curriculum" class="btn btn-ghost">مرور دوباره سرفصل‌ها</a></div></div></div></section><?php
	}

	private static function footer( $product, $data ) {
		?><footer class="footer"><div class="container">SAZAN</div></footer><div class="mobile-bar"><div class="mobile-bar__price"><small>هزینه ثبت‌نام</small><strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong></div><a href="#enroll"><span>ثبت‌نام دوره</span><?php echo self::svg( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></div><?php
	}
}
