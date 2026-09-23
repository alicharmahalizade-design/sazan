<?php
/**
 * "Verify it yourself" banner + participants' result cards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Results extends HKL_Widget_Base {

	protected static function slug() {
		return 'results';
	}

	protected static function title() {
		return 'حکمرانی — نتایج شرکت‌کنندگان';
	}

	public function get_icon() {
		return 'eicon-testimonial-carousel';
	}

	protected static function fields() {
		return [
			'banner' => [
				'label'    => 'بنر سؤال',
				'controls' => [
					'section_id'      => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'results' ],
					'banner_image'    => [ 'type' => 'media', 'label' => 'تصویر پس‌زمینه بنر', 'default' => 'question-banner.jpg' ],
					'question_intro'  => [ 'type' => 'text', 'label' => 'متن بالای سؤال', 'default' => 'اما احتمالاً اولین سؤال شما این است:' ],
					'question_title'  => [ 'type' => 'rich', 'label' => 'سؤال', 'default' => '<b><span>۳۰ تا ۳۰۰</span> درصد؟ چطور؟</b> برای چه کار و کسبی؟ مدرکش چیست؟' ],
					'question_strong' => [ 'type' => 'text', 'label' => 'متن پایانی', 'default' => 'پس اجازه بدهید قبل از هرچیز، خودتان راستی‌آزمایی کنید' ],
				],
			],
			'cards'  => [
				'label'    => 'کارت‌های نتیجه',
				'controls' => [
					'cards' => [
						'type'        => 'repeater',
						'label'       => 'کارت‌ها',
						'title_field' => '{{{ title }}}',
						'fields'      => [
							'title'        => [ 'type' => 'text', 'label' => 'عنوان (سمت)', 'default' => 'مدیرعامل' ],
							'before_label' => [ 'type' => 'text', 'label' => 'برچسب قبل', 'default' => 'قبل از دوره:' ],
							'before_value' => [ 'type' => 'text', 'label' => 'مقدار قبل', 'default' => 'فروش هفتگی ۴ میلیارد' ],
							'after_label'  => [ 'type' => 'text', 'label' => 'برچسب بعد', 'default' => 'بعد از دوره:' ],
							'after_value'  => [ 'type' => 'text', 'label' => 'مقدار بعد', 'default' => 'فروش هفتگی ۶ میلیارد' ],
							'video'        => [ 'type' => 'link', 'label' => 'لینک ویدیو (اختیاری)', 'default' => '', 'description' => 'با کلیک روی دکمه پخش در برگه جدید باز می‌شود.' ],
						],
						'default'     => [
							[ 'title' => 'مدیرعامل تابلو‌سازی' ],
							[ 'title' => 'مدیر فروش پخش مواد غذایی' ],
							[ 'title' => 'صاحب پخش مواد غذایی' ],
						],
					],
				],
			],
			'intro'  => [
				'label'    => 'متن معرفی',
				'controls' => [
					'intro_line1' => [ 'type' => 'rich', 'label' => 'تیتر - خط اول', 'default' => 'این عدد را از <em>ما</em> نپذیرید' ],
					'intro_line2' => [ 'type' => 'rich', 'label' => 'تیتر - خط دوم', 'default' => 'از شرکت‌کنندگان <b>قبلی بپرسید</b>' ],
					'intro_text'  => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'اینجا به‌جای تعریف کردن از خودمان، نتایج را از زبان مدیرانی می‌شنوید که در این مسیر بوده‌اند.' ],
					'intro_link_text' => [ 'type' => 'text', 'label' => 'متن لینک', 'default' => 'مشاهده نتایج شرکت‌کنندگان' ],
					'intro_link'  => [ 'type' => 'link', 'label' => 'لینک', 'default' => '#stories' ],
					'next_label'  => [ 'type' => 'text', 'label' => 'عنوان دکمه بعدی (دسترسی‌پذیری)', 'default' => 'نتیجه بعدی' ],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		$banner = self::media_url( $s['banner_image'] ?? '' );
		list( $bw, $bh ) = self::media_size( $s['banner_image'] ?? '', 1500, 193 );
		?>
<div<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="version-stage">
        <section class="coded-question-banner" aria-labelledby="coded-question-title">
          <?php if ( $banner ) : ?><img src="<?php echo esc_url( $banner ); ?>" alt="" width="<?php echo (int) $bw; ?>" height="<?php echo (int) $bh; ?>" aria-hidden="true"><?php endif; ?>

          <div class="coded-question-copy">
            <p><?php echo self::t( self::v( $s, 'question_intro' ) ); ?></p>
            <h2 id="coded-question-title"><?php echo self::r( self::v( $s, 'question_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
            <strong><?php echo self::t( self::v( $s, 'question_strong' ) ); ?></strong>
          </div>
        </section>
        <section class="coded-results" aria-labelledby="coded-results-title">
          <div class="coded-results-cards">
<?php foreach ( self::rows( $s, 'cards' ) as $card ) : ?>
            <article class="coded-result-card">
              <button class="coded-result-video" type="button" aria-label="<?php echo self::a( 'پخش تجربه ' . self::v( $card, 'title' ) ); ?>"<?php echo self::v( $card, 'video' ) ? ' data-video="' . esc_url( self::v( $card, 'video' ) ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span aria-hidden="true">▶</span></button>
              <div class="coded-result-stats"><p><b><?php echo self::t( self::v( $card, 'before_label' ) ); ?></b><strong><?php echo self::t( self::v( $card, 'before_value' ) ); ?></strong></p><p><b><?php echo self::t( self::v( $card, 'after_label' ) ); ?></b><strong><?php echo self::t( self::v( $card, 'after_value' ) ); ?></strong></p></div>
              <h3><?php echo self::t( self::v( $card, 'title' ) ); ?></h3>
            </article>
<?php endforeach; ?>
          </div>
          <div class="coded-results-intro">
            <h2 id="coded-results-title"><span class="result-title-line"><?php echo self::r( self::v( $s, 'intro_line1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="result-title-line"><?php echo self::r( self::v( $s, 'intro_line2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></h2>
            <p><?php echo self::t( self::v( $s, 'intro_text' ) ); ?></p>
            <a href="<?php echo self::u( self::v( $s, 'intro_link' ) ); ?>"><?php echo self::t( self::v( $s, 'intro_link_text' ) ); ?> <i aria-hidden="true">▷</i></a>
          </div>
          <button class="coded-results-next" type="button" aria-label="<?php echo self::a( self::v( $s, 'next_label' ) ); ?>">‹</button>
        </section>
    </div>
		<?php
	}
}
