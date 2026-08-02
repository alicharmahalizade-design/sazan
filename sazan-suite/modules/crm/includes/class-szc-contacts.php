<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** لایه‌ی داده‌ی مخاطبین (سرنخ‌ها). */
class SZC_Contacts {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_contacts';
	}

	public static function history_table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_contact_history';
	}

	protected static function history( $contact_id, $type, $old, $new, $duration_hours = 0 ) {
		global $wpdb;
		$wpdb->insert( self::history_table(), array(
			'contact_id' => (int) $contact_id,
			'actor_id' => SZC_Auth::actor_id(),
			'event_type' => sanitize_key( $type ),
			'old_value' => sanitize_text_field( (string) $old ),
			'new_value' => sanitize_text_field( (string) $new ),
			'duration_hours' => max( 0, (float) $duration_hours ),
			'created_at' => current_time( 'mysql' ),
		) );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d AND deleted_at IS NULL', (int) $id ) );
	}

	public static function get_any( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function get_by_mobile( $mobile ) {
		global $wpdb;
		$mobile = szc_normalize_mobile( $mobile );
		if ( $mobile === '' ) {
			return null;
		}
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE mobile=%s AND deleted_at IS NULL', $mobile ) );
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
			'city' => 'text', 'source' => 'text', 'campaign' => 'text', 'tags' => 'text',
			'email' => 'email', 'note' => 'textarea',
			'priority' => 'priority', 'stage' => 'stage', 'deal_value' => 'money',
			'expected_value' => 'money', 'final_value' => 'money',
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
				case 'money':    $out[ $k ] = max( 0, (float) str_replace( ',', '', szc_latin_digits( (string) $v ) ) ); break;
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
			'priority' => 'warm', 'stage' => SZC_Settings::default_stage(),
		), self::clean( $in ) );
		$data['mobile']     = $mobile;
		$data['created_by'] = SZC_Auth::actor_id();
		// مخاطبی که کارشناس می‌افزاید به خودِ او تعلق می‌گیرد (تا در فهرستِ خودش دیده شود).
		if ( ! isset( $data['owner_id'] ) && SZC_Auth::is_agent() ) {
			$data['owner_id'] = SZC_Auth::actor_id();
		}
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		$data['stage_changed_at'] = $now;
		$cf = self::clean_cf( $in['cf'] ?? array() );
		if ( $cf ) {
			$data['meta'] = wp_json_encode( $cf );
		}
		$wpdb->insert( self::table(), $data );
		$id = (int) $wpdb->insert_id;
		if ( $id && class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'create', 'contact', $id, 'مخاطب ایجاد شد', null, $data );
		}
		if ( $id ) {
			self::history( $id, 'stage', '', $data['stage'], 0 );
			if ( ! empty( $data['owner_id'] ) ) { self::history( $id, 'owner', 0, $data['owner_id'], 0 ); }
		}
		return $id;
	}

	public static function update( $id, $in ) {
		global $wpdb;
		$before = self::get( $id );
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
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'update', 'contact', $id, 'اطلاعات مخاطب ویرایش شد', $before, self::get( $id ) );
		}
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
		$where = array( ! empty( $a['trash'] ) ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL' );
		$vals  = array();
		if ( ! empty( $a['search'] ) ) {
			$term = trim( szc_latin_digits( (string) $a['search'] ) );
			$term = strtr( $term, array( 'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه' ) );
			$like = '%' . $wpdb->esc_like( $term ) . '%';
			$parts = array(
				'first_name LIKE %s',
				'last_name LIKE %s',
				"CONCAT_WS(' ', first_name, last_name) LIKE %s",
				'mobile LIKE %s',
				'company LIKE %s',
				'job LIKE %s',
				'city LIKE %s',
				'email LIKE %s',
				'source LIKE %s',
				'campaign LIKE %s',
				'tags LIKE %s',
				'meta LIKE %s',
			);
			for ( $i = 0; $i < count( $parts ); $i++ ) {
				$vals[] = $like;
			}
			// شماره‌هایی مثل +98912… و 0098912… نیز با مقدار نرمالِ 09… پیدا شوند.
			$mobile = szc_normalize_mobile( $term );
			if ( $mobile !== '' ) {
				$parts[] = 'mobile=%s';
				$vals[]  = $mobile;
			}
			$where[] = '(' . implode( ' OR ', $parts ) . ')';
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
		$where = 'WHERE deleted_at IS NULL' . ( $owner > 0 ? $wpdb->prepare( ' AND owner_id=%d', (int) $owner ) : '' );
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
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE deleted_at IS NULL AND owner_id=%d', (int) $owner ) );
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE deleted_at IS NULL' );
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
		$before = self::get( $id );
		$wpdb->update( self::table(), array( 'owner_id' => (int) $owner_id, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
		if ( $before && (int) $before->owner_id !== (int) $owner_id ) {
			self::history( $id, 'owner', (int) $before->owner_id, (int) $owner_id );
		}
		if ( $before && (int) $before->owner_id !== (int) $owner_id && class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'owner_change', 'contact', $id, 'مالک مخاطب تغییر کرد', array( 'owner_id' => (int) $before->owner_id ), array( 'owner_id' => (int) $owner_id ) );
		}
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

	/** Capacity-aware weighted distribution for currently unassigned contacts. */
	public static function smart_distribute( $contact_ids ) {
		global $wpdb;
		$lock = 'szc_smart_distribution_' . (int) get_current_blog_id();
		if ( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock ) ) !== 1 ) {
			return array( 'assigned' => 0, 'skipped' => count( (array) $contact_ids ), 'msg' => 'توزیع دیگری در حال اجراست.' );
		}
		try {
			$pool = array();
			foreach ( SZC_Agents::active() as $agent ) {
				$capacity = max( 0, (int) ( $agent->capacity ?? 100 ) );
				if ( $capacity <= 0 ) { continue; }
				$owner = SZC_Agents::to_owner( (int) $agent->id );
				$load = (int) $wpdb->get_var( $wpdb->prepare(
					'SELECT COUNT(*) FROM ' . self::table() . " WHERE deleted_at IS NULL AND owner_id=%d AND stage NOT IN ('registered','not_interested','wrong')",
					$owner
				) );
				$pool[] = array( 'owner' => $owner, 'capacity' => $capacity, 'weight' => max( 1, (int) ( $agent->distribution_weight ?? 1 ) ), 'load' => $load );
			}
			$assigned = 0; $skipped = 0;
			foreach ( array_map( 'intval', (array) $contact_ids ) as $cid ) {
				$c = self::get( $cid );
				if ( ! $c || (int) $c->owner_id !== 0 ) { $skipped++; continue; }
				$available = array_filter( $pool, function ( $a ) { return $a['load'] < $a['capacity']; } );
				if ( ! $available ) { $skipped++; continue; }
				usort( $available, function ( $a, $b ) {
					$sa = $a['load'] / ( $a['capacity'] * $a['weight'] );
					$sb = $b['load'] / ( $b['capacity'] * $b['weight'] );
					return $sa == $sb ? $a['owner'] <=> $b['owner'] : ( $sa < $sb ? -1 : 1 );
				} );
				$selected = $available[0]['owner'];
				self::set_owner( $cid, $selected );
				foreach ( $pool as &$entry ) { if ( $entry['owner'] === $selected ) { $entry['load']++; break; } } unset( $entry );
				$assigned++;
			}
			SZC_Audit::log( 'smart_distribution', 'contact', 0, $assigned . ' لید با کنترل ظرفیت توزیع شد' );
			return array( 'assigned' => $assigned, 'skipped' => $skipped, 'msg' => 'توزیع انجام شد.' );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	/* ==================== تغییرات سریع ==================== */

	public static function set_stage( $id, $stage ) {
		global $wpdb;
		if ( ! array_key_exists( $stage, SZC_Settings::stages() ) ) {
			return;
		}
		$before = self::get( $id );
		$now    = current_time( 'mysql' );
		$data   = array( 'stage' => $stage, 'stage_changed_at' => $now, 'updated_at' => $now );
		if ( $stage === 'registered' && ( ! $before || empty( $before->won_at ) ) ) {
			$data['won_at'] = $now;
		}
		$wpdb->update( self::table(), $data, array( 'id' => (int) $id ) );
		if ( $before && $before->stage !== $stage ) {
			$since = $before->stage_changed_at ?: $before->created_at;
			$hours = $since ? max( 0, ( time() - strtotime( $since ) ) / HOUR_IN_SECONDS ) : 0;
			self::history( $id, 'stage', $before->stage, $stage, $hours );
		}
		if ( $before && $before->stage !== $stage && class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'stage_change', 'contact', $id, 'مرحله فروش تغییر کرد', array( 'stage' => $before->stage ), array( 'stage' => $stage ) );
		}
	}

	public static function set_priority( $id, $priority ) {
		global $wpdb;
		if ( ! array_key_exists( $priority, SZC_Settings::priorities() ) ) {
			return;
		}
		$before = self::get( $id );
		$wpdb->update( self::table(), array( 'priority' => $priority, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
		if ( $before && $before->priority !== $priority && class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'priority_change', 'contact', $id, 'اولویت مخاطب تغییر کرد', array( 'priority' => $before->priority ), array( 'priority' => $priority ) );
		}
	}

	public static function set_opt_out( $id, $val ) {
		global $wpdb;
		$before = self::get( $id );
		$wpdb->update( self::table(), array( 'opt_out' => $val ? 1 : 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
		if ( $before && (int) $before->opt_out !== ( $val ? 1 : 0 ) && class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'opt_out_change', 'contact', $id, 'وضعیت دریافت پیامک تغییر کرد', array( 'opt_out' => (int) $before->opt_out ), array( 'opt_out' => $val ? 1 : 0 ) );
		}
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
		return self::trash( $id );
	}

	public static function trash( $id ) {
		global $wpdb;
		$id = (int) $id;
		$c  = self::get( $id );
		if ( ! $c ) {
			return false;
		}
		$wpdb->update( self::table(), array(
			'deleted_at' => current_time( 'mysql' ),
			'deleted_by' => SZC_Auth::actor_id(),
			'updated_at' => current_time( 'mysql' ),
		), array( 'id' => $id ) );
		if ( class_exists( 'SZC_SMS' ) ) {
			SZC_SMS::cancel_queued_for_contact( $id );
		}
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'trash', 'contact', $id, 'مخاطب به سطل زباله منتقل شد', $c, null );
		}
		return true;
	}

	public static function restore( $id ) {
		global $wpdb;
		$c = self::get_any( $id );
		if ( ! $c || ! $c->deleted_at ) {
			return false;
		}
		$wpdb->update( self::table(), array( 'deleted_at' => null, 'deleted_by' => 0, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => (int) $id ) );
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'restore', 'contact', $id, 'مخاطب از سطل زباله بازیابی شد' );
		}
		return true;
	}

	public static function permanent_delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		$c  = self::get_any( $id );
		if ( ! $c || ! $c->deleted_at ) {
			return false;
		}
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'delete_permanent', 'contact', $id, 'مخاطب برای همیشه حذف شد', $c, null );
		}
		$wpdb->delete( self::table(), array( 'id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_notes', array( 'contact_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_activities', array( 'contact_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_sms_queue', array( 'contact_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'szc_enrollments', array( 'contact_id' => $id ) );
		return true;
	}

	/** متغیرهای قابل‌استفاده در قالب پیامک برای یک مخاطب. */
	public static function vars( $c ) {
		$s    = SZC_Settings::all();
		$last = trim( (string) $c->last_name );
		if ( $last === '' ) {
			// قالب‌های پیش‌فرض با نام خانوادگی خطاب می‌کنند؛ برای داده‌ی ناقص خطاب خالی نماند.
			$last = trim( (string) $c->first_name );
		}
		$vars = array(
			'name'    => self::full_name( $c ),
			'first'   => (string) $c->first_name,
			'last'    => $last,
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
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'merge', 'contact', $primary_id, 'مخاطب تکراری ادغام شد', $s, self::get( $primary_id ) );
		}
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
			 WHERE deleted_at IS NULL AND TRIM(CONCAT(first_name,last_name))<>''
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
