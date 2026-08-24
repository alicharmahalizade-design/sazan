<?php
/** Front-end section renderer for Product Page v2. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Renderer {

	public static function render( $section, $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || 'product' !== get_post_type( $post_id ) ) {
			return '';
		}
		$data = SPP_V2_Data::get( $post_id );
		$method = 'section_' . str_replace( '-', '_', sanitize_key( $section ) );
		if ( ! method_exists( __CLASS__, $method ) ) {
			return '';
		}
		return (string) self::$method( $post_id, $data );
	}

	private static function product( $post_id ) {
		return function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;
	}

	private static function section_header( $eyebrow, $title, $description = '', $heading_id = '' ) {
		if ( '' === trim( $title ) ) {
			return '';
		}
		ob_start();
		?>
		<header class="spp-v2-section-head">
			<?php if ( $eyebrow ) : ?><span class="spp-v2-eyebrow"><?php echo esc_html( $eyebrow ); ?></span><?php endif; ?>
			<h2<?php echo $heading_id ? ' id="' . esc_attr( $heading_id ) . '"' : ''; ?>><?php echo esc_html( $title ); ?></h2>
			<?php if ( $description ) : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?>
		</header>
		<?php
		return (string) ob_get_clean();
	}

	private static function image( $attachment_id, $size, $class, $alt = '', $loading = 'lazy' ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return '';
		}
		if ( '' === trim( (string) $alt ) ) {
			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		}
		if ( '' === trim( (string) $alt ) ) {
			$alt = get_the_title( $attachment_id );
		}
		$attrs = array( 'class' => $class, 'alt' => $alt, 'loading' => $loading, 'decoding' => 'async' );
		if ( 'eager' === $loading ) {
			$attrs['fetchpriority'] = 'high';
		}
		return wp_get_attachment_image( $attachment_id, $size, false, $attrs );
	}

	private static function button( $text, $url, $class = 'spp-v2-btn--primary' ) {
		if ( ! $text || ! $url ) {
			return '';
		}
		return '<a class="spp-v2-btn ' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '<span aria-hidden="true">←</span></a>';
	}

	private static function stars( $rating ) {
		$rating = max( 0, min( 5, (float) $rating ) );
		return '<span class="spp-v2-stars" aria-label="' . esc_attr( sprintf( 'امتیاز %s از ۵', $rating ) ) . '"><span style="--spp-v2-rating:' . esc_attr( ( $rating / 5 ) * 100 ) . '%">★★★★★</span></span>';
	}

	private static function video_button( $url, $label, $class = '' ) {
		if ( ! $url ) {
			return '';
		}
		return '<button type="button" class="spp-v2-video-trigger ' . esc_attr( $class ) . '" data-spp-v2-video="' . esc_url( $url ) . '" aria-haspopup="dialog"><span class="spp-v2-play" aria-hidden="true">▶</span><span>' . esc_html( $label ) . '</span></button>';
	}

	private static function status( $key ) {
		$labels = array( 'open' => 'ثبت‌نام باز', 'limited' => 'ظرفیت محدود', 'soon' => 'به‌زودی', 'closed' => 'پایان ثبت‌نام', 'reserve' => 'رزرو', 'soldout' => 'تکمیل ظرفیت' );
		return isset( $labels[ $key ] ) ? $labels[ $key ] : $labels['open'];
	}

	private static function breadcrumb( $post_id, $product_name ) {
		$items = array(
			array( 'label' => 'خانه', 'url' => home_url( '/' ) ),
		);
		if ( function_exists( 'wc_get_page_id' ) ) {
			$shop_id = wc_get_page_id( 'shop' );
			if ( $shop_id > 0 ) {
				$items[] = array( 'label' => get_the_title( $shop_id ), 'url' => get_permalink( $shop_id ) );
			}
		}
		$terms = get_the_terms( $post_id, 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$term = reset( $terms );
			$url = get_term_link( $term );
			if ( ! is_wp_error( $url ) ) {
				$items[] = array( 'label' => $term->name, 'url' => $url );
			}
		}
		$items[] = array( 'label' => $product_name, 'url' => '' );
		ob_start();
		?>
		<nav class="spp-v2-breadcrumb spp-v2-container" aria-label="مسیر راهنمای صفحه">
			<ol><?php foreach ( $items as $index => $item ) : ?><li><?php if ( $item['url'] ) : ?><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a><?php else : ?><span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span><?php endif; ?><?php if ( $index < count( $items ) - 1 ) : ?><b aria-hidden="true">/</b><?php endif; ?></li><?php endforeach; ?></ol>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	private static function section_course_hero( $post_id, $data ) {
		$product = self::product( $post_id );
		if ( ! $product ) {
			return '';
		}
		$d = $data['intro'];
		$facts = array_slice( array_values( array_filter( $data['facts']['items'], function ( $item ) { return ! empty( $item['title'] ) || ! empty( $item['value'] ); } ) ), 0, 4 );
		$image_id = absint( $d['hero_image'] ) ?: absint( $product->get_image_id() );
		$short = $d['short_description'];
		if ( ! $short ) {
			$short = wp_strip_all_tags( apply_filters( 'woocommerce_short_description', get_post_field( 'post_excerpt', $post_id ) ) );
		}
		$rating = '' !== $d['manual_rating'] ? (float) $d['manual_rating'] : (float) $product->get_average_rating();
		$review_count = (int) $product->get_review_count();
		$background = absint( $d['background_image'] ) ? wp_get_attachment_image_url( absint( $d['background_image'] ), 'full' ) : '';
		$style = $background ? '--spp-v2-hero-bg:url(' . esc_url( $background ) . ');' : '';
		ob_start();
		?>
		<section id="spp-v2-top" class="spp-v2 spp-v2-section spp-v2-hero" dir="rtl" style="<?php echo esc_attr( $style ); ?>" data-spp-v2-section="hero" aria-labelledby="spp-v2-course-title">
			<?php echo self::breadcrumb( $post_id, $product->get_name() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="spp-v2-container spp-v2-hero__grid">
				<div class="spp-v2-hero__content">
					<?php if ( $d['eyebrow'] ) : ?><span class="spp-v2-eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span><?php endif; ?>
					<?php if ( $d['badge'] ) : ?><span class="spp-v2-badge spp-v2-badge--gold"><?php echo esc_html( $d['badge'] ); ?></span><?php endif; ?>
					<h1 id="spp-v2-course-title"><?php echo esc_html( $product->get_name() ); ?></h1>
					<?php if ( $d['subtitle'] ) : ?><p class="spp-v2-hero__subtitle"><?php echo esc_html( $d['subtitle'] ); ?></p><?php endif; ?>
					<?php if ( $short ) : ?><p class="spp-v2-hero__lead"><?php echo esc_html( $short ); ?></p><?php endif; ?>
					<div class="spp-v2-hero__proof">
						<?php if ( $rating > 0 ) : ?><?php echo self::stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?><strong><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></strong><?php endif; ?>
						<?php if ( $review_count ) : ?><span><?php echo esc_html( sprintf( '%s نظر', number_format_i18n( $review_count ) ) ); ?></span><?php endif; ?>
						<?php if ( '1' === $d['show_students'] && $d['student_count'] ) : ?><span><?php echo esc_html( sprintf( '%s دانشجو', number_format_i18n( (int) $d['student_count'] ) ) ); ?></span><?php endif; ?>
					</div>
					<?php if ( $facts ) : ?>
						<ul class="spp-v2-hero__facts">
							<?php foreach ( $facts as $fact ) : ?><li><?php echo spp_icon( $fact['icon'], 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><small><?php echo esc_html( $fact['title'] ); ?></small><strong><?php echo esc_html( $fact['value'] ); ?></strong></span></li><?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="spp-v2-hero__actions">
						<?php echo self::button( $d['primary_cta'], '#spp-v2-enrollment' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $d['preview_video'] ) : ?><?php echo self::video_button( $d['preview_video'], $d['secondary_cta'] ?: 'مشاهده پیش‌نمایش' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php elseif ( $d['secondary_url'] ) : ?><?php echo self::button( $d['secondary_cta'], $d['secondary_url'], 'spp-v2-btn--ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?>
					</div>
					<?php if ( $d['course_code'] ) : ?><p class="spp-v2-course-code">کد دوره: <bdi><?php echo esc_html( $d['course_code'] ); ?></bdi></p><?php endif; ?>
				</div>
				<div class="spp-v2-hero__visual">
					<div class="spp-v2-hero__media">
						<?php echo self::image( $image_id, 'large', 'spp-v2-hero__image', $product->get_name(), 'eager' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php if ( $d['preview_video'] ) : ?><?php echo self::video_button( $d['preview_video'], 'پخش ویدیوی معرفی', 'spp-v2-video-trigger--overlay' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?>
					</div>
					<div class="spp-v2-hero__price">
						<span>سرمایه‌گذاری برای این دوره</span>
						<strong class="spp-v2-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></strong>
						<?php if ( $product->is_on_sale() ) : ?><span class="spp-v2-discount"><?php echo esc_html( self::discount_text( $product ) ); ?></span><?php endif; ?>
					</div>
				</div>
			</div>
			<div class="spp-v2-modal" data-spp-v2-modal hidden role="dialog" aria-modal="true" aria-label="ویدیوی معرفی دوره"><div class="spp-v2-modal__dialog"><button type="button" class="spp-v2-modal__close" data-spp-v2-modal-close aria-label="بستن ویدیو">×</button><div class="spp-v2-modal__media" data-spp-v2-modal-media></div></div></div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function section_course_nav( $post_id, $data ) {
		if ( '1' !== $data['intro']['show_navigation'] ) {
			return '';
		}
		$items = array(
			'spp-v2-about' => 'معرفی', 'spp-v2-benefits' => 'چرا این دوره؟', 'spp-v2-curriculum' => 'سرفصل‌ها', 'spp-v2-instructors' => 'مدرس‌ها', 'spp-v2-reviews' => 'نظرات', 'spp-v2-enrollment' => 'ثبت‌نام', 'spp-v2-faq' => 'سوالات',
		);
		ob_start();
		?>
		<nav class="spp-v2 spp-v2-course-nav" data-spp-v2-nav aria-label="ناوبری صفحه دوره" dir="rtl"><div class="spp-v2-container"><div class="spp-v2-course-nav__track">
			<?php foreach ( $items as $target => $label ) : ?><a href="#<?php echo esc_attr( $target ); ?>" data-spp-v2-nav-link="<?php echo esc_attr( $target ); ?>"><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
			<a href="#spp-v2-enrollment" class="spp-v2-course-nav__cta">ثبت‌نام</a>
		</div></div></nav>
		<?php
		return (string) ob_get_clean();
	}

	private static function section_prerequisite( $post_id, $data ) {
		$d = $data['prerequisite'];
		if ( '1' !== $d['enabled'] || ( ! $d['title'] && ! $d['description'] ) ) {
			return '';
		}
		ob_start();
		?>
		<section id="spp-v2-prerequisite" class="spp-v2 spp-v2-section spp-v2-prerequisite" data-spp-v2-section="prerequisite" dir="rtl"><div class="spp-v2-container"><div class="spp-v2-prerequisite__card">
			<div class="spp-v2-prerequisite__icon"><?php echo spp_icon( 'check', 30 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div><?php if ( $d['eyebrow'] ) : ?><span class="spp-v2-eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span><?php endif; ?><h2><?php echo esc_html( $d['title'] ); ?></h2><div class="spp-v2-richtext"><?php echo wp_kses_post( wpautop( $d['description'] ) ); ?></div><?php echo self::button( $d['cta_text'], $d['cta_url'], 'spp-v2-btn--text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php echo self::image( $d['image'], 'medium', 'spp-v2-prerequisite__image', $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div></div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_benefits( $post_id, $data ) {
		$d = $data['benefits'];
		$items = array_values( array_filter( $d['items'], function ( $item ) { return ! empty( $item['title'] ) || ! empty( $item['description'] ); } ) );
		if ( ! $items ) {
			return '';
		}
		ob_start(); ?>
		<section id="spp-v2-benefits" class="spp-v2 spp-v2-section spp-v2-benefits" data-spp-v2-section="benefits" dir="rtl"><div class="spp-v2-container">
			<?php echo self::section_header( $d['eyebrow'], $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="spp-v2-benefits__grid"><?php foreach ( $items as $item ) : ?><article><span class="spp-v2-icon-box"><?php echo spp_icon( $item['icon'], 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php echo esc_html( $item['title'] ); ?></h3><p><?php echo esc_html( $item['description'] ); ?></p></article><?php endforeach; ?></div>
		</div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_course_introduction( $post_id, $data ) {
		$d = $data['about'];
		$title = $d['title'];
		$description = $d['description'];
		if ( ! $title ) {
			$title = get_the_title( $post_id );
		}
		if ( ! $description ) {
			$description = apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) );
		}
		if ( ! trim( wp_strip_all_tags( $description ) ) && ! $d['lead'] ) {
			return '';
		}
		ob_start(); ?>
		<section id="spp-v2-about" class="spp-v2 spp-v2-section spp-v2-about" data-spp-v2-section="about" dir="rtl"><div class="spp-v2-container spp-v2-about__grid">
			<div class="spp-v2-about__content"><?php if ( $d['eyebrow'] ) : ?><span class="spp-v2-eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span><?php endif; ?><h2><?php echo spp_highlight( $title, $d['highlight'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2><?php if ( $d['lead'] ) : ?><p class="spp-v2-about__lead"><?php echo esc_html( $d['lead'] ); ?></p><?php endif; ?><div class="spp-v2-richtext"><?php echo wp_kses_post( $description ); ?></div><?php echo self::button( $d['cta_text'], $d['cta_url'], 'spp-v2-btn--ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php if ( $d['image'] ) : ?><figure class="spp-v2-about__visual"><?php echo self::image( $d['image'], 'large', 'spp-v2-about__image', $title ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span aria-hidden="true"></span></figure><?php endif; ?>
		</div></section>
		<?php return (string) ob_get_clean();
	}

	private static function curriculum_stats( $groups ) {
		$chapters = 0; $lessons = 0;
		foreach ( $groups as $group ) {
			$chapters += count( $group['chapters'] );
			foreach ( $group['chapters'] as $chapter ) { $lessons += count( $chapter['lessons'] ); }
		}
		return array( count( $groups ), $chapters, $lessons );
	}

	private static function section_curriculum_overview( $post_id, $data ) {
		$d = $data['curriculum'];
		$groups = array_values( array_filter( $d['groups'], function ( $group ) { return ! empty( $group['title'] ) || ! empty( $group['chapters'] ); } ) );
		if ( ! $groups ) { return ''; }
		list( $group_count, $chapter_count, $lesson_count ) = self::curriculum_stats( $groups );
		ob_start(); ?>
		<section id="spp-v2-curriculum-overview" class="spp-v2 spp-v2-section spp-v2-curriculum-overview" dir="rtl"><div class="spp-v2-container"><div class="spp-v2-curriculum-overview__card">
			<div><?php if ( $d['eyebrow'] ) : ?><span class="spp-v2-eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span><?php endif; ?><h2><?php echo esc_html( $d['title'] ); ?></h2><?php if ( $d['description'] ) : ?><p><?php echo esc_html( $d['description'] ); ?></p><?php endif; ?></div>
			<ul><li><strong><?php echo esc_html( number_format_i18n( $group_count ) ); ?></strong><span>مسیر اصلی</span></li><li><strong><?php echo esc_html( number_format_i18n( $chapter_count ) ); ?></strong><span>فصل تخصصی</span></li><?php if ( $lesson_count ) : ?><li><strong><?php echo esc_html( number_format_i18n( $lesson_count ) ); ?></strong><span>جلسه آموزشی</span></li><?php endif; ?></ul>
		</div></div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_full_curriculum( $post_id, $data ) {
		$d = $data['curriculum'];
		$groups = array_values( array_filter( $d['groups'], function ( $group ) { return ! empty( $group['title'] ) || ! empty( $group['chapters'] ); } ) );
		if ( ! $groups ) { return ''; }
		ob_start(); ?>
		<section id="spp-v2-curriculum" class="spp-v2 spp-v2-section spp-v2-curriculum" data-spp-v2-section="curriculum" dir="rtl"><div class="spp-v2-container">
			<div class="spp-v2-curriculum__layout" data-spp-v2-curriculum>
				<aside class="spp-v2-curriculum__groups" role="tablist" aria-label="گروه‌های سرفصل">
					<?php foreach ( $groups as $g_index => $group ) : ?><button type="button" role="tab" id="spp-v2-group-tab-<?php echo (int) $g_index; ?>" aria-controls="spp-v2-group-<?php echo (int) $g_index; ?>" aria-selected="<?php echo 0 === $g_index ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $g_index ? '0' : '-1'; ?>" class="<?php echo 0 === $g_index ? 'is-active' : ''; ?>" data-spp-v2-group-tab="<?php echo (int) $g_index; ?>"><span><?php echo esc_html( sprintf( '%02d', $g_index + 1 ) ); ?></span><b><?php echo esc_html( $group['title'] ); ?></b><small><?php echo esc_html( trim( $group['course_count'] . ' · ' . $group['duration'], ' ·' ) ); ?></small></button><?php endforeach; ?>
				</aside>
				<div class="spp-v2-curriculum__panels">
					<?php foreach ( $groups as $g_index => $group ) : ?>
						<div id="spp-v2-group-<?php echo (int) $g_index; ?>" role="tabpanel" aria-labelledby="spp-v2-group-tab-<?php echo (int) $g_index; ?>" class="spp-v2-curriculum__panel<?php echo 0 === $g_index ? ' is-active' : ''; ?>" data-spp-v2-group-panel="<?php echo (int) $g_index; ?>"<?php echo 0 === $g_index ? '' : ' hidden'; ?>>
							<header class="spp-v2-curriculum__panelhead"><?php echo self::image( $group['image'], 'thumbnail', 'spp-v2-curriculum__group-image', $group['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><span>مرحله <?php echo esc_html( number_format_i18n( $g_index + 1 ) ); ?></span><h3><?php echo esc_html( $group['title'] ); ?></h3><?php if ( $group['description'] ) : ?><p><?php echo esc_html( $group['description'] ); ?></p><?php endif; ?></div></header>
							<div class="spp-v2-chapters">
								<?php foreach ( $group['chapters'] as $c_index => $chapter ) : if ( ! $chapter['title'] ) { continue; } $chapter_id = 'spp-v2-chapter-' . $g_index . '-' . $c_index; ?>
									<article class="spp-v2-chapter">
										<button type="button" class="spp-v2-chapter__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $chapter_id ); ?>" data-spp-v2-accordion>
											<span class="spp-v2-chapter__number"><?php echo esc_html( sprintf( '%02d', $c_index + 1 ) ); ?></span><span class="spp-v2-chapter__title"><b><?php echo esc_html( $chapter['title'] ); ?></b><small><?php echo esc_html( trim( $chapter['instructor'] . ' · ' . $chapter['duration'], ' ·' ) ); ?></small></span><span class="spp-v2-chapter__count"><?php echo esc_html( sprintf( '%s جلسه', number_format_i18n( count( $chapter['lessons'] ) ) ) ); ?></span><span class="spp-v2-plus" aria-hidden="true"></span>
										</button>
										<div id="<?php echo esc_attr( $chapter_id ); ?>" class="spp-v2-chapter__content" hidden>
											<?php if ( $chapter['description'] || $chapter['instructor'] ) : ?><div class="spp-v2-chapter__intro"><?php echo self::image( $chapter['instructor_image'], 'thumbnail', 'spp-v2-chapter__avatar', $chapter['instructor'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><?php if ( $chapter['instructor'] ) : ?><strong><?php echo esc_html( $chapter['instructor'] ); ?></strong><small><?php echo esc_html( $chapter['instructor_role'] ); ?></small><?php endif; ?><?php if ( $chapter['description'] ) : ?><p><?php echo esc_html( $chapter['description'] ); ?></p><?php endif; ?></div></div><?php endif; ?>
											<?php if ( $chapter['lessons'] ) : ?><ol class="spp-v2-lessons"><?php foreach ( $chapter['lessons'] as $lesson ) : if ( ! $lesson['title'] ) { continue; } ?><li><span class="spp-v2-lessons__icon"><?php echo spp_icon( $lesson['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php echo esc_html( $lesson['title'] ); ?></span><?php if ( $lesson['duration'] ) : ?><time><?php echo esc_html( $lesson['duration'] ); ?></time><?php endif; ?><?php if ( '1' === $lesson['preview'] && $lesson['preview_url'] ) : ?><button type="button" data-spp-v2-video="<?php echo esc_url( $lesson['preview_url'] ); ?>">پیش‌نمایش</button><?php endif; ?></li><?php endforeach; ?></ol><?php endif; ?>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_instructors( $post_id, $data ) {
		$d = $data['instructors'];
		$items = array_values( array_filter( $d['items'], function ( $item ) { return ! empty( $item['name'] ); } ) );
		if ( ! $items ) { return ''; }
		$single = 1 === count( $items );
		ob_start(); ?>
		<section id="spp-v2-instructors" class="spp-v2 spp-v2-section spp-v2-instructors" data-spp-v2-section="instructors" dir="rtl"><div class="spp-v2-container">
			<?php echo self::section_header( $d['eyebrow'], $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="spp-v2-instructors__grid<?php echo $single ? ' is-single' : ''; ?>"><?php foreach ( $items as $item ) : ?><article class="spp-v2-instructor"><div class="spp-v2-instructor__visual"><?php echo self::image( $item['image'], 'medium_large', 'spp-v2-instructor__image', $item['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><div class="spp-v2-instructor__body"><span>مدرس دوره</span><h3><?php echo esc_html( $item['name'] ); ?></h3><p class="spp-v2-instructor__role"><?php echo esc_html( $item['role'] ); ?></p><?php if ( $item['short_description'] ) : ?><p><?php echo esc_html( $item['short_description'] ); ?></p><?php endif; ?><div class="spp-v2-instructor__stats"><?php if ( $item['experience'] ) : ?><span><bdi><?php echo esc_html( $item['experience'] ); ?></bdi><small>سال تجربه</small></span><?php endif; ?><?php if ( $item['projects'] ) : ?><span><bdi><?php echo esc_html( $item['projects'] ); ?></bdi><small>پروژه</small></span><?php endif; ?><?php if ( $item['teaching_hours'] ) : ?><span><bdi><?php echo esc_html( $item['teaching_hours'] ); ?></bdi><small>ساعت آموزش</small></span><?php endif; ?></div><?php if ( ! empty( $item['company_logos'] ) ) : ?><div class="spp-v2-instructor__logos" aria-label="شرکت‌های همکار"><?php foreach ( $item['company_logos'] as $logo ) : if ( empty( $logo['image'] ) ) { continue; } echo self::image( $logo['image'], 'thumbnail', '', $logo['name'] ); endforeach; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?><?php if ( $item['bio'] ) : ?><details><summary>درباره مدرس</summary><div class="spp-v2-richtext"><?php echo wp_kses_post( wpautop( $item['bio'] ) ); ?></div></details><?php endif; ?><div class="spp-v2-instructor__links"><?php echo self::button( 'LinkedIn', $item['linkedin'], 'spp-v2-btn--text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo self::button( 'وب‌سایت', $item['website'], 'spp-v2-btn--text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div></div></article><?php endforeach; ?></div>
		</div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_certificate( $post_id, $data ) {
		$d = $data['certificate'];
		if ( '1' !== $d['enabled'] || ( ! $d['title'] && ! $d['image'] ) ) { return ''; }
		ob_start(); ?>
		<section id="spp-v2-certificate" class="spp-v2 spp-v2-section spp-v2-certificate" data-spp-v2-section="certificate" dir="rtl"><div class="spp-v2-container"><div class="spp-v2-certificate__card">
			<div class="spp-v2-certificate__content"><?php if ( $d['badge'] ) : ?><span class="spp-v2-badge spp-v2-badge--gold"><?php echo esc_html( $d['badge'] ); ?></span><?php endif; ?><h2><?php echo esc_html( $d['title'] ); ?></h2><div class="spp-v2-richtext"><?php echo wp_kses_post( wpautop( $d['description'] ) ); ?></div><?php if ( $d['conditions'] ) : ?><p class="spp-v2-certificate__condition"><?php echo spp_icon( 'certificate', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $d['conditions'] ); ?></p><?php endif; ?><?php echo self::button( $d['cta_text'], $d['cta_url'], 'spp-v2-btn--ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<figure><?php echo self::image( $d['image'], 'large', 'spp-v2-certificate__image', $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></figure>
		</div></div></section>
		<?php return (string) ob_get_clean();
	}

	public static function reviews_data( $post_id, $data = null ) {
		$data = is_array( $data ) ? $data : SPP_V2_Data::get( $post_id );
		$d = $data['reviews'];
		$out = array();
		if ( in_array( $d['source'], array( 'manual', 'both' ), true ) ) {
			foreach ( $d['items'] as $item ) {
				if ( ! empty( $item['is_sample'] ) ) {
					continue;
				}
				if ( $item['name'] || $item['text'] ) {
					$item['video_url'] = self::review_video_url( $item );
					$item['verified'] = '';
					$out[] = $item;
				}
			}
		}
		if ( in_array( $d['source'], array( 'woo', 'both' ), true ) && class_exists( 'SPP_Reviews' ) ) {
			foreach ( SPP_Reviews::approved( $post_id, 100 ) as $item ) {
				$out[] = array( 'avatar' => 0, 'avatar_url' => $item['avatar_url'], 'name' => $item['name'], 'job' => $item['role'], 'company' => '', 'rating' => $item['rating'], 'text' => $item['text'], 'video_id' => '', 'video_url' => '', 'is_sample' => '', 'verified' => '1' );
			}
		}
		return $out;
	}

	/** Resolve either an uploaded WordPress video or a direct video URL. */
	public static function review_video_url( $item ) {
		if ( ! empty( $item['video_url'] ) ) {
			return esc_url_raw( $item['video_url'] );
		}
		if ( ! empty( $item['video_id'] ) ) {
			$url = wp_get_attachment_url( absint( $item['video_id'] ) );
			return $url ? esc_url_raw( $url ) : '';
		}
		return '';
	}

	public static function review_card( $item ) {
		ob_start(); ?>
		<article class="spp-v2-review">
			<header><div class="spp-v2-review__avatar"><?php if ( ! empty( $item['avatar'] ) ) : ?><?php echo self::image( $item['avatar'], 'thumbnail', '', $item['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php elseif ( ! empty( $item['avatar_url'] ) ) : ?><img src="<?php echo esc_url( $item['avatar_url'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" loading="lazy" decoding="async"><?php else : ?><span aria-hidden="true"><?php echo esc_html( mb_substr( $item['name'], 0, 1 ) ); ?></span><?php endif; ?></div><div><h3><?php echo esc_html( $item['name'] ); ?></h3><p><?php echo esc_html( trim( $item['job'] . ( $item['company'] ? '، ' . $item['company'] : '' ), '، ' ) ); ?></p></div><?php echo self::stars( $item['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></header>
			<blockquote><?php echo esc_html( $item['text'] ); ?></blockquote>
			<?php if ( ! empty( $item['verified'] ) ) : ?><p class="spp-v2-review__verified">خرید تأییدشده</p><?php endif; ?>
			<?php if ( $item['video_url'] ) : ?><?php echo self::video_button( $item['video_url'], 'تماشای تجربه ویدیویی' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?>
		</article>
		<?php return (string) ob_get_clean();
	}

	private static function section_reviews( $post_id, $data ) {
		$d = $data['reviews'];
		$reviews = self::reviews_data( $post_id, $data );
		if ( ! $reviews ) { return ''; }
		$limit = max( 3, min( 12, absint( $d['initial_count'] ) ) );
		$visible = array_slice( $reviews, 0, $limit );
		ob_start(); ?>
		<section id="spp-v2-reviews" class="spp-v2 spp-v2-section spp-v2-reviews" data-spp-v2-section="reviews" dir="rtl"><div class="spp-v2-container">
			<?php echo self::section_header( $d['eyebrow'], $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="spp-v2-reviews__grid" data-spp-v2-review-grid><?php foreach ( $visible as $review ) { echo self::review_card( $review ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php if ( count( $reviews ) > $limit ) : ?><div class="spp-v2-reviews__more"><button type="button" class="spp-v2-btn spp-v2-btn--ghost" data-spp-v2-load-reviews data-product="<?php echo (int) $post_id; ?>" data-offset="<?php echo (int) $limit; ?>">نمایش نظرهای بیشتر</button><span role="status" aria-live="polite"></span></div><?php endif; ?>
		</div></section>
		<?php return (string) ob_get_clean();
	}

	private static function discount_text( $product ) {
		$regular = (float) $product->get_regular_price();
		$sale = (float) $product->get_price();
		if ( $regular > 0 && $sale > 0 && $regular > $sale ) {
			return sprintf( '%s٪ تخفیف', number_format_i18n( round( ( ( $regular - $sale ) / $regular ) * 100 ) ) );
		}
		return '';
	}

	private static function native_cart_form( $product, $button_text = '' ) {
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() || ! function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
			return '';
		}
		global $post;
		$GLOBALS['product'] = $product;
		$post = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$label_filter = null;
		if ( $button_text ) {
			$label_filter = static function () use ( $button_text ) { return $button_text; };
			add_filter( 'woocommerce_product_single_add_to_cart_text', $label_filter, 20 );
		}
		ob_start();
		woocommerce_template_single_add_to_cart();
		$html = (string) ob_get_clean();
		if ( $label_filter ) {
			remove_filter( 'woocommerce_product_single_add_to_cart_text', $label_filter, 20 );
		}
		return $html;
	}

	private static function section_enrollment( $post_id, $data ) {
		$product = self::product( $post_id );
		if ( ! $product ) { return ''; }
		$d = $data['enrollment'];
		$status = $product->is_in_stock() ? $d['status'] : 'soldout';
		$modes = array();
		if ( '1' === $d['cash_enabled'] ) { $modes['cash'] = 'پرداخت نقدی'; }
		if ( '1' === $d['installment_enabled'] ) { $modes['installment'] = 'پرداخت اقساطی'; }
		if ( '1' === $d['pre_enabled'] ) { $modes['pre'] = 'پیش‌ثبت‌نام'; }
		if ( '1' === $d['group_enabled'] ) { $modes['group'] = 'خرید گروهی'; }
		if ( ! $modes ) { $modes['cash'] = 'ثبت‌نام'; }
		$first = array_key_first( $modes );
		ob_start(); ?>
		<section id="spp-v2-enrollment" class="spp-v2 spp-v2-section spp-v2-enrollment" data-spp-v2-section="enrollment" dir="rtl"><div class="spp-v2-container">
			<?php echo self::section_header( $d['eyebrow'], $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="spp-v2-enrollment__layout" data-spp-v2-payment>
				<div class="spp-v2-enrollment__main">
					<div class="spp-v2-payment-tabs" role="tablist" aria-label="روش‌های ثبت‌نام"><?php foreach ( $modes as $mode => $label ) : ?><button type="button" role="tab" id="spp-v2-payment-tab-<?php echo esc_attr( $mode ); ?>" aria-controls="spp-v2-payment-<?php echo esc_attr( $mode ); ?>" aria-selected="<?php echo $mode === $first ? 'true' : 'false'; ?>" tabindex="<?php echo $mode === $first ? '0' : '-1'; ?>" data-spp-v2-payment-tab="<?php echo esc_attr( $mode ); ?>" class="<?php echo $mode === $first ? 'is-active' : ''; ?>"><?php echo esc_html( $label ); ?></button><?php endforeach; ?></div>
					<div class="spp-v2-payment-panels">
						<?php foreach ( $modes as $mode => $label ) : ?><div id="spp-v2-payment-<?php echo esc_attr( $mode ); ?>" role="tabpanel" aria-labelledby="spp-v2-payment-tab-<?php echo esc_attr( $mode ); ?>" data-spp-v2-payment-panel="<?php echo esc_attr( $mode ); ?>"<?php echo $mode === $first ? '' : ' hidden'; ?>>
							<?php if ( 'cash' === $mode ) : ?>
								<div class="spp-v2-payment-copy"><span class="spp-v2-badge spp-v2-badge--status spp-v2-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( self::status( $status ) ); ?></span><h3>دسترسی کامل به محتوای دوره</h3><p>پرداخت امن از مسیر استاندارد WooCommerce؛ سازگار با محصول ساده و متغیر.</p><?php if ( $d['cash_deadline'] ) : ?><div class="spp-v2-countdown" data-spp-v2-countdown="<?php echo esc_attr( $d['cash_deadline'] ); ?>"><span>زمان باقی‌مانده</span><strong data-days>۰۰</strong><small>روز</small><strong data-hours>۰۰</strong><small>ساعت</small><strong data-minutes>۰۰</strong><small>دقیقه</small></div><?php endif; ?></div>
								<?php if ( in_array( $status, array( 'open', 'limited', 'reserve' ), true ) ) : ?><div class="spp-v2-native-cart"><?php echo self::native_cart_form( $product, $d['cash_cta'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php else : ?><p class="spp-v2-unavailable"><?php echo esc_html( self::status( $status ) ); ?></p><?php endif; ?>
							<?php elseif ( 'installment' === $mode ) : ?>
								<div class="spp-v2-plan"><div><small>پیش‌پرداخت</small><strong><?php echo esc_html( $d['installment_downpayment'] ); ?></strong></div><div><small>تعداد اقساط</small><strong><?php echo esc_html( $d['installment_count'] ); ?></strong></div><div><small>مبلغ هر قسط</small><strong><?php echo esc_html( $d['installment_amount'] ); ?></strong></div><div><small>فاصله اقساط</small><strong><?php echo esc_html( $d['installment_interval'] ); ?></strong></div></div><p><?php echo esc_html( $d['installment_description'] ); ?></p><?php echo self::button( $d['installment_cta'], $d['installment_url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php elseif ( 'pre' === $mode ) : ?>
								<h3>رزرو جای شما در دوره</h3><?php if ( $d['pre_amount'] ) : ?><p class="spp-v2-plan-price"><?php echo esc_html( $d['pre_amount'] ); ?></p><?php endif; ?><p><?php echo esc_html( $d['pre_description'] ); ?></p><?php if ( $d['pre_deadline'] ) : ?><div class="spp-v2-countdown" data-spp-v2-countdown="<?php echo esc_attr( $d['pre_deadline'] ); ?>"><span>مهلت پیش‌ثبت‌نام</span><strong data-days>۰۰</strong><small>روز</small><strong data-hours>۰۰</strong><small>ساعت</small><strong data-minutes>۰۰</strong><small>دقیقه</small></div><?php endif; ?><?php echo self::button( $d['pre_cta'], $d['pre_url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php else : ?>
								<div class="spp-v2-plan"><div><small>حداقل تعداد</small><strong><?php echo esc_html( $d['group_min'] ); ?> نفر</strong></div><div><small>قیمت هر نفر</small><strong><?php echo esc_html( $d['group_price'] ); ?></strong></div></div><p><?php echo esc_html( $d['group_description'] ); ?></p><?php echo self::button( $d['group_cta'], $d['group_url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
						</div><?php endforeach; ?>
					</div>
				</div>
				<aside class="spp-v2-enrollment__card">
					<span>هزینه ثبت‌نام</span><strong class="spp-v2-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></strong><?php if ( $product->is_on_sale() ) : ?><span class="spp-v2-discount"><?php echo esc_html( self::discount_text( $product ) ); ?></span><?php endif; ?><ul><li><?php echo spp_icon( 'shield', 19 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>پرداخت امن</li><li><?php echo spp_icon( 'infinity', 19 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>دسترسی طبق شرایط دوره</li><li><?php echo spp_icon( 'support', 19 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>پشتیبانی سازان</li></ul><a class="spp-v2-btn spp-v2-btn--primary" href="#spp-v2-payment-<?php echo esc_attr( $first ); ?>">انتخاب روش ثبت‌نام</a>
				</aside>
			</div>
		</div></section>
		<?php if ( '1' === $data['intro']['show_mobile_bar'] ) : ?><div class="spp-v2-mobile-bar" data-spp-v2-mobile-bar dir="rtl"><div><small>هزینه دوره</small><strong><?php echo wp_kses_post( $product->get_price_html() ); ?></strong></div><a href="#spp-v2-enrollment"><?php echo esc_html( $data['intro']['primary_cta'] ?: 'ثبت‌نام در دوره' ); ?></a></div><?php endif; ?>
		<?php return (string) ob_get_clean();
	}

	private static function section_guarantee( $post_id, $data ) {
		$d = $data['guarantee'];
		if ( '1' !== $d['enabled'] || ( ! $d['title'] && ! $d['description'] ) ) { return ''; }
		ob_start(); ?>
		<section id="spp-v2-guarantee" class="spp-v2 spp-v2-section spp-v2-guarantee" data-spp-v2-section="guarantee" dir="rtl"><div class="spp-v2-container"><div class="spp-v2-guarantee__card">
			<div class="spp-v2-guarantee__visual"><?php echo self::image( $d['image'], 'medium', 'spp-v2-guarantee__image', $d['title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ( ! $d['image'] ) { echo spp_icon( 'shield', 72 ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div><div><?php if ( $d['eyebrow'] ) : ?><span class="spp-v2-eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span><?php endif; ?><h2><?php echo esc_html( $d['title'] ); ?></h2><?php if ( $d['duration'] ) : ?><strong class="spp-v2-guarantee__duration"><?php echo esc_html( $d['duration'] ); ?></strong><?php endif; ?><div class="spp-v2-richtext"><?php echo wp_kses_post( wpautop( $d['description'] ) ); ?></div><?php echo self::button( $d['cta_text'], $d['cta_url'], 'spp-v2-btn--ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div></div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_faq( $post_id, $data ) {
		$d = $data['faq'];
		$items = array_values( array_filter( $d['items'], function ( $item ) { return ! empty( $item['question'] ) && ! empty( $item['answer'] ); } ) );
		if ( ! $items ) { return ''; }
		ob_start(); ?>
		<section id="spp-v2-faq" class="spp-v2 spp-v2-section spp-v2-faq" data-spp-v2-section="faq" dir="rtl"><div class="spp-v2-container spp-v2-faq__layout">
			<div class="spp-v2-faq__intro"><?php echo self::section_header( $d['eyebrow'], $d['title'], 'پاسخ کوتاه و شفاف به پرسش‌های مهم پیش از ثبت‌نام.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><a class="spp-v2-btn spp-v2-btn--ghost" href="#spp-v2-enrollment">ثبت‌نام در دوره</a></div>
			<div class="spp-v2-faq__items"><?php foreach ( $items as $index => $item ) : $id = 'spp-v2-faq-answer-' . $index; ?><article><button type="button" data-spp-v2-accordion aria-expanded="false" aria-controls="<?php echo esc_attr( $id ); ?>"><span><?php echo esc_html( $item['question'] ); ?></span><i class="spp-v2-plus" aria-hidden="true"></i></button><div id="<?php echo esc_attr( $id ); ?>" hidden class="spp-v2-richtext"><?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?></div></article><?php endforeach; ?></div>
		</div></section>
		<?php return (string) ob_get_clean();
	}

	private static function section_final_cta( $post_id, $data ) {
		$product = self::product( $post_id );
		if ( ! $product ) { return ''; }
		ob_start(); ?>
		<section id="spp-v2-final-cta" class="spp-v2 spp-v2-section spp-v2-final-cta" data-spp-v2-section="final-cta" dir="rtl"><div class="spp-v2-container"><div class="spp-v2-final-cta__card"><span class="spp-v2-eyebrow">یک تصمیم تا شروع مسیر جدید</span><h2>آماده‌اید آموخته‌ها را به نتیجه واقعی تبدیل کنید؟</h2><p>همین حالا مسیر ثبت‌نام مناسب خود را انتخاب کنید.</p><div><strong class="spp-v2-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></strong><a class="spp-v2-btn spp-v2-btn--primary" href="#spp-v2-enrollment"><?php echo esc_html( $data['intro']['primary_cta'] ?: 'ثبت‌نام در دوره' ); ?><span aria-hidden="true">←</span></a></div></div></div></section>
		<?php return (string) ob_get_clean();
	}
}
