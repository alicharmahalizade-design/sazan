<?php
/**
 * Participants' stories carousel.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Stories extends HKL_Widget_Base {

	const ORDINALS = [ 'اول', 'دوم', 'سوم', 'چهارم', 'پنجم', 'ششم', 'هفتم', 'هشتم', 'نهم', 'دهم' ];

	protected static function slug() {
		return 'stories';
	}

	protected static function title() {
		return 'حکمرانی — تجربه شرکت‌کنندگان (کاروسل)';
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	protected static function fields() {
		return [
			'intro'  => [
				'label'    => 'عنوان بخش',
				'controls' => [
					'section_id'     => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'stories' ],
					'title'          => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'هیچ ماست‌فروشی نمی‌گوید ماست من ترش است' ],
					'text'           => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'ببینید این مفاهیم در کار و کسب‌های واقعی چگونه اجرا شده‌اند.' ],
					'autoplay'       => [ 'type' => 'switcher', 'label' => 'پخش خودکار اسلایدها', 'default' => 'yes' ],
					'interval'       => [ 'type' => 'text', 'label' => 'فاصله پخش خودکار (ثانیه)', 'default' => '5.6' ],
					'carousel_label' => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری کاروسل', 'default' => 'تجربه شرکت‌کنندگان' ],
				],
			],
			'labels' => [
				'label'    => 'برچسب‌ها',
				'controls' => [
					'result_label' => [ 'type' => 'text', 'label' => 'برچسب نتیجه', 'default' => 'تصمیم چه شد؟' ],
					'action_label' => [ 'type' => 'text', 'label' => 'برچسب اقدام', 'default' => 'چه اقدامی انجام دادیم؟' ],
					'before_label' => [ 'type' => 'text', 'label' => 'برچسب وضعیت قبل', 'default' => 'پیش از دوره چه بود؟' ],
					'play_icon'    => [ 'type' => 'icon', 'label' => 'آیکن دکمه پخش' ],
					'prev_text'    => [ 'type' => 'text', 'label' => 'نماد دکمه قبلی', 'default' => '→' ],
					'prev_icon'    => [ 'type' => 'icon', 'label' => 'آیکن دکمه قبلی' ],
					'next_text'    => [ 'type' => 'text', 'label' => 'نماد دکمه بعدی', 'default' => '←' ],
					'next_icon'    => [ 'type' => 'icon', 'label' => 'آیکن دکمه بعدی' ],
				],
			],
			'slides' => [
				'label'    => 'اسلایدها',
				'controls' => [
					'slides' => [
						'type'        => 'repeater',
						'label'       => 'تجربه‌ها',
						'title_field' => '{{{ name }}}',
						'fields'      => [
							'name'   => [ 'type' => 'text', 'label' => 'نام', 'default' => 'نام' ],
							'role'   => [ 'type' => 'text', 'label' => 'سمت', 'default' => '' ],
							'result' => [ 'type' => 'text', 'label' => 'نتیجه', 'default' => '' ],
							'action' => [ 'type' => 'textarea', 'label' => 'اقدام', 'rows' => 2, 'default' => '' ],
							'before' => [ 'type' => 'textarea', 'label' => 'وضعیت قبل', 'rows' => 2, 'default' => '' ],
							'cover'  => [ 'type' => 'media', 'label' => 'تصویر کاور (اختیاری)', 'default' => '' ],
							'video'  => [ 'type' => 'link', 'label' => 'لینک ویدیو (اختیاری)', 'default' => '', 'description' => 'با کلیک روی دکمه پخش در برگه جدید باز می‌شود.' ],
						],
						'default'     => [
							[ 'name' => 'امیر حسینی', 'role' => 'تجربه واقعی یک مدیر', 'result' => 'افزایش فروش در ۶ ماه', 'action' => 'بازطراحی فرایند فروش و آموزش تیم', 'before' => 'فروش ناپایدار و نبود فرایند روشن' ],
							[ 'name' => 'سارا شمس', 'role' => 'مدیر فروش مجموعه پوشاک', 'result' => 'رشد ۹۵٪ فروش در ۵ ماه', 'action' => 'طراحی تجربه مشتری و استانداردسازی پیگیری', 'before' => 'ریزش مشتری و پیگیری نامنظم سرنخ‌ها' ],
							[ 'name' => 'علی رضایی', 'role' => 'مدیر صنایع غذایی', 'result' => 'رشد ۳۳۰٪ فروش در ۸ ماه', 'action' => 'بازتعریف کانال‌های فروش و نظام مدیریت تیم', 'before' => 'وابستگی فروش به مدیر و نبود شاخص عملکرد' ],
						],
					],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'   => self::section_style( '.stories' ),
			'title'     => self::text_style( 'تیتر', '.stories .section-title h2' ),
			'text'      => self::text_style( 'توضیح', '.stories .section-title p' ),
			'grid'      => [
				'label'    => 'چیدمان اسلاید',
				'selector' => '.story-grid',
				'kinds'    => [ 'gap' ],
			],
			'video'     => self::box_style( 'کارت ویدیو', '.story-video' ),
			'play'      => [
				'label'    => 'دکمه پخش',
				'selector' => '.story-video button',
				'kinds'    => [ 'icon_color', 'bg', 'bg_hover', 'border_color', 'radius', 'box_size' ],
			],
			'play_icon' => self::icon_style( 'آیکن پخش', '.story-video button svg' ),
			'name'      => self::text_style( 'نام', '.story-video h3' ),
			'role'      => self::text_style( 'سمت', '.story-video p' ),
			'quote'     => self::box_style( 'کارت‌های پاسخ', '.quote' ),
			'q_label'   => self::text_style( 'برچسب پاسخ‌ها', '.quote small' ),
			'q_result'  => self::text_style( 'نتیجه', '.quote strong' ),
			'q_text'    => self::text_style( 'متن پاسخ‌ها', '.quote p' ),
			'controls'  => [
				'label'    => 'دکمه‌های قبلی و بعدی',
				'selector' => '.story-controls > button',
				'kinds'    => [ 'typography', 'color', 'color_hover', 'bg', 'bg_hover', 'border_color', 'radius', 'width', 'height' ],
			],
			'dots'      => [
				'label'    => 'نقطه‌ها',
				'selector' => '.story-dots button',
				'kinds'    => [ 'bg', 'width', 'height', 'radius' ],
			],
			'dot_active' => [
				'label'    => 'نقطه فعال',
				'selector' => '.story-dots button.is-active',
				'kinds'    => [ 'bg', 'width' ],
			],
		];
	}

	protected function render_html( array $s ) {
		$slides = self::rows( $s, 'slides' );
		$count  = count( $slides );
		$ords   = self::ORDINALS;
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="stories dark-section section-pad">
      <div class="container"><div class="section-title light"><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p></div>
        <div class="story-carousel"<?php echo 'yes' === self::v( $s, 'autoplay' ) ? ( '5.6' !== self::v( $s, 'interval' ) && (float) self::v( $s, 'interval' ) > 0 ? ' data-interval="' . esc_attr( (string) (int) ( (float) self::v( $s, 'interval' ) * 1000 ) ) . '"' : '' ) : ' data-autoplay="no"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-roledescription="کاروسل" aria-label="<?php echo self::a( self::v( $s, 'carousel_label' ) ); ?>">
          <div class="story-slides" aria-live="polite">
<?php
		foreach ( $slides as $i => $slide ) :
			$n     = $i + 1;
			$video = self::arr( $slide, 'video' );
			$video = isset( $video['url'] ) ? $video['url'] : '';
			$cover = self::media_url( $slide['cover'] ?? '' );
			?>
            <div class="story-grid story-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" data-story-slide aria-label="<?php echo self::a( 'تجربه ' . self::fa_digits( $n ) . ' از ' . self::fa_digits( $count ) ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
              <article class="story-video<?php echo $n > 1 ? ' story-video-' . (int) $n : ''; ?>"<?php echo $cover ? ' style="background:linear-gradient(180deg,transparent 35%,rgba(2,13,25,.85)),url(&quot;' . esc_url( $cover ) . '&quot;) center/cover no-repeat"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><button aria-label="<?php echo self::a( 'پخش تجربه ' . self::v( $slide, 'name' ) ); ?>"<?php echo $video ? ' data-video="' . esc_url( $video ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::icon( $s['play_icon'] ?? [], '<svg><use href="#i-play"/></svg>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button><h3><?php echo self::t( self::v( $slide, 'name' ) ); ?></h3><p><?php echo self::t( self::v( $slide, 'role' ) ); ?></p></article>
              <article class="quote"><small><?php echo self::t( self::v( $s, 'result_label' ) ); ?></small><strong><?php echo self::t( self::v( $slide, 'result' ) ); ?></strong></article>
              <article class="quote"><small><?php echo self::t( self::v( $s, 'action_label' ) ); ?></small><p><?php echo self::t( self::v( $slide, 'action' ) ); ?></p></article>
              <article class="quote"><small><?php echo self::t( self::v( $s, 'before_label' ) ); ?></small><p><?php echo self::t( self::v( $slide, 'before' ) ); ?></p></article>
            </div>
<?php endforeach; ?>
          </div>
          <div class="story-controls">
            <button type="button" data-story-prev aria-label="تجربه قبلی"><?php echo self::icon( $s['prev_icon'] ?? [], self::t( self::v( $s, 'prev_text' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
            <div class="story-dots" role="tablist" aria-label="انتخاب تجربه">
<?php foreach ( $slides as $i => $slide ) : ?>
              <button type="button"<?php echo 0 === $i ? ' class="is-active"' : ''; ?> data-story-dot="<?php echo (int) $i; ?>" aria-label="<?php echo self::a( 'تجربه ' . ( isset( $ords[ $i ] ) ? $ords[ $i ] : self::fa_digits( $i + 1 ) ) ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"></button>
<?php endforeach; ?>
            </div>
            <button type="button" data-story-next aria-label="تجربه بعدی"><?php echo self::icon( $s['next_icon'] ?? [], self::t( self::v( $s, 'next_text' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
          </div>
        </div>
      </div>
    </section>
		<?php
	}
}
