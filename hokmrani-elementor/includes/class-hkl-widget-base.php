<?php
/**
 * Base class of all landing widgets.
 *
 * Each widget describes its editable content with a small schema (see fields()).
 * The schema is used both to register the Elementor controls and to build the
 * default settings that the "build page" button writes into the new page, so the
 * editor, the default output and the generated page can never drift apart.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class HKL_Widget_Base extends \Elementor\Widget_Base {

	/** Slug without the "hk-" prefix, e.g. "hero". */
	abstract protected static function slug();

	/** Title shown in the Elementor panel. */
	abstract protected static function title();

	/**
	 * Editable content.
	 *
	 * Returns [ section_id => [ 'label' => string, 'controls' => [ name => definition ] ] ].
	 * Definition keys: type (text|textarea|rich|link|media|select|switcher|repeater),
	 * label, default, options (select), fields + title_field (repeater), description.
	 */
	abstract protected static function fields();

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
	/* Controls                                                             */
	/* ------------------------------------------------------------------ */

	protected function register_controls() {
		foreach ( static::fields() as $section_id => $section ) {
			$this->start_controls_section(
				'section_' . $section_id,
				[
					'label' => $section['label'],
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
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
						'type'    => \Elementor\Controls_Manager::REPEATER,
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
	}

	private static function control_args( array $def ) {
		$map  = [
			'text'     => \Elementor\Controls_Manager::TEXT,
			'link'     => \Elementor\Controls_Manager::TEXT,
			'textarea' => \Elementor\Controls_Manager::TEXTAREA,
			'rich'     => \Elementor\Controls_Manager::TEXTAREA,
			'media'    => \Elementor\Controls_Manager::MEDIA,
			'select'   => \Elementor\Controls_Manager::SELECT,
			'switcher' => \Elementor\Controls_Manager::SWITCHER,
		];
		$args = [
			'label'       => $def['label'],
			'type'        => $map[ $def['type'] ],
			'default'     => self::default_value( $def ),
			'label_block' => true,
		];
		if ( 'link' === $def['type'] ) {
			$args['placeholder'] = '#section-id  |  https://…';
			$args['description'] = isset( $def['description'] ) ? $def['description'] : 'آدرس لینک؛ برای رفتن به یک بخش از همین صفحه از # و شناسه آن بخش استفاده کنید.';
		}
		if ( 'rich' === $def['type'] ) {
			$args['description'] = isset( $def['description'] ) ? $def['description'] : 'می‌توانید از تگ‌های &lt;b&gt;، &lt;strong&gt;، &lt;em&gt;، &lt;mark&gt;، &lt;span&gt; و &lt;br&gt; استفاده کنید.';
			$args['rows']        = 3;
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
		return $args;
	}

	private static function default_value( array $def ) {
		$default = isset( $def['default'] ) ? $def['default'] : '';
		if ( 'media' === $def['type'] ) {
			return [
				'url' => $default ? HKL_Plugin::asset_url( 'img/' . $default ) : '',
				'id'  => '',
			];
		}
		if ( 'repeater' === $def['type'] ) {
			$items = [];
			foreach ( (array) $default as $item ) {
				$row = [];
				foreach ( $def['fields'] as $field_name => $field_def ) {
					$row[ $field_name ] = array_key_exists( $field_name, $item )
						? ( 'media' === $field_def['type'] ? self::default_value( array_merge( $field_def, [ 'default' => $item[ $field_name ] ] ) ) : $item[ $field_name ] )
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

	/** Escaped URL (keeps #anchors, tel: and mailto:). */
	protected static function u( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '#';
		}
		if ( '#' === $value[0] ) {
			return esc_attr( $value );
		}
		return esc_url( $value );
	}

	/** Inline HTML allowed in "rich" fields. */
	protected static function r( $value ) {
		static $allowed = [
			'b'      => [ 'class' => true ],
			'strong' => [ 'class' => true ],
			'em'     => [ 'class' => true ],
			'i'      => [ 'class' => true, 'aria-hidden' => true ],
			'mark'   => [ 'class' => true ],
			'span'   => [ 'class' => true, 'aria-hidden' => true ],
			'bdi'    => [],
			'small'  => [ 'class' => true ],
			'br'     => [],
			'a'      => [ 'href' => true, 'class' => true, 'target' => true, 'rel' => true ],
		];
		return wp_kses( (string) $value, $allowed );
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
}
