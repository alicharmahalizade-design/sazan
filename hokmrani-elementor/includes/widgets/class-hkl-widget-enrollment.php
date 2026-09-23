<?php
/**
 * Sticky enrollment bar, pre-registration modal form and the back-to-top button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Enrollment extends HKL_Widget_Base {

	protected static function slug() {
		return 'enrollment';
	}

	protected static function title() {
		return 'حکمرانی — نوار و فرم پیش‌ثبت‌نام';
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	protected static function fields() {
		return [
			'bar'     => [
				'label'    => 'نوار ثابت پایین صفحه',
				'controls' => [
					'show_bar'    => [ 'type' => 'switcher', 'label' => 'نمایش نوار ثابت', 'default' => 'yes' ],
					'bar_label'   => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری نوار', 'default' => 'پیش ثبت‌نام دوره' ],
					'price_label' => [ 'type' => 'text', 'label' => 'برچسب قیمت', 'default' => 'سرمایه‌گذاری دوره' ],
					'price'       => [ 'type' => 'text', 'label' => 'قیمت', 'default' => '۱۶۹٬۵۰۰٬۰۰۰' ],
					'currency'    => [ 'type' => 'text', 'label' => 'واحد پول', 'default' => 'تومان' ],
					'bar_button'  => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'پیش‌ثبت‌نام' ],
					'show_top'    => [ 'type' => 'switcher', 'label' => 'دکمه بازگشت به بالا', 'default' => 'yes' ],
				],
			],
			'form'    => [
				'label'    => 'فرم پیش‌ثبت‌نام',
				'controls' => [
					'eyebrow'   => [ 'type' => 'text', 'label' => 'برچسب بالا', 'default' => 'گام اول برای یک تصمیم مطمئن' ],
					'title'     => [ 'type' => 'text', 'label' => 'تیتر فرم', 'default' => 'فرم پیش‌ثبت‌نام' ],
					'text'      => [ 'type' => 'textarea', 'label' => 'توضیح', 'default' => 'اطلاعات اولیه را وارد کنید تا همکاران ما برای هماهنگی و ارائه جزئیات با شما تماس بگیرند.' ],
					'name_label'  => [ 'type' => 'text', 'label' => 'برچسب نام', 'default' => 'نام و نام خانوادگی' ],
					'name_ph'     => [ 'type' => 'text', 'label' => 'راهنمای نام', 'default' => 'مثلاً علی محمدی' ],
					'phone_label' => [ 'type' => 'text', 'label' => 'برچسب تلفن', 'default' => 'شماره تلفن' ],
					'phone_ph'    => [ 'type' => 'text', 'label' => 'راهنمای تلفن', 'default' => '0912 123 4567' ],
					'job_label'   => [ 'type' => 'text', 'label' => 'برچسب شغل', 'default' => 'شغل / سمت' ],
					'job_ph'      => [ 'type' => 'text', 'label' => 'راهنمای شغل', 'default' => 'مثلاً مدیرعامل' ],
					'staff_label' => [ 'type' => 'text', 'label' => 'برچسب تعداد پرسنل', 'default' => 'تعداد پرسنل' ],
					'staff_ph'    => [ 'type' => 'text', 'label' => 'راهنمای تعداد پرسنل', 'default' => 'مثلاً ۲۰' ],
					'submit'      => [ 'type' => 'text', 'label' => 'متن دکمه ارسال', 'default' => 'ثبت درخواست پیش‌ثبت‌نام' ],
					'privacy'     => [ 'type' => 'text', 'label' => 'متن حریم خصوصی', 'default' => 'اطلاعات شما محرمانه می‌ماند و فقط برای هماهنگی دوره استفاده می‌شود.' ],
					'error'       => [ 'type' => 'text', 'label' => 'پیام خطا', 'default' => 'لطفاً همه اطلاعات را به‌درستی تکمیل کنید.' ],
				],
			],
			'success' => [
				'label'    => 'پیام موفقیت',
				'controls' => [
					'success_title'  => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'درخواست شما ثبت شد' ],
					'success_text'   => [ 'type' => 'text', 'label' => 'متن', 'default' => 'برای هماهنگی اولیه با شما تماس می‌گیریم.' ],
					'success_button' => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'متوجه شدم' ],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		?>
<?php if ( 'yes' === self::v( $s, 'show_top' ) ) : ?>
<button class="back-to-top" type="button" aria-label="بازگشت به بالای صفحه"><span>↑</span></button>
<?php endif; ?>
<?php if ( 'yes' === self::v( $s, 'show_bar' ) ) : ?>
  <aside class="enrollment-bar" aria-label="<?php echo self::a( self::v( $s, 'bar_label' ) ); ?>">
    <div class="enrollment-bar__inner">
      <div class="enrollment-price"><span><?php echo self::t( self::v( $s, 'price_label' ) ); ?></span><strong><bdi><?php echo self::t( self::v( $s, 'price' ) ); ?></bdi> <small><?php echo self::t( self::v( $s, 'currency' ) ); ?></small></strong></div>
      <button class="enrollment-open" type="button" data-open-enrollment><?php echo self::t( self::v( $s, 'bar_button' ) ); ?> <span aria-hidden="true">←</span></button>
    </div>
  </aside>
<?php endif; ?>

  <div class="enrollment-modal" data-enrollment-modal hidden>
    <div class="enrollment-backdrop" data-close-enrollment></div>
    <section class="enrollment-dialog" role="dialog" aria-modal="true" aria-labelledby="enrollment-title" tabindex="-1">
      <button class="enrollment-close" type="button" data-close-enrollment aria-label="بستن فرم">×</button>
      <div class="enrollment-dialog__head">
        <span class="enrollment-eyebrow"><?php echo self::t( self::v( $s, 'eyebrow' ) ); ?></span>
        <h2 id="enrollment-title"><?php echo self::t( self::v( $s, 'title' ) ); ?></h2>
        <p><?php echo self::t( self::v( $s, 'text' ) ); ?></p>
      </div>
      <form class="enrollment-form" novalidate data-error="<?php echo self::a( self::v( $s, 'error' ) ); ?>">
        <label><span><?php echo self::t( self::v( $s, 'name_label' ) ); ?></span><input name="fullName" type="text" autocomplete="name" placeholder="<?php echo self::a( self::v( $s, 'name_ph' ) ); ?>" required></label>
        <label><span><?php echo self::t( self::v( $s, 'phone_label' ) ); ?></span><input name="phone" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr" placeholder="<?php echo self::a( self::v( $s, 'phone_ph' ) ); ?>" pattern="09[0-9]{9}" required></label>
        <label><span><?php echo self::t( self::v( $s, 'job_label' ) ); ?></span><input name="job" type="text" autocomplete="organization-title" placeholder="<?php echo self::a( self::v( $s, 'job_ph' ) ); ?>" required></label>
        <label><span><?php echo self::t( self::v( $s, 'staff_label' ) ); ?></span><input name="staffCount" type="number" inputmode="numeric" min="1" max="100000" placeholder="<?php echo self::a( self::v( $s, 'staff_ph' ) ); ?>" required></label>
        <p class="enrollment-error" role="alert" aria-live="polite"></p>
        <button class="enrollment-submit" type="submit"><?php echo self::t( self::v( $s, 'submit' ) ); ?> <span aria-hidden="true">←</span></button>
        <small class="enrollment-privacy"><?php echo self::t( self::v( $s, 'privacy' ) ); ?></small>
      </form>
      <div class="enrollment-success" aria-live="polite" hidden><span>✓</span><h3><?php echo self::t( self::v( $s, 'success_title' ) ); ?></h3><p><?php echo self::t( self::v( $s, 'success_text' ) ); ?></p><button type="button" data-close-enrollment><?php echo self::t( self::v( $s, 'success_button' ) ); ?></button></div>
    </section>
  </div>
		<?php
	}
}
