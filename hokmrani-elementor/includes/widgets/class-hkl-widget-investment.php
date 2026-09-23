<?php
/**
 * Price / payment terms and the enrollment call to action.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Investment extends HKL_Widget_Base {

	const ICONS = [
		'wallet'      => [ '', '<svg viewBox="0 0 140 120"><rect x="20" y="32" width="100" height="70" rx="12" fill="#122e57"/><rect x="28" y="40" width="84" height="54" rx="8" fill="#244776"/><path d="M70 24c11 16 20 29 20 43a20 20 0 0 1-40 0c0-14 9-27 20-43Z" fill="#ff6b2c"/><circle cx="70" cy="68" r="9" fill="#ffc14d"/><path d="M8 96c18-15 34-18 52-7 18 12 37 11 72-3" fill="none" stroke="#e8eef4" stroke-width="10" stroke-linecap="round"/><path d="M15 91h36M94 86h28" stroke="#74d5dd" stroke-width="5" stroke-linecap="round"/></svg>' ],
		'installment' => [ ' installment', '<svg viewBox="0 0 140 120"><circle cx="70" cy="59" r="46" fill="#eaf0f4" stroke="#9eabb8" stroke-width="2"/><path d="M70 59V13A46 46 0 0 1 110 82Z" fill="#74d5dd"/><path d="M70 59 110 82a46 46 0 0 1-72 14Z" fill="#ff702f"/><path d="M70 59 38 96A46 46 0 0 1 24 51Z" fill="#d7dde3"/><path d="M70 59 24 51A46 46 0 0 1 70 13Z" fill="#fff"/><circle cx="70" cy="59" r="11" fill="#163862"/></svg>' ],
	];

	protected static function slug() {
		return 'investment';
	}

	protected static function title() {
		return 'حکمرانی — سرمایه‌گذاری و شرایط پرداخت';
	}

	public function get_icon() {
		return 'eicon-price-table';
	}

	protected static function fields() {
		return [
			'hero'  => [
				'label'    => 'تصویر بالا',
				'controls' => [
					'section_id' => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => '' ],
					'title'     => [ 'type' => 'text', 'label' => 'تیتر (برای موتور جستجو، مخفی)', 'default' => '۹ درصد پیش‌پرداخت و تسهیلات پنج ماهه' ],
					'image'     => [ 'type' => 'media', 'label' => 'تصویر', 'default' => 'nine-percent.png' ],
					'image_alt' => [ 'type' => 'text', 'label' => 'متن جایگزین تصویر', 'default' => '۹ درصد پیش‌پرداخت و تسهیلات پنج ماهه' ],
				],
			],
			'cards' => [
				'label'    => 'کارت‌ها',
				'controls' => [
					'cards'    => [
						'type'        => 'repeater',
						'label'       => 'کارت‌ها',
						'title_field' => '{{{ title }}}',
						'fields'      => [
							'icon'    => [
								'type'    => 'select',
								'label'   => 'آیکن',
								'default' => 'wallet',
								'options' => [
									'wallet'      => 'کیف پول',
									'installment' => 'نمودار اقساط',
								],
							],
							'custom_icon' => [ 'type' => 'icon', 'label' => 'آیکن دلخواه' ],
							'image'   => [ 'type' => 'media', 'label' => 'تصویر به‌جای آیکن (اختیاری)', 'default' => '' ],
							'eyebrow' => [ 'type' => 'text', 'label' => 'برچسب', 'default' => '' ],
							'title'   => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'عنوان' ],
							'text'    => [ 'type' => 'rich', 'label' => 'متن', 'default' => '' ],
						],
						'default'     => [
							[ 'icon' => 'wallet', 'eyebrow' => 'جزئیات ثبت‌نام', 'title' => 'سرمایه‌گذاری در دوره', 'text' => 'بهای شرکت در دوره <b>۱۶۹٬۵۰۰٬۰۰۰ تومان</b> است. با تکمیل فرم پیش‌ثبت‌نام، شرایط پرداخت متناسب با شما بررسی می‌شود.' ],
							[ 'icon' => 'installment', 'eyebrow' => 'پرداخت منعطف', 'title' => 'شرایط پرداخت مرحله‌ای', 'text' => '<b>۹٪ پیش‌پرداخت</b> و باقیمانده در قالب تسهیلات ۵ ماهه، مطابق شرایط نهایی ثبت‌نام و اعتبارسنجی.' ],
						],
					],
					'show_cta'  => [ 'type' => 'switcher', 'label' => 'نمایش دکمه', 'default' => 'yes' ],
					'cta_text'  => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'دریافت مشاوره و شرایط ثبت‌نام' ],
					'cta_link'  => [ 'type' => 'link', 'label' => 'لینک دکمه (اختیاری)', 'default' => '', 'description' => 'اگر خالی بماند دکمه فرم پیش‌ثبت‌نام را باز می‌کند.' ],
					'cta_arrow' => [ 'type' => 'text', 'label' => 'نماد دکمه', 'default' => '←' ],
					'cta_icon'  => [ 'type' => 'icon', 'label' => 'آیکن دکمه' ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'  => self::section_style( '.investment-section' ),
			'wrap'     => [
				'label'    => 'چیدمان',
				'selector' => '.investment-wrap',
				'kinds'    => [ 'gap', 'max_width' ],
			],
			'image'    => [
				'label'    => 'تصویر بالا',
				'selector' => '.investment-hero img',
				'kinds'    => [ 'width', 'max_width', 'height', 'opacity', 'hide' ],
			],
			'box'      => self::box_style( 'کادر کارت‌ها', '.investment-box', [ 'gap' ] ),
			'card'     => self::box_style( 'کارت', '.investment-card', [ 'gap' ] ),
			'icon_box' => [
				'label'    => 'کادر آیکن',
				'selector' => '.investment-icon',
				'kinds'    => [ 'background', 'width', 'min_height', 'radius', 'icon_color', 'hide' ],
			],
			'icon'     => self::icon_style( 'آیکن', '.investment-icon svg, .investment-icon img' ),
			'eyebrow'  => self::text_style( 'برچسب', '.investment-card > div:last-child > span', [ 'bg', 'radius', 'padding' ] ),
			'title'    => self::text_style( 'عنوان', '.investment-card h3' ),
			'text'     => self::text_style( 'متن', '.investment-card p' ),
			'bold'     => [
				'label'    => 'بخش پررنگ متن',
				'selector' => '.investment-card p b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'cta'      => self::button_style( 'دکمه', '.investment-cta', [ 'width' ] ),
		];
	}

	protected function render_html( array $s ) {
		$image = self::media_url( $s['image'] ?? '' );
		list( $w, $h ) = self::media_size( $s['image'] ?? '', 1428, 523 );
		$icons = self::ICONS;
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="investment-section white-section section-pad" aria-labelledby="investment-title">
      <div class="container investment-wrap">
        <header class="investment-hero">
          <h2 id="investment-title" class="sr-only"><?php echo self::t( self::v( $s, 'title' ) ); ?></h2>
          <?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt="<?php echo self::a( self::v( $s, 'image_alt' ) ); ?>" width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>" loading="lazy"><?php endif; ?>

        </header>
        <div class="investment-box">
<?php
		foreach ( self::rows( $s, 'cards' ) as $card ) :
			$icon  = isset( $icons[ self::v( $card, 'icon' ) ] ) ? $icons[ self::v( $card, 'icon' ) ] : $icons['wallet'];
			$image = self::media_url( $card['image'] ?? '' );
			$art   = $image ? '<img src="' . esc_url( $image ) . '" alt="" loading="lazy">' : self::icon( $card['custom_icon'] ?? [], $icon[1] );
			?>
          <article class="investment-card">
            <div class="investment-icon<?php echo $icon[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" aria-hidden="true"><?php echo $art; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <div><span><?php echo self::t( self::v( $card, 'eyebrow' ) ); ?></span><h3><?php echo self::t( self::v( $card, 'title' ) ); ?></h3><p><?php echo self::r( self::v( $card, 'text' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p></div>
          </article>
<?php endforeach; ?>
        </div>
<?php
		if ( 'yes' === self::v( $s, 'show_cta' ) ) :
			$link  = self::arr( $s, 'cta_link' );
			$arrow = '<span aria-hidden="true">' . self::icon( $s['cta_icon'] ?? [], self::t( self::v( $s, 'cta_arrow' ) ) ) . '</span>';
			if ( ! empty( $link['url'] ) ) :
				?>
        <a class="investment-cta"<?php echo self::href( $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $s, 'cta_text' ) ); ?> <?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			<?php else : ?>
        <button class="investment-cta" type="button" data-open-enrollment><?php echo self::t( self::v( $s, 'cta_text' ) ); ?> <?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<?php
			endif;
		endif;
		?>
      </div>
    </section>
		<?php
	}
}
