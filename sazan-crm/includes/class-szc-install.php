<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZC_Install {

	const DB_VERSION = '1.7.0';

	public static function activate() {
		self::create_tables();
		SZC_Settings::maybe_seed();
		self::migrate();
		update_option( 'szc_db_version', self::DB_VERSION );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'szc_queue_sweep' );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'szc_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			SZC_Settings::maybe_seed();
			self::migrate();
			update_option( 'szc_db_version', self::DB_VERSION );
		}
	}

	/**
	 * مهاجرت خودکار برای نصب‌های موجود:
	 *  - قیفِ جدید را اعمال و مراحلِ مخاطبینِ قدیمی را به معادلِ جدید نگاشت می‌کند
	 *    (فقط اگر مدیر مراحل را سفارشی نکرده باشد).
	 *  - قالب‌های تشکرِ پیش‌فرض (۵ مدل) را در صورت نبود اضافه می‌کند (بدون تکرار).
	 */
	public static function migrate() {
		global $wpdb;
		$map = array(
			'new'        => 'lead',
			'contacted'  => 'answered',
			'interested' => 'info_want',
			'mini_sent'  => 'info_feedback',
			'invited'    => 'promised',
		);

		$s        = get_option( SZC_Settings::OPTION, array() );
		$s        = is_array( $s ) ? $s : array();
		$cur      = isset( $s['stages'] ) && is_array( $s['stages'] ) ? $s['stages'] : array();
		$old      = array( 'new', 'contacted', 'interested', 'mini_sent', 'invited', 'registered', 'not_interested', 'wrong' );
		$is_stock = ! $cur || array_keys( $cur ) === $old;

		if ( $is_stock && get_option( 'szc_funnel_migrated' ) !== '1' ) {
			$s['stages'] = SZC_Settings::default_stages();
			update_option( SZC_Settings::OPTION, $s );
			// نگاشتِ مراحلِ مخاطبین قدیمی → جدید.
			$table = $wpdb->prefix . 'szc_contacts';
			foreach ( $map as $from => $to ) {
				$wpdb->update( $table, array( 'stage' => $to ), array( 'stage' => $from ) );
			}
			update_option( 'szc_funnel_migrated', '1' );
		}

		// ترمیمِ مرحله‌های نامعتبر: مخاطبینی که مرحله‌شان (مثلاً 'new' قدیمی) در قیفِ
		// فعلی تعریف نشده، به نخستین مرحله‌ی قیف (لید) منتقل می‌شوند تا در صفِ
		// «تماس پشت‌سرهم» دیده شوند. یک‌بار برای هر نسخه‌ی DB اجرا می‌شود.
		if ( class_exists( 'SZC_Settings' ) ) {
			$valid = array_keys( SZC_Settings::stages() );
			$def   = SZC_Settings::default_stage();
			if ( $valid && $def ) {
				$table = $wpdb->prefix . 'szc_contacts';
				$ph    = implode( ',', array_fill( 0, count( $valid ), '%s' ) );
				$sql   = "UPDATE $table SET stage=%s WHERE stage NOT IN ($ph)";
				$wpdb->query( $wpdb->prepare( $sql, array_merge( array( $def ), $valid ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
		}

		// افزودن قالب‌های تشکرِ گمشده (بر اساس نام, بدون تکرار).
		if ( class_exists( 'SZC_Templates' ) ) {
			$existing = array();
			foreach ( SZC_Templates::all() as $t ) {
				$existing[ $t->name ] = true;
			}
			$wanted = array(
				'تشکر ۲ — گرم و صمیمی'         => '%first% جان، خوشحال شدیم که با شما صحبت کردیم. هر سؤالی داشتید ما در کنارتان هستیم. 🌟',
				'تشکر ۳ — رسمی'                => '%name% گرامی، از زمانی که در اختیار ما گذاشتید سپاسگزاریم. در صورت تمایل، اطلاعات تکمیلی خدمت شما ارسال می‌شود.',
				'تشکر ۴ — دعوت به اقدام'       => '%first% عزیز، ممنون از گفتگوی امروز. برای شروع می‌توانید از این لینک استفاده کنید: %mini%',
				'تشکر ۵ — پیگیری تماس ناموفق' => '%first% عزیز، تماس گرفتیم اما در دسترس نبودید. لطفاً زمان مناسب برای تماس مجدد را به ما اطلاع دهید. 🙏',
			);
			foreach ( $wanted as $name => $body ) {
				if ( empty( $existing[ $name ] ) ) {
					SZC_Templates::create( $name, $body );
				}
			}
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
			stage varchar(30) NOT NULL DEFAULT 'lead',
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			group_id bigint(20) unsigned NOT NULL DEFAULT 0,
			opt_out tinyint(1) NOT NULL DEFAULT 0,
			last_contacted_at datetime DEFAULT NULL,
			next_followup_at datetime DEFAULT NULL,
			note text NULL,
			meta longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY mobile (mobile),
			KEY stage (stage),
			KEY priority (priority),
			KEY owner_id (owner_id),
			KEY group_id (group_id),
			KEY next_followup_at (next_followup_at)
		) $charset;
		CREATE TABLE {$wpdb->prefix}szc_groups (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(150) NOT NULL DEFAULT '',
			sort int(11) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY parent_id (parent_id)
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
			reminded tinyint(1) NOT NULL DEFAULT 0,
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
			enrollment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			send_at datetime DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			attempts int(11) NOT NULL DEFAULT 0,
			response text NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY due (status,send_at),
			KEY contact_id (contact_id),
			KEY enrollment_id (enrollment_id)
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

		// ---- لیست سیاه (blacklist / opt-out سراسری بر اساس موبایل) ----
		$bl = $wpdb->prefix . 'szc_blacklist';
		$sql .= "
		CREATE TABLE $bl (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			mobile varchar(20) NOT NULL DEFAULT '',
			reason varchar(191) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY mobile (mobile)
		) $charset;";

		// ---- بخش‌بندی/فیلترِ ذخیره‌شده (segments) ----
		$sg = $wpdb->prefix . 'szc_segments';
		$sql .= "
		CREATE TABLE $sg (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(150) NOT NULL DEFAULT '',
			filters longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset;";

		// ---- دنباله‌ی پیامکی (drip sequences) ----
		$seq = $wpdb->prefix . 'szc_sequences';
		$sst = $wpdb->prefix . 'szc_sequence_steps';
		$enr = $wpdb->prefix . 'szc_enrollments';
		$sql .= "
		CREATE TABLE $seq (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(150) NOT NULL DEFAULT '',
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT NULL,
			updated_at datetime DEFAULT NULL,
			PRIMARY KEY  (id)
		) $charset;
		CREATE TABLE $sst (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sequence_id bigint(20) unsigned NOT NULL DEFAULT 0,
			step_no int(11) NOT NULL DEFAULT 0,
			day_offset int(11) NOT NULL DEFAULT 0,
			hour int(11) NOT NULL DEFAULT 10,
			template_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY sequence_id (sequence_id)
		) $charset;
		CREATE TABLE $enr (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			sequence_id bigint(20) unsigned NOT NULL DEFAULT 0,
			contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			started_at datetime DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY enroll (sequence_id,contact_id),
			KEY contact_id (contact_id)
		) $charset;";

		dbDelta( $sql );
	}
}
