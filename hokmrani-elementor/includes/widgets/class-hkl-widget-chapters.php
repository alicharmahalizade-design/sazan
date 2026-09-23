<?php
/**
 * Course chapters: roadmap wheel + accordion.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Chapters extends HKL_Widget_Base {

	protected static function slug() {
		return 'chapters';
	}

	protected static function title() {
		return 'حکمرانی — سرفصل‌ها';
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	protected static function fields() {
		return [
			'visual'  => [
				'label'    => 'تصویر نقشه مسیر',
				'controls' => [
					'section_id'   => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'chapters' ],
					'visual_label' => [ 'type' => 'text', 'label' => 'برچسب تصویر', 'default' => 'نقشه مسیر یادگیری' ],
					'image'        => [ 'type' => 'media', 'label' => 'تصویر', 'default' => 'chapter-wheel.png' ],
					'image_alt'    => [ 'type' => 'textarea', 'label' => 'متن جایگزین تصویر', 'rows' => 2, 'default' => 'هفت محور کلیدی دوره شامل عملکرد تیم فروش، تجربه مشتری، مذاکره، ارتباط و اعتماد، شناخت مشتری، مزیت رقابتی و رشد فروش' ],
				],
			],
			'heading' => [
				'label'    => 'عنوان',
				'controls' => [
					'eyebrow' => [ 'type' => 'text', 'label' => 'برچسب بالا', 'default' => 'نقشه راه رشد و توسعه فروش' ],
					'title'   => [ 'type' => 'text', 'label' => 'تیتر', 'default' => '۷ سرفصل کلیدی دوره' ],
					'text'    => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'هر سرفصل را باز کنید و ببینید دقیقاً چه چیزی یاد می‌گیرید.' ],
				],
			],
			'items'   => [
				'label'    => 'سرفصل‌ها',
				'controls' => [
					'items' => [
						'type'        => 'repeater',
						'label'       => 'سرفصل‌ها',
						'title_field' => '{{{ number }}} - {{{ title }}}',
						'fields'      => [
							'number' => [ 'type' => 'text', 'label' => 'شماره', 'default' => '۰۱' ],
							'title'  => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'سرفصل' ],
							'text'   => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => '' ],
						],
						'default'     => [
							[ 'number' => '۰۱', 'title' => 'عملکرد تیم فروش', 'text' => 'ساخت تیم اجرایی نتیجه‌محور، تعریف نقش‌ها، شاخص‌های عملکرد و ایجاد مسئولیت‌پذیری پایدار در تیم فروش.' ],
							[ 'number' => '۰۲', 'title' => 'تجربه مشتری', 'text' => 'طراحی یک تجربه منسجم و به‌یادماندنی که اعتماد مشتری را افزایش دهد و او را به خرید دوباره ترغیب کند.' ],
							[ 'number' => '۰۳', 'title' => 'مذاکره حرفه‌ای', 'text' => 'شناخت منافع طرفین، مدیریت اعتراض‌ها و نهایی‌کردن معامله بدون وابستگی دائمی به تخفیف.' ],
							[ 'number' => '۰۴', 'title' => 'ارتباط و اعتماد', 'text' => 'ساخت اعتبار، ایجاد گفت‌وگوی مؤثر و تبدیل ارتباط‌های پراکنده به یک فرایند اعتمادساز.' ],
							[ 'number' => '۰۵', 'title' => 'شناخت مشتری', 'text' => 'شناسایی مشتری واقعی، کشف نیازهای پنهان و بخش‌بندی بازار بر اساس رفتار و ارزش اقتصادی.' ],
							[ 'number' => '۰۶', 'title' => 'مزیت رقابتی', 'text' => 'ساخت پیشنهادی متمایز و قابل دفاع که پاسخ روشنی به این سؤال بدهد: چرا مشتری باید ما را انتخاب کند؟' ],
							[ 'number' => '۰۷', 'title' => 'رشد فروش', 'text' => 'تشخیص گلوگاه‌های اصلی فروش و طراحی یک مسیر سنجش‌پذیر برای رشد پایدار و سودآور.' ],
						],
					],
					'first_open' => [ 'type' => 'switcher', 'label' => 'سرفصل اول باز باشد', 'default' => 'yes' ],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		$image = self::media_url( $s['image'] ?? '' );
		list( $w, $h ) = self::media_size( $s['image'] ?? '', 1536, 1536 );
		$open = 'yes' === self::v( $s, 'first_open' );
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="chapters dark-section section-pad">
      <div class="container chapters-showcase">
        <figure class="chapters-visual">
          <span><?php echo self::t( self::v( $s, 'visual_label' ) ); ?></span>
          <?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo self::a( self::v( $s, 'image_alt' ) ); ?>" width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>" loading="lazy"><?php endif; ?>

        </figure>
        <div class="chapters-panel">
          <header class="chapters-heading"><span aria-hidden="true">▱</span><div><small><?php echo self::t( self::v( $s, 'eyebrow' ) ); ?></small><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p></div></header>
          <div class="chapter-accordion">
<?php foreach ( self::rows( $s, 'items' ) as $i => $item ) : ?>
            <details<?php echo ( $open && 0 === $i ) ? ' open' : ''; ?>><summary><b><?php echo self::t( self::v( $item, 'number' ) ); ?></b><span><?php echo self::t( self::v( $item, 'title' ) ); ?></span><i aria-hidden="true"></i></summary><div><p><?php echo self::t( self::v( $item, 'text' ) ); ?></p></div></details>
<?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
		<?php
	}
}
