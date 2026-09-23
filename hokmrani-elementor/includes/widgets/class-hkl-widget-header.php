<?php
/**
 * Header: logo, main menu and call to action.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Header extends HKL_Widget_Base {

	protected static function slug() {
		return 'header';
	}

	protected static function title() {
		return 'حکمرانی — هدر و منو';
	}

	public function get_icon() {
		return 'eicon-header';
	}

	protected static function fields() {
		return [
			'brand' => [
				'label'    => 'لوگو',
				'controls' => [
					'logo'       => [ 'type' => 'media', 'label' => 'تصویر لوگو', 'default' => 'logo-sazan.png' ],
					'logo_alt'   => [ 'type' => 'text', 'label' => 'متن جایگزین لوگو', 'default' => 'لوگوی سازان' ],
					'brand_link' => [ 'type' => 'link', 'label' => 'لینک لوگو', 'default' => '#top' ],
					'brand_label' => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری لوگو', 'default' => 'حکمرانی بر بازار' ],
				],
			],
			'menu'  => [
				'label'    => 'منو',
				'controls' => [
					'menu_label' => [ 'type' => 'text', 'label' => 'عنوان دکمه منو (موبایل)', 'default' => 'باز کردن منو' ],
					'nav_label'  => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری منو', 'default' => 'منوی اصلی' ],
					'items'      => [
						'type'        => 'repeater',
						'label'       => 'آیتم‌های منو',
						'title_field' => '{{{ text }}}',
						'fields'      => [
							'text' => [ 'type' => 'text', 'label' => 'عنوان', 'default' => 'آیتم منو' ],
							'link' => [ 'type' => 'link', 'label' => 'لینک', 'default' => '#' ],
						],
						'default'     => [
							[ 'text' => 'خانه', 'link' => '#top' ],
							[ 'text' => 'درباره دوره', 'link' => '#program' ],
							[ 'text' => 'سرفصل‌ها', 'link' => '#chapters' ],
							[ 'text' => 'تجربه شرکت‌کنندگان', 'link' => '#results' ],
							[ 'text' => 'سوالات متداول', 'link' => '#faq' ],
						],
					],
				],
			],
			'cta'   => [
				'label'    => 'دکمه',
				'controls' => [
					'cta_text' => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'درخواست بررسی کار و کسب' ],
					'cta_link' => [ 'type' => 'link', 'label' => 'لینک دکمه', 'default' => '#assessment' ],
				],
			],
		];
	}

	protected function render_html( array $s ) {
		$items = self::rows( $s, 'items' );
		$nav   = '';
		foreach ( $items as $i => $item ) {
			$nav .= '<a' . ( 0 === $i ? ' class="active"' : '' ) . ' href="' . self::u( self::v( $item, 'link' ) ) . '">' . self::t( self::v( $item, 'text' ) ) . '</a>';
		}
		?>
<div id="top" class="anchor-target" aria-hidden="true"></div>
<header class="topbar">
    <div class="container nav-wrap">
      <a class="brand" href="<?php echo self::u( self::v( $s, 'brand_link' ) ); ?>" aria-label="<?php echo self::a( self::v( $s, 'brand_label' ) ); ?>">
        <img class="brand-v2" src="<?php echo esc_url( self::media_url( $s['logo'] ?? '' ) ); ?>" alt="<?php echo self::a( self::v( $s, 'logo_alt' ) ); ?>" width="244" height="88">
      </a>
      <button class="menu" aria-label="<?php echo self::a( self::v( $s, 'menu_label' ) ); ?>" aria-expanded="false"><svg><use href="#i-menu"/></svg></button>
      <nav aria-label="<?php echo self::a( self::v( $s, 'nav_label' ) ); ?>">
        <?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>

      </nav>
      <a class="btn btn-small" href="<?php echo self::u( self::v( $s, 'cta_link' ) ); ?>"><?php echo self::t( self::v( $s, 'cta_text' ) ); ?></a>
    </div>
  </header>
		<?php
	}
}
