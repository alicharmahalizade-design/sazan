<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** @var int $user_id */
$ids = SZP_Access::user_courses( $user_id );
?>
<div class="szp szp-skin-neon">
	<h2 class="szp-h"><?php echo esc_html( szp_ui_text( 'chat_title', 'اتاق گفتگو' ) ); ?></h2>
	<p class="szp-text" style="margin-bottom:18px"><?php echo esc_html( szp_ui_text( 'chat_intro', 'برای ورود به اتاق گفتگوی هر دوره، آن را انتخاب کنید.' ) ); ?></p>

	<?php if ( ! $ids ) : ?>
		<div class="szp-empty"><?php echo esc_html( szp_ui_text( 'chat_empty', 'هنوز دوره‌ای برای شما ثبت نشده است.' ) ); ?></div>
	<?php else : ?>
		<div class="szp-grid">
			<?php foreach ( $ids as $cid ) :
				$thumb = get_the_post_thumbnail_url( $cid, 'medium' );
				$room  = SZP_Chat::get_room( $cid );
				$badge = array( 'pending' => 'به‌زودی', 'free' => 'گفتگوی آزاد', 'grouping' => 'گروه‌بندی', 'final' => 'گروه‌ها فعال' );
				$st    = $room ? ( $badge[ $room->status ] ?? '' ) : 'به‌زودی';
				?>
				<a class="szp-card" href="<?php echo SZP_Frontend::url_room( $cid ); ?>">
					<?php if ( $thumb ) : ?><span class="szp-card-img" style="background-image:url('<?php echo esc_url( $thumb ); ?>')"></span><?php endif; ?>
					<span class="szp-card-body">
						<span class="szp-card-title"><?php echo esc_html( get_the_title( $cid ) ); ?></span>
						<span class="szp-card-meta szp-chat-badge"><?php echo esc_html( $st ); ?></span>
						<span class="szp-card-cta">ورود به اتاق ‹</span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
<?php
