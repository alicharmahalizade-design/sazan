<?php
/**
 * Base class of all landing widgets.
 *
 * Each widget describes
 *  - its editable content with a small schema (fields()), used both to register the
 *    Elementor content controls and to build the default settings the "build page"
 *    button writes into the new page;
 *  - its style options (styles()): groups of style controls bound to the elements of
 *    the original markup.
 *
 * Every style control is empty by default, so an untouched widget renders exactly like
 * the original page. When a value is set, it is written with a selector that is more
 * specific than any rule of the original stylesheet (and with !important where the
 * original uses it), so the chosen value always wins.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;

abstract class HKL_Widget_Base extends \Elementor\Widget_Base {

	/** Raises the specificity of style selectors above the landing stylesheet. */
	const BOOST = '.elementor-widget.elementor-widget.elementor-widget';

	/** Slug without the "hk-" prefix, e.g. "hero". */
	abstract protected static function slug();

	/** Title shown in the Elementor panel. */
	abstract protected static function title();

	/**
	 * Editable content.
	 *
	 * Returns [ section_id => [ 'label' => string, 'controls' => [ name => definition ] ] ].
	 * Definition keys: type (text|textarea|rich|link|media|icon|select|switcher|repeater),
	 * label, default, options (select), fields + title_field (repeater), description.
	 */
	abstract protected static function fields();

	/**
	 * Style groups.
	 *
	 * Returns [ key => [ 'label' => string, 'selector' => css selector(s) relative to the
	 * widget, 'kinds' => [ control kinds ], optional 'hover' => selector(s) for hover
	 * kinds, 'note' => description ] ]. See add_style_kind() for the kinds.
	 */
	protected static function styles() {
		return [];
	}

	/** Prints the widget markup. */
	abstract protected function render_html( array $s );

	/* ------------------------------------------------------------------ */

	public static function widget_name() {
		return 'hk-' . static::slug();
	}

	public function get_name() {
		return static::widget_name();
	}

	public function get_title() {
		return static::title();
	}

	public function get_icon() {
		return 'eicon-site-identity';
	}

	public function get_categories() {
		return [ HKL_Plugin::CATEGORY ];
	}

	public function get_keywords() {
		return [ 'hokmrani', 'landing', 'sazan', 'حکمرانی', 'لندینگ', static::slug() ];
	}

	public function get_style_depends() {
		return [ HKL_Plugin::HANDLE ];
	}

	public function get_script_depends() {
		return [ HKL_Plugin::HANDLE ];
	}

	/** The markup must be identical to the original page, so no extra inner wrapper. */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function is_dynamic_content(): bool {
		return false;
	}

	/* ------------------------------------------------------------------ */
	/* Wrapper                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * On the front end the Elementor wrapper normally generates no box (display: contents).
	 * When the Advanced tab gives the wrapper its own box styles (margin, background,
	 * width, position, transform…), it is marked with .hk-boxed so it becomes a real box
	 * and those settings take effect.
	 */
	protected function add_render_attributes() {
		parent::add_render_attributes();
		if ( $this->uses_wrapper_box() ) {
			$this->add_render_attribute( '_wrapper', 'class', 'hk-boxed' );
		}
	}

	private function uses_wrapper_box() {
		$settings = $this->get_settings();
		if ( ! is_array( $settings ) ) {
			return false;
		}
		$pattern = '/^_(margin|padding|background_background|background_hover_background|border_border|border_radius|box_shadow_box_shadow_type|element_width|element_custom_width|position|z_index|transform_|flex_|offset_)/';
		foreach ( $settings as $key => $value ) {
			if ( preg_match( $pattern, $key ) && self::has_value( $value ) ) {
				return true;
			}
		}
		return false;
	}

	private static function has_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( [ 'size', 'top', 'right', 'bottom', 'left', 'url', 'value' ] as $part ) {
				if ( isset( $value[ $part ] ) && '' !== $value[ $part ] && null !== $value[ $part ] ) {
					return true;
				}
			}
			return false;
		}
		return null !== $value && '' !== $value && 'none' !== $value;
	}

	/* ------------------------------------------------------------------ */
	/* Content controls                                                     */
	/* ------------------------------------------------------------------ */

	protected function register_controls() {
		foreach ( static::fields() as $section_id => $section ) {
			$this->start_controls_section(
				'section_' . $section_id,
				[
					'label' => $section['label'],
					'tab'   => Controls_Manager::TAB_CONTENT,
				]
			);
			foreach ( $section['controls'] as $name => $def ) {
				if ( 'repeater' === $def['type'] ) {
					$repeater = new \Elementor\Repeater();
					foreach ( $def['fields'] as $field_name => $field_def ) {
						$repeater->add_control( $field_name, self::control_args( $field_def ) );
					}
					$args = [
						'label'   => $def['label'],
						'type'    => Controls_Manager::REPEATER,
						'fields'  => $repeater->get_controls(),
						'default' => self::default_value( $def ),
					];
					if ( ! empty( $def['title_field'] ) ) {
						$args['title_field'] = $def['title_field'];
					}
					$this->add_control( $name, $args );
				} else {
					$this->add_control( $name, self::control_args( $def ) );
				}
			}
			$this->end_controls_section();
		}

		foreach ( static::styles() as $key => $group ) {
			$this->register_style_group( $key, $group );
		}
	}

	private static function control_args( array $def ) {
		$map  = [
			'text'     => Controls_Manager::TEXT,
			'link'     => Controls_Manager::URL,
			'textarea' => Controls_Manager::TEXTAREA,
			'rich'     => Controls_Manager::TEXTAREA,
			'media'    => Controls_Manager::MEDIA,
			'icon'     => Controls_Manager::ICONS,
			'select'   => Controls_Manager::SELECT,
			'switcher' => Controls_Manager::SWITCHER,
		];
		$args = [
			'label'       => $def['label'],
			'type'        => $map[ $def['type'] ],
			'default'     => self::default_value( $def ),
			'label_block' => true,
		];
		if ( 'link' === $def['type'] ) {
			$args['placeholder'] = '#section-id  |  https://…';
			$args['description'] = isset( $def['description'] ) ? $def['description'] : 'برای رفتن به یک بخش از همین صفحه از # و شناسه آن بخش استفاده کنید (مثلاً #faq).';
		} elseif ( 'rich' === $def['type'] ) {
			$args['description'] = isset( $def['description'] ) ? $def['description'] : 'می‌توانید از تگ‌های &lt;b&gt;، &lt;strong&gt;، &lt;em&gt;، &lt;mark&gt;، &lt;span&gt; و &lt;br&gt; استفاده کنید.';
			$args['rows']        = 3;
		} elseif ( 'icon' === $def['type'] ) {
			$args['description'] = isset( $def['description'] ) ? $def['description'] : 'خالی بماند تا آیکن طرح اصلی نمایش داده شود.';
			$args['skin']        = 'inline';
			$args['label_block'] = false;
		} elseif ( isset( $def['description'] ) ) {
			$args['description'] = $def['description'];
		}
		if ( 'textarea' === $def['type'] ) {
			$args['rows'] = isset( $def['rows'] ) ? $def['rows'] : 4;
		}
		if ( 'select' === $def['type'] ) {
			$args['options']     = $def['options'];
			$args['label_block'] = false;
		}
		if ( 'switcher' === $def['type'] ) {
			$args['return_value'] = 'yes';
			$args['label_block']  = false;
		}
		if ( in_array( $def['type'], [ 'text', 'textarea', 'rich', 'link', 'media' ], true ) ) {
			$args['dynamic'] = [ 'active' => true ];
		}
		if ( isset( $def['condition'] ) ) {
			$args['condition'] = $def['condition'];
		}
		return $args;
	}

	private static function default_value( array $def ) {
		$default = isset( $def['default'] ) ? $def['default'] : '';
		switch ( $def['type'] ) {
			case 'media':
				if ( is_array( $default ) ) {
					return $default;
				}
				return [
					'url' => $default ? HKL_Plugin::asset_url( 'img/' . $default ) : '',
					'id'  => '',
				];
			case 'link':
				if ( is_array( $default ) ) {
					return $default;
				}
				return [
					'url'               => (string) $default,
					'is_external'       => '',
					'nofollow'          => '',
					'custom_attributes' => '',
				];
			case 'icon':
				return is_array( $default ) ? $default : [ 'value' => '', 'library' => '' ];
			case 'repeater':
				$items = [];
				foreach ( (array) $default as $item ) {
					$row = [];
					foreach ( $def['fields'] as $field_name => $field_def ) {
						$row[ $field_name ] = array_key_exists( $field_name, $item )
							? self::default_value( array_merge( $field_def, [ 'default' => $item[ $field_name ] ] ) )
							: self::default_value( $field_def );
					}
					$items[] = $row;
				}
				return $items;
		}
		return $default;
	}

	/**
	 * Complete settings of this widget with the original page's content.
	 * Used by the page builder (repeater rows get the unique _id Elementor expects).
	 */
	public static function default_settings() {
		$settings = [];
		foreach ( static::fields() as $section ) {
			foreach ( $section['controls'] as $name => $def ) {
				$value = self::default_value( $def );
				if ( 'repeater' === $def['type'] ) {
					foreach ( $value as &$row ) {
						$row = [ '_id' => self::random_id() ] + $row;
					}
					unset( $row );
				}
				$settings[ $name ] = $value;
			}
		}
		return $settings;
	}

	public static function random_id() {
		return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
	}

	/* ------------------------------------------------------------------ */
	/* Style controls                                                       */
	/* ------------------------------------------------------------------ */

	/** Full selector(s) for a style group: every comma part is prefixed with the boosted wrapper. */
	private static function selector( $selectors, $suffix = '' ) {
		$parts = [];
		foreach ( explode( ',', $selectors ) as $part ) {
			$part = trim( $part );
			if ( '' === $part ) {
				continue;
			}
			// "&" means the widget root itself (the first element of the widget).
			$parts[] = '{{WRAPPER}}' . self::BOOST . ( '&' === $part ? '' : ' ' . $part ) . $suffix;
		}
		return implode( ', ', $parts );
	}

	private function register_style_group( $key, array $group ) {
		$this->start_controls_section(
			'style_' . $key,
			[
				'label' => $group['label'],
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		if ( ! empty( $group['note'] ) ) {
			$this->add_control(
				$key . '_note',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => $group['note'],
					'content_classes' => 'elementor-descriptor',
				]
			);
		}
		$hover = isset( $group['hover'] ) ? $group['hover'] : null;
		foreach ( $group['kinds'] as $kind ) {
			$this->add_style_kind( $key, $kind, $group['selector'], $hover );
		}
		$this->end_controls_section();
	}

	/**
	 * Adds one style option.
	 *
	 * Kinds: typography, color, color_hover, bg, bg_hover, background, border, border_color,
	 * border_color_hover, radius, shadow, padding, margin, width, max_width, height,
	 * min_height, gap, align, icon_size, icon_color, icon_color_hover, bg_size, bg_position,
	 * opacity, hide.
	 */
	private function add_style_kind( $key, $kind, $selectors, $hover ) {
		$sel       = self::selector( $selectors );
		$hover_sel = $hover ? self::selector( $hover ) : self::selector( $selectors, ':hover' );
		$name      = $key . '_' . $kind;

		switch ( $kind ) {
			case 'typography':
				$this->add_control(
					$name,
					[
						'label'        => 'تایپوگرافی',
						'type'         => Controls_Manager::POPOVER_TOGGLE,
						'return_value' => 'yes',
					]
				);
				$this->start_popover();
				$this->add_control(
					$name . '_family',
					[
						'label'     => 'فونت',
						'type'      => Controls_Manager::FONT,
						'default'   => '',
						'selectors' => [ $sel => 'font-family: "{{VALUE}}", Tahoma, sans-serif !important;' ],
					]
				);
				$this->add_responsive_control(
					$name . '_size',
					[
						'label'      => 'اندازه',
						'type'       => Controls_Manager::SLIDER,
						'size_units' => [ 'px', 'em', 'rem', 'vw' ],
						'range'      => [
							'px'  => [ 'min' => 6, 'max' => 120 ],
							'em'  => [ 'min' => 0.3, 'max' => 8, 'step' => 0.05 ],
							'rem' => [ 'min' => 0.3, 'max' => 8, 'step' => 0.05 ],
							'vw'  => [ 'min' => 0.5, 'max' => 15, 'step' => 0.1 ],
						],
						'selectors'  => [ $sel => 'font-size: {{SIZE}}{{UNIT}} !important;' ],
					]
				);
				$this->add_control(
					$name . '_weight',
					[
						'label'     => 'ضخامت',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''    => 'پیش‌فرض',
							'100' => '100',
							'200' => '200',
							'300' => '300',
							'400' => '400 (عادی)',
							'500' => '500',
							'600' => '600',
							'700' => '700 (ضخیم)',
							'800' => '800',
							'900' => '900',
						],
						'selectors' => [ $sel => 'font-weight: {{VALUE}} !important;' ],
					]
				);
				$this->add_responsive_control(
					$name . '_line_height',
					[
						'label'      => 'ارتفاع خط',
						'type'       => Controls_Manager::SLIDER,
						'size_units' => [ 'em', 'px' ],
						'range'      => [
							'em' => [ 'min' => 0.6, 'max' => 3, 'step' => 0.05 ],
							'px' => [ 'min' => 6, 'max' => 120 ],
						],
						'selectors'  => [ $sel => 'line-height: {{SIZE}}{{UNIT}} !important;' ],
					]
				);
				$this->add_responsive_control(
					$name . '_letter_spacing',
					[
						'label'      => 'فاصله حروف',
						'type'       => Controls_Manager::SLIDER,
						'size_units' => [ 'px', 'em' ],
						'range'      => [
							'px' => [ 'min' => -5, 'max' => 10, 'step' => 0.1 ],
							'em' => [ 'min' => -0.3, 'max' => 0.6, 'step' => 0.01 ],
						],
						'selectors'  => [ $sel => 'letter-spacing: {{SIZE}}{{UNIT}} !important;' ],
					]
				);
				$this->add_control(
					$name . '_style',
					[
						'label'     => 'حالت',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''        => 'پیش‌فرض',
							'normal'  => 'عادی',
							'italic'  => 'مورب',
						],
						'selectors' => [ $sel => 'font-style: {{VALUE}} !important;' ],
					]
				);
				$this->add_control(
					$name . '_decoration',
					[
						'label'     => 'خط‌کشی',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''             => 'پیش‌فرض',
							'none'         => 'هیچ',
							'underline'    => 'زیرخط',
							'line-through' => 'خط‌خورده',
						],
						'selectors' => [ $sel => 'text-decoration: {{VALUE}} !important;' ],
					]
				);
				$this->end_popover();
				break;

			case 'color':
			case 'color_hover':
			case 'icon_color':
			case 'icon_color_hover':
				$labels = [
					'color'            => 'رنگ متن',
					'color_hover'      => 'رنگ متن (هاور)',
					'icon_color'       => 'رنگ آیکن',
					'icon_color_hover' => 'رنگ آیکن (هاور)',
				];
				$target = in_array( $kind, [ 'color_hover', 'icon_color_hover' ], true ) ? $hover_sel : $sel;
				$this->add_control(
					$name,
					[
						'label'     => $labels[ $kind ],
						'type'      => Controls_Manager::COLOR,
						'selectors' => [ $target => 'color: {{VALUE}} !important;' ],
					]
				);
				break;

			case 'bg':
			case 'bg_hover':
				$this->add_control(
					$name,
					[
						'label'     => 'bg' === $kind ? 'رنگ پس‌زمینه' : 'رنگ پس‌زمینه (هاور)',
						'type'      => Controls_Manager::COLOR,
						'selectors' => [ 'bg' === $kind ? $sel : $hover_sel => 'background: {{VALUE}} !important;' ],
					]
				);
				break;

			case 'background':
				$this->add_control(
					$name,
					[
						'label'   => 'نوع پس‌زمینه',
						'type'    => Controls_Manager::CHOOSE,
						'options' => [
							'classic'  => [ 'title' => 'رنگ / تصویر', 'icon' => 'eicon-paint-brush' ],
							'gradient' => [ 'title' => 'گرادیان', 'icon' => 'eicon-barcode' ],
						],
						'toggle'  => true,
					]
				);
				$this->add_control(
					$name . '_color',
					[
						'label'     => 'رنگ',
						'type'      => Controls_Manager::COLOR,
						'condition' => [ $name . '!' => '' ],
						'selectors' => [ $sel => 'background-color: {{VALUE}} !important; background-image: none !important;' ],
					]
				);
				$this->add_control(
					$name . '_image',
					[
						'label'     => 'تصویر',
						'type'      => Controls_Manager::MEDIA,
						'condition' => [ $name => 'classic' ],
						'selectors' => [ $sel => 'background-image: url("{{URL}}") !important;' ],
					]
				);
				$this->add_responsive_control(
					$name . '_size',
					[
						'label'     => 'اندازه تصویر',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''        => 'پیش‌فرض',
							'cover'   => 'پوشش کامل',
							'contain' => 'نمایش کامل',
							'auto'    => 'اندازه واقعی',
						],
						'condition' => [ $name => 'classic' ],
						'selectors' => [ $sel => 'background-size: {{VALUE}} !important;' ],
					]
				);
				$this->add_responsive_control(
					$name . '_position',
					[
						'label'     => 'موقعیت تصویر',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''              => 'پیش‌فرض',
							'center center' => 'وسط',
							'right center'  => 'راست',
							'left center'   => 'چپ',
							'center top'    => 'بالا',
							'center bottom' => 'پایین',
						],
						'condition' => [ $name => 'classic' ],
						'selectors' => [ $sel => 'background-position: {{VALUE}} !important;' ],
					]
				);
				$this->add_control(
					$name . '_repeat',
					[
						'label'     => 'تکرار تصویر',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''          => 'پیش‌فرض',
							'no-repeat' => 'بدون تکرار',
							'repeat'    => 'تکرار',
						],
						'condition' => [ $name => 'classic' ],
						'selectors' => [ $sel => 'background-repeat: {{VALUE}} !important;' ],
					]
				);
				$this->add_control(
					$name . '_color_b',
					[
						'label'     => 'رنگ دوم',
						'type'      => Controls_Manager::COLOR,
						'default'   => '',
						'condition' => [ $name => 'gradient' ],
					]
				);
				$this->add_control(
					$name . '_angle',
					[
						'label'      => 'زاویه گرادیان',
						'type'       => Controls_Manager::SLIDER,
						'size_units' => [ 'deg' ],
						'default'    => [ 'unit' => 'deg', 'size' => 180 ],
						'range'      => [ 'deg' => [ 'min' => 0, 'max' => 360, 'step' => 5 ] ],
						'condition'  => [ $name => 'gradient' ],
						'selectors'  => [ $sel => 'background-image: linear-gradient({{SIZE}}{{UNIT}}, {{' . $name . '_color.VALUE}}, {{' . $name . '_color_b.VALUE}}) !important;' ],
					]
				);
				break;

			case 'border':
				$this->add_group_control(
					\Elementor\Group_Control_Border::get_type(),
					[
						'name'           => $name,
						'label'          => 'حاشیه',
						'selector'       => $sel,
						'fields_options' => [
							'border' => [ 'selectors' => [ '{{SELECTOR}}' => 'border-style: {{VALUE}} !important;' ] ],
							'width'  => [ 'selectors' => [ '{{SELECTOR}}' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ] ],
							'color'  => [ 'selectors' => [ '{{SELECTOR}}' => 'border-color: {{VALUE}} !important;' ] ],
						],
					]
				);
				break;

			case 'border_color':
			case 'border_color_hover':
				$this->add_control(
					$name,
					[
						'label'     => 'border_color' === $kind ? 'رنگ حاشیه' : 'رنگ حاشیه (هاور)',
						'type'      => Controls_Manager::COLOR,
						'selectors' => [ 'border_color' === $kind ? $sel : $hover_sel => 'border-color: {{VALUE}} !important;' ],
					]
				);
				break;

			case 'radius':
			case 'padding':
			case 'margin':
				$props = [
					'radius'  => [ 'گردی گوشه‌ها', 'border-radius' ],
					'padding' => [ 'فاصله داخلی (Padding)', 'padding' ],
					'margin'  => [ 'فاصله خارجی (Margin)', 'margin' ],
				];
				$this->add_responsive_control(
					$name,
					[
						'label'      => $props[ $kind ][0],
						'type'       => Controls_Manager::DIMENSIONS,
						'size_units' => [ 'px', '%', 'em', 'rem', 'vw' ],
						'selectors'  => [ $sel => $props[ $kind ][1] . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;' ],
					]
				);
				break;

			case 'shadow':
				$this->add_group_control(
					\Elementor\Group_Control_Box_Shadow::get_type(),
					[
						'name'           => $name,
						'label'          => 'سایه',
						'selector'       => $sel,
						'fields_options' => [
							'box_shadow' => [ 'selectors' => [ '{{SELECTOR}}' => 'box-shadow: {{box_shadow_position.VALUE}} {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{SPREAD}}px {{COLOR}} !important;' ] ],
						],
					]
				);
				break;

			case 'width':
			case 'max_width':
			case 'height':
			case 'min_height':
			case 'gap':
			case 'icon_size':
				$props = [
					'width'      => [ 'عرض', 'width: {{SIZE}}{{UNIT}} !important;', 1200 ],
					'max_width'  => [ 'حداکثر عرض', 'max-width: {{SIZE}}{{UNIT}} !important;', 1600 ],
					'height'     => [ 'ارتفاع', 'height: {{SIZE}}{{UNIT}} !important;', 1000 ],
					'min_height' => [ 'حداقل ارتفاع', 'min-height: {{SIZE}}{{UNIT}} !important;', 1200 ],
					'gap'        => [ 'فاصله بین آیتم‌ها', 'gap: {{SIZE}}{{UNIT}} !important;', 120 ],
					'icon_size'  => [ 'اندازه آیکن', 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; font-size: {{SIZE}}{{UNIT}} !important;', 200 ],
				];
				$this->add_responsive_control(
					$name,
					[
						'label'      => $props[ $kind ][0],
						'type'       => Controls_Manager::SLIDER,
						'size_units' => in_array( $kind, [ 'gap', 'icon_size' ], true ) ? [ 'px', 'em', 'rem' ] : [ 'px', '%', 'vw', 'vh', 'em', 'rem' ],
						'range'      => [
							'px' => [ 'min' => 0, 'max' => $props[ $kind ][2] ],
							'%'  => [ 'min' => 0, 'max' => 100 ],
						],
						'selectors'  => [ $sel => $props[ $kind ][1] ],
					]
				);
				break;

			case 'box_size':
				$this->add_responsive_control(
					$name,
					[
						'label'      => 'اندازه (عرض و ارتفاع)',
						'type'       => Controls_Manager::SLIDER,
						'size_units' => [ 'px', 'em', 'rem' ],
						'range'      => [ 'px' => [ 'min' => 0, 'max' => 400 ] ],
						'selectors'  => [ $sel => 'width: {{SIZE}}{{UNIT}} !important; height: {{SIZE}}{{UNIT}} !important; min-width: {{SIZE}}{{UNIT}} !important; min-height: {{SIZE}}{{UNIT}} !important; flex: 0 0 {{SIZE}}{{UNIT}} !important;' ],
					]
				);
				break;

			case 'align':
				$this->add_responsive_control(
					$name,
					[
						'label'     => 'چینش متن',
						'type'      => Controls_Manager::CHOOSE,
						'options'   => [
							'right'   => [ 'title' => 'راست', 'icon' => 'eicon-text-align-right' ],
							'center'  => [ 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ],
							'left'    => [ 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ],
							'justify' => [ 'title' => 'تراز', 'icon' => 'eicon-text-align-justify' ],
						],
						'selectors' => [ $sel => 'text-align: {{VALUE}} !important;' ],
					]
				);
				break;

			case 'bg_size':
				$this->add_responsive_control(
					$name,
					[
						'label'     => 'اندازه تصویر پس‌زمینه',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''          => 'پیش‌فرض طرح',
							'cover'     => 'پوشش کامل (Cover)',
							'contain'   => 'نمایش کامل (Contain)',
							'auto'      => 'اندازه واقعی',
							'100% 100%' => 'کشیده',
						],
						'selectors' => [ $sel => 'background-size: {{VALUE}} !important;' ],
					]
				);
				break;

			case 'bg_position':
				$this->add_responsive_control(
					$name,
					[
						'label'     => 'موقعیت تصویر پس‌زمینه',
						'type'      => Controls_Manager::SELECT,
						'default'   => '',
						'options'   => [
							''              => 'پیش‌فرض طرح',
							'center center' => 'وسط',
							'right center'  => 'راست',
							'left center'   => 'چپ',
							'center top'    => 'بالا',
							'center bottom' => 'پایین',
							'right top'     => 'بالا راست',
							'left top'      => 'بالا چپ',
							'right bottom'  => 'پایین راست',
							'left bottom'   => 'پایین چپ',
						],
						'selectors' => [ $sel => 'background-position: {{VALUE}} !important;' ],
					]
				);
				break;

			case 'opacity':
				$this->add_control(
					$name,
					[
						'label'     => 'شفافیت',
						'type'      => Controls_Manager::SLIDER,
						'range'     => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.01 ] ],
						'selectors' => [ $sel => 'opacity: {{SIZE}} !important;' ],
					]
				);
				break;

			case 'speed':
				$this->add_control(
					$name,
					[
						'label'       => 'مدت یک دور حرکت (ثانیه)',
						'description' => 'عدد کمتر یعنی حرکت سریع‌تر.',
						'type'        => Controls_Manager::SLIDER,
						'range'       => [ 'px' => [ 'min' => 2, 'max' => 120, 'step' => 1 ] ],
						'selectors'   => [ $sel => 'animation-duration: {{SIZE}}s !important;' ],
					]
				);
				break;

			case 'hide':
				$this->add_responsive_control(
					$name,
					[
						'label'        => 'پنهان شود',
						'type'         => Controls_Manager::SWITCHER,
						'return_value' => 'yes',
						'selectors'    => [ $sel => 'display: none !important;' ],
					]
				);
				break;
		}
	}

	/* ------------------------------------------------------------------ */
	/* Rendering helpers                                                    */
	/* ------------------------------------------------------------------ */

	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->render_html( is_array( $settings ) ? $settings : [] );
	}

	/** Escaped plain text. */
	protected static function t( $value ) {
		return esc_html( (string) $value );
	}

	/** Escaped attribute. */
	protected static function a( $value ) {
		return esc_attr( (string) $value );
	}

	/** Escaped URL (keeps #anchors, tel: and mailto:). Accepts a URL control value. */
	protected static function u( $value ) {
		if ( is_array( $value ) ) {
			$value = isset( $value['url'] ) ? $value['url'] : '';
		}
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '#';
		}
		if ( '#' === $value[0] ) {
			return esc_attr( $value );
		}
		return esc_url( $value );
	}

	/**
	 * href plus the optional attributes of an Elementor URL control
	 * (new tab, nofollow, custom attributes). Returns ' href="…"…' (leading space).
	 */
	protected static function href( $value ) {
		$html = ' href="' . self::u( $value ) . '"';
		if ( ! is_array( $value ) ) {
			return $html;
		}
		$rel = [];
		if ( ! empty( $value['is_external'] ) ) {
			$html .= ' target="_blank"';
			$rel[] = 'noopener';
		}
		if ( ! empty( $value['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}
		if ( $rel ) {
			$html .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
		}
		if ( ! empty( $value['custom_attributes'] ) ) {
			foreach ( explode( ',', $value['custom_attributes'] ) as $pair ) {
				$parts = explode( '|', $pair, 2 );
				$attr  = strtolower( trim( $parts[0] ) );
				if ( '' === $attr || ! preg_match( '/^[a-z_:][a-z0-9_.:-]*$/', $attr ) || 0 === strpos( $attr, 'on' ) || in_array( $attr, [ 'href', 'class', 'style' ], true ) ) {
					continue;
				}
				$html .= ' ' . $attr . '="' . esc_attr( isset( $parts[1] ) ? trim( $parts[1] ) : '' ) . '"';
			}
		}
		return $html;
	}

	/** Inline HTML allowed in "rich" fields. */
	protected static function r( $value ) {
		static $allowed = [
			'b'      => [ 'class' => true, 'style' => true ],
			'strong' => [ 'class' => true, 'style' => true ],
			'em'     => [ 'class' => true, 'style' => true ],
			'i'      => [ 'class' => true, 'aria-hidden' => true, 'style' => true ],
			'mark'   => [ 'class' => true, 'style' => true ],
			'span'   => [ 'class' => true, 'aria-hidden' => true, 'style' => true ],
			'bdi'    => [],
			'small'  => [ 'class' => true, 'style' => true ],
			'u'      => [],
			'br'     => [],
			'a'      => [ 'href' => true, 'class' => true, 'target' => true, 'rel' => true ],
		];
		return wp_kses( (string) $value, $allowed );
	}

	/** Is a custom icon chosen in an ICONS control? */
	protected static function has_icon( $icon ) {
		return is_array( $icon ) && ! empty( $icon['value'] );
	}

	/**
	 * A custom icon chosen in an ICONS control, or $fallback (the original markup).
	 * Font icons are printed as inline SVG when possible so they inherit the size and
	 * colour rules the original stylesheet has for its SVG icons.
	 */
	protected static function icon( $icon, $fallback ) {
		if ( ! self::has_icon( $icon ) || ! class_exists( '\Elementor\Icons_Manager' ) ) {
			return $fallback;
		}
		$html = '';
		if ( 'svg' !== ( $icon['library'] ?? '' ) && method_exists( '\Elementor\Icons_Manager', 'get_font_icon_svg' ) ) {
			$html = (string) \Elementor\Icons_Manager::get_font_icon_svg( $icon, [ 'aria-hidden' => 'true' ] );
		}
		if ( '' === $html ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
			$html = (string) ob_get_clean();
		}
		return '' === trim( $html ) ? $fallback : '<span class="hk-icon" aria-hidden="true">' . $html . '</span>';
	}

	/** Media control value → URL. */
	protected static function media_url( $value ) {
		if ( is_array( $value ) ) {
			return isset( $value['url'] ) ? (string) $value['url'] : '';
		}
		return (string) $value;
	}

	/**
	 * width/height attributes of an image: the real size for library images,
	 * otherwise the size of the original artwork.
	 */
	protected static function media_size( $value, $width, $height ) {
		if ( is_array( $value ) && ! empty( $value['id'] ) ) {
			$src = wp_get_attachment_image_src( (int) $value['id'], 'full' );
			if ( $src && ! empty( $src[1] ) && ! empty( $src[2] ) ) {
				return [ (int) $src[1], (int) $src[2] ];
			}
		}
		return [ $width, $height ];
	}

	/** Non-empty lines of a textarea. */
	protected static function lines( $value ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
		return array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
	}

	/** Latin digits → Persian digits. */
	protected static function fa_digits( $value ) {
		return strtr( (string) $value, [ '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ] );
	}

	/** id="…" attribute (empty when no id is set). */
	protected static function id_attr( $value ) {
		$value = trim( (string) $value );
		return '' === $value ? '' : ' id="' . esc_attr( $value ) . '"';
	}

	/** Repeater rows (always an array). */
	protected static function rows( array $s, $key ) {
		return isset( $s[ $key ] ) && is_array( $s[ $key ] ) ? $s[ $key ] : [];
	}

	/** A string setting. */
	protected static function v( array $s, $key ) {
		return isset( $s[ $key ] ) && ! is_array( $s[ $key ] ) ? (string) $s[ $key ] : '';
	}

	/** An array setting (URL / media / icon control values). */
	protected static function arr( array $s, $key ) {
		return isset( $s[ $key ] ) && is_array( $s[ $key ] ) ? $s[ $key ] : ( isset( $s[ $key ] ) ? [ 'url' => (string) $s[ $key ] ] : [] );
	}

	/** Common "section box" style group of a widget root. */
	protected static function section_style( $selector, $label = 'بخش (پس‌زمینه و فاصله‌ها)' ) {
		return [
			'label'    => $label,
			'selector' => $selector,
			'kinds'    => [ 'background', 'padding', 'margin', 'min_height', 'border', 'radius', 'shadow' ],
			'note'     => 'فاصله، پس‌زمینه و حاشیه کل این بخش. (برای ظاهر یکسان با نسخه اصلی، این تنظیمات را خالی بگذارید.)',
		];
	}

	/** Common style group of a text element. */
	protected static function text_style( $label, $selector, array $extra = [] ) {
		return [
			'label'    => $label,
			'selector' => $selector,
			'kinds'    => array_merge( [ 'typography', 'color', 'align', 'margin' ], $extra ),
		];
	}

	/** Common style group of a button / link. */
	protected static function button_style( $label, $selector, array $extra = [] ) {
		return [
			'label'    => $label,
			'selector' => $selector,
			'kinds'    => array_merge( [ 'typography', 'color', 'color_hover', 'background', 'bg_hover', 'border', 'border_color_hover', 'radius', 'padding', 'shadow' ], $extra ),
		];
	}

	/** Common style group of a card / box. */
	protected static function box_style( $label, $selector, array $extra = [] ) {
		return [
			'label'    => $label,
			'selector' => $selector,
			'kinds'    => array_merge( [ 'background', 'border', 'radius', 'padding', 'shadow', 'min_height' ], $extra ),
		];
	}

	/** Common style group of an icon. */
	protected static function icon_style( $label, $selector, array $extra = [] ) {
		// Font icons that could not be printed as SVG are <i> elements inside .hk-icon.
		$parts = array_map( 'trim', explode( ',', $selector ) );
		foreach ( $parts as $part ) {
			if ( 'svg' === substr( $part, -3 ) ) {
				$parts[] = substr( $part, 0, -3 ) . '.hk-icon > i';
			}
		}
		return [
			'label'    => $label,
			'selector' => implode( ', ', $parts ),
			'kinds'    => array_merge( [ 'icon_size', 'icon_color' ], $extra ),
		];
	}
}
