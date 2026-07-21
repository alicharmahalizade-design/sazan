<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * «اسلایدر دوره‌ها» — هیروی معرفی دوره‌های در حال ثبت‌نام با ۱۷ طرح گرافیکی.
 *
 * طرح‌ها از یک بوم طراحی (Claude Design) استخراج و به قالب‌های نشانه‌گذاری تبدیل شده‌اند
 * (includes/elementor/slider-variants.php). موتور اجرا سمت کلاینت (بدون React) در
 * assets/js/sazan-courses-slider.js پیاده شده است.
 *
 * منبع داده:
 *   auto   → از پست‌تایپ دوره‌ها (عنوان، توضیح، تاریخِ نزدیک‌ترین جلسه، لینک).
 *   manual → ردیف‌های دستی.
 *   both   → دوره‌ها + ردیف‌های دستی.
 */
class SZP_Courses_Slider {

	/** داده‌ی طرح‌ها (کش‌شده). */
	protected static function data() {
		static $d = null;
		if ( $d === null ) {
			$d = require SZP_DIR . 'includes/elementor/slider-variants.php';
			if ( ! is_array( $d ) ) {
				$d = array();
			}
		}
		return $d;
	}

	/** نگاشت شناسه‌ی طرح → برچسب فارسی (برای کنترل‌های المنتور). */
	public static function variants() {
		$out = array();
		foreach ( self::data() as $id => $v ) {
			$out[ $id ] = $id . ' — ' . $v['label'];
		}
		return $out;
	}

	public static function default_design() {
		$k = array_keys( self::data() );
		return $k ? $k[0] : '6a';
	}

	/* ==================== تاریخ شمسی (فقط روز/ماه/سال) ==================== */

	protected static function jdate_only( $ts ) {
		if ( ! $ts || ! function_exists( 'szp_g2j' ) ) {
			return '';
		}
		$dt = new DateTime( '@' . $ts );
		$dt->setTimezone( wp_timezone() );
		list( $jy, $jm, $jd ) = szp_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
		$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		return szp_fa_digits( $jd . ' ' . $months[ $jm ] . ' ' . $jy );
	}

	/** نزدیک‌ترین (زودترین) زمان جلسه‌ی یک دوره. */
	protected static function course_start_ts( $cid ) {
		$q = new WP_Query( array(
			'post_type'      => 'szp_session',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_szp_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array( 'key' => '_szp_course_id', 'value' => (int) $cid, 'compare' => '=' ),
			),
		) );
		if ( empty( $q->posts ) ) {
			return 0;
		}
		return (int) szp_ts_from_datetime( get_post_meta( (int) $q->posts[0], '_szp_datetime', true ) );
	}

	protected static function first_word( $s ) {
		$s = trim( wp_strip_all_tags( (string) $s ) );
		if ( $s === '' ) {
			return '';
		}
		$parts = preg_split( '/\s+/u', $s );
		return $parts ? $parts[0] : '';
	}

	/* ==================== جمع‌آوری اسلایدها ==================== */

	protected static function auto_slides( $count ) {
		$q = new WP_Query( array(
			'post_type'      => 'szp_course',
			'post_status'    => 'publish',
			'posts_per_page' => $count > 0 ? $count : -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'no_found_rows'  => true,
		) );
		$out = array();
		foreach ( $q->posts as $p ) {
			$cid  = $p->ID;
			$desc = has_excerpt( $cid ) ? get_the_excerpt( $cid ) : wp_trim_words( wp_strip_all_tags( $p->post_content ), 24, '…' );
			$out[] = array(
				'name'  => get_the_title( $cid ),
				'desc'  => $desc,
				'date'  => self::jdate_only( self::course_start_ts( $cid ) ),
				'ghost' => self::first_word( get_the_title( $cid ) ),
				'url'   => get_permalink( $cid ),
			);
		}
		wp_reset_postdata();
		return $out;
	}

	protected static function manual_slides( $items ) {
		$out = array();
		if ( ! is_array( $items ) ) {
			return $out;
		}
		foreach ( $items as $it ) {
			$name = isset( $it['name'] ) ? trim( (string) $it['name'] ) : '';
			if ( $name === '' ) {
				continue;
			}
			$out[] = array(
				'name'  => $name,
				'desc'  => isset( $it['desc'] ) ? (string) $it['desc'] : '',
				'date'  => isset( $it['date'] ) ? (string) $it['date'] : '',
				'ghost' => ! empty( $it['ghost'] ) ? (string) $it['ghost'] : self::first_word( $name ),
				'url'   => ! empty( $it['url'] ) ? (string) $it['url'] : '#',
			);
		}
		return $out;
	}

	/** نمونه‌ی پیش‌فرض (وقتی هیچ دوره/ردیفی نیست) تا ویجت در ویرایشگر خالی نماند. */
	protected static function demo_slides() {
		return array(
			array( 'name' => 'کارگاه فروش حرفه‌ای', 'desc' => 'مهارت‌های فروش مدرن، مذاکره و بستن قرارداد را در یک کارگاه عملی و فشرده بیاموزید.', 'date' => '۲۵ تیر ۱۴۰۵', 'ghost' => 'فروش', 'url' => '#' ),
			array( 'name' => 'دوره حکمرانی بر بازار', 'desc' => 'استراتژی تسلط بر بازار، برندسازی و رهبری رقابت — برای مدیرانی که می‌خواهند قواعد بازی را بنویسند.', 'date' => '۱۰ مرداد ۱۴۰۵', 'ghost' => 'حکمرانی', 'url' => '#' ),
		);
	}

	protected static function collect_slides( $a ) {
		$src   = isset( $a['source'] ) ? $a['source'] : 'auto';
		$count = isset( $a['count'] ) ? (int) $a['count'] : 0;
		$slides = array();
		if ( $src === 'auto' || $src === 'both' ) {
			$slides = self::auto_slides( $count );
		}
		if ( $src === 'manual' || $src === 'both' ) {
			$slides = array_merge( $slides, self::manual_slides( isset( $a['manual'] ) ? $a['manual'] : array() ) );
		}
		if ( ! $slides ) {
			$slides = self::demo_slides();
		}
		if ( $count > 0 && count( $slides ) > $count ) {
			$slides = array_slice( $slides, 0, $count );
		}
		return $slides;
	}

	/* ==================== ساخت نشانه‌گذاری ==================== */

	protected static function fill_slide( $tpl, $anim, $i, $c ) {
		$name  = (string) $c['name'];
		$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $name ) ) );
		$bold  = $words ? array_pop( $words ) : $name;
		$thin  = $words ? ( implode( ' ', $words ) . ' ' ) : '';
		$num   = szp_fa_digits( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) );

		$tpl = str_replace( 'szp-cs-slide', 'szp-cs-slide szp-cs-anim-' . $anim, $tpl );
		$rep = array(
			'{ACTIVE}'   => $i === 0 ? ' is-active' : '',
			'{I}'        => (string) $i,
			'{NUM}'      => $num,
			'{NAME}'     => esc_html( $name ),
			'{DESC}'     => esc_html( (string) $c['desc'] ),
			'{DATE}'     => esc_html( (string) $c['date'] ),
			'{GHOST}'    => esc_html( (string) ( $c['ghost'] ?? '' ) ),
			'{NAMETHIN}' => esc_html( $thin ),
			'{NAMEBOLD}' => esc_html( $bold ),
			'{URL}'      => esc_url( $c['url'] ?: '#' ),
		);
		return strtr( $tpl, $rep );
	}

	protected static function fill_dot( $tpl, $i ) {
		return strtr( $tpl, array( '{DACTIVE}' => $i === 0 ? ' is-active' : '', '{I}' => (string) $i ) );
	}

	/** یک «سند طرح» (یک اسلایدر کامل با طرح مشخص). */
	protected static function build_doc( $vid, $slides, $archer_url, $a, $index, $active ) {
		$data = self::data();
		if ( ! isset( $data[ $vid ] ) ) {
			return '';
		}
		$v      = $data[ $vid ];
		$chrome = $v['chrome'];

		foreach ( $v['slides'] as $k => $loop ) {
			$html = '';
			foreach ( $slides as $i => $c ) {
				$html .= self::fill_slide( $loop['tpl'], $loop['anim'], $i, $c );
			}
			$chrome = str_replace( '{SLIDES' . $k . '}', $html, $chrome );
		}

		if ( ! empty( $v['dots'] ) ) {
			$dots = '';
			foreach ( $slides as $i => $c ) {
				$dots .= self::fill_dot( $v['dots'], $i );
			}
			$chrome = str_replace( '{DOTS}', $dots, $chrome );
		}

		if ( $archer_url ) {
			$chrome = str_replace( '{ARCHER}', esc_url( $archer_url ), $chrome );
		} else {
			$chrome = preg_replace( '/<img class="szp-cs-archer"[^>]*>/', '', $chrome );
		}

		$autoplay = ( isset( $a['autoplay'] ) && ! $a['autoplay'] ) ? '0' : '1';
		$interval = isset( $a['interval'] ) ? max( 2, (int) $a['interval'] ) : 5;

		return '<div class="szp-cs-doc' . ( $active ? ' is-active' : '' ) . '" data-doc="' . $index . '"'
			. ' data-autoplay="' . esc_attr( $autoplay ) . '" data-interval="' . esc_attr( $interval ) . '">'
			. $chrome . '</div>';
	}

	/* ==================== رندر عمومی ==================== */

	/**
	 * $a: design, source, count, autoplay, interval, archer, archer_url,
	 *     manual (آرایه)، show_switcher، designs (آرایه)، title.
	 */
	public static function render( $a = array() ) {
		$data = self::data();
		if ( ! $data ) {
			return '';
		}
		$design = isset( $a['design'] ) && isset( $data[ $a['design'] ] ) ? $a['design'] : self::default_design();

		// آرشیو تصویر کماندار.
		$archer = '';
		if ( ! isset( $a['archer'] ) || $a['archer'] ) {
			$archer = ! empty( $a['archer_url'] ) ? $a['archer_url'] : SZP_URL . 'assets/img/archer.png';
		}

		$slides = self::collect_slides( $a );

		// کدام طرح‌ها رندر شوند؟
		$switch  = ! empty( $a['show_switcher'] );
		$designs = array( $design );
		if ( $switch ) {
			$sel = isset( $a['designs'] ) && is_array( $a['designs'] ) ? array_values( array_filter( $a['designs'] ) ) : array();
			$sel = array_values( array_intersect( array_keys( $data ), $sel ) );
			if ( ! $sel ) {
				$sel = array_keys( $data );
			}
			if ( ! in_array( $design, $sel, true ) ) {
				array_unshift( $sel, $design );
			}
			// طرح انتخاب‌شده اول باشد.
			$sel = array_merge( array( $design ), array_values( array_diff( $sel, array( $design ) ) ) );
			$designs = $sel;
		}

		ob_start(); ?>
		<div class="szp szp-cslider" dir="rtl">
			<?php if ( ! empty( $a['title'] ) ) : ?>
				<h3 class="szp-cs-heading"><?php echo esc_html( $a['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $switch && count( $designs ) > 1 ) : ?>
				<div class="szp-cs-switch" role="tablist" aria-label="انتخاب طرح اسلایدر">
					<?php foreach ( $designs as $j => $vid ) : ?>
						<button type="button" class="szp-cs-switch-btn<?php echo $j === 0 ? ' is-active' : ''; ?>" data-doc="<?php echo (int) $j; ?>">
							<?php echo esc_html( $vid . ' — ' . $data[ $vid ]['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="szp-cs-scaler">
				<div class="szp-cs-stagewrap">
					<?php
					foreach ( $designs as $j => $vid ) {
						echo self::build_doc( $vid, $slides, $archer, $a, $j, $j === 0 ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
