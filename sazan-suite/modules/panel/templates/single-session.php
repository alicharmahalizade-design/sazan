<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var int $session_id @var int $course_id @var int $user_id */
if ( ! get_post( $session_id ) ) {
	echo '<div class="szp"><div class="szp-empty">جلسه یافت نشد.</div></div>';
	return;
}
$out  = '<div class="szp szp-skin-neon szp-session">';
$out .= SZP_Render::skin_switch();
$out .= '<a class="szp-back" href="' . SZP_Frontend::url_course( $course_id ) . '">‹ ' . esc_html( szp_ui_text( 'back_course', 'بازگشت به دوره' ) ) . '</a>';

$hero      = SZP_Render::session_hero( $session_id );
$countdown = SZP_Render::session_countdown( $session_id );
$identity  = SZP_Render::session_identity( $session_id );
$pack      = SZP_Render::session_pack( $session_id );
$task      = SZP_Render::session_task( $session_id, $user_id );
$checklist = SZP_Render::session_checklist( $session_id, $user_id );
$survey    = SZP_Render::session_survey( $session_id, $user_id );

// two-column row helper: spans full when only one section is present, skipped when empty
$row = function ( $a, $b ) {
	$cells = '';
	if ( $a !== '' ) { $cells .= '<div class="szp-scell">' . $a . '</div>'; }
	if ( $b !== '' ) { $cells .= '<div class="szp-scell">' . $b . '</div>'; }
	return $cells === '' ? '' : '<div class="szp-srow">' . $cells . '</div>';
};

$out .= '<div class="szp-session-grid">';
$out .= '<div class="szp-sfull">' . $hero . '</div>';          // ردیف ۱: جلسه و عنوان
if ( $countdown !== '' ) { $out .= '<div class="szp-sfull">' . $countdown . '</div>'; }
$out .= $row( $pack, $task );                                  // ردیف ۲: توشه / تکلیف
$out .= $row( $identity, $survey );                            // ردیف ۳: شناسنامه / نظرسنجی
if ( $checklist !== '' ) { $out .= '<div class="szp-sfull">' . $checklist . '</div>'; } // ردیف ۴: چک‌لیست
$out .= '</div>';
$out .= '</div>';
echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
