<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * رندر ویجت «زمان‌بندی جلسات کوچینگ» (مخصوص کوچ/مانتور).
 * فرم ثبت جلسه + فهرست جلسات آینده و گذشته با امکان ویرایش/لغو/حذف.
 */
class SZP_Sessions_Render {

	public static function render( $uid, $title = '' ) {
		$uid = (int) $uid;
		if ( ! is_user_logged_in() ) {
			return '<div class="szp"><div class="szp-empty">برای مشاهده ابتدا وارد شوید.</div></div>';
		}
		if ( ! SZP_Sessions::can_manage( $uid ) ) {
			return '<div class="szp"><div class="szp-empty">این بخش مخصوص کوچ‌ها و مانتورهاست. برای ثبت جلسه از مدیر بخواهید دسترسی کوچ به شما داده شود.</div></div>';
		}
		$title = $title !== '' ? $title : 'زمان‌بندی جلسات کوچینگ';
		$rows  = SZP_Sessions::list_for( $uid );
		$sms_on = SZP_SMS::enabled();

		ob_start(); ?>
		<div class="szp">
			<div class="szp-sess" data-today="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
				<div class="szp-sess-head">
					<h3 class="szp-sess-title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( ! $sms_on ) : ?>
						<span class="szp-sess-warn">سرویس پیامک فعال نیست — جلسات ثبت می‌شوند اما پیامکی ارسال نمی‌شود. از «تنظیمات ارزیابی» فعال کنید.</span>
					<?php endif; ?>
				</div>

				<div class="szp-sess-toast" hidden></div>

				<?php self::form( $uid ); ?>

				<?php self::list( $rows ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== فرم ثبت/ویرایش ==================== */

	protected static function form( $uid ) {
		$coaches = SZP_Sessions::coach_users();
		$default_survey = SZP_Sessions::default_survey_url();
		?>
		<section class="szp-sess-formwrap">
			<h4 class="szp-sess-sec">ثبت جلسه جدید</h4>
			<form class="szp-sess-form" novalidate>
				<input type="hidden" data-f="id" value="0">

				<div class="szp-sess-grid">
					<label class="szp-sess-fld szp-sess-col2">
						<span>عنوان جلسه</span>
						<input type="text" data-f="title" placeholder="جلسه کوچینگ">
					</label>

					<label class="szp-sess-fld">
						<span>نام مشتری</span>
						<input type="text" data-f="customer_name" placeholder="نام و نام خانوادگی">
					</label>
					<label class="szp-sess-fld">
						<span>موبایل مشتری *</span>
						<input type="text" inputmode="tel" dir="ltr" data-f="customer_mobile" placeholder="۰۹۱۲...">
					</label>

					<label class="szp-sess-fld">
						<span>کوچ</span>
						<select data-f="coach_id">
							<?php foreach ( $coaches as $cid => $info ) : ?>
								<option value="<?php echo (int) $cid; ?>" <?php selected( $cid, $uid ); ?>>
									<?php echo esc_html( $info['name'] . ( $info['mobile'] !== '' ? ' — ' . szp_fa_digits( $info['mobile'] ) : '' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<div class="szp-sess-fld szp-sess-two">
						<label><span>نام مانتور (اختیاری)</span><input type="text" data-f="mentor_name" placeholder="نام مانتور"></label>
						<label><span>موبایل مانتور</span><input type="text" inputmode="tel" dir="ltr" data-f="mentor_mobile" placeholder="۰۹..."></label>
					</div>

					<label class="szp-sess-fld">
						<span>تاریخ جلسه *</span>
						<input type="date" data-f="date">
						<em class="szp-sess-jalali" data-jalali></em>
					</label>
					<div class="szp-sess-fld szp-sess-two">
						<label><span>ساعت شروع *</span><input type="time" dir="ltr" data-f="start"></label>
						<label><span>ساعت پایان *</span><input type="time" dir="ltr" data-f="end"></label>
					</div>

					<label class="szp-sess-fld szp-sess-col2">
						<span>لینک نظرسنجی (اختیاری)</span>
						<input type="url" dir="ltr" data-f="survey_url" placeholder="<?php echo esc_attr( $default_survey !== '' ? $default_survey : 'https://... (در نبود این مورد، لینک پیش‌فرض تنظیمات استفاده می‌شود)' ); ?>">
					</label>
					<label class="szp-sess-fld szp-sess-col2">
						<span>یادداشت (اختیاری)</span>
						<textarea data-f="note" rows="2"></textarea>
					</label>
				</div>

				<p class="szp-sess-hint">پس از ثبت، پیامک «ثبت جلسه» برای مشتری، کوچ و مانتور ارسال می‌شود و <b><?php echo esc_html( szp_fa_digits( SZP_Sessions::survey_delay() ) ); ?> دقیقه</b> پس از پایان جلسه، لینک نظرسنجی برای مشتری پیامک می‌شود.</p>

				<div class="szp-sess-actions">
					<button type="submit" class="szp-sess-btn primary" data-act="save">ثبت جلسه و ارسال پیامک</button>
					<button type="button" class="szp-sess-btn ghost" data-act="reset" hidden>انصراف از ویرایش</button>
					<span class="szp-sess-msg" aria-live="polite"></span>
				</div>
			</form>
		</section>
		<?php
	}

	/* ==================== فهرست جلسات ==================== */

	protected static function list( $rows ) {
		$now      = time();
		$upcoming = array();
		$past     = array();
		foreach ( $rows as $r ) {
			if ( $r->status === 'scheduled' && (int) $r->end_ts > $now ) {
				$upcoming[] = $r;
			} else {
				$past[] = $r;
			}
		}
		usort( $upcoming, function ( $a, $b ) { return (int) $a->start_ts <=> (int) $b->start_ts; } );

		echo '<section class="szp-sess-listwrap">';
		echo '<h4 class="szp-sess-sec">جلسات پیشِ‌رو</h4>';
		if ( ! $upcoming ) {
			echo '<p class="szp-empty">جلسه‌ی آینده‌ای ثبت نشده است.</p>';
		} else {
			echo '<div class="szp-sess-cards">';
			foreach ( $upcoming as $r ) {
				self::card( $r, true );
			}
			echo '</div>';
		}

		if ( $past ) {
			echo '<h4 class="szp-sess-sec">بایگانی جلسات</h4><div class="szp-sess-cards past">';
			foreach ( $past as $r ) {
				self::card( $r, false );
			}
			echo '</div>';
		}
		echo '</section>';
	}

	protected static function card( $r, $upcoming ) {
		$edit = array(
			'id'              => (int) $r->id,
			'title'           => $r->title,
			'customer_name'   => $r->customer_name,
			'customer_mobile' => $r->customer_mobile,
			'coach_id'        => (int) $r->coach_id,
			'mentor_name'     => $r->mentor_name,
			'mentor_mobile'   => $r->mentor_mobile,
			'date'            => wp_date( 'Y-m-d', (int) $r->start_ts ),
			'start'           => wp_date( 'H:i', (int) $r->start_ts ),
			'end'             => wp_date( 'H:i', (int) $r->end_ts ),
			'survey_url'      => $r->survey_url,
			'note'            => $r->note,
		);
		$status_cls = 'st-' . esc_attr( $r->status );
		?>
		<div class="szp-sess-card <?php echo esc_attr( $status_cls ); ?>" data-id="<?php echo (int) $r->id; ?>">
			<div class="szp-sess-card-head">
				<b class="szp-sess-card-title"><?php echo esc_html( $r->title ); ?></b>
				<span class="szp-sess-status <?php echo esc_attr( $status_cls ); ?>"><?php echo esc_html( SZP_Sessions::status_label( $r->status ) ); ?></span>
			</div>
			<div class="szp-sess-card-when">
				<span class="szp-sess-date">📅 <?php echo esc_html( SZP_Sessions::fa_date( $r->start_ts ) ); ?></span>
				<span class="szp-sess-time">⏰ <?php echo esc_html( SZP_Sessions::fa_time_range( $r ) ); ?></span>
			</div>
			<?php if ( $upcoming ) : ?>
				<?php echo szp_countdown_html( (int) $r->start_ts, 'تا شروع جلسه' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
			<div class="szp-sess-card-people">
				<span><i>مشتری:</i> <?php echo esc_html( $r->customer_name !== '' ? $r->customer_name : '—' ); ?><?php echo $r->customer_mobile !== '' ? ' <b dir="ltr">' . esc_html( szp_fa_digits( $r->customer_mobile ) ) . '</b>' : ''; ?></span>
				<span><i>کوچ:</i> <?php echo esc_html( $r->coach_name !== '' ? $r->coach_name : '—' ); ?></span>
				<?php if ( $r->mentor_name !== '' || $r->mentor_mobile !== '' ) : ?>
					<span><i>مانتور:</i> <?php echo esc_html( $r->mentor_name !== '' ? $r->mentor_name : szp_fa_digits( $r->mentor_mobile ) ); ?></span>
				<?php endif; ?>
			</div>
			<div class="szp-sess-card-foot">
				<span class="szp-sess-survey <?php echo (int) $r->survey_sent ? 'sent' : 'pending'; ?>">
					<?php echo (int) $r->survey_sent ? '✓ نظرسنجی ارسال شد' : '⏳ نظرسنجی پس از جلسه'; ?>
				</span>
				<span class="szp-sess-ops">
					<?php if ( $r->status === 'scheduled' ) : ?>
						<button type="button" class="szp-sess-op" data-act="edit" data-edit='<?php echo esc_attr( wp_json_encode( $edit ) ); ?>'>ویرایش</button>
						<button type="button" class="szp-sess-op" data-act="done" data-id="<?php echo (int) $r->id; ?>">برگزار شد</button>
						<button type="button" class="szp-sess-op danger" data-act="cancel" data-id="<?php echo (int) $r->id; ?>">لغو</button>
					<?php else : ?>
						<button type="button" class="szp-sess-op danger" data-act="delete" data-id="<?php echo (int) $r->id; ?>">حذف</button>
					<?php endif; ?>
				</span>
			</div>
		</div>
		<?php
	}
}
