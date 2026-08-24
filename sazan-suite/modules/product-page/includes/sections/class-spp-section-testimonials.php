<?php
/**
 * سکشن ۶ — «دانشجویان دوره چه می‌گویند؟» + دکمه‌ی «ثبت تجربه» با فرم پاپ‌آپ.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_Testimonials implements SPP_Section {

	public function id() {
		return 'testimonials';
	}

	public function title() {
		return 'سکشن ۶ — نظرات دانشجویان';
	}

	public function icon() {
		return 'eicon-testimonial';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {
		return array(

			'g_tst' => array(
				'type'  => 'group',
				'label' => 'نظرات',
			),
			'title' => array(
				'type'    => 'text',
				'label'   => 'عنوان سکشن',
				'class'   => 'spp-f--lg',
				'default' => 'دانشجویان دوره چه می‌گویند؟',
			),
			'source' => array(
				'type'    => 'select',
				'label'   => 'منبع نظرات',
				'default' => 'both',
				'options' => array(
					'both'   => 'هم دستی، هم نظرات تأییدشده',
					'manual' => 'فقط نظرهای دستیِ زیر',
					'woo'    => 'فقط نظرات تأییدشده‌ی محصول',
				),
				'hint'    => 'نظرهایی که بازدیدکننده‌ها با دکمه‌ی «ثبت تجربه» می‌فرستند، پس از تأیید شما در حالت «تأییدشده» اینجا می‌آیند.',
			),
			'items' => array(
				'type'      => 'repeater',
				'label'     => 'نظرهای دستی',
				'row_label' => 'نظر',
				'max'       => 20,
				'fields'    => array(
					'avatar' => array(
						'type'  => 'image',
						'label' => 'عکس',
					),
					'name'   => array(
						'type'  => 'text',
						'label' => 'نام',
					),
					'role'   => array(
						'type'  => 'text',
						'label' => 'سمت شغلی',
					),
					'text'   => array(
						'type'  => 'textarea',
						'label' => 'متن نظر',
					),
					'rating' => array(
						'type'    => 'select',
						'label'   => 'امتیاز',
						'default' => '5',
						'options' => array(
							'5' => '۵ ستاره',
							'4' => '۴ ستاره',
							'3' => '۳ ستاره',
							'2' => '۲ ستاره',
							'1' => '۱ ستاره',
							'0' => 'بدون ستاره',
						),
					),
				),
				'default' => array(
					array(
						'name'   => 'مینا کاظمی',
						'role'   => 'مدیر بازاریابی',
						'text'   => 'بهترین دوره فروش که تا امروز گذراندم.',
						'rating' => '5',
					),
					array(
						'name'   => 'علی رضایی',
						'role'   => 'کارآفرین',
						'text'   => 'محتوای دوره بسیار کاربردی و عملی بود. عالی!',
						'rating' => '5',
					),
					array(
						'name'   => 'سارا محمدی',
						'role'   => 'مدیر فروش',
						'text'   => 'تکنیک‌های این دوره را اجرا کردم و فروش تیم تیم فروش‌ام برابر شد!',
						'rating' => '5',
					),
				),
			),

			/* --- گروه: فرم ثبت تجربه -------------------------------------- */
			'g_form' => array(
				'type'  => 'group',
				'label' => 'دکمه و فرم «ثبت تجربه»',
			),
			'btn_text' => array(
				'type'    => 'text',
				'label'   => 'متن دکمه',
				'default' => 'ثبت تجربه',
				'hint'    => 'خالی بگذار تا دکمه و فرم کامل حذف شوند.',
			),
			'form_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان فرم',
				'default' => 'تجربه‌ی خود را ثبت کنید',
			),
			'form_note' => array(
				'type'    => 'text',
				'label'   => 'توضیح زیر عنوان فرم',
				'default' => 'نظر شما پس از تأیید در همین بخش نمایش داده می‌شود.',
			),
			'form_submit' => array(
				'type'    => 'text',
				'label'   => 'متن دکمه‌ی ارسال',
				'default' => 'ارسال تجربه',
			),
		);
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	public function render( $post_id, $d ) {

		$items = $this->items( $post_id, $d );
		$form  = '' !== $d['btn_text'] && comments_open( $post_id );

		if ( empty( $items ) && ! $form && '' === $d['title'] ) {
			return;
		}
		?>
		<section class="spp spp-tst" dir="rtl">
			<div class="spp-wrap">
				<div class="spp-tst__card">

					<div class="spp-tst__head">
						<?php if ( '' !== $d['title'] ) : ?>
							<h2 class="spp-tst__h"><?php echo esc_html( $d['title'] ); ?></h2>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $items ) ) : ?>
						<div class="spp-tst__carousel">
							<button type="button" class="spp-nav spp-nav--prev" data-spp-slide="prev" aria-label="نظر بعدی">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
							</button>

							<div class="spp-tst__track" data-spp-track tabindex="0">
								<?php foreach ( $items as $row ) : ?>
									<article class="spp-tst__i">
										<header class="spp-tst__top">
											<span class="spp-tst__av">
												<?php
												if ( ! empty( $row['avatar'] ) ) {
													echo wp_get_attachment_image( (int) $row['avatar'], array( 96, 96 ), false, array(
														'class' => 'spp-tst__img',
														'alt'   => esc_attr( $row['name'] ?? '' ),
													) );
												} elseif ( ! empty( $row['avatar_url'] ) ) {
													printf(
														'<img class="spp-tst__img" src="%s" alt="%s" loading="lazy">',
														esc_url( $row['avatar_url'] ),
														esc_attr( $row['name'] ?? '' )
													);
												} else {
													printf( '<span class="spp-tst__ph">%s</span>', esc_html( mb_substr( (string) ( $row['name'] ?? '؟' ), 0, 1 ) ) );
												}
												?>
											</span>

											<span class="spp-tst__who">
												<strong class="spp-tst__name"><?php echo esc_html( $row['name'] ?? '' ); ?></strong>
												<?php if ( ! empty( $row['role'] ) ) : ?>
													<em class="spp-tst__role"><?php echo esc_html( $row['role'] ); ?></em>
												<?php endif; ?>
											</span>
										</header>

										<?php if ( ! empty( $row['text'] ) ) : ?>
											<p class="spp-tst__txt"><?php echo esc_html( $row['text'] ); ?></p>
										<?php endif; ?>

										<?php echo $this->stars( (int) ( $row['rating'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</article>
								<?php endforeach; ?>
							</div>

							<button type="button" class="spp-nav spp-nav--next" data-spp-slide="next" aria-label="نظر قبلی">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
							</button>
						</div>
					<?php endif; ?>

					<?php if ( $form ) : ?>
						<div class="spp-tst__cta">
							<button type="button" class="spp-btn spp-btn--outline" data-spp-open="spp-review-form">
								<?php echo spp_icon( 'star', 17 ); ?>
								<?php echo esc_html( $d['btn_text'] ); ?>
							</button>
						</div>

						<?php $this->form( $post_id, $d ); ?>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}

	/* =====================================================================
	 * فرم پاپ‌آپ
	 * =================================================================== */

	/**
	 * @param int   $post_id
	 * @param array $d
	 */
	private function form( $post_id, $d ) {

		$user      = wp_get_current_user();
		$logged_in = $user && $user->exists();
		$need_mail = (bool) get_option( 'require_name_email' );
		?>
		<div class="spp-modal spp-modal--form" id="spp-review-form" hidden>
			<div class="spp-modal__box spp-modal__box--form" role="dialog" aria-modal="true" aria-labelledby="spp-review-form-t">
				<button type="button" class="spp-modal__x" data-spp-close aria-label="بستن">×</button>

				<form class="spp-form" data-spp-form novalidate>
					<h3 class="spp-form__h" id="spp-review-form-t"><?php echo esc_html( $d['form_title'] ); ?></h3>

					<?php if ( '' !== $d['form_note'] ) : ?>
						<p class="spp-form__note"><?php echo esc_html( $d['form_note'] ); ?></p>
					<?php endif; ?>

					<div class="spp-form__grid">
						<label class="spp-form__f">
							<span>نام و نام خانوادگی <b>*</b></span>
							<input type="text" name="name" required value="<?php echo esc_attr( $logged_in ? $user->display_name : '' ); ?>">
						</label>

						<label class="spp-form__f">
							<span>سمت شغلی</span>
							<input type="text" name="role" placeholder="مثلاً مدیر فروش">
						</label>

						<label class="spp-form__f">
							<span>ایمیل <?php echo $need_mail ? '<b>*</b>' : '<i>(اختیاری)</i>'; ?></span>
							<input type="email" name="email" <?php echo $need_mail ? 'required' : ''; ?> value="<?php echo esc_attr( $logged_in ? $user->user_email : '' ); ?>">
						</label>

						<div class="spp-form__f">
							<span>امتیاز <b>*</b></span>
							<div class="spp-rate" data-spp-rate>
								<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
									<label class="spp-rate__s">
										<input type="radio" name="rating" value="<?php echo (int) $i; ?>" <?php checked( 5, $i ); ?>>
										<span aria-label="<?php echo esc_attr( spp_fa_num( $i ) . ' ستاره' ); ?>">★</span>
									</label>
								<?php endfor; ?>
							</div>
						</div>
					</div>

					<label class="spp-form__f spp-form__f--full">
						<span>تجربه‌ی شما <b>*</b></span>
						<textarea name="text" rows="4" required minlength="10" placeholder="از دوره چه چیزی به دست آوردید؟"></textarea>
					</label>

					<?php /* تله‌ی ربات — کاربر نمی‌بیند. */ ?>
					<div class="spp-form__hp" aria-hidden="true">
						<label>این فیلد را خالی بگذارید<input type="text" name="spp_hp" tabindex="-1" autocomplete="off"></label>
					</div>

					<input type="hidden" name="product" value="<?php echo (int) $post_id; ?>">

					<p class="spp-form__msg" data-spp-msg role="status" aria-live="polite"></p>

					<button type="submit" class="spp-btn spp-btn--gold spp-btn--block">
						<?php echo esc_html( $d['form_submit'] ); ?>
					</button>
				</form>
			</div>
		</div>
		<?php
	}

	/* =====================================================================
	 * کمکی
	 * =================================================================== */

	/**
	 * آیتم‌ها بر اساس منبعِ انتخابی.
	 *
	 * @param int   $post_id
	 * @param array $d
	 * @return array
	 */
	private function items( $post_id, $d ) {

		$manual = array_values( array_filter( (array) $d['items'], function ( $r ) {
			return ! empty( $r['text'] ) || ! empty( $r['name'] );
		} ) );

		$woo = array();
		if ( in_array( $d['source'], array( 'woo', 'both' ), true ) && class_exists( 'SPP_Reviews' ) ) {
			$woo = SPP_Reviews::approved( $post_id );
		}

		switch ( $d['source'] ) {
			case 'manual':
				return $manual;
			case 'woo':
				return $woo;
			default:
				return array_merge( $manual, $woo );
		}
	}

	/**
	 * ستاره‌ها.
	 *
	 * @param int $rating
	 * @return string
	 */
	private function stars( $rating ) {

		$rating = max( 0, min( 5, (int) $rating ) );

		if ( ! $rating ) {
			return '';
		}

		$out = '<span class="spp-stars" role="img" aria-label="' . esc_attr( spp_fa_num( $rating ) . ' از ۵' ) . '">';

		for ( $i = 1; $i <= 5; $i++ ) {
			$out .= '<span class="spp-stars__s' . ( $i <= $rating ? ' is-on' : '' ) . '">★</span>';
		}

		return $out . '</span>';
	}
}
