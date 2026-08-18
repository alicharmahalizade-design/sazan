<?php
/**
 * سکشن ۵ — «بعد از این دوره چه مهارت‌هایی کسب می‌کنید؟».
 *
 * ردیفی از دایره‌های آیکن که با خط‌چین به هم وصل شده‌اند.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Section_Skills implements SPP_Section {

	public function id() {
		return 'skills';
	}

	public function title() {
		return 'سکشن ۵ — مهارت‌های پس از دوره';
	}

	public function icon() {
		return 'eicon-time-line';
	}

	/* =====================================================================
	 * فیلدها
	 * =================================================================== */

	public function fields() {
		return array(

			'g_skills' => array(
				'type'  => 'group',
				'label' => 'مهارت‌ها',
			),
			'title' => array(
				'type'    => 'text',
				'label'   => 'عنوان سکشن',
				'class'   => 'spp-f--lg',
				'default' => 'بعد از این دوره چه مهارت‌هایی کسب می‌کنید؟',
			),
			'connector' => array(
				'type'    => 'select',
				'label'   => 'خط‌چین بین دایره‌ها',
				'default' => 'yes',
				'options' => array(
					'yes' => 'نمایش داده شود',
					'no'  => 'حذف شود',
				),
			),
			'items' => array(
				'type'      => 'repeater',
				'label'     => 'مهارت‌ها',
				'row_label' => 'مهارت',
				'max'       => 8,
				'hint'      => 'ترتیب از راست به چپ است.',
				'fields'    => array(
					'icon' => array(
						'type'    => 'icon',
						'label'   => 'آیکن',
						'default' => 'chart',
					),
					'text' => array(
						'type'  => 'text',
						'label' => 'متن',
					),
				),
				'default' => array(
					array(
						'icon' => 'chart',
						'text' => 'افزایش فروش و سودآوری',
					),
					array(
						'icon' => 'settings',
						'text' => 'مدیریت تیم و عملکرد',
					),
					array(
						'icon' => 'handshake',
						'text' => 'مذاکره و متقاعدسازی',
					),
					array(
						'icon' => 'community',
						'text' => 'طراحی استراتژی فروش',
					),
					array(
						'icon' => 'search',
						'text' => 'تحلیل دقیق بازار و مشتری',
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
			return ! empty( $r['text'] ) || ! empty( $r['icon'] );
		} ) );

		if ( empty( $items ) && '' === $d['title'] ) {
			return;
		}

		$classes = 'spp-skills__card';
		if ( 'no' === $d['connector'] ) {
			$classes .= ' spp-skills__card--noline';
		}
		?>
		<section class="spp spp-skills" dir="rtl">
			<div class="spp-wrap">
				<div class="<?php echo esc_attr( $classes ); ?>">
					<?php if ( '' !== $d['title'] ) : ?>
						<h2 class="spp-skills__h"><?php echo esc_html( $d['title'] ); ?></h2>
					<?php endif; ?>

					<?php if ( ! empty( $items ) ) : ?>
						<ul class="spp-skills__row" style="--spp-skill-n:<?php echo (int) count( $items ); ?>">
							<?php foreach ( $items as $row ) : ?>
								<li class="spp-skill">
									<span class="spp-skill__ic"><?php echo spp_icon( $row['icon'] ?? 'star', 21 ); ?></span>
									<?php if ( ! empty( $row['text'] ) ) : ?>
										<span class="spp-skill__t"><?php echo esc_html( $row['text'] ); ?></span>
									<?php endif; ?>
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
