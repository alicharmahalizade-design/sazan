<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Install {

	const DB_VERSION = '1.6.0';

	public static function activate() {
		self::create_tables();
		SZP_CPT::register();
		flush_rewrite_rules();
		update_option( 'szp_db_version', self::DB_VERSION );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'szp_eval_daily' );
		wp_clear_scheduled_hook( 'szp_session_survey_sweep' );
		flush_rewrite_rules();
	}

	/** Re-run dbDelta if the stored schema version differs (e.g. after a plugin update). */
	public static function maybe_upgrade() {
		if ( get_option( 'szp_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			update_option( 'szp_db_version', self::DB_VERSION );
		}
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$g  = $wpdb->prefix . 'szp_groups';
		$gu = $wpdb->prefix . 'szp_group_users';
		$ac = $wpdb->prefix . 'szp_access';
		$sb = $wpdb->prefix . 'szp_submissions';
		$sv = $wpdb->prefix . 'szp_survey';
		$ck = $wpdb->prefix . 'szp_checklist';

		$sql = "CREATE TABLE $g (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL DEFAULT '',
			slug varchar(191) NOT NULL DEFAULT '',
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset;
		CREATE TABLE $gu (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY group_user (group_id,user_id),
			KEY user_id (user_id)
		) $charset;
		CREATE TABLE $ac (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			target_type varchar(20) NOT NULL DEFAULT 'user',
			target_id bigint(20) unsigned NOT NULL DEFAULT 0,
			source varchar(20) NOT NULL DEFAULT 'manual',
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY access_unique (course_id,target_type,target_id,source),
			KEY course_id (course_id),
			KEY target (target_type,target_id)
		) $charset;
		CREATE TABLE $sb (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			content longtext NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY session_user (session_id,user_id)
		) $charset;
		CREATE TABLE $sv (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			context varchar(20) NOT NULL DEFAULT 'session',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			answers longtext NULL,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY response (context,object_id,user_id)
		) $charset;
		CREATE TABLE $ck (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			item_index int(11) NOT NULL DEFAULT 0,
			done tinyint(1) NOT NULL DEFAULT 0,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY item (session_id,user_id,item_index)
		) $charset;";

		// ---- chat room (اتاق گفتگو) ----
		$cr = $wpdb->prefix . 'szp_chat_rooms';
		$cg = $wpdb->prefix . 'szp_chat_groups';
		$cm = $wpdb->prefix . 'szp_chat_group_members';
		$cx = $wpdb->prefix . 'szp_chat_messages';
		$cv = $wpdb->prefix . 'szp_chat_votes';

		$sql .= "
		CREATE TABLE $cr (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			free_deadline datetime DEFAULT NULL,
			group_count int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY course_id (course_id)
		) $charset;
		CREATE TABLE $cg (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			room_id bigint(20) unsigned NOT NULL DEFAULT 0,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			idx int(11) NOT NULL DEFAULT 0,
			name varchar(191) NOT NULL DEFAULT '',
			logo_id bigint(20) unsigned NOT NULL DEFAULT 0,
			mission text NULL,
			slogan varchar(191) NOT NULL DEFAULT '',
			leader_id bigint(20) unsigned NOT NULL DEFAULT 0,
			vote_deadline datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'voting',
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY room_id (room_id)
		) $charset;
		CREATE TABLE $cm (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL DEFAULT 0,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			role varchar(191) NOT NULL DEFAULT '',
			joined_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY member (course_id,user_id),
			KEY group_id (group_id)
		) $charset;
		CREATE TABLE $cx (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			scope varchar(10) NOT NULL DEFAULT 'free',
			group_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			content longtext NULL,
			file_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY stream (course_id,scope,group_id,id)
		) $charset;
		CREATE TABLE $cv (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			group_id bigint(20) unsigned NOT NULL DEFAULT 0,
			voter_id bigint(20) unsigned NOT NULL DEFAULT 0,
			candidate_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY vote (group_id,voter_id),
			KEY group_id (group_id)
		) $charset;";

		// ---- کوچینگ کسب‌وکار (business coaching) ----
		$cj = $wpdb->prefix . 'szp_coach_journeys';
		$ckp = $wpdb->prefix . 'szp_coach_kpis';
		$can = $wpdb->prefix . 'szp_coach_answers';
		$cw = $wpdb->prefix . 'szp_coach_weeks';

		$sql .= "
		CREATE TABLE $cj (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			stage varchar(20) NOT NULL DEFAULT 'scan',
			status varchar(20) NOT NULL DEFAULT 'active',
			data longtext NULL,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY journey (course_id,user_id),
			KEY course_id (course_id)
		) $charset;
		CREATE TABLE $ckp (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			journey_id bigint(20) unsigned NOT NULL DEFAULT 0,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(191) NOT NULL DEFAULT '',
			unit varchar(60) NOT NULL DEFAULT '',
			direction varchar(10) NOT NULL DEFAULT 'up',
			baseline double NOT NULL DEFAULT 0,
			target double NOT NULL DEFAULT 0,
			sort int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY journey_id (journey_id)
		) $charset;
		CREATE TABLE $can (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			journey_id bigint(20) unsigned NOT NULL DEFAULT 0,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			answers longtext NULL,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY journey_id (journey_id)
		) $charset;
		CREATE TABLE $cw (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			journey_id bigint(20) unsigned NOT NULL DEFAULT 0,
			course_id bigint(20) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			week_no int(11) NOT NULL DEFAULT 0,
			title varchar(191) NOT NULL DEFAULT '',
			session_at varchar(32) NOT NULL DEFAULT '',
			actions longtext NULL,
			tasks longtext NULL,
			metrics longtext NULL,
			student_report longtext NULL,
			coach_feedback longtext NULL,
			score double NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'draft',
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY week (journey_id,week_no),
			KEY journey_id (journey_id)
		) $charset;";

		// ---- بوم طراحی خدمت (service design canvas) ----
		$cv = $wpdb->prefix . 'szp_canvas';

		$sql .= "
		CREATE TABLE $cv (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			canvas_key varchar(60) NOT NULL DEFAULT 'service_design',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			data longtext NULL,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY canvas_user (canvas_key,user_id)
		) $charset;";

		// ---- ارزیابی من (weekly target evaluation) ----
		$ev = $wpdb->prefix . 'szp_eval';

		$sql .= "
		CREATE TABLE $ev (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			week_no int(11) NOT NULL DEFAULT 0,
			title varchar(191) NOT NULL DEFAULT '',
			target double NOT NULL DEFAULT 0,
			result double NOT NULL DEFAULT 0,
			has_result tinyint(1) NOT NULL DEFAULT 0,
			note text NULL,
			target_set_at datetime DEFAULT NULL,
			result_set_at datetime DEFAULT NULL,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_week (user_id,week_no),
			KEY user_id (user_id)
		) $charset;";

		// ---- جلسات کوچینگ (coach session scheduling) ----
		$cs = $wpdb->prefix . 'szp_coach_sessions';

		$sql .= "
		CREATE TABLE $cs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(191) NOT NULL DEFAULT '',
			customer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			customer_name varchar(191) NOT NULL DEFAULT '',
			customer_mobile varchar(32) NOT NULL DEFAULT '',
			coach_id bigint(20) unsigned NOT NULL DEFAULT 0,
			coach_name varchar(191) NOT NULL DEFAULT '',
			coach_mobile varchar(32) NOT NULL DEFAULT '',
			mentor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			mentor_name varchar(191) NOT NULL DEFAULT '',
			mentor_mobile varchar(32) NOT NULL DEFAULT '',
			start_ts bigint(20) NOT NULL DEFAULT 0,
			end_ts bigint(20) NOT NULL DEFAULT 0,
			survey_url text NULL,
			note text NULL,
			status varchar(20) NOT NULL DEFAULT 'scheduled',
			notify_sent tinyint(1) NOT NULL DEFAULT 0,
			survey_sent tinyint(1) NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY coach_id (coach_id),
			KEY customer_id (customer_id),
			KEY survey_due (survey_sent,end_ts)
		) $charset;";

		dbDelta( $sql );
	}
}
