<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** لایه‌ی داده‌ی مخاطبین (سرنخ‌ها). */
class SZC_Contacts {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_contacts';
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function get_by_mobile( $mobile ) {
		global $wpdb;
		$mobile = szc_normalize_mobile( $mobile );
		if ( $mobile === '' ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE mobile=%s', $mobile ) );
	}

	public static function full_name( $c ) {
		$name = trim( (string) $c->first_name . ' ' . (string) $c->last_name );
		return $name !== '' ? $name : szc_fa_digits( $c->mobile );
	}

	/** فیلدهای مجاز و پاک‌سازی‌شده از ورودی خام. */
	protected static function clean( $in ) {
		$out = array();
		$map = array(
			'first_name' => 'text', 'last_name' => 'text', 'job' => 'text', 'company' => 'text',
			'city' => 'text', 'source' => 'text', 'tags' => 'text',
			'bale_id' => 'text', 'rubika_id' => 'text',
			'email' => 'email', 'note' => 'textarea',
			'priority' => 'priority', 'stage' => 'stage',
		);
		foreach ( $map as $k => $type ) {
			if ( ! array_key_exists( $k, $in ) ) {
				continue;
			}
			$v = $in[ $k ];
			switch ( $type ) {
				case 'email':    $out[ $k ] = sanitize_email( $v ); break;
				case 'textarea': $out[ $k ] = sanitize_textarea_field( $v ); break;
				case 'priority': $out[ $k ] = array_key_exists( $v, SZC_Settings::priorities() ) ? $v : 'warm'; break;
				case 'stage':    $out[ $k ] = SZC_Settings::is_valid_stage( $v ) ? $v : SZC_Settings::default_stage(); break;
				default:         $out[ $k ] = sanitize_text_field( $v );
			}
		}
		return $out;
	}

	public static function create( $in ) {
		global $wpdb;
		$mobile = szc_normalize_mobile( $in['mobile'] ?? '' );
		if ( $mobile === '' ) {
			return 0;
		}
		if ( self::get_by_mobile( $mobile ) ) {
			return 0; // موبایل تکراری
		}
		$now  = current_time( 'mysql' );
		// مرحله‌ی پیش‌فرض = نخستین مرحله‌ی قیف (لید) تا مخاطبِ تازه در «تماس پشت‌سرهم» بیاید.
		$data = array_merge( array(
			'first_name' => '', 'last_name' => '', 'job' => '', 'company' => '',
			'city' => '', 'email' => '', 'source' => '', 'tags' => '', 'note' => '',
			'bale_id' => '', 'rubika_id' => '',
			'priority' => 'warm', 'stage' => SZC_Settings::default_stage(),
		), self::clean( $in ) );
		$data['mobile']     = $mobile;
		$data['created_by'] = get_current_user_id();
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		$cf = self::clean_cf( $in['cf'] ?? array() );
		if ( $cf ) {
			$data['meta'] = wp_json_encode( $cf );
		}
		$wpdb->insert( self::table(), $data );
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $in ) {
		global $wpdb;
		$data = self::clean( $in );
		if ( isset( $in['mobile'] ) ) {
			$m = szc_normalize_mobile( $in['mobile'] );
			$existing = $m ? self::get_by_mobile( $m ) : null;
			if ( $m !== '' && ( ! $existing || (int) $existing->id === (int) $id ) ) {
				$data['mobile'] = $m;
			}
		}
		// فیلدهای سفارشی: ادغام با مقادیر موجود.
		if ( isset( $in['cf'] ) ) {
			$c = self::get( $id );
			$meta = $c ? self::get_meta( $c ) : array();
			foreach ( self::clean_cf( $in['cf'] ) as $k => $v ) {
				$meta[ $k ] = $v;
			}
			$data['meta'] = wp_json_encode( $meta );
		}
		if ( ! $data ) {
			return;
		}
		$data['updated_at'] = current_time( 'mysql' );
		$wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
	}

	/** مقادیر فیلدهای سفارشی (فقط کلیدهای تعریف‌شده). */
	protected static function clean_cf( $cf ) {
		$defined = array();
		foreach ( SZC_Settings::custom_fields() as $f ) {
			$defined[ $f['key'] ] = true;
		}
		$out = array();
		foreach ( (array) $cf as $k => $v ) {
			$k = sanitize_key( $k );
			if ( isset( $defined[ $k ] ) ) {
				$out[ $k ] = sanitize_text_field( $v );
			}
		}
		return $out;
	}

	/** مقادیر فیلدهای سفارشی یک مخاطب. */
	public static function get_meta( $c ) {
		$m = json_decode( (string) ( $c->meta ?? '' ), true );
		return is_array( $m ) ? $m : array();
	}

	/** درج یا به‌روزرسانی بر اساس موبایل (برای ایمپورت). خروجی: 'created'|'updated'|'skipped'. */
	public static function upsert_by_mobile( $in, $update_existing = true ) {
		$mobile = szc_normalize_mobile( $in['mobile'] ?? '' );
		if ( $mobile === '' ) {
			return 'skipped';
		}
		$existing = self::get_by_mobile( $mobile );
		if ( $existing ) {
			if ( $update_existing ) {
				// فقط فیلدهای خالیِ موجود را با مقدار جدید پر کن (تا داده‌ی دستی خراب نشود).
				$fill = array();
				foreach ( array( 'first_name', 'last_name', 'job', 'company', 'city', 'email', 'source', 'tags' ) as $k ) {
					if ( trim( (string) $existing->$k ) === '' && trim( (string) ( $in[ $k ] ?? '' ) ) !== '' ) {
						$fill[ $k ] = $in[ $k ];
					}
				}
				if ( $fill ) {
					self::update( (int) $existing->id, $fill );
				}
			}
			return 'updated';
		}
		$in['mobile'] = $mobile;
		return self::create( $in ) ? 'created' : 'skipped';
	}

	/* ==================== کوئری/فیلتر ==================== */

	public static function query( $args = array() ) {
		global $wpdb;
		$a = wp_parse_args( $args, array(
			'search'   => '',
			'stage'    => '',
			'priority' => '',
			'tag'      => '',
			'opt_out'  => '',
			'owner'    => '',   // آیدی مالک؛ برای محدودسازی کارشناس
			'due'      => '',   // 'today' | 'overdue'
			'orderby'  => 'updated_at',
			'order'    => 'DESC',
			'page'     => 1,
			'per_page' => 25,
		) );

		list( $where_sql, $vals ) = self::build_where( $a );

		$allowed_orderby = array( 'updated_at', 'created_at', 'last_contacted_at', 'next_followup_at', 'first_name', 'priority' );
		$orderby = in_array( $a['orderby'], $allowed_orderby, true ) ? $a['orderby'] : 'updated_at';
		$order   = strtoupper( $a['order'] ) === 'ASC' ? 'ASC' : 'DESC';
		// اولویت با ترتیب معنایی (طبق ترتیب تنظیم‌شده) وقتی مرتب‌سازی روی priority است.
		if ( $orderby === 'priority' ) {
			$quoted = array();
			foreach ( SZC_Settings::priority_keys() as $pk ) {
				$quoted[] = "'" . esc_sql( $pk ) . "'";
			}
			$orderby_sql = $quoted ? 'FIELD(priority,' . implode( ',', $quoted ) . ') ' . $order : 'updated_at ' . $order;
		} else {
			$orderby_sql = "$orderby $order";
		}

		$per_page = max( 1, min( 200, (int) $a['per_page'] ) );
		$page     = max( 1, (int) $a['page'] );
		$offset   = ( $page - 1 ) * $per_page;

		$count_sql = 'SELECT COUNT(*) FROM ' . self::table() . " WHERE $where_sql";
		$total     = (int) ( $vals ? $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) ) : $wpdb->get_var( $count_sql ) );

		$list_sql = 'SELECT * FROM ' . self::table() . " WHERE $where_sql ORDER BY $orderby_sql LIMIT %d OFFSET %d";
		$list_vals = array_merge( $vals, array( $per_page, $offset ) );
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_vals ) );

		return array( 'items' => $items, 'total' => $total, 'per_page' => $per_page, 'page' => $page );
	}

	/** ساخت WHERE مشترک از آرگومان‌های فیلتر. خروجی: array( where_sql, vals ). */
	protected static function build_where( $a ) {
		global $wpdb;
		$where = array( '1=1' );
		$vals  = array();
		if ( ! empty( $a['search'] ) ) {
			$like = '%' . $wpdb->esc_like( szc_latin_digits( $a['search'] ) ) . '%';
			$where[] = '(first_name LIKE %s OR last_name LIKE %s OR mobile LIKE %s OR company LIKE %s OR job LIKE %s OR city LIKE %s)';
			array_push( $vals, $like, $like, $like, $like, $like, $like );
		}
		if ( ! empty( $a['stage'] ) ) {
			$where[] = 'stage=%s'; $vals[] = $a['stage'];
		}
		if ( ! empty( $a['priority'] ) ) {
			$where[] = 'priority=%s'; $vals[] = $a['priority'];
		}
		if ( ! empty( $a['tag'] ) ) {
			$where[] = 'tags LIKE %s'; $vals[] = '%' . $wpdb->esc_like( $a['tag'] ) . '%';
		}
		if ( isset( $a['opt_out'] ) && $a['opt_out'] !== '' ) {
			$where[] = 'opt_out=%d'; $vals[] = (int) $a['opt_out'];
		}
		if ( ! empty( $a['owner'] ) ) {
			$where[] = 'owner_id=%d'; $vals[] = (int) $a['owner'];
		}
		if ( ! empty( $a['group'] ) ) {
			// شاملِ زیرپوشه‌ها.
			$gids = class_exists( 'SZC_Groups' ) ? SZC_Groups::descendants( (int) $a['group'] ) : array( (int) $a['group'] );
			$ph   = implode( ',', array_fill( 0, count( $gids ), '%d' ) );
			$where[] = 'group_id IN (' . $ph . ')';
			foreach ( $gids as $g ) { $vals[] = (int) $g; }
		}
		if ( ( $a['due'] ?? '' ) === 'today' ) {
			$where[] = 'next_followup_at IS NOT NULL AND next_followup_at <= %s'; $vals[] = current_time( 'mysql' );
		} elseif ( ( $a['due'] ?? '' ) === 'overdue' ) {
			$where[] = 'next_followup_at IS NOT NULL AND next_followup_at < %s'; $vals[] = current_time( 'mysql' );
		}
		return array( implode( ' AND ', $where ), $vals );
	}

	/** همه‌ی آیدی‌های منطبق با یک فیلتر (برای اقدام گروهی روی کل نتایج). */
	public static function ids_matching( $args, $cap = 20000 ) {
		global $wpdb;
		list( $where_sql, $vals ) = self::build_where( $args );
		$sql = 'SELECT id FROM ' . self::table() . " WHERE $where_sql LIMIT " . (int) $cap;
		$col = $vals ? $wpdb->get_col( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_col( $sql );
		return array_map( 'intval', $col );
	}

	/** همه‌ی ردیف‌های منطبق با فیلتر (برای خروجی CSV). */
	public static function rows_matching( $args, $cap = 50000 ) {
		global $wpdb;
		list( $where_sql, $vals ) = self::build_where( $args );
		$sql = 'SELECT * FROM ' . self::table() . " WHERE $where_sql ORDER BY id ASC LIMIT " . (int) $cap;
		return $vals ? $wpdb->get_results( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_results( $sql );
	}

	public static function counts_by_stage( $owner = 0 ) {
		global $wpdb;
		$where = $owner > 0 ? $wpdb->prepare( 'WHERE owner_id=%d', (int) $owner ) : '';
		$rows  = $wpdb->get_results( 'SELECT stage, COUNT(*) c FROM ' . self::table() . " $where GROUP BY stage", OBJECT_K );
		$out   = array();
		foreach ( SZC_Settings::stages() as $k => $lbl ) {
			$out[ $k ] = isset( $rows[ $k ] ) ? (int) $rows[ $k ]->c : 0;
		}
		return $out;
	}

	public static function total( $owner = 0 ) {
		global $wpdb;
		if ( $owner > 0 ) {
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE owner_id=%d', (int) $owner ) );
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
	}

	/** افزودن یک برچسب به مخاطب (اگر نبود). */
	public static function add_tag( $id, $tag ) {
		global $wpdb;
		$tag = sanitize_text_field( $tag );
		if ( $tag === '' ) {
			return;
		}
		$c = self::get( $id );
		if ( ! $c ) {
			return;
		}
		$tags = array_filter( array_map( 'trim', explode( ',', (string) $c->tags ) ), 'strlen' );
		if ( in_array( $tag, $tags, true ) ) {
			return;
		}
		$tags[] = $tag;
		$wpdb->update( self::table(), array( 'tags' => implode( '، ', $tags ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	public static function set_owner( $id, $owner_id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'owner_id' => (int) $owner_id, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	/** تخصیص گردشی (round-robin) گروهی از مخاطبین به فهرستی از کارشناسان. */
	public static function assign_round_robin( $contact_ids, $agent_ids ) {
		$agent_ids = array_values( array_filter( array_map( 'intval', (array) $agent_ids ) ) );
		if ( ! $agent_ids ) {
			return 0;
		}
		$i = 0;
		foreach ( (array) $contact_ids as $cid ) {
			self::set_owner( (int) $cid, $agent_ids[ $i % count( $agent_ids ) ] );
			$i++;
		}
		return $i;
	}

	/* ==================== تغییرات سریع ==================== */

	public static function set_stage( $id, $stage ) {
		global $wpdb;
		if ( ! array_key_exists( $stage, SZC_Settings::stages() ) ) {
			return;
		}
		$wpdb->update( self::table(), array( 'stage' => $stage, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	public static function set_priority( $id, $priority ) {
		global $wpdb;
		if ( ! array_key_exists( $priority, SZC_Settings::priorities() ) ) {
			return;
		}
		$wpdb->update( self::table(), array( 'priority' => $priority, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	public static function set_opt_out( $id, $val ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'opt_out' => $val ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	public static function touch_contacted( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'last_contacted_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
	}

	public static function set_followup( $id, $mysql_or_null ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'next_followup_at' => $mysql_or_null ?: null ), array( 'id' => (int) $id ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		$wpdb->delete( self::table(), array( 'id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_notes', array( 'contact_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_activities', array( 'contact_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_sms_queue', array( 'contact_id' => $id ) );
	}

	/** متغیرهای قابل‌استفاده در قالب پیامک برای یک مخاطب. */
	public static function vars( $c ) {
		$s    = SZC_Settings::all();
		$vars = array(
			'name'    => self::full_name( $c ),
			'first'   => (string) $c->first_name,
			'last'    => (string) $c->last_name,
			'company' => (string) $c->company,
			'job'     => (string) $c->job,
			'city'    => (string) $c->city,
			'mini'    => (string) $s['mini_link'],
			'intro'   => (string) $s['intro_link'],
			'link'    => (string) $s['mini_link'],
		);
		// فیلدهای سفارشی به‌صورت %key%.
		$meta = self::get_meta( $c );
		foreach ( SZC_Settings::custom_fields() as $f ) {
			$vars[ $f['key'] ] = (string) ( $meta[ $f['key'] ] ?? '' );
		}
		return $vars;
	}

	/* ==================== ادغام تکراری‌ها ==================== */

	/**
	 * ادغام مخاطب فرعی در مخاطب اصلی: انتقال یادداشت/فعالیت/صف/دنباله،
	 * پر کردن فیلدهای خالیِ اصلی از فرعی، سپس حذف فرعی.
	 */
	public static function merge( $primary_id, $secondary_id ) {
		global $wpdb;
		$primary_id   = (int) $primary_id;
		$secondary_id = (int) $secondary_id;
		if ( $primary_id === $secondary_id ) {
			return array( 'ok' => false, 'msg' => 'دو مخاطب یکسان‌اند.' );
		}
		$p = self::get( $primary_id );
		$s = self::get( $secondary_id );
		if ( ! $p || ! $s ) {
			return array( 'ok' => false, 'msg' => 'مخاطب یافت نشد.' );
		}

		// پر کردن فیلدهای خالیِ اصلی از فرعی.
		$fill = array();
		foreach ( array( 'first_name', 'last_name', 'job', 'company', 'city', 'email', 'source', 'tags' ) as $k ) {
			if ( trim( (string) $p->$k ) === '' && trim( (string) $s->$k ) !== '' ) {
				$fill[ $k ] = $s->$k;
			}
		}
		if ( ! $p->owner_id && $s->owner_id ) {
			$fill['owner_id'] = (int) $s->owner_id;
		}
		// ادغام فیلدهای سفارشی (خالی‌ها از فرعی).
		$pm = self::get_meta( $p );
		$sm = self::get_meta( $s );
		foreach ( $sm as $k => $v ) {
			if ( ( ! isset( $pm[ $k ] ) || $pm[ $k ] === '' ) && $v !== '' ) {
				$pm[ $k ] = $v;
			}
		}
		$fill['meta']       = wp_json_encode( $pm );
		$fill['updated_at'] = current_time( 'mysql' );
		$wpdb->update( self::table(), $fill, array( 'id' => $primary_id ) );

		// انتقال یادداشت‌ها/فعالیت‌ها/صف.
		$wpdb->update( $wpdb->prefix . 'szc_notes', array( 'contact_id' => $primary_id ), array( 'contact_id' => $secondary_id ) );
		$wpdb->update( $wpdb->prefix . 'szc_activities', array( 'contact_id' => $primary_id ), array( 'contact_id' => $secondary_id ) );
		$wpdb->update( $wpdb->prefix . 'szc_sms_queue', array( 'contact_id' => $primary_id ), array( 'contact_id' => $secondary_id ) );

		// دنباله‌ها: از تداخل کلید یکتا (sequence_id,contact_id) پرهیز کن.
		$enr = $wpdb->get_results( $wpdb->prepare( 'SELECT id, sequence_id FROM ' . $wpdb->prefix . 'szc_enrollments WHERE contact_id=%d', $secondary_id ) );
		foreach ( $enr as $e ) {
			$dup = $wpdb->get_var( $wpdb->prepare(
				'SELECT id FROM ' . $wpdb->prefix . 'szc_enrollments WHERE sequence_id=%d AND contact_id=%d', (int) $e->sequence_id, $primary_id ) );
			if ( $dup ) {
				$wpdb->delete( $wpdb->prefix . 'szc_enrollments', array( 'id' => (int) $e->id ) );
			} else {
				$wpdb->update( $wpdb->prefix . 'szc_enrollments', array( 'contact_id' => $primary_id ), array( 'id' => (int) $e->id ) );
			}
		}

		// حذف رکورد فرعی (فرزندانش قبلاً منتقل شدند).
		$wpdb->delete( self::table(), array( 'id' => $secondary_id ) );
		self::log_merge( $primary_id, $s );
		return array( 'ok' => true, 'msg' => 'مخاطب‌ها ادغام شدند.' );
	}

	protected static function log_merge( $primary_id, $secondary ) {
		if ( class_exists( 'SZC_Activity' ) ) {
			SZC_Activity::log( $primary_id, 'stage', array(
				'outcome' => 'merge',
				'body'    => 'ادغام با رکورد تکراری: ' . self::full_name( $secondary ) . ' (' . szc_fa_digits( $secondary->mobile ) . ')',
			) );
		}
	}

	/** گروه‌های مشکوک به تکراری بر اساس نامِ کاملِ یکسان (نام+فامیل غیرخالی). */
	public static function find_duplicate_groups( $limit = 100 ) {
		global $wpdb;
		$t    = self::table();
		$rows = $wpdb->get_results(
			"SELECT LOWER(CONCAT(TRIM(first_name),' ',TRIM(last_name))) k, GROUP_CONCAT(id) ids, COUNT(*) c
			 FROM $t
			 WHERE TRIM(CONCAT(first_name,last_name))<>''
			 GROUP BY k HAVING c>1 ORDER BY c DESC LIMIT " . (int) $limit );
		$groups = array();
		foreach ( $rows as $r ) {
			$ids  = array_map( 'intval', explode( ',', $r->ids ) );
			$list = array();
			foreach ( $ids as $id ) {
				$c = self::get( $id );
				if ( $c ) {
					$list[] = $c;
				}
			}
			if ( count( $list ) > 1 ) {
				$groups[] = $list;
			}
		}
		return $groups;
	}
}
