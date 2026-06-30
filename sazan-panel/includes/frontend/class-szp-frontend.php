<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Frontend {

	public static function init() {
		add_shortcode( 'sazan_panel', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'sazan_courses', array( __CLASS__, 'sc_courses' ) );
		add_shortcode( 'sazan_sessions', array( __CLASS__, 'sc_sessions' ) );
		add_shortcode( 'sazan_chat', array( __CLASS__, 'sc_chat' ) );
		add_shortcode( 'sazan_coaching', array( __CLASS__, 'sc_coaching' ) );
		add_shortcode( 'sazan_service_canvas', array( __CLASS__, 'sc_canvas' ) );
		add_shortcode( 'sazan_canvas_gallery', array( __CLASS__, 'sc_canvas_gallery' ) );
		add_shortcode( 'sazan_my_eval', array( __CLASS__, 'sc_eval' ) );
		add_shortcode( 'sazan_eval_board', array( __CLASS__, 'sc_eval_board' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	protected static function enqueue() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_script( 'szp-front' );
	}

	public static function assets() {
		$css = SZP_DIR . 'assets/css/sazan-panel.css';
		$js  = SZP_DIR . 'assets/js/sazan-panel.js';
		wp_register_style( 'szp-front', SZP_URL . 'assets/css/sazan-panel.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
		wp_register_script( 'szp-front', SZP_URL . 'assets/js/sazan-panel.js', array(), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
		wp_localize_script( 'szp-front', 'SZP_FRONT', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szp_front' ),
		) );

		$ccss = SZP_DIR . 'assets/css/sazan-chat.css';
		$cjs  = SZP_DIR . 'assets/js/sazan-chat.js';
		wp_register_style( 'szp-chat', SZP_URL . 'assets/css/sazan-chat.css', array( 'szp-front' ), file_exists( $ccss ) ? filemtime( $ccss ) : SZP_VERSION );
		wp_register_script( 'szp-chat', SZP_URL . 'assets/js/sazan-chat.js', array( 'szp-front' ), file_exists( $cjs ) ? filemtime( $cjs ) : SZP_VERSION, true );

		$cocss = SZP_DIR . 'assets/css/sazan-coach.css';
		$cojs  = SZP_DIR . 'assets/js/sazan-coach.js';
		wp_register_style( 'szp-coach', SZP_URL . 'assets/css/sazan-coach.css', array( 'szp-front' ), file_exists( $cocss ) ? filemtime( $cocss ) : SZP_VERSION );
		wp_register_script( 'szp-coach', SZP_URL . 'assets/js/sazan-coach.js', array( 'szp-front' ), file_exists( $cojs ) ? filemtime( $cojs ) : SZP_VERSION, true );

		$cvcss = SZP_DIR . 'assets/css/sazan-canvas.css';
		$cvjs  = SZP_DIR . 'assets/js/sazan-canvas.js';
		wp_register_style( 'szp-canvas', SZP_URL . 'assets/css/sazan-canvas.css', array( 'szp-front' ), file_exists( $cvcss ) ? filemtime( $cvcss ) : SZP_VERSION );
		wp_register_script( 'szp-canvas', SZP_URL . 'assets/js/sazan-canvas.js', array( 'szp-front' ), file_exists( $cvjs ) ? filemtime( $cvjs ) : SZP_VERSION, true );

		$evcss = SZP_DIR . 'assets/css/sazan-eval.css';
		$evjs  = SZP_DIR . 'assets/js/sazan-eval.js';
		wp_register_style( 'szp-eval', SZP_URL . 'assets/css/sazan-eval.css', array( 'szp-front' ), file_exists( $evcss ) ? filemtime( $evcss ) : SZP_VERSION );
		wp_register_script( 'szp-eval', SZP_URL . 'assets/js/sazan-eval.js', array( 'szp-front' ), file_exists( $evjs ) ? filemtime( $evjs ) : SZP_VERSION, true );
	}

	/* ---------------- «ارزیابی من» shortcode ---------------- */

	public static function sc_eval( $atts ) {
		$a = shortcode_atts( array( 'title' => '', 'currency' => 'تومان' ), $atts, 'sazan_my_eval' );
		if ( ! is_user_logged_in() ) {
			return self::login_box();
		}
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-eval' );
		wp_enqueue_script( 'szp-eval' );
		return SZP_Eval::render( $a );
	}

	/** تابلوی ارزیابی همه‌ی اشخاص (شبکه‌ای) — مخصوص مدیر/مدرّب. */
	public static function sc_eval_board( $atts ) {
		$a = shortcode_atts( array( 'title' => '', 'currency' => '', 'group' => '0' ), $atts, 'sazan_eval_board' );
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-eval' );
		return SZP_Eval::board( $a );
	}

	/* ---------------- service canvas shortcode ---------------- */

	public static function sc_canvas( $atts ) {
		$a = shortcode_atts( array( 'key' => '', 'title' => '' ), $atts, 'sazan_service_canvas' );
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-canvas' );
		wp_enqueue_script( 'szp-canvas' );
		return SZP_Canvas::render( $a );
	}

	/** گالری همهٔ شرکت‌کنندگان یک بوم (کاروسل/شبکه). */
	public static function sc_canvas_gallery( $atts ) {
		$a = shortcode_atts( array( 'key' => '', 'view' => 'grid', 'count' => '0', 'title' => '' ), $atts, 'sazan_canvas_gallery' );
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_script( 'szp-front' );
		wp_enqueue_style( 'szp-canvas' );
		return SZP_Canvas::gallery( $a );
	}

	public static function shortcode( $atts ) {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_script( 'szp-front' );

		if ( ! is_user_logged_in() ) {
			return '<div class="szp"><div class="szp-empty">برای مشاهده دوره‌ها ابتدا وارد شوید.</div></div>';
		}

		$user_id = get_current_user_id();
		$view    = isset( $_GET['szp'] ) ? sanitize_key( $_GET['szp'] ) : 'list';
		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( $view === 'course' && $id ) {
			if ( ! SZP_Access::user_can_course( $user_id, $id ) ) {
				return self::forbidden();
			}
			return szp_locate_template( 'single-course.php', array( 'course_id' => $id, 'user_id' => $user_id ) );
		}

		if ( $view === 'session' && $id ) {
			$cid = (int) get_post_meta( $id, '_szp_course_id', true );
			if ( ! $cid || ! SZP_Access::user_can_course( $user_id, $cid ) ) {
				return self::forbidden();
			}
			return szp_locate_template( 'single-session.php', array( 'session_id' => $id, 'course_id' => $cid, 'user_id' => $user_id ) );
		}

		return szp_locate_template( 'course-list.php', array( 'user_id' => $user_id ) );
	}

	protected static function forbidden() {
		return '<div class="szp"><div class="szp-empty">شما به این بخش دسترسی ندارید.</div></div>';
	}

	/* ---------------- URL helpers ---------------- */

	public static function current_clean() {
		return remove_query_arg( array( 'szp', 'id', 'room' ) );
	}

	/** Resolve link base for the mini-shortcodes: page id, explicit url, or current page. */
	public static function panel_base( $panel = '' ) {
		if ( $panel === '' ) {
			return self::current_clean();
		}
		if ( is_numeric( $panel ) ) {
			$u = get_permalink( (int) $panel );
			return $u ? $u : self::current_clean();
		}
		return $panel;
	}

	public static function url_course( $cid ) {
		return esc_url( add_query_arg( array( 'szp' => 'course', 'id' => (int) $cid ), self::current_clean() ) );
	}

	public static function url_session( $sid ) {
		return esc_url( add_query_arg( array( 'szp' => 'session', 'id' => (int) $sid ), self::current_clean() ) );
	}

	public static function url_list() {
		return esc_url( self::current_clean() );
	}

	public static function url_room( $cid ) {
		return esc_url( add_query_arg( array( 'room' => (int) $cid ), self::current_clean() ) );
	}

	/* ---------------- coaching shortcode ---------------- */

	public static function sc_coaching( $atts ) {
		$a = shortcode_atts( array( 'course' => '0', 'panel' => '' ), $atts, 'sazan_coaching' );
		if ( ! is_user_logged_in() ) {
			return self::login_box();
		}
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-coach' );
		wp_enqueue_script( 'szp-coach' );

		$course = isset( $_GET['cc'] ) ? absint( $_GET['cc'] ) : (int) $a['course'];
		return SZP_Coach_Render::render( get_current_user_id(), $course, $a['panel'] );
	}

	/* ---------------- chat room shortcode ---------------- */

	public static function sc_chat( $atts ) {
		if ( ! is_user_logged_in() ) {
			return self::login_box();
		}
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-chat' );
		wp_enqueue_script( 'szp-chat' );

		$user_id = get_current_user_id();
		$room    = isset( $_GET['room'] ) ? absint( $_GET['room'] ) : 0;

		if ( $room ) {
			if ( ! SZP_Access::user_can_course( $user_id, $room ) ) {
				return self::forbidden();
			}
			return szp_locate_template( 'chatroom.php', array( 'course_id' => $room, 'user_id' => $user_id ) );
		}
		return szp_locate_template( 'chat-list.php', array( 'user_id' => $user_id ) );
	}

	/* ---------------- data helper ---------------- */

	/** Sessions of a course split into past list + the single next upcoming. */
	public static function course_sessions( $course_id ) {
		$q = new WP_Query( array(
			'post_type'      => 'szp_session',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_szp_course_id',
			'meta_value'     => (int) $course_id,
			'no_found_rows'  => true,
		) );
		$now   = time();
		$items = array();
		foreach ( $q->posts as $p ) {
			$ts = szp_ts_from_datetime( get_post_meta( $p->ID, '_szp_datetime', true ) );
			$items[] = array( 'id' => $p->ID, 'title' => $p->post_title, 'ts' => $ts );
		}
		usort( $items, function( $a, $b ) {
			return $a['ts'] <=> $b['ts'];
		} );

		$past = array();
		$next = null;
		foreach ( $items as $it ) {
			if ( $it['ts'] && $it['ts'] > $now ) {
				if ( $next === null ) {
					$next = $it;
				}
			} else {
				$past[] = $it;
			}
		}
		return array( 'past' => $past, 'next' => $next, 'all' => $items );
	}

	/** Session ids the user may see (across accessible courses, or one course), newest first. */
	public static function user_sessions( $user_id, $course_id = 0, $count = -1 ) {
		$courses = self::user_courses( $user_id );
		if ( ! $courses ) {
			return array();
		}
		if ( $course_id ) {
			if ( ! in_array( (int) $course_id, array_map( 'intval', $courses ), true ) ) {
				return array();
			}
			$courses = array( (int) $course_id );
		}
		$q = new WP_Query( array(
			'post_type'      => 'szp_session',
			'post_status'    => 'publish',
			'posts_per_page' => $count > 0 ? $count : -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_szp_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array( 'key' => '_szp_course_id', 'value' => $courses, 'compare' => 'IN' ),
			),
		) );
		return $q->posts;
	}

	/* ---------------- card builders (shared) ---------------- */

	public static function course_card_html( $cid, $base ) {
		$instr = get_post_meta( $cid, '_szp_instructor', true );
		$thumb = get_the_post_thumbnail_url( $cid, 'medium' );
		$url   = esc_url( add_query_arg( array( 'szp' => 'course', 'id' => (int) $cid ), $base ) );
		ob_start(); ?>
		<a class="szp-card" href="<?php echo $url; ?>">
			<?php if ( $thumb ) : ?><span class="szp-card-img" style="background-image:url('<?php echo esc_url( $thumb ); ?>')"></span><?php endif; ?>
			<span class="szp-card-body">
				<span class="szp-card-title"><?php echo esc_html( get_the_title( $cid ) ); ?></span>
				<?php if ( $instr ) : ?><span class="szp-card-meta">مدرس: <?php echo esc_html( $instr ); ?></span><?php endif; ?>
				<span class="szp-card-cta">ورود به دوره ‹</span>
			</span>
		</a>
		<?php return ob_get_clean();
	}

	public static function session_card_html( $sid, $base ) {
		$cid   = (int) get_post_meta( $sid, '_szp_course_id', true );
		$ts    = szp_ts_from_datetime( get_post_meta( $sid, '_szp_datetime', true ) );
		$thumb = get_the_post_thumbnail_url( $sid, 'medium' );
		$url   = esc_url( add_query_arg( array( 'szp' => 'session', 'id' => (int) $sid ), $base ) );
		ob_start(); ?>
		<a class="szp-card" href="<?php echo $url; ?>">
			<?php if ( $thumb ) : ?><span class="szp-card-img" style="background-image:url('<?php echo esc_url( $thumb ); ?>')"></span><?php endif; ?>
			<span class="szp-card-body">
				<span class="szp-card-title"><?php echo esc_html( get_the_title( $sid ) ); ?></span>
				<?php if ( $cid ) : ?><span class="szp-card-meta"><?php echo esc_html( get_the_title( $cid ) ); ?></span><?php endif; ?>
				<?php if ( $ts ) : ?><span class="szp-card-meta szp-card-date"><?php echo esc_html( szp_format_datetime( $ts ) ); ?></span><?php endif; ?>
				<span class="szp-card-cta">مشاهده جلسه ‹</span>
			</span>
		</a>
		<?php return ob_get_clean();
	}

	protected static function wrap_collection( $cards, $view ) {
		if ( $view === 'carousel' ) {
			return '<div class="szp"><div class="szp-carousel">'
				. '<button type="button" class="szp-car-btn szp-car-prev" aria-label="قبلی">›</button>'
				. '<div class="szp-car-track">' . $cards . '</div>'
				. '<button type="button" class="szp-car-btn szp-car-next" aria-label="بعدی">‹</button>'
				. '</div></div>';
		}
		return '<div class="szp"><div class="szp-grid">' . $cards . '</div></div>';
	}

	protected static function login_box() {
		return '<div class="szp"><div class="szp-empty">برای مشاهده ابتدا وارد شوید.</div></div>';
	}

	/* ---------------- collection shortcodes ---------------- */

	public static function sc_courses( $atts ) {
		$a = shortcode_atts( array( 'view' => 'grid', 'count' => '-1', 'panel' => '' ), $atts, 'sazan_courses' );
		if ( ! is_user_logged_in() ) {
			return self::login_box();
		}
		self::enqueue();
		return self::render_courses_collection( get_current_user_id(), $a['view'], (int) $a['count'], $a['panel'] );
	}

	public static function sc_sessions( $atts ) {
		$a = shortcode_atts( array( 'view' => 'grid', 'count' => '12', 'course' => '0', 'panel' => '' ), $atts, 'sazan_sessions' );
		if ( ! is_user_logged_in() ) {
			return self::login_box();
		}
		self::enqueue();
		return self::render_sessions_collection( get_current_user_id(), $a['view'], (int) $a['count'], (int) $a['course'], $a['panel'] );
	}

	/** Build the courses grid/carousel. Assumes caller handled login + enqueue. */
	public static function render_courses_collection( $user_id, $view = 'grid', $count = -1, $panel = '' ) {
		$ids = self::user_courses( $user_id );
		if ( $count > 0 ) {
			$ids = array_slice( $ids, 0, $count );
		}
		if ( ! $ids ) {
			return '<div class="szp"><div class="szp-empty">دوره‌ای برای نمایش نیست.</div></div>';
		}
		$base  = self::panel_base( $panel );
		$cards = '';
		foreach ( $ids as $cid ) {
			$cards .= self::course_card_html( $cid, $base );
		}
		return self::wrap_collection( $cards, $view === 'carousel' ? 'carousel' : 'grid' );
	}

	/** Build the sessions grid/carousel. Assumes caller handled login + enqueue. */
	public static function render_sessions_collection( $user_id, $view = 'grid', $count = 12, $course = 0, $panel = '' ) {
		$ids = self::user_sessions( $user_id, (int) $course, (int) $count );
		if ( ! $ids ) {
			return '<div class="szp"><div class="szp-empty">جلسه‌ای برای نمایش نیست.</div></div>';
		}
		$base  = self::panel_base( $panel );
		$cards = '';
		foreach ( $ids as $sid ) {
			$cards .= self::session_card_html( $sid, $base );
		}
		return self::wrap_collection( $cards, $view === 'carousel' ? 'carousel' : 'grid' );
	}
}
