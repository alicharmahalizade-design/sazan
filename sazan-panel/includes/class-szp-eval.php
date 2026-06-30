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
	const NEAR       = 0.85; // مرز «قابل بهبود» در برابر «در مسیر»

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_eval';
	}

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

		$week_no = $latest ? ( (int) $latest->week_no + 1 ) : (int) apply_filters( 'szp_eval_start_week', 1, $user_id );
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
	public static function save_result( $user_id, $amount ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$amount  = max( 0, (float) $amount );
		$now     = current_time( 'mysql' );
		$latest  = self::latest( $user_id );
		if ( ! $latest || $latest->has_result ) {
			return array( 'ok' => false );
		}
		$wpdb->update( self::table(),
			array( 'result' => $amount, 'has_result' => 1, 'result_set_at' => $now, 'updated_at' => $now ),
			array( 'id' => (int) $latest->id ), array( '%f', '%d', '%s', '%s' ), array( '%d' ) );
		return array(
			'ok'     => true,
			'week'   => (int) $latest->week_no,
			'status' => self::compute_status( $latest->target, $amount, true ),
		);
	}

	/** درج/به‌روزرسانی دستی یک هفته (برای مدیر). $result=null یعنی بدون نتیجه. */
	public static function upsert( $user_id, $week_no, $target, $result = null ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$week_no = (int) $week_no;
		if ( $user_id < 1 || $week_no < 1 ) {
			return;
		}
		$now        = current_time( 'mysql' );
		$has_result = ( $result !== null && $result !== '' ) ? 1 : 0;
		$result_val = $has_result ? (float) $result : 0;
		$existing   = self::get_week( $user_id, $week_no );

		if ( $existing ) {
			$wpdb->update( self::table(), array(
				'target'        => (float) $target,
				'result'        => $result_val,
				'has_result'    => $has_result,
				'result_set_at' => $has_result ? ( $existing->result_set_at ?: $now ) : null,
				'updated_at'    => $now,
			), array( 'id' => (int) $existing->id ), array( '%f', '%f', '%d', '%s', '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert( self::table(), array(
				'user_id'       => $user_id,
				'week_no'       => $week_no,
				'target'        => (float) $target,
				'result'        => $result_val,
				'has_result'    => $has_result,
				'target_set_at' => $now,
				'result_set_at' => $has_result ? $now : null,
				'created_at'    => $now,
				'updated_at'    => $now,
			), array( '%d', '%d', '%f', '%f', '%d', '%s', '%s', '%s', '%s' ) );
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
		if ( $result >= $target * self::NEAR ) {
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
		$need = ( $which === 'target' ) ? self::DAY_TARGET : self::DAY_RESULT;
		return (int) wp_date( 'N' ) !== $need;
	}

	public static function day_name( $n ) {
		$names = array( 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه', 7 => 'یکشنبه' );
		return isset( $names[ $n ] ) ? $names[ $n ] : '';
	}

	/* ==================== رندر ==================== */

	/** $atts: title، currency. */
	public static function render( $atts = array() ) {
		$title    = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'ارزیابی من';
		$currency = isset( $atts['currency'] ) ? (string) $atts['currency'] : 'تومان';

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
				data-near="<?php echo esc_attr( self::NEAR ); ?>"
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

	/** بخش هفته‌ی جاری: فرم ثبت تارگت یا فرم ثبت نتیجه. */
	protected static function current_html( $latest, $phase, $currency ) {
		$can_target = ! self::day_locked( 'target' );
		$can_result = ! self::day_locked( 'result' );

		ob_start();

		if ( $phase === 'result' ) :
			$wk     = (int) $latest->week_no;
			$target = (float) $latest->target;
			?>
			<div class="szp-ev-current szp-ev-phase-result">
				<div class="szp-ev-cur-top">
					<span class="szp-ev-week-badge">هفته <?php echo esc_html( szp_fa_digits( $wk ) ); ?></span>
					<span class="szp-ev-cur-target">تارگت این هفته: <b><?php echo esc_html( szp_money( $target, $currency ) ); ?></b></span>
				</div>
				<p class="szp-ev-cur-hint">نتیجه‌ی این هفته را وارد کنید (روز ثبت نتیجه: <b><?php echo esc_html( self::day_name( self::DAY_RESULT ) ); ?></b>).</p>
				<div class="szp-ev-form" data-mode="result" data-target="<?php echo esc_attr( $target ); ?>">
					<div class="szp-ev-inrow">
						<input type="text" inputmode="numeric" class="szp-ev-amount" placeholder="نتیجه‌ی واقعی (عدد)" <?php disabled( ! $can_result ); ?>>
						<span class="szp-ev-cur-unit"><?php echo esc_html( $currency ); ?></span>
						<button type="button" class="szp-ev-submit button-primary" <?php disabled( ! $can_result ); ?>>ثبت نتیجه</button>
					</div>
					<div class="szp-ev-preview" aria-live="polite"></div>
					<?php if ( ! $can_result ) : ?>
						<p class="szp-ev-locked">ثبت نتیجه فقط در روز <b><?php echo esc_html( self::day_name( self::DAY_RESULT ) ); ?></b> امکان‌پذیر است.</p>
					<?php endif; ?>
					<div class="szp-ev-msg" aria-live="polite"></div>
				</div>
			</div>
			<?php
		else :
			$next = $latest ? ( (int) $latest->week_no + 1 ) : 1;
			?>
			<div class="szp-ev-current szp-ev-phase-target">
				<div class="szp-ev-cur-top">
					<span class="szp-ev-week-badge">هفته <?php echo esc_html( szp_fa_digits( $next ) ); ?></span>
					<span class="szp-ev-cur-target">تارگت جدید را مشخص کنید</span>
				</div>
				<p class="szp-ev-cur-hint">تارگت مالی این هفته را ثبت کنید (روز ثبت تارگت: <b><?php echo esc_html( self::day_name( self::DAY_TARGET ) ); ?></b>).</p>
				<div class="szp-ev-form" data-mode="target">
					<div class="szp-ev-inrow">
						<input type="text" inputmode="numeric" class="szp-ev-amount" placeholder="تارگت هفته (مثلاً ۵۰۰٬۰۰۰٬۰۰۰)" <?php disabled( ! $can_target ); ?>>
						<span class="szp-ev-cur-unit"><?php echo esc_html( $currency ); ?></span>
						<button type="button" class="szp-ev-submit button-primary" <?php disabled( ! $can_target ); ?>>ثبت تارگت</button>
					</div>
					<div class="szp-ev-preview" aria-live="polite"></div>
					<?php if ( ! $can_target ) : ?>
						<p class="szp-ev-locked">ثبت تارگت فقط در روز <b><?php echo esc_html( self::day_name( self::DAY_TARGET ) ); ?></b> امکان‌پذیر است.</p>
					<?php endif; ?>
					<div class="szp-ev-msg" aria-live="polite"></div>
				</div>
			</div>
			<?php
		endif;

		return ob_get_clean();
	}

	/** نمودار ستونی تحقق هفته‌ها (بدون وابستگی بیرونی؛ خط مبنای ۱۰۰٪). */
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
		// مقیاس: سقف ۱۵۰٪ تا «فراتر از تارگت» هم بالای خط مبنا دیده شود.
		$cap      = 150;
		$baseline = round( 100 / $cap * 100, 3 ); // درصد ارتفاع خط ۱۰۰٪ از پایین (≈۶۶.۶۷)

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
						$h    = round( max( 2, min( $pct, $cap ) / $cap * 100 ), 2 );
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
		</div>
		<?php
		return ob_get_clean();
	}

	/** جدول هفته‌های قبل با وضعیت و درصد. */
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
						<th>هفته</th><th>تارگت</th><th>نتیجه</th><th>تحقق</th><th>وضعیت</th>
					</tr></thead>
					<tbody>
					<?php foreach ( $list as $w ) :
						$st   = self::compute_status( $w->target, $w->result, $w->has_result );
						$meta = self::status_meta( $st );
						$pct  = $w->has_result ? self::pct( $w->target, $w->result ) : 0;
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
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
