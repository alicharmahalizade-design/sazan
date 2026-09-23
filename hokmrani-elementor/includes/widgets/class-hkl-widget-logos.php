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
					'aria_label' => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری', 'default' => 'سازمان‌های شرکت‌کننده در برنامه' ],
					'label'      => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'برخی از سازمان‌های همراه برنامه' ],
					'logos'      => [
						'type'        => 'repeater',
						'label'       => 'سازمان‌ها',
						'title_field' => '{{{ name }}}',
						'fields'      => [
							'letter' => [ 'type' => 'text', 'label' => 'حرف نشان', 'default' => 'س' ],
							'name'   => [ 'type' => 'text', 'label' => 'نام', 'default' => 'سازمان' ],
							'image'  => [ 'type' => 'media', 'label' => 'لوگو (اختیاری)', 'default' => '', 'description' => 'اگر تصویر انتخاب شود به‌جای حرف و نام نمایش داده می‌شود.' ],
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

	protected function render_html( array $s ) {
		$set = '';
		foreach ( self::rows( $s, 'logos' ) as $logo ) {
			$image = self::media_url( $logo['image'] ?? '' );
			if ( $image ) {
				$set .= '<div class="org-logo has-image"><img src="' . esc_url( $image ) . '" alt="' . self::a( self::v( $logo, 'name' ) ) . '" loading="lazy"></div>';
			} else {
				$set .= '<div class="org-logo"><i>' . self::t( self::v( $logo, 'letter' ) ) . '</i><span>' . self::t( self::v( $logo, 'name' ) ) . '</span></div>';
			}
		}
		?>
<section class="logo-carousel" aria-label="<?php echo self::a( self::v( $s, 'aria_label' ) ); ?>">
      <div class="logo-carousel-label"><span></span><b><?php echo self::t( self::v( $s, 'label' ) ); ?></b><span></span></div>
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
