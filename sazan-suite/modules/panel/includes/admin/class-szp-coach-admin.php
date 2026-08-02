<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** کوچینگ کسب‌وکار — تنظیمات دوره (فعال‌سازی، کوچ‌ها، فرم) + گزارش‌ها. */
class SZP_Coach_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_szp_course', array( __CLASS__, 'save' ), 11, 1 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 13 );
	}

	public static function boxes() {
		add_meta_box( 'szp_course_coach', 'کوچینگ کسب‌وکار', array( __CLASS__, 'metabox' ), 'szp_course', 'normal', 'default' );
	}

	public static function metabox( $post ) {
		wp_nonce_field( 'szp_coach_meta', 'szp_coach_meta_nonce' );
		$enabled = (int) get_post_meta( $post->ID, '_szp_coach_enabled', true );
		$coaches = SZP_Coach::coach_ids( $post->ID );
		$form    = SZP_Coach::form_def( $post->ID );
		?>
		<p class="szp-f">
			<label><input type="checkbox" name="_szp_coach_enabled" value="1" <?php checked( $enabled, 1 ); ?>> فعال‌سازی برنامه کوچینگ کسب‌وکار برای این دوره</label>
		</p>
		<p class="description">با فعال‌سازی، کاربران دارای دسترسی این دوره می‌توانند سفر کوچینگ (اسکن، فرم، اقدامات، تکالیف، رشد هفتگی) را در شورت‌کد <code>[sazan_coaching]</code> ببینند.</p>

		<div class="szp-access" style="margin-top:12px">
			<label>کوچ‌های این دوره</label>
			<div class="szp-user-search" data-target="_szp_coach_ids">
				<input type="text" class="widefat szp-user-q" placeholder="جستجوی کوچ (نام یا ایمیل)...">
				<div class="szp-user-results"></div>
				<div class="szp-user-chips">
					<?php foreach ( $coaches as $cid ) : $u = get_userdata( $cid ); if ( ! $u ) { continue; } ?>
						<span class="szp-chip" data-id="<?php echo (int) $cid; ?>">
							<input type="hidden" name="_szp_coach_ids[]" value="<?php echo (int) $cid; ?>">
							<?php echo esc_html( $u->display_name ); ?>
							<a href="#" class="szp-chip-del">×</a>
						</span>
					<?php endforeach; ?>
				</div>
			</div>
			<p class="description">مدیران سایت همیشه دسترسی کوچ دارند. کوچ‌ها از همان شورت‌کد، حالت مدیریت کارآموزان را می‌بینند.</p>
		</div>

		<h4 style="margin:16px 0 6px">فرم تحلیلی (سوالات گام دوم)</h4>
		<div class="szp-rep" data-name="_szp_coach_form">
			<div class="szp-rep-rows">
				<?php if ( $form ) : foreach ( $form as $f ) { self::form_row( $f['q'], $f['type'], implode( '|', $f['opts'] ) ); } else : self::form_row(); endif; ?>
			</div>
			<button type="button" class="button szp-rep-add">+ افزودن سوال</button>
			<template class="szp-rep-tpl"><?php self::form_row(); ?></template>
		</div>
		<p class="description">نوع «طیفی» امتیاز ۱ تا ۵ می‌گیرد. برای «چندگزینه‌ای»، گزینه‌ها را با علامت | جدا کنید (مثلاً: بله|خیر|تا حدی).</p>
		<?php
	}

	protected static function form_row( $q = '', $type = 'textarea', $opts = '' ) {
		$types = array(
			'textarea' => 'متن بلند',
			'text'     => 'متن کوتاه',
			'number'   => 'عدد',
			'scale'    => 'طیفی (۱ تا ۵)',
			'choice'   => 'چندگزینه‌ای',
		);
		?>
		<div class="szp-rep-row szp-coach-formrow">
			<input type="text" name="_szp_coach_form[q][]" value="<?php echo esc_attr( $q ); ?>" placeholder="متن سوال" class="widefat">
			<select name="_szp_coach_form[type][]">
				<?php foreach ( $types as $k => $lbl ) { printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $type, $k, false ), esc_html( $lbl ) ); } ?>
			</select>
			<input type="text" name="_szp_coach_form[opts][]" value="<?php echo esc_attr( $opts ); ?>" placeholder="گزینه‌ها با | (برای چندگزینه‌ای)">
			<button type="button" class="button szp-rep-del">×</button>
		</div>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['szp_coach_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['szp_coach_meta_nonce'] ) ), 'szp_coach_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

		update_post_meta( $post_id, '_szp_coach_enabled', empty( $_POST['_szp_coach_enabled'] ) ? 0 : 1 );

		$coaches = isset( $_POST['_szp_coach_ids'] ) ? array_values( array_unique( array_filter( array_map( 'intval', (array) $_POST['_szp_coach_ids'] ) ) ) ) : array();
		update_post_meta( $post_id, '_szp_coach_ids', $coaches );

		$raw = isset( $_POST['_szp_coach_form'] ) ? (array) wp_unslash( $_POST['_szp_coach_form'] ) : array();
		$q   = (array) ( $raw['q'] ?? array() );
		$ty  = (array) ( $raw['type'] ?? array() );
		$op  = (array) ( $raw['opts'] ?? array() );
		$form = array();
		foreach ( $q as $i => $qq ) {
			$qq = sanitize_text_field( $qq );
			if ( $qq === '' ) { continue; }
			$form[] = array(
				'q'    => $qq,
				'type' => sanitize_key( $ty[ $i ] ?? 'textarea' ),
				'opts' => sanitize_text_field( $op[ $i ] ?? '' ),
			);
		}
		update_post_meta( $post_id, '_szp_coach_form', $form );
	}

	/* ---------------- reports ---------------- */

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'کوچینگ کسب‌وکار', 'کوچینگ کسب‌وکار', 'manage_options', 'szp-coaching', array( __CLASS__, 'report' ) );
	}

	public static function report() {
		$courses = get_posts( array(
			'post_type'   => 'szp_course',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => array( array( 'key' => '_szp_coach_enabled', 'value' => '1' ) ),
		) );
		echo '<div class="wrap"><h1>کوچینگ کسب‌وکار</h1>';
		echo '<p>برای استفاده، شورت‌کد <code>[sazan_coaching]</code> را در یک برگه قرار دهید (یا ویجت «سازان: کوچینگ کسب‌وکار» را در المنتور بیفزایید). کوچ‌ها و دانشجویان از همان صفحه نمای مناسب خود را می‌بینند.</p>';
		if ( ! $courses ) {
			echo '<p>هنوز دوره‌ای کوچینگ‌فعال نیست. در ویرایش دوره، باکس «کوچینگ کسب‌وکار» را فعال کنید.</p></div>';
			return;
		}
		foreach ( $courses as $c ) {
			$rows = SZP_Coach::course_journeys( $c->ID );
			printf( '<h2 style="margin-top:24px">%s <span style="font-weight:400;color:#666">(%s کارآموز)</span></h2>',
				esc_html( $c->post_title ), esc_html( szp_fa_digits( count( $rows ) ) ) );
			if ( ! $rows ) { echo '<p>—</p>'; continue; }
			echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>کسب‌وکار</th><th>کاربر</th><th>مرحله</th><th>هفته</th><th>شاخص رشد</th></tr></thead><tbody>';
			$stages = array( 'scan' => 'اسکن', 'form' => 'فرم', 'review' => 'بررسی کوچ', 'tracking' => 'چرخه هفتگی' );
			foreach ( $rows as $j ) {
				$u   = get_userdata( $j->user_id );
				$d   = SZP_Coach::journey_data( $j );
				$pl  = SZP_Coach::chart_payload( $j );
				$idx = end( $pl['growth'] ); $idx = $idx === false ? 0 : $idx;
				printf( '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s٪</td></tr>',
					esc_html( $d['biz_name'] ?? '—' ),
					esc_html( $u ? $u->display_name : ( '#' . $j->user_id ) ),
					esc_html( $stages[ $j->stage ] ?? $j->stage ),
					esc_html( szp_fa_digits( SZP_Coach::last_week_no( $j->id ) ) ),
					esc_html( szp_fa_digits( $idx ) )
				);
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}
}
