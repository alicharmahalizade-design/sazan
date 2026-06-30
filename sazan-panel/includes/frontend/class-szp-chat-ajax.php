<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** اتاق گفتگو — endpointهای AJAX کاربر. */
class SZP_Chat_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_chat_state',   array( __CLASS__, 'state' ) );
		add_action( 'wp_ajax_szp_chat_send',    array( __CLASS__, 'send' ) );
		add_action( 'wp_ajax_szp_chat_join',    array( __CLASS__, 'join' ) );
		add_action( 'wp_ajax_szp_chat_leave',   array( __CLASS__, 'leave' ) );
		add_action( 'wp_ajax_szp_chat_vote',    array( __CLASS__, 'vote' ) );
		add_action( 'wp_ajax_szp_chat_profile', array( __CLASS__, 'profile' ) );
		add_action( 'wp_ajax_szp_chat_role',    array( __CLASS__, 'role' ) );
	}

	/** ورود + nonce + دسترسی به دوره. برمی‌گرداند: [user_id, course_id]. */
	protected static function guard() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
		$uid = get_current_user_id();
		$cid = isset( $_REQUEST['course_id'] ) ? absint( $_REQUEST['course_id'] ) : 0;
		if ( ! $cid || ! SZP_Access::user_can_course( $uid, $cid ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی به این دوره.' ), 403 );
		}
		return array( $uid, $cid );
	}

	/* ---------------- state (polling) ---------------- */

	public static function state() {
		list( $uid, $cid ) = self::guard();
		wp_send_json_success( self::build_state( $uid, $cid ) );
	}

	public static function build_state( $uid, $cid ) {
		$room = SZP_Chat::get_room( $cid );
		$out  = array(
			'phase'        => 'pending',
			'course_title' => get_the_title( $cid ),
		);

		if ( ! $room || $room->status === 'pending' ) {
			return $out;
		}

		// مرحله گفتگوی آزاد
		if ( $room->status === 'free' ) {
			$open = SZP_Chat::free_open( $room );
			$out['phase']    = $open ? 'free' : 'free_closed';
			$out['deadline'] = $room->free_deadline ? szp_ts_from_datetime( $room->free_deadline ) * 1000 : 0;
			$out['free_msgs'] = self::msgs_payload( SZP_Chat::messages( $cid, 'free', 0, 0, 200 ), $uid );
			return $out;
		}

		// مرحله گروه‌بندی توسط کاربر
		if ( $room->status === 'grouping' ) {
			$out['phase']  = 'grouping';
			$out['groups'] = self::groups_payload( $room->id, $uid );
			$mine          = SZP_Chat::user_group( $cid, $uid );
			$out['my_group_id'] = $mine ? (int) $mine->id : 0;
			return $out;
		}

		// مرحله نهایی → چرخه‌ی گروهِ کاربر
		$mine = SZP_Chat::user_group( $cid, $uid );
		if ( ! $mine ) {
			$out['phase'] = 'no_group';
			return $out;
		}
		$mine = SZP_Chat::resolve_group( $mine );

		$members = SZP_Chat::group_members( $mine->id );
		$mem_out = array();
		foreach ( $members as $m ) {
			$mem_out[] = array(
				'id'     => (int) $m->user_id,
				'name'   => SZP_Chat::user_label( $m->user_id ),
				'avatar' => SZP_Chat::user_avatar( $m->user_id ),
				'role'   => $m->role,
			);
		}

		$grp = array(
			'id'        => (int) $mine->id,
			'name'      => $mine->name,
			'mission'   => $mine->mission,
			'slogan'    => $mine->slogan,
			'logo'      => $mine->logo_id ? wp_get_attachment_image_url( $mine->logo_id, 'thumbnail' ) : '',
			'leader_id' => (int) $mine->leader_id,
			'leader'    => $mine->leader_id ? SZP_Chat::user_label( $mine->leader_id ) : '',
			'is_leader' => ( (int) $mine->leader_id === (int) $uid ),
			'members'   => $mem_out,
		);

		if ( $mine->status === 'voting' ) {
			$out['phase']         = 'group_voting';
			$grp['vote_deadline'] = $mine->vote_deadline ? szp_ts_from_datetime( $mine->vote_deadline ) * 1000 : 0;
			$grp['tally']         = SZP_Chat::vote_tally( $mine->id );
			$grp['my_vote']       = SZP_Chat::my_vote( $mine->id, $uid );
		} else {
			$out['phase']     = 'group_active';
			$out['grp_msgs']  = self::msgs_payload( SZP_Chat::messages( $cid, 'group', $mine->id, 0, 200 ), $uid );
		}

		$out['group'] = $grp;
		return $out;
	}

	protected static function groups_payload( $room_id, $uid ) {
		$groups = SZP_Chat::groups( $room_id );
		$data   = array();
		foreach ( $groups as $g ) {
			$members = SZP_Chat::group_members( $g->id );
			$mem     = array();
			$is_mine = false;
			foreach ( $members as $m ) {
				if ( (int) $m->user_id === (int) $uid ) {
					$is_mine = true;
				}
				$mem[] = array(
					'id'     => (int) $m->user_id,
					'name'   => SZP_Chat::user_label( $m->user_id ),
					'avatar' => SZP_Chat::user_avatar( $m->user_id ),
				);
			}
			$data[] = array(
				'id'      => (int) $g->id,
				'idx'     => (int) $g->idx,
				'name'    => $g->name,
				'count'   => count( $mem ),
				'members' => $mem,
				'is_mine' => $is_mine,
			);
		}
		return $data;
	}

	protected static function msgs_payload( $rows, $uid ) {
		$out = array();
		foreach ( $rows as $r ) {
			$file_url = $name = $is_img = '';
			if ( $r->file_id ) {
				$file_url = wp_get_attachment_url( $r->file_id );
				$name     = get_the_title( $r->file_id );
				$is_img   = (bool) wp_attachment_is_image( $r->file_id );
			}
			$out[] = array(
				'id'       => (int) $r->id,
				'user_id'  => (int) $r->user_id,
				'name'     => SZP_Chat::user_label( $r->user_id ),
				'avatar'   => SZP_Chat::user_avatar( $r->user_id ),
				'mine'     => ( (int) $r->user_id === (int) $uid ),
				'content'  => $r->content,
				'file_url' => $file_url ? $file_url : '',
				'file_name'=> $name,
				'is_image' => (bool) $is_img,
				'time'     => szp_format_datetime( szp_ts_from_datetime( $r->created_at ) ),
			);
		}
		return $out;
	}

	/* ---------------- send message ---------------- */

	public static function send() {
		list( $uid, $cid ) = self::guard();
		$room = SZP_Chat::get_room( $cid );
		if ( ! $room ) {
			wp_send_json_error( array( 'msg' => 'اتاق گفتگو فعال نیست.' ) );
		}

		$scope    = ( isset( $_POST['scope'] ) && $_POST['scope'] === 'group' ) ? 'group' : 'free';
		$content  = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$group_id = 0;

		if ( $scope === 'free' ) {
			if ( ! SZP_Chat::free_open( $room ) ) {
				wp_send_json_error( array( 'msg' => 'گفتگوی آزاد باز نیست.' ) );
			}
		} else {
			$grp = SZP_Chat::user_group( $cid, $uid );
			if ( ! $grp ) {
				wp_send_json_error( array( 'msg' => 'شما عضو گروهی نیستید.' ) );
			}
			$grp = SZP_Chat::resolve_group( $grp );
			if ( $grp->status !== 'active' ) {
				wp_send_json_error( array( 'msg' => 'چت گروه پس از تعیین سرگروه باز می‌شود.' ) );
			}
			$group_id = (int) $grp->id;
		}

		$file_id = 0;
		if ( ! empty( $_FILES['file']['name'] ) ) {
			$file_id = self::handle_upload( $uid );
			if ( is_wp_error( $file_id ) ) {
				wp_send_json_error( array( 'msg' => $file_id->get_error_message() ) );
			}
		}
		if ( trim( wp_strip_all_tags( $content ) ) === '' && ! $file_id ) {
			wp_send_json_error( array( 'msg' => 'پیام یا فایل را وارد کنید.' ) );
		}

		SZP_Chat::add_message( $cid, $scope, $group_id, $uid, $content, $file_id );
		wp_send_json_success();
	}

	protected static function handle_upload( $uid ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed = array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'webp' );
		$name    = sanitize_file_name( $_FILES['file']['name'] );
		$ext     = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, $allowed, true ) ) {
			return new WP_Error( 'type', 'فرمت فایل مجاز نیست.' );
		}
		if ( (int) $_FILES['file']['size'] > 15 * 1024 * 1024 ) {
			return new WP_Error( 'size', 'حجم فایل بیش از ۱۵ مگابایت است.' );
		}
		$file = wp_handle_upload( $_FILES['file'], array( 'test_form' => false ) );
		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'upload', $file['error'] );
		}
		$id = wp_insert_attachment( array(
			'post_mime_type' => $file['type'],
			'post_title'     => $name,
			'post_status'    => 'inherit',
			'post_author'    => $uid,
		), $file['file'] );
		if ( ! $id ) {
			return new WP_Error( 'attach', 'خطا در ذخیره فایل.' );
		}
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file['file'] ) );
		return (int) $id;
	}

	/* ---------------- grouping actions ---------------- */

	public static function join() {
		list( $uid, $cid ) = self::guard();
		$room = SZP_Chat::get_room( $cid );
		if ( ! $room || $room->status !== 'grouping' ) {
			wp_send_json_error( array( 'msg' => 'مرحله گروه‌بندی فعال نیست.' ) );
		}
		$gid = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
		if ( ! SZP_Chat::join_group( $cid, $gid, $uid ) ) {
			wp_send_json_error( array( 'msg' => 'گروه نامعتبر است.' ) );
		}
		wp_send_json_success( self::build_state( $uid, $cid ) );
	}

	public static function leave() {
		list( $uid, $cid ) = self::guard();
		$room = SZP_Chat::get_room( $cid );
		if ( ! $room || $room->status !== 'grouping' ) {
			wp_send_json_error( array( 'msg' => 'امکان ترک گروه پس از نهایی‌سازی نیست.' ) );
		}
		SZP_Chat::leave_group( $cid, $uid );
		wp_send_json_success( self::build_state( $uid, $cid ) );
	}

	/* ---------------- voting ---------------- */

	public static function vote() {
		list( $uid, $cid ) = self::guard();
		$grp = SZP_Chat::user_group( $cid, $uid );
		if ( ! $grp ) {
			wp_send_json_error( array( 'msg' => 'شما عضو گروهی نیستید.' ) );
		}
		$grp = SZP_Chat::resolve_group( $grp );
		if ( $grp->status !== 'voting' ) {
			wp_send_json_error( array( 'msg' => 'مهلت رای‌گیری پایان یافته است.' ) );
		}
		$cand = isset( $_POST['candidate_id'] ) ? absint( $_POST['candidate_id'] ) : 0;
		if ( ! SZP_Chat::cast_vote( $grp->id, $uid, $cand ) ) {
			wp_send_json_error( array( 'msg' => 'رای نامعتبر است (به خود نمی‌توان رای داد).' ) );
		}
		wp_send_json_success( self::build_state( $uid, $cid ) );
	}

	/* ---------------- group profile + roles ---------------- */

	public static function profile() {
		list( $uid, $cid ) = self::guard();
		$grp = SZP_Chat::user_group( $cid, $uid );
		if ( ! $grp ) {
			wp_send_json_error( array( 'msg' => 'شما عضو گروهی نیستید.' ), 403 );
		}
		$logo_id = null;
		if ( ! empty( $_FILES['logo']['name'] ) ) {
			$tmp = $_FILES['file'] ?? null;
			$_FILES['file'] = $_FILES['logo'];
			$res = self::handle_upload( $uid );
			$_FILES['file'] = $tmp;
			if ( is_wp_error( $res ) ) {
				wp_send_json_error( array( 'msg' => $res->get_error_message() ) );
			}
			$logo_id = (int) $res;
		}
		SZP_Chat::update_group_profile(
			$grp->id,
			isset( $_POST['name'] ) ? wp_unslash( $_POST['name'] ) : null,
			isset( $_POST['mission'] ) ? wp_unslash( $_POST['mission'] ) : null,
			isset( $_POST['slogan'] ) ? wp_unslash( $_POST['slogan'] ) : null,
			$logo_id
		);
		wp_send_json_success( self::build_state( $uid, $cid ) );
	}

	/** فقط سرگروه نقش اعضا را تعیین می‌کند. */
	public static function role() {
		list( $uid, $cid ) = self::guard();
		$grp = SZP_Chat::user_group( $cid, $uid );
		if ( ! $grp || (int) $grp->leader_id !== (int) $uid ) {
			wp_send_json_error( array( 'msg' => 'فقط سرگروه می‌تواند نقش تعیین کند.' ), 403 );
		}
		$member = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
		$role   = isset( $_POST['role'] ) ? wp_unslash( $_POST['role'] ) : '';
		SZP_Chat::set_member_role( $grp->id, $member, $role );
		wp_send_json_success( self::build_state( $uid, $cid ) );
	}
}
