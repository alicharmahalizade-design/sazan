<?php
/**
 * سکشن ۴ — معرفی مدرس.
 *
 * تصویر مدرس عمداً از لبه‌ی بالای کارت بیرون می‌زند: به کفِ کارت لنگر شده و
 * ارتفاعش به اندازه‌ی --spp-inst-out بیشتر از سلولِ خودش است. پس کارت نباید
 * overflow: hidden داشته باشد.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_Instructor implements SPP_Section {

	public function id() {
		return 'instructor';
	}

	public function title() {
		return 'سکشن ۴ — معرفی مدرس';
	}

	public function icon() {
		return 'eicon-user-circle-o';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {
		return array(

			/* --- گروه: مدرس ---------------------------------------------- */
			'g_inst' => array(
				'type'  => 'group',
				'label' => 'مدرس',
			),
			'eyebrow' => array(
				'type'    => 'text',
				'label'   => 'برچسب بالای نام',
				'default' => 'مدرس دوره',
			),
			'name' => array(
				'type'    => 'text',
				'label'   => 'نام مدرس',
				'class'   => 'spp-f--lg',
				'default' => 'عباس شنبه سازان',
			),
			'role' => array(
				'type'    => 'text',
				'label'   => 'عنوان شغلی',
				'default' => 'مشاور و مدرس استراتژی فروش و بازاریابی',
			),
			'image' => array(
				'type'  => 'image',
				'label' => 'عکس مدرس (اختیاری — فقط این دوره)',
				'hint'  => 'خالی بگذار تا عکس سراسری بیاید (محصولات ← <strong>صفحه اختصاصی محصول</strong>). PNG با پس‌زمینه‌ی حذف‌شده، نیم‌تنه، تا سر کامل دیده شود.',
			),

			/* --- گروه: آمار ---------------------------------------------- */
			'g_stats' => array(
				'type'  => 'group',
				'label' => 'آمار (زیر نام مدرس)',
			),
			'stats' => array(
				'type'      => 'repeater',
				'label'     => 'آمار',
				'row_label' => 'آمار',
				'max'       => 5,
				'fields'    => array(
					'value' => array(
						'type'  => 'text',
						'label' => 'عدد',
					),
					'label' => array(
						'type'  => 'text',
						'label' => 'برچسب',
					),
				),
				'default' => array(
					array(
						'value' => '۲۹,۰۰۰+',
						'label' => 'ساعت تدریس',
					),
					array(
						'value' => '۵۰۰+',
						'label' => 'پروژه موفق',
					),
					array(
						'value' => '۱۴+',
						'label' => 'سال تجربه',
					),
				),
			),

			/* --- گروه: سوابق --------------------------------------------- */
			'g_points' => array(
				'type'  => 'group',
				'label' => 'سوابق (ستون چپ)',
			),
			'points' => array(
				'type'      => 'repeater',
				'label'     => 'موارد',
				'row_label' => 'مورد',
				'max'       => 8,
				'fields'    => array(
					'icon' => array(
						'type'    => 'icon',
						'label'   => 'آیکن',
						'default' => 'star',
					),
					'text' => array(
						'type'  => 'text',
						'label' => 'متن',
					),
				),
				'default' => array(
					array(
						'icon' => 'certificate',
						'text' => 'مشاور برندهای معتبر در حوزه فروش',
					),
					array(
						'icon' => 'teacher',
						'text' => 'مدرس دوره‌های فروش در سازمان‌ها',
					),
					array(
						'icon' => 'content',
						'text' => 'نویسنده مقالات تخصصی فروش و بازاریابی',
					),
					array(
						'icon' => 'star',
						'text' => 'دارای گواهینامه‌های بین‌المللی فروش',
					),
				),
			),
		);
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	public function render( $post_id, $d ) {

		$img_id = function_exists( 'spp_image_id' ) ? spp_image_id( $d['image'], 'inst_image' ) : (int) $d['image'];
		?>
		<section class="spp spp-inst" dir="rtl">
			<div class="spp-wrap">
				<div class="spp-inst__card<?php echo $img_id ? '' : ' spp-inst__card--nofig'; ?>">
					<div class="spp-inst__glow" aria-hidden="true"></div>

					<?php /* ---------- عکس مدرس (راست، از بالا بیرون می‌زند) ---------- */ ?>
					<?php if ( $img_id ) : ?>
						<div class="spp-inst__fig">
							<?php
							echo wp_get_attachment_image(
								$img_id,
								'medium_large',
								false,
								array(
									'class' => 'spp-inst__img',
									'alt'   => esc_attr( $d['name'] ),
								)
							);
							?>
						</div>
					<?php endif; ?>

					<?php /* ---------- نام و آمار ---------- */ ?>
					<div class="spp-inst__main">
						<?php if ( '' !== $d['eyebrow'] ) : ?>
							<span class="spp-inst__eyebrow"><?php echo esc_html( $d['eyebrow'] ); ?></span>
						<?php endif; ?>

						<?php if ( '' !== $d['name'] ) : ?>
							<h2 class="spp-inst__name"><?php echo esc_html( $d['name'] ); ?></h2>
						<?php endif; ?>

						<?php if ( '' !== $d['role'] ) : ?>
							<p class="spp-inst__role"><?php echo esc_html( $d['role'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $d['stats'] ) ) : ?>
							<ul class="spp-inst__stats">
								<?php foreach ( $d['stats'] as $row ) : ?>
									<?php if ( empty( $row['value'] ) && empty( $row['label'] ) ) { continue; } ?>
									<li class="spp-stat">
										<strong class="spp-stat__v"><?php echo esc_html( $row['value'] ); ?></strong>
										<em class="spp-stat__l"><?php echo esc_html( $row['label'] ); ?></em>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>

					<?php /* ---------- سوابق (چپ) ---------- */ ?>
					<?php if ( ! empty( $d['points'] ) ) : ?>
						<ul class="spp-inst__points">
							<?php foreach ( $d['points'] as $row ) : ?>
								<?php if ( empty( $row['text'] ) ) { continue; } ?>
								<li class="spp-point">
									<span class="spp-point__ic"><?php echo spp_icon( $row['icon'] ?? 'star', 15 ); ?></span>
									<span class="spp-point__t"><?php echo esc_html( $row['text'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
}
