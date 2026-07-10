<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZC_Install {

	const DB_VERSION = '1.0.0';

	public static function activate() {
		self::create_tables();
		SZC_Settings::maybe_seed();
		update_option( 'szc_db_version', self::DB_VERSION );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'szc_queue_sweep' );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'szc_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			SZC_Settings::maybe_seed();
			update_option( 'szc_db_version', self::DB_VERSION );
		}
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$c  = $wpdb->prefix . 'szc_contacts';
		$n  = $wpdb->prefix . 'szc_notes';
		$a  = $wpdb->prefix . 'szc_activities';
		$q  = $wpdb->prefix . 'szc_sms_queue';
		$t  = $wpdb->prefix . 'szc_templates';

		$sql = "CREATE TABLE $c (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			first_name varchar(100) NOT NULL DEFAULT '',
			last_name varchar(100) NOT NULL DEFAULT '',
			mobile varchar(20) NOT NULL DEFAULT '',
			job varchar(150) NOT NULL DEFAULT '',
			company varchar(150) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			source varchar(100) NOT NULL DEFAULT '',
			tags varchar(255) NOT NULL DEFAULT '',
			priority varchar(20) NOT NULL DEFAULT 'warm',
			stage varchar(30) NOT NULL DEFAULT 'new',
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			opt_out tinyint(1) NOT NULL DEFAULT 0,
			last_contacted_at datetime DEFAULT NULL,
			next_followup_at datetime DEFAULT NULL,
			note text NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY mobile (mobile),
			KEY stage (stage),
			KEY priority (priority),
			KEY owner_id (owner_id),
			KEY next_followup_at (next_followup_at)
		) $charset;
		CREATE TABLE $n (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			body text NULL,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY contact_id (contact_id)
		) $charset;
		CREATE TABLE $a (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			type varchar(20) NOT NULL DEFAULT 'call',
			outcome varchar(30) NOT NULL DEFAULT '',
			body text NULL,
			meta longtext NULL,
			due_at datetime DEFAULT NULL,
			done tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY contact_id (contact_id),
			KEY type (type),
			KEY due (type,done,due_at)
		) $charset;
		CREATE TABLE $q (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
			mobile varchar(20) NOT NULL DEFAULT '',
			message text NULL,
			pattern_code varchar(60) NOT NULL DEFAULT '',
			pattern_values longtext NULL,
			template_id bigint(20) unsigned NOT NULL DEFAULT 0,
			send_at datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts int(11) NOT NULL DEFAULT 0,
			response text NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY due (status,send_at),
			KEY contact_id (contact_id)
		) $charset;
		CREATE TABLE $t (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(150) NOT NULL DEFAULT '',
			body text NULL,
			pattern_code varchar(60) NOT NULL DEFAULT '',
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset;";

		dbDelta( $sql );
	}
}
