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
					'show_visual'  => [ 'type' => 'switcher', 'label' => 'نمایش تصویر', 'default' => 'yes' ],
					'visual_label' => [ 'type' => 'text', 'label' => 'برچسب تصویر', 'default' => 'نقشه مسیر یادگیری' ],
					'image'        => [ 'type' => 'media', 'label' => 'تصویر', 'default' => 'chapter-wheel.png' ],
					'image_alt'    => [ 'type' => 'textarea', 'label' => 'متن جایگزین تصویر', 'rows' => 2, 'default' => 'هفت محور کلیدی دوره شامل عملکرد تیم فروش، تجربه مشتری، مذاکره، ارتباط و اعتماد، شناخت مشتری، مزیت رقابتی و رشد فروش' ],
				],
			],
			'heading' => [
				'label'    => 'عنوان',
				'controls' => [
					'heading_icon'   => [ 'type' => 'text', 'label' => 'نماد کنار عنوان', 'default' => '▱' ],
					'heading_icon_c' => [ 'type' => 'icon', 'label' => 'آیکن کنار عنوان' ],
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

	protected static function styles() {
		return [
			'section'      => self::section_style( '.chapters' ),
			'layout'       => [
				'label'    => 'چیدمان',
				'selector' => '.chapters-showcase',
				'kinds'    => [ 'gap', 'max_width' ],
			],
			'visual'       => self::box_style( 'کادر تصویر', '.chapters-visual' ),
			'visual_label' => self::text_style( 'برچسب تصویر', '.chapters-visual > span', [ 'bg', 'radius', 'padding', 'hide' ] ),
			'image'        => [
				'label'    => 'تصویر',
				'selector' => '.chapters-visual img',
				'kinds'    => [ 'width', 'max_width', 'radius', 'opacity' ],
			],
			'panel'        => self::box_style( 'کادر سرفصل‌ها', '.chapters-panel' ),
			'heading_icon' => [
				'label'    => 'نماد کنار عنوان',
				'selector' => '.chapters-heading > span',
				'kinds'    => [ 'typography', 'icon_color', 'bg', 'radius', 'width', 'height', 'hide' ],
			],
			'heading_svg'  => self::icon_style( 'آیکن کنار عنوان', '.chapters-heading > span svg' ),
			'eyebrow'      => self::text_style( 'برچسب بالا', '.chapters-heading small' ),
			'title'        => self::text_style( 'تیتر', '.chapters-heading h2' ),
			'text'         => self::text_style( 'توضیح', '.chapters-heading p' ),
			'list'         => [
				'label'    => 'فهرست سرفصل‌ها',
				'selector' => '.chapter-accordion',
				'kinds'    => [ 'gap' ],
			],
			'item'         => self::box_style( 'سرفصل', '.chapter-accordion details', [ 'border_color_hover' ] ),
			'item_open'    => [
				'label'    => 'سرفصل باز',
				'selector' => '.chapter-accordion details[open]',
				'kinds'    => [ 'background', 'border_color', 'shadow' ],
			],
			'summary'      => [
				'label'    => 'سربرگ سرفصل',
				'selector' => '.chapter-accordion summary',
				'kinds'    => [ 'padding', 'gap', 'min_height' ],
			],
			'number'       => [
				'label'    => 'شماره',
				'selector' => '.chapter-accordion summary b',
				'kinds'    => [ 'typography', 'color', 'bg', 'radius', 'width', 'height' ],
			],
			'item_title'   => [
				'label'    => 'عنوان سرفصل',
				'selector' => '.chapter-accordion summary span',
				'kinds'    => [ 'typography', 'color', 'color_hover' ],
			],
			'toggle'       => [
				'label'    => 'دکمه باز و بسته',
				'selector' => '.chapter-accordion summary i',
				'kinds'    => [ 'icon_color', 'bg', 'border_color', 'radius', 'width', 'height', 'hide' ],
			],
			'answer'       => self::text_style( 'توضیح سرفصل', '.chapter-accordion details > div p', [ 'padding' ] ),
		];
	}

	protected function render_html( array $s ) {
		$image = self::media_url( $s['image'] ?? '' );
		list( $w, $h ) = self::media_size( $s['image'] ?? '', 1536, 1536 );
		$open = 'yes' === self::v( $s, 'first_open' );
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="chapters dark-section section-pad">
      <div class="container chapters-showcase">
<?php if ( 'yes' === self::v( $s, 'show_visual' ) ) : ?>
        <figure class="chapters-visual">
          <span><?php echo self::t( self::v( $s, 'visual_label' ) ); ?></span>
          <?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo self::a( self::v( $s, 'image_alt' ) ); ?>" width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>" loading="lazy"><?php endif; ?>

        </figure>
<?php endif; ?>
        <div class="chapters-panel">
          <header class="chapters-heading"><span aria-hidden="true"><?php echo self::icon( $s['heading_icon_c'] ?? [], self::t( self::v( $s, 'heading_icon' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><small><?php echo self::t( self::v( $s, 'eyebrow' ) ); ?></small><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p></div></header>
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
