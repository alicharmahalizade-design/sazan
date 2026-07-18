<?php
/**
 * نوع پست‌های فرم‌ساز سازان:
 *   - sazan_form        : تعریف فرم (سازنده‌ی فرم)
 *   - sazan_form_entry  : ثبت‌های ارسالی کاربران
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Form_CPT {

	const POST_TYPE  = 'sazan_form';
	const ENTRY_TYPE = 'sazan_form_entry';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );

		// ستون شورت‌کد در لیست فرم‌ها
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'form_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'form_column' ), 10, 2 );

		// ستون‌های لیست ثبت‌ها
		add_filter( 'manage_' . self::ENTRY_TYPE . '_posts_columns', array( $this, 'entry_columns' ) );
		add_action( 'manage_' . self::ENTRY_TYPE . '_posts_custom_column', array( $this, 'entry_column' ), 10, 2 );
	}

	public function register() {

		register_post_type( self::POST_TYPE, array(
			'labels' => array(
				'name'          => esc_html__( 'فرم‌ساز', 'sazan-core' ),
				'singular_name' => esc_html__( 'فرم', 'sazan-core' ),
				'menu_name'     => esc_html__( 'فرم‌ساز سازان', 'sazan-core' ),
				'add_new'       => esc_html__( 'فرم جدید', 'sazan-core' ),
				'add_new_item'  => esc_html__( 'افزودن فرم جدید', 'sazan-core' ),
				'edit_item'     => esc_html__( 'ویرایش فرم', 'sazan-core' ),
				'all_items'     => esc_html__( 'همه‌ی فرم‌ها', 'sazan-core' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-feedback',
			'menu_position'       => 27,
			'capability_type'     => 'post',
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
		) );

		register_post_type( self::ENTRY_TYPE, array(
			'labels' => array(
				'name'          => esc_html__( 'ثبت‌های فرم', 'sazan-core' ),
				'singular_name' => esc_html__( 'ثبت فرم', 'sazan-core' ),
				'menu_name'     => esc_html__( 'ثبت‌های فرم', 'sazan-core' ),
				'all_items'     => esc_html__( 'همه‌ی ثبت‌ها', 'sazan-core' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=' . self::POST_TYPE,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
		) );
	}

	/* ---------- لیست فرم‌ها ---------- */

	public function form_columns( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['sz_shortcode'] = esc_html__( 'شورت‌کد', 'sazan-core' );
			}
		}
		return $new;
	}

	public function form_column( $col, $post_id ) {
		if ( 'sz_shortcode' === $col ) {
			printf(
				'<code style="user-select:all">[sazan_form id="%d"]</code>',
				(int) $post_id
			);
		}
	}

	/* ---------- لیست ثبت‌ها ---------- */

	public function entry_columns( $cols ) {
		return array(
			'cb'         => $cols['cb'],
			'title'      => esc_html__( 'عنوان', 'sazan-core' ),
			'sz_form'    => esc_html__( 'فرم', 'sazan-core' ),
			'sz_summary' => esc_html__( 'خلاصه', 'sazan-core' ),
			'sz_created' => esc_html__( 'تاریخ ثبت', 'sazan-core' ),
		);
	}

	public function entry_column( $col, $post_id ) {
		switch ( $col ) {
			case 'sz_form':
				$fid = (int) get_post_meta( $post_id, '_szf_form', true );
				echo $fid ? esc_html( get_the_title( $fid ) ) : '—';
				break;
			case 'sz_summary':
				$data = json_decode( (string) get_post_meta( $post_id, '_szf_data', true ), true );
				if ( is_array( $data ) ) {
					$bits = array();
					foreach ( $data as $row ) {
						$bits[] = '<strong>' . esc_html( $row['label'] ) . ':</strong> ' . esc_html( $row['value'] );
					}
					echo wp_kses_post( implode( ' — ', array_slice( $bits, 0, 4 ) ) );
				}
				break;
			case 'sz_created':
				echo esc_html( get_the_date( 'Y/m/d H:i', $post_id ) );
				break;
		}
	}
}
