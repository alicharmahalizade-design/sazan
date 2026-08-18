<?php
/**
 * نوع پست اختصاصی «درخواست مشاوره کسب‌وکار» + ستون‌های لیست مدیریت.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Consult_CPT {

	const POST_TYPE = 'sazan_consult';

	private static $instance = null;

	/** وضعیت‌های درخواست. */
	public static function statuses() {
		return array(
			'pending'   => esc_html__( 'در انتظار', 'sazan-core' ),
			'confirmed' => esc_html__( 'تایید شده', 'sazan-core' ),
			'done'      => esc_html__( 'انجام شده', 'sazan-core' ),
			'cancelled' => esc_html__( 'لغو شده', 'sazan-core' ),
		);
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );

		// فیلتر وضعیت در بالای لیست
		add_action( 'restrict_manage_posts', array( $this, 'status_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_query' ) );
	}

	public function register() {
		register_post_type( self::POST_TYPE, array(
			'labels' => array(
				'name'          => esc_html__( 'مشاوره‌های کسب‌وکار', 'sazan-core' ),
				'singular_name' => esc_html__( 'درخواست مشاوره', 'sazan-core' ),
				'menu_name'     => esc_html__( 'مشاوره کسب‌وکار', 'sazan-core' ),
				'all_items'     => esc_html__( 'همه‌ی درخواست‌ها', 'sazan-core' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-businessperson',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
		) );
	}

	public function columns( $cols ) {
		return array(
			'cb'         => $cols['cb'],
			'title'      => esc_html__( 'نام و نام خانوادگی', 'sazan-core' ),
			'sz_biz'     => esc_html__( 'کسب‌وکار', 'sazan-core' ),
			'sz_phone'   => esc_html__( 'تماس', 'sazan-core' ),
			'sz_when'    => esc_html__( 'تاریخ مشاوره', 'sazan-core' ),
			'sz_status'  => esc_html__( 'وضعیت', 'sazan-core' ),
			'sz_created' => esc_html__( 'ثبت', 'sazan-core' ),
		);
	}

	public function column_content( $col, $post_id ) {
		switch ( $col ) {
			case 'sz_biz':
				$biz   = get_post_meta( $post_id, '_sz_business', true );
				$field = get_post_meta( $post_id, '_sz_field', true );
				$staff = get_post_meta( $post_id, '_sz_staff', true );
				echo esc_html( $biz );
				if ( $field ) { echo '<br><small>' . esc_html( $field ) . '</small>'; }
				if ( $staff ) { echo ' <small>(' . esc_html( $staff ) . ' نفر)</small>'; }
				break;
			case 'sz_phone':
				echo '<span style="direction:ltr">' . esc_html( get_post_meta( $post_id, '_sz_phone', true ) ) . '</span>';
				break;
			case 'sz_when':
				$jdate = get_post_meta( $post_id, '_sz_jdate', true );
				$time  = get_post_meta( $post_id, '_sz_time', true );
				echo esc_html( trim( $jdate . ' — ' . $time, ' —' ) );
				break;
			case 'sz_created':
				echo esc_html( get_the_date( 'Y/m/d H:i', $post_id ) );
				break;
			case 'sz_status':
				$st  = get_post_meta( $post_id, '_sz_status', true ) ?: 'pending';
				$all = self::statuses();
				$colors = array( 'pending' => '#b8860b', 'confirmed' => '#0a7d24', 'done' => '#155fa0', 'cancelled' => '#a32020' );
				printf(
					'<span style="display:inline-block;padding:3px 10px;border-radius:20px;color:#fff;font-size:12px;background:%s">%s</span>',
					esc_attr( $colors[ $st ] ?? '#555' ),
					esc_html( $all[ $st ] ?? $st )
				);
				break;
		}
	}

	/* فیلتر وضعیت در نوار بالای لیست */
	public function status_filter( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) { return; }
		$cur = isset( $_GET['sz_status'] ) ? sanitize_key( $_GET['sz_status'] ) : '';
		echo '<select name="sz_status"><option value="">' . esc_html__( 'همه‌ی وضعیت‌ها', 'sazan-core' ) . '</option>';
		foreach ( self::statuses() as $k => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $cur, $k, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	public function filter_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) { return; }
		if ( ( $query->get( 'post_type' ) ) !== self::POST_TYPE ) { return; }
		if ( ! empty( $_GET['sz_status'] ) ) {
			$query->set( 'meta_query', array( array(
				'key'   => '_sz_status',
				'value' => sanitize_key( $_GET['sz_status'] ),
			) ) );
		}
	}
}
