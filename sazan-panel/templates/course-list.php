<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var int $user_id */
$ids  = SZP_Access::user_courses( $user_id );
$base = SZP_Frontend::current_clean();
?>
<div class="szp szp-skin-neon">
	<?php echo SZP_Render::skin_switch(); // phpcs:ignore ?>
	<h2 class="szp-h">دوره‌های من</h2>

	<?php if ( ! $ids ) : ?>
		<div class="szp-empty">هنوز دوره‌ای برای شما ثبت نشده است.</div>
	<?php else : ?>
		<div class="szp-grid">
			<?php foreach ( $ids as $cid ) {
				echo SZP_Frontend::course_card_html( $cid, $base ); // phpcs:ignore WordPress.Security.EscapeOutput
			} ?>
		</div>
	<?php endif; ?>
</div>
