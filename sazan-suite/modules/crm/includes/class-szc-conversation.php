<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * دستیار مکالمه فروش؛ پیشنهادهای قابل مدیریت را با زمینه واقعی هر مخاطب ترکیب می‌کند.
 * این موتور قاعده‌محور است و برای کارکردن، داده مخاطب را به سرویس بیرونی نمی‌فرستد.
 */
class SZC_Conversation {

	protected static function lines( $value ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
		return array_values( array_filter( array_map( 'trim', $lines ), function ( $line ) {
			return $line !== '';
		} ) );
	}

	protected static function latest_context( $contact_id ) {
		global $wpdb;
		$table = SZC_Activity::t_act();
		$call = $wpdb->get_row( $wpdb->prepare(
			"SELECT outcome,body,created_at FROM $table WHERE contact_id=%d AND type='call' ORDER BY created_at DESC,id DESC LIMIT 1",
			(int) $contact_id
		) );
		$followup = $wpdb->get_row( $wpdb->prepare(
			"SELECT body,due_at FROM $table WHERE contact_id=%d AND type='followup' AND done=0 ORDER BY due_at ASC,id ASC LIMIT 1",
			(int) $contact_id
		) );
		return array( $call, $followup );
	}

	protected static function fill( $text, $contact ) {
		$first = trim( (string) ( $contact->first_name ?? '' ) );
		$last  = trim( (string) ( $contact->last_name ?? '' ) );
		$name  = trim( $first . ' ' . $last );
		$vars  = array(
			'%first%'   => $first ?: 'دوست',
			'%last%'    => $last ?: ( $first ?: 'دوست' ),
			'%name%'    => $name ?: 'مخاطب گرامی',
			'%company%' => trim( (string) ( $contact->company ?? '' ) ) ?: 'مجموعه شما',
			'%stage%'   => SZC_Settings::stage_label( (string) ( $contact->stage ?? '' ) ),
			'%source%'  => trim( (string) ( $contact->source ?? '' ) ) ?: 'مسیر آشنایی شما',
		);
		return strtr( (string) $text, $vars );
	}

	public static function guide( $contact ) {
		if ( ! $contact || empty( SZC_Settings::get( 'conversation_enabled' ) ) ) {
			return null;
		}
		list( $last_call, $followup ) = self::latest_context( $contact->id );
		$stage = (string) ( $contact->stage ?? '' );
		$goals = (array) SZC_Settings::get( 'conversation_stage_goals' );
		$goal  = trim( (string) ( $goals[ $stage ] ?? '' ) );
		if ( $goal === '' ) {
			$goal = 'نیاز مخاطب را روشن کنید و مکالمه را با یک اقدام بعدی مشخص به پایان برسانید.';
		}

		$warnings = array();
		if ( empty( $contact->last_contacted_at ) ) {
			$warnings[] = 'این اولین تماس ثبت‌شده است؛ ابتدا اجازه گفت‌وگو بگیرید و با سؤال باز شروع کنید.';
		}
		if ( ( $contact->priority ?? '' ) === 'hot' ) {
			$warnings[] = 'این مخاطب اولویت داغ دارد؛ روی فوریت، مانع تصمیم و قدم بعدی مشخص تمرکز کنید.';
		}
		if ( $followup && ! empty( $followup->due_at ) && strtotime( $followup->due_at ) <= current_time( 'timestamp' ) ) {
			$warnings[] = 'پیگیری سررسیده است' . ( ! empty( $followup->body ) ? ': ' . $followup->body : '؛ با اشاره کوتاه به توافق قبلی شروع کنید.' );
		}
		if ( $last_call && $last_call->outcome === 'no_answer' ) {
			$warnings[] = 'تماس قبلی بی‌پاسخ بوده؛ شروع مکالمه را کوتاه نگه دارید و زمان مناسب را بپرسید.';
		} elseif ( $last_call && $last_call->outcome === 'callback' ) {
			$warnings[] = 'مخاطب قبلاً درخواست تماس مجدد داده؛ به زمان یا موضوع توافق‌شده اشاره کنید.';
		} elseif ( $last_call && $last_call->outcome === 'not_interested' ) {
			$warnings[] = 'نتیجه قبلی «بی‌علاقه» بوده؛ فقط علت را محترمانه بررسی کنید و از فشار فروش خودداری کنید.';
		}

		$objections = array();
		foreach ( (array) SZC_Settings::get( 'conversation_objections' ) as $item ) {
			$title = sanitize_text_field( $item['title'] ?? '' );
			$reply = sanitize_textarea_field( $item['response'] ?? '' );
			if ( $title === '' || $reply === '' ) { continue; }
			$objections[] = array(
				'title'    => $title,
				'signals'  => sanitize_text_field( $item['signals'] ?? '' ),
				'response' => self::fill( $reply, $contact ),
				'question' => self::fill( sanitize_textarea_field( $item['question'] ?? '' ), $contact ),
			);
		}

		return array(
			'goal'         => self::fill( $goal, $contact ),
			'opening'      => self::fill( SZC_Settings::get( 'conversation_opening' ), $contact ),
			'questions'    => array_map( function ( $line ) use ( $contact ) { return self::fill( $line, $contact ); }, self::lines( SZC_Settings::get( 'conversation_questions' ) ) ),
			'value_points' => array_map( function ( $line ) use ( $contact ) { return self::fill( $line, $contact ); }, self::lines( SZC_Settings::get( 'conversation_value_points' ) ) ),
			'closings'     => array_map( function ( $line ) use ( $contact ) { return self::fill( $line, $contact ); }, self::lines( SZC_Settings::get( 'conversation_closings' ) ) ),
			'guardrails'   => self::lines( SZC_Settings::get( 'conversation_guardrails' ) ),
			'objections'   => $objections,
			'warnings'     => $warnings,
			'stage'        => SZC_Settings::stage_label( $stage ),
			'last_outcome' => $last_call ? SZC_Settings::outcome_label( $last_call->outcome ) : '',
			'last_note'    => $last_call ? trim( (string) $last_call->body ) : '',
		);
	}

	public static function render( $contact, $context = 'portal' ) {
		$g = self::guide( $contact );
		if ( ! $g ) { return ''; }
		$compact = $context === 'dialer';
		ob_start();
		?>
		<details class="szc-talk <?php echo $compact ? 'is-compact' : ''; ?>" aria-label="دستیار مکالمه هوشمند">
			<summary class="szc-talk__summary">
				<div class="szc-talk__summary-title"><span class="szc-talk__eyebrow">همراه تماس</span><h2>دستیار مکالمه هوشمند</h2><small>برای مشاهده راهنمای مکالمه باز کنید</small></div>
				<div class="szc-talk__summary-side"><div class="szc-talk__context"><span><?php echo esc_html( $g['stage'] ); ?></span><?php if ( $g['last_outcome'] ) : ?><span>آخرین نتیجه: <?php echo esc_html( $g['last_outcome'] ); ?></span><?php endif; ?></div><span class="szc-talk__toggle" aria-hidden="true"><?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
			</summary>
			<div class="szc-talk__body">
			<div class="szc-talk__goal"><b>هدف این مکالمه</b><p><?php echo esc_html( $g['goal'] ); ?></p></div>
			<?php if ( $g['warnings'] ) : ?><div class="szc-talk__alerts"><?php foreach ( $g['warnings'] as $warning ) : ?><p>● <?php echo esc_html( $warning ); ?></p><?php endforeach; ?></div><?php endif; ?>
			<div class="szc-talk__opening">
				<div><b>شروع پیشنهادی</b><small>طبیعی بخوانید؛ لازم نیست کلمه‌به‌کلمه تکرار شود.</small></div>
				<p data-talk-copy-text><?php echo esc_html( $g['opening'] ); ?></p>
				<button type="button" data-talk-copy>کپی متن</button>
			</div>
			<div class="szc-talk__grid">
				<div><h3>سؤال‌های کشف نیاز</h3><ul class="szc-talk__checklist"><?php foreach ( $g['questions'] as $question ) : ?><li><label><input type="checkbox"><span><?php echo esc_html( $question ); ?></span></label></li><?php endforeach; ?></ul></div>
				<div><h3>نکات پیشنهادی</h3><ul><?php foreach ( $g['value_points'] as $point ) : ?><li><?php echo esc_html( $point ); ?></li><?php endforeach; ?></ul></div>
			</div>
			<?php if ( $g['objections'] ) : ?>
				<div class="szc-talk__objections"><h3>پاسخ به اعتراض‌های رایج</h3><div>
				<?php foreach ( $g['objections'] as $item ) : ?><details><summary><?php echo esc_html( $item['title'] ); ?> <small><?php echo esc_html( $item['signals'] ); ?></small></summary><p><?php echo esc_html( $item['response'] ); ?></p><?php if ( $item['question'] ) : ?><p class="szc-talk__followq"><b>سؤال بعدی:</b> <?php echo esc_html( $item['question'] ); ?></p><?php endif; ?></details><?php endforeach; ?>
				</div></div>
			<?php endif; ?>
			<div class="szc-talk__footer">
				<div><h3>جمع‌بندی و قدم بعدی</h3><ul><?php foreach ( $g['closings'] as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?></ul></div>
				<div><h3>خط قرمزهای مکالمه</h3><ul><?php foreach ( $g['guardrails'] as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?></ul></div>
			</div>
			</div>
		</details>
		<?php
		return ob_get_clean();
	}
}
