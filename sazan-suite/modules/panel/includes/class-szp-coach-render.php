<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * کوچینگ کسب‌وکار — رندر تمام نماها (دانشجو و کوچ).
 * تنها منبع حقیقت رندر؛ توسط شورت‌کد و ویجت المنتور استفاده می‌شود.
 */
class SZP_Coach_Render {

	/* ==================== entry ==================== */

	public static function render( $uid, $course_hint = 0, $panel = '' ) {
		$is_coach_any = SZP_Coach::is_coach( $uid );
		$as_student   = isset( $_GET['as'] ) && $_GET['as'] === 'student';

		ob_start();
		echo '<div class="szp szp-coach">';

		if ( $is_coach_any && ! $as_student ) {
			self::coach_app( $uid, $course_hint );
		} else {
			self::student_app( $uid, $course_hint );
		}

		echo '</div>';
		return ob_get_clean();
	}

	protected static function base() {
		return remove_query_arg( array( 'cc', 'cj', 'cw', 'as', 'szp', 'id', 'room' ) );
	}

	protected static function notice() {
		echo '<div class="szp-co-toast" hidden></div>';
	}

	/* ==================== STUDENT ==================== */

	protected static function student_app( $uid, $course_hint ) {
		$courses = SZP_Coach::student_course_ids( $uid );
		if ( ! $courses ) {
			echo '<div class="szp-empty">در حال حاضر برنامه کوچینگ فعالی برای شما وجود ندارد.</div>';
			return;
		}

		$cid = (int) $course_hint;
		if ( ! in_array( $cid, $courses, true ) ) {
			$cid = ( count( $courses ) === 1 ) ? $courses[0] : 0;
		}

		if ( ! $cid ) {
			echo '<h2 class="szp-h">برنامه‌های کوچینگ من</h2><div class="szp-co-pick">';
			foreach ( $courses as $c ) {
				printf(
					'<a class="szp-co-pickcard" href="%s"><span class="szp-co-pickname">%s</span><span class="szp-co-pickcta">ورود به برنامه ‹</span></a>',
					esc_url( add_query_arg( 'cc', $c, self::base() ) ),
					esc_html( get_the_title( $c ) )
				);
			}
			echo '</div>';
			return;
		}

		$j     = SZP_Coach::ensure_journey( $cid, $uid );
		$stage = $j->stage;

		printf( '<h2 class="szp-h">کوچینگ کسب‌وکار — %s</h2>', esc_html( get_the_title( $cid ) ) );
		self::stepper( $stage );
		self::notice();

		if ( $stage === SZP_Coach::STAGE_SCAN ) {
			self::view_scan( $j );
		} elseif ( $stage === SZP_Coach::STAGE_FORM ) {
			self::view_form( $j );
		} elseif ( $stage === SZP_Coach::STAGE_REVIEW ) {
			self::view_review( $j );
		} else {
			self::view_tracking( $j, false );
		}
	}

	protected static function stepper( $stage ) {
		$steps = array(
			SZP_Coach::STAGE_SCAN     => 'اسکن کسب‌وکار',
			SZP_Coach::STAGE_FORM     => 'فرم تحلیلی',
			SZP_Coach::STAGE_REVIEW   => 'بررسی کوچ',
			SZP_Coach::STAGE_TRACKING => 'چرخه رشد هفتگی',
		);
		$order = array_keys( $steps );
		$cur   = array_search( $stage, $order, true );
		echo '<div class="szp-co-steps">';
		$i = 0;
		foreach ( $steps as $k => $label ) {
			$cls = ( $i < $cur ) ? 'done' : ( ( $i === $cur ) ? 'now' : '' );
			printf(
				'<div class="szp-co-step %s"><span class="szp-co-stepnum">%s</span><span class="szp-co-steplbl">%s</span></div>',
				esc_attr( $cls ), esc_html( szp_fa_digits( $i + 1 ) ), esc_html( $label )
			);
			$i++;
		}
		echo '</div>';
	}

	/* ----- step 1: scan ----- */

	protected static function view_scan( $j ) {
		$d   = SZP_Coach::journey_data( $j );
		$sw  = $d['swot'] ?? array();
		$profile = class_exists( 'SZP_Growth' ) ? SZP_Growth::profile( $j->user_id ) : array();
		$biz_name = ! empty( $profile['business_name'] ) ? $profile['business_name'] : ( $d['biz_name'] ?? '' );
		$industry = ! empty( $profile['industry'] ) ? $profile['industry'] : ( $d['industry'] ?? '' );
		$position = ! empty( $profile['position'] ) ? $profile['position'] : ( $d['position'] ?? '' );
		?>
		<section class="szp-sec szp-co-scan" data-course="<?php echo (int) $j->course_id; ?>">
			<h3 class="szp-sec-h">گام اول: اسکن و تحلیل کسب‌وکار</h3>
			<p class="szp-co-help">وضعیت فعلی کسب‌وکارتان را ثبت کنید تا کوچ و خودتان نقطه شروع رشد را بشناسید.</p>

			<div class="szp-co-grid2">
				<label class="szp-co-fld"><span>نام کسب‌وکار *</span><input type="text" data-f="biz_name" value="<?php echo esc_attr( $biz_name ); ?>" readonly></label>
				<label class="szp-co-fld"><span>حوزه / صنعت</span><input type="text" data-f="industry" value="<?php echo esc_attr( $industry ); ?>" readonly></label>
				<label class="szp-co-fld"><span>سمت در مجموعه</span><input type="text" data-f="position" value="<?php echo esc_attr( $position ); ?>" readonly></label>
				<label class="szp-co-fld"><span>مرحله کسب‌وکار</span><input type="text" data-f="biz_stage" value="<?php echo esc_attr( $d['biz_stage'] ?? '' ); ?>" placeholder="ایده / نوپا / در حال رشد / بالغ"></label>
				<label class="szp-co-fld"><span>تعداد کارکنان</span><input type="text" data-f="employees" value="<?php echo esc_attr( $d['employees'] ?? '' ); ?>" placeholder="مثلاً: ۵ نفر"></label>
			</div>

			<h4 class="szp-co-subh">تحلیل SWOT</h4>
			<div class="szp-co-swot">
				<label class="szp-co-fld s"><span>نقاط قوت</span><textarea data-f="swot_s" rows="3"><?php echo esc_textarea( $sw['s'] ?? '' ); ?></textarea></label>
				<label class="szp-co-fld w"><span>نقاط ضعف</span><textarea data-f="swot_w" rows="3"><?php echo esc_textarea( $sw['w'] ?? '' ); ?></textarea></label>
				<label class="szp-co-fld o"><span>فرصت‌ها</span><textarea data-f="swot_o" rows="3"><?php echo esc_textarea( $sw['o'] ?? '' ); ?></textarea></label>
				<label class="szp-co-fld t"><span>تهدیدها</span><textarea data-f="swot_t" rows="3"><?php echo esc_textarea( $sw['t'] ?? '' ); ?></textarea></label>
			</div>

			<label class="szp-co-fld"><span>توضیحات تکمیلی (اختیاری)</span><textarea data-f="note" rows="3"><?php echo esc_textarea( $d['note'] ?? '' ); ?></textarea></label>

			<h4 class="szp-co-subh">شاخص‌های کلیدی عملکرد (KPI)</h4>
			<p class="szp-co-help">شاخص‌هایی که می‌خواهید رشدشان را هفته‌به‌هفته بسنجید (درآمد، تعداد مشتری، نرخ تبدیل و…). مقدار فعلی = پایه، مقدار آرمانی = هدف.</p>
			<?php self::kpi_editor( SZP_Coach::get_kpis( $j->id ) ); ?>

			<div class="szp-co-actions">
				<button type="button" class="szp-co-btn primary" data-act="save-scan">ثبت اسکن و رفتن به مرحله بعد</button>
			</div>
		</section>
		<?php
	}

	/** ویرایشگر تکرارشونده شاخص‌ها (مشترک دانشجو/کوچ). */
	protected static function kpi_editor( $kpis ) {
		echo '<div class="szp-co-kpis" data-repeater="kpi">';
		echo '<div class="szp-co-kpihead"><span>نام شاخص</span><span>واحد</span><span>جهت بهبود</span><span>مقدار پایه</span><span>هدف</span><span></span></div>';
		echo '<div class="szp-co-kpirows">';
		if ( $kpis ) {
			foreach ( $kpis as $k ) {
				self::kpi_row( $k->name, $k->unit, $k->direction, $k->baseline, $k->target );
			}
		} else {
			self::kpi_row();
		}
		echo '</div>';
		echo '<button type="button" class="szp-co-btn ghost" data-add="kpi">+ افزودن شاخص</button>';
		echo '</div>';
	}

	protected static function kpi_row( $name = '', $unit = '', $dir = 'up', $base = '', $target = '' ) {
		?>
		<div class="szp-co-kpirow">
			<input type="text" data-k="name" value="<?php echo esc_attr( $name ); ?>" placeholder="مثلاً درآمد ماهانه">
			<input type="text" data-k="unit" value="<?php echo esc_attr( $unit ); ?>" placeholder="تومان">
			<select data-k="direction">
				<option value="up" <?php selected( $dir, 'up' ); ?>>بیشتر بهتر ▲</option>
				<option value="down" <?php selected( $dir, 'down' ); ?>>کمتر بهتر ▼</option>
			</select>
			<input type="number" step="any" data-k="baseline" value="<?php echo esc_attr( $base ); ?>">
			<input type="number" step="any" data-k="target" value="<?php echo esc_attr( $target ); ?>">
			<button type="button" class="szp-co-del" data-del="kpi" aria-label="حذف">×</button>
		</div>
		<?php
	}

	/* ----- step 2: form ----- */

	protected static function view_form( $j ) {
		$def  = SZP_Coach::form_def( $j->course_id );
		$prev = SZP_Coach::get_answers( $j->id );
		?>
		<section class="szp-sec szp-co-form" data-course="<?php echo (int) $j->course_id; ?>">
			<h3 class="szp-sec-h">گام دوم: فرم تحلیلی</h3>
			<p class="szp-co-help">به سوالات زیر با دقت پاسخ دهید؛ این پاسخ‌ها مبنای طراحی اقدامات توسط کوچ شماست.</p>
			<?php foreach ( $def as $i => $f ) : $val = $prev[ $i ] ?? ''; ?>
				<div class="szp-co-q" data-i="<?php echo (int) $i; ?>">
					<label class="szp-co-qlbl"><?php echo esc_html( szp_fa_digits( $i + 1 ) . '. ' . $f['q'] ); ?></label>
					<?php if ( $f['type'] === 'text' ) : ?>
						<input type="text" data-a value="<?php echo esc_attr( $val ); ?>">
					<?php elseif ( $f['type'] === 'number' ) : ?>
						<input type="number" step="any" data-a value="<?php echo esc_attr( $val ); ?>">
					<?php elseif ( $f['type'] === 'scale' ) : ?>
						<div class="szp-co-scale">
							<?php for ( $n = 1; $n <= 5; $n++ ) : ?>
								<label class="<?php echo ( (string) $val === (string) $n ) ? 'on' : ''; ?>">
									<input type="radio" name="szpq<?php echo (int) $i; ?>" data-a value="<?php echo $n; ?>" <?php checked( (string) $val, (string) $n ); ?>>
									<?php echo esc_html( szp_fa_digits( $n ) ); ?>
								</label>
							<?php endfor; ?>
						</div>
					<?php elseif ( $f['type'] === 'choice' ) : ?>
						<select data-a>
							<option value="">— انتخاب —</option>
							<?php foreach ( $f['opts'] as $opt ) : ?>
								<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $val, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<textarea data-a rows="3"><?php echo esc_textarea( $val ); ?></textarea>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<div class="szp-co-actions">
				<button type="button" class="szp-co-btn primary" data-act="submit-form" data-week="0">ثبت پاسخ‌ها</button>
			</div>
		</section>
		<?php
	}

	/* ----- step 3: review ----- */

	protected static function view_review( $j ) {
		?>
		<section class="szp-sec szp-co-review">
			<div class="szp-co-waiting">
				<div class="szp-co-pulse"></div>
				<h3>در انتظار بررسی کوچ</h3>
				<p class="szp-co-help">اطلاعات کسب‌وکار و پاسخ‌های شما ثبت شد. کوچ به‌زودی اقدامات و تکالیف هفته اول را برایتان مشخص می‌کند.</p>
			</div>
			<?php self::scan_summary( $j ); ?>
		</section>
		<?php
	}

	protected static function scan_summary( $j ) {
		$d  = SZP_Coach::journey_data( $j );
		$sw = $d['swot'] ?? array();
		echo '<div class="szp-co-summary"><h4 class="szp-co-subh">خلاصه اسکن</h4><div class="szp-co-tags">';
		foreach ( array( 'biz_name' => 'کسب‌وکار', 'industry' => 'صنعت', 'position' => 'سمت', 'biz_stage' => 'مرحله', 'employees' => 'کارکنان' ) as $k => $lbl ) {
			if ( ! empty( $d[ $k ] ) ) {
				printf( '<span class="szp-co-tag"><i>%s</i> %s</span>', esc_html( $lbl ), esc_html( $d[ $k ] ) );
			}
		}
		echo '</div>';
		$labels = array( 's' => 'قوت', 'w' => 'ضعف', 'o' => 'فرصت', 't' => 'تهدید' );
		echo '<div class="szp-co-swotview">';
		foreach ( $labels as $k => $lbl ) {
			if ( ! empty( $sw[ $k ] ) ) {
				printf( '<div class="szp-co-swotbox %s"><b>%s</b><p>%s</p></div>', esc_attr( $k ), esc_html( $lbl ), nl2br( esc_html( $sw[ $k ] ) ) );
			}
		}
		echo '</div></div>';
	}

	/* ----- step 4/5/tracking ----- */

	protected static function view_tracking( $j, $is_coach ) {
		$weeks   = SZP_Coach::get_weeks( $j->id );
		$active  = null;
		foreach ( $weeks as $w ) { if ( $w->status !== 'draft' ) { $active = $w; } } // آخرین هفته فعال
		$payload = SZP_Coach::chart_payload( $j );
		$snap    = SZP_Coach::kpi_snapshot( $j );
		$idx     = end( $payload['growth'] );
		$idx     = $idx === false ? 0 : $idx;

		/* hero + growth chart */
		?>
		<section class="szp-sec szp-co-hero">
			<div class="szp-co-herowrap">
				<div class="szp-co-gauge">
					<span class="szp-co-gaugelbl">شاخص رشد فعلی</span>
					<span class="szp-co-gaugeval" data-count="<?php echo esc_attr( $idx ); ?>"><?php echo esc_html( szp_fa_digits( $idx ) ); ?>٪</span>
					<span class="szp-co-gaugesub">بر اساس میانگین پیشرفت شاخص‌ها به‌سمت هدف</span>
				</div>
				<div class="szp-co-chartbox">
					<div class="szp-co-charttitle">روند رشد هفتگی</div>
					<div class="szp-chart" data-type="growth" data-chart='<?php echo esc_attr( wp_json_encode( $payload ) ); ?>'></div>
				</div>
			</div>
		</section>
		<?php

		/* KPI snapshot cards */
		if ( $snap ) {
			echo '<div class="szp-co-kpicards">';
			foreach ( $snap as $s ) {
				self::kpi_card( $s );
			}
			echo '</div>';
		}

		/* per-KPI trend charts */
		if ( ! empty( $payload['kpis'] ) && count( $payload['labels'] ) > 1 ) {
			echo '<section class="szp-sec"><h3 class="szp-sec-h">روند تک‌تک شاخص‌ها</h3><div class="szp-co-mini">';
			foreach ( $payload['kpis'] as $ki => $kp ) {
				$one = array( 'labels' => $payload['labels'], 'kpi' => $kp );
				printf(
					'<div class="szp-co-minicard"><div class="szp-co-minititle">%s</div><div class="szp-chart" data-type="kpi" data-chart=\'%s\'></div></div>',
					esc_html( $kp['name'] ), esc_attr( wp_json_encode( $one ) )
				);
			}
			echo '</div></section>';
		}

		/* current week */
		if ( $active ) {
			self::week_panel( $j, $active, $is_coach, true );
		} elseif ( $is_coach ) {
			self::week_editor_empty( $j );
		}

		/* history */
		$history = array_filter( $weeks, function ( $w ) use ( $active ) {
			return $w->status !== 'draft' && ( ! $active || $w->week_no !== $active->week_no );
		} );
		if ( $history ) {
			echo '<section class="szp-sec szp-co-history"><h3 class="szp-sec-h">بایگانی هفته‌ها</h3>';
			foreach ( array_reverse( $history ) as $w ) {
				self::week_panel( $j, $w, $is_coach, false );
			}
			echo '</section>';
		}

		if ( $is_coach ) {
			printf(
				'<div class="szp-co-actions"><button type="button" class="szp-co-btn primary" data-act="new-week" data-journey="%d">+ شروع هفته بعد</button></div>',
				(int) $j->id
			);
		}
	}

	protected static function kpi_card( $s ) {
		$k    = $s['kpi'];
		$cur  = $s['current'];
		$prev = $s['prev'];
		$pct  = $s['progress'] !== null ? round( $s['progress'] * 100 ) : 0;
		$delta = '';
		if ( $cur !== null && $prev !== null && $prev != 0 ) {
			$dv   = $cur - $prev;
			$up   = ( $k->direction === 'up' ) ? ( $dv > 0 ) : ( $dv < 0 );
			$arrow = $dv > 0 ? '▲' : ( $dv < 0 ? '▼' : '—' );
			$delta = sprintf(
				'<span class="szp-co-delta %s">%s %s</span>',
				$up ? 'good' : ( $dv == 0 ? '' : 'bad' ),
				$arrow,
				esc_html( szp_fa_digits( round( abs( $dv ), 2 ) ) )
			);
		}
		printf(
			'<div class="szp-co-kpicard"><div class="szp-co-kpiname">%s</div>'
			. '<div class="szp-co-kpival">%s <i>%s</i></div>%s'
			. '<div class="szp-co-bar"><span style="width:%d%%"></span></div>'
			. '<div class="szp-co-kpifoot">هدف: %s — %s٪ مسیر</div></div>',
			esc_html( $k->name ),
			esc_html( $cur !== null ? szp_fa_digits( rtrim( rtrim( number_format( $cur, 2, '.', '' ), '0' ), '.' ) ) : '—' ),
			esc_html( $k->unit ),
			$delta,
			max( 0, min( 100, $pct ) ),
			esc_html( szp_fa_digits( rtrim( rtrim( number_format( (float) $k->target, 2, '.', '' ), '0' ), '.' ) ) ),
			esc_html( szp_fa_digits( $pct ) )
		);
	}

	/**
	 * پنل یک هفته. $is_coach کنترل می‌کند نمای ویرایش کوچ یا نمای دانشجو نمایش داده شود.
	 * $open: باز بودن پیش‌فرض (هفته جاری).
	 */
	protected static function week_panel( $j, $w, $is_coach, $open ) {
		$actions = SZP_Coach::decode( $w->actions );
		$tasks   = SZP_Coach::decode( $w->tasks );
		$metrics = SZP_Coach::decode( $w->metrics );
		$kpis    = SZP_Coach::get_kpis( $j->id );
		$title   = $w->title ? $w->title : ( 'هفته ' . szp_fa_digits( $w->week_no ) );
		$growth  = SZP_Coach::week_growth( $kpis, $metrics );
		?>
		<section class="szp-sec szp-co-week <?php echo $open ? 'open' : ''; ?>" data-week="<?php echo (int) $w->week_no; ?>" data-journey="<?php echo (int) $j->id; ?>" data-course="<?php echo (int) $j->course_id; ?>">
			<?php
			$sess_ts   = ! empty( $w->session_at ) ? szp_ts_from_datetime( $w->session_at ) : 0;
			$sess_disp = $sess_ts ? szp_format_datetime( $sess_ts ) : '';
			?>
			<div class="szp-co-weekhead" data-toggle="week">
				<h3 class="szp-sec-h"><?php echo esc_html( $title ); ?></h3>
				<?php if ( $sess_disp ) : ?><span class="szp-co-weeksession">🗓 جلسه: <?php echo esc_html( $sess_disp ); ?></span><?php endif; ?>
				<span class="szp-co-weekgrowth">رشد: <?php echo esc_html( szp_fa_digits( $growth ) ); ?>٪</span>
				<span class="szp-co-caret">▾</span>
			</div>
			<div class="szp-co-weekbody">

				<?php if ( $is_coach ) : ?>
					<?php self::week_coach_editor( $w, $actions, $tasks ); ?>
				<?php else : ?>
					<?php self::week_student_view( $w, $actions, $tasks ); ?>
				<?php endif; ?>

				<?php /* coach feedback */ if ( ! empty( $w->coach_feedback ) && ! $is_coach ) : ?>
					<div class="szp-co-feedback"><b>بازخورد کوچ:</b><p><?php echo nl2br( esc_html( $w->coach_feedback ) ); ?></p></div>
				<?php endif; ?>

				<?php /* metrics record (student) / view */ ?>
				<div class="szp-co-record">
					<h4 class="szp-co-subh">ثبت نتایج هفته</h4>
					<?php if ( $kpis ) : ?>
						<div class="szp-co-metrics">
							<?php foreach ( $kpis as $k ) : $mv = $metrics[ $k->id ] ?? ''; ?>
								<label class="szp-co-metric">
									<span><?php echo esc_html( $k->name ); ?> <i><?php echo esc_html( $k->unit ); ?></i></span>
									<input type="number" step="any" data-m="<?php echo (int) $k->id; ?>" value="<?php echo esc_attr( $mv ); ?>" <?php disabled( $is_coach ); ?>>
								</label>
							<?php endforeach; ?>
						</div>
						<label class="szp-co-fld"><span>گزارش/توضیح دانشجو</span><textarea data-report rows="3" <?php disabled( $is_coach ); ?>><?php echo esc_textarea( (string) $w->student_report ); ?></textarea></label>
						<?php if ( ! $is_coach ) : ?>
							<div class="szp-co-actions">
								<button type="button" class="szp-co-btn primary" data-act="record" data-week="<?php echo (int) $w->week_no; ?>">ثبت نتایج این هفته</button>
							</div>
						<?php elseif ( $w->student_report ) : ?>
							<div class="szp-co-feedback"><b>گزارش دانشجو:</b><p><?php echo nl2br( esc_html( $w->student_report ) ); ?></p></div>
						<?php endif; ?>
					<?php else : ?>
						<p class="szp-co-help">هنوز شاخصی تعریف نشده است.</p>
					<?php endif; ?>
				</div>

			</div>
		</section>
		<?php
	}

	/** نمای دانشجو از اقدامات/تکالیف هفته. */
	protected static function week_student_view( $w, $actions, $tasks ) {
		if ( $actions ) {
			echo '<h4 class="szp-co-subh">اقدامات این هفته</h4><ul class="szp-co-checklist">';
			foreach ( $actions as $i => $a ) {
				printf(
					'<li class="%s"><button type="button" class="szp-co-check" data-act="toggle" data-kind="action" data-index="%d" data-week="%d"></button>'
					. '<div class="szp-co-itemtext"><b>%s</b>%s</div></li>',
					! empty( $a['done'] ) ? 'done' : '',
					(int) $i, (int) $w->week_no,
					esc_html( $a['t'] ?? '' ),
					! empty( $a['d'] ) ? '<p>' . nl2br( esc_html( $a['d'] ) ) . '</p>' : ''
				);
			}
			echo '</ul>';
		}
		if ( $tasks ) {
			echo '<h4 class="szp-co-subh">تکالیف این هفته</h4><div class="szp-co-tasks">';
			$st_lbl = array( 'todo' => 'انجام‌نشده', 'doing' => 'در حال انجام', 'done' => 'انجام شد' );
			foreach ( $tasks as $i => $t ) {
				$st = $t['st'] ?? 'todo';
				echo '<div class="szp-co-task st-' . esc_attr( $st ) . '">';
				printf( '<div class="szp-co-itemtext"><b>%s</b>%s%s</div>',
					esc_html( $t['t'] ?? '' ),
					! empty( $t['d'] ) ? '<p>' . nl2br( esc_html( $t['d'] ) ) . '</p>' : '',
					! empty( $t['due'] ) ? '<span class="szp-co-due">مهلت: ' . esc_html( szp_fa_digits( $t['due'] ) ) . '</span>' : ''
				);
				echo '<div class="szp-co-taskst">';
				foreach ( $st_lbl as $key => $lbl ) {
					printf(
						'<button type="button" class="%s" data-act="toggle" data-kind="task" data-st="%s" data-index="%d" data-week="%d">%s</button>',
						$st === $key ? 'on' : '', esc_attr( $key ), (int) $i, (int) $w->week_no, esc_html( $lbl )
					);
				}
				echo '</div></div>';
			}
			echo '</div>';
		}
		if ( ! $actions && ! $tasks ) {
			echo '<p class="szp-co-help">کوچ هنوز اقدام یا تکلیفی برای این هفته ثبت نکرده است.</p>';
		}
	}

	/* ==================== COACH ==================== */

	protected static function coach_app( $uid, $course_hint ) {
		$courses = SZP_Coach::coach_course_ids( $uid );
		if ( ! $courses ) {
			echo '<div class="szp-empty">هیچ دوره کوچینگ‌فعالی برای مدیریت ندارید. در ویرایش دوره، «کوچینگ کسب‌وکار» را فعال کنید.</div>';
			return;
		}
		$cc = isset( $_GET['cc'] ) ? absint( $_GET['cc'] ) : 0;
		if ( ! in_array( $cc, array_map( 'intval', $courses ), true ) ) {
			$cc = $courses[0];
		}
		$cj = isset( $_GET['cj'] ) ? absint( $_GET['cj'] ) : 0;

		echo '<div class="szp-co-coachbar"><span class="szp-co-badge">حالت کوچ</span>';
		echo '<select class="szp-co-courses" data-base="' . esc_attr( self::base() ) . '">';
		foreach ( $courses as $c ) {
			printf( '<option value="%d" %s>%s</option>', $c, selected( $cc, $c, false ), esc_html( get_the_title( $c ) ) );
		}
		echo '</select></div>';
		self::notice();

		if ( $cj ) {
			$j = SZP_Coach::get_journey_by_id( $cj );
			if ( $j && (int) $j->course_id === $cc ) {
				self::coach_manage( $j );
				return;
			}
		}
		self::coach_roster( $cc );
	}

	protected static function coach_roster( $course_id ) {
		$rows = SZP_Coach::course_journeys( $course_id );
		printf( '<h2 class="szp-h">کارآموزان — %s</h2>', esc_html( get_the_title( $course_id ) ) );
		if ( ! $rows ) {
			echo '<div class="szp-empty">هنوز کسی برنامه را آغاز نکرده است.</div>';
			return;
		}
		$stages = array(
			SZP_Coach::STAGE_SCAN     => 'اسکن',
			SZP_Coach::STAGE_FORM     => 'فرم',
			SZP_Coach::STAGE_REVIEW   => 'بررسی کوچ',
			SZP_Coach::STAGE_TRACKING => 'چرخه هفتگی',
		);
		echo '<div class="szp-co-roster">';
		foreach ( $rows as $j ) {
			$u    = get_userdata( $j->user_id );
			$d    = SZP_Coach::journey_data( $j );
			$pl   = SZP_Coach::chart_payload( $j );
			$idx  = end( $pl['growth'] );
			$idx  = $idx === false ? 0 : $idx;
			$wk   = SZP_Coach::last_week_no( $j->id );
			$name = ! empty( $d['biz_name'] ) ? $d['biz_name'] : ( $u ? $u->display_name : ( 'کاربر #' . $j->user_id ) );
			$need = ( $j->stage === SZP_Coach::STAGE_REVIEW ) ? '<span class="szp-co-need">نیازمند اقدام</span>' : '';
			printf(
				'<a class="szp-co-rcard" href="%s"><div class="szp-co-rtop"><b>%s</b>%s</div>'
				. '<div class="szp-co-rmeta"><span>%s</span><span>کاربر: %s</span></div>'
				. '<div class="szp-co-rfoot"><span class="szp-co-rstage">%s</span><span class="szp-co-rwk">هفته %s</span><span class="szp-co-rgrowth">رشد %s٪</span></div></a>',
				esc_url( add_query_arg( array( 'cc' => $course_id, 'cj' => $j->id ), self::base() ) ),
				esc_html( $name ), $need,
				esc_html( $d['industry'] ?? '—' ),
				esc_html( $u ? $u->display_name : ( '#' . $j->user_id ) ),
				esc_html( $stages[ $j->stage ] ?? $j->stage ),
				esc_html( szp_fa_digits( $wk ) ),
				esc_html( szp_fa_digits( $idx ) )
			);
		}
		echo '</div>';
	}

	protected static function coach_manage( $j ) {
		$u = get_userdata( $j->user_id );
		$d = SZP_Coach::journey_data( $j );
		printf(
			'<a class="szp-back" href="%s">بازگشت به لیست کارآموزان</a>',
			esc_url( add_query_arg( 'cc', $j->course_id, self::base() ) )
		);
		printf(
			'<h2 class="szp-h">%s <span class="szp-co-sub">— %s</span></h2>',
			esc_html( ! empty( $d['biz_name'] ) ? $d['biz_name'] : ( $u ? $u->display_name : '#' . $j->user_id ) ),
			esc_html( $u ? $u->display_name : '' )
		);

		self::scan_summary( $j );
		self::coach_answers( $j );

		/* KPI editor (coach) */
		echo '<section class="szp-sec szp-co-kpiedit" data-journey="' . (int) $j->id . '"><h3 class="szp-sec-h">شاخص‌های کلیدی</h3>';
		self::kpi_editor( SZP_Coach::get_kpis( $j->id ) );
		echo '<div class="szp-co-actions"><button type="button" class="szp-co-btn ghost" data-act="set-kpis" data-journey="' . (int) $j->id . '">ذخیره شاخص‌ها</button></div></section>';

		/* tracking with coach editors */
		if ( SZP_Coach::last_week_no( $j->id ) > 0 ) {
			self::view_tracking( $j, true );
		} else {
			self::week_editor_empty( $j );
		}
	}

	protected static function coach_answers( $j ) {
		$def = SZP_Coach::form_def( $j->course_id );
		$ans = SZP_Coach::get_answers( $j->id );
		if ( ! $def || ! $ans ) { return; }
		echo '<section class="szp-sec"><h3 class="szp-sec-h">پاسخ‌های فرم</h3><div class="szp-co-answers">';
		foreach ( $def as $i => $f ) {
			printf( '<div class="szp-co-ans"><b>%s</b><p>%s</p></div>',
				esc_html( $f['q'] ),
				nl2br( esc_html( $ans[ $i ] ?? '—' ) ) );
		}
		echo '</div></section>';
	}

	protected static function week_editor_empty( $j ) {
		?>
		<section class="szp-sec szp-co-week open" data-week="1" data-journey="<?php echo (int) $j->id; ?>">
			<h3 class="szp-sec-h">راه‌اندازی هفته اول</h3>
			<p class="szp-co-help">بر اساس اسکن و پاسخ‌ها، اقدامات و تکالیف هفته اول را تعریف کنید.</p>
			<div class="szp-co-weekbody"><?php self::week_coach_editor( null, array(), array() ); ?></div>
		</section>
		<?php
	}

	/** ویرایشگر اقدامات/تکالیف/بازخورد یک هفته توسط کوچ. */
	protected static function week_coach_editor( $w, $actions, $tasks ) {
		$wn   = $w ? (int) $w->week_no : 1;
		$fb   = $w ? (string) $w->coach_feedback : '';
		$ttl  = $w ? (string) $w->title : '';
		?>
		<?php
		$session_at = $w ? (string) $w->session_at : '';
		$session_disp = '';
		if ( $session_at !== '' ) {
			$sts          = szp_ts_from_datetime( $session_at );
			$session_disp = $sts ? szp_format_datetime( $sts ) : '';
		}
		?>
		<div class="szp-co-editor">
			<label class="szp-co-fld"><span>عنوان هفته</span><input type="text" data-w="title" value="<?php echo esc_attr( $ttl ); ?>" placeholder="هفته <?php echo esc_attr( szp_fa_digits( $wn ) ); ?>"></label>

			<label class="szp-co-fld"><span>تاریخ و ساعت جلسه‌ی کوچینگ</span>
				<span class="szp-jp" data-jp="datetime">
					<input type="text" class="szp-jp-disp" readonly placeholder="انتخاب تاریخ و ساعت جلسه" value="<?php echo esc_attr( $session_disp ); ?>">
					<input type="hidden" class="szp-jp-val" data-w="session_at" value="<?php echo esc_attr( $session_at ); ?>">
				</span>
			</label>

			<h4 class="szp-co-subh">اقدامات (Action)</h4>
			<div class="szp-co-rep" data-repeater="action">
				<div class="szp-co-reprows">
					<?php if ( $actions ) : foreach ( $actions as $a ) : self::action_row( $a['t'] ?? '', $a['d'] ?? '' ); endforeach; else : self::action_row(); endif; ?>
				</div>
				<button type="button" class="szp-co-btn ghost" data-add="action">+ افزودن اقدام</button>
			</div>

			<h4 class="szp-co-subh">تکالیف (Task)</h4>
			<div class="szp-co-rep" data-repeater="task">
				<div class="szp-co-reprows">
					<?php if ( $tasks ) : foreach ( $tasks as $t ) : self::task_row( $t['t'] ?? '', $t['d'] ?? '', $t['due'] ?? '' ); endforeach; else : self::task_row(); endif; ?>
				</div>
				<button type="button" class="szp-co-btn ghost" data-add="task">+ افزودن تکلیف</button>
			</div>

			<label class="szp-co-fld"><span>بازخورد کوچ</span><textarea data-w="feedback" rows="3"><?php echo esc_textarea( $fb ); ?></textarea></label>

			<div class="szp-co-actions">
				<button type="button" class="szp-co-btn primary" data-act="set-week" data-week="<?php echo $wn; ?>">ذخیره و ابلاغ هفته</button>
			</div>
		</div>
		<?php
	}

	protected static function action_row( $t = '', $d = '' ) {
		?>
		<div class="szp-co-reprow">
			<input type="text" data-r="t" value="<?php echo esc_attr( $t ); ?>" placeholder="عنوان اقدام">
			<input type="text" data-r="d" value="<?php echo esc_attr( $d ); ?>" placeholder="توضیح (اختیاری)">
			<button type="button" class="szp-co-del" data-del="rep" aria-label="حذف">×</button>
		</div>
		<?php
	}

	protected static function task_row( $t = '', $d = '', $due = '' ) {
		?>
		<div class="szp-co-reprow task">
			<input type="text" data-r="t" value="<?php echo esc_attr( $t ); ?>" placeholder="عنوان تکلیف">
			<input type="text" data-r="d" value="<?php echo esc_attr( $d ); ?>" placeholder="توضیح (اختیاری)">
			<input type="text" data-r="due" class="szp-jp-date" readonly value="<?php echo esc_attr( $due ); ?>" placeholder="مهلت (انتخاب از تقویم)">
			<button type="button" class="szp-co-del" data-del="rep" aria-label="حذف">×</button>
		</div>
		<?php
	}
}
