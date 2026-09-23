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
					'show_banner'     => [ 'type' => 'switcher', 'label' => 'نمایش بنر سؤال', 'default' => 'yes' ],
					'banner_image'    => [ 'type' => 'media', 'label' => 'تصویر پس‌زمینه بنر', 'default' => 'question-banner.jpg' ],
					'question_intro'  => [ 'type' => 'text', 'label' => 'متن بالای سؤال', 'default' => 'اما احتمالاً اولین سؤال شما این است:' ],
					'question_title'  => [ 'type' => 'rich', 'label' => 'سؤال', 'default' => '<b><span>۳۰ تا ۳۰۰</span> درصد؟ چطور؟</b> برای چه کار و کسبی؟ مدرکش چیست؟' ],
					'question_strong' => [ 'type' => 'text', 'label' => 'متن پایانی', 'default' => 'پس اجازه بدهید قبل از هرچیز، خودتان راستی‌آزمایی کنید' ],
				],
			],
			'cards'  => [
				'label'    => 'کارت‌های نتیجه',
				'controls' => [
					'cards'     => [
						'type'        => 'repeater',
						'label'       => 'کارت‌ها',
						'title_field' => '{{{ title }}}',
						'fields'      => [
							'title'        => [ 'type' => 'text', 'label' => 'عنوان (سمت)', 'default' => 'مدیرعامل' ],
							'before_label' => [ 'type' => 'text', 'label' => 'برچسب قبل', 'default' => 'قبل از دوره:' ],
							'before_value' => [ 'type' => 'text', 'label' => 'مقدار قبل', 'default' => 'فروش هفتگی ۴ میلیارد' ],
							'after_label'  => [ 'type' => 'text', 'label' => 'برچسب بعد', 'default' => 'بعد از دوره:' ],
							'after_value'  => [ 'type' => 'text', 'label' => 'مقدار بعد', 'default' => 'فروش هفتگی ۶ میلیارد' ],
							'cover'        => [ 'type' => 'media', 'label' => 'تصویر کاور ویدیو (اختیاری)', 'default' => '' ],
							'video'        => [ 'type' => 'link', 'label' => 'لینک ویدیو (اختیاری)', 'default' => '', 'description' => 'با کلیک روی دکمه پخش در برگه جدید باز می‌شود.' ],
						],
						'default'     => [
							[ 'title' => 'مدیرعامل تابلو‌سازی' ],
							[ 'title' => 'مدیر فروش پخش مواد غذایی' ],
							[ 'title' => 'صاحب پخش مواد غذایی' ],
						],
					],
					'play_icon' => [ 'type' => 'icon', 'label' => 'آیکن پخش' ],
					'play_text' => [ 'type' => 'text', 'label' => 'نماد پخش (متنی)', 'default' => '▶' ],
				],
			],
			'intro'  => [
				'label'    => 'متن معرفی',
				'controls' => [
					'intro_line1'     => [ 'type' => 'rich', 'label' => 'تیتر - خط اول', 'default' => 'این عدد را از <em>ما</em> نپذیرید' ],
					'intro_line2'     => [ 'type' => 'rich', 'label' => 'تیتر - خط دوم', 'default' => 'از شرکت‌کنندگان <b>قبلی بپرسید</b>' ],
					'intro_text'      => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'اینجا به‌جای تعریف کردن از خودمان، نتایج را از زبان مدیرانی می‌شنوید که در این مسیر بوده‌اند.' ],
					'intro_link_text' => [ 'type' => 'text', 'label' => 'متن لینک', 'default' => 'مشاهده نتایج شرکت‌کنندگان' ],
					'intro_link'      => [ 'type' => 'link', 'label' => 'لینک', 'default' => '#stories' ],
					'link_arrow'      => [ 'type' => 'text', 'label' => 'نماد لینک', 'default' => '▷' ],
					'link_icon'       => [ 'type' => 'icon', 'label' => 'آیکن لینک' ],
					'show_next'       => [ 'type' => 'switcher', 'label' => 'نمایش دکمه بعدی', 'default' => 'yes' ],
					'next_text'       => [ 'type' => 'text', 'label' => 'نماد دکمه بعدی', 'default' => '‹' ],
					'next_label'      => [ 'type' => 'text', 'label' => 'عنوان دکمه بعدی (دسترسی‌پذیری)', 'default' => 'نتیجه بعدی' ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'      => self::section_style( '.version-stage' ),
			'banner'       => [
				'label'    => 'بنر سؤال',
				'selector' => '.coded-question-banner',
				'kinds'    => [ 'background', 'min_height', 'padding', 'radius', 'hide' ],
			],
			'banner_image' => [
				'label'    => 'تصویر بنر',
				'selector' => '.coded-question-banner > img',
				'kinds'    => [ 'opacity', 'hide' ],
			],
			'q_intro'      => self::text_style( 'متن بالای سؤال', '.coded-question-copy p' ),
			'q_title'      => self::text_style( 'سؤال', '.coded-question-copy h2' ),
			'q_bold'       => [
				'label'    => 'بخش پررنگ سؤال',
				'selector' => '.coded-question-copy h2 b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'q_hl'         => [
				'label'    => 'عدد برجسته سؤال',
				'selector' => '.coded-question-copy h2 b span',
				'kinds'    => [ 'typography', 'color', 'bg', 'radius', 'padding' ],
			],
			'q_strong'     => self::text_style( 'متن پایانی بنر', '.coded-question-copy strong' ),
			'results'      => [
				'label'    => 'بخش کارت‌ها',
				'selector' => '.coded-results',
				'kinds'    => [ 'background', 'padding', 'gap', 'radius' ],
			],
			'card'         => self::box_style( 'کارت نتیجه', '.coded-result-card', [ 'width' ] ),
			'video'        => [
				'label'    => 'کادر ویدیو',
				'selector' => '.coded-result-video',
				'kinds'    => [ 'background', 'height', 'border_color', 'radius' ],
			],
			'play'         => [
				'label'    => 'دکمه پخش',
				'selector' => '.coded-result-video span',
				'kinds'    => [ 'typography', 'color', 'bg', 'border_color', 'radius', 'width', 'height', 'opacity' ],
			],
			'play_icon'    => self::icon_style( 'آیکن پخش', '.coded-result-video span svg' ),
			'stats'        => [
				'label'    => 'کادر آمار',
				'selector' => '.coded-result-stats',
				'kinds'    => [ 'background', 'padding', 'gap' ],
			],
			'stat_label'   => [
				'label'    => 'برچسب آمار',
				'selector' => '.coded-result-stats b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'stat_before'  => [
				'label'    => 'مقدار قبل',
				'selector' => '.coded-result-stats p:first-child strong',
				'kinds'    => [ 'typography', 'color' ],
			],
			'stat_after'   => [
				'label'    => 'مقدار بعد',
				'selector' => '.coded-result-stats p:last-child strong',
				'kinds'    => [ 'typography', 'color' ],
			],
			'card_title'   => self::text_style( 'عنوان کارت', '.coded-result-card h3', [ 'bg', 'padding' ] ),
			'intro'        => [
				'label'    => 'کادر معرفی',
				'selector' => '.coded-results-intro',
				'kinds'    => [ 'background', 'padding', 'radius', 'align' ],
			],
			'intro_title'  => self::text_style( 'تیتر معرفی', '.coded-results-intro h2' ),
			'intro_hl'     => [
				'label'    => 'بخش‌های رنگی تیتر معرفی',
				'selector' => '.coded-results-intro h2 em, .coded-results-intro h2 b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'intro_text'   => self::text_style( 'توضیح معرفی', '.coded-results-intro p' ),
			'intro_link'   => self::button_style( 'لینک معرفی', '.coded-results-intro a' ),
			'next'         => [
				'label'    => 'دکمه بعدی',
				'selector' => '.coded-results-next',
				'kinds'    => [ 'typography', 'color', 'bg', 'bg_hover', 'border_color', 'radius', 'width', 'height' ],
			],
		];
	}

	protected function render_html( array $s ) {
		$banner = self::media_url( $s['banner_image'] ?? '' );
		list( $bw, $bh ) = self::media_size( $s['banner_image'] ?? '', 1500, 193 );
		$play  = self::icon( $s['play_icon'] ?? [], self::t( self::v( $s, 'play_text' ) ) );
		$arrow = self::icon( $s['link_icon'] ?? [], self::t( self::v( $s, 'link_arrow' ) ) );
		?>
<div<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="version-stage">
<?php if ( 'yes' === self::v( $s, 'show_banner' ) ) : ?>
        <section class="coded-question-banner" aria-labelledby="coded-question-title">
          <?php if ( $banner ) : ?><img src="<?php echo esc_url( $banner ); ?>" alt="" width="<?php echo (int) $bw; ?>" height="<?php echo (int) $bh; ?>" aria-hidden="true"><?php endif; ?>

          <div class="coded-question-copy">
            <p><?php echo self::t( self::v( $s, 'question_intro' ) ); ?></p>
            <h2 id="coded-question-title"><?php echo self::r( self::v( $s, 'question_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
            <strong><?php echo self::t( self::v( $s, 'question_strong' ) ); ?></strong>
          </div>
        </section>
<?php endif; ?>
        <section class="coded-results" aria-labelledby="coded-results-title">
          <div class="coded-results-cards">
<?php
		foreach ( self::rows( $s, 'cards' ) as $card ) :
			$video = self::arr( $card, 'video' );
			$cover = self::media_url( $card['cover'] ?? '' );
			?>
            <article class="coded-result-card">
              <button class="coded-result-video" type="button" aria-label="<?php echo self::a( 'پخش تجربه ' . self::v( $card, 'title' ) ); ?>"<?php echo ! empty( $video['url'] ) ? ' data-video="' . esc_url( $video['url'] ) . '"' : ''; ?><?php echo $cover ? ' style="background:#000 url(&quot;' . esc_url( $cover ) . '&quot;) center/cover no-repeat"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span aria-hidden="true"><?php echo $play; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></button>
              <div class="coded-result-stats"><p><b><?php echo self::t( self::v( $card, 'before_label' ) ); ?></b><strong><?php echo self::t( self::v( $card, 'before_value' ) ); ?></strong></p><p><b><?php echo self::t( self::v( $card, 'after_label' ) ); ?></b><strong><?php echo self::t( self::v( $card, 'after_value' ) ); ?></strong></p></div>
              <h3><?php echo self::t( self::v( $card, 'title' ) ); ?></h3>
            </article>
<?php endforeach; ?>
          </div>
          <div class="coded-results-intro">
            <h2 id="coded-results-title"><span class="result-title-line"><?php echo self::r( self::v( $s, 'intro_line1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><span class="result-title-line"><?php echo self::r( self::v( $s, 'intro_line2' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></h2>
            <p><?php echo self::t( self::v( $s, 'intro_text' ) ); ?></p>
            <a<?php echo self::href( self::arr( $s, 'intro_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $s, 'intro_link_text' ) ); ?> <i aria-hidden="true"><?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></i></a>
          </div>
<?php if ( 'yes' === self::v( $s, 'show_next' ) ) : ?>
          <button class="coded-results-next" type="button" aria-label="<?php echo self::a( self::v( $s, 'next_label' ) ); ?>"><?php echo self::t( self::v( $s, 'next_text' ) ); ?></button>
<?php endif; ?>
        </section>
    </div>
		<?php
	}
}
