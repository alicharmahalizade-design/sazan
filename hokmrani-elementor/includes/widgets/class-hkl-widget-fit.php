<?php
/**
 * "Hokmrani is not for everyone" – who it is / isn't for.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Fit extends HKL_Widget_Base {

	protected static function slug() {
		return 'fit';
	}

	protected static function title() {
		return 'حکمرانی — برای چه کسانی است';
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	protected static function fields() {
		return [
			'intro' => [
				'label'    => 'عنوان بخش',
				'controls' => [
					'title' => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'حکمرانی بر بازار برای همه نیست' ],
				],
			],
			'yes'   => [
				'label'    => 'مناسب است',
				'controls' => [
					'yes_title' => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'این برنامه برای شماست اگر:' ],
					'yes_items' => [ 'type' => 'textarea', 'label' => 'موارد (هر خط یک مورد)', 'rows' => 6, 'default' => "فروش دارید اما از نتیجه فعلی راضی نیستید.\nتیم دارید و می‌خواهید عملکردش بهتر شود.\nمی‌دانید کار و کسبتان ظرفیت رشد بیشتری دارد.\nبرای تغییر، تصمیم و اجرای جدی آماده هستید." ],
				],
			],
			'no'    => [
				'label'    => 'مناسب نیست',
				'controls' => [
					'no_title' => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'احتمالاً این برنامه برای شما نیست اگر:' ],
					'no_items' => [ 'type' => 'textarea', 'label' => 'موارد (هر خط یک مورد)', 'rows' => 6, 'default' => "به دنبال فرمول یک‌شبه فروش هستید.\nنمی‌خواهید برای کار و کسبتان وقت بگذارید.\nانتظار دارید بدون تغییر، نتیجه بزرگ بگیرید.\nبه دنبال راه‌حل‌های مقطعی و کوتاه‌مدت هستید." ],
				],
			],
		];
	}

	private static function list_html( $value ) {
		$html = '';
		foreach ( self::lines( $value ) as $line ) {
			$html .= '<li>' . self::t( $line ) . '</li>';
		}
		return $html;
	}

	protected function render_html( array $s ) {
		?>
<section class="fit white-section section-pad"><div class="container"><div class="section-title"><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2></div><div class="fit-grid">
      <article class="yes"><h3><?php echo self::t( self::v( $s, 'yes_title' ) ); ?></h3><ul><?php echo self::list_html( self::v( $s, 'yes_items' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul></article>
      <article class="no"><h3><?php echo self::t( self::v( $s, 'no_title' ) ); ?></h3><ul><?php echo self::list_html( self::v( $s, 'no_items' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul></article>
    </div></div></section>
		<?php
	}
}
