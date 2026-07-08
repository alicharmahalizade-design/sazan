<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Base widget. Subclasses set $ctx ('course' | 'session' | 'none') and implement output().
 * For course/session widgets, the target id comes from a control or the panel URL (?id=).
 */
abstract class SZP_Widget_Base extends \Elementor\Widget_Base {

	protected $ctx = 'course';

	public function get_categories() {
		return array( 'sazan-panel' );
	}
	public function get_icon() {
		return 'eicon-document-file';
	}
	public function get_keywords() {
		return array( 'sazan', 'panel', 'course', 'session', 'سازان', 'دوره', 'جلسه' );
	}

	abstract protected function output( $id, $uid );

	protected function register_controls() {
		if ( $this->ctx === 'none' ) {
			return;
		}
		$this->start_controls_section( 'szp_src', array( 'label' => 'منبع' ) );
		$this->add_control( 'source', array(
			'label'   => 'تعیین شناسه',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'url',
			'options' => array(
				'url'    => 'از آدرس صفحه پنل (خودکار)',
				'manual' => 'انتخاب دستی',
			),
		) );
		$this->add_control( 'obj_id', array(
			'label'       => ( $this->ctx === 'session' ) ? 'شناسه جلسه' : 'شناسه دوره',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'condition'   => array( 'source' => 'manual' ),
			'description' => 'شناسه (ID) را از لیست دوره‌ها/جلسات در پیشخوان بردارید.',
		) );
		$this->end_controls_section();
	}

	protected function resolve_id() {
		$s = $this->get_settings_for_display();
		if ( isset( $s['source'] ) && $s['source'] === 'manual' ) {
			return absint( $s['obj_id'] ?? 0 );
		}
		return isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	}

	protected function empty_box( $msg ) {
		echo '<div class="szp"><div class="szp-empty">' . esc_html( $msg ) . '</div></div>';
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_script( 'szp-front' );

		if ( ! is_user_logged_in() ) {
			$this->empty_box( 'برای مشاهده ابتدا وارد شوید.' );
			return;
		}
		$uid = get_current_user_id();

		if ( $this->ctx === 'none' ) {
			echo $this->output( 0, $uid ); // phpcs:ignore WordPress.Security.EscapeOutput
			return;
		}

		$id = $this->resolve_id();
		if ( ! $id ) {
			$this->empty_box( 'شناسه مشخص نشده است (در تنظیمات ویجت انتخاب کنید یا داخل صفحه‌ی پنل استفاده کنید).' );
			return;
		}
		$cid = ( $this->ctx === 'course' ) ? $id : (int) get_post_meta( $id, '_szp_course_id', true );
		if ( ! $cid || ! SZP_Access::user_can_course( $uid, $cid ) ) {
			$this->empty_box( 'به این محتوا دسترسی ندارید.' );
			return;
		}
		echo $this->output( $id, $uid ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/* ==================== COURSE SECTION WIDGETS ==================== */

class SZP_W_Course_Identity extends SZP_Widget_Base {
	protected $ctx = 'course';
	public function get_name() { return 'szp_course_identity'; }
	public function get_title() { return 'سازان: شناسنامه دوره'; }
	public function get_icon() { return 'eicon-info-circle-o'; }
	protected function output( $id, $uid ) { return SZP_Render::course_identity( $id ); }
}

class SZP_W_Course_Schedule extends SZP_Widget_Base {
	protected $ctx = 'course';
	public function get_name() { return 'szp_course_schedule'; }
	public function get_title() { return 'سازان: زمان‌بندی جلسات + تایمر'; }
	public function get_icon() { return 'eicon-countdown'; }
	protected function output( $id, $uid ) { return SZP_Render::course_schedule( $id ); }
}

class SZP_W_Course_Announcements extends SZP_Widget_Base {
	protected $ctx = 'course';
	public function get_name() { return 'szp_course_announcements'; }
	public function get_title() { return 'سازان: تابلو اعلانات'; }
	public function get_icon() { return 'eicon-bullhorn'; }
	protected function output( $id, $uid ) { return SZP_Render::course_announcements( $id ); }
}

class SZP_W_Course_Files extends SZP_Widget_Base {
	protected $ctx = 'course';
	public function get_name() { return 'szp_course_files'; }
	public function get_title() { return 'سازان: فولدر فایل‌ها و منابع'; }
	public function get_icon() { return 'eicon-folder'; }
	protected function output( $id, $uid ) { return SZP_Render::course_files( $id ); }
}

class SZP_W_Course_Workbench extends SZP_Widget_Base {
	protected $ctx = 'course';
	public function get_name() { return 'szp_course_workbench'; }
	public function get_title() { return 'سازان: میز کار تمرین‌ها'; }
	public function get_icon() { return 'eicon-table'; }
	protected function output( $id, $uid ) { return SZP_Render::course_workbench( $id, $uid ); }
}

class SZP_W_Course_Survey extends SZP_Widget_Base {
	protected $ctx = 'course';
	public function get_name() { return 'szp_course_survey'; }
	public function get_title() { return 'سازان: نظرسنجی دوره'; }
	public function get_icon() { return 'eicon-form-horizontal'; }
	protected function output( $id, $uid ) { return SZP_Render::course_survey( $id, $uid ); }
}

class SZP_W_Group extends SZP_Widget_Base {
	protected $ctx = 'none'; // user-scoped, no course/session needed
	public function get_name() { return 'szp_group'; }
	public function get_title() { return 'سازان: پنل گروه من'; }
	public function get_icon() { return 'eicon-users'; }
	protected function output( $id, $uid ) { return SZP_Render::course_group( $uid ); }
}

/* ==================== SESSION SECTION WIDGETS ==================== */

class SZP_W_Session_Identity extends SZP_Widget_Base {
	protected $ctx = 'session';
	public function get_name() { return 'szp_session_identity'; }
	public function get_title() { return 'سازان: شناسنامه جلسه'; }
	public function get_icon() { return 'eicon-info-circle-o'; }
	protected function output( $id, $uid ) { return SZP_Render::session_identity( $id ); }
}

class SZP_W_Session_Countdown extends SZP_Widget_Base {
	protected $ctx = 'session';
	public function get_name() { return 'szp_session_countdown'; }
	public function get_title() { return 'سازان: تایمر معکوس جلسه'; }
	public function get_icon() { return 'eicon-countdown'; }
	protected function output( $id, $uid ) { return SZP_Render::session_countdown( $id ); }
}

class SZP_W_Session_Pack extends SZP_Widget_Base {
	protected $ctx = 'session';
	public function get_name() { return 'szp_session_pack'; }
	public function get_title() { return 'سازان: توشه دیجیتال جلسه'; }
	public function get_icon() { return 'eicon-download-bold'; }
	protected function output( $id, $uid ) { return SZP_Render::session_pack( $id ); }
}

class SZP_W_Session_Task extends SZP_Widget_Base {
	protected $ctx = 'session';
	public function get_name() { return 'szp_session_task'; }
	public function get_title() { return 'سازان: تکلیف جلسه (ارسال پاسخ)'; }
	public function get_icon() { return 'eicon-edit'; }
	protected function output( $id, $uid ) { return SZP_Render::session_task( $id, $uid ); }
}

class SZP_W_Session_Checklist extends SZP_Widget_Base {
	protected $ctx = 'session';
	public function get_name() { return 'szp_session_checklist'; }
	public function get_title() { return 'سازان: چک‌لیست جلسه'; }
	public function get_icon() { return 'eicon-check-circle'; }
	protected function output( $id, $uid ) { return SZP_Render::session_checklist( $id, $uid ); }
}

class SZP_W_Session_Survey extends SZP_Widget_Base {
	protected $ctx = 'session';
	public function get_name() { return 'szp_session_survey'; }
	public function get_title() { return 'سازان: نظرسنجی جلسه'; }
	public function get_icon() { return 'eicon-form-horizontal'; }
	protected function output( $id, $uid ) { return SZP_Render::session_survey( $id, $uid ); }
}

/* ==================== COLLECTION WIDGETS ==================== */

class SZP_W_Courses extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_courses'; }
	public function get_title() { return 'سازان: دوره‌های من (شبکه/کاروسل)'; }
	public function get_icon() { return 'eicon-posts-grid'; }

	protected function register_controls() {
		$this->start_controls_section( 'szp_c', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'view', array(
			'label'   => 'حالت نمایش',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => array( 'grid' => 'شبکه‌ای', 'carousel' => 'کاروسل' ),
		) );
		$this->add_control( 'count', array(
			'label'   => 'حداکثر تعداد (۰ = همه)',
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 0,
		) );
		$this->add_control( 'panel', array(
			'label'       => 'شناسه برگه پنل (اختیاری)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'description' => 'اگر این ویجت خارج از تب پنل است، شناسه برگه‌ای که [sazan_panel] دارد را بدهید تا کارت‌ها به آن لینک شوند.',
		) );
		$this->end_controls_section();
	}

	protected function output( $id, $uid ) {
		$s = $this->get_settings_for_display();
		return SZP_Frontend::render_courses_collection( $uid, $s['view'] ?? 'grid', (int) ( $s['count'] ?? 0 ), $s['panel'] ?? '' );
	}
}

class SZP_W_Sessions extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_sessions'; }
	public function get_title() { return 'سازان: جلسات من (شبکه/کاروسل)'; }
	public function get_icon() { return 'eicon-slides'; }

	protected function register_controls() {
		$this->start_controls_section( 'szp_s', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'view', array(
			'label'   => 'حالت نمایش',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'grid',
			'options' => array( 'grid' => 'شبکه‌ای', 'carousel' => 'کاروسل' ),
		) );
		$this->add_control( 'count', array(
			'label'   => 'حداکثر تعداد',
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 12,
		) );
		$this->add_control( 'course', array(
			'label'       => 'فقط جلسات یک دوره (شناسه دوره، اختیاری)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
		) );
		$this->add_control( 'panel', array(
			'label' => 'شناسه برگه پنل (اختیاری)',
			'type'  => \Elementor\Controls_Manager::NUMBER,
		) );
		$this->end_controls_section();
	}

	protected function output( $id, $uid ) {
		$s = $this->get_settings_for_display();
		return SZP_Frontend::render_sessions_collection( $uid, $s['view'] ?? 'grid', (int) ( $s['count'] ?? 12 ), (int) ( $s['course'] ?? 0 ), $s['panel'] ?? '' );
	}
}

class SZP_W_Panel extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_panel'; }
	public function get_title() { return 'سازان: پنل کامل'; }
	public function get_icon() { return 'eicon-dashboard'; }
	protected function output( $id, $uid ) { return SZP_Frontend::shortcode( array() ); }
}

class SZP_W_Coaching extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_coaching'; }
	public function get_title() { return 'سازان: کوچینگ کسب‌وکار'; }
	public function get_icon() { return 'eicon-line-chart'; }

	protected function register_controls() {
		$this->start_controls_section( 'szp_co', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'course', array(
			'label'       => 'شناسه دوره (اختیاری)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'description' => 'برای محدود کردن به یک دوره؛ خالی = تشخیص خودکار.',
		) );
		$this->add_control( 'panel', array(
			'label' => 'شناسه برگه پنل (اختیاری)',
			'type'  => \Elementor\Controls_Manager::NUMBER,
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-coach' );
		wp_enqueue_script( 'szp-coach' );
		if ( ! is_user_logged_in() ) {
			$this->empty_box( 'برای مشاهده ابتدا وارد شوید.' );
			return;
		}
		$s      = $this->get_settings_for_display();
		$course = isset( $_GET['cc'] ) ? absint( $_GET['cc'] ) : (int) ( $s['course'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		echo SZP_Coach_Render::render( get_current_user_id(), $course, $s['panel'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	protected function output( $id, $uid ) { return ''; }
}

class SZP_W_Service_Canvas extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_service_canvas'; }
	public function get_title() { return 'سازان: بوم طراحی خدمت'; }
	public function get_icon() { return 'eicon-form-horizontal'; }
	public function get_keywords() { return array( 'sazan', 'canvas', 'service', 'بوم', 'خدمت', 'سازان' ); }

	protected function register_controls() {
		$this->start_controls_section( 'szp_cv', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array(
			'label'   => 'عنوان بوم',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'شناسایی روش‌های طراحی خدمت',
		) );
		$this->add_control( 'key', array(
			'label'       => 'کلید بوم',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'service_design',
			'description' => 'برای داشتن چند بوم مجزا روی سایت، کلید یکتا بدهید (فقط حروف انگلیسی/عدد).',
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-canvas' );
		wp_enqueue_script( 'szp-canvas' );
		$s = $this->get_settings_for_display();
		echo SZP_Canvas::render( array( 'key' => $s['key'] ?? '', 'title' => $s['title'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	protected function output( $id, $uid ) { return ''; }
}

/**
 * گالری شرکت‌کنندگان بوم. زیرکلاس‌ها فقط $view را تعیین می‌کنند.
 */
abstract class SZP_W_Canvas_Gallery extends SZP_Widget_Base {
	protected $ctx  = 'none';
	protected $view = 'grid';

	public function get_keywords() { return array( 'sazan', 'canvas', 'gallery', 'بوم', 'گالری', 'سازان' ); }

	protected function register_controls() {
		$this->start_controls_section( 'szp_cvg', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array(
			'label'   => 'عنوان (اختیاری)',
			'type'    => \Elementor\Controls_Manager::TEXT,
		) );
		$this->add_control( 'key', array(
			'label'       => 'کلید بوم',
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'service_design',
			'description' => 'همان کلیدی که در ویجت/شورت‌کد بوم استفاده کرده‌اید.',
		) );
		$this->add_control( 'count', array(
			'label'   => 'حداکثر تعداد (۰ = همه)',
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 0,
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_script( 'szp-front' );
		wp_enqueue_style( 'szp-canvas' );
		$s = $this->get_settings_for_display();
		echo SZP_Canvas::gallery( array( // phpcs:ignore WordPress.Security.EscapeOutput
			'key'   => $s['key'] ?? '',
			'view'  => $this->view,
			'count' => (int) ( $s['count'] ?? 0 ),
			'title' => $s['title'] ?? '',
		) );
	}

	protected function output( $id, $uid ) { return ''; }
}

class SZP_W_My_Eval extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_my_eval'; }
	public function get_title() { return 'سازان: ارزیابی من'; }
	public function get_icon() { return 'eicon-number-field'; }
	public function get_keywords() { return array( 'sazan', 'eval', 'target', 'ارزیابی', 'تارگت', 'سازان' ); }

	protected function register_controls() {
		$this->start_controls_section( 'szp_ev', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array(
			'label'   => 'عنوان',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'ارزیابی من',
		) );
		$this->add_control( 'currency', array(
			'label'   => 'واحد پول',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'تومان',
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-eval' );
		wp_enqueue_script( 'szp-eval' );
		if ( ! is_user_logged_in() ) {
			$this->empty_box( 'برای مشاهده «ارزیابی من» ابتدا وارد شوید.' );
			return;
		}
		$s = $this->get_settings_for_display();
		echo SZP_Eval::render( array( 'title' => $s['title'] ?? '', 'currency' => $s['currency'] ?? 'تومان' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	protected function output( $id, $uid ) { return ''; }
}

class SZP_W_Eval_Board extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_eval_board'; }
	public function get_title() { return 'سازان: تابلوی ارزیابی (همه افراد)'; }
	public function get_icon() { return 'eicon-gallery-grid'; }
	public function get_keywords() { return array( 'sazan', 'eval', 'board', 'ارزیابی', 'تابلو', 'سازان' ); }

	protected function register_controls() {
		$this->start_controls_section( 'szp_evb', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array(
			'label'   => 'عنوان',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'تابلوی ارزیابی',
		) );
		$this->add_control( 'currency', array(
			'label'   => 'واحد پول',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'تومان',
		) );
		$this->add_control( 'group', array(
			'label'       => 'فقط یک گروه (شناسه گروه، اختیاری)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'description' => 'برای محدود کردن تابلو به اعضای یک گروه؛ خالی = همه.',
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-eval' );
		$s = $this->get_settings_for_display();
		echo SZP_Eval::board( array( // phpcs:ignore WordPress.Security.EscapeOutput
			'title'    => $s['title'] ?? '',
			'currency' => $s['currency'] ?? '',
			'group'    => (int) ( $s['group'] ?? 0 ),
		) );
	}

	protected function output( $id, $uid ) { return ''; }
}

class SZP_W_Eval_Ledger extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_eval_ledger'; }
	public function get_title() { return 'سازان: دفتر ارزیابی (همه تارگت‌ها و نتایج)'; }
	public function get_icon() { return 'eicon-table-of-contents'; }
	public function get_keywords() { return array( 'sazan', 'eval', 'ledger', 'ارزیابی', 'دفتر', 'تارگت', 'سازان' ); }

	protected function register_controls() {
		$this->start_controls_section( 'szp_evl', array( 'label' => 'تنظیمات' ) );
		$this->add_control( 'title', array(
			'label'   => 'عنوان',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'دفتر ارزیابی — همه‌ی تارگت‌ها و نتایج',
		) );
		$this->add_control( 'currency', array(
			'label'   => 'واحد پول',
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'تومان',
		) );
		$this->add_control( 'group', array(
			'label'       => 'فقط یک گروه (شناسه گروه، اختیاری)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'description' => 'برای محدود کردن به اعضای یک گروه؛ خالی = همه.',
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-front' );
		wp_enqueue_style( 'szp-eval' );
		$s = $this->get_settings_for_display();
		echo SZP_Eval::board_full( array( // phpcs:ignore WordPress.Security.EscapeOutput
			'title'    => $s['title'] ?? '',
			'currency' => $s['currency'] ?? '',
			'group'    => (int) ( $s['group'] ?? 0 ),
		) );
	}

	protected function output( $id, $uid ) { return ''; }
}

class SZP_W_Canvas_Carousel extends SZP_W_Canvas_Gallery {
	protected $view = 'carousel';
	public function get_name() { return 'szp_canvas_carousel'; }
	public function get_title() { return 'سازان: گالری بوم (کاروسل)'; }
	public function get_icon() { return 'eicon-slider-push'; }
}

class SZP_W_Canvas_Grid extends SZP_W_Canvas_Gallery {
	protected $view = 'grid';
	public function get_name() { return 'szp_canvas_grid'; }
	public function get_title() { return 'سازان: گالری بوم (شبکه‌ای)'; }
	public function get_icon() { return 'eicon-gallery-grid'; }
}

class SZP_W_Courses_Slider extends SZP_Widget_Base {
	protected $ctx = 'none';
	public function get_name() { return 'szp_courses_slider'; }
	public function get_title() { return 'سازان: اسلایدر دوره‌ها'; }
	public function get_icon() { return 'eicon-slider-push'; }
	public function get_keywords() { return array( 'sazan', 'slider', 'courses', 'hero', 'اسلایدر', 'دوره', 'سازان' ); }

	protected function register_controls() {
		$variants = class_exists( 'SZP_Courses_Slider' ) ? SZP_Courses_Slider::variants() : array();
		$default  = class_exists( 'SZP_Courses_Slider' ) ? SZP_Courses_Slider::default_design() : '6a';

		/* ---- طرح ---- */
		$this->start_controls_section( 'szp_cs_design', array( 'label' => 'طرح' ) );
		$this->add_control( 'design', array(
			'label'   => 'طرح اسلایدر',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => $default,
			'options' => $variants,
		) );
		$this->add_control( 'show_switcher', array(
			'label'        => 'نوار انتخاب طرح برای بازدیدکننده',
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => '',
			'description'  => 'اگر فعال باشد، بازدیدکننده می‌تواند بین طرح‌ها جابه‌جا شود.',
		) );
		$this->add_control( 'designs', array(
			'label'       => 'طرح‌های موجود در نوار (خالی = همه)',
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $variants,
			'condition'   => array( 'show_switcher' => 'yes' ),
		) );
		$this->add_control( 'title', array(
			'label' => 'عنوان بالای اسلایدر (اختیاری)',
			'type'  => \Elementor\Controls_Manager::TEXT,
		) );
		$this->end_controls_section();

		/* ---- محتوا ---- */
		$this->start_controls_section( 'szp_cs_content', array( 'label' => 'محتوا' ) );
		$this->add_control( 'source', array(
			'label'   => 'منبع اسلایدها',
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => array(
				'auto'   => 'خودکار از دوره‌ها',
				'manual' => 'دستی',
				'both'   => 'هر دو (دوره‌ها + دستی)',
			),
		) );
		$this->add_control( 'count', array(
			'label'       => 'حداکثر تعداد اسلاید (۰ = همه)',
			'type'        => \Elementor\Controls_Manager::NUMBER,
			'default'     => 0,
		) );

		$rep = new \Elementor\Repeater();
		$rep->add_control( 'name', array( 'label' => 'عنوان دوره', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$rep->add_control( 'desc', array( 'label' => 'توضیح کوتاه', 'type' => \Elementor\Controls_Manager::TEXTAREA ) );
		$rep->add_control( 'date', array( 'label' => 'تاریخ شروع (متن)', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '' ) );
		$rep->add_control( 'ghost', array( 'label' => 'کلمه‌ی سایه‌ای (اختیاری)', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$rep->add_control( 'url', array( 'label' => 'لینک ثبت‌نام', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'manual', array(
			'label'       => 'اسلایدهای دستی',
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ name }}}',
			'condition'   => array( 'source' => array( 'manual', 'both' ) ),
			'default'     => array(),
		) );
		$this->end_controls_section();

		/* ---- رفتار ---- */
		$this->start_controls_section( 'szp_cs_behavior', array( 'label' => 'رفتار و نمایش' ) );
		$this->add_control( 'autoplay', array(
			'label'   => 'پخش خودکار',
			'type'    => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );
		$this->add_control( 'interval', array(
			'label'     => 'فاصله‌ی زمانی (ثانیه)',
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 5,
			'min'       => 2,
			'max'       => 12,
			'condition' => array( 'autoplay' => 'yes' ),
		) );
		$this->add_control( 'archer', array(
			'label'   => 'نمایش تصویر کماندار',
			'type'    => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
		) );
		$this->add_control( 'archer_img', array(
			'label'     => 'جایگزینی تصویر کماندار (اختیاری)',
			'type'      => \Elementor\Controls_Manager::MEDIA,
			'condition' => array( 'archer' => 'yes' ),
		) );
		$this->end_controls_section();
	}

	public function render() {
		wp_enqueue_style( 'szp-courses-slider' );
		wp_enqueue_script( 'szp-courses-slider' );
		if ( ! class_exists( 'SZP_Courses_Slider' ) ) {
			return;
		}
		$s = $this->get_settings_for_display();

		$manual = array();
		if ( ! empty( $s['manual'] ) && is_array( $s['manual'] ) ) {
			foreach ( $s['manual'] as $row ) {
				$manual[] = array(
					'name'  => $row['name'] ?? '',
					'desc'  => $row['desc'] ?? '',
					'date'  => $row['date'] ?? '',
					'ghost' => $row['ghost'] ?? '',
					'url'   => isset( $row['url']['url'] ) ? $row['url']['url'] : '',
				);
			}
		}

		echo SZP_Courses_Slider::render( array( // phpcs:ignore WordPress.Security.EscapeOutput
			'design'        => $s['design'] ?? '',
			'source'        => $s['source'] ?? 'auto',
			'count'         => (int) ( $s['count'] ?? 0 ),
			'autoplay'      => ( ( $s['autoplay'] ?? 'yes' ) === 'yes' ),
			'interval'      => (int) ( $s['interval'] ?? 5 ),
			'archer'        => ( ( $s['archer'] ?? 'yes' ) === 'yes' ),
			'archer_url'    => ( ! empty( $s['archer_img']['url'] ) ) ? $s['archer_img']['url'] : '',
			'manual'        => $manual,
			'show_switcher' => ( ( $s['show_switcher'] ?? '' ) === 'yes' ),
			'designs'       => isset( $s['designs'] ) && is_array( $s['designs'] ) ? $s['designs'] : array(),
			'title'         => $s['title'] ?? '',
		) );
	}

	protected function output( $id, $uid ) { return ''; }
}

/** List of widget class names to register. */
function szp_elementor_widget_list() {
	return array(
		'SZP_W_Panel',
		'SZP_W_Coaching',
		'SZP_W_My_Eval',
		'SZP_W_Eval_Board',
		'SZP_W_Eval_Ledger',
		'SZP_W_Courses_Slider',
		'SZP_W_Service_Canvas',
		'SZP_W_Canvas_Carousel',
		'SZP_W_Canvas_Grid',
		'SZP_W_Courses',
		'SZP_W_Sessions',
		'SZP_W_Course_Identity',
		'SZP_W_Course_Schedule',
		'SZP_W_Course_Announcements',
		'SZP_W_Course_Files',
		'SZP_W_Course_Workbench',
		'SZP_W_Course_Survey',
		'SZP_W_Group',
		'SZP_W_Session_Identity',
		'SZP_W_Session_Countdown',
		'SZP_W_Session_Pack',
		'SZP_W_Session_Task',
		'SZP_W_Session_Checklist',
		'SZP_W_Session_Survey',
	);
}
