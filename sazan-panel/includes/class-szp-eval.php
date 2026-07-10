<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * «ارزیابی من» — ثبت تارگت مالی هفتگی هر کاربر و سنجش وضعیت تحقق آن.
 *
 * چرخه‌ی هر هفته:
 *   - روز ثبت تارگت: سه‌شنبه  (DAY_TARGET)
 *   - روز ثبت نتیجه: دوشنبه   (DAY_RESULT)
 * کاربر از هفته‌ی جاری به بعد خودش ثبت می‌کند؛ هفته‌های گذشته را مدیر از پیشخوان وارد می‌کند.
 *
 * وضعیت تحقق تارگت بر اساس نسبت نتیجه به تارگت:
 *   نتیجه  >  تارگت            → فراتر از تارگت (beyond)
 *   نتیجه  == تارگت            → موفق (success)
 *   نتیجه  >= NEAR×تارگت       → قابل بهبود (improve)
 *   در غیر این صورت            → در مسیر (ontrack)
 */
class SZP_Eval {

	const DAY_TARGET = 2; // سه‌شنبه (wp_date('N'): دوشنبه=1 … یکشنبه=7)
	const DAY_RESULT = 1; // دوشنبه
	const NEAR       = 0.85; // مرز پیش‌فرض «قابل بهبود» در برابر «در مسیر»
	const OPTION     = 'szp_eval_settings';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_eval';
	}

	/* ==================== تنظیمات ==================== */

	public static function defaults() {
		return array(
			'start_week'         => 6,
			'currency'           => 'تومان',
			'near'               => 85,   // درصد مرز «قابل بهبود»
			'day_target'         => 2,    // سه‌شنبه
			'day_result'         => 1,    // دوشنبه
			'sms_enabled'        => 0,
			'sms_base'           => 'https://rest.ippanel.com/v1',
			'sms_apikey'         => '',
			'sms_originator'     => '',
			'sms_mode'           => 'pattern', // pattern | text
			'sms_var'            => 'name',
			'sms_pattern_target' => '',
			'sms_pattern_result' => '',
			'sms_text_target'    => '%name% عزیز، امروز روز ثبت تارگت هفتگی شماست. لطفاً تارگت این هفته را در پنل ثبت کنید.',
			'sms_text_result'    => '%name% عزیز، امروز آخرین مهلت ثبت نتیجه‌ی تارگت این هفته است. لطفاً نتیجه را در پنل وارد کنید.',
			// جلسات کوچینگ (زمان‌بندی + نظرسنجی)
			'sms_pattern_session' => '',
			'sms_pattern_survey'  => '',
			'sms_text_session'    => '%name% عزیز، جلسه‌ی «%title%» با کوچ %coach% در تاریخ %date% ساعت %time% ثبت شد.',
			'sms_text_survey'     => '%name% عزیز، از حضور شما در جلسه سپاسگزاریم. لطفاً نظرسنجی کوتاه را تکمیل کنید: %link%',
			'survey_url'          => '',
			'survey_delay'        => 60, // دقیقه پس از پایان جلسه
		);
	}

	public static function settings() {
		$s = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
	}

	public static function opt( $key ) {
		$s = self::settings();
		return isset( $s[ $key ] ) ? $s[ $key ] : null;
	}

	public static function near() {
		$n = (float) self::opt( 'near' );
		if ( $n <= 0 || $n >= 100 ) {
			$n = 85;
		}
		return $n / 100;
	}

	public static function day_target() { return (int) self::opt( 'day_target' ); }
	public static function day_result() { return (int) self::opt( 'day_result' ); }
	public static function start_week() { return max( 1, (int) self::opt( 'start_week' ) ); }
	public static function currency() { return (string) self::opt( 'currency' ); }

	/* ==================== داده ==================== */

	/** همه‌ی هفته‌های یک کاربر، مرتب بر اساس شماره هفته (صعودی). */
	public static function weeks( $user_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d ORDER BY week_no ASC', (int) $user_id ) );
	}

	/** آخرین هفته (بیشترین شماره) یا null. */
	public static function latest( $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d ORDER BY week_no DESC LIMIT 1', (int) $user_id ) );
	}

	public static function get_week( $user_id, $week_no ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d AND week_no=%d', (int) $user_id, (int) $week_no ) );
	}

	/**
	 * ثبت/به‌روزرسانی تارگت. اگر هفته‌ی باز همان امروز ساخته شده باشد، تارگتش به‌روز می‌شود؛
	 * در غیر این صورت یک هفته‌ی جدید باز می‌شود. خروجی: array( week, created ).
	 */
	public static function save_target( $user_id, $amount ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$amount  = max( 0, (float) $amount );
		$now     = current_time( 'mysql' );
		$today   = substr( $now, 0, 10 );
		$latest  = self::latest( $user_id );

		if ( $latest && ! $latest->has_result && substr( (string) $latest->target_set_at, 0, 10 ) === $today ) {
			$wpdb->update( self::table(),
				array( 'target' => $amount, 'target_set_at' => $now, 'updated_at' => $now ),
				array( 'id' => (int) $latest->id ), array( '%f', '%s', '%s' ), array( '%d' ) );
			return array( 'week' => (int) $latest->week_no, 'created' => false );
		}

		$week_no = $latest ? ( (int) $latest->week_no + 1 ) : (int) apply_filters( 'szp_eval_start_week', self::start_week(), $user_id );
		$wpdb->insert( self::table(), array(
			'user_id'       => $user_id,
			'week_no'       => $week_no,
			'target'        => $amount,
			'result'        => 0,
			'has_result'    => 0,
			'target_set_at' => $now,
			'created_at'    => $now,
			'updated_at'    => $now,
		), array( '%d', '%d', '%f', '%f', '%d', '%s', '%s', '%s' ) );
		return array( 'week' => $week_no, 'created' => true );
	}

	/** ثبت نتیجه روی آخرین هفته‌ی باز. خروجی: array( ok, week?, status? ) */
	public static function save_result( $user_id, $amount, $note = '' ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$amount  = max( 0, (float) $amount );
		$note    = sanitize_textarea_field( $note );
		$now     = current_time( 'mysql' );
		$latest  = self::latest( $user_id );
		if ( ! $latest || $latest->has_result ) {
			return array( 'ok' => false );
		}
		$wpdb->update( self::table(),
			array( 'result' => $amount, 'has_result' => 1, 'note' => $note, 'result_set_at' => $now, 'updated_at' => $now ),
			array( 'id' => (int) $latest->id ), array( '%f', '%d', '%s', '%s', '%s' ), array( '%d' ) );
		return array(
			'ok'     => true,
			'week'   => (int) $latest->week_no,
			'status' => self::compute_status( $latest->target, $amount, true ),
		);
	}

	/** درج/به‌روزرسانی دستی یک هفته (برای مدیر). $result=null یعنی بدون نتیجه. */
	public static function upsert( $user_id, $week_no, $target, $result = null, $note = null ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$week_no = (int) $week_no;
		if ( $user_id < 1 || $week_no < 1 ) {
			return;
		}
		$now        = current_time( 'mysql' );
		$has_result = ( $result !== null && $result !== '' ) ? 1 : 0;
		$result_val = $has_result ? (float) $result : 0;
		$note_val   = ( $note === null ) ? null : sanitize_textarea_field( $note );
		$existing   = self::get_week( $user_id, $week_no );

		if ( $existing ) {
			$data    = array(
				'target'        => (float) $target,
				'result'        => $result_val,
				'has_result'    => $has_result,
				'result_set_at' => $has_result ? ( $existing->result_set_at ?: $now ) : null,
				'updated_at'    => $now,
			);
			$formats = array( '%f', '%f', '%d', '%s', '%s' );
			if ( $note_val !== null ) {
				$data['note'] = $note_val;
				$formats[]    = '%s';
			}
			$wpdb->update( self::table(), $data, array( 'id' => (int) $existing->id ), $formats, array( '%d' ) );
		} else {
			$wpdb->insert( self::table(), array(
				'user_id'       => $user_id,
				'week_no'       => $week_no,
				'target'        => (float) $target,
				'result'        => $result_val,
				'has_result'    => $has_result,
				'note'          => $note_val === null ? '' : $note_val,
				'target_set_at' => $now,
				'result_set_at' => $has_result ? $now : null,
				'created_at'    => $now,
				'updated_at'    => $now,
			), array( '%d', '%d', '%f', '%f', '%d', '%s', '%s', '%s', '%s', '%s' ) );
		}
	}

	public static function delete_week( $user_id, $week_no ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'user_id' => (int) $user_id, 'week_no' => (int) $week_no ), array( '%d', '%d' ) );
	}

	/* ==================== وضعیت ==================== */

	public static function statuses() {
		return array(
			'beyond'  => array( 'label' => 'فراتر از تارگت', 'color' => '#0ea5e9', 'emoji' => '🚀', 'msg' => 'فراتر از تارگت رفتی، فوق‌العاده بود!' ),
			'success' => array( 'label' => 'موفق', 'color' => '#16a34a', 'emoji' => '🎯', 'msg' => 'دقیقاً به تارگت رسیدی!' ),
			'improve' => array( 'label' => 'قابل بهبود', 'color' => '#f59e0b', 'emoji' => '📈', 'msg' => 'نزدیک بودی؛ کمی تا تارگت فاصله داری.' ),
			'ontrack' => array( 'label' => 'در مسیر', 'color' => '#ef4444', 'emoji' => '🧭', 'msg' => 'در مسیر هستی؛ فاصله تا تارگت زیاد است.' ),
			'pending' => array( 'label' => 'در انتظار نتیجه', 'color' => '#9ca3af', 'emoji' => '⏳', 'msg' => 'هنوز نتیجه ثبت نشده است.' ),
		);
	}

	public static function status_meta( $key ) {
		$all = self::statuses();
		return isset( $all[ $key ] ) ? $all[ $key ] : $all['pending'];
	}

	public static function compute_status( $target, $result, $has_result ) {
		if ( ! $has_result ) {
			return 'pending';
		}
		$target = (float) $target;
		$result = (float) $result;
		if ( $target <= 0 ) {
			return 'pending';
		}
		if ( $result > $target ) {
			return 'beyond';
		}
		if ( $result == $target ) {
			return 'success';
		}
		if ( $result >= $target * self::near() ) {
			return 'improve';
		}
		return 'ontrack';
	}

	/** درصد تحقق (نتیجه/تارگت). */
	public static function pct( $target, $result ) {
		$target = (float) $target;
		if ( $target <= 0 ) {
			return 0;
		}
		return (int) round( ( (float) $result / $target ) * 100 );
	}

	/* ==================== قفل روز ==================== */

	/** آیا ثبت در این روز قفل است؟ مدیران و فیلتر می‌توانند آزاد کنند. */
	public static function day_locked( $which ) {
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( ! apply_filters( 'szp_eval_enforce_days', true ) ) {
			return false;
		}
		$need = ( $which === 'target' ) ? self::day_target() : self::day_result();
		return (int) wp_date( 'N' ) !== $need;
	}

	public static function day_name( $n ) {
		$names = array( 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه', 7 => 'یکشنبه' );
		return isset( $names[ $n ] ) ? $names[ $n ] : '';
	}

	/** تعداد روز تا رسیدن روز هفته‌ی موردنظر (۰ = همین امروز). */
	public static function days_until( $target_dow ) {
		$today = (int) wp_date( 'N' );
		$diff  = ( (int) $target_dow - $today + 7 ) % 7;
		return $diff;
	}

	/** شناسه‌ی همه‌ی کاربرانی که حداقل یک هفته‌ی ثبت‌شده دارند. */
	public static function participants() {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( 'SELECT DISTINCT user_id FROM ' . self::table() ) );
	}

	/* ==================== رندر ==================== */

	/** $atts: title، currency. */
	public static function render( $atts = array() ) {
		$title    = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'ارزیابی من';
		$currency = ( isset( $atts['currency'] ) && $atts['currency'] !== '' ) ? (string) $atts['currency'] : self::currency();

		if ( ! is_user_logged_in() ) {
			return '<div class="szp"><div class="szp-empty">برای مشاهده «ارزیابی من» ابتدا وارد شوید.</div></div>';
		}
		$uid   = get_current_user_id();
		$weeks = self::weeks( $uid );

		$latest = $weeks ? end( $weeks ) : null;
		reset( $weeks );
		$phase = ( $latest && ! $latest->has_result ) ? 'result' : 'target';

		ob_start(); ?>
		<div class="szp">
			<div class="szp-eval" data-currency="<?php echo esc_attr( $currency ); ?>"
				data-near="<?php echo esc_attr( self::near() ); ?>"
				data-can-target="<?php echo self::day_locked( 'target' ) ? '0' : '1'; ?>"
				data-can-result="<?php echo self::day_locked( 'result' ) ? '0' : '1'; ?>">

				<div class="szp-eval-head">
					<h3 class="szp-eval-title"><?php echo esc_html( $title ); ?></h3>
					<div class="szp-eval-legend"><?php echo self::legend_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>

				<?php echo self::summary_html( $weeks ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php echo self::current_html( $latest, $phase, $currency ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php echo self::chart_html( $weeks ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php echo self::history_html( $weeks, $currency ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function legend_html() {
		$out = '';
		foreach ( array( 'beyond', 'success', 'improve', 'ontrack' ) as $k ) {
			$m    = self::status_meta( $k );
			$out .= '<span class="szp-ev-leg"><i style="background:' . esc_attr( $m['color'] ) . '"></i>' . esc_html( $m['label'] ) . '</span>';
		}
		return $out;
	}

	/** کارت‌های خلاصه: تعداد هفته‌ها، تحقق‌یافته، میانگین درصد. */
	protected static function summary_html( $weeks ) {
		$done = 0;
		$hit  = 0;
		$sum  = 0;
		foreach ( $weeks as $w ) {
			if ( ! $w->has_result ) {
				continue;
			}
			$done++;
			$sum += self::pct( $w->target, $w->result );
			$st   = self::compute_status( $w->target, $w->result, true );
			if ( $st === 'success' || $st === 'beyond' ) {
				$hit++;
			}
		}
		$avg = $done ? (int) round( $sum / $done ) : 0;

		ob_start(); ?>
		<div class="szp-eval-summary">
			<div class="szp-ev-stat"><span class="szp-ev-stat-n"><?php echo esc_html( szp_fa_digits( count( $weeks ) ) ); ?></span><span class="szp-ev-stat-l">هفته ثبت‌شده</span></div>
			<div class="szp-ev-stat"><span class="szp-ev-stat-n"><?php echo esc_html( szp_fa_digits( $hit ) ); ?></span><span class="szp-ev-stat-l">تارگت محقق‌شده</span></div>
			<div class="szp-ev-stat"><span class="szp-ev-stat-n"><?php echo esc_html( szp_fa_digits( $avg ) ); ?>٪</span><span class="szp-ev-stat-l">میانگین تحقق</span></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** شمارش معکوس تا روز هدف به‌صورت متن فارسی. */
	protected static function countdown_text( $dow, $label ) {
		$d = self::days_until( $dow );
		if ( $d === 0 ) {
			return 'امروز روز ' . $label . ' است.';
		}
		return szp_fa_digits( $d ) . ' روز تا ' . $label . ' (' . self::day_name( $dow ) . ').';
	}

	/** بخش هفته‌ی جاری: فرم ثبت تارگت یا فرم ثبت نتیجه. */
	protected static function current_html( $latest, $phase, $currency ) {
		$can_target = ! self::day_locked( 'target' );
		$can_result = ! self::day_locked( 'result' );
		$d_target   = self::day_target();
		$d_result   = self::day_result();

		ob_start();

		if ( $phase === 'result' ) :
			$wk     = (int) $latest->week_no;
			$target = (float) $latest->target;
			?>
			<div class="szp-ev-current szp-ev-phase-result">
				<div class="szp-ev-cur-top">
					<span class="szp-ev-week-badge">هفته <?php echo esc_html( szp_fa_digits( $wk ) ); ?></span>
					<span class="szp-ev-cur-target">تارگت این هفته: <b><?php echo esc_html( szp_money( $target, $currency ) ); ?></b></span>
					<span class="szp-ev-countdown">⏳ <?php echo esc_html( self::countdown_text( $d_result, 'ثبت نتیجه' ) ); ?></span>
				</div>
				<p class="szp-ev-cur-hint">نتیجه‌ی این هفته را وارد کنید (روز ثبت نتیجه: <b><?php echo esc_html( self::day_name( $d_result ) ); ?></b>).</p>
				<div class="szp-ev-form" data-mode="result" data-target="<?php echo esc_attr( $target ); ?>">
					<div class="szp-ev-inrow">
						<input type="text" inputmode="numeric" class="szp-ev-amount" placeholder="نتیجه‌ی واقعی (عدد)" <?php disabled( ! $can_result ); ?>>
						<span class="szp-ev-cur-unit"><?php echo esc_html( $currency ); ?></span>
						<button type="button" class="szp-ev-submit button-primary" <?php disabled( ! $can_result ); ?>>ثبت نتیجه</button>
					</div>
					<textarea class="szp-ev-note" rows="2" placeholder="یادداشت این هفته (اختیاری) — مثلاً چرا به تارگت رسیدم/نرسیدم" <?php disabled( ! $can_result ); ?>></textarea>
					<div class="szp-ev-preview" aria-live="polite"></div>
					<?php if ( ! $can_result ) : ?>
						<p class="szp-ev-locked">ثبت نتیجه فقط در روز <b><?php echo esc_html( self::day_name( $d_result ) ); ?></b> امکان‌پذیر است.</p>
					<?php endif; ?>
					<div class="szp-ev-msg" aria-live="polite"></div>
				</div>
			</div>
			<?php
		else :
			$next = $latest ? ( (int) $latest->week_no + 1 ) : self::start_week();
			?>
			<div class="szp-ev-current szp-ev-phase-target">
				<div class="szp-ev-cur-top">
					<span class="szp-ev-week-badge">هفته <?php echo esc_html( szp_fa_digits( $next ) ); ?></span>
					<span class="szp-ev-cur-target">تارگت جدید را مشخص کنید</span>
					<span class="szp-ev-countdown">⏳ <?php echo esc_html( self::countdown_text( $d_target, 'ثبت تارگت' ) ); ?></span>
				</div>
				<p class="szp-ev-cur-hint">تارگت مالی این هفته را ثبت کنید (روز ثبت تارگت: <b><?php echo esc_html( self::day_name( $d_target ) ); ?></b>).</p>
				<div class="szp-ev-form" data-mode="target">
					<div class="szp-ev-inrow">
						<input type="text" inputmode="numeric" class="szp-ev-amount" placeholder="تارگت هفته (مثلاً ۵۰۰٬۰۰۰٬۰۰۰)" <?php disabled( ! $can_target ); ?>>
						<span class="szp-ev-cur-unit"><?php echo esc_html( $currency ); ?></span>
						<button type="button" class="szp-ev-submit button-primary" <?php disabled( ! $can_target ); ?>>ثبت تارگت</button>
					</div>
					<div class="szp-ev-preview" aria-live="polite"></div>
					<?php if ( ! $can_target ) : ?>
						<p class="szp-ev-locked">ثبت تارگت فقط در روز <b><?php echo esc_html( self::day_name( $d_target ) ); ?></b> امکان‌پذیر است.</p>
					<?php endif; ?>
					<div class="szp-ev-msg" aria-live="polite"></div>
				</div>
			</div>
			<?php
		endif;

		return ob_get_clean();
	}

	/** نمودار ستونی تحقق + نمودار خطی روند درآمد. مقیاس کاملاً پویا. */
	protected static function chart_html( $weeks ) {
		$rows = array();
		foreach ( $weeks as $w ) {
			if ( $w->has_result ) {
				$rows[] = $w;
			}
		}
		if ( ! $rows ) {
			return '';
		}

		// --- مقیاس پویا برای نمودار ستونی (درصد تحقق) ---
		$max_pct = 0;
		$max_res = 0;
		foreach ( $rows as $w ) {
			$max_pct = max( $max_pct, self::pct( $w->target, $w->result ) );
			$max_res = max( $max_res, (float) $w->result );
		}
		$scale    = max( 120, $max_pct );          // همیشه خط ۱۰۰٪ دیده شود
		$scale    = (int) ( ceil( $scale / 10 ) * 10 );
		$baseline = round( 100 / $scale * 100, 3 ); // ارتفاع خط ۱۰۰٪

		ob_start(); ?>
		<div class="szp-eval-chartwrap">
			<h4 class="szp-ev-sec-title">نمودار تحقق تارگت</h4>
			<div class="szp-eval-chart">
				<div class="szp-ev-plot" style="--baseline:<?php echo esc_attr( $baseline ); ?>%">
					<span class="szp-ev-base"><span class="szp-ev-base-lbl">تارگت ۱۰۰٪</span></span>
					<?php foreach ( $rows as $w ) :
						$pct  = self::pct( $w->target, $w->result );
						$st   = self::compute_status( $w->target, $w->result, true );
						$meta = self::status_meta( $st );
						$h    = round( max( 2, $pct / $scale * 100 ), 2 );
						?>
						<div class="szp-ev-bar" title="هفته <?php echo esc_attr( szp_fa_digits( $w->week_no ) ); ?> — <?php echo esc_attr( szp_fa_digits( $pct ) ); ?>٪ (<?php echo esc_attr( $meta['label'] ); ?>)">
							<span class="szp-ev-bar-fill" style="height:<?php echo esc_attr( $h ); ?>%;background:<?php echo esc_attr( $meta['color'] ); ?>">
								<span class="szp-ev-bar-val"><?php echo esc_html( szp_fa_digits( $pct ) ); ?>٪</span>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="szp-ev-xaxis">
					<?php foreach ( $rows as $w ) : ?>
						<span class="szp-ev-xlbl">هفته <?php echo esc_html( szp_fa_digits( $w->week_no ) ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>

			<?php echo self::trend_svg( $rows, $max_res ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** نمودار خطی روند درآمد (SVG، بدون وابستگی). */
	protected static function trend_svg( $rows, $max_res ) {
		if ( $max_res <= 0 ) {
			$max_res = 1;
		}
		$n   = count( $rows );
		$pad = 4;
		$top = 6;
		$bot = 40;
		$pts = array();
		foreach ( array_values( $rows ) as $i => $w ) {
			$x = ( $n <= 1 ) ? 50 : round( $pad + ( $i / ( $n - 1 ) ) * ( 100 - 2 * $pad ), 2 );
			$y = round( $bot - ( (float) $w->result / $max_res ) * ( $bot - $top ), 2 );
			$pts[] = array( $x, $y, $w );
		}
		$poly = '';
		foreach ( $pts as $p ) {
			$poly .= $p[0] . ',' . $p[1] . ' ';
		}
		$area = 'M' . $pts[0][0] . ',' . $bot . ' L' . trim( str_replace( ' ', ' L', trim( $poly ) ) ) . ' L' . end( $pts )[0] . ',' . $bot . ' Z';

		ob_start(); ?>
		<h4 class="szp-ev-sec-title" style="margin-top:18px">روند رشد درآمد</h4>
		<div class="szp-eval-trend">
			<svg viewBox="0 0 100 44" preserveAspectRatio="none" class="szp-ev-svg" role="img" aria-label="نمودار روند درآمد">
				<path d="<?php echo esc_attr( $area ); ?>" class="szp-ev-area"></path>
				<?php if ( $n > 1 ) : ?>
					<polyline points="<?php echo esc_attr( trim( $poly ) ); ?>" class="szp-ev-line"></polyline>
				<?php endif; ?>
				<?php foreach ( $pts as $p ) : ?>
					<circle cx="<?php echo esc_attr( $p[0] ); ?>" cy="<?php echo esc_attr( $p[1] ); ?>" r="1.4" class="szp-ev-dot"></circle>
				<?php endforeach; ?>
			</svg>
			<div class="szp-ev-xaxis">
				<?php foreach ( $rows as $w ) : ?>
					<span class="szp-ev-xlbl">هفته <?php echo esc_html( szp_fa_digits( $w->week_no ) ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function jalali_or_dash( $datetime ) {
		if ( empty( $datetime ) ) {
			return '—';
		}
		$ts = strtotime( $datetime );
		return $ts ? szp_format_datetime( $ts ) : '—';
	}

	/** جدول هفته‌های قبل با وضعیت، درصد، تاریخ شمسی و یادداشت. */
	protected static function history_html( $weeks, $currency ) {
		if ( ! $weeks ) {
			return '<div class="szp-eval-history"><h4 class="szp-ev-sec-title">هفته‌های قبل</h4><p class="szp-empty">هنوز هفته‌ای ثبت نشده است.</p></div>';
		}
		$list = array_reverse( $weeks ); // جدیدترین بالا

		ob_start(); ?>
		<div class="szp-eval-history">
			<h4 class="szp-ev-sec-title">هفته‌های قبل</h4>
			<div class="szp-ev-table-wrap">
				<table class="szp-ev-table">
					<thead><tr>
						<th>هفته</th><th>تارگت</th><th>نتیجه</th><th>تحقق</th><th>وضعیت</th><th>تاریخ ثبت</th><th>یادداشت</th>
					</tr></thead>
					<tbody>
					<?php foreach ( $list as $w ) :
						$st   = self::compute_status( $w->target, $w->result, $w->has_result );
						$meta = self::status_meta( $st );
						$pct  = $w->has_result ? self::pct( $w->target, $w->result ) : 0;
						$date = $w->has_result ? self::jalali_or_dash( $w->result_set_at ) : self::jalali_or_dash( $w->target_set_at );
						?>
						<tr>
							<td data-th="هفته"><span class="szp-ev-week-badge sm">هفته <?php echo esc_html( szp_fa_digits( $w->week_no ) ); ?></span></td>
							<td data-th="تارگت"><?php echo esc_html( szp_money( $w->target, $currency ) ); ?></td>
							<td data-th="نتیجه"><?php echo $w->has_result ? esc_html( szp_money( $w->result, $currency ) ) : '—'; ?></td>
							<td data-th="تحقق"><?php echo $w->has_result ? esc_html( szp_fa_digits( $pct ) . '٪' ) : '—'; ?></td>
							<td data-th="وضعیت">
								<span class="szp-ev-badge" style="--c:<?php echo esc_attr( $meta['color'] ); ?>">
									<?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?>
								</span>
							</td>
							<td data-th="تاریخ ثبت" class="szp-ev-date"><?php echo esc_html( $date ); ?></td>
							<td data-th="یادداشت" class="szp-ev-notecell"><?php echo ! empty( $w->note ) ? esc_html( $w->note ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== تابلوی ارزیابی (همه‌ی اشخاص) ==================== */

	/** خلاصه‌ی وضعیت یک کاربر برای کارت تابلو. */
	public static function user_summary( $user_id ) {
		$weeks = self::weeks( $user_id );
		$done  = 0;
		$hit   = 0;
		$sum   = 0;
		$latest = null;
		foreach ( $weeks as $w ) {
			$latest = $w;
			if ( ! $w->has_result ) {
				continue;
			}
			$done++;
			$sum += self::pct( $w->target, $w->result );
			$st   = self::compute_status( $w->target, $w->result, true );
			if ( $st === 'success' || $st === 'beyond' ) {
				$hit++;
			}
		}
		return array(
			'weeks'  => count( $weeks ),
			'done'   => $done,
			'hit'    => $hit,
			'avg'    => $done ? (int) round( $sum / $done ) : 0,
			'latest' => $latest,
		);
	}

	/** $atts: title، currency، group (شناسه گروه برای فیلتر). نمایش شبکه‌ای همه‌ی اشخاص دارای تارگت. */
	public static function board( $atts = array() ) {
		$cap = apply_filters( 'szp_eval_board_cap', 'manage_options' );
		if ( ! current_user_can( $cap ) ) {
			return '<div class="szp"><div class="szp-empty">شما به تابلوی ارزیابی دسترسی ندارید.</div></div>';
		}
		$title    = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'تابلوی ارزیابی';
		$currency = ( isset( $atts['currency'] ) && $atts['currency'] !== '' ) ? (string) $atts['currency'] : self::currency();
		$group    = isset( $atts['group'] ) ? absint( $atts['group'] ) : 0;

		$ids = self::participants();
		if ( $group && class_exists( 'SZP_Groups' ) ) {
			$members = array_flip( SZP_Groups::members( $group ) );
			$ids     = array_values( array_filter( $ids, function ( $id ) use ( $members ) {
				return isset( $members[ $id ] );
			} ) );
		}

		// مرتب‌سازی بر اساس میانگین تحقق (نزولی).
		$cards = array();
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			if ( ! $info ) {
				continue;
			}
			$cards[] = array( 'info' => $info, 'sum' => self::user_summary( $id ) );
		}
		usort( $cards, function ( $a, $b ) {
			return $b['sum']['avg'] <=> $a['sum']['avg'];
		} );

		ob_start(); ?>
		<div class="szp">
			<div class="szp-eval szp-eval-board">
				<div class="szp-eval-head">
					<h3 class="szp-eval-title"><?php echo esc_html( $title ); ?></h3>
					<div class="szp-eval-legend"><?php echo self::legend_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>
				<?php if ( ! $cards ) : ?>
					<p class="szp-empty">هنوز هیچ کاربری تارگتی ثبت نکرده است.</p>
				<?php else : ?>
					<div class="szp-ev-grid">
						<?php foreach ( $cards as $c ) :
							$info = $c['info'];
							$sum  = $c['sum'];
							$lw   = $sum['latest'];
							$st   = $lw ? self::compute_status( $lw->target, $lw->result, $lw->has_result ) : 'pending';
							$meta = self::status_meta( $st );
							?>
							<div class="szp-ev-pcard">
								<div class="szp-ev-pc-head">
									<span class="szp-ev-pc-avatar"><?php echo get_avatar( $info['id'], 40 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="szp-ev-pc-id">
										<span class="szp-ev-pc-name"><?php echo esc_html( $info['name'] ); ?></span>
										<?php if ( $info['mobile'] !== '' ) : ?><span class="szp-ev-pc-mobile"><?php echo esc_html( szp_fa_digits( $info['mobile'] ) ); ?></span><?php endif; ?>
									</span>
									<span class="szp-ev-badge" style="--c:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span>
								</div>
								<div class="szp-ev-pc-body">
									<?php if ( $lw ) : ?>
										<div class="szp-ev-pc-row"><span>هفته جاری</span><b>هفته <?php echo esc_html( szp_fa_digits( $lw->week_no ) ); ?></b></div>
										<div class="szp-ev-pc-row"><span>تارگت</span><b><?php echo esc_html( szp_money( $lw->target, $currency ) ); ?></b></div>
										<div class="szp-ev-pc-row"><span>نتیجه</span><b><?php echo $lw->has_result ? esc_html( szp_money( $lw->result, $currency ) ) : '—'; ?></b></div>
									<?php endif; ?>
								</div>
								<div class="szp-ev-pc-foot">
									<span><?php echo esc_html( szp_fa_digits( $sum['weeks'] ) ); ?> هفته</span>
									<span><?php echo esc_html( szp_fa_digits( $sum['hit'] ) ); ?> محقق</span>
									<span>میانگین <?php echo esc_html( szp_fa_digits( $sum['avg'] ) ); ?>٪</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
