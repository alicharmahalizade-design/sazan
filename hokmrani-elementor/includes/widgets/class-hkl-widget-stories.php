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
					'carousel_label' => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری کاروسل', 'default' => 'تجربه شرکت‌کنندگان' ],
				],
			],
			'labels' => [
				'label'    => 'برچسب‌ها',
				'controls' => [
					'result_label' => [ 'type' => 'text', 'label' => 'برچسب نتیجه', 'default' => 'تصمیم چه شد؟' ],
					'action_label' => [ 'type' => 'text', 'label' => 'برچسب اقدام', 'default' => 'چه اقدامی انجام دادیم؟' ],
					'before_label' => [ 'type' => 'text', 'label' => 'برچسب وضعیت قبل', 'default' => 'پیش از دوره چه بود؟' ],
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

	protected function render_html( array $s ) {
		$slides = self::rows( $s, 'slides' );
		$count  = count( $slides );
		$ords   = self::ORDINALS;
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="stories dark-section section-pad">
      <div class="container"><div class="section-title light"><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p></div>
        <div class="story-carousel" aria-roledescription="کاروسل" aria-label="<?php echo self::a( self::v( $s, 'carousel_label' ) ); ?>">
          <div class="story-slides" aria-live="polite">
<?php
		foreach ( $slides as $i => $slide ) :
			$n     = $i + 1;
			$video = self::v( $slide, 'video' );
			?>
            <div class="story-grid story-slide<?php echo 0 === $i ? ' is-active' : ''; ?>" data-story-slide aria-label="<?php echo self::a( 'تجربه ' . self::fa_digits( $n ) . ' از ' . self::fa_digits( $count ) ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
              <article class="story-video<?php echo $n > 1 ? ' story-video-' . (int) $n : ''; ?>"><button aria-label="<?php echo self::a( 'پخش تجربه ' . self::v( $slide, 'name' ) ); ?>"<?php echo $video ? ' data-video="' . esc_url( $video ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><svg><use href="#i-play"/></svg></button><h3><?php echo self::t( self::v( $slide, 'name' ) ); ?></h3><p><?php echo self::t( self::v( $slide, 'role' ) ); ?></p></article>
              <article class="quote"><small><?php echo self::t( self::v( $s, 'result_label' ) ); ?></small><strong><?php echo self::t( self::v( $slide, 'result' ) ); ?></strong></article>
              <article class="quote"><small><?php echo self::t( self::v( $s, 'action_label' ) ); ?></small><p><?php echo self::t( self::v( $slide, 'action' ) ); ?></p></article>
              <article class="quote"><small><?php echo self::t( self::v( $s, 'before_label' ) ); ?></small><p><?php echo self::t( self::v( $slide, 'before' ) ); ?></p></article>
            </div>
<?php endforeach; ?>
          </div>
          <div class="story-controls">
            <button type="button" data-story-prev aria-label="تجربه قبلی">→</button>
            <div class="story-dots" role="tablist" aria-label="انتخاب تجربه">
<?php foreach ( $slides as $i => $slide ) : ?>
              <button type="button"<?php echo 0 === $i ? ' class="is-active"' : ''; ?> data-story-dot="<?php echo (int) $i; ?>" aria-label="<?php echo self::a( 'تجربه ' . ( isset( $ords[ $i ] ) ? $ords[ $i ] : self::fa_digits( $i + 1 ) ) ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"></button>
<?php endforeach; ?>
            </div>
            <button type="button" data-story-next aria-label="تجربه بعدی">←</button>
          </div>
        </div>
      </div>
    </section>
		<?php
	}
}
