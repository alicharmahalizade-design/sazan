<?php
/**
 * "Maybe your problem is not a lack of customers" – diagnosis cards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Diagnosis extends HKL_Widget_Base {

	const ICONS = [
		'advantage'  => '<path d="M18 43 32 13l14 30-14 8-14-8Z"/><path d="m25 36 7-13 7 13-7 4-7-4Z"/>',
		'team'       => '<circle cx="22" cy="24" r="7"/><circle cx="42" cy="24" r="7"/><path d="M10 49c1-9 6-14 12-14s11 5 12 14M30 49c1-9 6-14 12-14s11 5 12 14"/>',
		'customer'   => '<circle cx="28" cy="27" r="10"/><path d="M10 52c2-11 8-16 18-16s16 5 18 16M45 17v14m-7-7h14"/>',
		'trust'      => '<path d="M32 8 51 16v13c0 12-7 21-19 27-12-6-19-15-19-27V16l19-8Z"/><path d="m23 31 6 6 13-14"/>',
		'negotiate'  => '<path d="m8 33 13-13 12 8-13 13L8 33Zm48 0L43 20l-12 8 13 13 12-8Z"/><path d="m24 36 8 8 8-8"/>',
		'experience' => '<path d="M10 45V19h44v26H10Z"/><path d="M19 35c5 7 21 7 26 0M21 28h1m20 0h1"/>',
	];

	protected static function slug() {
		return 'diagnosis';
	}

	protected static function title() {
		return 'حکمرانی — ریشه‌یابی افت فروش';
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	protected static function fields() {
		return [
			'intro' => [
				'label'    => 'عنوان بخش',
				'controls' => [
					'section_id'   => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => '' ],
					'eyebrow'      => [ 'type' => 'text', 'label' => 'برچسب بالا', 'default' => 'ریشه‌یابی افت فروش' ],
					'title'        => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'شاید مشکل فروش شما، کمبود مشتری نیست' ],
					'mobile_text'  => [ 'type' => 'text', 'label' => 'توضیح (موبایل)', 'default' => 'گاهی فروش رشد نمی‌کند چون...' ],
					'desktop_text' => [ 'type' => 'textarea', 'label' => 'توضیح (دسکتاپ)', 'default' => 'گاهی مسئله اصلی در بخش‌هایی پنهان شده که هر روز با آن‌ها کار می‌کنید؛ اما اثرشان را یک‌جا نمی‌بینید.' ],
				],
			],
			'cards' => [
				'label'    => 'کارت‌ها',
				'controls' => [
					'cards' => [
						'type'        => 'repeater',
						'label'       => 'کارت‌ها',
						'title_field' => '{{{ title }}}',
						'fields'      => [
							'icon'        => [
								'type'    => 'select',
								'label'   => 'آیکن طرح',
								'default' => 'advantage',
								'options' => [
									'advantage'  => 'مزیت رقابتی',
									'team'       => 'تیم',
									'customer'   => 'مشتری',
									'trust'      => 'اعتماد',
									'negotiate'  => 'مذاکره',
									'experience' => 'تجربه مشتری',
								],
							],
							'custom_icon' => [ 'type' => 'icon', 'label' => 'آیکن دلخواه' ],
							'title'       => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'عنوان' ],
							'text'        => [ 'type' => 'text', 'label' => 'متن', 'default' => 'توضیح' ],
							'link'        => [ 'type' => 'link', 'label' => 'لینک (اختیاری)', 'default' => '', 'description' => 'اگر خالی بماند کارت لینک نمی‌شود.' ],
						],
						'default'     => [
							[ 'icon' => 'advantage', 'title' => 'مزیت رقابتی', 'text' => 'واقعی نیست' ],
							[ 'icon' => 'team', 'title' => 'تیم فروش', 'text' => 'درست عمل نمی‌کند' ],
							[ 'icon' => 'customer', 'title' => 'مشتری واقعی', 'text' => 'درست شناخته نشده' ],
							[ 'icon' => 'trust', 'title' => 'اعتماد در فرایند', 'text' => 'فروش ساخته نمی‌شود' ],
							[ 'icon' => 'negotiate', 'title' => 'مذاکره و تخفیف', 'text' => 'فروش را ختم می‌کند' ],
							[ 'icon' => 'experience', 'title' => 'تجربه مشتری', 'text' => 'ضعیف است' ],
						],
					],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'   => self::section_style( '.diagnosis' ),
			'container' => [
				'label'    => 'عرض محتوا',
				'selector' => '.diagnosis > .container',
				'kinds'    => [ 'max_width', 'padding' ],
			],
			'eyebrow'   => self::text_style( 'برچسب بالا', '.diagnosis-intro .eyebrow', [ 'bg', 'radius', 'padding', 'hide' ] ),
			'title'     => self::text_style( 'تیتر', '.diagnosis-intro h2' ),
			'mobile'    => self::text_style( 'توضیح (موبایل)', '.diagnosis-mobile-copy', [ 'hide' ] ),
			'desktop'   => self::text_style( 'توضیح (دسکتاپ)', '.diagnosis-desktop-copy', [ 'hide' ] ),
			'grid'      => [
				'label'    => 'چیدمان کارت‌ها',
				'selector' => '.diagnosis-grid',
				'kinds'    => [ 'gap', 'margin' ],
			],
			'card'      => self::box_style( 'کارت', '.mini-card', [ 'border_color_hover', 'bg_hover', 'align' ] ),
			'badge'     => [
				'label'    => 'نشان ستاره کارت',
				'selector' => '.mini-card:before',
				'kinds'    => [ 'bg', 'width', 'height', 'hide' ],
			],
			'visual'    => [
				'label'    => 'کادر آیکن',
				'selector' => '.mini-visual',
				'kinds'    => [ 'icon_color', 'bg', 'radius', 'width', 'height', 'padding' ],
			],
			'icon'      => self::icon_style( 'آیکن', '.mini-visual svg' ),
			'card_title' => [
				'label'    => 'عنوان کارت',
				'selector' => '.mini-card b',
				'kinds'    => [ 'typography', 'color' ],
			],
			'card_text' => [
				'label'    => 'متن کارت',
				'selector' => '.mini-card p',
				'kinds'    => [ 'typography', 'color', 'margin' ],
			],
		];
	}

	protected function render_html( array $s ) {
		$icons = self::ICONS;
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="white-section diagnosis section-pad">
      <div class="container">
        <div class="section-title diagnosis-intro"><span class="eyebrow"><?php echo self::t( self::v( $s, 'eyebrow' ) ); ?></span><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p class="diagnosis-mobile-copy"><?php echo self::t( self::v( $s, 'mobile_text' ) ); ?></p><p class="diagnosis-desktop-copy"><?php echo self::t( self::v( $s, 'desktop_text' ) ); ?></p></div>
        <div class="diagnosis-grid">
<?php
		foreach ( self::rows( $s, 'cards' ) as $card ) :
			$icon = self::v( $card, 'icon' );
			if ( ! isset( $icons[ $icon ] ) ) {
				$icon = 'advantage';
			}
			$visual = self::icon( $card['custom_icon'] ?? [], '<svg viewBox="0 0 64 64">' . $icons[ $icon ] . '</svg>' );
			$link   = self::arr( $card, 'link' );
			$tag    = ! empty( $link['url'] ) ? 'a' : 'div';
			?>
          <<?php echo $tag; ?> class="mini-card"<?php echo 'a' === $tag ? self::href( $link ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span class="mini-visual visual-<?php echo esc_attr( $icon ); ?>" aria-hidden="true"><?php echo $visual; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><b><?php echo self::t( self::v( $card, 'title' ) ); ?></b><p><?php echo self::t( self::v( $card, 'text' ) ); ?></p></<?php echo $tag; ?>>
<?php endforeach; ?>
        </div>
      </div>
    </section>
		<?php
	}
}
