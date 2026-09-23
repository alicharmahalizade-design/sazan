<?php
/**
 * Guarantee message + FAQ accordion.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Guarantee extends HKL_Widget_Base {

	protected static function slug() {
		return 'guarantee';
	}

	protected static function title() {
		return 'حکمرانی — تضمین و سوالات متداول';
	}

	public function get_icon() {
		return 'eicon-help-o';
	}

	protected static function fields() {
		return [
			'guarantee' => [
				'label'    => 'تضمین',
				'controls' => [
					'section_id'   => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => '' ],
					'shield_text'  => [ 'type' => 'text', 'label' => 'نماد داخل سپر', 'default' => '✓' ],
					'shield_icon'  => [ 'type' => 'icon', 'label' => 'آیکن داخل سپر' ],
					'title'    => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'نتیجه برای ما مهم است.' ],
					'text'     => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'ما کنار شما می‌مانیم تا به نتیجه برسید.' ],
					'btn_text' => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'مشاهده شرایط کامل تضمین' ],
					'btn_link' => [ 'type' => 'link', 'label' => 'لینک دکمه', 'default' => '#contact' ],
					'show_btn' => [ 'type' => 'switcher', 'label' => 'نمایش دکمه', 'default' => 'yes' ],
				],
			],
			'faq'       => [
				'label'    => 'سوالات متداول',
				'controls' => [
					'faq_id'    => [ 'type' => 'text', 'label' => 'شناسه بخش سوالات (Anchor)', 'default' => 'faq' ],
					'show_faq'  => [ 'type' => 'switcher', 'label' => 'نمایش سوالات متداول', 'default' => 'yes' ],
					'faq_title' => [ 'type' => 'text', 'label' => 'تیتر سوالات', 'default' => 'اگر برای کار و کسب من جواب نداد چه؟' ],
					'faqs'      => [
						'type'        => 'repeater',
						'label'       => 'سوالات',
						'title_field' => '{{{ question }}}',
						'fields'      => [
							'question' => [ 'type' => 'text', 'label' => 'سؤال', 'default' => 'سؤال' ],
							'answer'   => [ 'type' => 'textarea', 'label' => 'پاسخ', 'default' => '' ],
						],
						'default'     => [
							[ 'question' => 'چه چیزی دقیقاً تضمین می‌شود؟', 'answer' => 'کیفیت آموزش، جلسات راهبری و همراهی در اجرای برنامه طبق شرایط اعلام‌شده تضمین می‌شود.' ],
							[ 'question' => 'مبنای اندازه‌گیری چیست؟', 'answer' => 'شاخص‌های فروش و عملکرد، پیش از شروع ثبت و در طول برنامه پایش می‌شوند.' ],
							[ 'question' => 'دوره از آغاز تا اجرا چقدر طول می‌کشد؟', 'answer' => 'برنامه اصلی ۱۴ هفته است و مسیر پیگیری متناسب با نیاز کار و کسب ادامه پیدا می‌کند.' ],
							[ 'question' => 'چه تیم‌هایی از طرف شرکت‌کننده حضور دارند؟', 'answer' => 'مدیرعامل، مدیر فروش و اعضای کلیدی تیم بر اساس مسئله اصلی سازمان مشارکت می‌کنند.' ],
						],
					],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'   => self::section_style( '.guarantee' ),
			'layout'    => [
				'label'    => 'چیدمان',
				'selector' => '.guarantee-grid',
				'kinds'    => [ 'gap', 'max_width' ],
			],
			'shield'    => [
				'label'    => 'سپر',
				'selector' => '.shield',
				'kinds'    => [ 'typography', 'icon_color', 'background', 'width', 'height', 'hide' ],
			],
			'shield_icon' => self::icon_style( 'آیکن داخل سپر', '.shield svg' ),
			'title'     => self::text_style( 'تیتر', '.guarantee-grid > div:nth-child(2) h2' ),
			'text'      => self::text_style( 'توضیح', '.guarantee-grid > div:nth-child(2) p' ),
			'button'    => self::button_style( 'دکمه', '.guarantee .btn' ),
			'faq'       => self::box_style( 'کادر سوالات', '.faq' ),
			'faq_title' => self::text_style( 'تیتر سوالات', '.faq h2' ),
			'faq_item'  => self::box_style( 'سؤال', '.faq details', [ 'border_color_hover', 'margin' ] ),
			'faq_open'  => [
				'label'    => 'سؤال باز',
				'selector' => '.faq details[open]',
				'kinds'    => [ 'background', 'border_color', 'shadow' ],
			],
			'question'  => [
				'label'    => 'متن سؤال',
				'selector' => '.faq summary',
				'kinds'    => [ 'typography', 'color', 'color_hover', 'padding' ],
			],
			'toggle'    => [
				'label'    => 'نماد باز و بسته',
				'selector' => '.faq summary:after',
				'kinds'    => [ 'typography', 'color', 'hide' ],
			],
			'answer'    => self::text_style( 'پاسخ', '.faq details p', [ 'padding' ] ),
		];
	}

	protected function render_html( array $s ) {
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="guarantee white-section section-pad">
      <div class="container guarantee-grid"><div class="shield" aria-hidden="true"><?php echo self::icon( $s['shield_icon'] ?? [], self::t( self::v( $s, 'shield_text' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p><?php if ( 'yes' === self::v( $s, 'show_btn' ) ) : ?><a class="btn blue"<?php echo self::href( self::arr( $s, 'btn_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $s, 'btn_text' ) ); ?></a><?php endif; ?></div>
<?php if ( 'yes' === self::v( $s, 'show_faq' ) ) : ?>
      <div<?php echo self::id_attr( self::v( $s, 'faq_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="faq"><h2><?php echo self::t( self::v( $s, 'faq_title' ) ); ?></h2>
<?php foreach ( self::rows( $s, 'faqs' ) as $faq ) : ?>
        <details><summary><?php echo self::t( self::v( $faq, 'question' ) ); ?></summary><p><?php echo self::t( self::v( $faq, 'answer' ) ); ?></p></details>
<?php endforeach; ?>
      </div>
<?php endif; ?>
      </div>
    </section>
		<?php
	}
}
