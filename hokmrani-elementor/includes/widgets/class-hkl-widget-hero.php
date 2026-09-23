<?php
/**
 * Hero: photo, headline with growth numbers and the offer card.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Hero extends HKL_Widget_Base {

	protected static function slug() {
		return 'hero';
	}

	protected static function title() {
		return 'حکمرانی — هیرو (بخش اول)';
	}

	public function get_icon() {
		return 'eicon-banner';
	}

	protected static function fields() {
		return [
			'photo' => [
				'label'    => 'تصویر',
				'controls' => [
					'photo'       => [ 'type' => 'media', 'label' => 'تصویر مدرس', 'default' => '', 'description' => 'خالی بماند تا تصویر اصلی طرح نمایش داده شود.' ],
					'photo_label' => [ 'type' => 'text', 'label' => 'توضیح تصویر (دسترسی‌پذیری)', 'default' => 'مدرس برنامه در دفتر کار' ],
				],
			],
			'copy'  => [
				'label'    => 'تیتر',
				'controls' => [
					'brand'           => [ 'type' => 'text', 'label' => 'نام برنامه', 'default' => 'حکمرانی' ],
					'brand_highlight' => [ 'type' => 'text', 'label' => 'بخش رنگی نام برنامه', 'default' => 'بر بازار' ],
					'title'           => [ 'type' => 'text', 'label' => 'تیتر اصلی (H1)', 'default' => 'فروش کار و کسب را' ],
					'growth_from'     => [ 'type' => 'text', 'label' => 'عدد شروع', 'default' => '۲۵' ],
					'growth_between'  => [ 'type' => 'text', 'label' => 'واژه میانی', 'default' => 'تا' ],
					'growth_to'       => [ 'type' => 'text', 'label' => 'عدد پایان', 'default' => '۳۰۰' ],
					'growth_unit'     => [ 'type' => 'text', 'label' => 'واحد', 'default' => 'درصد' ],
					'subtitle'        => [ 'type' => 'text', 'label' => 'ادامه تیتر', 'default' => 'افزایش دهید' ],
					'note'            => [ 'type' => 'rich', 'label' => 'توضیح زیر تیتر', 'default' => '<mark>نه</mark> با تخفیف بیشتر؛ با پیدا کردن و اصلاح گلوگاه‌هایی <span class="hero-note-keep">که جلوی رشد فروش شما را گرفته‌اند</span>' ],
				],
			],
			'offer' => [
				'label'    => 'کارت پیشنهاد',
				'controls' => [
					'offer_label'     => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری کارت', 'default' => 'جزئیات برنامه' ],
					'duration_number' => [ 'type' => 'text', 'label' => 'مدت (عدد)', 'default' => '۱۴' ],
					'duration_unit'   => [ 'type' => 'text', 'label' => 'مدت (واحد)', 'default' => 'هفته' ],
					'offer_text'      => [ 'type' => 'textarea', 'label' => 'متن کارت', 'default' => 'برای صاحبان و مدیران کار و کسبی که فروش‌شان آن‌طور که باید نیست و می‌خواهند ظرفیت بیشتری برای رشد داشته باشند.' ],
					'offer_strong'    => [ 'type' => 'text', 'label' => 'متن پررنگ', 'default' => 'آموزش + راهبری + کوچینگ + منتورینگ روی کار و کسب واقعی شما' ],
					'cta_text'        => [ 'type' => 'text', 'label' => 'متن لینک', 'default' => 'می‌خواهم ببینم این برنامه برای کار و کسب من مناسب است' ],
					'cta_link'        => [ 'type' => 'link', 'label' => 'لینک', 'default' => '#assessment' ],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		$photo = self::media_url( $s['photo'] ?? '' );
		$style = $photo ? ' style="background-image:url(&quot;' . esc_url( $photo ) . '&quot;)"' : '';
		$from  = self::v( $s, 'growth_from' );
		$mid   = self::v( $s, 'growth_between' );
		$to    = self::v( $s, 'growth_to' );
		$unit  = self::v( $s, 'growth_unit' );
		?>
<section class="hero-v2-coded" aria-labelledby="hero-v2-title">
      <div class="hero-v2-photo" role="img" aria-label="<?php echo self::a( self::v( $s, 'photo_label' ) ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>></div>
      <div class="hero-v2-copy">
        <p class="hero-v2-brand"><?php echo self::t( self::v( $s, 'brand' ) ); ?> <span><?php echo self::t( self::v( $s, 'brand_highlight' ) ); ?></span></p>
        <h1 id="hero-v2-title"><?php echo self::t( self::v( $s, 'title' ) ); ?></h1>
        <div class="hero-v2-growth" aria-label="<?php echo self::a( trim( $from . ' ' . $mid . ' ' . $to . ' ' . $unit ) ); ?>"><b><?php echo self::t( $from ); ?></b><i><?php echo self::t( $mid ); ?></i><strong><?php echo self::t( $to ); ?></strong><em><?php echo self::t( $unit ); ?></em></div>
        <h2><?php echo self::t( self::v( $s, 'subtitle' ) ); ?></h2>
        <p class="hero-v2-note"><?php echo self::r( self::v( $s, 'note' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
      </div>
      <aside class="hero-v2-offer" aria-label="<?php echo self::a( self::v( $s, 'offer_label' ) ); ?>">
        <div class="hero-v2-duration"><span><b><?php echo self::t( self::v( $s, 'duration_number' ) ); ?></b> <?php echo self::t( self::v( $s, 'duration_unit' ) ); ?></span><svg aria-hidden="true"><use href="#i-calendar"/></svg></div>
        <p><?php echo self::t( self::v( $s, 'offer_text' ) ); ?></p>
        <div class="hero-v2-icons" aria-hidden="true"><svg><use href="#i-shield"/></svg><i>＋</i><svg><use href="#i-support"/></svg><i>＋</i><svg><use href="#i-focus"/></svg><i>＋</i><svg><use href="#i-chart"/></svg></div>
        <strong><?php echo self::t( self::v( $s, 'offer_strong' ) ); ?></strong>
        <a href="<?php echo self::u( self::v( $s, 'cta_link' ) ); ?>"><?php echo self::t( self::v( $s, 'cta_text' ) ); ?></a>
      </aside>
    </section>
		<?php
	}
}
