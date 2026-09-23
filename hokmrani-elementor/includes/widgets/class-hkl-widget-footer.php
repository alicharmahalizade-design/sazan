<?php
/**
 * Footer: brand, quick links, contact details and address.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Footer extends HKL_Widget_Base {

	protected static function slug() {
		return 'footer';
	}

	protected static function title() {
		return 'حکمرانی — فوتر';
	}

	public function get_icon() {
		return 'eicon-footer';
	}

	protected static function fields() {
		return [
			'brand'   => [
				'label'    => 'برند',
				'controls' => [
					'section_id' => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'contact' ],
					'logo'       => [ 'type' => 'media', 'label' => 'لوگو', 'default' => 'logo-sazan-full.png' ],
					'logo_alt'   => [ 'type' => 'text', 'label' => 'متن جایگزین لوگو', 'default' => 'لوگوی کامل سازان' ],
					'logo_link'  => [ 'type' => 'link', 'label' => 'لینک لوگو', 'default' => '#top' ],
					'logo_label' => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری لینک لوگو', 'default' => 'بازگشت به ابتدای صفحه' ],
					'about'      => [ 'type' => 'textarea', 'label' => 'درباره', 'default' => 'سازان؛ مرکز آموزش و مشاوره تخصصی کار و کسب، همراه مسیر رشد حرفه‌ای شما.' ],
				],
			],
			'links'   => [
				'label'    => 'دسترسی سریع',
				'controls' => [
					'links_title' => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'دسترسی سریع' ],
					'links'       => [
						'type'        => 'repeater',
						'label'       => 'لینک‌ها',
						'title_field' => '{{{ text }}}',
						'fields'      => [
							'text' => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'لینک' ],
							'link' => [ 'type' => 'link', 'label' => 'لینک', 'default' => '#' ],
						],
						'default'     => [
							[ 'text' => 'درباره دوره', 'link' => '#program' ],
							[ 'text' => 'سرفصل‌ها', 'link' => '#chapters' ],
							[ 'text' => 'تجربه شرکت‌کنندگان', 'link' => '#results' ],
							[ 'text' => 'سؤالات متداول', 'link' => '#faq' ],
						],
					],
				],
			],
			'contact' => [
				'label'    => 'تماس و نشانی',
				'controls' => [
					'contact_title' => [ 'type' => 'text', 'label' => 'عنوان تماس', 'default' => 'اطلاعات تماس' ],
					'phone'         => [ 'type' => 'text', 'label' => 'تلفن (نمایشی)', 'default' => '09169119789' ],
					'phone_link'    => [ 'type' => 'link', 'label' => 'لینک تلفن', 'default' => 'tel:+989169119789' ],
					'email'         => [ 'type' => 'text', 'label' => 'ایمیل', 'default' => 'info@irsazan.com' ],
					'extra_contacts' => [
						'type'        => 'repeater',
						'label'       => 'راه‌های ارتباطی بیشتر (اختیاری)',
						'title_field' => '{{{ text }}}',
						'fields'      => [
							'text' => [ 'type' => 'text', 'label' => 'متن', 'default' => '' ],
							'link' => [ 'type' => 'link', 'label' => 'لینک', 'default' => '' ],
							'ltr'  => [ 'type' => 'switcher', 'label' => 'چپ‌چین (LTR)', 'default' => 'yes' ],
						],
						'default'     => [],
					],
					'address_title' => [ 'type' => 'text', 'label' => 'عنوان نشانی', 'default' => 'نشانی و ساعت کاری' ],
					'address'       => [ 'type' => 'textarea', 'label' => 'نشانی', 'rows' => 2, 'default' => 'اهواز، کیان‌آباد، نبش خیابان سپهری، ساختمان آسمان، طبقه ۶، واحد ۱۲' ],
					'hours'         => [ 'type' => 'text', 'label' => 'ساعت کاری', 'default' => 'شنبه تا پنجشنبه، ساعت ۹ تا ۲۱' ],
					'copyright'     => [ 'type' => 'text', 'label' => 'کپی‌رایت', 'default' => '© ۱۴۰۵ سازان — تمامی حقوق محفوظ است.' ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section'   => self::section_style( 'footer' ),
			'grid'      => [
				'label'    => 'چیدمان',
				'selector' => '.footer-grid',
				'kinds'    => [ 'gap', 'max_width', 'padding' ],
			],
			'logo'      => [
				'label'    => 'لوگو',
				'selector' => '.footer-logo',
				'kinds'    => [ 'width', 'height', 'opacity' ],
			],
			'about'     => self::text_style( 'متن درباره', '.footer-brand p' ),
			'headings'  => self::text_style( 'عنوان ستون‌ها', '.footer-grid h3' ),
			'links'     => [
				'label'    => 'لینک‌ها',
				'selector' => '.footer-links a, .footer-contact a',
				'kinds'    => [ 'typography', 'color', 'color_hover', 'margin' ],
			],
			'address'   => self::text_style( 'نشانی و ساعت کاری', '.footer-address address, .footer-address p' ),
			'copyright' => [
				'label'    => 'کپی‌رایت',
				'selector' => '.copyright',
				'kinds'    => [ 'typography', 'color', 'background', 'padding', 'align', 'border', 'hide' ],
			],
		];
	}

	protected function render_html( array $s ) {
		$logo  = self::media_url( $s['logo'] ?? '' );
		$email = self::v( $s, 'email' );
		?>
<footer<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="container footer-grid">
      <div class="footer-brand">
        <a class="footer-logo-link"<?php echo self::href( self::arr( $s, 'logo_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo self::a( self::v( $s, 'logo_label' ) ); ?>">
          <img class="footer-logo" src="<?php echo esc_url( $logo ); ?>" alt="<?php echo self::a( self::v( $s, 'logo_alt' ) ); ?>" width="244" height="88">
        </a>
        <p><?php echo self::t( self::v( $s, 'about' ) ); ?></p>
      </div>
      <div class="footer-links">
        <h3><?php echo self::t( self::v( $s, 'links_title' ) ); ?></h3>
<?php foreach ( self::rows( $s, 'links' ) as $link ) : ?>
        <a<?php echo self::href( self::arr( $link, 'link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $link, 'text' ) ); ?></a>
<?php endforeach; ?>
      </div>
      <div class="footer-contact">
        <h3><?php echo self::t( self::v( $s, 'contact_title' ) ); ?></h3>
<?php if ( self::v( $s, 'phone' ) ) : ?>
        <a<?php echo self::href( self::arr( $s, 'phone_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> dir="ltr"><?php echo self::t( self::v( $s, 'phone' ) ); ?></a>
<?php endif; ?>
<?php if ( $email ) : ?>
        <a href="<?php echo esc_url( 'mailto:' . $email ); ?>" dir="ltr"><?php echo self::t( $email ); ?></a>
<?php endif; ?>
<?php foreach ( self::rows( $s, 'extra_contacts' ) as $contact ) : ?>
        <a<?php echo self::href( self::arr( $contact, 'link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo 'yes' === self::v( $contact, 'ltr' ) ? ' dir="ltr"' : ''; ?>><?php echo self::t( self::v( $contact, 'text' ) ); ?></a>
<?php endforeach; ?>
      </div>
      <div class="footer-address">
        <h3><?php echo self::t( self::v( $s, 'address_title' ) ); ?></h3>
        <address><?php echo self::t( self::v( $s, 'address' ) ); ?></address>
        <p><?php echo self::t( self::v( $s, 'hours' ) ); ?></p>
      </div>
    </div>
    <div class="copyright"><?php echo self::t( self::v( $s, 'copyright' ) ); ?></div>
  </footer>
		<?php
	}
}
