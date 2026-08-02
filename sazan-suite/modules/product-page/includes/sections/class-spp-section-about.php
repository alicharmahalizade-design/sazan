<?php
/**
 * سکشن ۲ — «درباره دوره» + «این دوره برای شماست اگر...».
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_About implements SPP_Section {

	public function id() {
		return 'about';
	}

	public function title() {
		return 'سکشن ۲ — درباره دوره';
	}

	public function icon() {
		return 'eicon-info-circle-o';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {
		return array(

			/* --- گروه: درباره دوره --------------------------------------- */
			'g_about' => array(
				'type'  => 'group',
				'label' => 'درباره دوره (ستون راست)',
			),
			'about_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان',
				'default' => 'درباره دوره',
			),
			'about_text' => array(
				'type'    => 'textarea',
				'label'   => 'متن معرفی',
				'class'   => 'spp-f--tall',
				'hint'    => 'یک خطِ خالی بین پاراگراف‌ها بگذار تا جدا شوند.',
				'default' => "استراتژی طلایی فروش، یک مسیر کامل و عملی برای تبدیل شدن به یک فروشنده حرفه‌ای است.\n\nدر این دوره، با تکنیک‌های اثبات‌شده فروش، روانشناسی مشتری، ساختار فروش و مدیریت تیم فروش آشنا می‌شوید و مهارت‌هایی را به دست می‌آورید که مستقیماً در افزایش درآمد شما تأثیر دارد.",
			),
			'image' => array(
				'type'  => 'image',
				'label' => 'تصویر تزئینی (اختیاری — فقط این دوره)',
				'hint'  => 'خالی بگذار تا تصویر سراسری بیاید (محصولات ← <strong>صفحه اختصاصی محصول</strong>). فقط اگر این دوره باید تصویر متفاوتی داشته باشد اینجا انتخاب کن.',
			),
			'divider' => array(
				'type'    => 'select',
				'label'   => 'خط جداکننده‌ی دو ستون',
				'default' => 'yes',
				'options' => array(
					'yes' => 'نمایش داده شود',
					'no'  => 'حذف شود',
				),
			),

			/* --- گروه: مناسب برای ---------------------------------------- */
			'g_fit' => array(
				'type'  => 'group',
				'label' => 'این دوره برای شماست اگر... (ستون چپ)',
			),
			'fit_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان',
				'default' => 'این دوره برای شماست اگر...',
			),
			'fit_items' => array(
				'type'      => 'repeater',
				'label'     => 'موارد',
				'row_label' => 'مورد',
				'max'       => 8,
				'fields'    => array(
					'icon' => array(
						'type'    => 'icon',
						'label'   => 'آیکن',
						'default' => 'level',
					),
					'text' => array(
						'type'  => 'text',
						'label' => 'متن',
					),
				),
				'default' => array(
					array(
						'icon' => 'level',
						'text' => 'می‌خواهید فروش خود را چند برابر کنید',
					),
					array(
						'icon' => 'update',
						'text' => 'به دنبال سیستم فروش قابل اعتماد هستید',
					),
					array(
						'icon' => 'user',
						'text' => 'مدیر یا صاحب کسب‌وکار هستید',
					),
					array(
						'icon' => 'community',
						'text' => 'می‌خواهید تیم فروش حرفه‌ای بسازید',
					),
				),
			),
		);
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	public function render( $post_id, $d ) {

		$body = spp_paragraphs( $d['about_text'] );

		// تصویر: اگر برای این محصول انتخاب نشده، از تنظیمات سراسری می‌آید.
		$fig_id  = function_exists( 'spp_image_id' ) ? spp_image_id( $d['image'], 'about_image' ) : (int) $d['image'];
		$has_fig = $fig_id > 0;
		$classes = 'spp-about__card';

		if ( 'no' === $d['divider'] ) {
			$classes .= ' spp-about__card--nodiv';
		}
		if ( ! $has_fig ) {
			$classes .= ' spp-about__card--nofig';
		}
		?>
		<section class="spp spp-about" dir="rtl">
			<div class="spp-wrap">
				<div class="<?php echo esc_attr( $classes ); ?>">
					<div class="spp-about__glow" aria-hidden="true"></div>

					<?php /* ستون راست: معرفی + تصویر تزئینی */ ?>
					<div class="spp-about__intro">
						<?php if ( $has_fig ) : ?>
							<div class="spp-about__fig">
								<?php
								echo wp_get_attachment_image(
									$fig_id,
									'medium_large',
									false,
									array(
										'class' => 'spp-about__img',
										'alt'   => '',
									)
								);
								?>
							</div>
						<?php endif; ?>

						<div class="spp-about__txt">
							<?php if ( '' !== $d['about_title'] ) : ?>
								<h2 class="spp-about__h"><?php echo esc_html( $d['about_title'] ); ?></h2>
							<?php endif; ?>

							<?php if ( '' !== $body ) : ?>
								<div class="spp-about__body"><?php echo wp_kses_post( $body ); ?></div>
							<?php endif; ?>
						</div>
					</div>

					<?php /* ستون چپ: مناسب برای */ ?>
					<div class="spp-about__fit">
						<?php if ( '' !== $d['fit_title'] ) : ?>
							<h3 class="spp-about__h spp-about__h--fit"><?php echo esc_html( $d['fit_title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $d['fit_items'] ) ) : ?>
							<ul class="spp-fit">
								<?php foreach ( $d['fit_items'] as $row ) : ?>
									<?php if ( empty( $row['text'] ) ) { continue; } ?>
									<li class="spp-fit__i">
										<span class="spp-fit__ic"><?php echo spp_icon( $row['icon'] ?? 'check', 17 ); ?></span>
										<span class="spp-fit__tx"><?php echo esc_html( $row['text'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
