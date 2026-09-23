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
					'section_id' => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => '' ],
					'title'      => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'اسلایدهای ما تکراری نیستند' ],
					'subtitle'   => [ 'type' => 'text', 'label' => 'زیرتیتر', 'default' => 'اول کار و کسب شما را می‌بینیم' ],
				],
			],
			'steps' => [
				'label'    => 'مراحل',
				'controls' => [
					'steps'        => [
						'type'        => 'repeater',
						'label'       => 'مراحل',
						'title_field' => '{{{ number }}} - {{{ title }}}',
						'fields'      => [
							'number' => [ 'type' => 'text', 'label' => 'شماره', 'default' => '۱' ],
							'icon'   => [ 'type' => 'icon', 'label' => 'آیکن (به‌جای شماره)', 'description' => 'خالی بماند تا شماره نمایش داده شود.' ],
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
					'separator'    => [ 'type' => 'text', 'label' => 'نماد بین مراحل', 'default' => '‹' ],
					'show_process' => [ 'type' => 'switcher', 'label' => 'نمایش متن فرایند', 'default' => 'yes' ],
					'process'      => [ 'type' => 'text', 'label' => 'متن فرایند', 'default' => 'یعنی: آموزش ← اجرا ← اندازه‌گیری ← اصلاح ← رشد' ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'    => self::section_style( '.roadmap' ),
			'title'      => self::text_style( 'تیتر', '.roadmap .section-title h2' ),
			'subtitle'   => self::text_style( 'زیرتیتر', '.roadmap .section-title h3 span', [ 'bg', 'radius', 'padding' ] ),
			'steps'      => [
				'label'    => 'چیدمان مراحل',
				'selector' => '.steps',
				'kinds'    => [ 'gap', 'margin' ],
			],
			'card'       => self::box_style( 'کارت مرحله', '.steps article', [ 'border_color_hover', 'bg_hover', 'align' ] ),
			'number'     => [
				'label'    => 'شماره مرحله',
				'selector' => '.steps article > b',
				'kinds'    => [ 'typography', 'color', 'bg', 'border_color', 'radius', 'width', 'height' ],
			],
			'step_icon'  => self::icon_style( 'آیکن مرحله', '.steps article > b svg' ),
			'step_title' => self::text_style( 'عنوان مرحله', '.steps article h3' ),
			'step_text'  => self::text_style( 'توضیح مرحله', '.steps article p' ),
			'separator'  => [
				'label'    => 'نماد بین مراحل',
				'selector' => '.steps > i',
				'kinds'    => [ 'typography', 'color', 'hide' ],
			],
			'process'    => [
				'label'    => 'متن فرایند',
				'selector' => '.process',
				'kinds'    => [ 'typography', 'color', 'background', 'radius', 'padding', 'margin', 'max_width', 'align' ],
			],
		];
	}

	protected function render_html( array $s ) {
		$parts = [];
		foreach ( self::rows( $s, 'steps' ) as $step ) {
			$parts[] = '<article><b>' . self::icon( $step['icon'] ?? [], self::t( self::v( $step, 'number' ) ) ) . '</b><h3>' . self::t( self::v( $step, 'title' ) ) . '</h3><p>' . self::t( self::v( $step, 'text' ) ) . '</p></article>';
		}
		$separator = '<i>' . self::t( self::v( $s, 'separator' ) ) . "</i>\n          ";
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="roadmap dark-section section-pad">
      <div class="container"><div class="section-title light"><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><h3><span><?php echo self::t( self::v( $s, 'subtitle' ) ); ?></span></h3></div>
        <div class="steps">
          <?php echo implode( $separator, $parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        </div>
<?php if ( 'yes' === self::v( $s, 'show_process' ) ) : ?>
        <p class="process"><?php echo self::t( self::v( $s, 'process' ) ); ?></p>
<?php endif; ?>
      </div>
    </section>
		<?php
	}
}
