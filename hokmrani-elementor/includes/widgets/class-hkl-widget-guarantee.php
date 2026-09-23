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
					'title'    => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'نتیجه برای ما مهم است.' ],
					'text'     => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'ما کنار شما می‌مانیم تا به نتیجه برسید.' ],
					'btn_text' => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'مشاهده شرایط کامل تضمین' ],
					'btn_link' => [ 'type' => 'link', 'label' => 'لینک دکمه', 'default' => '#contact' ],
				],
			],
			'faq'       => [
				'label'    => 'سوالات متداول',
				'controls' => [
					'faq_id'    => [ 'type' => 'text', 'label' => 'شناسه بخش سوالات (Anchor)', 'default' => 'faq' ],
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

	protected function render_html( array $s ) {
		?>
<section class="guarantee white-section section-pad">
      <div class="container guarantee-grid"><div class="shield" aria-hidden="true">✓</div><div><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2><p><?php echo self::t( self::v( $s, 'text' ) ); ?></p><a class="btn blue" href="<?php echo self::u( self::v( $s, 'btn_link' ) ); ?>"><?php echo self::t( self::v( $s, 'btn_text' ) ); ?></a></div>
      <div<?php echo self::id_attr( self::v( $s, 'faq_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="faq"><h2><?php echo self::t( self::v( $s, 'faq_title' ) ); ?></h2>
<?php foreach ( self::rows( $s, 'faqs' ) as $faq ) : ?>
        <details><summary><?php echo self::t( self::v( $faq, 'question' ) ); ?></summary><p><?php echo self::t( self::v( $faq, 'answer' ) ); ?></p></details>
<?php endforeach; ?>
      </div></div>
    </section>
		<?php
	}
}
