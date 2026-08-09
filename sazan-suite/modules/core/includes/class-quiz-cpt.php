<?php
/**
 * پست‌تایپ آزمون (Sazan Quiz) + سازنده‌ی آزمون در پیشخوان.
 *
 * کل پیکربندی آزمون در یک متای JSON ذخیره می‌شود: _sazan_quiz_data
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quiz_CPT {

	const POST_TYPE = 'sazan_quiz';
	const META      = '_sazan_quiz_data';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	public function register_cpt() {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}
		$labels = array(
			'name'          => esc_html__( 'آزمون‌ها', 'sazan-core' ),
			'singular_name' => esc_html__( 'آزمون', 'sazan-core' ),
			'add_new'       => esc_html__( 'افزودن آزمون', 'sazan-core' ),
			'add_new_item'  => esc_html__( 'افزودن آزمون جدید', 'sazan-core' ),
			'edit_item'     => esc_html__( 'ویرایش آزمون', 'sazan-core' ),
			'all_items'     => esc_html__( 'همه آزمون‌ها', 'sazan-core' ),
			'menu_name'     => esc_html__( 'آزمون‌های سازان', 'sazan-core' ),
		);
		register_post_type( self::POST_TYPE, array(
			'labels'      => $labels,
			'public'      => true,
			'has_archive' => false,
			'menu_icon'   => 'dashicons-forms',
			'rewrite'     => array( 'slug' => 'quiz' ),
			'supports'    => array( 'title' ),
			'show_in_rest'=> false,
		) );
	}

	/** ساختار پیش‌فرض یک آزمون. */
	public static function defaults() {
		return array(
			'scoring' => 'sum',          // sum | axis
			'tier_basis' => 'percent',    // percent | raw
			'lead'    => array( 'required' => 1, 'name' => 1, 'mobile' => 1, 'email' => 0, 'company' => 0, 'name_label' => 'نام و نام خانوادگی', 'mobile_label' => 'شماره موبایل', 'email_label' => 'ایمیل', 'company_label' => 'نام کسب‌وکار' ),
			'intro'   => array( 'title' => '', 'desc' => '', 'start_label' => 'شروع آزمون' ),
			'axes'    => array(),        // [ {id,label} ]
			'questions' => array(),      // [ {text,type,axis,options:[{label,score}]} ]
			'tiers'   => array(),        // [ {min,max,title,message,desc,products,cta_label,cta_url} ]
			'result'  => array( 'show_radar' => 1, 'show_pdf' => 1, 'show_products' => 1 ),
			'sms'     => array( 'enabled' => 0, 'template' => 'نتیجه آزمون شما: امتیاز {score} از 100 — سطح: {tier}. {message}' ),
		);
	}

	/** پریست آمادهٔ تست ۱۶ سؤالی اسکن سیستم کسب‌وکار. */
	public static function business_scan_preset() {
		$texts = array(
			'برنامه فروش هفتگی، قابل پیش‌بینی و قابل اندازه‌گیری دارم.', 'نرخ تبدیل فروش ما مشخص و قابل ردیابی است.',
			'تیم فروش من استاندارد پیگیری مشخص دارد.', 'لیدها طبقه‌بندی و مدیریت می‌شوند (سرد/گرم/داغ).',
			'تیم من وظایف را به‌موقع و کامل انجام می‌دهد.', 'رفتار و تعامل اعضا با مشتری حرفه‌ای و یکپارچه است.',
			'هیچ کاری وابسته به فرد نیست و قابل جایگزینی است.', 'سیستم آموزش داخلی فعال و مداوم داریم.',
			'تصمیمات من سریع، شفاف و بدون تعلل گرفته می‌شود.', 'برای توسعه کسب‌وکار، مسیر مشخص ۹۰ روزه دارم.',
			'میزان توقف‌ها، عقب‌انداختن‌ها و معطلی‌ها در کار کم است.', 'ریسک‌های لازم را با تحلیل، نه حدس، مدیریت می‌کنم.',
			'هدف‌های من قابل‌اندازه‌گیری، زمانی و عملیاتی هستند.', 'برای تک‌تک فعالیت‌های کلیدی، شاخص عملکرد داریم.',
			'برنامه تولید محتوا و بازاریابی منسجم و ادامه‌دار داریم.', 'سیستم سنجش و گزارش‌گیری هفتگی داریم.',
		);
		$options = array();
		foreach ( range( 1, 5 ) as $score ) { $options[] = array( 'label' => (string) $score, 'score' => $score ); }
		$questions = array();
		foreach ( $texts as $text ) { $questions[] = array( 'text' => $text, 'type' => 'scale', 'axis' => '', 'options' => $options ); }
		$tiers = array(
			array( 16, 31, 'نیازمند بازنگری فوری', 'در حال حاضر بخش زیادی از فعالیت‌های کسب‌وکار شما به تصمیمات لحظه‌ای، پیگیری‌های پراکنده و حضور مستقیم خودتان وابسته است. ادامه این روند می‌تواند باعث فرسودگی، کاهش فروش و کندشدن رشد مجموعه شود. اولین قدم شما باید ایجاد ساختار، تعریف فرایندها و مشخص‌کردن مسئولیت‌ها باشد.', 'دوره «سیستم‌سازی کسب‌وکار از صفر»', 'شروع بازسازی کسب‌وکار' ),
			array( 32, 47, 'پایه‌های ناپایدار', 'کسب‌وکار شما ظرفیت رشد دارد، اما هنوز فرایندهای فروش، مدیریت تیم و پیگیری عملکرد به یک سیستم منسجم تبدیل نشده‌اند. بعضی بخش‌ها خوب پیش می‌روند، اما نتایج همچنان قابل پیش‌بینی نیستند و احتمالاً بسیاری از کارها به افراد خاص وابسته‌اند.', 'دوره «سیستم‌سازی تیم و فرایندها»', 'ساختن یک سیستم منسجم' ),
			array( 48, 59, 'در مسیر رشد', 'بخش‌هایی از کسب‌وکار شما ساختار مناسبی دارند، اما هنوز بین فروش، تیم، بازاریابی و مدیریت عملکرد هماهنگی کامل ایجاد نشده است. با استانداردسازی فرایندها و تعریف شاخص‌های دقیق، می‌توانید رشد کسب‌وکارتان را سریع‌تر و قابل پیش‌بینی‌تر کنید.', 'دوره «مدیریت عملکرد و فروش سیستماتیک»', 'تبدیل رشد اتفاقی به رشد پایدار' ),
			array( 60, 71, 'کسب‌وکار منظم و آماده توسعه', 'کسب‌وکار شما از ساختار و نظم مناسبی برخوردار است و بسیاری از فرایندهای کلیدی به‌درستی اجرا می‌شوند. چالش اصلی شما دیگر فقط نظم‌دهی نیست؛ اکنون باید روی توسعه بازار، افزایش سهم فروش، تصمیم‌گیری استراتژیک و رشد مقیاس‌پذیر تمرکز کنید.', 'دوره «حکمرانی بر بازار»', 'آماده ورود به مرحله توسعه' ),
			array( 72, 80, 'کسب‌وکار پیشرو', 'شما توانسته‌اید بخش زیادی از کسب‌وکار خود را به‌صورت ساختاریافته، قابل‌اندازه‌گیری و حرفه‌ای مدیریت کنید. در این مرحله، فرصت اصلی شما توسعه بازار، ساخت مزیت رقابتی پایدار، تربیت مدیران میانی و کاهش وابستگی کسب‌وکار به شخص بنیان‌گذار است.', 'دوره پیشرفته «رهبری و مقیاس‌سازی کسب‌وکار» یا برنامه منتورینگ اختصاصی', 'ورود به سطح رهبری بازار' ),
		);
		$tier_data = array();
		foreach ( $tiers as $t ) { $tier_data[] = array( 'min'=>$t[0], 'max'=>$t[1], 'title'=>$t[2], 'message'=>$t[3], 'desc'=>'<p><strong>محصول پیشنهادی:</strong> ' . esc_html( $t[4] ) . '</p>', 'products'=>array(), 'cta_label'=>$t[5], 'cta_url'=>'' ); }
		return array( 'scoring'=>'sum', 'tier_basis'=>'raw', 'lead'=>array( 'required'=>1, 'name'=>1, 'mobile'=>1, 'email'=>0, 'company'=>0, 'name_label'=>'نام و نام خانوادگی', 'mobile_label'=>'تلفن', 'email_label'=>'ایمیل', 'company_label'=>'نام کسب‌وکار' ), 'intro'=>array( 'title'=>'تست ۱۶ سؤالی اسکن سیستم کسب‌وکارتان', 'desc'=>'<p><strong>این نکته را جدی بگیرید که این تست فقط برای تفریح ساخته نشده است.</strong></p><p>به هر عبارت از ۱ تا ۵ امتیاز دهید: ۱ بسیار ضعیف، ۲ ضعیف، ۳ متوسط، ۴ خوب و ۵ عالی.</p>', 'start_label'=>'شروع تست' ), 'axes'=>array(), 'questions'=>$questions, 'tiers'=>$tier_data, 'result'=>array( 'show_radar'=>0, 'show_pdf'=>1, 'show_products'=>0 ), 'sms'=>array( 'enabled'=>0, 'template'=>'نتیجه آزمون شما: امتیاز {score} — سطح: {tier}. {message}' ) );
	}

	/** پریست تشخیص فرصت‌های از دست‌رفته در سیستم فروش. */
	public static function sales_opportunity_preset() {
		$texts = array(
			'هر مرحله از فروش شما تعریف و مستند شده است (فرایند فروش شما روی کاغذ یا نرم‌افزار ثبت شده باشد).',
			'تیم می‌داند در هر مرحله چه کاری انجام دهد (مثلاً پاسخ به اعتراض مشتری، ارسال نمونه، پیگیری بعدی).',
			'مراحل پیشرفت مشتری در قیف فروش مشخص است (از آشنایی تا خرید نهایی).', 'بعد از هر تماس یا ملاقات، اقدامات بعدی ثبت می‌شوند.',
			'بررسی و اصلاح فرایند فروش به‌صورت هفتگی انجام می‌شود.', 'تیم فروش فعال و انگیزه‌مند است.',
			'فروشندگان می‌دانند چگونه مشتری مردد را متقاعد کنند.', 'فروشندگان نقش مشاور رشد مشتری را ایفا می‌کنند.',
			'تیم به‌صورت خودجوش با هم هماهنگ است.', 'رفتار تیم باعث اعتماد و وفاداری مشتری می‌شود.',
			'از ابزار CRM یا مشابه استفاده می‌کنید.', 'داده‌ها و آمار فروش روزانه ثبت می‌شوند.',
			'سیستم گزارش‌گیری نقاط ضعف و فرصت‌ها را نشان می‌دهد.', 'تیم به داده‌ها و گزارش‌ها دسترسی دارد.',
			'ابزارها و قالب‌های فروش همیشه قابل استفاده هستند.', 'بعد از تماس اولیه، مشتری پیگیری می‌شود.',
			'تماس‌ها و ایمیل‌ها ثبت و زمان‌بندی دارند.', 'مشتریان مردد هدفمند دنبال می‌شوند.',
			'روند تبدیل مشتری‌های بالقوه به واقعی پایش می‌شود.', 'علت فرصت‌های از دست‌رفته بررسی می‌شود.',
			'مراحل قیف فروش شما تعریف شده است.', 'مشتریان بالقوه در مسیر قیف مشخص حرکت می‌کنند.',
			'نرخ تبدیل هر مرحله ثبت و تحلیل می‌شود.', 'اصلاحات بر اساس داده‌های قیف فروش انجام می‌شود.',
			'قیف فروش امکان پیش‌بینی درآمد را فراهم می‌کند.', 'اهداف کمی و کیفی ۹۰ روزه مشخص هستند.',
			'برنامه عملیاتی هر هفته تعریف و اجرا می‌شود.', 'فعالیت‌های تیم با پلن رشد هماهنگ است.',
			'شاخص‌های موفقیت قابل اندازه‌گیری هستند.', 'گزارش هفتگی برای بررسی و اصلاح برنامه تهیه می‌شود.',
		);
		$labels = array( 'کاملاً مخالف / ندارم', 'مخالف', 'متوسط', 'موافق', 'کاملاً موافق / کاملاً داریم' );
		$options = array(); foreach ( $labels as $i => $label ) { $options[] = array( 'label' => $label, 'score' => $i + 1 ); }
		$questions = array(); foreach ( $texts as $text ) { $questions[] = array( 'text'=>$text, 'type'=>'scale', 'axis'=>'', 'options'=>$options ); }
		return array(
			'scoring'=>'sum', 'tier_basis'=>'raw',
			'lead'=>array( 'required'=>1, 'name'=>1, 'mobile'=>1, 'email'=>0, 'company'=>1, 'name_label'=>'نام و نام خانوادگی', 'mobile_label'=>'تلفن', 'email_label'=>'ایمیل', 'company_label'=>'حوزه فعالیت' ),
			'intro'=>array( 'title'=>'تشخیص فرصت‌های از دست‌رفته در سیستم فروش شما', 'desc'=>'<p>به هر عبارت بر اساس وضعیت واقعی سیستم فروش خود پاسخ دهید.</p>', 'start_label'=>'شروع ارزیابی' ),
			'axes'=>array(), 'questions'=>$questions, 'tiers'=>array(),
			'result'=>array( 'show_radar'=>0, 'show_pdf'=>1, 'show_products'=>0 ),
			'sms'=>array( 'enabled'=>0, 'template'=>'نتیجه ارزیابی سیستم فروش شما: امتیاز {score}. {message}' ),
		);
	}

	public static function get_data( $post_id ) {
		$raw = get_post_meta( $post_id, self::META, true );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : ( is_array( $raw ) ? $raw : array() );
		if ( ! is_array( $data ) ) { $data = array(); }
		return wp_parse_args( $data, self::defaults() );
	}

	public function add_meta_box() {
		add_meta_box( 'sazan_quiz_builder', esc_html__( 'سازنده‌ی آزمون', 'sazan-core' ), array( $this, 'render_box' ), self::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'sazan_quiz_embed', esc_html__( 'نمایش / شورت‌کد', 'sazan-core' ), array( $this, 'render_embed' ), self::POST_TYPE, 'side' );
	}

	public function render_embed( $post ) {
		$code = '[sazan_quiz id="' . (int) $post->ID . '"]';
		echo '<p>' . esc_html__( 'این شورت‌کد را در هر صفحه قرار بده یا از ویجت «آزمون سازان» در المنتور استفاده کن:', 'sazan-core' ) . '</p>';
		echo '<input type="text" readonly onclick="this.select()" style="width:100%;direction:ltr;text-align:center" value="' . esc_attr( $code ) . '">';
	}

	public function render_box( $post ) {
		wp_nonce_field( 'sazan_quiz_meta', 'sazan_quiz_nonce' );
		$data = self::get_data( $post->ID );
		echo '<div id="sazan-quiz-app"></div>';
		echo '<textarea id="sazan-quiz-data" name="' . esc_attr( self::META ) . '" hidden>' . esc_textarea( wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ) . '</textarea>';
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['sazan_quiz_nonce'] ) || ! wp_verify_nonce( $_POST['sazan_quiz_nonce'], 'sazan_quiz_meta' ) ) { return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

		$raw  = isset( $_POST[ self::META ] ) ? wp_unslash( $_POST[ self::META ] ) : '';
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) { return; }

		$clean = $this->sanitize_data( $data );
		update_post_meta( $post_id, self::META, wp_json_encode( $clean, JSON_UNESCAPED_UNICODE ) );
	}

	/** پاکسازی کامل ساختار آزمون. */
	private function sanitize_data( $d ) {
		$out = self::defaults();
		$out['scoring'] = in_array( ( $d['scoring'] ?? 'sum' ), array( 'sum', 'axis' ), true ) ? $d['scoring'] : 'sum';
		$out['tier_basis'] = in_array( ( $d['tier_basis'] ?? 'percent' ), array( 'percent', 'raw' ), true ) ? $d['tier_basis'] : 'percent';

		foreach ( array( 'required', 'name', 'mobile', 'email', 'company' ) as $k ) {
			$out['lead'][ $k ] = empty( $d['lead'][ $k ] ) ? 0 : 1;
		}
		foreach ( array( 'name_label', 'mobile_label', 'email_label', 'company_label' ) as $k ) {
			$out['lead'][ $k ] = sanitize_text_field( $d['lead'][ $k ] ?? self::defaults()['lead'][ $k ] );
		}
		$out['intro']['title']       = sanitize_text_field( $d['intro']['title'] ?? '' );
		$out['intro']['desc']        = wp_kses_post( $d['intro']['desc'] ?? '' );
		$out['intro']['start_label'] = sanitize_text_field( $d['intro']['start_label'] ?? 'شروع آزمون' );

		$out['axes'] = array();
		foreach ( (array) ( $d['axes'] ?? array() ) as $a ) {
			$label = sanitize_text_field( $a['label'] ?? '' );
			if ( '' === $label ) { continue; }
			$out['axes'][] = array(
				'id'    => sanitize_key( $a['id'] ?? ( 'ax' . substr( md5( $label . wp_rand() ), 0, 6 ) ) ),
				'label' => $label,
			);
		}

		$out['questions'] = array();
		foreach ( (array) ( $d['questions'] ?? array() ) as $q ) {
			$text = sanitize_text_field( $q['text'] ?? '' );
			if ( '' === $text ) { continue; }
			$type = in_array( ( $q['type'] ?? 'single' ), array( 'single', 'multi', 'scale' ), true ) ? $q['type'] : 'single';
			$opts = array();
			foreach ( (array) ( $q['options'] ?? array() ) as $o ) {
				$ol = sanitize_text_field( $o['label'] ?? '' );
				if ( '' === $ol ) { continue; }
				$opts[] = array( 'label' => $ol, 'score' => floatval( $o['score'] ?? 0 ) );
			}
			$out['questions'][] = array(
				'text'    => $text,
				'type'    => $type,
				'axis'    => sanitize_key( $q['axis'] ?? '' ),
				'options' => $opts,
			);
		}

		$out['tiers'] = array();
		foreach ( (array) ( $d['tiers'] ?? array() ) as $t ) {
			$product_ids = is_array( $t['products'] ?? null ) ? $t['products'] : explode( ',', (string) ( $t['products'] ?? '' ) );
			$out['tiers'][] = array(
				'min'       => floatval( $t['min'] ?? 0 ),
				'max'       => floatval( $t['max'] ?? 100 ),
				'title'     => sanitize_text_field( $t['title'] ?? '' ),
				'message'   => sanitize_textarea_field( $t['message'] ?? '' ),
				'desc'      => wp_kses_post( $t['desc'] ?? '' ),
				'products'  => array_values( array_filter( array_map( 'absint', $product_ids ) ) ),
				'cta_label' => sanitize_text_field( $t['cta_label'] ?? '' ),
				'cta_url'   => esc_url_raw( $t['cta_url'] ?? '' ),
			);
		}

		$out['result']['show_radar']    = empty( $d['result']['show_radar'] ) ? 0 : 1;
		$out['result']['show_pdf']      = empty( $d['result']['show_pdf'] ) ? 0 : 1;
		$out['result']['show_products'] = empty( $d['result']['show_products'] ) ? 0 : 1;

		$out['sms']['enabled']  = empty( $d['sms']['enabled'] ) ? 0 : 1;
		$out['sms']['template'] = sanitize_textarea_field( $d['sms']['template'] ?? '' );

		return $out;
	}

	public function admin_assets( $hook ) {
		global $post;
		if ( ! $post || self::POST_TYPE !== $post->post_type ) { return; }
		$ver = function ( $rel ) { $p = SAZAN_CORE_PATH . $rel; return file_exists( $p ) ? filemtime( $p ) : SAZAN_CORE_VERSION; };

		wp_enqueue_style( 'sazan-quiz-admin', SAZAN_CORE_URL . 'assets/quiz/quiz-admin.css', array(), $ver( 'assets/quiz/quiz-admin.css' ) );
		wp_enqueue_script( 'sazan-quiz-admin', SAZAN_CORE_URL . 'assets/quiz/quiz-admin.js', array( 'jquery', 'jquery-ui-sortable' ), $ver( 'assets/quiz/quiz-admin.js' ), true );
		wp_localize_script( 'sazan-quiz-admin', 'SazanQuizAdmin', array(
			'presets' => array( 'business_scan' => self::business_scan_preset(), 'sales_opportunity' => self::sales_opportunity_preset() ),
			'i18n' => array(
				'sum' => esc_html__( 'مجموع/درصدی ساده', 'sazan-core' ),
				'axis'=> esc_html__( 'چندمحوری (رادار)', 'sazan-core' ),
			),
		) );
	}
}
