<?php
/**
 * Teacher, mentor and coach cards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKL_Widget_Teachers extends HKL_Widget_Base {

	protected static function slug() {
		return 'teachers';
	}

	protected static function title() {
		return 'حکمرانی — معرفی اساتید';
	}

	public function get_icon() {
		return 'eicon-person';
	}

	protected static function fields() {
		return [
			'intro'    => [
				'label'    => 'عنوان بخش',
				'controls' => [
					'section_id' => [ 'type' => 'text', 'label' => 'شناسه بخش (Anchor)', 'default' => 'teachers' ],
					'title'      => [ 'type' => 'text', 'label' => 'تیتر', 'default' => 'معرفی استاد، منتور و کوچ' ],
				],
			],
			'teachers' => [
				'label'    => 'افراد',
				'controls' => [
					'teachers' => [
						'type'        => 'repeater',
						'label'       => 'افراد',
						'title_field' => '{{{ name }}}',
						'fields'      => [
							'photo' => [ 'type' => 'media', 'label' => 'عکس (اختیاری)', 'default' => '', 'description' => 'خالی بماند تا آواتار طرح اصلی نمایش داده شود.' ],
							'name'  => [ 'type' => 'text', 'label' => 'نام', 'default' => 'نام' ],
							'role'  => [ 'type' => 'text', 'label' => 'نقش', 'default' => '' ],
							'text'  => [ 'type' => 'textarea', 'label' => 'توضیح', 'rows' => 2, 'default' => '' ],
							'link'  => [ 'type' => 'link', 'label' => 'لینک (اختیاری)', 'default' => '', 'description' => 'اگر وارد شود نام فرد لینک می‌شود.' ],
						],
						'default'     => [
							[ 'name' => 'عباس شانه سازان', 'role' => 'مدرس و راهبر برنامه', 'text' => 'بیش از ۲۰ سال تجربه در فروش و توسعه کار و کسب' ],
							[ 'name' => 'دکتر علی ویسی تبار', 'role' => 'کوچ و منتور کار و کسب', 'text' => 'متخصص استراتژی، بازاریابی و توسعه بازار' ],
							[ 'name' => 'مونا کمایی', 'role' => 'کوچ و منتور کار و کسب', 'text' => 'تجربه در توسعه کار و کسب، فروش و برنامه‌ریزی' ],
						],
					],
				],
			],
		];
	}

	protected static function styles() {
		return [
			'section' => self::section_style( '.teachers' ),
			'title'   => self::text_style( 'تیتر', '.teachers .section-title h2' ),
			'grid'    => [
				'label'    => 'چیدمان',
				'selector' => '.teacher-grid',
				'kinds'    => [ 'gap' ],
			],
			'card'    => self::box_style( 'کارت', '.teacher-grid article', [ 'gap', 'border_color_hover' ] ),
			'avatar'  => [
				'label'    => 'تصویر / آواتار',
				'selector' => '.teacher-grid .avatar',
				'kinds'    => [ 'background', 'box_size', 'radius', 'border', 'shadow' ],
			],
			'name'    => [
				'label'    => 'نام',
				'selector' => '.teacher-grid h3, .teacher-grid h3 a',
				'kinds'    => [ 'typography', 'color', 'color_hover', 'margin' ],
			],
			'role'    => [
				'label'    => 'نقش',
				'selector' => '.teacher-grid b',
				'kinds'    => [ 'typography', 'color', 'margin' ],
			],
			'text'    => [
				'label'    => 'توضیح',
				'selector' => '.teacher-grid p',
				'kinds'    => [ 'typography', 'color', 'margin' ],
			],
		];
	}

	protected function render_html( array $s ) {
		?>
<section<?php echo self::id_attr( self::v( $s, 'section_id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="teachers white-section section-pad">
      <div class="container"><div class="section-title"><h2><?php echo self::t( self::v( $s, 'title' ) ); ?></h2></div><div class="teacher-grid">
<?php
		foreach ( self::rows( $s, 'teachers' ) as $i => $person ) :
			$photo = self::media_url( $person['photo'] ?? '' );
			$attr  = $photo ? ' has-photo" style="background-image:url(&quot;' . esc_url( $photo ) . '&quot;)' : '';
			?>
        <article><div class="avatar av<?php echo (int) ( $i % 3 ) + 1; ?><?php echo $attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"></div><div><h3><?php echo $this->name_html( $person ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h3><b><?php echo self::t( self::v( $person, 'role' ) ); ?></b><p><?php echo self::t( self::v( $person, 'text' ) ); ?></p></div></article>
<?php endforeach; ?>
      </div></div>
    </section>
		<?php
	}

	private function name_html( array $person ) {
		$name = self::t( self::v( $person, 'name' ) );
		$link = self::arr( $person, 'link' );
		return empty( $link['url'] ) ? $name : '<a' . self::href( $link ) . '>' . $name . '</a>';
	}
}
