<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var int $course_id @var int $user_id */
if ( ! get_post( $course_id ) ) {
	echo '<div class="szp"><div class="szp-empty">دوره یافت نشد.</div></div>';
	return;
}

$identity = SZP_Render::course_identity( $course_id );
$schedule = SZP_Render::course_schedule( $course_id );
$ann      = SZP_Render::course_announcements( $course_id );
$surv     = SZP_Render::course_survey( $course_id, $user_id );
$files    = SZP_Render::course_files( $course_id );
$work     = SZP_Render::course_workbench( $course_id, $user_id );
$group    = SZP_Render::course_group( $user_id );

/*
 * One DOM, two layouts (toggled by skin class on .szp):
 *  - کلاسیک (glass): flex single column, original order (via CSS order).
 *  - مجله‌ای (neon): CSS grid areas → identity | (announce/survey) ، schedule full ، files | workbench ، group full.
 * Empty sections are omitted so neither layout shows gaps.
 */
$out  = '<div class="szp szp-skin-neon szp-course">';
$out .= SZP_Render::skin_switch();
$out .= '<a class="szp-back" href="' . SZP_Frontend::url_list() . '">‹ ' . esc_html( szp_ui_text( 'back_courses', 'بازگشت به دوره‌ها' ) ) . '</a>';
$out .= SZP_Render::course_hero( $course_id );
$out .= '<div class="szp-course-grid">';
$out .= '<div class="szp-area szp-area-identity">' . $identity . '</div>';
if ( $ann !== '' )  { $out .= '<div class="szp-area szp-area-announce">' . $ann . '</div>'; }
if ( $surv !== '' ) { $out .= '<div class="szp-area szp-area-survey">' . $surv . '</div>'; }
$out .= '<div class="szp-area szp-area-schedule">' . $schedule . '</div>';
if ( $files !== '' ) { $out .= '<div class="szp-area szp-area-files">' . $files . '</div>'; }
if ( $work !== '' )  { $out .= '<div class="szp-area szp-area-workbench">' . $work . '</div>'; }
if ( $group !== '' ) { $out .= '<div class="szp-area szp-area-group">' . $group . '</div>'; }
$out .= '</div></div>';

echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
