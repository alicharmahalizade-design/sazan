<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Logged-in AJAX for assignment submission, checklist, and survey. */
class SZP_Front_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_submit_task', array( __CLASS__, 'submit_task' ) );
		add_action( 'wp_ajax_szp_save_checklist', array( __CLASS__, 'save_checklist' ) );
		add_action( 'wp_ajax_szp_submit_survey', array( __CLASS__, 'submit_survey' ) );
	}

	/** Verify login, nonce, and that the user can access the session's course. Returns user id. */
	protected static function guard_session( $session_id ) {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
		$uid = get_current_user_id();
		$cid = (int) get_post_meta( $session_id, '_szp_course_id', true );
		if ( ! $cid || ! SZP_Access::user_can_course( $uid, $cid ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی.' ), 403 );
		}
		return $uid;
	}

	public static function submit_task() {
		$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
		$uid        = self::guard_session( $session_id );
		$content    = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$file_id    = 0;

		if ( ! empty( $_FILES['file']['name'] ) ) {
			$file_id = self::handle_upload( $uid );
			if ( is_wp_error( $file_id ) ) {
				wp_send_json_error( array( 'msg' => $file_id->get_error_message() ) );
			}
		}
		if ( $content === '' && ! $file_id ) {
			wp_send_json_error( array( 'msg' => 'متن پاسخ یا فایل را وارد کنید.' ) );
		}

		SZP_Data::save_submission( $session_id, $uid, $content, $file_id );
		wp_send_json_success( array( 'msg' => 'پاسخ شما با موفقیت ثبت شد.' ) );
	}

	protected static function handle_upload( $uid ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed = array( 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png' );
		$name    = isset( $_FILES['file']['name'] ) ? sanitize_file_name( $_FILES['file']['name'] ) : '';
		$ext     = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, $allowed, true ) ) {
			return new WP_Error( 'type', 'فرمت فایل مجاز نیست.' );
		}
		if ( (int) $_FILES['file']['size'] > 15 * 1024 * 1024 ) {
			return new WP_Error( 'size', 'حجم فایل بیش از حد مجاز (۱۵ مگابایت) است.' );
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

	public static function save_checklist() {
		$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
		$uid        = self::guard_session( $session_id );
		$index      = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : 0;
		$done       = ! empty( $_POST['done'] );
		SZP_Data::set_checklist_item( $session_id, $uid, $index, $done );
		wp_send_json_success();
	}

	public static function submit_survey() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
		$uid     = get_current_user_id();
		$context = ( isset( $_POST['context'] ) && $_POST['context'] === 'course' ) ? 'course' : 'session';
		$object  = isset( $_POST['object_id'] ) ? absint( $_POST['object_id'] ) : 0;
		$answers = isset( $_POST['answers'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['answers'] ) ) : array();

		$cid = ( $context === 'session' ) ? (int) get_post_meta( $object, '_szp_course_id', true ) : $object;
		if ( ! $cid || ! SZP_Access::user_can_course( $uid, $cid ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی.' ), 403 );
		}

		SZP_Data::save_survey( $context, $object, $uid, $answers );
		wp_send_json_success( array( 'msg' => 'نظر شما ثبت شد. سپاسگزاریم.' ) );
	}
}
