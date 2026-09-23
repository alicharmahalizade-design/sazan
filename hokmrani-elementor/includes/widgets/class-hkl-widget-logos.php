<?php
/**
 * Infinite carousel of participating organisations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Logos extends HKL_Widget_Base {

	protected static function slug() {
		return 'logos';
	}

	protected static function title() {
		return 'حکمرانی — کاروسل لوگوها';
	}

	public function get_icon() {
		return 'eicon-logo';
	}

	protected static function fields() {
		return [
			'content' => [
				'label'    => 'لوگوها',
				'controls' => [
					'show_label' => [ 'type' => 'switcher', 'label' => 'نمایش عنوان', 'default' => 'yes' ],
					'label'      => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'برخی از سازمان‌های همراه برنامه' ],
					'aria_label' => [ 'type' => 'text', 'label' => 'عنوان بخش (دسترسی‌پذیری)', 'default' => 'سازمان‌های شرکت‌کننده در برنامه' ],
					'logos'      => [
						'type'        => 'repeater',
						'label'       => 'سازمان‌ها',
						'title_field' => '{{{ name }}}',
						'fields'      => [
							'letter' => [ 'type' => 'text', 'label' => 'حرف نشان', 'default' => 'س' ],
							'name'   => [ 'type' => 'text', 'label' => 'نام', 'default' => 'سازمان' ],
							'image'  => [ 'type' => 'media', 'label' => 'لوگو (اختیاری)', 'default' => '', 'description' => 'اگر تصویر انتخاب شود به‌جای حرف و نام نمایش داده می‌شود.' ],
							'link'   => [ 'type' => 'link', 'label' => 'لینک (اختیاری)', 'default' => '', 'description' => 'اگر خالی بماند لوگو لینک نمی‌شود.' ],
						],
						'default'     => [
							[ 'letter' => 'ف', 'name' => 'فناوری' ],
							[ 'letter' => 'ت', 'name' => 'تولید' ],
							[ 'letter' => 'خ', 'name' => 'خدمات' ],
							[ 'letter' => 'آ', 'name' => 'آموزش' ],
							[ 'letter' => 'س', 'name' => 'سلامت' ],
							[ 'letter' => 'ب', 'name' => 'بازرگانی' ],
						],
					],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'   => self::section_style( '.logo-carousel' ),
			'label'     => self::text_style( 'عنوان', '.logo-carousel-label b' ),
			'lines'     => [
				'label'    => 'خطوط کنار عنوان',
				'selector' => '.logo-carousel-label span',
				'kinds'    => [ 'bg', 'height', 'hide' ],
			],
			'track'     => [
				'label'    => 'حرکت کاروسل',
				'selector' => '.logo-track',
				'kinds'    => [ 'speed' ],
			],
			'set'       => [
				'label'    => 'فاصله لوگوها',
				'selector' => '.logo-set',
				'kinds'    => [ 'gap', 'padding' ],
			],
			'item'      => self::box_style( 'کارت لوگو', '.org-logo', [ 'width', 'height', 'gap' ] ),
			'letter'    => [
				'label'    => 'حرف نشان',
				'selector' => '.org-logo i',
				'kinds'    => [ 'typography', 'color', 'bg', 'radius' ],
			],
			'name'      => [
				'label'    => 'نام سازمان',
				'selector' => '.org-logo span',
				'kinds'    => [ 'typography', 'color' ],
			],
			'image'     => [
				'label'    => 'تصویر لوگو',
				'selector' => '.org-logo.has-image img',
				'kinds'    => [ 'height', 'max_width', 'opacity' ],
			],
		];
	}

	protected function render_html( array $s ) {
		$set = '';
		foreach ( self::rows( $s, 'logos' ) as $logo ) {
			$image = self::media_url( $logo['image'] ?? '' );
			$link  = self::arr( $logo, 'link' );
			$inner = $image
				? '<img src="' . esc_url( $image ) . '" alt="' . self::a( self::v( $logo, 'name' ) ) . '" loading="lazy">'
				: '<i>' . self::t( self::v( $logo, 'letter' ) ) . '</i><span>' . self::t( self::v( $logo, 'name' ) ) . '</span>';
			if ( ! empty( $link['url'] ) ) {
				$set .= '<a class="org-logo' . ( $image ? ' has-image' : '' ) . '"' . self::href( $link ) . '>' . $inner . '</a>';
			} else {
				$set .= '<div class="org-logo' . ( $image ? ' has-image' : '' ) . '">' . $inner . '</div>';
			}
		}
		?>
<section class="logo-carousel" aria-label="<?php echo self::a( self::v( $s, 'aria_label' ) ); ?>">
<?php if ( 'yes' === self::v( $s, 'show_label' ) ) : ?>
      <div class="logo-carousel-label"><span></span><b><?php echo self::t( self::v( $s, 'label' ) ); ?></b><span></span></div>
<?php endif; ?>
      <div class="logo-viewport">
        <div class="logo-track">
          <div class="logo-set">
            <?php echo $set; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

          </div>
          <div class="logo-set" aria-hidden="true">
            <?php echo $set; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

          </div>
        </div>
      </div>
    </section>
		<?php
	}
}
