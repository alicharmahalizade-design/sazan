<?php
/**
 * سکشن ۳ — «ویژگی‌های دوره» + «سرفصل‌های دوره».
 *
 * سرفصل‌ها با <details>/<summary> ساخته می‌شوند: بدون جاوااسکریپت باز و بسته
 * می‌شوند و برای صفحه‌خوان‌ها هم درست کار می‌کنند.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_Curriculum implements SPP_Section {

	public function id() {
		return 'curriculum';
	}

	public function title() {
		return 'سکشن ۳ — ویژگی‌ها و سرفصل‌ها';
	}

	public function icon() {
		return 'eicon-accordion';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {
		return array(

			/* --- گروه: ویژگی‌های دوره ------------------------------------- */
			'g_feat' => array(
				'type'  => 'group',
				'label' => 'ویژگی‌های دوره (کارت راست)',
			),
			'feat_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان کارت',
				'default' => 'ویژگی‌های دوره',
			),
			'feat_cols' => array(
				'type'    => 'select',
				'label'   => 'تعداد ستون',
				'default' => '4',
				'options' => array(
					'4' => '۴ ستون',
					'3' => '۳ ستون',
					'2' => '۲ ستون',
				),
			),
			'feat_items' => array(
				'type'      => 'repeater',
				'label'     => 'کاشی‌های ویژگی',
				'row_label' => 'ویژگی',
				'max'       => 12,
				'fields'    => array(
					'icon'  => array(
						'type'    => 'icon',
						'label'   => 'آیکن',
						'default' => 'star',
					),
					'title' => array(
						'type'  => 'text',
						'label' => 'عنوان',
					),
					'desc'  => array(
						'type'  => 'text',
						'label' => 'توضیح کوتاه',
					),
				),
				'default' => array(
					array(
						'icon'  => 'update',
						'title' => 'آپدیت رایگان',
						'desc'  => 'محتوای جدید',
					),
					array(
						'icon'  => 'support',
						'title' => 'پشتیبانی اختصاصی',
						'desc'  => 'در تلگرام',
					),
					array(
						'icon'  => 'infinity',
						'title' => 'دسترسی دائمی',
						'desc'  => 'به تمام محتوا',
					),
					array(
						'icon'  => 'clock',
						'title' => '۸۵ ساعت آموزش',
						'desc'  => 'محتوای ویدیویی',
					),
					array(
						'icon'  => 'video',
						'title' => 'محتوای عملیاتی',
						'desc'  => 'با کیفیت بالا',
					),
					array(
						'icon'  => 'project',
						'title' => 'پروژه‌های عملی',
						'desc'  => 'پروژه‌های کاربردی',
					),
					array(
						'icon'  => 'download',
						'title' => 'تمرین‌های ضمیمه',
						'desc'  => 'جزوات و چک‌لیست‌ها',
					),
					array(
						'icon'  => 'certificate',
						'title' => 'گواهی معتبر',
						'desc'  => 'قابل استعلام',
					),
				),
			),

			/* --- گروه: سرفصل‌ها ------------------------------------------- */
			'g_syl' => array(
				'type'  => 'group',
				'label' => 'سرفصل‌های دوره (کارت چپ)',
			),
			'syl_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان کارت',
				'default' => 'سرفصل‌های دوره',
			),
			'syl_items' => array(
				'type'      => 'repeater',
				'label'     => 'سرفصل‌ها',
				'row_label' => 'سرفصل',
				'max'       => 30,
				'hint'      => 'اگر «توضیح» را پر کنی، آن سرفصل با کلیک باز می‌شود؛ خالی باشد فقط یک ردیف ساده است.',
				'fields'    => array(
					'title'    => array(
						'type'  => 'text',
						'label' => 'عنوان سرفصل',
					),
					'sessions' => array(
						'type'  => 'text',
						'label' => 'تعداد جلسه',
						'hint'  => 'مثال: ۴ جلسه',
					),
					'content'  => array(
						'type'  => 'textarea',
						'label' => 'توضیح (بازشو)',
					),
				),
				'default' => array(
					array(
						'title'    => 'مبانی استراتژی فروش',
						'sessions' => '۴ جلسه',
					),
					array(
						'title'    => 'شناخت بازار و مشتریان',
						'sessions' => '۱۰ جلسه',
					),
					array(
						'title'    => 'مذاکره و تکنیک‌های رفتاری پیشرفته',
						'sessions' => '۱۲ جلسه',
					),
					array(
						'title'    => 'مذاکره و متقاعدسازی حرفه‌ای',
						'sessions' => '۹ جلسه',
					),
					array(
						'title'    => 'مدیریت تیم فروش و عملکرد',
						'sessions' => '۷ جلسه',
					),
					array(
						'title'    => 'ابزارها، سیستم‌ها و اتوماسیون فروش',
						'sessions' => '۶ جلسه',
					),
					array(
						'title'    => 'تمرین نهایی و اجرای استراتژی',
						'sessions' => '۳ جلسه',
					),
				),
			),
			'syl_numbers' => array(
				'type'    => 'select',
				'label'   => 'شماره‌ی سرفصل‌ها',
				'default' => 'yes',
				'options' => array(
					'yes' => 'نمایش داده شود',
					'no'  => 'حذف شود',
				),
			),
			'syl_btn_text' => array(
				'type'    => 'text',
				'label'   => 'متن دکمه‌ی پایین',
				'default' => 'مشاهده همه سرفصل‌ها',
				'hint'    => 'خالی بگذار تا دکمه حذف شود.',
			),
			'syl_btn_link' => array(
				'type'  => 'text',
				'label' => 'لینک دکمه',
			),
		);
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	public function render( $post_id, $d ) {

		$cols    = in_array( $d['feat_cols'], array( '2', '3', '4' ), true ) ? $d['feat_cols'] : '4';
		$numbers = 'no' !== $d['syl_numbers'];
		?>
		<section class="spp spp-curr" dir="rtl">
			<div class="spp-wrap">
				<div class="spp-curr__grid">

					<?php /* ---------- کارت راست: ویژگی‌ها ---------- */ ?>
					<div class="spp-card spp-feat">
						<?php if ( '' !== $d['feat_title'] ) : ?>
							<h2 class="spp-card__h"><?php echo esc_html( $d['feat_title'] ); ?></h2>
						<?php endif; ?>

						<?php if ( ! empty( $d['feat_items'] ) ) : ?>
							<div class="spp-feat__grid" style="--spp-feat-cols:<?php echo (int) $cols; ?>">
								<?php foreach ( $d['feat_items'] as $row ) : ?>
									<?php if ( empty( $row['title'] ) ) { continue; } ?>
									<div class="spp-feat__i">
										<span class="spp-feat__ic"><?php echo spp_icon( $row['icon'] ?? 'star', 15 ); ?></span>
										<strong class="spp-feat__t"><?php echo esc_html( $row['title'] ); ?></strong>
										<?php if ( ! empty( $row['desc'] ) ) : ?>
											<em class="spp-feat__d"><?php echo esc_html( $row['desc'] ); ?></em>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php /* ---------- کارت چپ: سرفصل‌ها ---------- */ ?>
					<div class="spp-card spp-syl">
						<?php if ( '' !== $d['syl_title'] ) : ?>
							<h2 class="spp-card__h"><?php echo esc_html( $d['syl_title'] ); ?></h2>
						<?php endif; ?>

						<?php if ( ! empty( $d['syl_items'] ) ) : ?>
							<ul class="spp-syl__list">
								<?php $n = 0; ?>
								<?php foreach ( $d['syl_items'] as $row ) : ?>
									<?php
									if ( empty( $row['title'] ) ) {
										continue;
									}
									$n++;
									$has_body = ! empty( $row['content'] );
									?>
									<li class="spp-syl__i">
										<?php if ( $has_body ) : ?>
											<details class="spp-syl__d">
												<summary class="spp-syl__row">
													<?php $this->row_inner( $row, $n, $numbers, true ); ?>
												</summary>
												<div class="spp-syl__body"><?php echo wp_kses_post( spp_paragraphs( $row['content'] ) ); ?></div>
											</details>
										<?php else : ?>
											<div class="spp-syl__row spp-syl__row--plain">
												<?php $this->row_inner( $row, $n, $numbers, false ); ?>
											</div>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php if ( '' !== $d['syl_btn_text'] ) : ?>
							<a class="spp-btn spp-btn--outline spp-syl__btn" href="<?php echo esc_url( '' !== $d['syl_btn_link'] ? $d['syl_btn_link'] : '#' ); ?>">
								<?php echo esc_html( $d['syl_btn_text'] ); ?>
							</a>
						<?php endif; ?>
					</div>

				</div>
			</div>
		</section>
		<?php
	}

	/* =====================================================================
	 * کمکی
	 * =================================================================== */

	/**
	 * محتوای یک ردیف سرفصل.
	 *
	 * @param array $row
	 * @param int   $n
	 * @param bool  $numbers
	 * @param bool  $toggle
	 */
	private function row_inner( $row, $n, $numbers, $toggle ) {
		?>
		<?php if ( $numbers ) : ?>
			<span class="spp-syl__n"><?php echo esc_html( spp_fa_num( $n ) ); ?></span>
		<?php endif; ?>

		<span class="spp-syl__t"><?php echo esc_html( $row['title'] ); ?></span>

		<?php if ( ! empty( $row['sessions'] ) ) : ?>
			<span class="spp-syl__s"><?php echo esc_html( $row['sessions'] ); ?></span>
		<?php endif; ?>

		<?php if ( $toggle ) : ?>
			<span class="spp-syl__chev" aria-hidden="true">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
			</span>
		<?php endif; ?>
		<?php
	}
}
