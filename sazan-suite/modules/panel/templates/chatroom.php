<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var int $course_id @var int $user_id */
?>
<div class="szp szp-skin-neon szp-chat-root" data-course="<?php echo (int) $course_id; ?>" data-me="<?php echo (int) $user_id; ?>">
	<a class="szp-back" href="<?php echo SZP_Frontend::url_list(); ?>">بازگشت به فهرست دوره‌ها ›</a>
	<div class="szp-chat-app">
		<div class="szp-chat-loading">در حال بارگذاری اتاق گفتگو…</div>
	</div>
</div>
<?php
