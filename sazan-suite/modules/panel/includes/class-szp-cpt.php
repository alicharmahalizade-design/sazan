<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_CPT {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		register_post_type( 'szp_course', array(
			'labels' => array(
				'name'          => 'دوره‌ها',
				'singular_name' => 'دوره',
				'add_new'       => 'افزودن دوره',
				'add_new_item'  => 'افزودن دوره جدید',
				'edit_item'     => 'ویرایش دوره',
				'new_item'      => 'دوره جدید',
				'all_items'     => 'همه دوره‌ها',
				'search_items'  => 'جستجوی دوره',
				'menu_name'     => 'دوره‌ها',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'sazan-panel',
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-book-alt',
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
		) );

		register_post_type( 'szp_session', array(
			'labels' => array(
				'name'          => 'جلسات',
				'singular_name' => 'جلسه',
				'add_new'       => 'افزودن جلسه',
				'add_new_item'  => 'افزودن جلسه جدید',
				'edit_item'     => 'ویرایش جلسه',
				'new_item'      => 'جلسه جدید',
				'all_items'     => 'همه جلسات',
				'search_items'  => 'جستجوی جلسه',
				'menu_name'     => 'جلسات',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'sazan-panel',
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-calendar-alt',
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
		) );
	}
}
