<?php
/**
 * سکشن ۷ — سوالات متداول.
 *
 * سوال‌ها به دو ستونِ مستقل تقسیم می‌شوند (هر ستون یک لیست جدا) تا باز شدن یک
 * سوال، چیدمانِ ستون کناری را جابه‌جا نکند.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_FAQ implements SPP_Section {

	public function id() {
		return 'faq';
	}

	public function title() {
		return 'سکشن ۷ — سوالات متداول';
	}

	public function icon() {
		return 'eicon-help-o';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {

		$todo = 'پاسخ این سوال را از متاباکس «صفحه اختصاصی محصول» ← تب سکشن ۷ بنویسید.';

		return array(

			'g_faq' => array(
				'type'  => 'group',
				'label' => 'سوالات متداول',
			),
			'title' => array(
				'type'    => 'text',
				'label'   => 'عنوان سکشن',
				'class'   => 'spp-f--lg',
				'default' => 'سوالات متداول',
			),
			'cols' => array(
				'type'    => 'select',
				'label'   => 'تعداد ستون',
				'default' => '2',
				'options' => array(
					'2' => '۲ ستون',
					'1' => '۱ ستون',
				),
			),
			'image' => array(
				'type'  => 'image',
				'label' => 'تصویر تزئینی (اختیاری)',
				'hint'  => 'خالی بگذار تا تصویر سراسری بیاید؛ اگر آن هم خالی باشد، علامت «؟» گرافیکی نمایش داده می‌شود.',
			),
			'items' => array(
				'type'      => 'repeater',
				'label'     => 'سوال‌ها',
				'row_label' => 'سوال',
				'max'       => 30,
				'hint'      => 'ترتیب: ستون راست از بالا به پایین، بعد ستون چپ. اگر پاسخ خالی باشد، ردیف بازشو نمی‌شود.',
				'fields'    => array(
					'q' => array(
						'type'  => 'text',
						'label' => 'سوال',
					),
					'a' => array(
						'type'  => 'textarea',
						'label' => 'پاسخ',
					),
				),
				'default' => array(
					array(
						'q' => 'آیا بعد از خرید به صورت دائمی به دوره دسترسی دارم؟',
						'a' => $todo,
					),
					array(
						'q' => 'آیا دوره آپدیت و محتوای جدید دریافت می‌کند؟',
						'a' => $todo,
					),
					array(
						'q' => 'مدت زمان دسترسی به دوره چقدر است؟',
						'a' => $todo,
					),
					array(
						'q' => 'آیا گواهینامه پایان دوره معتبر است؟',
						'a' => $todo,
					),
					array(
						'q' => 'پشتیبانی دوره به چه صورت است؟',
						'a' => $todo,
					),
					array(
						'q' => 'شرایط بازگشت وجه چگونه است؟',
						'a' => $todo,
					),
				),
			),
		);
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	public function render( $post_id, $d ) {

		$items = array_values( array_filter( (array) $d['items'], function ( $r ) {
			return ! empty( $r['q'] );
		} ) );

		if ( empty( $items ) && '' === $d['title'] ) {
			return;
		}

		$cols   = '1' === $d['cols'] ? 1 : 2;
		$chunks = $items ? array_chunk( $items, (int) ceil( count( $items ) / $cols ) ) : array();
		$img_id = function_exists( 'spp_image_id' ) ? spp_image_id( $d['image'], 'faq_image' ) : (int) $d['image'];
		$n      = 0;
		?>
		<section class="spp spp-faq" dir="rtl">
			<div class="spp-wrap">
				<div class="spp-faq__card">

					<?php /* ستون راست: عنوان + تصویر تزئینی */ ?>
					<div class="spp-faq__aside">
						<?php if ( '' !== $d['title'] ) : ?>
							<h2 class="spp-faq__h"><?php echo esc_html( $d['title'] ); ?></h2>
						<?php endif; ?>

						<span class="spp-faq__deco" aria-hidden="true">
							<?php if ( $img_id ) : ?>
								<?php echo wp_get_attachment_image( $img_id, 'medium', false, array( 'class' => 'spp-faq__img', 'alt' => '' ) ); ?>
							<?php else : ?>
								<span class="spp-faq__mark">؟</span>
							<?php endif; ?>
						</span>
					</div>

					<?php /* ستون‌های سوال */ ?>
					<div class="spp-faq__cols" style="--spp-faq-cols:<?php echo (int) $cols; ?>">
						<?php foreach ( $chunks as $chunk ) : ?>
							<ul class="spp-faq__col">
								<?php foreach ( $chunk as $row ) : ?>
									<?php
									$n++;
									$has_a = ! empty( $row['a'] );
									?>
									<li class="spp-faq__i">
										<?php if ( $has_a ) : ?>
											<details class="spp-faq__d">
												<summary class="spp-faq__row">
													<span class="spp-faq__q"><?php echo esc_html( $row['q'] ); ?></span>
													<span class="spp-faq__chev" aria-hidden="true">
														<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
													</span>
												</summary>
												<div class="spp-faq__a"><?php echo wp_kses_post( spp_paragraphs( $row['a'] ) ); ?></div>
											</details>
										<?php else : ?>
											<div class="spp-faq__row spp-faq__row--plain">
												<span class="spp-faq__q"><?php echo esc_html( $row['q'] ); ?></span>
											</div>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
		<?php
	}
}
