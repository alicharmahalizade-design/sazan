<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Single source of truth for rendering each panel section.
 * Used by both the panel templates and the Elementor widgets.
 * Conditional sections return '' when they have no content.
 */
class SZP_Render {

	/* ==================== COURSE ==================== */

	public static function course_hero( $course_id ) {
		$course = get_post( $course_id );
		if ( ! $course ) {
			return '';
		}
		$instr = get_post_meta( $course_id, '_szp_instructor', true );
		ob_start(); ?>
		<div class="szp-hero">
			<h2><?php echo esc_html( $course->post_title ); ?></h2>
			<?php if ( $instr ) : ?><p class="szp-hero-sub">مدرس: <?php echo esc_html( $instr ); ?></p><?php endif; ?>
		</div>
		<?php return ob_get_clean();
	}

	public static function course_identity( $course_id ) {
		$course = get_post( $course_id );
		if ( ! $course ) {
			return '';
		}
		$goals = get_post_meta( $course_id, '_szp_goals', true );

		// Normalize syllabus (supports old flat strings and new nested arrays).
		$syll = array();
		foreach ( (array) get_post_meta( $course_id, '_szp_syllabus', true ) as $it ) {
			if ( is_array( $it ) ) {
				$t    = trim( (string) ( $it['title'] ?? '' ) );
				$subs = array_values( array_filter( array_map( 'strval', (array) ( $it['subs'] ?? array() ) ), 'strlen' ) );
				if ( $t !== '' || $subs ) {
					$syll[] = array( 'title' => $t, 'subs' => $subs );
				}
			} else {
				$t = trim( (string) $it );
				if ( $t !== '' ) {
					$syll[] = array( 'title' => $t, 'subs' => array() );
				}
			}
		}
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">اطلاعات و شناسنامه دوره</h3>
			<?php if ( trim( $course->post_content ) !== '' ) : ?>
				<div class="szp-rich"><?php echo wp_kses_post( apply_filters( 'the_content', $course->post_content ) ); ?></div>
			<?php endif; ?>
			<?php if ( $syll ) : ?>
				<h4>سرفصل‌ها</h4>
				<ul class="szp-list">
					<?php foreach ( $syll as $it ) : ?>
						<li>
							<?php echo esc_html( $it['title'] ); ?>
							<?php if ( ! empty( $it['subs'] ) ) : ?>
								<ul class="szp-sublist"><?php foreach ( $it['subs'] as $sub ) { echo '<li>' . esc_html( $sub ) . '</li>'; } ?></ul>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $goals ) : ?>
				<h4>اهداف نهایی</h4>
				<p class="szp-text"><?php echo nl2br( esc_html( $goals ) ); ?></p>
			<?php endif; ?>
		</section>
		<?php return ob_get_clean();
	}

	public static function course_schedule( $course_id ) {
		$sessions = SZP_Frontend::course_sessions( $course_id );
		$next     = $sessions['next'];
		$past     = array_reverse( $sessions['past'] ); // newest first
		$ordered  = array_reverse( $sessions['all'] );  // newest first; undated last
		$now      = time();
		ob_start(); ?>
		<section class="szp-sec szp-sec-schedule">
			<h3 class="szp-sec-h">زمان‌بندی جلسات</h3>
			<?php if ( $ordered ) : ?><p class="szp-sched-hint">برای دیدن هر جلسه، روی آن بزنید.</p><?php endif; ?>

			<div class="szp-sched-classic">
				<?php if ( $next ) : $n = $next; ?>
					<div class="szp-next">
						<div class="szp-next-info">
							<span class="szp-badge">جلسه بعدی</span>
							<a class="szp-next-title" href="<?php echo SZP_Frontend::url_session( $n['id'] ); ?>"><?php echo esc_html( $n['title'] ); ?></a>
							<span class="szp-next-date"><?php echo esc_html( szp_format_datetime( $n['ts'] ) ); ?></span>
						</div>
						<?php echo szp_countdown_html( $n['ts'] ); ?>
					</div>
				<?php endif; ?>
				<?php if ( $past ) : ?>
					<h4>جلسات برگزارشده</h4>
					<ul class="szp-sessions">
						<?php foreach ( $past as $s ) : ?>
							<li>
								<a href="<?php echo SZP_Frontend::url_session( $s['id'] ); ?>"><?php echo esc_html( $s['title'] ); ?></a>
								<?php if ( $s['ts'] ) : ?><span class="szp-s-date"><?php echo esc_html( szp_format_datetime( $s['ts'] ) ); ?></span><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! $next ) : ?>
					<p class="szp-text">هنوز جلسه‌ای ثبت نشده است.</p>
				<?php endif; ?>
			</div>

			<div class="szp-sched-poster">
				<?php if ( $ordered ) : ?>
					<div class="szp-posters">
						<?php foreach ( $ordered as $i => $s ) :
							$thumb     = get_the_post_thumbnail_url( $s['id'], 'medium_large' );
							$is_future = $s['ts'] && $s['ts'] > $now;
							$cls       = ( $i < 8 ) ? '' : ( ( $i < 12 ) ? ' is-teaser' : ' is-hidden' );
							$style     = $thumb ? 'background-image:url(\'' . esc_url( $thumb ) . '\')' : '';
							?>
							<a class="szp-poster<?php echo $cls; ?><?php echo $thumb ? '' : ' szp-poster-nobg'; ?>" href="<?php echo SZP_Frontend::url_session( $s['id'] ); ?>" style="<?php echo esc_attr( $style ); ?>">
								<span class="szp-poster-ov"></span>
								<span class="szp-poster-body">
									<?php if ( $s['ts'] ) : ?><span class="szp-poster-badge <?php echo $is_future ? 'is-next' : 'is-past'; ?>"><?php echo $is_future ? 'پیش‌رو' : 'برگزارشده'; ?></span><?php endif; ?>
									<span class="szp-poster-title"><?php echo esc_html( $s['title'] ); ?></span>
									<?php if ( $s['ts'] ) : ?><span class="szp-poster-date"><?php echo esc_html( szp_format_datetime( $s['ts'] ) ); ?></span><?php endif; ?>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
					<?php if ( count( $ordered ) > 8 ) : ?>
						<div class="szp-more-wrap"><button type="button" class="szp-more-btn">مشاهده بیشتر</button></div>
					<?php endif; ?>
				<?php else : ?>
					<p class="szp-text">هنوز جلسه‌ای ثبت نشده است.</p>
				<?php endif; ?>
			</div>
		</section>
		<?php return ob_get_clean();
	}

	public static function course_announcements( $course_id ) {
		$ann = (array) get_post_meta( $course_id, '_szp_announce', true );
		if ( empty( $ann['text'] ) ) {
			return '';
		}
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">تابلو اعلانات کلاس</h3>
			<ul class="szp-announce">
				<?php foreach ( $ann['text'] as $i => $t ) :
					if ( $t === '' ) {
						continue;
					} ?>
					<li>
						<span class="szp-an-dot"></span>
						<span class="szp-an-text"><?php echo esc_html( $t ); ?></span>
						<?php if ( ! empty( $ann['date'][ $i ] ) ) : ?><span class="szp-an-date"><?php echo esc_html( $ann['date'][ $i ] ); ?></span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php return ob_get_clean();
	}

	public static function course_files( $course_id ) {
		$files = array_filter( array_map( 'absint', (array) get_post_meta( $course_id, '_szp_files', true ) ) );
		$links = (array) get_post_meta( $course_id, '_szp_links', true );
		if ( ! $files && empty( $links['url'] ) ) {
			return '';
		}
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">فولدر فایل‌ها و منابع تکمیلی</h3>
			<div class="szp-files">
				<?php foreach ( $files as $fid ) :
					$url = wp_get_attachment_url( $fid );
					if ( ! $url ) {
						continue;
					} ?>
					<a class="szp-file" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><span class="szp-file-ic">⬇</span><?php echo esc_html( get_the_title( $fid ) ); ?></a>
				<?php endforeach; ?>
				<?php if ( ! empty( $links['url'] ) ) {
					foreach ( $links['url'] as $i => $u ) {
						if ( ! $u ) {
							continue;
						}
						$lbl = ! empty( $links['label'][ $i ] ) ? $links['label'][ $i ] : $u;
						printf(
							'<a class="szp-file szp-link" href="%1$s" target="_blank" rel="noopener"><span class="szp-file-ic">↗</span>%2$s</a>',
							esc_url( $u ), esc_html( $lbl )
						);
					}
				} ?>
			</div>
		</section>
		<?php return ob_get_clean();
	}

	public static function course_workbench( $course_id, $user_id ) {
		$sessions = SZP_Frontend::course_sessions( $course_id );
		$alls     = $sessions['all'];
		$has_task = false;
		foreach ( $alls as $s ) {
			if ( get_post_meta( $s['id'], '_szp_task', true ) !== '' ) {
				$has_task = true;
				break;
			}
		}
		if ( ! $has_task ) {
			return '';
		}
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">میز کار تمرین‌ها</h3>
			<table class="szp-table">
				<thead><tr><th>جلسه</th><th>ددلاین</th><th>وضعیت</th></tr></thead>
				<tbody>
					<?php foreach ( $alls as $idx => $s ) :
						if ( get_post_meta( $s['id'], '_szp_task', true ) === '' ) {
							continue;
						}
						$deadline = isset( $alls[ $idx + 1 ] ) ? $alls[ $idx + 1 ]['ts'] : 0;
						$sub      = SZP_Data::get_submission( $s['id'], $user_id ); ?>
						<tr>
							<td><a href="<?php echo SZP_Frontend::url_session( $s['id'] ); ?>"><?php echo esc_html( $s['title'] ); ?></a></td>
							<td><?php echo $deadline ? esc_html( szp_format_datetime( $deadline ) ) : '—'; ?></td>
							<td>
								<?php if ( $sub ) : ?>
									<span class="szp-status szp-status-ok">ارسال شد</span>
								<?php else : ?>
									<span class="szp-status szp-status-pending">ارسال نشده</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<?php return ob_get_clean();
	}

	public static function course_group( $user_id ) {
		$groups = SZP_Groups::user_groups( $user_id );
		$mates  = array();
		foreach ( $groups as $gid ) {
			foreach ( SZP_Groups::members( $gid ) as $m ) {
				if ( (int) $m !== (int) $user_id ) {
					$mates[ $m ] = true;
				}
			}
		}
		$mates = array_keys( $mates );
		if ( ! $mates ) {
			return '';
		}
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">پنل گروه من</h3>
			<div class="szp-mates">
				<?php foreach ( $mates as $m ) :
					$u = get_userdata( $m );
					if ( ! $u ) {
						continue;
					} ?>
					<span class="szp-mate"><?php echo get_avatar( $m, 40 ); ?><i><?php echo esc_html( $u->display_name ); ?></i></span>
				<?php endforeach; ?>
			</div>
		</section>
		<?php return ob_get_clean();
	}

	public static function course_survey( $course_id, $user_id ) {
		$questions = array_values( array_filter( (array) get_post_meta( $course_id, '_szp_course_survey', true ), 'strlen' ) );
		if ( ! $questions ) {
			return '';
		}
		$ans = SZP_Data::get_survey( 'course', $course_id, $user_id );
		return self::survey_form( 'course', $course_id, $questions, $ans, 'نظرسنجی‌های دوره' );
	}

	/* ==================== SESSION ==================== */

	public static function session_hero( $session_id ) {
		$s = get_post( $session_id );
		if ( ! $s ) {
			return '';
		}
		$topic = get_post_meta( $session_id, '_szp_topic', true );
		$dt    = szp_ts_from_datetime( get_post_meta( $session_id, '_szp_datetime', true ) );
		ob_start(); ?>
		<div class="szp-hero">
			<h2><?php echo esc_html( $s->post_title ); ?></h2>
			<?php if ( $topic ) : ?><p class="szp-hero-sub"><?php echo esc_html( $topic ); ?></p><?php endif; ?>
			<?php if ( $dt ) : ?><p class="szp-hero-date"><?php echo esc_html( szp_format_datetime( $dt ) ); ?></p><?php endif; ?>
		</div>
		<?php return ob_get_clean();
	}

	public static function session_countdown( $session_id ) {
		$dt = szp_ts_from_datetime( get_post_meta( $session_id, '_szp_datetime', true ) );
		if ( ! $dt || $dt <= time() ) {
			return '';
		}
		return szp_countdown_html( $dt );
	}

	public static function session_identity( $session_id ) {
		$s = get_post( $session_id );
		if ( ! $s ) {
			return '';
		}
		$skeys = array_filter( (array) get_post_meta( $session_id, '_szp_skeys', true ), 'strlen' );
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">شناسنامه جلسه</h3>
			<?php if ( trim( $s->post_content ) !== '' ) : ?>
				<div class="szp-rich"><?php echo wp_kses_post( apply_filters( 'the_content', $s->post_content ) ); ?></div>
			<?php endif; ?>
			<?php if ( $skeys ) : ?>
				<h4>اهداف کلیدی</h4>
				<ul class="szp-list"><?php foreach ( $skeys as $k ) { echo '<li>' . esc_html( $k ) . '</li>'; } ?></ul>
			<?php endif; ?>
		</section>
		<?php return ob_get_clean();
	}

	/** Single custom audio player markup. */
	protected static function audio_player( $url, $title ) {
		ob_start(); ?>
		<div class="szp-audio">
			<button type="button" class="szp-ap-play" aria-label="پخش"><svg class="szp-ic szp-ic-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.14v13.72c0 .8.87 1.28 1.54.85l10.5-6.86a1 1 0 0 0 0-1.7L9.54 4.29A1 1 0 0 0 8 5.14z"/></svg><svg class="szp-ic szp-ic-pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5h3.2c.55 0 1 .45 1 1v13c0 .55-.45 1-1 1H7c-.55 0-1-.45-1-1v-13c0-.55.45-1 1-1zM13.8 4.5H17c.55 0 1 .45 1 1v13c0 .55-.45 1-1 1h-3.2c-.55 0-1-.45-1-1v-13c0-.55.45-1 1-1z"/></svg></button>
			<div class="szp-ap-main">
				<div class="szp-ap-top">
					<span class="szp-ap-name"><?php echo esc_html( $title ); ?></span>
					<span class="szp-ap-tools">
						<button type="button" class="szp-ap-speed" data-speed="1" aria-label="سرعت پخش">۱x</button>
						<a class="szp-ap-dl" href="<?php echo esc_url( $url ); ?>" download>دانلود</a>
					</span>
				</div>
				<canvas class="szp-audio-viz"></canvas>
				<div class="szp-ap-bar"><span class="szp-ap-fill"></span></div>
				<div class="szp-ap-time"><span class="szp-ap-cur">۰:۰۰</span><span class="szp-ap-dur">۰:۰۰</span></div>
			</div>
			<audio class="szp-audio-el" src="<?php echo esc_url( $url ); ?>" preload="metadata"></audio>
		</div>
		<?php return ob_get_clean();
	}

	public static function session_pack( $session_id ) {
		// multipart audio (title + url); url may be a media URL or a direct download-host link
		$parts  = (array) get_post_meta( $session_id, '_szp_audio_parts', true );
		$titles = isset( $parts['title'] ) ? (array) $parts['title'] : array();
		$urls   = isset( $parts['url'] ) ? (array) $parts['url'] : array();
		$audio_list = array();
		$pn = max( count( $titles ), count( $urls ) );
		for ( $i = 0; $i < $pn; $i++ ) {
			$u = isset( $urls[ $i ] ) ? trim( (string) $urls[ $i ] ) : '';
			if ( $u === '' ) {
				continue;
			}
			$t = ( isset( $titles[ $i ] ) && $titles[ $i ] !== '' ) ? $titles[ $i ] : 'پارت ' . szp_fa_digits( count( $audio_list ) + 1 );
			$audio_list[] = array( 'title' => $t, 'url' => $u );
		}
		// legacy fallback: single media audio
		if ( ! $audio_list ) {
			$legacy = (int) get_post_meta( $session_id, '_szp_audio', true );
			if ( $legacy && ( $lu = wp_get_attachment_url( $legacy ) ) ) {
				$audio_list[] = array( 'title' => 'صوت کلاس', 'url' => $lu );
			}
		}

		// PDF: prefer direct link, else media library attachment
		$pdf_url = trim( (string) get_post_meta( $session_id, '_szp_pdf_url', true ) );
		if ( $pdf_url === '' ) {
			$pdf_id = (int) get_post_meta( $session_id, '_szp_pdf', true );
			if ( $pdf_id ) {
				$pdf_url = (string) wp_get_attachment_url( $pdf_id );
			}
		}

		$sfiles = array_filter( array_map( 'absint', (array) get_post_meta( $session_id, '_szp_sfiles', true ) ) );
		$slinks = (array) get_post_meta( $session_id, '_szp_sfile_links', true );
		$sl_lab = isset( $slinks['label'] ) ? (array) $slinks['label'] : array();
		$sl_url = isset( $slinks['url'] ) ? (array) $slinks['url'] : array();

		if ( ! $audio_list && ! $pdf_url && ! $sfiles && ! array_filter( $sl_url ) ) {
			return '';
		}
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">توشه دیجیتال جلسه</h3>

			<?php if ( $audio_list ) : ?>
				<div class="szp-audio-parts-front<?php echo count( $audio_list ) > 1 ? ' szp-multi' : ''; ?>">
					<?php foreach ( $audio_list as $ai => $a ) :
						$label = count( $audio_list ) > 1 ? szp_fa_digits( $ai + 1 ) . '. ' . $a['title'] : $a['title']; ?>
						<?php echo self::audio_player( $a['url'], $label ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="szp-files">
				<?php if ( $pdf_url ) : ?>
					<a class="szp-file" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener"><span class="szp-file-ic">📄</span>اسلایدهای جلسه (PDF)</a>
				<?php endif; ?>
				<?php foreach ( $sfiles as $fid ) :
					$u = wp_get_attachment_url( $fid );
					if ( ! $u ) {
						continue;
					} ?>
					<a class="szp-file" href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener"><span class="szp-file-ic">⬇</span><?php echo esc_html( get_the_title( $fid ) ); ?></a>
				<?php endforeach; ?>
				<?php $sln = max( count( $sl_lab ), count( $sl_url ) );
				for ( $j = 0; $j < $sln; $j++ ) :
					$u = isset( $sl_url[ $j ] ) ? trim( (string) $sl_url[ $j ] ) : '';
					if ( $u === '' ) {
						continue;
					}
					$l = ( isset( $sl_lab[ $j ] ) && $sl_lab[ $j ] !== '' ) ? $sl_lab[ $j ] : 'فایل تکمیلی'; ?>
					<a class="szp-file" href="<?php echo esc_url( $u ); ?>" target="_blank" rel="noopener"><span class="szp-file-ic">⬇</span><?php echo esc_html( $l ); ?></a>
				<?php endfor; ?>
			</div>
			<div class="szp-soonbox">فرم ارزیابی فروش شخصی به‌زودی فعال می‌شود.</div>
		</section>
		<?php return ob_get_clean();
	}

	public static function session_task( $session_id, $user_id ) {
		$task    = get_post_meta( $session_id, '_szp_task', true );
		$quiz_id = (int) get_post_meta( $session_id, '_szp_quiz_id', true );
		$quiz    = $quiz_id ? get_post( $quiz_id ) : null;
		$quiz_ok = $quiz && $quiz->post_type === 'sazan_quiz' && $quiz->post_status === 'publish';

		if ( ! $quiz_ok && $task === '' ) {
			return '';
		}
		$sub = ( $task !== '' ) ? SZP_Data::get_submission( $session_id, $user_id ) : null;
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">تکلیف اختصاصی جلسه</h3>

			<?php if ( $quiz_ok ) : ?>
				<div class="szp-quiz-box">
					<span class="szp-quiz-title">📝 <?php echo esc_html( $quiz->post_title ); ?></span>
					<a class="szp-btn szp-quiz-btn" href="<?php echo esc_url( get_permalink( $quiz_id ) ); ?>">ورود به تمرین / آزمون</a>
				</div>
			<?php endif; ?>

			<?php if ( $task !== '' ) : ?>
				<div class="szp-rich"><?php echo wp_kses_post( $task ); ?></div>
				<?php if ( $sub ) : ?>
					<div class="szp-submitted">
						<span class="szp-ok-badge">✓ پاسخ شما ثبت شده است</span>
						<span class="szp-sub-date">آخرین ویرایش: <?php echo esc_html( szp_format_datetime( strtotime( $sub->updated_at ) ) ); ?></span>
						<?php if ( $sub->file_id && ( $fu = wp_get_attachment_url( $sub->file_id ) ) ) : ?>
							<a class="szp-file" href="<?php echo esc_url( $fu ); ?>" target="_blank" rel="noopener"><span class="szp-file-ic">⬇</span>فایل ارسالی شما</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<form class="szp-task-form" enctype="multipart/form-data">
					<input type="hidden" name="session_id" value="<?php echo (int) $session_id; ?>">
					<textarea name="content" rows="4" class="szp-textarea" placeholder="پاسخ خود را بنویسید..."><?php echo $sub ? esc_textarea( $sub->content ) : ''; ?></textarea>
					<label class="szp-file-label">پیوست فایل (اختیاری): <input type="file" name="file"></label>
					<div class="szp-form-row">
						<button type="submit" class="szp-btn"><?php echo $sub ? 'ویرایش پاسخ' : 'ارسال پاسخ'; ?></button>
						<span class="szp-form-msg"></span>
					</div>
				</form>
			<?php endif; ?>
		</section>
		<?php return ob_get_clean();
	}

	public static function session_checklist( $session_id, $user_id ) {
		$check = array_filter( (array) get_post_meta( $session_id, '_szp_checklist', true ), 'strlen' );
		if ( ! $check ) {
			return '';
		}
		$ck_state = SZP_Data::get_checklist( $session_id, $user_id );
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h">چک‌لیست جلسه</h3>
			<ul class="szp-check">
				<?php $ci = 0; foreach ( $check as $c ) :
					$done = ! empty( $ck_state[ $ci ] ); ?>
					<li class="<?php echo $done ? 'szp-done' : ''; ?>">
						<label>
							<input type="checkbox" class="szp-check-item" data-session="<?php echo (int) $session_id; ?>" data-index="<?php echo (int) $ci; ?>" <?php checked( $done ); ?>>
							<?php echo esc_html( $c ); ?>
						</label>
					</li>
				<?php $ci++; endforeach; ?>
			</ul>
			<p class="szp-hint">تیک هر مورد بلافاصله ذخیره می‌شود.</p>
		</section>
		<?php return ob_get_clean();
	}

	public static function session_survey( $session_id, $user_id ) {
		$questions = array_values( array_filter( (array) get_post_meta( $session_id, '_szp_survey', true ), 'strlen' ) );
		if ( ! $questions ) {
			return '';
		}
		$ans = SZP_Data::get_survey( 'session', $session_id, $user_id );
		return self::survey_form( 'session', $session_id, $questions, $ans, 'نظرسنجی بازخورد سریع' );
	}

	/* ==================== shared ==================== */

	/** Theme switcher (glass / neon). JS persists the choice and applies it to all .szp roots. */
	public static function skin_switch() {
		return '';
	}

	protected static function survey_form( $context, $object_id, $questions, $ans, $title ) {
		ob_start(); ?>
		<section class="szp-sec">
			<h3 class="szp-sec-h"><?php echo esc_html( $title ); ?></h3>
			<?php if ( $ans ) : ?><p class="szp-hint">شما قبلاً پاسخ داده‌اید؛ می‌توانید ویرایش کنید.</p><?php endif; ?>
			<form class="szp-survey-form">
				<input type="hidden" name="context" value="<?php echo esc_attr( $context ); ?>">
				<input type="hidden" name="object_id" value="<?php echo (int) $object_id; ?>">
				<?php foreach ( $questions as $qi => $qq ) :
					$cur = isset( $ans[ $qi ] ) ? (int) $ans[ $qi ] : 0; ?>
					<div class="szp-q">
						<p class="szp-q-text"><?php echo esc_html( ( $qi + 1 ) . '. ' . $qq ); ?></p>
						<div class="szp-stars">
							<?php for ( $v = 1; $v <= 5; $v++ ) : ?>
								<label class="szp-star"><input type="radio" name="answers[<?php echo (int) $qi; ?>]" value="<?php echo $v; ?>" <?php checked( $cur, $v ); ?> required> <?php echo szp_fa_digits( $v ); ?></label>
							<?php endfor; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="szp-form-row">
					<button type="submit" class="szp-btn">ثبت نظر</button>
					<span class="szp-form-msg"></span>
				</div>
			</form>
		</section>
		<?php return ob_get_clean();
	}
}
