<?php
/**
 * موتور آزمون: ساخت جدول لیدها، امتیازدهی، ثبت پاسخ، AJAX، پیامک، رندر فرانت.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quiz_Engine {

	const DB_VERSION = '1.0.0';
	const DB_OPTION  = 'sazan_quiz_db_version';

	private static $instance = null;
	private $enqueued = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'sazan_quiz_submissions';
	}

	private function __construct() {
		add_action( 'init', array( $this, 'maybe_upgrade_db' ) );
		add_shortcode( 'sazan_quiz', array( $this, 'shortcode' ) );
		add_shortcode( 'sazan_quiz_result', array( $this, 'shortcode_result' ) );

		add_action( 'wp_ajax_sazan_quiz_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_sazan_quiz_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_sazan_quiz_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_nopriv_sazan_quiz_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_sazan_quiz_verify', array( $this, 'ajax_verify' ) );
		add_action( 'wp_ajax_nopriv_sazan_quiz_verify', array( $this, 'ajax_verify' ) );
		add_action( 'wp_ajax_sazan_quiz_save_step', array( $this, 'ajax_save_step' ) );
		add_action( 'wp_ajax_nopriv_sazan_quiz_save_step', array( $this, 'ajax_save_step' ) );
	}

	private static function normalize_mobile( $value ) {
		$mobile = preg_replace( '/\D/', '', (string) $value );
		if ( '98' === substr( $mobile, 0, 2 ) && 12 === strlen( $mobile ) ) { $mobile = '0' . substr( $mobile, 2 ); }
		if ( '9' === substr( $mobile, 0, 1 ) && 10 === strlen( $mobile ) ) { $mobile = '0' . $mobile; }
		return $mobile;
	}

	private static function draft_key( $token ) { return 'sazan_quiz_d_' . md5( (string) $token ); }
	private static function otp_key( $challenge ) { return 'sazan_quiz_o_' . md5( (string) $challenge ); }

	public function ajax_start() {
		check_ajax_referer( 'sazan_quiz', 'nonce' );
		$quiz_id = absint( $_POST['quiz_id'] ?? 0 );
		if ( ! $quiz_id || Quiz_CPT::POST_TYPE !== get_post_type( $quiz_id ) ) { wp_send_json_error( array( 'msg' => 'آزمون نامعتبر است.' ) ); }
		$mobile = self::normalize_mobile( wp_unslash( $_POST['mobile'] ?? '' ) );
		if ( ! preg_match( '/^09\d{9}$/', $mobile ) ) { wp_send_json_error( array( 'msg' => 'شماره موبایل معتبر نیست.' ) ); }
		$cooldown_key = 'sazan_quiz_cd_' . md5( $mobile . '|' . $quiz_id );
		if ( get_transient( $cooldown_key ) ) { wp_send_json_error( array( 'msg' => 'لطفاً کمی بعد دوباره تلاش کنید.' ) ); }
		$challenge = wp_generate_password( 32, false );
		$code = (string) wp_rand( 100000, 999999 );
		set_transient( self::otp_key( $challenge ), array( 'quiz_id' => $quiz_id, 'mobile' => $mobile, 'code' => $code, 'tries' => 0 ), 10 * MINUTE_IN_SECONDS );
		set_transient( $cooldown_key, 1, MINUTE_IN_SECONDS );
		$data = Quiz_CPT::get_data( $quiz_id );
		$settings = self::settings();
		if ( empty( $settings['sms_apikey'] ) || empty( $settings['sms_sender'] ) ) { delete_transient( self::otp_key( $challenge ) ); wp_send_json_error( array( 'msg' => 'تنظیمات پیامک آزمون کامل نشده است.' ) ); }
		do_action( 'sazan_quiz_send_otp', $mobile, $code, $quiz_id, $data );
		wp_send_json_success( array( 'challenge' => $challenge, 'mobile' => substr( $mobile, 0, 4 ) . '****' . substr( $mobile, -2 ) ) );
	}

	public function ajax_verify() {
		check_ajax_referer( 'sazan_quiz', 'nonce' );
		$challenge = sanitize_text_field( wp_unslash( $_POST['challenge'] ?? '' ) );
		$code = preg_replace( '/\D/', '', (string) wp_unslash( $_POST['code'] ?? '' ) );
		$otp = get_transient( self::otp_key( $challenge ) );
		if ( ! is_array( $otp ) || (int) ( $otp['tries'] ?? 0 ) >= 5 ) { wp_send_json_error( array( 'msg' => 'کد تأیید منقضی شده است؛ دوباره درخواست کد کنید.' ) ); }
		if ( ! hash_equals( (string) $otp['code'], $code ) ) { $otp['tries'] = (int) ( $otp['tries'] ?? 0 ) + 1; set_transient( self::otp_key( $challenge ), $otp, 10 * MINUTE_IN_SECONDS ); wp_send_json_error( array( 'msg' => 'کد تأیید نادرست است.' ) ); }
		$draft = wp_generate_password( 40, false );
		set_transient( self::draft_key( $draft ), array( 'quiz_id' => absint( $otp['quiz_id'] ), 'mobile' => $otp['mobile'], 'answers' => array(), 'updated' => time() ), 12 * HOUR_IN_SECONDS );
		delete_transient( self::otp_key( $challenge ) );
		wp_send_json_success( array( 'draft' => $draft ) );
	}

	public function ajax_save_step() {
		check_ajax_referer( 'sazan_quiz', 'nonce' );
		$token = sanitize_text_field( wp_unslash( $_POST['draft'] ?? '' ) );
		$draft = get_transient( self::draft_key( $token ) );
		$quiz_id = absint( $_POST['quiz_id'] ?? 0 );
		if ( ! is_array( $draft ) || $quiz_id !== absint( $draft['quiz_id'] ) ) { wp_send_json_error( array( 'msg' => 'نشست آزمون معتبر نیست.' ) ); }
		$step = absint( $_POST['step'] ?? 0 );
		$value = json_decode( wp_unslash( $_POST['value'] ?? 'null' ), true );
		$draft['answers'][ $step ] = is_array( $value ) ? array_map( 'intval', $value ) : absint( $value );
		$draft['updated'] = time();
		set_transient( self::draft_key( $token ), $draft, 12 * HOUR_IN_SECONDS );
		wp_send_json_success( array( 'saved' => true, 'step' => $step ) );
	}

	/* ===================== دیتابیس ===================== */

	public static function install() {
		global $wpdb;
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			quiz_id BIGINT(20) UNSIGNED NOT NULL,
			token VARCHAR(40) NOT NULL,
			lead_name VARCHAR(190) DEFAULT '',
			lead_mobile VARCHAR(20) DEFAULT '',
			lead_email VARCHAR(190) DEFAULT '',
			lead_company VARCHAR(190) DEFAULT '',
			answers LONGTEXT NULL,
			score_total FLOAT DEFAULT 0,
			score_axes LONGTEXT NULL,
			tier_index INT DEFAULT -1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY quiz_id (quiz_id),
			KEY token (token)
		) $charset;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( self::DB_OPTION, self::DB_VERSION );
	}

	public function maybe_upgrade_db() {
		if ( get_option( self::DB_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/* ===================== امتیازدهی ===================== */

	/**
	 * محاسبه‌ی نتیجه از روی پاسخ‌ها.
	 * $answers: آرایه‌ی index سوال => مقدار (int یا آرایه‌ای از int برای multi).
	 */
	public static function score( $data, $answers ) {
		$axis_score = array();
		$axis_max   = array();
		$total      = 0.0;
		$max        = 0.0;

		foreach ( $data['questions'] as $qi => $q ) {
			$opts = $q['options'];
			if ( empty( $opts ) ) { continue; }
			$axis = $q['axis'] ?: '_';

			$q_max = ( 'multi' === $q['type'] )
				? array_sum( array_map( function ( $o ) { return max( 0, floatval( $o['score'] ) ); }, $opts ) )
				: max( array_map( function ( $o ) { return floatval( $o['score'] ); }, $opts ) );

			$sel = isset( $answers[ $qi ] ) ? $answers[ $qi ] : null;
			$got = 0.0;
			if ( 'multi' === $q['type'] && is_array( $sel ) ) {
				foreach ( $sel as $oi ) {
					if ( isset( $opts[ (int) $oi ] ) ) { $got += floatval( $opts[ (int) $oi ]['score'] ); }
				}
			} elseif ( null !== $sel && '' !== $sel && isset( $opts[ (int) $sel ] ) ) {
				$got = floatval( $opts[ (int) $sel ]['score'] );
			}

			$total += $got;
			$max    += $q_max;
			$axis_score[ $axis ] = ( $axis_score[ $axis ] ?? 0 ) + $got;
			$axis_max[ $axis ]   = ( $axis_max[ $axis ] ?? 0 ) + $q_max;
		}

		$percent = $max > 0 ? round( ( $total / $max ) * 100, 1 ) : 0;

		$axes_out = array();
		foreach ( $data['axes'] as $a ) {
			$id  = $a['id'];
			$amx = $axis_max[ $id ] ?? 0;
			$axes_out[] = array(
				'id'      => $id,
				'label'   => $a['label'],
				'percent' => $amx > 0 ? round( ( ( $axis_score[ $id ] ?? 0 ) / $amx ) * 100, 1 ) : 0,
			);
		}

		$tier_value = ( isset( $data['tier_basis'] ) && 'raw' === $data['tier_basis'] ) ? $total : $percent;
		$tier_i = self::resolve_tier( $data['tiers'], $tier_value );

		return array(
			'percent' => $percent,
			'raw'     => $total,
			'max'     => $max,
			'display' => $tier_value,
			'basis'   => ( isset( $data['tier_basis'] ) && 'raw' === $data['tier_basis'] ) ? 'raw' : 'percent',
			'axes'    => $axes_out,
			'tier'    => $tier_i,
		);
	}

	public static function resolve_tier( $tiers, $percent ) {
		foreach ( $tiers as $i => $t ) {
			if ( $percent >= floatval( $t['min'] ) && $percent <= floatval( $t['max'] ) ) {
				return $i;
			}
		}
		return -1;
	}

	/* ===================== ثبت پاسخ (AJAX) ===================== */

	public function ajax_submit() {
		check_ajax_referer( 'sazan_quiz', 'nonce' );

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		if ( ! $quiz_id || get_post_type( $quiz_id ) !== Quiz_CPT::POST_TYPE ) {
			wp_send_json_error( array( 'msg' => 'آزمون نامعتبر است.' ) );
		}
		$data = Quiz_CPT::get_data( $quiz_id );

		$answers_raw = isset( $_POST['answers'] ) ? json_decode( wp_unslash( $_POST['answers'] ), true ) : array();
		$answers     = array();
		if ( is_array( $answers_raw ) ) {
			foreach ( $answers_raw as $k => $v ) {
				$answers[ (int) $k ] = is_array( $v ) ? array_map( 'intval', $v ) : intval( $v );
			}
		}
		$draft_token = sanitize_text_field( wp_unslash( $_POST['draft'] ?? '' ) );
		$draft = $draft_token ? get_transient( self::draft_key( $draft_token ) ) : false;
		if ( ! is_array( $draft ) || absint( $draft['quiz_id'] ) !== $quiz_id ) { wp_send_json_error( array( 'msg' => 'ابتدا شماره موبایل را تأیید کنید.' ) ); }
		if ( is_array( $draft ) && absint( $draft['quiz_id'] ) === $quiz_id ) {
			$answers = (array) $draft['answers'];
		}

		// اعتبارسنجی لید
		$lead = $data['lead'];
		$name = sanitize_text_field( wp_unslash( $_POST['lead_name'] ?? '' ) );
		$mob  = $draft && ! empty( $draft['mobile'] ) ? self::normalize_mobile( $draft['mobile'] ) : self::normalize_mobile( wp_unslash( $_POST['lead_mobile'] ?? '' ) );
		$mail = sanitize_email( wp_unslash( $_POST['lead_email'] ?? '' ) );
		$comp = sanitize_text_field( wp_unslash( $_POST['lead_company'] ?? '' ) );

		if ( ! empty( $lead['required'] ) ) {
			if ( ! empty( $lead['name'] ) && '' === $name ) { wp_send_json_error( array( 'msg' => 'نام را وارد کنید.' ) ); }
			if ( ! empty( $lead['mobile'] ) && ! preg_match( '/^(0?9\d{9}|989\d{9})$/', $mob ) ) {
				wp_send_json_error( array( 'msg' => 'شماره موبایل معتبر نیست.' ) );
			}
			if ( ! empty( $lead['email'] ) && ! is_email( $mail ) ) { wp_send_json_error( array( 'msg' => 'ایمیل معتبر نیست.' ) ); }
		}
		if ( $mob && '09' !== substr( $mob, 0, 2 ) && '9' === substr( $mob, 0, 1 ) ) { $mob = '0' . $mob; }
		if ( '98' === substr( $mob, 0, 2 ) && strlen( $mob ) === 12 ) { $mob = '0' . substr( $mob, 2 ); }

		$result = self::score( $data, $answers );
		$token  = wp_generate_password( 24, false );

		global $wpdb;
		$wpdb->insert( self::table(), array(
			'quiz_id'      => $quiz_id,
			'token'        => $token,
			'lead_name'    => $name,
			'lead_mobile'  => $mob,
			'lead_email'   => $mail,
			'lead_company' => $comp,
			'answers'      => wp_json_encode( $answers, JSON_UNESCAPED_UNICODE ),
			'score_total'  => $result['display'],
			'score_axes'   => wp_json_encode( $result['axes'], JSON_UNESCAPED_UNICODE ),
			'tier_index'   => $result['tier'],
			'created_at'   => current_time( 'mysql' ),
		) );

		// پیامک
		if ( ! empty( $data['sms']['enabled'] ) && $mob ) {
			$tier_title = ( $result['tier'] >= 0 && isset( $data['tiers'][ $result['tier'] ] ) ) ? $data['tiers'][ $result['tier'] ]['title'] : '';
			$ctx = array(
				'quiz_id' => $quiz_id,
				'token'   => $token,
				'percent' => $result['display'],
				'tier'    => $tier_title,
				'name'    => $name,
				'link'    => self::result_link( $token ),
				'message' => $tier_title,
			);
			$this->send_sms( $mob, $data, $ctx );
		}

		// آماده‌سازی خروجی نتیجه
		$tier = ( $result['tier'] >= 0 && isset( $data['tiers'][ $result['tier'] ] ) ) ? $data['tiers'][ $result['tier'] ] : null;
		$products = array();
		if ( $tier && ! empty( $data['result']['show_products'] ) && function_exists( 'wc_get_product' ) ) {
			foreach ( (array) $tier['products'] as $pid ) {
				$p = wc_get_product( $pid );
				if ( ! $p ) { continue; }
				$products[] = array(
					'title' => $p->get_name(),
					'price' => $p->get_price_html(),
					'img'   => wp_get_attachment_image_url( $p->get_image_id(), 'medium' ),
					'url'   => get_permalink( $pid ),
				);
			}
		}

		if ( $draft_token ) { delete_transient( self::draft_key( $draft_token ) ); }
		wp_send_json_success( array(
			'token'    => $token,
			'percent'  => $result['percent'],
			'score'    => $result['display'],
			'max'      => $result['max'],
			'basis'    => $result['basis'],
			'axes'     => $result['axes'],
			'show_radar' => ! empty( $data['result']['show_radar'] ) && 'axis' === $data['scoring'],
			'show_pdf'   => ! empty( $data['result']['show_pdf'] ),
			'tier'     => $tier ? array(
				'title'     => $tier['title'],
				'message'   => $tier['message'],
				'desc'      => wpautop( $tier['desc'] ),
				'cta_label' => $tier['cta_label'],
				'cta_url'   => $tier['cta_url'],
			) : null,
			'products' => $products,
			'lead'     => array( 'name' => $name ),
		) );
	}

	/* ===================== پیامک (فراز اس ام اس / قابل توسعه) ===================== */

	/** تنظیمات سراسری آزمون. */
	public static function settings() {
		return wp_parse_args( (array) get_option( 'sazan_quiz_settings', array() ), array(
			'sms_apikey'  => '',
			'sms_sender'  => '',
			'sms_mode'    => 'pattern', // simple | pattern
			'sms_pattern' => '',
			'sms_otp_pattern' => '',
			'result_page' => 0,
		) );
	}

	/** لینک صفحه‌ی نتیجه بر اساس توکن. */
	public static function result_link( $token ) {
		$s = self::settings();
		$base = $s['result_page'] ? get_permalink( (int) $s['result_page'] ) : home_url( '/' );
		return add_query_arg( 'token', $token, $base );
	}

	private function send_sms( $mobile, $data, $ctx ) {
		$text = strtr( $data['sms']['template'], array(
			'{score}'   => $ctx['percent'],
			'{tier}'    => $ctx['tier'],
			'{message}' => $ctx['message'],
			'{link}'    => $ctx['link'],
			'{name}'    => $ctx['name'],
		) );

		/**
		 * هوک ارسال پیامک. هندلر داخلی فراز در کلاس Quiz_SMS وصل است.
		 * امضا: ( string $mobile, string $text, array $ctx, array $data )
		 */
		do_action( 'sazan_quiz_send_sms', $mobile, $text, $ctx, $data );
	}

	/* ===================== رندر فرانت ===================== */

	public function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'sazan_quiz' );
		return $this->render( absint( $atts['id'] ) );
	}

	/** صفحه‌ی نتیجه بر اساس توکن (پیامک/اشتراک‌گذاری). */
	public function shortcode_result( $atts ) {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		if ( '' === $token ) { return '<div class="sz-quiz-err">توکن نتیجه یافت نشد.</div>'; }

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE token=%s', $token ) );
		if ( ! $row ) { return '<div class="sz-quiz-err">نتیجه‌ای برای این لینک یافت نشد.</div>'; }

		$this->enqueue_front();
		$data = Quiz_CPT::get_data( $row->quiz_id );
		$axes = json_decode( (string) $row->score_axes, true ) ?: array();
		$saved_answers = json_decode( (string) $row->answers, true ) ?: array();
		$saved_score = self::score( $data, $saved_answers );
		$tier = ( $row->tier_index >= 0 && isset( $data['tiers'][ $row->tier_index ] ) ) ? $data['tiers'][ $row->tier_index ] : null;

		$products = array();
		if ( $tier && ! empty( $data['result']['show_products'] ) && function_exists( 'wc_get_product' ) ) {
			foreach ( (array) $tier['products'] as $pid ) {
				$p = wc_get_product( $pid );
				if ( ! $p ) { continue; }
				$products[] = array( 'title' => $p->get_name(), 'price' => $p->get_price_html(), 'img' => wp_get_attachment_image_url( $p->get_image_id(), 'medium' ), 'url' => get_permalink( $pid ) );
			}
		}

		$payload = array(
			'percent'    => $saved_score['percent'],
			'score'      => $saved_score['display'],
			'max'        => $saved_score['max'],
			'basis'      => $saved_score['basis'],
			'axes'       => $axes,
			'show_radar' => ! empty( $data['result']['show_radar'] ) && 'axis' === $data['scoring'],
			'show_pdf'   => ! empty( $data['result']['show_pdf'] ),
			'tier'       => $tier ? array( 'title' => $tier['title'], 'message' => $tier['message'], 'desc' => wpautop( $tier['desc'] ), 'cta_label' => $tier['cta_label'], 'cta_url' => $tier['cta_url'] ) : null,
			'products'   => $products,
		);

		return '<div class="sz-quiz" dir="rtl"><script type="application/json" class="sz-quiz-result-data">'
			. wp_json_encode( $payload, JSON_UNESCAPED_UNICODE )
			. '</script><div class="sz-quiz-stage"></div></div>';
	}

	public function enqueue_front() {
		if ( $this->enqueued ) { return; }
		$this->enqueued = true;
		$ver = function ( $rel ) { $p = SAZAN_CORE_PATH . $rel; return file_exists( $p ) ? filemtime( $p ) : SAZAN_CORE_VERSION; };

		wp_enqueue_style( 'sazan-quiz-front', SAZAN_CORE_URL . 'assets/quiz/quiz-front.css', array(), $ver( 'assets/quiz/quiz-front.css' ) );

		// local-first: اگر فایل کتابخانه داخل افزونه باشد همان، وگرنه CDN.
		$lib = function ( $file, $cdn ) {
			$rel = 'assets/quiz/lib/' . $file;
			if ( file_exists( SAZAN_CORE_PATH . $rel ) ) {
				return array( SAZAN_CORE_URL . $rel, filemtime( SAZAN_CORE_PATH . $rel ) );
			}
			return array( $cdn, null );
		};
		list( $chart_src, $chart_v )   = $lib( 'chart.umd.min.js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js' );
		list( $jspdf_src, $jspdf_v )   = $lib( 'jspdf.umd.min.js', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js' );
		list( $h2c_src, $h2c_v )       = $lib( 'html2canvas.min.js', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js' );

		wp_enqueue_script( 'sazan-chartjs', $chart_src, array(), $chart_v, true );
		wp_enqueue_script( 'sazan-jspdf', $jspdf_src, array(), $jspdf_v, true );
		wp_enqueue_script( 'sazan-html2canvas', $h2c_src, array(), $h2c_v, true );
		wp_enqueue_script( 'sazan-quiz-front', SAZAN_CORE_URL . 'assets/quiz/quiz-front.js', array( 'jquery', 'sazan-chartjs' ), $ver( 'assets/quiz/quiz-front.js' ), true );
		wp_localize_script( 'sazan-quiz-front', 'SazanQuiz', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'sazan_quiz' ),
		) );
	}

	/** پیکربندی امن برای فرانت (بدون امتیازها و محتوای بازه‌ها). */
	private function front_config( $data ) {
		$questions = array();
		foreach ( $data['questions'] as $q ) {
			$questions[] = array(
				'text' => $q['text'],
				'type' => $q['type'],
				'opts' => array_map( function ( $o ) { return $o['label']; }, $q['options'] ),
			);
		}
		return array(
			'lead'  => $data['lead'],
			'intro' => $data['intro'],
			'questions' => $questions,
		);
	}

	public function render( $quiz_id ) {
		if ( ! $quiz_id || get_post_type( $quiz_id ) !== Quiz_CPT::POST_TYPE ) {
			return current_user_can( 'edit_posts' ) ? '<div class="sz-quiz-err">آزمون انتخاب نشده است.</div>' : '';
		}
		$this->enqueue_front();
		$data = Quiz_CPT::get_data( $quiz_id );
		$cfg  = wp_json_encode( $this->front_config( $data ), JSON_UNESCAPED_UNICODE );

		ob_start();
		?>
		<div class="sz-quiz" data-quiz="<?php echo esc_attr( $quiz_id ); ?>" dir="rtl">
			<script type="application/json" class="sz-quiz-cfg"><?php echo $cfg; // محتوای امن، JSON ?></script>
			<div class="sz-quiz-stage"><div class="sz-quiz-loading">در حال بارگذاری…</div></div>
		</div>
		<?php
		return ob_get_clean();
	}
}
