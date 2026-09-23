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
					'logo'        => [ 'type' => 'media', 'label' => 'تصویر لوگو', 'default' => 'logo-sazan.png' ],
					'logo_alt'    => [ 'type' => 'text', 'label' => 'متن جایگزین لوگو', 'default' => 'لوگوی سازان' ],
					'brand_link'  => [ 'type' => 'link', 'label' => 'لینک لوگو', 'default' => '#top' ],
					'brand_label' => [ 'type' => 'text', 'label' => 'عنوان دسترسی‌پذیری لوگو', 'default' => 'حکمرانی بر بازار' ],
				],
			],
			'menu'  => [
				'label'    => 'منو',
				'controls' => [
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
					'menu_icon'  => [ 'type' => 'icon', 'label' => 'آیکن دکمه منو (موبایل)' ],
					'menu_label' => [ 'type' => 'text', 'label' => 'عنوان دکمه منو (دسترسی‌پذیری)', 'default' => 'باز کردن منو' ],
					'nav_label'  => [ 'type' => 'text', 'label' => 'عنوان منو (دسترسی‌پذیری)', 'default' => 'منوی اصلی' ],
				],
			],
			'cta'   => [
				'label'    => 'دکمه',
				'controls' => [
					'show_cta' => [ 'type' => 'switcher', 'label' => 'نمایش دکمه', 'default' => 'yes' ],
					'cta_text' => [ 'type' => 'text', 'label' => 'متن دکمه', 'default' => 'درخواست بررسی کار و کسب' ],
					'cta_link' => [ 'type' => 'link', 'label' => 'لینک دکمه', 'default' => '#assessment' ],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'bar'         => [
				'label'    => 'نوار هدر',
				'selector' => '.topbar',
				'kinds'    => [ 'background', 'height', 'border', 'shadow' ],
			],
			'layout'      => [
				'label'    => 'چیدمان',
				'selector' => '.nav-wrap',
				'kinds'    => [ 'max_width', 'gap', 'padding' ],
			],
			'logo'        => [
				'label'    => 'لوگو',
				'selector' => '.brand-v2',
				'kinds'    => [ 'height', 'max_width', 'opacity' ],
			],
			'menu'        => [
				'label'    => 'منو',
				'selector' => '.nav-wrap nav',
				'kinds'    => [ 'gap' ],
			],
			'links'       => [
				'label'    => 'لینک‌های منو',
				'selector' => '.nav-wrap nav a',
				'hover'    => '.nav-wrap nav a:hover, .nav-wrap nav a.active',
				'kinds'    => [ 'typography', 'color', 'color_hover', 'border_color_hover', 'padding' ],
			],
			'mobile_menu' => [
				'label'    => 'منوی باز شده (موبایل)',
				'selector' => '.nav-wrap nav.open',
				'kinds'    => [ 'background', 'padding', 'radius', 'shadow' ],
			],
			'toggle'      => [
				'label'    => 'دکمه منو (موبایل)',
				'selector' => '.menu',
				'kinds'    => [ 'icon_color', 'bg', 'border_color', 'radius', 'width', 'height' ],
			],
			'toggle_icon' => self::icon_style( 'آیکن دکمه منو', '.menu svg' ),
			'cta'         => self::button_style( 'دکمه', '.nav-wrap .btn', [ 'hide' ] ),
		];
	}

	protected function render_html( array $s ) {
		$nav = '';
		foreach ( self::rows( $s, 'items' ) as $i => $item ) {
			$nav .= '<a' . ( 0 === $i ? ' class="active"' : '' ) . self::href( self::arr( $item, 'link' ) ) . '>' . self::t( self::v( $item, 'text' ) ) . '</a>';
		}
		?>
<div id="top" class="anchor-target" aria-hidden="true"></div>
<header class="topbar">
    <div class="container nav-wrap">
      <a class="brand"<?php echo self::href( self::arr( $s, 'brand_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo self::a( self::v( $s, 'brand_label' ) ); ?>">
        <img class="brand-v2" src="<?php echo esc_url( self::media_url( $s['logo'] ?? '' ) ); ?>" alt="<?php echo self::a( self::v( $s, 'logo_alt' ) ); ?>" width="244" height="88">
      </a>
      <button class="menu" aria-label="<?php echo self::a( self::v( $s, 'menu_label' ) ); ?>" aria-expanded="false"><?php echo self::icon( $s['menu_icon'] ?? [], '<svg><use href="#i-menu"/></svg>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
      <nav aria-label="<?php echo self::a( self::v( $s, 'nav_label' ) ); ?>">
        <?php echo $nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>

      </nav>
<?php if ( 'yes' === self::v( $s, 'show_cta' ) ) : ?>
      <a class="btn btn-small"<?php echo self::href( self::arr( $s, 'cta_link' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo self::t( self::v( $s, 'cta_text' ) ); ?></a>
<?php endif; ?>
    </div>
  </header>
		<?php
	}
}
