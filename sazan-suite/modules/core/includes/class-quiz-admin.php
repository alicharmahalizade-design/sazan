<?php
/**
 * داشبورد لیدها/نتایج آزمون + اکسپورت CSV.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quiz_Admin {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_sazan_quiz_export', array( $this, 'export_csv' ) );
	}

	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Quiz_CPT::POST_TYPE,
			esc_html__( 'نتایج و لیدها', 'sazan-core' ),
			esc_html__( 'نتایج و لیدها', 'sazan-core' ),
			'manage_options',
			'sazan-quiz-results',
			array( $this, 'render_page' )
		);
	}

	private function quizzes() {
		return get_posts( array( 'post_type' => Quiz_CPT::POST_TYPE, 'numberposts' => -1, 'post_status' => 'any' ) );
	}

	public function render_page() {
		global $wpdb;
		$table   = Quiz_Engine::table();
		$quiz_id = isset( $_GET['quiz'] ) ? absint( $_GET['quiz'] ) : 0;

		echo '<div class="wrap"><h1>' . esc_html__( 'نتایج آزمون‌ها', 'sazan-core' ) . '</h1>';

		// فیلتر آزمون
		echo '<form method="get" style="margin:12px 0"><input type="hidden" name="post_type" value="' . esc_attr( Quiz_CPT::POST_TYPE ) . '"><input type="hidden" name="page" value="sazan-quiz-results">';
		echo '<select name="quiz" onchange="this.form.submit()"><option value="0">' . esc_html__( 'همه آزمون‌ها', 'sazan-core' ) . '</option>';
		foreach ( $this->quizzes() as $q ) {
			printf( '<option value="%d" %s>%s</option>', $q->ID, selected( $quiz_id, $q->ID, false ), esc_html( $q->post_title ) );
		}
		echo '</select> ';
		$export = wp_nonce_url( admin_url( 'admin-post.php?action=sazan_quiz_export&quiz=' . $quiz_id ), 'sazan_quiz_export' );
		echo '<a class="button" href="' . esc_url( $export ) . '">' . esc_html__( 'اکسپورت CSV', 'sazan-core' ) . '</a></form>';

		$where = $quiz_id ? $wpdb->prepare( 'WHERE quiz_id=%d', $quiz_id ) : '';
		$rows  = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY id DESC LIMIT 500" );

		// آمار خلاصه
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table " . ( $quiz_id ? $wpdb->prepare( 'WHERE quiz_id=%d', $quiz_id ) : '' ) );
		$avg   = $wpdb->get_var( "SELECT AVG(score_total) FROM $table " . ( $quiz_id ? $wpdb->prepare( 'WHERE quiz_id=%d', $quiz_id ) : '' ) );
		echo '<p><b>' . esc_html__( 'تعداد شرکت‌کننده:', 'sazan-core' ) . '</b> ' . (int) $count . ' &nbsp;|&nbsp; <b>' . esc_html__( 'میانگین امتیاز:', 'sazan-core' ) . '</b> ' . esc_html( round( (float) $avg, 1 ) ) . '</p>';

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( array( 'تاریخ', 'آزمون', 'نام', 'موبایل', 'ایمیل', 'شرکت', 'امتیاز', 'سطح' ) as $h ) {
			echo '<th>' . esc_html( $h ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="8">' . esc_html__( 'موردی ثبت نشده است.', 'sazan-core' ) . '</td></tr>';
		}
		foreach ( (array) $rows as $r ) {
			$data = Quiz_CPT::get_data( $r->quiz_id );
			$tier = ( $r->tier_index >= 0 && isset( $data['tiers'][ $r->tier_index ] ) ) ? $data['tiers'][ $r->tier_index ]['title'] : '—';
			echo '<tr>';
			echo '<td>' . esc_html( $r->created_at ) . '</td>';
			echo '<td>' . esc_html( get_the_title( $r->quiz_id ) ) . '</td>';
			echo '<td>' . esc_html( $r->lead_name ) . '</td>';
			echo '<td style="direction:ltr">' . esc_html( $r->lead_mobile ) . '</td>';
			echo '<td>' . esc_html( $r->lead_email ) . '</td>';
			echo '<td>' . esc_html( $r->lead_company ) . '</td>';
			echo '<td>' . esc_html( $r->score_total ) . '</td>';
			echo '<td>' . esc_html( $tier ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'sazan_quiz_export' ) ) {
			wp_die( 'دسترسی غیرمجاز.' );
		}
		global $wpdb;
		$table   = Quiz_Engine::table();
		$quiz_id = isset( $_GET['quiz'] ) ? absint( $_GET['quiz'] ) : 0;
		$where   = $quiz_id ? $wpdb->prepare( 'WHERE quiz_id=%d', $quiz_id ) : '';
		$rows    = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY id DESC", ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sazan-quiz-' . gmdate( 'Ymd-His' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // BOM برای فارسی در اکسل
		fputcsv( $out, array( 'تاریخ', 'آزمون', 'نام', 'موبایل', 'ایمیل', 'شرکت', 'امتیاز', 'سطح' ) );
		foreach ( (array) $rows as $r ) {
			$data = Quiz_CPT::get_data( $r['quiz_id'] );
			$tier = ( $r['tier_index'] >= 0 && isset( $data['tiers'][ $r['tier_index'] ] ) ) ? $data['tiers'][ $r['tier_index'] ]['title'] : '';
			fputcsv( $out, array(
				$r['created_at'], get_the_title( $r['quiz_id'] ), $r['lead_name'],
				$r['lead_mobile'], $r['lead_email'], $r['lead_company'], $r['score_total'], $tier,
			) );
		}
		fclose( $out );
		exit;
	}
}
