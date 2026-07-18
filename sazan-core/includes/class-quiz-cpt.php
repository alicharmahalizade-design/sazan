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
			'lead'    => array( 'required' => 1, 'name' => 1, 'mobile' => 1, 'email' => 0, 'company' => 0 ),
			'intro'   => array( 'title' => '', 'desc' => '', 'start_label' => 'شروع آزمون' ),
			'axes'    => array(),        // [ {id,label} ]
			'questions' => array(),      // [ {text,type,axis,options:[{label,score}]} ]
			'tiers'   => array(),        // [ {min,max,title,message,desc,products,cta_label,cta_url} ]
			'result'  => array( 'show_radar' => 1, 'show_pdf' => 1, 'show_products' => 1 ),
			'sms'     => array( 'enabled' => 0, 'template' => 'نتیجه آزمون شما: امتیاز {score} از 100 — سطح: {tier}. {message}' ),
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

		foreach ( array( 'required', 'name', 'mobile', 'email', 'company' ) as $k ) {
			$out['lead'][ $k ] = empty( $d['lead'][ $k ] ) ? 0 : 1;
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
			$out['tiers'][] = array(
				'min'       => floatval( $t['min'] ?? 0 ),
				'max'       => floatval( $t['max'] ?? 100 ),
				'title'     => sanitize_text_field( $t['title'] ?? '' ),
				'message'   => sanitize_textarea_field( $t['message'] ?? '' ),
				'desc'      => wp_kses_post( $t['desc'] ?? '' ),
				'products'  => array_filter( array_map( 'absint', explode( ',', (string) ( $t['products'] ?? '' ) ) ) ),
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
			'i18n' => array(
				'sum' => esc_html__( 'مجموع/درصدی ساده', 'sazan-core' ),
				'axis'=> esc_html__( 'چندمحوری (رادار)', 'sazan-core' ),
			),
		) );
	}
}
