<?php
/**
 * "Our slides are not repetitive" – four step process.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Roadmap extends HKL_Widget_Base {

	protected static function slug() {
		return 'roadmap';
	}

	protected static function title() {
		return 'حکمرانی — مراحل کار';
	}

	public function get_icon() {
		return 'eicon-flow';
	}

	protected static function fields() {
		return [
			'intro' => [
				'label'    => 'عنوان بخش',
				'controls' => [
					'title'    => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'اسلایدهای ما تکراری نیستند' ],
					'subtitle' => [ 'type' => 'text', 'label' => 'زیرتیتر', 'default' => 'اول کار و کسب شما را می‌بینیم' ],
				],
			],
			'steps' => [
				'label'    => 'مراحل',
				'controls' => [
					'steps'   => [
						'type'        => 'repeater',
						'label'       => 'مراحل',
						'title_field' => '{{{ number }}} - {{{ title }}}',
						'fields'      => [
							'number' => [ 'type' => 'text', 'label' => 'شماره', 'default' => '۱' ],
							'title'  => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'مرحله' ],
							'text'   => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => '' ],
						],
						'default'     => [
							[ 'number' => '۱', 'title' => 'تشخیص', 'text' => 'گلوگاه‌های اصلی کار و کسب مشخص می‌شوند.' ],
							[ 'number' => '۲', 'title' => 'اولویت‌بندی', 'text' => 'مشخص می‌شود کدام مسئله بیشترین اثر را دارد.' ],
							[ 'number' => '۳', 'title' => 'اقدام', 'text' => 'راهکار متناسب با همان کار و کسب طراحی و اجرا می‌شود.' ],
							[ 'number' => '۴', 'title' => 'پیگیری', 'text' => 'نتیجه بررسی و در صورت نیاز، مسیر اصلاح می‌شود.' ],
						],
					],
					'process' => [ 'type' => 'text', 'label' => 'متن فرایند', 'default' => 'یعنی: آموزش ← اجرا ← اندازه‌گیری ← اصلاح ← رشد' ],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		$steps = self::rows( $s, 'steps' );
		$parts = [];
		foreach ( $steps as $step ) {
			$parts[] = '<article><b>' . self::t( self::v( $step, 'number' ) ) . '</b><h3>' . self::t( self::v( $step, 'title' ) ) . '</h3><p>' . self::t( self::v( $step, 'text' ) ) . '</p></article>';
		}
		?>
<section class="roadmap dark-section section-pad">
      <div class="container"><div class="section-title light"><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><h3><span><?php echo self::t( self::v( $s, 'subtitle' ) ); ?></span></h3></div>
        <div class="steps">
          <?php echo implode( "<i>‹</i>\n          ", $parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        </div>
        <p class="process"><?php echo self::t( self::v( $s, 'process' ) ); ?></p>
      </div>
    </section>
		<?php
	}
}
