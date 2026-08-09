<?php
/**
 * ویجت‌های صفحه فرود «ارزیابی کسب‌وکار».
 * هر سکشن یک ویجت مستقل با تنظیمات کامل محتوایی و استایلی.
 *
 * @package Sazan_Panel
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ==================== BASE ==================== */

abstract class SZL_Widget_Base extends \Elementor\Widget_Base {

	public function get_categories() { return array( 'sazan-panel' ); }
	public function get_icon() { return 'eicon-single-page'; }
	public function get_keywords() {
		return array( 'sazan', 'landing', 'assessment', 'سازان', 'ارزیابی', 'صفحه فرود' );
	}

	/** خروجی نهایی ویجت. */
	abstract protected function html( array $s );

	public function render() {
		wp_enqueue_style( 'szl-landing' );
		wp_enqueue_script( 'szl-landing' );
		echo $this->html( (array) $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/* ---------------- helpers: markup ---------------- */

	/** رندر آیکن کنترل ICONS. */
	protected function icon( $icon, $class = '' ) {
		if ( empty( $icon['value'] ) ) { return ''; }
		ob_start();
		\Elementor\Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
		$out = ob_get_clean();
		return $class ? '<span class="' . esc_attr( $class ) . '">' . $out . '</span>' : $out;
	}

	/** ساخت attribute برای لینک کنترل URL. */
	protected function link_attrs( $url ) {
		if ( empty( $url['url'] ) ) { return 'href="#"'; }
		$a = 'href="' . esc_url( $url['url'] ) . '"';
		if ( ! empty( $url['is_external'] ) ) { $a .= ' target="_blank"'; }
		if ( ! empty( $url['nofollow'] ) ) { $a .= ' rel="nofollow"'; }
		return $a;
	}

	/** تصویر کنترل MEDIA. */
	protected function img( $media, $alt = '' ) {
		if ( empty( $media['url'] ) ) { return ''; }
		return '<img src="' . esc_url( $media['url'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" />';
	}

	/** دکمه استاندارد. */
	protected function button( $text, $url, $variant = 'gold', $icon = null, $class = '' ) {
		if ( '' === trim( (string) $text ) ) { return ''; }
		return '<a ' . $this->link_attrs( (array) $url ) . ' class="szl-btn szl-btn--' . esc_attr( $variant ) . ' ' . esc_attr( $class ) . '">'
			. esc_html( $text )
			. ( $icon ? $this->icon( $icon ) : '' )
			. '</a>';
	}

	/** تبدیل عدد به ارقام فارسی. */
	protected function fa_num( $n ) {
		return str_replace( range( 0, 9 ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), (string) $n );
	}

	/* ---------------- helpers: charts (SVG درون‌خطی) ---------------- */

	/**
	 * نمودار راداری.
	 *
	 * @param array $points آرایه‌ای از array( 'label' => ..., 'value' => 0..100 ).
	 */
	protected function radar_svg( $points, $stroke = '#f0ad2e', $fill = 'rgba(240,173,46,.18)', $grid = '#1d3149' ) {
		$n = count( $points );
		if ( $n < 3 ) { return ''; }

		$size = 300;
		$cx   = $size / 2;
		$cy   = $size / 2;
		$r    = 100;
		$out  = '<svg class="szl-chart" viewBox="0 0 ' . $size . ' ' . $size . '" role="img">';

		// حلقه‌های راهنما.
		for ( $ring = 1; $ring <= 4; $ring++ ) {
			$rr  = $r * $ring / 4;
			$pts = array();
			for ( $i = 0; $i < $n; $i++ ) {
				$a     = -M_PI / 2 + 2 * M_PI * $i / $n;
				$pts[] = round( $cx + $rr * cos( $a ), 2 ) . ',' . round( $cy + $rr * sin( $a ), 2 );
			}
			$out .= '<polygon points="' . esc_attr( implode( ' ', $pts ) ) . '" fill="none" stroke="' . esc_attr( $grid ) . '" stroke-width="1"/>';
		}

		// شعاع‌ها و برچسب‌ها.
		$shape = array();
		for ( $i = 0; $i < $n; $i++ ) {
			$a  = -M_PI / 2 + 2 * M_PI * $i / $n;
			$ex = $cx + $r * cos( $a );
			$ey = $cy + $r * sin( $a );
			$out .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . round( $ex, 2 ) . '" y2="' . round( $ey, 2 ) . '" stroke="' . esc_attr( $grid ) . '" stroke-width="1"/>';

			$v       = max( 0, min( 100, (float) $points[ $i ]['value'] ) ) / 100;
			$shape[] = round( $cx + $r * $v * cos( $a ), 2 ) . ',' . round( $cy + $r * $v * sin( $a ), 2 );

			$lx    = $cx + ( $r + 26 ) * cos( $a );
			$ly    = $cy + ( $r + 26 ) * sin( $a );
			$anchor = ( abs( cos( $a ) ) < 0.2 ) ? 'middle' : ( cos( $a ) > 0 ? 'start' : 'end' );
			$out   .= '<text x="' . round( $lx, 2 ) . '" y="' . round( $ly + 4, 2 ) . '" text-anchor="' . $anchor . '" font-size="12" fill="#8ba0b8">' . esc_html( $points[ $i ]['label'] ) . '</text>';
		}

		$out .= '<polygon points="' . esc_attr( implode( ' ', $shape ) ) . '" fill="' . esc_attr( $fill ) . '" stroke="' . esc_attr( $stroke ) . '" stroke-width="2"/>';
		foreach ( $shape as $p ) {
			list( $px, $py ) = explode( ',', $p );
			$out .= '<circle cx="' . esc_attr( $px ) . '" cy="' . esc_attr( $py ) . '" r="3" fill="' . esc_attr( $stroke ) . '"/>';
		}

		return $out . '</svg>';
	}

	/**
	 * حلقه امتیاز.
	 */
	protected function ring_svg( $value, $max = 100, $sub = '', $color = '#2fc6ea', $track = '#1d3149' ) {
		$max   = $max > 0 ? $max : 100;
		$pct   = max( 0, min( 1, $value / $max ) );
		$r     = 52;
		$c     = 2 * M_PI * $r;
		$dash  = round( $c * $pct, 2 );
		$label = $this->fa_num( $value ) . '<tspan font-size="13" fill="#8ba0b8">/' . $this->fa_num( $max ) . '</tspan>';

		return '<svg class="szl-ring" width="140" height="140" viewBox="0 0 140 140" role="img">'
			. '<circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="' . esc_attr( $track ) . '" stroke-width="10"/>'
			. '<circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="10" stroke-linecap="round"'
			. ' stroke-dasharray="' . $dash . ' ' . round( $c, 2 ) . '" transform="rotate(-90 70 70)"/>'
			. '<text class="szl-ring__v" x="70" y="72" text-anchor="middle" style="fill:' . esc_attr( $color ) . '">' . $label . '</text>'
			. ( $sub ? '<text class="szl-ring__s" x="70" y="94" text-anchor="middle">' . esc_html( $sub ) . '</text>' : '' )
			. '</svg>';
	}

	/**
	 * نمودار خطی دو سری.
	 *
	 * @param array $series آرایه‌ای از array( 'color' => ..., 'values' => array(...) ).
	 * @param array $labels برچسب محور افقی.
	 */
	protected function line_svg( $series, $labels = array(), $grid = '#1d3149' ) {
		$w = 520;
		$h = 240;
		$p = array( 't' => 16, 'r' => 14, 'b' => 30, 'l' => 34 );

		$all = array();
		foreach ( $series as $s ) { $all = array_merge( $all, $s['values'] ); }
		if ( ! $all ) { return ''; }

		$min = min( $all );
		$max = max( $all );
		if ( $max === $min ) { $max = $min + 1; }
		$min = floor( $min / 10 ) * 10;
		$max = ceil( $max / 10 ) * 10;

		$iw  = $w - $p['l'] - $p['r'];
		$ih  = $h - $p['t'] - $p['b'];
		$out = '<svg class="szl-chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img">';

		for ( $i = 0; $i <= 4; $i++ ) {
			$y    = $p['t'] + $ih * $i / 4;
			$val  = round( $max - ( $max - $min ) * $i / 4 );
			$out .= '<line x1="' . $p['l'] . '" y1="' . round( $y, 2 ) . '" x2="' . ( $w - $p['r'] ) . '" y2="' . round( $y, 2 ) . '" stroke="' . esc_attr( $grid ) . '" stroke-width="1"/>';
			$out .= '<text x="' . ( $p['l'] - 8 ) . '" y="' . round( $y + 4, 2 ) . '" text-anchor="end" font-size="10" fill="#8ba0b8">' . esc_html( $this->fa_num( $val ) ) . '</text>';
		}

		foreach ( $series as $s ) {
			$vals = $s['values'];
			$cnt  = count( $vals );
			if ( $cnt < 2 ) { continue; }
			$pts = array();
			foreach ( $vals as $i => $v ) {
				$x     = $p['l'] + $iw * $i / ( $cnt - 1 );
				$y     = $p['t'] + $ih * ( 1 - ( $v - $min ) / ( $max - $min ) );
				$pts[] = round( $x, 2 ) . ',' . round( $y, 2 );
			}
			$out .= '<polyline points="' . esc_attr( implode( ' ', $pts ) ) . '" fill="none" stroke="' . esc_attr( $s['color'] ) . '" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"'
				. ( ! empty( $s['dashed'] ) ? ' stroke-dasharray="5 5"' : '' ) . '/>';
			if ( empty( $s['dashed'] ) ) {
				foreach ( $pts as $pt ) {
					list( $px, $py ) = explode( ',', $pt );
					$out .= '<circle cx="' . esc_attr( $px ) . '" cy="' . esc_attr( $py ) . '" r="2.5" fill="' . esc_attr( $s['color'] ) . '"/>';
				}
			}
		}

		$lc = count( $labels );
		if ( $lc > 1 ) {
			foreach ( $labels as $i => $lb ) {
				$x    = $p['l'] + $iw * $i / ( $lc - 1 );
				$out .= '<text x="' . round( $x, 2 ) . '" y="' . ( $h - 10 ) . '" text-anchor="middle" font-size="10" fill="#8ba0b8">' . esc_html( $lb ) . '</text>';
			}
		}

		return $out . '</svg>';
	}

	/* ---------------- helpers: quiz ---------------- */

	/** آیا موتور آزمون سازان در دسترس است؟ */
	protected function quiz_engine_ready() {
		return class_exists( '\\Sazan\\Quiz_CPT' ) && class_exists( '\\Sazan\\Quiz_Engine' );
	}

	/** فهرست آزمون‌های ثبت‌شده برای کنترل انتخاب. */
	protected function quiz_options() {
		$opts = array( '' => '— بدون آزمون —' );
		if ( ! class_exists( '\\Sazan\\Quiz_CPT' ) ) {
			return $opts;
		}
		$posts = get_posts( array(
			'post_type'   => \Sazan\Quiz_CPT::POST_TYPE,
			'numberposts' => -1,
			'post_status' => array( 'publish', 'draft', 'private' ),
			'orderby'     => 'title',
			'order'       => 'ASC',
		) );
		foreach ( $posts as $q ) {
			$opts[ (string) $q->ID ] = $q->post_title ? $q->post_title : ( '#' . $q->ID );
		}
		return $opts;
	}

	/** فهرست برگه‌ها برای حالت «برگه‌ی مشخص». */
	protected function page_options() {
		$opts  = array( '' => '— انتخاب برگه —' );
		$pages = get_posts( array( 'post_type' => 'page', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		foreach ( $pages as $pg ) {
			$opts[ (string) $pg->ID ] = $pg->post_title ? $pg->post_title : ( '#' . $pg->ID );
		}
		return $opts;
	}

	/** کنترل مشترک «مقصد دکمه آزمون». */
	protected function quiz_dest_controls( $prefix, $condition = array() ) {
		$this->add_control( $prefix . 'quiz_mode', array(
			'label'       => 'وقتی روی دکمه کلیک شد',
			'type'        => \Elementor\Controls_Manager::SELECT,
			'default'     => 'modal',
			'options'     => array(
				'modal'     => 'آزمون در همین صفحه باز شود (پنجره‌ی شناور)',
				'permalink' => 'به صفحه‌ی خود آزمون برود',
				'page'      => 'به یک برگه‌ی مشخص برود (شناسه آزمون در آدرس)',
				'custom'    => 'از لینک دستی کارت استفاده شود',
			),
			'description' => 'حالت «پنجره‌ی شناور» به هیچ برگه‌ای نیاز ندارد و همیشه کار می‌کند.',
			'condition'   => $condition,
		) );

		$this->add_control( $prefix . 'quiz_page', array(
			'label'     => 'برگه‌ی مقصد',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'options'   => $this->page_options(),
			'default'   => '',
			'condition' => array_merge( $condition, array( $prefix . 'quiz_mode' => 'page' ) ),
		) );

		$this->add_control( $prefix . 'quiz_arg', array(
			'label'       => 'نام پارامتر آدرس',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'quiz',
			'description' => 'مثال: /assessment/?quiz=۱۲۳',
			'condition'   => array_merge( $condition, array( $prefix . 'quiz_mode' => 'page' ) ),
		) );
	}

	/** آدرس مقصد یک آزمون بر اساس حالت انتخاب‌شده. */
	protected function quiz_url( $quiz_id, $mode, $page_id, $arg ) {
		$quiz_id = absint( $quiz_id );
		if ( ! $quiz_id ) { return ''; }

		if ( 'page' === $mode && $page_id ) {
			$arg = $arg ? $arg : 'quiz';
			return add_query_arg( $arg, $quiz_id, get_permalink( absint( $page_id ) ) );
		}
		return (string) get_permalink( $quiz_id );
	}

	/** پنجره‌ی شناور شامل آزمون رندرشده. */
	protected function quiz_modal_html( $modal_id, $quiz_id, $title = '' ) {
		if ( ! $this->quiz_engine_ready() ) { return ''; }
		$quiz_id = absint( $quiz_id );
		if ( ! $quiz_id || \Sazan\Quiz_CPT::POST_TYPE !== get_post_type( $quiz_id ) ) { return ''; }

		$title = $title ? $title : get_the_title( $quiz_id );

		return '<div class="szl-modal" id="' . esc_attr( $modal_id ) . '" role="dialog" aria-modal="true" aria-label="' . esc_attr( $title ) . '">'
			. '<div class="szl-modal__box">'
			. '<button type="button" class="szl-modal__x" aria-label="بستن">&times;</button>'
			. '<h3 class="szl-modal__t">' . esc_html( $title ) . '</h3>'
			. '<div class="szl-modal__body">' . \Sazan\Quiz_Engine::instance()->render( $quiz_id ) . '</div>'
			. '</div></div>';
	}

	/**
	 * دکمه‌ی آزمون: بسته به حالت، لینک می‌شود یا پنجره‌ی شناور را باز می‌کند.
	 *
	 * @param string $modal_id شناسه‌ی یکتای پنجره (فقط در حالت modal).
	 */
	protected function quiz_button( $text, $quiz_id, $mode, $page_id, $arg, $fallback_link, $variant = 'cyan', $icon = null, $modal_id = '' ) {
		if ( '' === trim( (string) $text ) ) { return ''; }
		$quiz_id = absint( $quiz_id );

		if ( $quiz_id && 'modal' === $mode && $this->quiz_engine_ready() ) {
			return '<button type="button" class="szl-btn szl-btn--' . esc_attr( $variant ) . ' szl-quiz-open" data-target="' . esc_attr( $modal_id ) . '">'
				. esc_html( $text ) . ( $icon ? $this->icon( $icon ) : '' ) . '</button>';
		}

		if ( $quiz_id && 'custom' !== $mode ) {
			$url = $this->quiz_url( $quiz_id, $mode, $page_id, $arg );
			if ( $url ) {
				return $this->button( $text, array( 'url' => $url ), $variant, $icon );
			}
		}

		return $this->button( $text, $fallback_link, $variant, $icon );
	}

	/* ---------------- helpers: controls ---------------- */

	/** بخش استایل مشترک جعبه (پس‌زمینه، حاشیه، گردی، فاصله). */
	protected function box_style_section( $id, $label, $selector ) {
		$this->start_controls_section( $id, array(
			'label' => $label,
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( $id . '_bg', array(
			'label'     => 'رنگ پس‌زمینه',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => 'background: {{VALUE}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Border::get_type(), array(
			'name'     => $id . '_border',
			'selector' => '{{WRAPPER}} ' . $selector,
		) );

		$this->add_control( $id . '_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( $id . '_padding', array(
			'label'      => 'فاصله داخلی',
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em', '%' ),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_group_control( \Elementor\Group_Control_Box_Shadow::get_type(), array(
			'name'     => $id . '_shadow',
			'selector' => '{{WRAPPER}} ' . $selector,
		) );

		$this->end_controls_section();
	}

	/** کنترل‌های رنگ + تایپوگرافی برای یک عنصر متنی. */
	protected function text_style( $id, $label, $selector ) {
		$this->add_control( $id . '_color', array(
			'label'     => $label . ' – رنگ',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => $id . '_typo',
			'label'    => $label . ' – تایپوگرافی',
			'selector' => '{{WRAPPER}} ' . $selector,
		) );
	}

	/** بخش استایل دکمه (عادی/شناور). */
	protected function button_style_section( $id, $label, $selector ) {
		$this->start_controls_section( $id, array(
			'label' => $label,
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => $id . '_typo',
			'selector' => '{{WRAPPER}} ' . $selector,
		) );

		$this->start_controls_tabs( $id . '_tabs' );

		$this->start_controls_tab( $id . '_normal', array( 'label' => 'عادی' ) );
		$this->add_control( $id . '_color', array(
			'label'     => 'رنگ متن',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
		) );
		$this->add_control( $id . '_bg', array(
			'label'     => 'پس‌زمینه',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => 'background: {{VALUE}};' ),
		) );
		$this->add_control( $id . '_bc', array(
			'label'     => 'رنگ حاشیه',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->start_controls_tab( $id . '_hover', array( 'label' => 'شناور' ) );
		$this->add_control( $id . '_h_color', array(
			'label'     => 'رنگ متن',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector . ':hover' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( $id . '_h_bg', array(
			'label'     => 'پس‌زمینه',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector . ':hover' => 'background: {{VALUE}};' ),
		) );
		$this->add_control( $id . '_h_bc', array(
			'label'     => 'رنگ حاشیه',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} ' . $selector . ':hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control( $id . '_radius', array(
			'label'      => 'گردی گوشه‌ها',
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'separator'  => 'before',
			'selectors'  => array( '{{WRAPPER}} ' . $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( $id . '_padding', array(
			'label'      => 'فاصله داخلی',
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', 'em' ),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->end_controls_section();
	}

	/** کنترل تعداد ستون‌ها (ریسپانسیو). */
	protected function columns_control( $id, $selector, $default = 3 ) {
		$this->add_responsive_control( $id, array(
			'label'          => 'تعداد ستون‌ها',
			'type'           => \Elementor\Controls_Manager::SELECT,
			'default'        => (string) $default,
			'tablet_default' => '2',
			'mobile_default' => '1',
			'options'        => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶' ),
			'selectors'      => array( '{{WRAPPER}} ' . $selector => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );
	}

	/** کنترل فاصله بین آیتم‌ها. */
	protected function gap_control( $id, $selector, $default = 20 ) {
		$this->add_responsive_control( $id, array(
			'label'      => 'فاصله بین آیتم‌ها',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
			'default'    => array( 'unit' => 'px', 'size' => $default ),
			'selectors'  => array( '{{WRAPPER}} ' . $selector => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
	}

	/** بخش «پالت رنگ» مشترک همه ویجت‌ها. */
	protected function palette_section() {
		$this->start_controls_section( 'szl_palette', array(
			'label' => 'پالت رنگ',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$map = array(
			'pal_bg'    => array( 'پس‌زمینه بخش', '--szl-bg' ),
			'pal_card'  => array( 'پس‌زمینه کارت', '--szl-card' ),
			'pal_card2' => array( 'پس‌زمینه ثانویه', '--szl-card-2' ),
			'pal_line'  => array( 'رنگ خطوط', '--szl-line' ),
			'pal_text'  => array( 'رنگ متن اصلی', '--szl-text' ),
			'pal_muted' => array( 'رنگ متن کم‌رنگ', '--szl-muted' ),
			'pal_cyan'  => array( 'رنگ تاکیدی (فیروزه‌ای)', '--szl-cyan' ),
			'pal_gold'  => array( 'رنگ تاکیدی (طلایی)', '--szl-gold' ),
			'pal_gold2' => array( 'رنگ طلایی روشن', '--szl-gold-2' ),
		);
		foreach ( $map as $key => $def ) {
			$this->add_control( $key, array(
				'label'     => $def[0],
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .szl' => $def[1] . ': {{VALUE}};' ),
			) );
		}
		$this->add_control( 'pal_radius', array(
			'label'      => 'گردی کلی گوشه‌ها',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl' => '--szl-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	/** بخش محتوایی سرتیتر (عنوان + زیرعنوان) مشترک. */
	protected function heading_content_controls( $title_default = '', $sub_default = '' ) {
		$this->add_control( 'sec_title', array(
			'label'   => 'عنوان بخش',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => $title_default,
			'label_block' => true,
		) );
		$this->add_control( 'sec_sub', array(
			'label'   => 'زیرعنوان',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => $sub_default,
		) );
		$this->add_control( 'sec_tag', array(
			'label'   => 'تگ عنوان',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'h2',
			'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => 'DIV' ),
		) );
	}

	/** خروجی سرتیتر بخش. */
	protected function heading_html( array $s, $align_class = 'szl-head' ) {
		if ( empty( $s['sec_title'] ) && empty( $s['sec_sub'] ) ) { return ''; }
		$tag = in_array( $s['sec_tag'] ?? 'h2', array( 'h1', 'h2', 'h3', 'h4', 'div' ), true ) ? $s['sec_tag'] : 'h2';
		$o   = '<div class="' . esc_attr( $align_class ) . '">';
		if ( ! empty( $s['sec_title'] ) ) {
			$o .= '<' . $tag . ' class="szl-h">' . esc_html( $s['sec_title'] ) . '</' . $tag . '>';
		}
		if ( ! empty( $s['sec_sub'] ) ) {
			$o .= '<p class="szl-sub">' . esc_html( $s['sec_sub'] ) . '</p>';
		}
		return $o . '</div>';
	}

	/** بخش استایل سرتیتر بخش. */
	protected function heading_style_section() {
		$this->start_controls_section( 'st_head', array(
			'label' => 'استایل سرتیتر',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_responsive_control( 'head_align', array(
			'label'     => 'چینش',
			'type'      => \Elementor\Controls_Manager::CHOOSE,
			'options'   => array(
				'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'default'   => 'center',
			'selectors' => array( '{{WRAPPER}} .szl-head' => 'text-align: {{VALUE}};' ),
		) );
		$this->text_style( 'head_title', 'عنوان', '.szl-head .szl-h' );
		$this->text_style( 'head_sub', 'زیرعنوان', '.szl-head .szl-sub' );
		$this->add_responsive_control( 'head_gap', array(
			'label'      => 'فاصله تا محتوا',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-head' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}
}

/* ==================== ۱) هیرو ==================== */

class SZL_W_Hero extends SZL_Widget_Base {

	public function get_name() { return 'szl_hero'; }
	public function get_title() { return 'سازان: هیرو ارزیابی'; }
	public function get_icon() { return 'eicon-banner'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_main', array( 'label' => 'محتوا' ) );

		$this->add_control( 'badge', array(
			'label'       => 'برچسب بالای عنوان (اختیاری)',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label_block' => true,
		) );
		$this->add_control( 'title', array(
			'label'       => 'عنوان',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'ارزیابی کسب‌وکار',
			'label_block' => true,
		) );
		$this->add_control( 'title_tag', array(
			'label'   => 'تگ عنوان',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'h1',
			'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'DIV' ),
		) );
		$this->add_control( 'desc', array(
			'label'   => 'توضیح',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 3,
			'default' => 'با آزمون‌های تخصصی سازان، نقاط قوت، ضعف، گلوگاه‌ها و فرصت‌های رشد کسب‌وکارتان را شناسایی کنید.',
		) );
		$this->add_control( 'image', array(
			'label'   => 'تصویر',
			'type'    => \Elementor\Controls_Manager::MEDIA,
			'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
		) );
		$this->add_control( 'media_pos', array(
			'label'     => 'جای تصویر',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'start',
			'options'   => array( 'start' => 'سمت چپ (ابتدای گرید)', 'end' => 'سمت راست' ),
			'selectors' => array( '{{WRAPPER}} .szl-hero__media' => 'order: {{VALUE}};' ),
			'selectors_dictionary' => array( 'start' => '0', 'end' => '2' ),
		) );
		$this->add_control( 'glow', array(
			'label'        => 'درخشش پشت تصویر',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$this->end_controls_section();

		$this->start_controls_section( 'c_btns', array( 'label' => 'دکمه‌ها' ) );

		$this->add_control( 'b1_text', array(
			'label'   => 'دکمه اول – متن',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'شروع ارزیابی',
		) );
		$this->add_control( 'b1_link', array(
			'label'   => 'دکمه اول – لینک',
			'type'    => \Elementor\Controls_Manager::URL,
			'default' => array( 'url' => '#' ),
		) );
		$this->add_control( 'b1_icon', array(
			'label'   => 'دکمه اول – آیکن',
			'type'    => \Elementor\Controls_Manager::ICONS,
		) );
		$this->add_control( 'b1_quiz', array(
			'label'       => 'دکمه اول – آزمون',
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'options'     => $this->quiz_options(),
			'default'     => '',
			'label_block' => true,
			'description' => 'اگر آزمونی انتخاب کنید، دکمه به‌جای لینک بالا همان آزمون را باز می‌کند.',
		) );
		$this->quiz_dest_controls( '' );
		$this->add_control( 'b2_text', array(
			'label'     => 'دکمه دوم – متن',
			'type'      => \Elementor\Controls_Manager::TEXT,
			'default'   => 'مشاهده همه آزمون‌ها',
			'separator' => 'before',
		) );
		$this->add_control( 'b2_link', array(
			'label'   => 'دکمه دوم – لینک',
			'type'    => \Elementor\Controls_Manager::URL,
			'default' => array( 'url' => '#' ),
		) );
		$this->add_control( 'b2_icon', array(
			'label' => 'دکمه دوم – آیکن',
			'type'  => \Elementor\Controls_Manager::ICONS,
		) );
		$this->add_responsive_control( 'btn_align', array(
			'label'     => 'چینش دکمه‌ها',
			'type'      => \Elementor\Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center'     => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .szl-hero__actions' => 'justify-content: {{VALUE}};' ),
		) );

		$this->end_controls_section();

		/* ---- style ---- */
		$this->box_style_section( 'st_box', 'جعبه هیرو', '.szl-hero-box' );

		$this->start_controls_section( 'st_txt', array(
			'label' => 'متن‌ها',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_responsive_control( 'txt_align', array(
			'label'     => 'چینش متن',
			'type'      => \Elementor\Controls_Manager::CHOOSE,
			'options'   => array(
				'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'default'   => 'right',
			'selectors' => array( '{{WRAPPER}} .szl-hero__body' => 'text-align: {{VALUE}};' ),
		) );
		$this->text_style( 'h_title', 'عنوان', '.szl-hero__body .szl-h' );
		$this->text_style( 'h_desc', 'توضیح', '.szl-hero__body .szl-sub' );
		$this->text_style( 'h_badge', 'برچسب', '.szl-hero__body .szl-eyebrow' );
		$this->add_responsive_control( 'cols_ratio', array(
			'label'     => 'نسبت ستون‌ها (تصویر/متن)',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => '1fr 1fr',
			'options'   => array(
				'1fr 1fr'   => '۵۰ / ۵۰',
				'1fr 1.3fr' => 'متن بزرگ‌تر',
				'1.3fr 1fr' => 'تصویر بزرگ‌تر',
				'1fr'       => 'تک‌ستونی',
			),
			'selectors' => array( '{{WRAPPER}} .szl-hero' => 'grid-template-columns: {{VALUE}};' ),
		) );
		$this->gap_control( 'hero_gap', '.szl-hero', 34 );
		$this->end_controls_section();

		$this->button_style_section( 'st_b1', 'دکمه اول', '.szl-hero__actions .szl-btn--gold' );
		$this->button_style_section( 'st_b2', 'دکمه دوم', '.szl-hero__actions .szl-btn--ghost' );
		$this->palette_section();
	}

	protected function html( array $s ) {
		$tag = in_array( $s['title_tag'] ?? 'h1', array( 'h1', 'h2', 'h3', 'div' ), true ) ? $s['title_tag'] : 'h1';

		$o  = '<div class="szl"><div class="szl-box szl-hero-box"><div class="szl-hero">';
		$o .= '<div class="szl-hero__body">';
		if ( ! empty( $s['badge'] ) ) {
			$o .= '<div class="szl-eyebrow">' . esc_html( $s['badge'] ) . '</div>';
		}
		if ( ! empty( $s['title'] ) ) {
			$o .= '<' . $tag . ' class="szl-h">' . esc_html( $s['title'] ) . '</' . $tag . '>';
		}
		if ( ! empty( $s['desc'] ) ) {
			$o .= '<p class="szl-sub">' . nl2br( esc_html( $s['desc'] ) ) . '</p>';
		}
		$quiz_id  = absint( $s['b1_quiz'] ?? 0 );
		$mode     = $s['quiz_mode'] ?? 'modal';
		$modal_id = 'szl-quiz-' . $this->get_id() . '-hero';

		$o .= '<div class="szl-hero__actions">'
			. $this->quiz_button(
				$s['b1_text'] ?? '',
				$quiz_id,
				$mode,
				$s['quiz_page'] ?? '',
				$s['quiz_arg'] ?? 'quiz',
				$s['b1_link'] ?? array(),
				'gold',
				$s['b1_icon'] ?? null,
				$modal_id
			)
			. $this->button( $s['b2_text'] ?? '', $s['b2_link'] ?? array(), 'ghost', $s['b2_icon'] ?? null )
			. '</div></div>';

		$glow = ( ( $s['glow'] ?? 'yes' ) === 'yes' ) ? '' : ' szl-hero__media--flat';
		$o   .= '<div class="szl-hero__media' . $glow . '">' . $this->img( $s['image'] ?? array(), $s['title'] ?? '' ) . '</div>';

		$modal = ( $quiz_id && 'modal' === $mode ) ? $this->quiz_modal_html( $modal_id, $quiz_id, $s['b1_text'] ?? '' ) : '';

		return $o . '</div></div>' . $modal . '</div>';
	}
}

/* ==================== ۲) نوار آمار ==================== */

class SZL_W_Stats extends SZL_Widget_Base {

	public function get_name() { return 'szl_stats'; }
	public function get_title() { return 'سازان: نوار آمار'; }
	public function get_icon() { return 'eicon-counter'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_main', array( 'label' => 'آیتم‌ها' ) );

		$rep = new \Elementor\Repeater();
		$rep->add_control( 'icon', array(
			'label'   => 'آیکن',
			'type'    => \Elementor\Controls_Manager::ICONS,
			'default' => array( 'value' => 'fas fa-clipboard-list', 'library' => 'fa-solid' ),
		) );
		$rep->add_control( 'number', array(
			'label'       => 'عدد',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '۵۰',
			'description' => 'برای شمارش انیمیشنی، فقط عدد انگلیسی وارد کنید و «شمارنده» را روشن کنید.',
		) );
		$rep->add_control( 'prefix', array( 'label' => 'پیشوند', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$rep->add_control( 'suffix', array( 'label' => 'پسوند', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '+' ) );
		$rep->add_control( 'label', array(
			'label'       => 'برچسب',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'آزمون تخصصی',
			'label_block' => true,
		) );

		$this->add_control( 'items', array(
			'label'       => 'آمارها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ number }}} – {{{ label }}}',
			'default'     => array(
				array( 'number' => '50', 'suffix' => '+', 'label' => 'آزمون تخصصی', 'icon' => array( 'value' => 'fas fa-clipboard-list', 'library' => 'fa-solid' ) ),
				array( 'number' => '3000', 'suffix' => '+', 'label' => 'ارزیابی انجام شده', 'icon' => array( 'value' => 'fas fa-users', 'library' => 'fa-solid' ) ),
				array( 'number' => 'گزارش تحلیلی', 'suffix' => '', 'label' => 'اختصاصی', 'icon' => array( 'value' => 'fas fa-file-alt', 'library' => 'fa-solid' ) ),
				array( 'number' => 'نتایج کاربردی', 'suffix' => '', 'label' => 'و قابل اجرا', 'icon' => array( 'value' => 'fas fa-bullseye', 'library' => 'fa-solid' ) ),
			),
		) );

		$this->add_control( 'counter', array(
			'label'        => 'شمارنده انیمیشنی',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'fa_digits', array(
			'label'        => 'ارقام فارسی',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'dividers', array(
			'label'        => 'خط جداکننده بین آیتم‌ها',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$this->end_controls_section();

		/* ---- style ---- */
		$this->box_style_section( 'st_box', 'جعبه آمار', '.szl-stats-box' );

		$this->start_controls_section( 'st_items', array(
			'label' => 'آیتم‌ها',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->columns_control( 'cols', '.szl-stats', 4 );
		$this->gap_control( 'items_gap', '.szl-stats', 18 );
		$this->add_responsive_control( 'item_align', array(
			'label'     => 'چینش آیتم',
			'type'      => \Elementor\Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center'     => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .szl-stat' => 'justify-content: {{VALUE}};' ),
		) );
		$this->text_style( 'num', 'عدد', '.szl-stat__num' );
		$this->text_style( 'lbl', 'برچسب', '.szl-stat__label' );
		$this->end_controls_section();

		$this->start_controls_section( 'st_icon', array(
			'label' => 'آیکن',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->add_control( 'ic_color', array(
			'label'     => 'رنگ آیکن',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .szl-stat__icon'     => 'color: {{VALUE}};',
				'{{WRAPPER}} .szl-stat__icon svg' => 'fill: {{VALUE}};',
			),
		) );
		$this->add_control( 'ic_bg', array(
			'label'     => 'پس‌زمینه آیکن',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-stat__icon' => 'background: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'ic_size', array(
			'label'      => 'اندازه جعبه آیکن',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 30, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-stat__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'ic_font', array(
			'label'      => 'اندازه آیکن',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 10, 'max' => 60 ) ),
			'selectors'  => array(
				'{{WRAPPER}} .szl-stat__icon'     => 'font-size: {{SIZE}}{{UNIT}};',
				'{{WRAPPER}} .szl-stat__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
			),
		) );
		$this->add_control( 'ic_radius', array(
			'label'      => 'گردی جعبه آیکن',
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => array( 'px', '%' ),
			'selectors'  => array( '{{WRAPPER}} .szl-stat__icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->palette_section();
	}

	protected function html( array $s ) {
		$items = (array) ( $s['items'] ?? array() );
		if ( ! $items ) { return ''; }

		$fa   = ( $s['fa_digits'] ?? 'yes' ) === 'yes';
		$anim = ( $s['counter'] ?? 'yes' ) === 'yes';
		$div  = ( $s['dividers'] ?? 'yes' ) === 'yes' ? '' : ' szl-stats--nodiv';

		$o = '<div class="szl"><div class="szl-box szl-stats-box"><div class="szl-stats' . $div . '">';
		foreach ( $items as $it ) {
			$num     = (string) ( $it['number'] ?? '' );
			$prefix  = (string) ( $it['prefix'] ?? '' );
			$suffix  = (string) ( $it['suffix'] ?? '' );
			$numeric = is_numeric( $num );
			$shown   = $numeric && $fa ? $this->fa_num( $num ) : $num;

			$attrs = '';
			if ( $anim && $numeric ) {
				$attrs = ' data-to="' . esc_attr( $num ) . '" data-prefix="' . esc_attr( $prefix ) . '"'
					. ' data-suffix="' . esc_attr( $suffix ) . '" data-fa="' . ( $fa ? 'yes' : 'no' ) . '"';
			}

			$o .= '<div class="szl-stat">'
				. '<div class="szl-stat__icon">' . $this->icon( $it['icon'] ?? array() ) . '</div>'
				. '<div class="szl-stat__body">'
				. '<div class="szl-stat__num"' . $attrs . '>' . esc_html( $prefix . $shown . $suffix ) . '</div>'
				. '<div class="szl-stat__label">' . esc_html( $it['label'] ?? '' ) . '</div>'
				. '</div></div>';
		}

		return $o . '</div></div></div>';
	}
}

/* ==================== ۳) شبکه آزمون‌ها + فیلتر ==================== */

class SZL_W_Tests extends SZL_Widget_Base {

	public function get_name() { return 'szl_tests'; }
	public function get_title() { return 'سازان: آزمون‌ها + فیلتر دسته'; }
	public function get_icon() { return 'eicon-posts-grid'; }

	protected function register_controls() {

		/* --- فیلترها --- */
		$this->start_controls_section( 'c_filters', array( 'label' => 'فیلتر دسته‌ها' ) );

		$this->add_control( 'show_filters', array(
			'label'        => 'نمایش نوار فیلتر',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );

		$frep = new \Elementor\Repeater();
		$frep->add_control( 'key', array(
			'label'       => 'کلید دسته',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '',
			'description' => 'برای دکمه «همه» خالی بگذارید یا * بنویسید. برای بقیه، کلید انگلیسی یکتا بدهید و در کارت‌ها همان را انتخاب کنید.',
		) );
		$frep->add_control( 'label', array(
			'label'       => 'عنوان',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'همه',
			'label_block' => true,
		) );
		$frep->add_control( 'icon', array( 'label' => 'آیکن', 'type' => \Elementor\Controls_Manager::ICONS ) );

		$this->add_control( 'filters', array(
			'label'       => 'دسته‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $frep->get_controls(),
			'title_field' => '{{{ label }}}',
			'condition'   => array( 'show_filters' => 'yes' ),
			'default'     => array(
				array( 'key' => '*', 'label' => 'همه' ),
				array( 'key' => 'sales', 'label' => 'فروش' ),
				array( 'key' => 'marketing', 'label' => 'مارکتینگ' ),
				array( 'key' => 'branding', 'label' => 'برندینگ' ),
				array( 'key' => 'system', 'label' => 'سیستم‌سازی' ),
				array( 'key' => 'hr', 'label' => 'منابع انسانی' ),
				array( 'key' => 'finance', 'label' => 'مالی' ),
				array( 'key' => 'leadership', 'label' => 'رهبری' ),
			),
		) );

		$this->end_controls_section();

		/* --- کارت‌ها --- */
		$this->start_controls_section( 'c_cards', array( 'label' => 'آزمون‌ها' ) );

		$rep = new \Elementor\Repeater();
		$rep->add_control( 'cat_key', array(
			'label'       => 'کلید دسته (برای فیلتر)',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'sales',
			'label_block' => true,
		) );
		$rep->add_control( 'cat_label', array(
			'label'   => 'برچسب دسته روی کارت',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'فروش',
		) );
		$rep->add_control( 'title', array(
			'label'       => 'عنوان آزمون',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'ارزیابی سیستم فروش',
			'label_block' => true,
		) );
		$rep->add_control( 'desc', array(
			'label'   => 'توضیح',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 3,
			'default' => 'کارایی قیف فروش، جذب سرنخ تا بستن فروش و نگهداشت مشتری را تحلیل کنید.',
		) );
		$rep->add_control( 'image', array( 'label' => 'تصویر/آیکن تصویری', 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$rep->add_control( 'icon', array( 'label' => 'آیکن (اگر تصویر ندارید)', 'type' => \Elementor\Controls_Manager::ICONS ) );
		$rep->add_control( 'time', array( 'label' => 'مدت زمان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '۱۸ دقیقه' ) );
		$rep->add_control( 'level', array( 'label' => 'سطح دشواری', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'متوسط' ) );
		$rep->add_control( 'quiz_id', array(
			'label'       => 'آزمون این کارت',
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'options'     => $this->quiz_options(),
			'default'     => '',
			'label_block' => true,
			'description' => 'یکی از آزمون‌های ثبت‌شده در «آزمون‌های سازان» را انتخاب کنید.',
		) );
		$rep->add_control( 'auto_fill', array(
			'label'        => 'عنوان و توضیح از خود آزمون گرفته شود',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'description'  => 'اگر روشن باشد، عنوان و توضیح کارت از آزمون انتخاب‌شده خوانده می‌شود.',
		) );
		$rep->add_control( 'btn_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'شروع آزمون' ) );
		$rep->add_control( 'btn_link', array(
			'label'       => 'لینک دستی (وقتی آزمونی انتخاب نشده)',
			'type'        => \Elementor\Controls_Manager::URL,
			'default'     => array( 'url' => '#' ),
		) );
		$rep->add_control( 'btn_icon', array(
			'label'   => 'آیکن دکمه',
			'type'    => \Elementor\Controls_Manager::ICONS,
			'default' => array( 'value' => 'fas fa-plus', 'library' => 'fa-solid' ),
		) );

		$this->add_control( 'cards', array(
			'label'       => 'کارت‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array( 'cat_key' => 'sales', 'cat_label' => 'فروش', 'title' => 'ارزیابی سیستم فروش', 'desc' => 'کارایی قیف فروش، جذب سرنخ تا بستن فروش و نگهداشت مشتری را تحلیل کنید.', 'time' => '۱۸ دقیقه', 'level' => 'متوسط' ),
				array( 'cat_key' => 'branding', 'cat_label' => 'برندینگ', 'title' => 'تست بلوغ برند', 'desc' => 'سطح بلوغ برند شما در ابعاد هویت، تجربه مشتری و جایگاه‌یابی ارزیابی می‌شود.', 'time' => '۱۲ دقیقه', 'level' => 'آسان' ),
				array( 'cat_key' => 'marketing', 'cat_label' => 'مارکتینگ', 'title' => 'تحلیل آمادگی دیجیتال مارکتینگ', 'desc' => 'بررسی آمادگی زیرساخت‌ها، ابزارها، تیم و استراتژی دیجیتال مارکتینگ کسب‌وکار شما.', 'time' => '۱۵ دقیقه', 'level' => 'متوسط' ),
				array( 'cat_key' => 'finance', 'cat_label' => 'مالی', 'title' => 'تست سلامت مالی کسب‌وکار', 'desc' => 'سلامت جریان نقدی، حاشیه سود و ساختار هزینه و ریسک‌های مالی را بررسی کنید.', 'time' => '۱۶ دقیقه', 'level' => 'متوسط' ),
				array( 'cat_key' => 'hr', 'cat_label' => 'منابع انسانی', 'title' => 'سنجش عملکرد تیم فروش', 'desc' => 'عملکرد فردی و تیمی، مهارت‌ها و شاخص‌های کلیدی تیم فروش شما را ارزیابی می‌کند.', 'time' => '۱۵ دقیقه', 'level' => 'متوسط' ),
				array( 'cat_key' => 'system', 'cat_label' => 'سیستم‌سازی', 'title' => 'ارزیابی فرایندهای کسب‌وکار', 'desc' => 'میزان مستندسازی، استانداردسازی و کارایی فرایندهای کلیدی را بسنجید.', 'time' => '۲۰ دقیقه', 'level' => 'سخت' ),
			),
		) );

		$this->add_control( 'show_time', array( 'label' => 'نمایش مدت زمان', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'time_icon', array(
			'label'     => 'آیکن مدت زمان',
			'type'      => \Elementor\Controls_Manager::ICONS,
			'default'   => array( 'value' => 'far fa-clock', 'library' => 'fa-regular' ),
			'condition' => array( 'show_time' => 'yes' ),
		) );
		$this->add_control( 'show_level', array( 'label' => 'نمایش سطح دشواری', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'level_icon', array(
			'label'     => 'آیکن سطح دشواری',
			'type'      => \Elementor\Controls_Manager::ICONS,
			'default'   => array( 'value' => 'fas fa-tachometer-alt', 'library' => 'fa-solid' ),
			'condition' => array( 'show_level' => 'yes' ),
		) );
		$this->add_control( 'show_badge', array( 'label' => 'نمایش برچسب دسته', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'badge_pos', array(
			'label'     => 'جای برچسب دسته',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'end',
			'options'   => array( 'end' => 'بالا سمت چپ', 'start' => 'بالا سمت راست' ),
			'condition' => array( 'show_badge' => 'yes' ),
		) );
		$this->add_control( 'art_pos', array(
			'label'     => 'جای تصویر آزمون',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'end',
			'options'   => array( 'end' => 'سمت چپ (کنار متن)', 'start' => 'سمت راست (کنار متن)' ),
			'selectors' => array( '{{WRAPPER}} .szl-card__art' => 'order: {{VALUE}};' ),
			'selectors_dictionary' => array( 'end' => '2', 'start' => '0' ),
		) );

		$this->end_controls_section();

		/* --- اتصال به آزمون‌های سازان --- */
		$this->start_controls_section( 'c_quiz', array( 'label' => 'اتصال به آزمون‌های سازان' ) );
		$this->quiz_dest_controls( '' );
		$this->end_controls_section();

		/* ---- style ---- */
		$this->start_controls_section( 'st_filters', array(
			'label'     => 'استایل فیلترها',
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_filters' => 'yes' ),
		) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array(
			'name'     => 'f_typo',
			'selector' => '{{WRAPPER}} .szl-filter',
		) );
		$this->start_controls_tabs( 'f_tabs' );
		$this->start_controls_tab( 'f_n', array( 'label' => 'عادی' ) );
		$this->add_control( 'f_color', array( 'label' => 'رنگ متن', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-filter' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'f_bg', array( 'label' => 'پس‌زمینه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-filter' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'f_bc', array( 'label' => 'حاشیه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-filter' => 'border-color: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'f_a', array( 'label' => 'فعال' ) );
		$this->add_control( 'f_a_color', array( 'label' => 'رنگ متن', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-filter.is-active' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'f_a_bg', array( 'label' => 'پس‌زمینه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-filter.is-active' => 'background: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_responsive_control( 'f_align', array(
			'label'     => 'چینش',
			'type'      => \Elementor\Controls_Manager::CHOOSE,
			'options'   => array(
				'flex-start' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
				'center'     => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
				'flex-end'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
			),
			'default'   => 'center',
			'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .szl-filters' => 'justify-content: {{VALUE}};' ),
		) );
		$this->gap_control( 'f_gap', '.szl-filters', 10 );
		$this->add_responsive_control( 'f_mb', array(
			'label'      => 'فاصله تا کارت‌ها',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-filters' => 'margin-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->box_style_section( 'st_card', 'کارت آزمون', '.szl-card' );

		$this->start_controls_section( 'st_card_txt', array(
			'label' => 'متن کارت',
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );
		$this->columns_control( 'cols', '.szl-tests', 3 );
		$this->gap_control( 'cards_gap', '.szl-tests', 20 );
		$this->text_style( 'ct', 'عنوان', '.szl-card__title' );
		$this->text_style( 'cd', 'توضیح', '.szl-card__desc' );
		$this->text_style( 'cb', 'برچسب دسته', '.szl-card__badge' );
		$this->text_style( 'cc', 'چیپ‌ها', '.szl-card .szl-chip' );
		$this->add_responsive_control( 'art_w', array(
			'label'      => 'عرض تصویر کارت',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 40, 'max' => 200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-card__art' => 'width: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'card_hover_bc', array(
			'label'     => 'رنگ حاشیه هنگام شناور',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-card:hover' => 'border-color: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		$this->button_style_section( 'st_cbtn', 'دکمه کارت', '.szl-card .szl-btn' );
		$this->palette_section();
	}

	protected function html( array $s ) {
		$cards = (array) ( $s['cards'] ?? array() );
		if ( ! $cards ) { return ''; }

		$o = '<div class="szl">';

		if ( ( $s['show_filters'] ?? 'yes' ) === 'yes' && ! empty( $s['filters'] ) ) {
			$o .= '<div class="szl-filters">';
			foreach ( (array) $s['filters'] as $i => $f ) {
				$key    = trim( (string) ( $f['key'] ?? '' ) );
				$active = ( 0 === $i ) ? ' is-active' : '';
				$o     .= '<button type="button" class="szl-filter' . $active . '" data-cat="' . esc_attr( $key ) . '">'
					. '<span>' . esc_html( $f['label'] ?? '' ) . '</span>'
					. $this->icon( $f['icon'] ?? array(), 'szl-filter__ico' )
					. '</button>';
			}
			$o .= '</div>';
		}

		$mode    = $s['quiz_mode'] ?? 'modal';
		$page_id = $s['quiz_page'] ?? '';
		$arg     = $s['quiz_arg'] ?? 'quiz';
		$modals  = '';

		$o .= '<div class="szl-grid szl-tests">';
		foreach ( $cards as $idx => $c ) {
			$quiz_id = absint( $c['quiz_id'] ?? 0 );

			// عنوان و توضیح از خود آزمون.
			if ( $quiz_id && ( $c['auto_fill'] ?? '' ) === 'yes' ) {
				$intro = array();
				if ( class_exists( '\\Sazan\\Quiz_CPT' ) ) {
					$qdata = \Sazan\Quiz_CPT::get_data( $quiz_id );
					$intro = isset( $qdata['intro'] ) ? (array) $qdata['intro'] : array();
				}
				$c['title'] = ! empty( $intro['title'] ) ? $intro['title'] : get_the_title( $quiz_id );
				if ( ! empty( $intro['desc'] ) ) { $c['desc'] = $intro['desc']; }
				if ( ! empty( $intro['start_label'] ) ) { $c['btn_text'] = $intro['start_label']; }
			}

			$art = ! empty( $c['image']['url'] ) ? $this->img( $c['image'], $c['title'] ?? '' ) : $this->icon( $c['icon'] ?? array() );

			$bpos = ( ( $s['badge_pos'] ?? 'end' ) === 'start' ) ? ' szl-card__badge--start' : '';

			$o .= '<article class="szl-card" data-cat="' . esc_attr( $c['cat_key'] ?? '' ) . '">';
			if ( ( $s['show_badge'] ?? 'yes' ) === 'yes' && ! empty( $c['cat_label'] ) ) {
				$o .= '<span class="szl-card__badge' . $bpos . '">' . esc_html( $c['cat_label'] ) . '</span>';
			}
			$o .= '<div class="szl-card__top">'
				. '<div class="szl-card__head">'
				. '<h3 class="szl-card__title">' . esc_html( $c['title'] ?? '' ) . '</h3>'
				. '<p class="szl-card__desc">' . esc_html( $c['desc'] ?? '' ) . '</p>'
				. '</div>';
			if ( $art ) { $o .= '<div class="szl-card__art">' . $art . '</div>'; }
			$o .= '</div>';

			$o .= '<div class="szl-card__foot">';
			if ( ( $s['show_time'] ?? 'yes' ) === 'yes' && ! empty( $c['time'] ) ) {
				$o .= '<span class="szl-chip">' . $this->icon( $s['time_icon'] ?? array() ) . esc_html( $c['time'] ) . '</span>';
			}
			if ( ( $s['show_level'] ?? 'yes' ) === 'yes' && ! empty( $c['level'] ) ) {
				$o .= '<span class="szl-chip szl-chip--gold">' . $this->icon( $s['level_icon'] ?? array() ) . esc_html( $c['level'] ) . '</span>';
			}
			$modal_id = 'szl-quiz-' . $this->get_id() . '-' . $idx;
			$o       .= $this->quiz_button(
				$c['btn_text'] ?? '',
				$quiz_id,
				$mode,
				$page_id,
				$arg,
				$c['btn_link'] ?? array(),
				'cyan',
				$c['btn_icon'] ?? null,
				$modal_id
			);
			$o .= '</div></article>';

			if ( $quiz_id && 'modal' === $mode ) {
				$modals .= $this->quiz_modal_html( $modal_id, $quiz_id, $c['title'] ?? '' );
			}
		}

		return $o . '</div>' . $modals . '</div>';
	}
}

/* ==================== ۴) ارزیابی جامع (ویژه) ==================== */

class SZL_W_Featured extends SZL_Widget_Base {

	public function get_name() { return 'szl_featured'; }
	public function get_title() { return 'سازان: ارزیابی جامع (نمودار رادار)'; }
	public function get_icon() { return 'eicon-radar-chart'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_main', array( 'label' => 'محتوا' ) );

		$this->add_control( 'badge', array( 'label' => 'برچسب گوشه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'پیشنهاد ویژه' ) );
		$this->add_control( 'title', array(
			'label'       => 'عنوان',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'ارزیابی جامع کسب‌وکار',
			'label_block' => true,
		) );
		$this->add_control( 'desc', array(
			'label'   => 'توضیح',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 3,
			'default' => 'یک ارزیابی کامل در ۶ بُعد کلیدی کسب‌وکار و دریافت گزارش تحلیلی جامع به همراه نقشه راه شخصی‌سازی‌شده برای رشد پایدار.',
		) );

		$meta = new \Elementor\Repeater();
		$meta->add_control( 'text', array( 'label' => 'متن', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '۴۵ دقیقه' ) );
		$meta->add_control( 'icon', array( 'label' => 'آیکن', 'type' => \Elementor\Controls_Manager::ICONS ) );
		$meta->add_control( 'gold', array( 'label' => 'رنگ طلایی', 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes' ) );
		$this->add_control( 'metas', array(
			'label'       => 'چیپ‌های اطلاعات',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $meta->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array(
				array( 'text' => '۴۵ دقیقه' ),
				array( 'text' => 'سخت', 'gold' => 'yes' ),
				array( 'text' => '۶ بعد کلیدی' ),
			),
		) );

		$feat = new \Elementor\Repeater();
		$feat->add_control( 'icon', array( 'label' => 'آیکن', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-chart-pie', 'library' => 'fa-solid' ) ) );
		$feat->add_control( 'text', array( 'label' => 'متن', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'تحلیل کیفی', 'label_block' => true ) );
		$this->add_control( 'features', array(
			'label'       => 'ویژگی‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $feat->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array(
				array( 'text' => 'نمودار کیفیتی' ),
				array( 'text' => 'تحلیل ۶ مقایسه‌ای' ),
				array( 'text' => 'مقایسه با میانگین صنعت' ),
				array( 'text' => 'نقشه نشانه‌ای' ),
				array( 'text' => 'گزارش PDF اختصاصی' ),
			),
		) );

		$this->add_control( 'btn_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'شروع ارزیابی جامع', 'separator' => 'before' ) );
		$this->add_control( 'btn_link', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'btn_icon', array(
			'label'   => 'آیکن دکمه',
			'type'    => \Elementor\Controls_Manager::ICONS,
			'default' => array( 'value' => 'fas fa-arrow-left', 'library' => 'fa-solid' ),
		) );
		$this->add_control( 'quiz_id', array(
			'label'       => 'آزمون این بخش',
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'options'     => $this->quiz_options(),
			'default'     => '',
			'label_block' => true,
			'description' => 'اگر آزمونی انتخاب کنید، دکمه به‌جای لینک بالا همان آزمون را باز می‌کند.',
		) );
		$this->quiz_dest_controls( '' );

		$this->end_controls_section();

		/* --- نمودار و امتیاز --- */
		$this->start_controls_section( 'c_chart', array( 'label' => 'نمودار و امتیاز' ) );

		$this->add_control( 'show_radar', array( 'label' => 'نمایش نمودار رادار', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );

		$ax = new \Elementor\Repeater();
		$ax->add_control( 'label', array( 'label' => 'عنوان محور', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'فروش' ) );
		$ax->add_control( 'value', array(
			'label'   => 'مقدار (۰ تا ۱۰۰)',
			'type'    => \Elementor\Controls_Manager::SLIDER,
			'range'   => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
			'default' => array( 'size' => 70 ),
		) );
		$this->add_control( 'axes', array(
			'label'       => 'محورهای نمودار',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $ax->get_controls(),
			'title_field' => '{{{ label }}}',
			'condition'   => array( 'show_radar' => 'yes' ),
			'default'     => array(
				array( 'label' => 'فروش', 'value' => array( 'size' => 88 ) ),
				array( 'label' => 'بازاریابی', 'value' => array( 'size' => 62 ) ),
				array( 'label' => 'مالی', 'value' => array( 'size' => 74 ) ),
				array( 'label' => 'فرایندها', 'value' => array( 'size' => 55 ) ),
				array( 'label' => 'منابع انسانی', 'value' => array( 'size' => 80 ) ),
				array( 'label' => 'رهبری', 'value' => array( 'size' => 68 ) ),
			),
		) );

		$this->add_control( 'show_score', array( 'label' => 'نمایش حلقه امتیاز', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'score', array( 'label' => 'امتیاز', 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 87, 'condition' => array( 'show_score' => 'yes' ) ) );
		$this->add_control( 'score_max', array( 'label' => 'حداکثر امتیاز', 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 100, 'condition' => array( 'show_score' => 'yes' ) ) );
		$this->add_control( 'score_label', array( 'label' => 'برچسب زیر امتیاز', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'امتیاز نهایی', 'condition' => array( 'show_score' => 'yes' ) ) );

		$this->end_controls_section();

		/* ---- style ---- */
		$this->box_style_section( 'st_box', 'جعبه بخش', '.szl-featured' );

		$this->start_controls_section( 'st_txt', array( 'label' => 'متن‌ها', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->text_style( 'f_title', 'عنوان', '.szl-featured .szl-h' );
		$this->text_style( 'f_desc', 'توضیح', '.szl-featured .szl-sub' );
		$this->text_style( 'f_badge', 'برچسب گوشه', '.szl-featured .szl-eyebrow' );
		$this->text_style( 'f_feat', 'ویژگی‌ها', '.szl-feature' );
		$this->add_control( 'feat_ico_color', array(
			'label'     => 'رنگ آیکن ویژگی‌ها',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-feature__ico' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'feat_ico_bg', array(
			'label'     => 'پس‌زمینه آیکن ویژگی‌ها',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-feature__ico' => 'background: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'feat_cols', array(
			'label'          => 'ستون‌های ویژگی‌ها',
			'type'           => \Elementor\Controls_Manager::SELECT,
			'default'        => '5',
			'tablet_default' => '3',
			'mobile_default' => '1',
			'options'        => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶' ),
			'selectors' => array( '{{WRAPPER}} .szl-features' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'st_chart', array( 'label' => 'نمودار', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'radar_stroke', array( 'label' => 'رنگ خط نمودار', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#f0ad2e' ) );
		$this->add_control( 'radar_fill', array( 'label' => 'رنگ پرکننده نمودار', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => 'rgba(240,173,46,0.18)' ) );
		$this->add_control( 'radar_grid', array( 'label' => 'رنگ شبکه', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#1d3149' ) );
		$this->add_control( 'ring_color', array( 'label' => 'رنگ حلقه امتیاز', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#2fc6ea' ) );
		$this->add_responsive_control( 'chart_w', array(
			'label'      => 'عرض ستون نمودار',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 180, 'max' => 520 ) ),
			'default'    => array( 'unit' => 'px', 'size' => 320 ),
			'selectors'  => array( '{{WRAPPER}} .szl-featured__grid' => 'grid-template-columns: var(--szl-aside-w, 190px) 1fr {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'aside_w', array(
			'label'      => 'عرض ستون امتیاز',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 120, 'max' => 360 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-featured' => '--szl-aside-w: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->button_style_section( 'st_btn', 'دکمه', '.szl-featured__cta .szl-btn' );
		$this->palette_section();
	}

	protected function html( array $s ) {
		$o = '<div class="szl"><div class="szl-box szl-featured"><div class="szl-featured__grid">';

		/* ستون راست: برچسب ویژه + حلقه امتیاز */
		$o .= '<div class="szl-featured__aside">';
		if ( ! empty( $s['badge'] ) ) {
			$o .= '<div class="szl-eyebrow">' . esc_html( $s['badge'] ) . '</div>';
		}
		if ( ( $s['show_score'] ?? 'yes' ) === 'yes' ) {
			$o .= '<div class="szl-score">'
				. $this->ring_svg( (int) ( $s['score'] ?? 0 ), (int) ( $s['score_max'] ?? 100 ), '', ( $s['ring_color'] ?? '' ) ?: '#2fc6ea' )
				. '<span class="szl-score__label">' . esc_html( $s['score_label'] ?? '' ) . '</span>'
				. '</div>';
		}
		$o .= '</div>';

		/* ستون میانی: متن و ویژگی‌ها */
		$o .= '<div class="szl-featured__body">';
		if ( ! empty( $s['title'] ) ) {
			$o .= '<h2 class="szl-h">' . esc_html( $s['title'] ) . '</h2>';
		}
		if ( ! empty( $s['desc'] ) ) {
			$o .= '<p class="szl-sub">' . esc_html( $s['desc'] ) . '</p>';
		}
		if ( ! empty( $s['features'] ) ) {
			$o .= '<div class="szl-features">';
			foreach ( (array) $s['features'] as $f ) {
				$o .= '<div class="szl-feature">'
					. '<span class="szl-feature__ico">' . $this->icon( $f['icon'] ?? array() ) . '</span>'
					. '<span class="szl-feature__t">' . esc_html( $f['text'] ?? '' ) . '</span>'
					. '</div>';
			}
			$o .= '</div>';
		}
		$o .= '</div>';

		/* ستون چپ: نمودار رادار + چیپ‌های اطلاعات */
		$o .= '<div class="szl-featured__chart">';
		if ( ( $s['show_radar'] ?? 'yes' ) === 'yes' && ! empty( $s['axes'] ) ) {
			$pts = array();
			foreach ( (array) $s['axes'] as $a ) {
				$pts[] = array(
					'label' => $a['label'] ?? '',
					'value' => isset( $a['value']['size'] ) ? (float) $a['value']['size'] : 0,
				);
			}
			$o .= $this->radar_svg(
				$pts,
				( $s['radar_stroke'] ?? '' ) ?: '#f0ad2e',
				( $s['radar_fill'] ?? '' ) ?: 'rgba(240,173,46,.18)',
				( $s['radar_grid'] ?? '' ) ?: '#1d3149'
			);
		}
		if ( ! empty( $s['metas'] ) ) {
			$o .= '<div class="szl-featured__meta">';
			foreach ( (array) $s['metas'] as $m ) {
				$cls = ( ( $m['gold'] ?? '' ) === 'yes' ) ? ' szl-chip--gold' : '';
				$o  .= '<span class="szl-chip' . $cls . '">'
					. $this->icon( $m['icon'] ?? array() )
					. esc_html( $m['text'] ?? '' ) . '</span>';
			}
			$o .= '</div>';
		}
		$o .= '</div>';

		$quiz_id  = absint( $s['quiz_id'] ?? 0 );
		$mode     = $s['quiz_mode'] ?? 'modal';
		$modal_id = 'szl-quiz-' . $this->get_id() . '-featured';

		$o .= '</div><div class="szl-featured__cta">'
			. $this->quiz_button(
				$s['btn_text'] ?? '',
				$quiz_id,
				$mode,
				$s['quiz_page'] ?? '',
				$s['quiz_arg'] ?? 'quiz',
				$s['btn_link'] ?? array(),
				'gold',
				$s['btn_icon'] ?? null,
				$modal_id
			)
			. '</div></div>';

		if ( $quiz_id && 'modal' === $mode ) {
			$o .= $this->quiz_modal_html( $modal_id, $quiz_id, $s['title'] ?? '' );
		}

		return $o . '</div>';
	}
}

/* ==================== ۵) چگونه کار می‌کند ==================== */

class SZL_W_Steps extends SZL_Widget_Base {

	public function get_name() { return 'szl_steps'; }
	public function get_title() { return 'سازان: مراحل (چگونه کار می‌کند)'; }
	public function get_icon() { return 'eicon-number-field'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_main', array( 'label' => 'محتوا' ) );
		$this->heading_content_controls( 'چگونه کار می‌کند؟', 'در ۳ گام ساده، مسیر رشد کسب‌وکارتان را روشن کنید.' );

		$rep = new \Elementor\Repeater();
		$rep->add_control( 'num', array( 'label' => 'شماره گام', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '۱' ) );
		$rep->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'انتخاب آزمون', 'label_block' => true ) );
		$rep->add_control( 'desc', array(
			'label'   => 'توضیح',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 3,
			'default' => 'آزمون موردنظر خود را بر اساس نیاز و حوزه کسب‌وکارتان انتخاب کنید.',
		) );
		$rep->add_control( 'image', array( 'label' => 'تصویر', 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$rep->add_control( 'icon', array( 'label' => 'آیکن (اگر تصویر ندارید)', 'type' => \Elementor\Controls_Manager::ICONS ) );

		$this->add_control( 'items', array(
			'label'       => 'گام‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ num }}}. {{{ title }}}',
			'default'     => array(
				array( 'num' => '۱', 'title' => 'انتخاب آزمون', 'desc' => 'آزمون موردنظر خود را بر اساس نیاز و حوزه کسب‌وکارتان انتخاب کنید.' ),
				array( 'num' => '۲', 'title' => 'پاسخ به سوالات', 'desc' => 'به سوالات دقیق و کاربردی پاسخ دهید؛ هیچ پاسخ درست یا غلطی وجود ندارد.' ),
				array( 'num' => '۳', 'title' => 'دریافت گزارش و پیشنهادها', 'desc' => 'گزارش تحلیلی و پیشنهادهای عملی متناسب با وضعیت کسب‌وکارتان دریافت کنید.' ),
			),
		) );

		$this->add_control( 'reverse', array(
			'label'        => 'ترتیب معکوس (راست‌چین شماره‌گذاری)',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
		) );
		$this->add_control( 'arrows', array(
			'label'        => 'فلش اتصال بین گام‌ها',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->end_controls_section();

		/* ---- style ---- */
		$this->heading_style_section();
		$this->box_style_section( 'st_item', 'کارت گام', '.szl-step' );

		$this->start_controls_section( 'st_txt', array( 'label' => 'متن‌ها', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->columns_control( 'cols', '.szl-steps', 3 );
		$this->gap_control( 'items_gap', '.szl-steps', 20 );
		$this->text_style( 's_title', 'عنوان گام', '.szl-step__title' );
		$this->text_style( 's_desc', 'توضیح گام', '.szl-step__desc' );
		$this->add_control( 'n_color', array( 'label' => 'رنگ شماره', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-step__n' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'n_bg', array( 'label' => 'پس‌زمینه شماره', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-step__n' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'art_w', array(
			'label'      => 'عرض تصویر گام',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 30, 'max' => 200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-step__art' => 'width: {{SIZE}}{{UNIT}}; flex-basis: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->palette_section();
	}

	protected function html( array $s ) {
		$items = (array) ( $s['items'] ?? array() );
		if ( ! $items ) { return ''; }
		if ( ( $s['reverse'] ?? '' ) === 'yes' ) { $items = array_reverse( $items ); }

		$arrows = ( $s['arrows'] ?? 'yes' ) === 'yes' ? ' szl-steps--arrows' : '';

		$o = '<div class="szl">' . $this->heading_html( $s ) . '<div class="szl-grid szl-steps' . $arrows . '">';
		foreach ( $items as $it ) {
			$art = ! empty( $it['image']['url'] ) ? $this->img( $it['image'], $it['title'] ?? '' ) : $this->icon( $it['icon'] ?? array() );
			$o  .= '<div class="szl-step"><div class="szl-step__body">'
				. '<div class="szl-step__title">'
				. ( '' !== ( $it['num'] ?? '' ) ? '<span class="szl-step__n">' . esc_html( $it['num'] ) . '</span>' : '' )
				. '<span>' . esc_html( $it['title'] ?? '' ) . '</span>'
				. '</div>'
				. '<p class="szl-step__desc">' . esc_html( $it['desc'] ?? '' ) . '</p>'
				. '</div>';
			if ( $art ) { $o .= '<div class="szl-step__art">' . $art . '</div>'; }
			$o .= '</div>';
		}
		return $o . '</div></div>';
	}
}

/* ==================== ۶) نمونه گزارش تحلیلی ==================== */

class SZL_W_Report extends SZL_Widget_Base {

	public function get_name() { return 'szl_report'; }
	public function get_title() { return 'سازان: نمونه گزارش تحلیلی'; }
	public function get_icon() { return 'eicon-chart-line'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_main', array( 'label' => 'عنوان بخش' ) );
		$this->heading_content_controls( 'نمونه‌ای از گزارش تحلیلی شما', '' );
		$this->end_controls_section();

		/* --- نمودار روند --- */
		$this->start_controls_section( 'c_line', array( 'label' => 'نمودار روند' ) );
		$this->add_control( 'line_title', array( 'label' => 'عنوان نمودار', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'روند رشد امتیازها' ) );
		$this->add_control( 'series_a_label', array( 'label' => 'نام سری اول', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'شما' ) );
		$this->add_control( 'series_a', array(
			'label'       => 'مقادیر سری اول',
			'type'        => \Elementor\Controls_Manager::TEXTAREA,
			'rows'        => 2,
			'default'     => '42, 48, 55, 61, 66, 70, 75, 79, 84, 87',
			'description' => 'اعداد را با کاما جدا کنید.',
		) );
		$this->add_control( 'series_a_color', array( 'label' => 'رنگ سری اول', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#2fc6ea' ) );
		$this->add_control( 'series_b_label', array( 'label' => 'نام سری دوم', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'میانگین صنعت' ) );
		$this->add_control( 'series_b', array(
			'label'   => 'مقادیر سری دوم',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => '40, 43, 47, 50, 54, 57, 60, 63, 66, 68',
		) );
		$this->add_control( 'series_b_color', array( 'label' => 'رنگ سری دوم', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#f0ad2e' ) );
		$this->add_control( 'x_labels', array(
			'label'   => 'برچسب‌های محور افقی',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 2,
			'default' => '۱۴۰۴/۰۲, ۱۴۰۴/۰۳, ۱۴۰۴/۰۴, ۱۴۰۴/۰۵, ۱۴۰۴/۰۶',
		) );
		$this->add_control( 'show_legend', array( 'label' => 'نمایش راهنما', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->end_controls_section();

		/* --- امتیازها --- */
		$this->start_controls_section( 'c_scores', array( 'label' => 'نمای کلی امتیازها' ) );
		$this->add_control( 'scores_title', array( 'label' => 'عنوان ستون', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'نمای کلی امتیازها' ) );
		$this->add_control( 'total', array( 'label' => 'امتیاز کل', 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 87 ) );
		$this->add_control( 'total_max', array( 'label' => 'حداکثر', 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 100 ) );
		$this->add_control( 'total_label', array( 'label' => 'برچسب امتیاز کل', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'امتیاز کلی' ) );

		$tile = new \Elementor\Repeater();
		$tile->add_control( 'label', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'فروش' ) );
		$tile->add_control( 'value', array( 'label' => 'امتیاز', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '۸۹' ) );
		$tile->add_control( 'gold', array( 'label' => 'رنگ طلایی', 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes' ) );
		$this->add_control( 'tiles', array(
			'label'       => 'کاشی‌های امتیاز',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $tile->get_controls(),
			'title_field' => '{{{ label }}}: {{{ value }}}',
			'default'     => array(
				array( 'label' => 'فروش', 'value' => '۸۹' ),
				array( 'label' => 'بازاریابی', 'value' => '۷۸' ),
				array( 'label' => 'مالی', 'value' => '۷۴' ),
				array( 'label' => 'رهبری', 'value' => '۸۸' ),
				array( 'label' => 'منابع انسانی', 'value' => '۸۶' ),
				array( 'label' => 'رهبری تیم', 'value' => '۹۰' ),
			),
		) );
		$this->end_controls_section();

		/* --- بینش‌ها --- */
		$this->start_controls_section( 'c_insights', array( 'label' => 'بینش‌های کلیدی' ) );
		$this->add_control( 'ins_title', array( 'label' => 'عنوان ستون', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'بینش‌های کلیدی' ) );

		$ins = new \Elementor\Repeater();
		$ins->add_control( 'icon', array( 'label' => 'آیکن', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-lightbulb', 'library' => 'fa-solid' ) ) );
		$ins->add_control( 'text', array( 'label' => 'متن', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => 'فرصت بزرگ در بهبود نرخ تبدیل قیف فروش.' ) );
		$this->add_control( 'insights', array(
			'label'       => 'بینش‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $ins->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array(
				array( 'text' => 'فرصت بزرگ در بهبود نرخ تبدیل قیف فروش.' ),
				array( 'text' => 'مدیریت هزینه‌ها می‌تواند سودآوری را افزایش دهد.' ),
				array( 'text' => 'مستندسازی فرایندها نیاز به تقویت دارد.' ),
			),
		) );
		$this->add_control( 'btn_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مشاهده گزارش کامل', 'separator' => 'before' ) );
		$this->add_control( 'btn_link', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->end_controls_section();

		/* ---- style ---- */
		$this->heading_style_section();
		$this->box_style_section( 'st_box', 'جعبه گزارش', '.szl-report' );
		$this->box_style_section( 'st_panel', 'پنل‌های داخلی', '.szl-report .szl-panel' );

		$this->start_controls_section( 'st_misc', array( 'label' => 'اجزا', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'grid_tpl', array(
			'label'     => 'چیدمان ستون‌ها',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => '1.4fr 1fr 1.2fr',
			'options'   => array(
				'1.4fr 1fr 1.2fr' => 'نمودار پهن (پیش‌فرض)',
				'1fr 1fr 1fr'     => 'مساوی',
				'1fr'             => 'تک‌ستونی',
			),
			'selectors' => array( '{{WRAPPER}} .szl-report__grid' => 'grid-template-columns: {{VALUE}};' ),
		) );
		$this->gap_control( 'grid_gap', '.szl-report__grid', 22 );
		$this->text_style( 'p_title', 'عنوان پنل‌ها', '.szl-panel__title' );
		$this->text_style( 't_v', 'امتیاز کاشی‌ها', '.szl-tile__v' );
		$this->text_style( 't_k', 'عنوان کاشی‌ها', '.szl-tile__k' );
		$this->text_style( 'i_t', 'متن بینش‌ها', '.szl-insight' );
		$this->add_control( 'i_ico_color', array(
			'label'     => 'رنگ آیکن بینش‌ها',
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => array( '{{WRAPPER}} .szl-insight__ico' => 'color: {{VALUE}};' ),
		) );
		$this->add_control( 'ring_color', array( 'label' => 'رنگ حلقه امتیاز', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#2fc6ea' ) );
		$this->add_responsive_control( 'tiles_cols', array(
			'label'     => 'ستون‌های کاشی',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => '3',
			'options'   => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴' ),
			'selectors' => array( '{{WRAPPER}} .szl-tiles' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));' ),
		) );
		$this->end_controls_section();

		$this->button_style_section( 'st_btn', 'دکمه', '.szl-report .szl-btn' );
		$this->palette_section();
	}

	/** رشته «۱, ۲, ۳» را به آرایه عدد تبدیل می‌کند. */
	protected function to_numbers( $str ) {
		$out = array();
		foreach ( explode( ',', (string) $str ) as $p ) {
			$p = trim( $p );
			if ( '' === $p ) { continue; }
			$out[] = (float) $p;
		}
		return $out;
	}

	protected function html( array $s ) {
		$o = '<div class="szl">' . $this->heading_html( $s ) . '<div class="szl-box szl-report"><div class="szl-report__grid">';

		/* --- ستون بینش‌ها (راست در RTL) --- */
		$o .= '<div class="szl-panel">';
		if ( ! empty( $s['ins_title'] ) ) {
			$o .= '<div class="szl-panel__title">' . esc_html( $s['ins_title'] ) . '</div>';
		}
		$o .= '<div class="szl-insights">';
		foreach ( (array) ( $s['insights'] ?? array() ) as $i ) {
			$o .= '<div class="szl-insight">'
				. '<span class="szl-insight__ico">' . $this->icon( $i['icon'] ?? array() ) . '</span>'
				. '<span>' . esc_html( $i['text'] ?? '' ) . '</span>'
				. '</div>';
		}
		$o .= '</div>' . $this->button( $s['btn_text'] ?? '', $s['btn_link'] ?? array(), 'ghost' ) . '</div>';

		/* --- ستون امتیازها --- */
		$o .= '<div class="szl-panel">';
		if ( ! empty( $s['scores_title'] ) ) {
			$o .= '<div class="szl-panel__title">' . esc_html( $s['scores_title'] ) . '</div>';
		}
		$o .= '<div class="szl-scores"><div class="szl-tiles">';
		foreach ( (array) ( $s['tiles'] ?? array() ) as $t ) {
			$cls = ( ( $t['gold'] ?? '' ) === 'yes' ) ? ' szl-tile--gold' : '';
			$o  .= '<div class="szl-tile' . $cls . '">'
				. '<div class="szl-tile__v">' . esc_html( $t['value'] ?? '' ) . '</div>'
				. '<div class="szl-tile__k">' . esc_html( $t['label'] ?? '' ) . '</div>'
				. '</div>';
		}
		$o .= '</div><div class="szl-score">'
			. $this->ring_svg( (int) ( $s['total'] ?? 0 ), (int) ( $s['total_max'] ?? 100 ), '', ( $s['ring_color'] ?? '' ) ?: '#2fc6ea' )
			. '<span class="szl-score__label">' . esc_html( $s['total_label'] ?? '' ) . '</span>'
			. '</div></div></div>';

		/* --- ستون نمودار --- */
		$a = $this->to_numbers( $s['series_a'] ?? '' );
		$b = $this->to_numbers( $s['series_b'] ?? '' );
		$o .= '<div class="szl-panel">';
		if ( ! empty( $s['line_title'] ) ) {
			$o .= '<div class="szl-panel__title">' . esc_html( $s['line_title'] ) . '</div>';
		}
		$series = array();
		if ( $a ) { $series[] = array( 'color' => ( $s['series_a_color'] ?? '' ) ?: '#2fc6ea', 'values' => $a ); }
		if ( $b ) { $series[] = array( 'color' => ( $s['series_b_color'] ?? '' ) ?: '#f0ad2e', 'values' => $b, 'dashed' => true ); }

		$labels = array_filter( array_map( 'trim', explode( ',', (string) ( $s['x_labels'] ?? '' ) ) ) );
		$o     .= $this->line_svg( $series, array_values( $labels ) );

		if ( ( $s['show_legend'] ?? 'yes' ) === 'yes' ) {
			$o .= '<div class="szl-legend">'
				. '<span><i style="background:' . esc_attr( ( $s['series_a_color'] ?? '' ) ?: '#2fc6ea' ) . '"></i>' . esc_html( $s['series_a_label'] ?? '' ) . '</span>'
				. '<span><i style="background:' . esc_attr( ( $s['series_b_color'] ?? '' ) ?: '#f0ad2e' ) . '"></i>' . esc_html( $s['series_b_label'] ?? '' ) . '</span>'
				. '</div>';
		}
		$o .= '</div>';

		return $o . '</div></div></div>';
	}
}

/* ==================== ۷) سوالات متداول ==================== */

class SZL_W_Faq extends SZL_Widget_Base {

	public function get_name() { return 'szl_faq'; }
	public function get_title() { return 'سازان: سوالات متداول'; }
	public function get_icon() { return 'eicon-help-o'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_main', array( 'label' => 'محتوا' ) );
		$this->heading_content_controls( 'سوالات متداول', '' );

		$this->add_control( 'image', array( 'label' => 'تصویر کنار سوالات', 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$this->add_control( 'image_pos', array(
			'label'     => 'جای تصویر',
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => '2',
			'options'   => array( '2' => 'سمت چپ', '0' => 'سمت راست' ),
			'selectors' => array( '{{WRAPPER}} .szl-faq__media' => 'order: {{VALUE}};' ),
		) );

		$rep = new \Elementor\Repeater();
		$rep->add_control( 'q', array( 'label' => 'سوال', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'آیا نتایج ارزیابی‌ها محرمانه است؟', 'label_block' => true ) );
		$rep->add_control( 'a', array(
			'label'   => 'پاسخ',
			'type'    => \Elementor\Controls_Manager::WYSIWYG,
			'default' => 'بله. تمام پاسخ‌ها و نتایج شما محرمانه است و تنها برای تولید گزارش تحلیلی خودتان استفاده می‌شود.',
		) );
		$rep->add_control( 'open', array( 'label' => 'به‌صورت پیش‌فرض باز باشد', 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes' ) );

		$this->add_control( 'items', array(
			'label'       => 'سوال‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ q }}}',
			'default'     => array(
				array( 'q' => 'آیا نتایج ارزیابی‌ها محرمانه است؟' ),
				array( 'q' => 'چقدر طول می‌کشد تا گزارش را دریافت کنم؟' ),
				array( 'q' => 'آیا می‌توانم آزمون‌ها را بیش از یک‌بار انجام دهم؟' ),
				array( 'q' => 'این آزمون‌ها برای چه کسب‌وکارهایی مناسب است؟' ),
			),
		) );

		$this->add_control( 'single', array(
			'label'        => 'هم‌زمان فقط یک پاسخ باز باشد',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'return_value' => 'yes',
		) );
		$this->add_control( 'icon_open', array( 'label' => 'آیکن بسته', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-chevron-down', 'library' => 'fa-solid' ) ) );

		$this->end_controls_section();

		/* ---- style ---- */
		$this->heading_style_section();
		$this->box_style_section( 'st_item', 'کارت سوال', '.szl-qa' );

		$this->start_controls_section( 'st_txt', array( 'label' => 'متن‌ها', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->text_style( 'q_t', 'سوال', '.szl-qa__q' );
		$this->text_style( 'a_t', 'پاسخ', '.szl-qa__a' );
		$this->add_control( 'ico_color', array( 'label' => 'رنگ آیکن', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-qa__ico' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'open_bc', array( 'label' => 'رنگ حاشیه در حالت باز', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-qa.is-open' => 'border-color: {{VALUE}};' ) ) );
		$this->gap_control( 'items_gap', '.szl-faq__list', 12 );
		$this->add_responsive_control( 'media_w', array(
			'label'      => 'عرض ستون تصویر',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 120, 'max' => 500 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-faq__grid' => 'grid-template-columns: 1fr {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->palette_section();
	}

	protected function html( array $s ) {
		$items = (array) ( $s['items'] ?? array() );
		if ( ! $items ) { return ''; }

		$single = ( $s['single'] ?? 'yes' ) === 'yes' ? 'yes' : 'no';
		$img    = $this->img( $s['image'] ?? array(), $s['sec_title'] ?? '' );

		$o  = '<div class="szl" data-single="' . esc_attr( $single ) . '">';
		$o .= '<div class="szl-box szl-faq">' . $this->heading_html( $s ) . '<div class="szl-faq__grid">';
		$o .= '<div class="szl-faq__list">';

		foreach ( $items as $idx => $it ) {
			$open = ( ( $it['open'] ?? '' ) === 'yes' ) ? ' is-open' : '';
			$o   .= '<div class="szl-qa' . $open . '">'
				. '<button type="button" class="szl-qa__q" aria-expanded="' . ( $open ? 'true' : 'false' ) . '">'
				. '<span>' . esc_html( $it['q'] ?? '' ) . '</span>'
				. '<span class="szl-qa__ico">' . $this->icon( $s['icon_open'] ?? array() ) . '</span>'
				. '</button>'
				. '<div class="szl-qa__a">' . wp_kses_post( $it['a'] ?? '' ) . '</div>'
				. '</div>';
		}

		$o .= '</div><div class="szl-faq__media">' . $img . '</div>';

		return $o . '</div></div></div>';
	}
}

/* ==================== ۸) هدر (لوگو + منو + جستجو) ==================== */

class SZL_W_Header extends SZL_Widget_Base {

	public function get_name() { return 'szl_header'; }
	public function get_title() { return 'سازان: هدر سایت'; }
	public function get_icon() { return 'eicon-header'; }

	protected function register_controls() {

		$this->start_controls_section( 'c_logo', array( 'label' => 'لوگو' ) );
		$this->add_control( 'logo', array( 'label' => 'تصویر لوگو', 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$this->add_control( 'logo_text', array( 'label' => 'متن لوگو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'SAZAN' ) );
		$this->add_control( 'logo_sub', array( 'label' => 'زیرنویس لوگو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'آکادمی کسب‌وکار سازان' ) );
		$this->add_control( 'logo_link', array( 'label' => 'لینک لوگو', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '/' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_menu', array( 'label' => 'منو' ) );
		$rep = new \Elementor\Repeater();
		$rep->add_control( 'label', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'صفحه اصلی', 'label_block' => true ) );
		$rep->add_control( 'link', array( 'label' => 'لینک', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$rep->add_control( 'active', array( 'label' => 'آیتم فعال', 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes' ) );
		$this->add_control( 'menu', array(
			'label'       => 'آیتم‌های منو',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ label }}}',
			'default'     => array(
				array( 'label' => 'صفحه اصلی' ),
				array( 'label' => 'دوره‌ها' ),
				array( 'label' => 'مقالات' ),
				array( 'label' => 'ارزیابی', 'active' => 'yes' ),
				array( 'label' => 'درباره ما' ),
			),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_search', array( 'label' => 'جستجو' ) );
		$this->add_control( 'show_search', array( 'label' => 'نمایش کادر جستجو', 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes' ) );
		$this->add_control( 'search_ph', array(
			'label'     => 'متن راهنما',
			'type'      => \Elementor\Controls_Manager::TEXT,
			'default'   => 'جستجو در آزمون‌ها و مقالات...',
			'condition' => array( 'show_search' => 'yes' ),
		) );
		$this->add_control( 'search_icon', array(
			'label'     => 'آیکن جستجو',
			'type'      => \Elementor\Controls_Manager::ICONS,
			'default'   => array( 'value' => 'fas fa-search', 'library' => 'fa-solid' ),
			'condition' => array( 'show_search' => 'yes' ),
		) );
		$this->add_control( 'search_action', array(
			'label'       => 'آدرس صفحه نتایج',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '/',
			'description' => 'خالی بگذارید تا از جستجوی پیش‌فرض وردپرس استفاده شود.',
			'condition'   => array( 'show_search' => 'yes' ),
		) );
		$this->add_control( 'sticky', array( 'label' => 'چسبان در بالای صفحه', 'type' => \Elementor\Controls_Manager::SWITCHER, 'return_value' => 'yes' ) );
		$this->end_controls_section();

		/* ---- style ---- */
		$this->box_style_section( 'st_box', 'نوار هدر', '.szl-header' );

		$this->start_controls_section( 'st_menu', array( 'label' => 'استایل منو', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->text_style( 'm_item', 'آیتم منو', '.szl-nav a' );
		$this->add_control( 'm_active', array( 'label' => 'رنگ آیتم فعال', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-nav a.is-active' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'm_hover', array( 'label' => 'رنگ شناور', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-nav a:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'm_underline', array( 'label' => 'رنگ خط زیر آیتم فعال', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-nav a.is-active::after' => 'background: {{VALUE}};' ) ) );
		$this->gap_control( 'm_gap', '.szl-nav', 26 );
		$this->end_controls_section();

		$this->start_controls_section( 'st_logo', array( 'label' => 'استایل لوگو', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->text_style( 'l_text', 'متن لوگو', '.szl-logo__t' );
		$this->text_style( 'l_sub', 'زیرنویس لوگو', '.szl-logo__s' );
		$this->add_responsive_control( 'l_w', array(
			'label'      => 'عرض تصویر لوگو',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 20, 'max' => 200 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-logo img' => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'st_search', array(
			'label'     => 'استایل جستجو',
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => array( 'show_search' => 'yes' ),
		) );
		$this->add_control( 's_bg', array( 'label' => 'پس‌زمینه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-search' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 's_bc', array( 'label' => 'رنگ حاشیه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-search' => 'border-color: {{VALUE}};' ) ) );
		$this->text_style( 's_input', 'متن ورودی', '.szl-search input' );
		$this->add_control( 's_btn_bg', array( 'label' => 'پس‌زمینه دکمه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-search__btn' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 's_btn_c', array( 'label' => 'رنگ آیکن دکمه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-search__btn' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 's_w', array(
			'label'      => 'عرض کادر جستجو',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ),
			'range'      => array( 'px' => array( 'min' => 120, 'max' => 600 ), '%' => array( 'min' => 10, 'max' => 100 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-search' => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->palette_section();
	}

	protected function html( array $s ) {
		$sticky = ( ( $s['sticky'] ?? '' ) === 'yes' ) ? ' szl-header--sticky' : '';

		$o = '<div class="szl"><div class="szl-box szl-header' . $sticky . '">';

		/* لوگو */
		$o .= '<a ' . $this->link_attrs( (array) ( $s['logo_link'] ?? array() ) ) . ' class="szl-logo">';
		if ( ! empty( $s['logo']['url'] ) ) {
			$o .= $this->img( $s['logo'], $s['logo_text'] ?? '' );
		}
		if ( ! empty( $s['logo_text'] ) || ! empty( $s['logo_sub'] ) ) {
			$o .= '<span class="szl-logo__b">'
				. '<span class="szl-logo__t">' . esc_html( $s['logo_text'] ?? '' ) . '</span>'
				. '<span class="szl-logo__s">' . esc_html( $s['logo_sub'] ?? '' ) . '</span>'
				. '</span>';
		}
		$o .= '</a>';

		/* منو */
		if ( ! empty( $s['menu'] ) ) {
			$o .= '<nav class="szl-nav">';
			foreach ( (array) $s['menu'] as $m ) {
				$cls = ( ( $m['active'] ?? '' ) === 'yes' ) ? ' class="is-active"' : '';
				$o  .= '<a ' . $this->link_attrs( (array) ( $m['link'] ?? array() ) ) . $cls . '>' . esc_html( $m['label'] ?? '' ) . '</a>';
			}
			$o .= '</nav>';
		}

		/* جستجو */
		if ( ( $s['show_search'] ?? 'yes' ) === 'yes' ) {
			$action = trim( (string) ( $s['search_action'] ?? '' ) );
			$action = $action ? $action : home_url( '/' );
			$o     .= '<form class="szl-search" role="search" method="get" action="' . esc_url( $action ) . '">'
				. '<button type="submit" class="szl-search__btn" aria-label="جستجو">' . $this->icon( $s['search_icon'] ?? array() ) . '</button>'
				. '<input type="search" name="s" value="" placeholder="' . esc_attr( $s['search_ph'] ?? '' ) . '" />'
				. '</form>';
		}

		return $o . '</div></div>';
	}
}

/* ==================== ۹) فوتر ==================== */

class SZL_W_Footer extends SZL_Widget_Base {

	public function get_name() { return 'szl_footer'; }
	public function get_title() { return 'سازان: فوتر سایت'; }
	public function get_icon() { return 'eicon-footer'; }

	/** ریپیتر لینک‌های ساده. */
	protected function links_repeater() {
		$r = new \Elementor\Repeater();
		$r->add_control( 'label', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دوره‌ها', 'label_block' => true ) );
		$r->add_control( 'link', array( 'label' => 'لینک', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		return $r;
	}

	protected function register_controls() {

		/* ستون تماس */
		$this->start_controls_section( 'c_contact', array( 'label' => 'ستون اطلاعات تماس' ) );
		$this->add_control( 'contact_title', array( 'label' => 'عنوان ستون', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'اطلاعات تماس' ) );
		$cr = new \Elementor\Repeater();
		$cr->add_control( 'icon', array( 'label' => 'آیکن', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-phone', 'library' => 'fa-solid' ) ) );
		$cr->add_control( 'text', array( 'label' => 'متن', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => '۰۲۱-۹۱۰۰۹۰۰۰', 'label_block' => true ) );
		$cr->add_control( 'link', array( 'label' => 'لینک (اختیاری)', 'type' => \Elementor\Controls_Manager::URL ) );
		$this->add_control( 'contacts', array(
			'label'       => 'آیتم‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $cr->get_controls(),
			'title_field' => '{{{ text }}}',
			'default'     => array(
				array( 'text' => '۰۲۱-۹۱۰۰۹۰۰۰', 'icon' => array( 'value' => 'fas fa-phone', 'library' => 'fa-solid' ) ),
				array( 'text' => 'info@sazan.academy', 'icon' => array( 'value' => 'fas fa-envelope', 'library' => 'fa-solid' ) ),
				array( 'text' => "تهران، خیابان سهروردی، پلاک ۱۳۳\nواحد ۵، ساختمان سازان", 'icon' => array( 'value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid' ) ),
			),
		) );
		$this->end_controls_section();

		/* ستون دسته‌بندی‌ها */
		$this->start_controls_section( 'c_cats', array( 'label' => 'ستون دسته‌بندی‌ها' ) );
		$this->add_control( 'cats_title', array( 'label' => 'عنوان ستون', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دسته‌بندی‌ها' ) );
		$this->add_control( 'cats', array(
			'label'       => 'لینک‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $this->links_repeater()->get_controls(),
			'title_field' => '{{{ label }}}',
			'default'     => array(
				array( 'label' => 'فروش' ),
				array( 'label' => 'مارکتینگ' ),
				array( 'label' => 'برندینگ' ),
				array( 'label' => 'سیستم‌سازی' ),
				array( 'label' => 'منابع انسانی' ),
				array( 'label' => 'مالی' ),
				array( 'label' => 'رهبری' ),
			),
		) );
		$this->end_controls_section();

		/* ستون دسترسی سریع */
		$this->start_controls_section( 'c_quick', array( 'label' => 'ستون دسترسی سریع' ) );
		$this->add_control( 'quick_title', array( 'label' => 'عنوان ستون', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دسترسی سریع' ) );
		$this->add_control( 'quick', array(
			'label'       => 'لینک‌ها',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $this->links_repeater()->get_controls(),
			'title_field' => '{{{ label }}}',
			'default'     => array(
				array( 'label' => 'دوره‌ها' ),
				array( 'label' => 'ارزیابی' ),
				array( 'label' => 'مقالات' ),
				array( 'label' => 'تقویم آموزشی' ),
				array( 'label' => 'تماس با ما' ),
			),
		) );
		$this->end_controls_section();

		/* ستون درباره */
		$this->start_controls_section( 'c_about', array( 'label' => 'ستون درباره' ) );
		$this->add_control( 'about_title', array( 'label' => 'عنوان ستون', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'درباره سازان' ) );
		$this->add_control( 'about_text', array(
			'label'   => 'متن',
			'type'    => \Elementor\Controls_Manager::TEXTAREA,
			'rows'    => 5,
			'default' => 'سازان مرجع آموزش و مشاوره کسب‌وکار برای کارآفرینان و مدیران ایرانی است. با آموزش، مشاوره و ابزارهای کاربردی، مسیر رشد کسب‌وکار شما را هموار می‌کنیم.',
		) );
		$sr = new \Elementor\Repeater();
		$sr->add_control( 'icon', array( 'label' => 'آیکن', 'type' => \Elementor\Controls_Manager::ICONS, 'default' => array( 'value' => 'fab fa-instagram', 'library' => 'fa-brands' ) ) );
		$sr->add_control( 'link', array( 'label' => 'لینک', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'socials', array(
			'label'       => 'شبکه‌های اجتماعی',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $sr->get_controls(),
			'title_field' => '{{{ icon.value }}}',
			'default'     => array(
				array( 'icon' => array( 'value' => 'fab fa-linkedin-in', 'library' => 'fa-brands' ) ),
				array( 'icon' => array( 'value' => 'fab fa-instagram', 'library' => 'fa-brands' ) ),
				array( 'icon' => array( 'value' => 'fab fa-telegram-plane', 'library' => 'fa-brands' ) ),
				array( 'icon' => array( 'value' => 'fab fa-youtube', 'library' => 'fa-brands' ) ),
			),
		) );
		$this->end_controls_section();

		$this->start_controls_section( 'c_bottom', array( 'label' => 'کپی‌رایت' ) );
		$this->add_control( 'copy', array(
			'label'   => 'متن کپی‌رایت',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '© ۱۴۰۴ تمامی حقوق این وب‌سایت متعلق به سازان است.',
			'label_block' => true,
		) );
		$this->end_controls_section();

		/* ---- style ---- */
		$this->box_style_section( 'st_box', 'جعبه فوتر', '.szl-footer' );

		$this->start_controls_section( 'st_txt', array( 'label' => 'متن‌ها', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->columns_control( 'cols', '.szl-footer__cols', 4 );
		$this->gap_control( 'cols_gap', '.szl-footer__cols', 26 );
		$this->text_style( 'f_h', 'عنوان ستون‌ها', '.szl-fcol__h' );
		$this->text_style( 'f_l', 'لینک‌ها و متن‌ها', '.szl-fcol a, .szl-fcol p, .szl-fcol li' );
		$this->add_control( 'f_l_hover', array( 'label' => 'رنگ لینک شناور', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-fcol a:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'f_ico', array( 'label' => 'رنگ آیکن تماس', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-fcontact__ico' => 'color: {{VALUE}};' ) ) );
		$this->text_style( 'f_copy', 'کپی‌رایت', '.szl-footer__copy' );
		$this->end_controls_section();

		$this->start_controls_section( 'st_social', array( 'label' => 'شبکه‌های اجتماعی', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'so_color', array( 'label' => 'رنگ آیکن', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-social a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'so_bg', array( 'label' => 'پس‌زمینه', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-social a' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'so_h_color', array( 'label' => 'رنگ آیکن (شناور)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-social a:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'so_h_bg', array( 'label' => 'پس‌زمینه (شناور)', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .szl-social a:hover' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'so_size', array(
			'label'      => 'اندازه',
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => array( 'px' ),
			'range'      => array( 'px' => array( 'min' => 24, 'max' => 70 ) ),
			'selectors'  => array( '{{WRAPPER}} .szl-social a' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		$this->palette_section();
	}

	/** ستون لینک‌ها. */
	protected function links_col( $title, $items ) {
		if ( empty( $items ) ) { return ''; }
		$o = '<div class="szl-fcol"><div class="szl-fcol__h">' . esc_html( $title ) . '</div><ul class="szl-flinks">';
		foreach ( (array) $items as $l ) {
			$o .= '<li><a ' . $this->link_attrs( (array) ( $l['link'] ?? array() ) ) . '>' . esc_html( $l['label'] ?? '' ) . '</a></li>';
		}
		return $o . '</ul></div>';
	}

	protected function html( array $s ) {
		$o = '<div class="szl"><div class="szl-box szl-footer"><div class="szl-footer__cols">';

		/* ستون تماس (راست) */
		if ( ! empty( $s['contacts'] ) ) {
			$o .= '<div class="szl-fcol"><div class="szl-fcol__h">' . esc_html( $s['contact_title'] ?? '' ) . '</div>';
			foreach ( (array) $s['contacts'] as $c ) {
				$txt = nl2br( esc_html( $c['text'] ?? '' ) );
				if ( ! empty( $c['link']['url'] ) ) {
					$txt = '<a ' . $this->link_attrs( (array) $c['link'] ) . '>' . $txt . '</a>';
				}
				$o .= '<div class="szl-fcontact">'
					. '<span class="szl-fcontact__ico">' . $this->icon( $c['icon'] ?? array() ) . '</span>'
					. '<span>' . $txt . '</span></div>';
			}
			$o .= '</div>';
		}

		$o .= $this->links_col( $s['cats_title'] ?? '', $s['cats'] ?? array() );
		$o .= $this->links_col( $s['quick_title'] ?? '', $s['quick'] ?? array() );

		/* ستون درباره (چپ) */
		$o .= '<div class="szl-fcol">';
		if ( ! empty( $s['about_title'] ) ) {
			$o .= '<div class="szl-fcol__h">' . esc_html( $s['about_title'] ) . '</div>';
		}
		if ( ! empty( $s['about_text'] ) ) {
			$o .= '<p>' . nl2br( esc_html( $s['about_text'] ) ) . '</p>';
		}
		if ( ! empty( $s['socials'] ) ) {
			$o .= '<div class="szl-social">';
			foreach ( (array) $s['socials'] as $so ) {
				$o .= '<a ' . $this->link_attrs( (array) ( $so['link'] ?? array() ) ) . '>' . $this->icon( $so['icon'] ?? array() ) . '</a>';
			}
			$o .= '</div>';
		}
		$o .= '</div></div>';

		if ( ! empty( $s['copy'] ) ) {
			$o .= '<div class="szl-footer__copy">' . esc_html( $s['copy'] ) . '</div>';
		}

		return $o . '</div></div>';
	}
}

/** فهرست کلاس ویجت‌های صفحه فرود برای ثبت در المنتور. */
function szl_elementor_widget_list() {
	return array(
		'SZL_W_Header',
		'SZL_W_Hero',
		'SZL_W_Stats',
		'SZL_W_Tests',
		'SZL_W_Featured',
		'SZL_W_Steps',
		'SZL_W_Report',
		'SZL_W_Faq',
		'SZL_W_Footer',
	);
}
