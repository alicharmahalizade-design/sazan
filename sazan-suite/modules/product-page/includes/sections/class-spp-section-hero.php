<?php
/**
 * سکشن ۱ — هیرو: کارت معرفی دوره + کارت خرید + نوار مزایا.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_Hero implements SPP_Section {

	public function id() {
		return 'hero';
	}

	public function title() {
		return 'سکشن ۱ — هیرو (معرفی و خرید)';
	}

	public function icon() {
		return 'eicon-banner';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {
		return array(

			/* --- گروه: معرفی دوره ---------------------------------------- */
			'g_intro' => array(
				'type'  => 'group',
				'label' => 'معرفی دوره',
			),
			'badge' => array(
				'type'    => 'text',
				'label'   => 'برچسب بالای عنوان',
				'default' => 'دوره جامع',
				'hint'    => 'اگر خالی بماند نمایش داده نمی‌شود.',
			),
			'title' => array(
				'type'    => 'text',
				'label'   => 'عنوان اصلی',
				'default' => 'استراتژی طلایی فروش',
				'class'   => 'spp-f--lg',
			),
			'title_hl' => array(
				'type'  => 'text',
				'label' => 'بخشی از عنوان که طلایی شود',
				'hint'  => 'مثلاً «طلایی». باید عیناً داخل عنوان باشد.',
			),
			'subtitle' => array(
				'type'    => 'textarea',
				'label'   => 'زیرعنوان',
				'default' => 'کلیدهای افزایش فروش و دستیابی به موفقیت پایدار',
			),
			'image' => array(
				'type'  => 'image',
				'label' => 'تصویر مدرس / تصویر شاخص هیرو',
				'hint'  => 'تصویر PNG با پس‌زمینه‌ی حذف‌شده بهترین نتیجه را می‌دهد.',
			),
			'bg_image' => array(
				'type'  => 'image',
				'label' => 'تصویر پس‌زمینه‌ی کارت (اختیاری)',
			),

			/* --- گروه: مشخصات دوره --------------------------------------- */
			'g_meta' => array(
				'type'  => 'group',
				'label' => 'مشخصات دوره (ردیف زیر عنوان)',
			),
			'meta_items' => array(
				'type'      => 'repeater',
				'label'     => 'آیتم‌های مشخصات',
				'row_label' => 'مشخصه',
				'max'       => 6,
				'fields'    => array(
					'icon'  => array(
						'type'    => 'icon',
						'label'   => 'آیکن',
						'default' => 'user',
					),
					'label' => array(
						'type'  => 'text',
						'label' => 'برچسب',
					),
					'value' => array(
						'type'  => 'text',
						'label' => 'مقدار',
					),
				),
				'default' => array(
					array(
						'icon'  => 'user',
						'label' => 'مدرس',
						'value' => 'عباس شنبه سازان',
					),
					array(
						'icon'  => 'clock',
						'label' => 'مدت زمان',
						'value' => '۸۵ ساعت',
					),
					array(
						'icon'  => 'level',
						'label' => 'سطح دوره',
						'value' => 'مبتدی تا پیشرفته',
					),
					array(
						'icon'  => 'monitor',
						'label' => 'نوع برگزاری',
						'value' => 'غیرحضوری',
					),
				),
			),

			/* --- گروه: دکمه‌ها و پیش‌نمایش -------------------------------- */
			'g_actions' => array(
				'type'  => 'group',
				'label' => 'دکمه‌ها و پیش‌نمایش',
			),
			'btn1_text' => array(
				'type'    => 'text',
				'label'   => 'دکمه‌ی اول (طلایی)',
				'default' => 'ثبت‌نام در دوره',
			),
			'btn1_link' => array(
				'type'  => 'text',
				'label' => 'لینک دکمه‌ی اول',
				'hint'  => 'خالی = افزودن به سبد خرید همین محصول.',
			),
			'btn2_text' => array(
				'type'    => 'text',
				'label'   => 'دکمه‌ی دوم (خطی)',
				'default' => 'مشاهده سرفصل‌ها',
			),
			'btn2_link' => array(
				'type'    => 'text',
				'label'   => 'لینک دکمه‌ی دوم',
				'default' => '#spp-syllabus',
			),
			'preview_text' => array(
				'type'    => 'text',
				'label'   => 'متن دکمه‌ی پخش',
				'default' => 'پیش‌نمایش دوره',
			),
			'preview_video' => array(
				'type'  => 'url',
				'label' => 'لینک ویدیوی پیش‌نمایش',
				'hint'  => 'MP4 یا آدرس آپارات/یوتیوب. خالی = دکمه‌ی پخش نمایش داده نمی‌شود.',
			),

			/* --- گروه: اعتماد اجتماعی ------------------------------------ */
			'g_proof' => array(
				'type'  => 'group',
				'label' => 'اعتماد اجتماعی (دانشجویان)',
			),
			'students_count' => array(
				'type'    => 'text',
				'label'   => 'تعداد دانشجو',
				'default' => '+۸۵۰۰',
			),
			'students_text' => array(
				'type'    => 'text',
				'label'   => 'متن کنار تعداد',
				'default' => 'دانشجو این دوره را تهیه کرده‌اند',
			),
			'avatars' => array(
				'type'      => 'repeater',
				'label'     => 'آواتار دانشجویان (اختیاری — فقط برای این دوره)',
				'row_label' => 'آواتار',
				'max'       => 8,
				'inline'    => true,
				'hint'      => 'خالی بگذار تا آواتارهای سراسری بیایند (محصولات ← <strong>صفحه اختصاصی محصول</strong>). فقط اگر این دوره باید آواتار متفاوتی داشته باشد اینجا تصویر اضافه کن.',
				'fields'    => array(
					'img' => array(
						'type'  => 'image',
						'label' => 'تصویر',
					),
				),
			),

			/* --- گروه: نوار مزایا ---------------------------------------- */
			'g_features' => array(
				'type'  => 'group',
				'label' => 'نوار مزایا (پایین کارت)',
			),
			'features' => array(
				'type'      => 'repeater',
				'label'     => 'مزایا',
				'row_label' => 'مزیت',
				'max'       => 4,
				'fields'    => array(
					'icon'  => array(
						'type'    => 'icon',
						'label'   => 'آیکن',
						'default' => 'support',
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
						'icon'  => 'support',
						'title' => 'پشتیبانی ویژه',
						'desc'  => 'در طول دوره',
					),
					array(
						'icon'  => 'teacher',
						'title' => 'اساتید متخصص',
						'desc'  => 'از تجربه‌های واقعی',
					),
					array(
						'icon'  => 'content',
						'title' => 'محتوای کاربردی',
						'desc'  => 'قابل اجرا در محل کار',
					),
					array(
						'icon'  => 'project',
						'title' => 'پروژه‌محور',
						'desc'  => 'با تمرین‌های واقعی',
					),
				),
			),

			/* --- گروه: کارت خرید ----------------------------------------- */
			'g_buy' => array(
				'type'  => 'group',
				'label' => 'کارت خرید (ستون کنار)',
			),
			'offer_badge' => array(
				'type'    => 'text',
				'label'   => 'برچسب پیشنهاد',
				'default' => 'فرصت ویژه',
			),
			'offer_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان پیشنهاد',
				'default' => 'تخفیف پایان هفته',
			),
			'price_mode' => array(
				'type'    => 'select',
				'label'   => 'منبع قیمت',
				'default' => 'auto',
				'options' => array(
					'auto'   => 'خودکار از ووکامرس',
					'manual' => 'دستی (فیلدهای زیر)',
				),
			),
			'price_num' => array(
				'type'  => 'text',
				'label' => 'قیمت — عدد',
				'hint'  => 'مثال: ۸۵',
			),
			'price_unit' => array(
				'type'    => 'text',
				'label'   => 'قیمت — واحد',
				'default' => 'میلیون تومان',
			),
			'price_old' => array(
				'type'  => 'text',
				'label' => 'قیمت قبلی (خط‌خورده)',
				'hint'  => 'مثال: ۱۴۵ میلیون تومان',
			),
			'price_off' => array(
				'type'  => 'text',
				'label' => 'درصد تخفیف',
				'hint'  => 'مثال: ۳۵ — فقط عدد.',
			),
			'buy_list' => array(
				'type'      => 'repeater',
				'label'     => 'فهرست امکانات کارت خرید',
				'row_label' => 'مورد',
				'max'       => 8,
				'fields'    => array(
					'text' => array(
						'type'  => 'text',
						'label' => 'متن',
					),
				),
				'default' => array(
					array( 'text' => 'دسترسی به دوره به‌روز' ),
					array( 'text' => 'آپدیت‌ها و محتوای جدید' ),
					array( 'text' => 'پشتیبانی اختصاصی' ),
					array( 'text' => 'دسترسی همیشگی به دوره' ),
					array( 'text' => 'ضمانت بازگشت وجه ۷ روزه' ),
				),
			),
			'cta_text' => array(
				'type'    => 'text',
				'label'   => 'متن دکمه‌ی خرید',
				'default' => 'ثبت‌نام و خرید دوره',
			),
			'cta_link' => array(
				'type'  => 'text',
				'label' => 'لینک دکمه‌ی خرید',
				'hint'  => 'خالی = افزودن به سبد خرید همین محصول.',
			),
			'guarantee_title' => array(
				'type'    => 'text',
				'label'   => 'عنوان جعبه‌ی ضمانت',
				'default' => '۷ روز ضمانت بازگشت وجه',
			),
			'guarantee_text' => array(
				'type'    => 'text',
				'label'   => 'توضیح جعبه‌ی ضمانت',
				'default' => 'در صورت عدم رضایت کامل',
			),
		);
	}

	/* =====================================================================
	 * رندر
	 * =================================================================== */

	public function render( $post_id, $d ) {

		$cart = spp_cart_link( $post_id );

		$btn1_link = '' !== $d['btn1_link'] ? $d['btn1_link'] : $cart;
		$cta_link  = '' !== $d['cta_link'] ? $d['cta_link'] : $cart;

		$price = $this->price( $post_id, $d );

		// آواتارها: اگر برای این محصول چیزی ثبت نشده، از تنظیمات سراسری می‌آید.
		$avatars = function_exists( 'spp_avatars' ) ? spp_avatars( $d['avatars'] ) : (array) $d['avatars'];

		if ( '' === $d['students_text'] && function_exists( 'spp_global' ) ) {
			$d['students_text'] = (string) spp_global( 'students_text', '' );
		}

		$bg_url = $d['bg_image'] ? wp_get_attachment_image_url( (int) $d['bg_image'], 'full' ) : '';
		$style  = $bg_url ? ' style="--spp-hero-bg:url(' . esc_url( $bg_url ) . ')"' : '';
		?>
		<section class="spp spp-hero" dir="rtl">
			<div class="spp-wrap">
				<div class="spp-hero__grid">

					<?php /* ---------- کارت خرید (سمت راست) ---------- */ ?>
					<aside class="spp-buy">

						<?php /* بالا: پیشنهاد و قیمت */ ?>
						<div class="spp-buy__top">
							<?php if ( '' !== $d['offer_badge'] ) : ?>
								<span class="spp-chip spp-chip--gold"><?php echo spp_icon( 'fire', 14 ); ?><?php echo esc_html( $d['offer_badge'] ); ?></span>
							<?php endif; ?>

							<?php if ( '' !== $d['offer_title'] ) : ?>
								<p class="spp-buy__offer"><?php echo esc_html( $d['offer_title'] ); ?></p>
							<?php endif; ?>

							<?php if ( '' !== $price['num'] || '' !== $price['unit'] ) : ?>
								<p class="spp-buy__price">
									<span class="spp-buy__num"><?php echo esc_html( $price['num'] ); ?></span>
									<span class="spp-buy__unit"><?php echo esc_html( $price['unit'] ); ?></span>
								</p>
							<?php endif; ?>

							<?php if ( '' !== $price['off'] || '' !== $price['old'] ) : ?>
								<p class="spp-buy__sub">
									<?php if ( '' !== $price['off'] ) : ?>
										<span class="spp-off"><?php echo esc_html( $price['off'] ); ?>٪ تخفیف</span>
									<?php endif; ?>
									<?php if ( '' !== $price['old'] ) : ?>
										<del class="spp-old">(<?php echo esc_html( $price['old'] ); ?>)</del>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</div>

						<?php /* وسط: فهرست امکانات — در فضای آزادِ کارت وسط‌چین می‌شود */ ?>
						<?php if ( ! empty( $d['buy_list'] ) ) : ?>
							<div class="spp-buy__mid">
								<ul class="spp-buy__list">
									<?php foreach ( $d['buy_list'] as $row ) : ?>
										<?php if ( empty( $row['text'] ) ) { continue; } ?>
										<li>
											<span class="spp-tick"><?php echo spp_icon( 'check', 14 ); ?></span>
											<span><?php echo esc_html( $row['text'] ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php /* پایین: دکمه‌ی خرید و ضمانت — چسبیده به کف کارت */ ?>
						<div class="spp-buy__foot">
							<?php if ( '' !== $d['cta_text'] ) : ?>
								<a class="spp-btn spp-btn--gold spp-btn--block" href="<?php echo esc_url( $cta_link ); ?>">
									<?php echo esc_html( $d['cta_text'] ); ?>
								</a>
							<?php endif; ?>

							<?php if ( '' !== $d['guarantee_title'] ) : ?>
								<div class="spp-guard">
									<span class="spp-guard__icon"><?php echo spp_icon( 'shield', 22 ); ?></span>
									<span class="spp-guard__txt">
										<strong><?php echo esc_html( $d['guarantee_title'] ); ?></strong>
										<?php if ( '' !== $d['guarantee_text'] ) : ?>
											<em><?php echo esc_html( $d['guarantee_text'] ); ?></em>
										<?php endif; ?>
									</span>
								</div>
							<?php endif; ?>
						</div>
					</aside>

					<?php /* ---------- ستون اصلی ---------- */ ?>
					<div class="spp-hero__main">

						<div class="spp-hero__card"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<div class="spp-hero__glow" aria-hidden="true"></div>

							<div class="spp-hero__body">
								<?php if ( '' !== $d['badge'] ) : ?>
									<span class="spp-chip spp-chip--gold-o"><?php echo esc_html( $d['badge'] ); ?></span>
								<?php endif; ?>

								<?php if ( '' !== $d['title'] ) : ?>
									<h2 class="spp-hero__title"><?php echo wp_kses_post( spp_highlight( $d['title'], $d['title_hl'] ) ); ?></h2>
								<?php endif; ?>

								<?php if ( '' !== $d['subtitle'] ) : ?>
									<p class="spp-hero__sub"><?php echo esc_html( $d['subtitle'] ); ?></p>
								<?php endif; ?>

								<?php if ( ! empty( $d['meta_items'] ) ) : ?>
									<ul class="spp-meta">
										<?php foreach ( $d['meta_items'] as $row ) : ?>
											<?php if ( empty( $row['label'] ) && empty( $row['value'] ) ) { continue; } ?>
											<li class="spp-meta__i">
												<span class="spp-meta__top">
													<span class="spp-meta__ic"><?php echo spp_icon( $row['icon'] ?? 'check', 17 ); ?></span>
													<span class="spp-meta__lb"><?php echo esc_html( $row['label'] ?? '' ); ?></span>
												</span>
												<span class="spp-meta__vl"><?php echo esc_html( $row['value'] ?? '' ); ?></span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>

								<div class="spp-hero__actions">
									<?php if ( '' !== $d['btn1_text'] ) : ?>
										<a class="spp-btn spp-btn--gold" href="<?php echo esc_url( $btn1_link ); ?>">
											<?php echo spp_icon( 'cart', 18 ); ?>
											<?php echo esc_html( $d['btn1_text'] ); ?>
										</a>
									<?php endif; ?>
									<?php if ( '' !== $d['btn2_text'] ) : ?>
										<a class="spp-btn spp-btn--ghost" href="<?php echo esc_url( $d['btn2_link'] ); ?>">
											<?php echo esc_html( $d['btn2_text'] ); ?>
										</a>
									<?php endif; ?>
								</div>

								<?php if ( '' !== $d['students_count'] || '' !== $d['students_text'] ) : ?>
									<div class="spp-proof">
										<?php if ( ! empty( $avatars ) ) : ?>
											<span class="spp-avatars">
												<?php foreach ( $avatars as $row ) : ?>
													<?php
													$aid = isset( $row['img'] ) ? (int) $row['img'] : 0;
													if ( ! $aid ) {
														continue;
													}
													echo wp_get_attachment_image( $aid, array( 44, 44 ), false, array( 'class' => 'spp-avatars__i', 'alt' => '' ) );
													?>
												<?php endforeach; ?>
											</span>
										<?php endif; ?>
										<p class="spp-proof__txt">
											<?php if ( '' !== $d['students_count'] ) : ?>
												<b><?php echo esc_html( $d['students_count'] ); ?></b>
											<?php endif; ?>
											<?php echo esc_html( $d['students_text'] ); ?>
										</p>
									</div>
								<?php endif; ?>
							</div>

							<div class="spp-hero__figure">
								<?php if ( $d['image'] ) : ?>
									<?php
									echo wp_get_attachment_image(
										(int) $d['image'],
										'large',
										false,
										array(
											'class' => 'spp-hero__img',
											'alt'   => esc_attr( $d['title'] ),
										)
									);
									?>
								<?php endif; ?>

								<?php if ( '' !== $d['preview_video'] ) : ?>
									<button type="button" class="spp-play" data-spp-video="<?php echo esc_url( $d['preview_video'] ); ?>">
										<span class="spp-play__btn"><?php echo spp_icon( 'video', 18 ); ?></span>
										<span class="spp-play__txt"><?php echo esc_html( $d['preview_text'] ); ?></span>
									</button>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( ! empty( $d['features'] ) ) : ?>
							<div class="spp-features">
								<?php foreach ( $d['features'] as $row ) : ?>
									<?php if ( empty( $row['title'] ) ) { continue; } ?>
									<div class="spp-features__i">
										<span class="spp-features__ic"><?php echo spp_icon( $row['icon'] ?? 'check', 20 ); ?></span>
										<span class="spp-features__tx">
											<strong><?php echo esc_html( $row['title'] ); ?></strong>
											<?php if ( ! empty( $row['desc'] ) ) : ?>
												<em><?php echo esc_html( $row['desc'] ); ?></em>
											<?php endif; ?>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
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
	 * محاسبه‌ی قیمت‌های کارت خرید بر اساس حالتِ انتخابی.
	 *
	 * @param int   $post_id
	 * @param array $d
	 * @return array{num:string,unit:string,old:string,off:string}
	 */
	private function price( $post_id, $d ) {

		$out = array(
			'num'  => (string) $d['price_num'],
			'unit' => (string) $d['price_unit'],
			'old'  => (string) $d['price_old'],
			'off'  => (string) $d['price_off'],
		);

		if ( 'manual' === $d['price_mode'] ) {
			return $out;
		}

		$p = spp_product_prices( $post_id );

		if ( $p['sale'] > 0 ) {
			$parts        = spp_price_parts( $p['sale'] );
			$out['num']   = $parts['num'];
			$out['unit']  = $parts['unit'];
		}

		if ( $p['off'] > 0 ) {
			$old         = spp_price_parts( $p['regular'] );
			$out['old']  = trim( $old['num'] . ' ' . $old['unit'] );
			$out['off']  = spp_fa_num( $p['off'] );
		} else {
			$out['old'] = '';
			$out['off'] = '';
		}

		return $out;
	}
}
