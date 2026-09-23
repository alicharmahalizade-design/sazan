<?php
/**
 * Hero: photo, headline with growth numbers and the offer card.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Hero extends HKL_Widget_Base {

	const SPRITES = [
		'shield'   => 'سپر (تضمین)',
		'support'  => 'پشتیبانی',
		'focus'    => 'تمرکز',
		'chart'    => 'نمودار',
		'calendar' => 'تقویم',
		'logo'     => 'ستون‌ها',
	];

	protected static function slug() {
		return 'hero';
	}

	protected static function title() {
		return 'حکمرانی — هیرو (بخش اول)';
	}

	public function get_icon() {
		return 'eicon-banner';
	}

	protected static function fields() {
		return [
			'photo' => [
				'label'    => 'تصویر',
				'controls' => [
					'photo'       => [ 'type' => 'media', 'label' => 'تصویر مدرس', 'default' => '', 'description' => 'خالی بماند تا تصویر اصلی طرح نمایش داده شود. اندازه و موقعیت تصویر از تب استایل قابل تنظیم است.' ],
					'photo_label' => [ 'type' => 'text', 'label' => 'توضیح تصویر (دسترسی‌پذیری)', 'default' => 'مدرس برنامه در دفتر کار' ],
				],
			],
			'copy'  => [
				'label'    => 'تیتر',
				'controls' => [
					'brand'           => [ 'type' => 'text', 'label' => 'نام برنامه', 'default' => 'حکمرانی' ],
					'brand_highlight' => [ 'type' => 'text', 'label' => 'بخش رنگی نام برنامه', 'default' => 'بر بازار' ],
					'title'           => [ 'type' => 'text', 'label' => 'تیتر اصلی (H1)', 'default' => 'فروش کار و کسب را' ],
					'growth_from'     => [ 'type' => 'text', 'label' => 'عدد شروع', 'default' => '۲۵' ],
					'growth_between'  => [ 'type' => 'text', 'label' => 'واژه میانی', 'default' => 'تا' ],
					'growth_to'       => [ 'type' => 'text', 'label' => 'عدد پایان', 'default' => '۳۰۰' ],
					'growth_unit'     => [ 'type' => 'text', 'label' => 'واحد', 'default' => 'درصد' ],
					'subtitle'        => [ 'type' => 'text', 'label' => 'ادامه تیتر', 'default' => 'افزایش دهید' ],
					'note'            => [ 'type' => 'rich', 'label' => 'توضیح زیر تیتر', 'default' => '<mark>نه</mark> با تخفیف بیشتر؛ با پیدا کردن و اصلاح گلوگاه‌هایی <span class="hero-note-keep">که جلوی رشد فروش شما را گرفته‌اند</span>' ],
				],
			],
			'offer' => [
				'label'    => 'کارت پیشنهاد',
				'controls' => [
					'show_offer'      => [ 'type' => 'switcher', 'label' => 'نمایش کارت', 'default' => 'yes' ],
					'offer_label'     => [ 'type' => 'text', 'label' => 'عنوان کارت (دسترسی‌پذیری)', 'default' => 'جزئیات برنامه' ],
					'duration_number' => [ 'type' => 'text', 'label' => 'مدت (عدد)', 'default' => '۱۴' ],
					'duration_unit'   => [ 'type' => 'text', 'label' => 'مدت (واحد)', 'default' => 'هفته' ],
					'duration_icon'   => [ 'type' => 'icon', 'label' => 'آیکن مدت' ],
					'offer_text'      => [ 'type' => 'textarea', 'label' => 'متن کارت', 'default' => 'برای صاحبان و مدیران کار و کسبی که فروش‌شان آن‌طور که باید نیست و می‌خواهند ظرفیت بیشتری برای رشد داشته باشند.' ],
					'icons'           => [
						'type'        => 'repeater',
						'label'       => 'ردیف آیکن‌ها',
						'title_field' => '{{{ builtin }}}',
						'fields'      => [
							'builtin' => [ 'type' => 'select', 'label' => 'آیکن طرح', 'default' => 'shield', 'options' => self::SPRITES ],
							'custom'  => [ 'type' => 'icon', 'label' => 'آیکن دلخواه' ],
						],
						'default'     => [
							[ 'builtin' => 'shield' ],
							[ 'builtin' => 'support' ],
							[ 'builtin' => 'focus' ],
							[ 'builtin' => 'chart' ],
						],
					],
					'icon_separator'  => [ 'type' => 'text', 'label' => 'جداکننده آیکن‌ها', 'default' => '＋' ],
					'offer_strong'    => [ 'type' => 'text', 'label' => 'متن پررنگ', 'default' => 'آموزش + راهبری + کوچینگ + منتورینگ روی کار و کسب واقعی شما' ],
					'cta_text'        => [ 'type' => 'text', 'label' => 'متن لینک', 'default' => 'می‌خواهم ببینم این برنامه برای کار و کسب من مناسب است' ],
					'cta_link'        => [ 'type' => 'link', 'label' => 'لینک', 'default' => '#assessment' ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'        => self::section_style( '.hero-v2-coded' ),
			'photo'          => [
				'label'    => 'تصویر',
				'selector' => '.hero-v2-photo',
				'kinds'    => [ 'bg_size', 'bg_position', 'height', 'min_height', 'radius', 'opacity', 'hide' ],
			],
			'photo_overlay'  => [
				'label'    => 'سایه‌رنگ روی تصویر',
				'selector' => '.hero-v2-photo:after',
				'kinds'    => [ 'background', 'width', 'opacity', 'hide' ],
			],
			'copy'           => [
				'label'    => 'ستون متن',
				'selector' => '.hero-v2-copy',
				'kinds'    => [ 'background', 'padding', 'align' ],
			],
			'brand'          => self::text_style( 'نام برنامه', '.hero-v2-brand' ),
			'brand_hl'       => [
				'label'    => 'بخش رنگی نام برنامه',
				'selector' => '.hero-v2-brand span',
				'kinds'    => [ 'typography', 'color' ],
			],
			'title'          => self::text_style( 'تیتر اصلی', '.hero-v2-copy h1' ),
			'growth'         => [
				'label'    => 'ردیف اعداد',
				'selector' => '.hero-v2-growth',
				'kinds'    => [ 'gap', 'margin' ],
			],
			'growth_from'    => [
				'label'    => 'عدد شروع',
				'selector' => '.hero-v2-growth b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'growth_to'      => [
				'label'    => 'عدد پایان',
				'selector' => '.hero-v2-growth strong',
				'kinds'    => [ 'typography', 'color' ],
			],
			'growth_words'   => [
				'label'    => 'واژه‌های کنار اعداد',
				'selector' => '.hero-v2-growth i, .hero-v2-growth em',
				'kinds'    => [ 'typography', 'color' ],
			],
			'subtitle'       => self::text_style( 'ادامه تیتر', '.hero-v2-copy h2' ),
			'note'           => self::text_style( 'توضیح زیر تیتر', '.hero-v2-note' ),
			'note_mark'      => [
				'label'    => 'واژه برجسته توضیح',
				'selector' => '.hero-v2-note mark',
				'kinds'    => [ 'typography', 'color', 'bg', 'radius', 'padding' ],
			],
			'offer'          => self::box_style( 'کارت پیشنهاد', '.hero-v2-offer', [ 'width', 'max_width' ] ),
			'duration'       => [
				'label'    => 'مدت برنامه',
				'selector' => '.hero-v2-duration',
				'kinds'    => [ 'typography', 'color', 'background', 'radius', 'padding' ],
			],
			'duration_num'   => [
				'label'    => 'عدد مدت',
				'selector' => '.hero-v2-duration b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'duration_icon'  => self::icon_style( 'آیکن مدت', '.hero-v2-duration svg' ),
			'offer_text'     => self::text_style( 'متن کارت', '.hero-v2-offer > p' ),
			'icons'          => [
				'label'    => 'ردیف آیکن‌ها',
				'selector' => '.hero-v2-icons',
				'kinds'    => [ 'gap', 'border_color', 'padding', 'hide' ],
			],
			'icons_icon'     => self::icon_style( 'آیکن‌ها', '.hero-v2-icons svg' ),
			'icons_sep'      => [
				'label'    => 'جداکننده آیکن‌ها',
				'selector' => '.hero-v2-icons > i',
				'kinds'    => [ 'typography', 'color' ],
			],
			'offer_strong'   => self::text_style( 'متن پررنگ کارت', '.hero-v2-offer > strong' ),
			'cta'            => self::button_style( 'لینک کارت', '.hero-v2-offer > a' ),
		];
	}

	protected function render_html( array $s ) {
		$photo = self::media_url( $s['photo'] ?? '' );
		$style = $photo ? ' style="background-image:url(&quot;' . esc_url( $photo ) . '&quot;)"' : '';
		$from  = self::v( $s, 'growth_from' );
		$mid   = self::v( $s, 'growth_between' );
		$to    = self::v( $s, 'growth_to' );
		$unit  = self::v( $s, 'growth_unit' );
		$icons = [];
		foreach ( self::rows( $s, 'icons' ) as $row ) {
			$sprite  = array_key_exists( self::v( $row, 'builtin' ), self::SPRITES ) ? self::v( $row, 'builtin' ) : 'shield';
			$icons[] = self::icon( $row['custom'] ?? [], '<svg><use href="#i-' . $sprite . '"/></svg>' );
		}
		$separator = '<i>' . self::t( self::v( $s, 'icon_separator' ) ) . '</i>';
		?>
<section class="hero-v2-coded" aria-labelledby="hero-v2-title">
      <div class="hero-v2-photo" role="img" aria-label="<?php echo self::a( self::v( $s, 'photo_label' ) ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
      <div class="hero-v2-copy">
        <p class="hero-v2-brand"><?php echo self::t( self::v( $s, 'brand' ) ); ?> <span><?php echo self::t( self::v( $s, 'brand_highlight' ) ); ?></span></p>
        <h1 id="hero-v2-title"><?php echo self::t( self::v( $s, 'title' ) ); ?></h1>
        <div class="hero-v2-growth" aria-label="<?php echo self::a( trim( $from . ' ' . $mid . ' ' . $to . ' ' . $unit ) ); ?>"><b><?php echo self::t( $from ); ?></b><i><?php echo self::t( $mid ); ?></i><strong><?php echo self::t( $to ); ?></strong><em><?php echo self::t( $unit ); ?></em></div>
        <h2><?php echo self::t( self::v( $s, 'subtitle' ) ); ?></h2>
        <p class="hero-v2-note"><?php echo self::r( self::v( $s, 'note' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
      </div>
<?php if ( 'yes' === self::v( $s, 'show_offer' ) ) : ?>
      <aside class="hero-v2-offer" aria-label="<?php echo self::a( self::v( $s, 'offer_label' ) ); ?>">
        <div class="hero-v2-duration"><span><b><?php echo self::t( self::v( $s, 'duration_number' ) ); ?></b> <?php echo self::t( self::v( $s, 'duration_unit' ) ); ?></span><?php echo self::icon( $s['duration_icon'] ?? [], '<svg aria-hidden="true"><use href="#i-calendar"/></svg>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        <p><?php echo self::t( self::v( $s, 'offer_text' ) ); ?></p>
<?php if ( $icons ) : ?>
        <div class="hero-v2-icons" aria-hidden="true"><?php echo implode( $separator, $icons ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
<?php endif; ?>
        <strong><?php echo self::t( self::v( $s, 'offer_strong' ) ); ?></strong>
        <a<?php echo self::href( self::arr( $s, 'cta_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $s, 'cta_text' ) ); ?></a>
      </aside>
<?php endif; ?>
    </section>
		<?php
	}
}
