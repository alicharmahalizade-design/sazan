<?php
/**
 * "What is Hokmrani?" – intro with two media cards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Program extends HKL_Widget_Base {

	protected static function slug() {
		return 'program';
	}

	protected static function title() {
		return 'حکمرانی — درباره برنامه';
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	protected static function fields() {
		return [
			'intro' => [
				'label'    => 'معرفی',
				'controls' => [
					'section_id' => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'program' ],
					'title'      => [ 'type' => 'rich', 'label' => 'تیتر', 'default' => 'حکمرانی <span>بر بازار</span> چیست؟' ],
					'text'       => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'یک برنامه ۱۴ هفته‌ای برای مدیرانی که می‌خواهند فقط اطلاعات بیشتری نداشته باشند؛ می‌خواهند کار و کسبشان بهتر نتیجه بگیرد.' ],
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
							'style'    => [
								'type'    => 'select',
								'label'   => 'طرح تصویر',
								'default' => 'teacher-bg',
								'options' => [
									'teacher-bg' => 'آموزش',
									'board-bg'   => 'کارگروه',
								],
							],
							'image'    => [ 'type' => 'media', 'label' => 'عکس (اختیاری)', 'default' => '', 'description' => 'خالی بماند تا طرح اصلی نمایش داده شود.' ],
							'title'    => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'عنوان' ],
							'subtitle' => [ 'type' => 'text', 'label' => 'زیرعنوان', 'default' => '' ],
						],
						'default'     => [
							[ 'style' => 'teacher-bg', 'title' => '۱۴ جلسه آموزش و راهبری', 'subtitle' => 'با عباس شانه سازان' ],
							[ 'style' => 'board-bg', 'title' => 'کارگروه منتورینگ و بررسی مسئله', 'subtitle' => 'با دکتر علی ویسی تبار و مونا کمایی' ],
						],
					],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="program white-section section-pad">
      <div class="container program-grid">
        <div><h2><?php echo self::r( self::v( $s, 'title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p></div>
<?php
		foreach ( self::rows( $s, 'cards' ) as $card ) :
			$style = 'board-bg' === self::v( $card, 'style' ) ? 'board-bg' : 'teacher-bg';
			$image = self::media_url( $card['image'] ?? '' );
			$attr  = $image ? ' has-photo" style="background-image:url(&quot;' . esc_url( $image ) . '&quot;)' : '';
			?>
        <article class="media-card"><div class="media-photo <?php echo $style . $attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"></div><b><?php echo self::t( self::v( $card, 'title' ) ); ?></b><span><?php echo self::t( self::v( $card, 'subtitle' ) ); ?></span></article>
<?php endforeach; ?>
      </div>
    </section>
		<?php
	}
}
