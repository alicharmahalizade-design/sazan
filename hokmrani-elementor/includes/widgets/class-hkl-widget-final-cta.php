<?php
/**
 * Final call to action ("request a business review").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Final_Cta extends HKL_Widget_Base {

	protected static function slug() {
		return 'final-cta';
	}

	protected static function title() {
		return 'حکمرانی — دعوت به اقدام پایانی';
	}

	public function get_icon() {
		return 'eicon-button';
	}

	protected static function fields() {
		return [
			'content' => [
				'label'    => 'محتوا',
				'controls' => [
					'section_id' => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'assessment' ],
					'kicker'     => [ 'type' => 'text', 'label' => 'متن بالا', 'default' => 'حالا سؤال این است که آیا این دوره خوب است؟' ],
					'title'      => [ 'type' => 'textarea', 'label' => 'تیتر (هر خط یک سطر)', 'rows' => 3, 'default' => "سؤال این است: آیا کار و کسب شما واقعاً ظرفیت بیشتری\nاز چیزی که امروز به دست می‌آورد دارد؟" ],
					'text'       => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'اگر جواب شما مثبت است، بیایید قبل از هر تصمیمی کار و کسبتان را بررسی کنیم.' ],
					'btn_text'   => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'درخواست بررسی کار و کسب' ],
					'btn_link'   => [ 'type' => 'link', 'label' => 'لینک دکمه', 'default' => '#contact' ],
					'btn_arrow'  => [ 'type' => 'text', 'label' => 'نماد دکمه', 'default' => '←' ],
					'btn_icon'   => [ 'type' => 'icon', 'label' => 'آیکن دکمه' ],
					'btn_form'   => [ 'type' => 'switcher', 'label' => 'دکمه فرم پیش‌ثبت‌نام را باز کند', 'default' => '' ],
					'notes'      => [ 'type' => 'textarea', 'label' => 'نکات زیر دکمه (هر خط یک مورد)', 'rows' => 4, 'default' => "گفت‌وگو برای شناخت کار و کسب\nارزیابی اولیه رایگان و شفاف\nبدون هیچ تعهدی" ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section' => self::section_style( '.final-cta' ),
			'content' => [
				'label'    => 'محتوا',
				'selector' => '.final-cta > .container',
				'kinds'    => [ 'max_width', 'align', 'padding' ],
			],
			'kicker'  => self::text_style( 'متن بالا', '.final-cta .container > p:first-child', [ 'hide' ] ),
			'title'   => self::text_style( 'تیتر', '.final-cta h2' ),
			'text'    => self::text_style( 'توضیح', '.final-cta h2 + p' ),
			'button'  => self::button_style( 'دکمه', '.final-cta .btn' ),
			'notes'   => [
				'label'    => 'چیدمان نکات',
				'selector' => '.cta-notes',
				'kinds'    => [ 'gap', 'margin', 'hide' ],
			],
			'note'    => self::text_style( 'نکته‌ها', '.cta-notes span', [ 'bg', 'radius', 'padding' ] ),
		];
	}

	protected function render_html( array $s ) {
		$title = implode( '<br>', array_map( 'esc_html', self::lines( self::v( $s, 'title' ) ) ) );
		$notes = '';
		foreach ( self::lines( self::v( $s, 'notes' ) ) as $note ) {
			$notes .= '<span>' . self::t( $note ) . '</span>';
		}
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="final-cta"><div class="container"><p><?php echo self::t( self::v( $s, 'kicker' ) ); ?></p><h2><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p><?php if ( 'yes' === self::v( $s, 'btn_form' ) ) : ?><button class="btn" type="button" data-open-enrollment><?php echo self::t( self::v( $s, 'btn_text' ) ); ?> <span><?php echo self::icon( $s['btn_icon'] ?? [], self::t( self::v( $s, 'btn_arrow' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></button><?php else : ?><a class="btn"<?php echo self::href( self::arr( $s, 'btn_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $s, 'btn_text' ) ); ?> <span><?php echo self::icon( $s['btn_icon'] ?? [], self::t( self::v( $s, 'btn_arrow' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></a><?php endif; ?><div class="cta-notes"><?php echo $notes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div></section>
		<?php
	}
}
