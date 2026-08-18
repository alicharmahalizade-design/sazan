<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * اتاق گفتگو — chat-room data + lifecycle.
 *
 * Room status: pending → free → grouping → final
 *   pending  : ساخته نشده / شروع نشده
 *   free     : گفتگوی آزاد باز (تا free_deadline). پس از مهلت، آزاد بسته است ولی هنوز گروه‌بندی نشده.
 *   grouping : گفتگوی آزاد آرشیو شد، مدیریت ظرفیت گروه‌ها را تعیین کرد، کاربران خود را عضو می‌کنند.
 *   final    : گروه‌بندی نهایی شد؛ هر گروه چرخه‌ی رای‌گیری → فعال دارد.
 *
 * Group status: voting → active
 *   voting : رای‌گیری سرگروه در جریان (تا vote_deadline). چت قفل.
 *   active : سرگروه مشخص شد، چت گروه باز است.
 */
class SZP_Chat {

	public static function t_room()  { global $wpdb; return $wpdb->prefix . 'szp_chat_rooms'; }
	public static function t_grp()   { global $wpdb; return $wpdb->prefix . 'szp_chat_groups'; }
	public static function t_mem()   { global $wpdb; return $wpdb->prefix . 'szp_chat_group_members'; }
	public static function t_msg()   { global $wpdb; return $wpdb->prefix . 'szp_chat_messages'; }
	public static function t_vote()  { global $wpdb; return $wpdb->prefix . 'szp_chat_votes'; }

	/* ---------------- room ---------------- */

	public static function get_room( $course_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t_room() . ' WHERE course_id=%d', (int) $course_id ) );
	}

	/** Ensure a room row exists for the course; returns the row. */
	public static function ensure_room( $course_id ) {
		global $wpdb;
		$room = self::get_room( $course_id );
		if ( $room ) {
			return $room;
		}
		$wpdb->insert( self::t_room(), array(
			'course_id'  => (int) $course_id,
			'status'     => 'pending',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		), array( '%d', '%s', '%s', '%s' ) );
		return self::get_room( $course_id );
	}

	public static function update_room( $course_id, $data, $fmt ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );
		$fmt[]              = '%s';
		$wpdb->update( self::t_room(), $data, array( 'course_id' => (int) $course_id ), $fmt, array( '%d' ) );
	}

	/** Is the free chat currently open (status free AND before deadline)? */
	public static function free_open( $room ) {
		if ( ! $room || $room->status !== 'free' ) {
			return false;
		}
		$dl = szp_ts_from_datetime( $room->free_deadline );
		return ( ! $dl || time() < $dl );
	}

	/* ---------------- admin lifecycle ---------------- */

	/** مدیریت: شروع گفتگوی آزاد با مهلت مشخص. */
	public static function start_free( $course_id, $deadline ) {
		self::ensure_room( $course_id );
		self::update_room( $course_id, array(
			'status'        => 'free',
			'free_deadline' => $deadline ? $deadline : null,
			'group_count'   => 0,
		), array( '%s', '%s', '%d' ) );
	}

	/** مدیریت: بستن آزاد و ساخت N گروه خالی. */
	public static function close_free_make_groups( $course_id, $count ) {
		global $wpdb;
		$count = max( 1, min( 20, (int) $count ) );
		$room  = self::ensure_room( $course_id );
		$title = get_the_title( $course_id );

		$wpdb->delete( self::t_grp(), array( 'room_id' => (int) $room->id ), array( '%d' ) );
		$wpdb->delete( self::t_mem(), array( 'course_id' => (int) $course_id ), array( '%d' ) );

		$names = array( 1 => 'یک', 2 => 'دو', 3 => 'سه', 4 => 'چهار', 5 => 'پنج', 6 => 'شش', 7 => 'هفت', 8 => 'هشت', 9 => 'نه', 10 => 'ده' );
		for ( $i = 1; $i <= $count; $i++ ) {
			$word = isset( $names[ $i ] ) ? $names[ $i ] : $i;
			$wpdb->insert( self::t_grp(), array(
				'room_id'    => (int) $room->id,
				'course_id'  => (int) $course_id,
				'idx'        => $i,
				'name'       => sprintf( 'گروه %s دوره %s', $word, $title ),
				'status'     => 'voting',
				'created_at' => current_time( 'mysql' ),
			), array( '%d', '%d', '%d', '%s', '%s', '%s' ) );
		}
		self::update_room( $course_id, array( 'status' => 'grouping', 'group_count' => $count ), array( '%s', '%d' ) );
	}

	/** مدیریت: نهایی‌سازی گروه‌بندی و شروع رای‌گیری سرگروه با مهلت. */
	public static function finalize( $course_id, $vote_deadline ) {
		global $wpdb;
		$room = self::get_room( $course_id );
		if ( ! $room ) {
			return;
		}
		$wpdb->query( $wpdb->prepare(
			'UPDATE ' . self::t_grp() . " SET status='voting', vote_deadline=%s, leader_id=0 WHERE room_id=%d",
			$vote_deadline ? $vote_deadline : null, (int) $room->id ) );
		self::update_room( $course_id, array( 'status' => 'final' ), array( '%s' ) );
	}

	/** مدیریت: بازنشانی کامل اتاق. */
	public static function reset( $course_id ) {
		global $wpdb;
		$room = self::get_room( $course_id );
		if ( $room ) {
			$wpdb->delete( self::t_grp(),  array( 'room_id' => (int) $room->id ), array( '%d' ) );
		}
		$wpdb->delete( self::t_mem(),  array( 'course_id' => (int) $course_id ), array( '%d' ) );
		$wpdb->delete( self::t_msg(),  array( 'course_id' => (int) $course_id ), array( '%d' ) );
		if ( $room ) {
			$wpdb->query( $wpdb->prepare( 'DELETE v FROM ' . self::t_vote() . ' v JOIN ' . self::t_grp() . ' g ON v.group_id=g.id WHERE g.room_id=%d', (int) $room->id ) );
		}
		self::update_room( $course_id, array( 'status' => 'pending', 'free_deadline' => null, 'group_count' => 0 ), array( '%s', '%s', '%d' ) );
	}

	/* ---------------- groups ---------------- */

	public static function groups( $room_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_grp() . ' WHERE room_id=%d ORDER BY idx ASC', (int) $room_id ) );
	}

	public static function get_group( $group_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t_grp() . ' WHERE id=%d', (int) $group_id ) );
	}

	public static function group_members( $group_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT user_id, role FROM ' . self::t_mem() . ' WHERE group_id=%d ORDER BY id ASC', (int) $group_id ) );
	}

	public static function group_count_members( $group_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::t_mem() . ' WHERE group_id=%d', (int) $group_id ) );
	}

	/** گروه کاربر در این دوره (یا null). */
	public static function user_group( $course_id, $user_id ) {
		global $wpdb;
		$gid = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT group_id FROM ' . self::t_mem() . ' WHERE course_id=%d AND user_id=%d', (int) $course_id, (int) $user_id ) );
		return $gid ? self::get_group( $gid ) : null;
	}

	/** عضویت کاربر در گروه (هر کاربر فقط یک گروه در هر دوره). */
	public static function join_group( $course_id, $group_id, $user_id ) {
		global $wpdb;
		$grp = self::get_group( $group_id );
		if ( ! $grp || (int) $grp->course_id !== (int) $course_id ) {
			return false;
		}
		$wpdb->query( $wpdb->prepare(
			'INSERT INTO ' . self::t_mem() . ' (group_id,course_id,user_id,joined_at) VALUES (%d,%d,%d,%s)
			 ON DUPLICATE KEY UPDATE group_id=VALUES(group_id), joined_at=VALUES(joined_at), role=\'\'',
			(int) $group_id, (int) $course_id, (int) $user_id, current_time( 'mysql' ) ) );
		// ترک گروه ⇒ رای‌های قبلی نامعتبر
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::t_vote() . ' WHERE voter_id=%d AND group_id<>%d', (int) $user_id, (int) $group_id ) );
		return true;
	}

	public static function leave_group( $course_id, $user_id ) {
		global $wpdb;
		$grp = self::user_group( $course_id, $user_id );
		$wpdb->delete( self::t_mem(), array( 'course_id' => (int) $course_id, 'user_id' => (int) $user_id ), array( '%d', '%d' ) );
		if ( $grp ) {
			$wpdb->delete( self::t_vote(), array( 'group_id' => (int) $grp->id, 'voter_id' => (int) $user_id ), array( '%d', '%d' ) );
		}
	}

	public static function update_group_profile( $group_id, $name, $mission, $slogan, $logo_id ) {
		global $wpdb;
		$data = array();
		$fmt  = array();
		if ( $name !== null )    { $data['name']    = sanitize_text_field( $name );  $fmt[] = '%s'; }
		if ( $mission !== null ) { $data['mission'] = wp_kses_post( $mission );       $fmt[] = '%s'; }
		if ( $slogan !== null )  { $data['slogan']  = sanitize_text_field( $slogan ); $fmt[] = '%s'; }
		if ( $logo_id !== null ) { $data['logo_id'] = (int) $logo_id;                 $fmt[] = '%d'; }
		if ( $data ) {
			$wpdb->update( self::t_grp(), $data, array( 'id' => (int) $group_id ), $fmt, array( '%d' ) );
		}
	}

	public static function set_member_role( $group_id, $user_id, $role ) {
		global $wpdb;
		$wpdb->update( self::t_mem(), array( 'role' => sanitize_text_field( $role ) ),
			array( 'group_id' => (int) $group_id, 'user_id' => (int) $user_id ), array( '%s' ), array( '%d', '%d' ) );
	}

	/* ---------------- voting ---------------- */

	public static function cast_vote( $group_id, $voter_id, $candidate_id ) {
		global $wpdb;
		if ( (int) $voter_id === (int) $candidate_id ) {
			return false; // به خود رای نمی‌توان داد
		}
		// کاندیدا باید هم‌گروه باشد
		$ok = $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . self::t_mem() . ' WHERE group_id=%d AND user_id=%d', (int) $group_id, (int) $candidate_id ) );
		if ( ! $ok ) {
			return false;
		}
		$wpdb->query( $wpdb->prepare(
			'INSERT INTO ' . self::t_vote() . ' (group_id,voter_id,candidate_id,created_at) VALUES (%d,%d,%d,%s)
			 ON DUPLICATE KEY UPDATE candidate_id=VALUES(candidate_id), created_at=VALUES(created_at)',
			(int) $group_id, (int) $voter_id, (int) $candidate_id, current_time( 'mysql' ) ) );
		return true;
	}

	/** شمارش آرا: [candidate_id => count]. */
	public static function vote_tally( $group_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT candidate_id, COUNT(*) c FROM ' . self::t_vote() . ' WHERE group_id=%d GROUP BY candidate_id', (int) $group_id ) );
		$out = array();
		foreach ( $rows as $r ) {
			$out[ (int) $r->candidate_id ] = (int) $r->c;
		}
		return $out;
	}

	public static function my_vote( $group_id, $voter_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT candidate_id FROM ' . self::t_vote() . ' WHERE group_id=%d AND voter_id=%d', (int) $group_id, (int) $voter_id ) );
	}

	/** بالاترین رای؛ مساوی ⇒ کاربری که زودتر رای آورده/کوچک‌ترین شناسه. */
	public static function vote_winner( $group_id ) {
		$tally = self::vote_tally( $group_id );
		if ( ! $tally ) {
			return 0;
		}
		arsort( $tally );
		$max  = max( $tally );
		$best = array_keys( $tally, $max, true );
		sort( $best );
		return (int) $best[0];
	}

	/**
	 * چرخه‌ی گروه را بر اساس مهلت رای‌گیری حل می‌کند (تنبل، هنگام خواندن).
	 * اگر مهلت گذشته و گروه هنوز در رای‌گیری است: سرگروه = بیشترین رای، گروه فعال می‌شود.
	 * برمی‌گرداند: رکورد گروهِ به‌روزشده.
	 */
	public static function resolve_group( $group ) {
		if ( ! $group || $group->status !== 'voting' ) {
			return $group;
		}
		$dl = szp_ts_from_datetime( $group->vote_deadline );
		if ( $dl && time() >= $dl ) {
			$winner = self::vote_winner( $group->id );
			if ( ! $winner ) {
				// رایی ثبت نشده ⇒ اولین عضو سرگروه می‌شود
				$mem    = self::group_members( $group->id );
				$winner = $mem ? (int) $mem[0]->user_id : 0;
			}
			self::set_leader( $group->id, $winner );
			$group = self::get_group( $group->id );
		}
		return $group;
	}

	/** مدیریت/سیستم: تعیین سرگروه و فعال‌سازی چت گروه. */
	public static function set_leader( $group_id, $user_id ) {
		global $wpdb;
		$wpdb->update( self::t_grp(),
			array( 'leader_id' => (int) $user_id, 'status' => 'active' ),
			array( 'id' => (int) $group_id ), array( '%d', '%s' ), array( '%d' ) );
	}

	/* ---------------- messages ---------------- */

	public static function add_message( $course_id, $scope, $group_id, $user_id, $content, $file_id = 0 ) {
		global $wpdb;
		$wpdb->insert( self::t_msg(), array(
			'course_id'  => (int) $course_id,
			'scope'      => ( $scope === 'group' ) ? 'group' : 'free',
			'group_id'   => (int) $group_id,
			'user_id'    => (int) $user_id,
			'content'    => $content,
			'file_id'    => (int) $file_id,
			'created_at' => current_time( 'mysql' ),
		), array( '%d', '%s', '%d', '%d', '%s', '%d', '%s' ) );
		return (int) $wpdb->insert_id;
	}

	/** پیام‌ها پس از after_id (برای پولینگ). */
	public static function messages( $course_id, $scope, $group_id, $after_id = 0, $limit = 100 ) {
		global $wpdb;
		$scope = ( $scope === 'group' ) ? 'group' : 'free';
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_msg() . ' WHERE course_id=%d AND scope=%s AND group_id=%d AND id>%d ORDER BY id ASC LIMIT %d',
			(int) $course_id, $scope, (int) $group_id, (int) $after_id, (int) $limit ) );
	}

	/* ---------------- helpers ---------------- */

	public static function user_label( $uid ) {
		$u = get_userdata( $uid );
		return $u ? $u->display_name : ( 'کاربر #' . (int) $uid );
	}

	public static function user_avatar( $uid ) {
		return get_avatar_url( $uid, array( 'size' => 64 ) );
	}
}
