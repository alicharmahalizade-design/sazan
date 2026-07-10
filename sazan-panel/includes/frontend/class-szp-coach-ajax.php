<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** کوچینگ کسب‌وکار — endpointهای AJAX (دانشجو و کوچ). */
class SZP_Coach_Ajax {

	public static function init() {
		// student
		add_action( 'wp_ajax_szp_coach_save_scan',   array( __CLASS__, 'save_scan' ) );
		add_action( 'wp_ajax_szp_coach_submit_form', array( __CLASS__, 'submit_form' ) );
		add_action( 'wp_ajax_szp_coach_record',      array( __CLASS__, 'record_week' ) );
		add_action( 'wp_ajax_szp_coach_toggle',      array( __CLASS__, 'toggle_item' ) );
		// coach
		add_action( 'wp_ajax_szp_coach_set_kpis',    array( __CLASS__, 'set_kpis' ) );
		add_action( 'wp_ajax_szp_coach_set_week',    array( __CLASS__, 'set_week' ) );
		add_action( 'wp_ajax_szp_coach_new_week',    array( __CLASS__, 'new_week' ) );
	}

	/* ---------------- guards ---------------- */

	protected static function base() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
		return get_current_user_id();
	}

	/** سفر دانشجوی فعلی برای دوره مشخص. */
	protected static function student_journey() {
		$uid = self::base();
		$cid = isset( $_REQUEST['course_id'] ) ? absint( $_REQUEST['course_id'] ) : 0;
		if ( ! $cid || ! SZP_Coach::is_enabled( $cid ) || ! SZP_Access::user_can_course( $uid, $cid ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی به این برنامه کوچینگ.' ), 403 );
		}
		return SZP_Coach::ensure_journey( $cid, $uid );
	}

	/** سفر مورد مدیریت توسط کوچ (با journey_id). */
	protected static function coach_journey() {
		$uid = self::base();
		$jid = isset( $_REQUEST['journey_id'] ) ? absint( $_REQUEST['journey_id'] ) : 0;
		$j   = $jid ? SZP_Coach::get_journey_by_id( $jid ) : null;
		if ( ! $j || ! SZP_Coach::is_coach( $uid, $j->course_id ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی کوچ.' ), 403 );
		}
		return $j;
	}

	/* ---------------- student ---------------- */

	public static function save_scan() {
		$j   = self::student_journey();
		if ( $j->stage !== SZP_Coach::STAGE_SCAN ) {
			wp_send_json_error( array( 'msg' => 'این مرحله قبلاً ثبت شده است.' ) );
		}
		$raw  = isset( $_POST['scan'] ) ? (array) wp_unslash( $_POST['scan'] ) : array();
		$data = array(
			'biz_name'  => sanitize_text_field( $raw['biz_name'] ?? '' ),
			'industry'  => sanitize_text_field( $raw['industry'] ?? '' ),
			'biz_stage' => sanitize_text_field( $raw['biz_stage'] ?? '' ),
			'employees' => sanitize_text_field( $raw['employees'] ?? '' ),
			'swot'      => array(
				's' => sanitize_textarea_field( $raw['swot_s'] ?? '' ),
				'w' => sanitize_textarea_field( $raw['swot_w'] ?? '' ),
				'o' => sanitize_textarea_field( $raw['swot_o'] ?? '' ),
				't' => sanitize_textarea_field( $raw['swot_t'] ?? '' ),
			),
			'note'      => sanitize_textarea_field( $raw['note'] ?? '' ),
		);
		if ( $data['biz_name'] === '' ) {
			wp_send_json_error( array( 'msg' => 'نام کسب‌وکار را وارد کنید.' ) );
		}

		$kpis = isset( $_POST['kpis'] ) ? (array) wp_unslash( $_POST['kpis'] ) : array();
		$rows = array();
		foreach ( $kpis as $k ) {
			$rows[] = array(
				'name'      => sanitize_text_field( $k['name'] ?? '' ),
				'unit'      => sanitize_text_field( $k['unit'] ?? '' ),
				'direction' => ( ( $k['direction'] ?? 'up' ) === 'down' ) ? 'down' : 'up',
				'baseline'  => (float) ( $k['baseline'] ?? 0 ),
				'target'    => (float) ( $k['target'] ?? 0 ),
			);
		}
		$rows = array_filter( $rows, function ( $r ) { return $r['name'] !== ''; } );
		if ( count( $rows ) < 1 ) {
			wp_send_json_error( array( 'msg' => 'حداقل یک شاخص کلیدی (KPI) تعریف کنید.' ) );
		}

		SZP_Coach::save_scan( $j->id, $data );
		SZP_Coach::sync_kpis( $j, $rows );
		// اگر دوره فرم دارد → مرحله فرم، وگرنه مستقیم انتظار کوچ.
		$next = SZP_Coach::form_def( $j->course_id ) ? SZP_Coach::STAGE_FORM : SZP_Coach::STAGE_REVIEW;
		SZP_Coach::set_stage( $j->id, $next );
		wp_send_json_success( array( 'msg' => 'اسکن کسب‌وکار ثبت شد.', 'reload' => true ) );
	}

	public static function submit_form() {
		$j = self::student_journey();
		if ( $j->stage !== SZP_Coach::STAGE_FORM ) {
			wp_send_json_error( array( 'msg' => 'این مرحله در دسترس نیست.' ) );
		}
		$def = SZP_Coach::form_def( $j->course_id );
		$in  = isset( $_POST['answers'] ) ? (array) wp_unslash( $_POST['answers'] ) : array();
		$ans = array();
		foreach ( $def as $i => $field ) {
			$val = $in[ $i ] ?? '';
			$ans[ $i ] = ( $field['type'] === 'number' || $field['type'] === 'scale' )
				? (string) ( $val === '' ? '' : floatval( $val ) )
				: sanitize_textarea_field( $val );
		}
		SZP_Coach::save_answers( $j, $ans );
		SZP_Coach::set_stage( $j->id, SZP_Coach::STAGE_REVIEW );
		wp_send_json_success( array( 'msg' => 'پاسخ‌های فرم ثبت شد. منتظر بررسی کوچ بمانید.', 'reload' => true ) );
	}

	/** ثبت نتایج هفته جاری توسط دانشجو (KPIها + گزارش). */
	public static function record_week() {
		$j  = self::student_journey();
		$wn = isset( $_POST['week_no'] ) ? absint( $_POST['week_no'] ) : 0;
		$w  = SZP_Coach::get_week( $j->id, $wn );
		if ( ! $w || $w->status === 'draft' ) {
			wp_send_json_error( array( 'msg' => 'هفته معتبر نیست.' ) );
		}
		$kpis    = SZP_Coach::get_kpis( $j->id );
		$in      = isset( $_POST['metrics'] ) ? (array) wp_unslash( $_POST['metrics'] ) : array();
		$metrics = array();
		foreach ( $kpis as $k ) {
			if ( isset( $in[ $k->id ] ) && $in[ $k->id ] !== '' ) {
				$metrics[ $k->id ] = (string) floatval( $in[ $k->id ] );
			}
		}
		$report = isset( $_POST['report'] ) ? sanitize_textarea_field( wp_unslash( $_POST['report'] ) ) : '';
		$score  = SZP_Coach::week_growth( $kpis, $metrics );

		SZP_Coach::upsert_week( $j, $wn, array(
			'metrics'        => wp_json_encode( $metrics ),
			'student_report' => $report,
			'score'          => $score,
			'status'         => 'reported',
		) );
		wp_send_json_success( array( 'msg' => 'نتایج این هفته ثبت شد. شاخص رشد: ' . szp_fa_digits( $score ) . '٪', 'reload' => true ) );
	}

	/** علامت‌زدن اقدام/تکلیف توسط دانشجو. kind=action|task */
	public static function toggle_item() {
		$j    = self::student_journey();
		$wn   = isset( $_POST['week_no'] ) ? absint( $_POST['week_no'] ) : 0;
		$kind = ( isset( $_POST['kind'] ) && $_POST['kind'] === 'task' ) ? 'task' : 'action';
		$idx  = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : 0;
		$w    = SZP_Coach::get_week( $j->id, $wn );
		if ( ! $w ) { wp_send_json_error( array( 'msg' => 'هفته نامعتبر.' ) ); }

		if ( $kind === 'action' ) {
			$list = SZP_Coach::decode( $w->actions );
			if ( isset( $list[ $idx ] ) ) {
				$list[ $idx ]['done'] = empty( $list[ $idx ]['done'] ) ? 1 : 0;
				SZP_Coach::upsert_week( $j, $wn, array( 'actions' => wp_json_encode( $list ) ) );
			}
		} else {
			$list = SZP_Coach::decode( $w->tasks );
			$st   = isset( $_POST['st'] ) ? sanitize_key( $_POST['st'] ) : 'done';
			if ( isset( $list[ $idx ] ) ) {
				$list[ $idx ]['st'] = in_array( $st, array( 'todo', 'doing', 'done' ), true ) ? $st : 'done';
				SZP_Coach::upsert_week( $j, $wn, array( 'tasks' => wp_json_encode( $list ) ) );
			}
		}
		wp_send_json_success();
	}

	/* ---------------- coach ---------------- */

	public static function set_kpis() {
		$j    = self::coach_journey();
		$kpis = isset( $_POST['kpis'] ) ? (array) wp_unslash( $_POST['kpis'] ) : array();
		$rows = array();
		foreach ( $kpis as $k ) {
			$rows[] = array(
				'name'      => sanitize_text_field( $k['name'] ?? '' ),
				'unit'      => sanitize_text_field( $k['unit'] ?? '' ),
				'direction' => ( ( $k['direction'] ?? 'up' ) === 'down' ) ? 'down' : 'up',
				'baseline'  => (float) ( $k['baseline'] ?? 0 ),
				'target'    => (float) ( $k['target'] ?? 0 ),
			);
		}
		SZP_Coach::sync_kpis( $j, $rows );
		wp_send_json_success( array( 'msg' => 'شاخص‌ها به‌روزرسانی شد.', 'reload' => true ) );
	}

	/** تعریف/ویرایش اقدامات و تکالیف و بازخورد یک هفته توسط کوچ. */
	public static function set_week() {
		$j  = self::coach_journey();
		$wn = isset( $_POST['week_no'] ) ? absint( $_POST['week_no'] ) : 0;
		if ( $wn < 1 ) { wp_send_json_error( array( 'msg' => 'شماره هفته نامعتبر.' ) ); }

		$existing = SZP_Coach::get_week( $j->id, $wn );

		$actions_in = isset( $_POST['actions'] ) ? (array) wp_unslash( $_POST['actions'] ) : array();
		$actions    = array();
		foreach ( $actions_in as $i => $a ) {
			$t = sanitize_text_field( $a['t'] ?? '' );
			if ( $t === '' ) { continue; }
			$old_done = ( $existing ) ? ( SZP_Coach::decode( $existing->actions )[ $i ]['done'] ?? 0 ) : 0;
			$actions[] = array( 't' => $t, 'd' => sanitize_textarea_field( $a['d'] ?? '' ), 'done' => (int) $old_done );
		}

		$tasks_in = isset( $_POST['tasks'] ) ? (array) wp_unslash( $_POST['tasks'] ) : array();
		$tasks    = array();
		foreach ( $tasks_in as $i => $tk ) {
			$t = sanitize_text_field( $tk['t'] ?? '' );
			if ( $t === '' ) { continue; }
			$old_st = ( $existing ) ? ( SZP_Coach::decode( $existing->tasks )[ $i ]['st'] ?? 'todo' ) : 'todo';
			$tasks[] = array(
				't'   => $t,
				'd'   => sanitize_textarea_field( $tk['d'] ?? '' ),
				'due' => sanitize_text_field( $tk['due'] ?? '' ),
				'st'  => in_array( $old_st, array( 'todo', 'doing', 'done' ), true ) ? $old_st : 'todo',
			);
		}

		$fields = array(
			'title'          => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
			'session_at'     => sanitize_text_field( wp_unslash( $_POST['session_at'] ?? '' ) ),
			'actions'        => wp_json_encode( $actions ),
			'tasks'          => wp_json_encode( $tasks ),
			'coach_feedback' => sanitize_textarea_field( wp_unslash( $_POST['feedback'] ?? '' ) ),
		);
		// در صورت ابلاغ، وضعیت را فعال کن.
		if ( ! empty( $_POST['publish'] ) ) {
			$fields['status'] = ( $existing && $existing->status === 'reported' ) ? 'reported' : 'active';
		} elseif ( ! $existing ) {
			$fields['status'] = 'active';
		}

		SZP_Coach::upsert_week( $j, $wn, $fields );

		// اولین هفته → سفر را وارد چرخه هفتگی کن.
		if ( in_array( $j->stage, array( SZP_Coach::STAGE_REVIEW, SZP_Coach::STAGE_FORM, SZP_Coach::STAGE_SCAN ), true ) ) {
			SZP_Coach::set_stage( $j->id, SZP_Coach::STAGE_TRACKING );
		}
		wp_send_json_success( array( 'msg' => 'هفته ' . szp_fa_digits( $wn ) . ' ذخیره شد.', 'reload' => true ) );
	}

	/** ساخت هفته بعدی (کپی اختیاری اقدامات/تکالیف باز). */
	public static function new_week() {
		$j    = self::coach_journey();
		$last = SZP_Coach::last_week_no( $j->id );
		$next = $last + 1;
		SZP_Coach::upsert_week( $j, $next, array(
			'title'   => 'هفته ' . szp_fa_digits( $next ),
			'actions' => wp_json_encode( array() ),
			'tasks'   => wp_json_encode( array() ),
			'metrics' => wp_json_encode( array() ),
			'status'  => 'active',
		) );
		SZP_Coach::set_stage( $j->id, SZP_Coach::STAGE_TRACKING );
		wp_send_json_success( array( 'msg' => 'هفته ' . szp_fa_digits( $next ) . ' ایجاد شد.', 'reload' => true ) );
	}
}
