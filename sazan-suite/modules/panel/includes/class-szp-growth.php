<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Unified growth dashboard: permanent profile, weekly plan and weekly review.
 *
 * Legacy financial target/result columns remain the source of truth so existing
 * reports, SMS notifications and status calculations continue to work.
 */
class SZP_Growth {

	const PROFILE_META = 'szp_growth_profile';

	public static function revisions_table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_eval_revisions';
	}

	public static function profile( $user_id ) {
		$user = get_userdata( (int) $user_id );
		$raw  = get_user_meta( (int) $user_id, self::PROFILE_META, true );
		$data = is_array( $raw ) ? $raw : array();
		return wp_parse_args( $data, array(
			'full_name'     => $user ? $user->display_name : '',
			'business_name' => '',
			'position'      => '',
			'industry'      => '',
			'completed_at'  => '',
			'updated_at'    => '',
		) );
	}

	public static function profile_complete( $user_id ) {
		$p = self::profile( $user_id );
		return $p['full_name'] !== '' && $p['business_name'] !== '' && $p['position'] !== '' && $p['industry'] !== '';
	}

	public static function save_profile( $user_id, $input ) {
		if ( class_exists( 'SZP_Eval_Roles' ) && ! SZP_Eval_Roles::registers_own( $user_id ) ) {
			return new WP_Error( 'forbidden', 'نقش شما نیازی به ثبت اطلاعات پایه ارزیابی ندارد.' );
		}
		$old = self::profile( $user_id );
		$now = current_time( 'mysql' );
		$new = array(
			'full_name'     => sanitize_text_field( $input['full_name'] ?? '' ),
			'business_name' => sanitize_text_field( $input['business_name'] ?? '' ),
			'position'      => sanitize_text_field( $input['position'] ?? '' ),
			'industry'      => sanitize_text_field( $input['industry'] ?? '' ),
			'completed_at'  => $old['completed_at'] ? $old['completed_at'] : $now,
			'updated_at'    => $now,
		);
		foreach ( array( 'full_name', 'business_name', 'position', 'industry' ) as $required ) {
			if ( $new[ $required ] === '' ) {
				return new WP_Error( 'required', 'لطفاً همه اطلاعات پایه را تکمیل کنید.' );
			}
		}
		update_user_meta( (int) $user_id, self::PROFILE_META, $new );
		return $new;
	}

	public static function row_payload( $row ) {
		if ( ! $row ) {
			return array();
		}
		$fields = array(
			'target', 'result', 'has_result', 'note', 'target_success', 'target_customers',
			'target_actions', 'target_obstacle', 'target_obstacle_plan', 'motivation',
			'commitment', 'target_complete', 'result_percent', 'result_customers',
			'result_success', 'result_weakness', 'result_learning', 'result_complete',
			'target_set_at', 'result_set_at',
		);
		$out = array();
		foreach ( $fields as $field ) {
			$out[ $field ] = isset( $row->{$field} ) ? $row->{$field} : null;
		}
		return $out;
	}

	public static function record_revision( $user_id, $week_no, $kind, $previous, $current, $actor_id = 0 ) {
		global $wpdb;
		$wpdb->insert(
			self::revisions_table(),
			array(
				'user_id'         => (int) $user_id,
				'week_no'         => (int) $week_no,
				'actor_id'        => $actor_id ? (int) $actor_id : get_current_user_id(),
				'kind'            => sanitize_key( $kind ),
				'previous_payload'=> wp_json_encode( $previous, JSON_UNESCAPED_UNICODE ),
				'new_payload'     => wp_json_encode( $current, JSON_UNESCAPED_UNICODE ),
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	public static function save_target( $user_id, $session_no, $input ) {
		global $wpdb;
		$before  = SZP_Eval::get_week( $user_id, $session_no );
		$amount  = szp_parse_amount( $input['amount'] ?? '' );
		$actions = array();
		foreach ( (array) ( $input['actions'] ?? array() ) as $action ) {
			if ( ! is_scalar( $action ) ) {
				continue;
			}
			$action = sanitize_textarea_field( (string) $action );
			if ( trim( $action ) !== '' ) {
				$actions[] = $action;
			}
		}
		$data    = array(
			'target_success'       => sanitize_textarea_field( $input['success'] ?? '' ),
			'target_customers'     => max( 0, absint( $input['customers'] ?? 0 ) ),
			'target_actions'       => wp_json_encode( $actions, JSON_UNESCAPED_UNICODE ),
			'target_obstacle'      => sanitize_textarea_field( $input['obstacle'] ?? '' ),
			'target_obstacle_plan' => sanitize_textarea_field( $input['obstacle_plan'] ?? '' ),
			'motivation'           => min( 10, max( 1, absint( $input['motivation'] ?? 0 ) ) ),
			'commitment'           => min( 10, max( 1, absint( $input['commitment'] ?? 0 ) ) ),
			'target_complete'      => 1,
		);
		if ( $amount <= 0 || $data['target_success'] === '' || trim( (string) ( $input['customers'] ?? '' ) ) === '' ||
			count( $actions ) < 1 || $data['target_obstacle'] === '' || $data['target_obstacle_plan'] === '' ||
			$data['motivation'] < 1 || $data['commitment'] < 1 ) {
			return new WP_Error( 'required', 'لطفاً همه پرسش‌های تارگت هفتگی را تکمیل کنید.' );
		}
		$res = SZP_Eval::set_session_target( $user_id, $session_no, $amount );
		if ( empty( $res['ok'] ) ) {
			return new WP_Error( 'save', 'ثبت تارگت ناموفق بود.' );
		}
		$data['updated_at'] = current_time( 'mysql' );
		$wpdb->update( SZP_Eval::table(), $data, array( 'user_id' => (int) $user_id, 'week_no' => (int) $session_no ) );
		$after = SZP_Eval::get_week( $user_id, $session_no );
		self::record_revision( $user_id, $session_no, 'target', self::row_payload( $before ), self::row_payload( $after ) );
		return $res;
	}

	public static function save_result( $user_id, $session_no, $input ) {
		global $wpdb;
		$before = SZP_Eval::get_week( $user_id, $session_no );
		if ( ! $before || (float) $before->target <= 0 ) {
			return new WP_Error( 'notarget', 'برای این هفته تارگتی ثبت نشده است.' );
		}
		$amount = szp_parse_amount( $input['amount'] ?? '' );
		$data   = array(
			'result_percent'   => min( 1000, max( 0, (float) ( $input['percent'] ?? 0 ) ) ),
			'result_customers' => max( 0, absint( $input['customers'] ?? 0 ) ),
			'result_success'   => sanitize_textarea_field( $input['success'] ?? '' ),
			'result_weakness'  => sanitize_textarea_field( $input['weakness'] ?? '' ),
			'result_learning'  => sanitize_textarea_field( $input['learning'] ?? '' ),
			'result_complete'  => 1,
		);
		if ( trim( (string) ( $input['amount'] ?? '' ) ) === '' || trim( (string) ( $input['percent'] ?? '' ) ) === '' ||
			trim( (string) ( $input['customers'] ?? '' ) ) === '' || $data['result_success'] === '' ||
			$data['result_weakness'] === '' || $data['result_learning'] === '' ) {
			return new WP_Error( 'required', 'لطفاً همه پرسش‌های گزارش عملکرد را تکمیل کنید.' );
		}
		$note = implode( "\n\n", array(
			'بزرگ‌ترین موفقیت: ' . $data['result_success'],
			'مهم‌ترین اشتباه یا نقطه ضعف: ' . $data['result_weakness'],
			'مهم‌ترین یادگیری: ' . $data['result_learning'],
		) );
		$res = SZP_Eval::set_session_result( $user_id, $session_no, $amount, $note );
		if ( empty( $res['ok'] ) ) {
			return new WP_Error( 'save', 'ثبت نتیجه ناموفق بود.' );
		}
		$data['updated_at'] = current_time( 'mysql' );
		$wpdb->update( SZP_Eval::table(), $data, array( 'user_id' => (int) $user_id, 'week_no' => (int) $session_no ) );
		$after = SZP_Eval::get_week( $user_id, $session_no );
		self::record_revision( $user_id, $session_no, 'result', self::row_payload( $before ), self::row_payload( $after ) );
		return $res;
	}

	protected static function icon( $name ) {
		$paths = array(
			'user'   => '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
			'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
			'report' => '<path d="M4 19V5"/><path d="M8 17v-6"/><path d="M12 17V7"/><path d="M16 17v-3"/><path d="M20 17V4"/>',
			'lock'   => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		);
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? $paths['target'] ) . '</svg>';
	}

	public static function profile_card( $user_id ) {
		if ( class_exists( 'SZP_Eval_Roles' ) && ! SZP_Eval_Roles::registers_own( $user_id ) ) {
			return '';
		}
		$p        = self::profile( $user_id );
		$complete = self::profile_complete( $user_id );
		ob_start(); ?>
		<details class="szp-growth-profile" <?php echo $complete ? '' : 'open'; ?>>
			<summary>
				<span class="szp-growth-icon"><?php echo self::icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span><b><?php echo esc_html( szp_ui_text( 'profile_title', 'اطلاعات پایه' ) ); ?></b><small><?php echo $complete ? esc_html( $p['business_name'] . ' · ' . $p['position'] ) : esc_html( szp_ui_text( 'profile_incomplete_hint', 'برای شروع، این بخش را یک‌بار تکمیل کنید.' ) ); ?></small></span>
				<span class="szp-growth-state <?php echo $complete ? 'is-done' : ''; ?>"><?php echo esc_html( $complete ? szp_ui_text( 'profile_complete_label', 'تکمیل شده' ) : szp_ui_text( 'profile_required_label', 'نیازمند تکمیل' ) ); ?></span>
			</summary>
			<div class="szp-growth-profile-body">
				<div class="szp-growth-grid">
					<label><span><?php echo esc_html( szp_ui_text( 'profile_full_name_label', 'نام و نام خانوادگی' ) ); ?></span><input type="text" data-profile="full_name" value="<?php echo esc_attr( $p['full_name'] ); ?>" autocomplete="name"></label>
					<label><span><?php echo esc_html( szp_ui_text( 'profile_business_label', 'نام کسب‌وکار' ) ); ?></span><input type="text" data-profile="business_name" value="<?php echo esc_attr( $p['business_name'] ); ?>"></label>
					<label><span><?php echo esc_html( szp_ui_text( 'profile_position_label', 'سمت در مجموعه' ) ); ?></span><input type="text" data-profile="position" value="<?php echo esc_attr( $p['position'] ); ?>" placeholder="<?php echo esc_attr( szp_ui_text( 'profile_position_hint', 'مثلاً مدیر مجموعه، مدیر فروش یا تیم فروش' ) ); ?>"></label>
					<label><span><?php echo esc_html( szp_ui_text( 'profile_industry_label', 'حوزه فعالیت' ) ); ?></span><input type="text" data-profile="industry" value="<?php echo esc_attr( $p['industry'] ); ?>" placeholder="<?php echo esc_attr( szp_ui_text( 'profile_industry_hint', 'مثلاً آموزش، پوشاک یا خدمات' ) ); ?>"></label>
				</div>
				<div class="szp-growth-actions">
					<button type="button" class="szp-growth-btn primary" data-growth-action="save-profile"><?php echo esc_html( szp_ui_text( 'profile_save_button', 'ذخیره اطلاعات پایه' ) ); ?></button>
					<span class="szp-growth-msg" aria-live="polite"></span>
				</div>
			</div>
		</details>
		<?php
		return ob_get_clean();
	}

	public static function self_panel( $user_id, $currency, $has_team = false, $has_coaching = false ) {
		if ( class_exists( 'SZP_Eval_Roles' ) && ! SZP_Eval_Roles::registers_own( $user_id ) ) {
			return '';
		}
		$current = SZP_Eval::current_session();
		$due     = $current - 1;
		$weeks   = SZP_Eval::weeks( $user_id );
		$bypass  = SZP_Eval::can_bypass( $user_id );
		$state   = self::dashboard_state( $user_id, $current, $due, $weeks, $bypass );
		$current_actions = self::current_week_actions( $user_id, $current, $bypass, $state );
		$action_modes    = array_values( array_unique( array_column( $current_actions, 'mode' ) ) );
		$action_tone     = 1 === count( $action_modes ) ? 'is-' . $action_modes[0] : 'is-mixed';
		$quick_count     = 2 + ( $has_team ? 1 : 0 ) + ( $has_coaching ? 1 : 0 );
		$done    = array_values( array_filter( $weeks, function ( $week ) {
			return (float) $week->target > 0 && ! empty( $week->target_complete ) && ! empty( $week->result_complete );
		} ) );

		ob_start(); ?>
		<section class="szp-growth-dashboard" aria-labelledby="szp-growth-dashboard-title">
			<div class="szp-growth-dashboard-head">
				<div>
					<span class="szp-growth-kicker"><?php echo esc_html( szp_ui_text( 'dashboard_kicker', 'ارزیابی من' ) ); ?></span>
					<h4 id="szp-growth-dashboard-title"><?php echo esc_html( szp_ui_text( 'dashboard_title', 'داشبورد فعالیت هفتگی' ) ); ?></h4>
				</div>
				<span class="szp-growth-session-badge">
					<small><?php echo esc_html( szp_ui_text( 'dashboard_current_session', 'جلسه جاری' ) ); ?></small>
					<b><?php echo esc_html( SZP_Eval::session_label( $current ) ); ?></b>
					<em><?php echo esc_html( SZP_Eval::session_date_fa( $current ) ); ?></em>
				</span>
			</div>
			<div class="szp-growth-status-card is-<?php echo esc_attr( $state['tone'] ); ?>">
				<span class="szp-growth-icon"><?php echo self::icon( $state['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="szp-growth-status-copy">
					<small><?php echo esc_html( szp_ui_text( 'dashboard_status_label', 'وضعیت فعلی' ) ); ?></small>
					<b><?php echo esc_html( $state['label'] ); ?></b>
					<em><?php echo esc_html( $state['detail'] ); ?></em>
				</span>
				<span class="szp-growth-status-session"><small><?php echo esc_html( szp_ui_text( 'dashboard_current_session', 'جلسه جاری' ) ); ?></small><b><?php echo esc_html( SZP_Eval::session_label( $current ) ); ?></b></span>
				<button type="button" class="szp-growth-status-button" data-growth-wizard-open data-growth-wizard-view="primary" aria-haspopup="dialog" aria-controls="szp-growth-wizard">
					<?php echo esc_html( $state['button'] ); ?>
				</button>
			</div>
			<?php if ( ! empty( $state['timestamp'] ) ) { echo self::countdown_markup( $state['timestamp'], $state['countdown_label'] ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $state['overdue'] > 0 ) : ?>
				<div class="szp-growth-overdue"><b><?php echo esc_html( szp_fa_digits( $state['overdue'] ) ); ?></b><span><?php echo esc_html( szp_ui_text( 'dashboard_overdue_label', 'نقص‌های قبلی' ) ); ?></span></div>
			<?php endif; ?>
			<?php if ( $current_actions ) : ?>
				<div class="szp-growth-current-actions <?php echo esc_attr( $action_tone ); ?>" aria-label="ثبت‌های در دسترس این هفته">
					<span><?php echo esc_html( szp_ui_text( 'dashboard_current_actions_label', 'ثبت‌های این هفته' ) ); ?></span>
					<div>
						<?php foreach ( $current_actions as $action ) : ?>
							<button type="button" data-growth-action-type="<?php echo esc_attr( $action['mode'] ); ?>" data-growth-wizard-open data-growth-wizard-view="<?php echo esc_attr( $action['view'] ); ?>" aria-haspopup="dialog" aria-controls="szp-growth-wizard">
								<?php echo self::icon( $action['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<b><?php echo esc_html( $action['button'] ); ?></b>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>
		<nav class="szp-growth-mobile-quick" data-mobile-quick data-mobile-quick-count="<?php echo esc_attr( $quick_count ); ?>" aria-label="دید سریع">
			<span><?php echo esc_html( szp_ui_text( 'mobile_quick_title', 'دید سریع' ) ); ?></span>
			<button type="button" data-mobile-quick-target="summary" aria-expanded="false"><?php echo self::icon( 'report' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( szp_ui_text( 'mobile_quick_summary', 'خلاصه من' ) ); ?></b></button>
			<button type="button" data-mobile-quick-target="history" aria-expanded="false"><?php echo self::icon( 'target' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( szp_ui_text( 'mobile_quick_history', 'سوابق' ) ); ?></b></button>
			<?php if ( $has_team ) : ?><button type="button" data-mobile-quick-target="team" aria-expanded="false"><?php echo self::icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( szp_ui_text( 'mobile_quick_team', 'تیم من' ) ); ?></b></button><?php endif; ?>
			<?php if ( $has_coaching ) : ?><button type="button" data-mobile-quick-target="coaching" aria-expanded="false"><?php echo self::icon( 'report' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b><?php echo esc_html( szp_ui_text( 'mobile_quick_coaching', 'کوچینگ' ) ); ?></b></button><?php endif; ?>
		</nav>
		<?php echo self::summary_cards( $weeks ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo self::history_panel( $done, $currency, 12, false, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo self::wizard_modal( $user_id, $currency, $current, $bypass, $state, $current_actions ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Keep overdue warnings visible without blocking the valid work of this week.
	 *
	 * The primary status still opens the oldest incomplete item. These secondary
	 * actions stay visible independently from overdue items.
	 */
	protected static function current_week_actions( $user_id, $current, $bypass, $state ) {
		if ( 'profile' === $state['mode'] ) {
			return array();
		}

		$actions       = array();
		$result_session = 0;
		// آخرین نتیجه ناقص را مستقل از ترتیب سایر نقص‌ها در دسترس نگه دار.
		for ( $session = $current; $session >= max( 1, SZP_Eval::start_week() ); $session-- ) {
			$row = SZP_Eval::get_week( $user_id, $session );
			if ( $row && (float) $row->target > 0 && empty( $row->result_complete ) ) {
				$result_session = $session;
				break;
			}
		}
		if ( $result_session > 0 ) {
			$is_primary = 'result' === $state['mode'] && (int) $state['session'] === $result_session;
			$actions[]  = array(
				'mode'    => 'result',
				'session' => $result_session,
				'view'    => $is_primary ? 'primary' : 'current-result-' . $result_session,
				'icon'    => 'report',
				'access'  => true,
				'button'  => $result_session === $current
					? szp_ui_text( 'dashboard_current_result_button', 'ثبت نتیجه این هفته' )
					: sprintf( 'ثبت نتیجه %s', SZP_Eval::session_label( $result_session ) ),
			);
		}

		$current_row = SZP_Eval::get_week( $user_id, $current );
		if ( ( ! $current_row || empty( $current_row->target_complete ) || (float) $current_row->target <= 0 ) &&
			( SZP_Eval::target_open( $current ) || $bypass ) ) {
			$is_primary = 'target' === $state['mode'] && (int) $state['session'] === $current;
			$actions[]  = array(
				'mode'    => 'target',
				'session' => $current,
				'view'    => $is_primary ? 'primary' : 'current-target-' . $current,
				'icon'    => 'target',
				'access'  => true,
				'button'  => szp_ui_text( 'dashboard_current_target_button', 'ثبت تارگت این هفته' ),
			);
		}

		return $actions;
	}

	protected static function dashboard_state( $user_id, $current, $due, $weeks, $bypass ) {
		$profile_complete = self::profile_complete( $user_id );
		$rows             = array();
		$missing_results  = array();
		foreach ( $weeks as $week ) {
			$rows[ (int) $week->week_no ] = $week;
			if ( (int) $week->week_no < $current && (float) $week->target > 0 && empty( $week->result_complete ) ) {
				$missing_results[] = $week;
			}
		}
		$missing_targets = array();
		for ( $i = max( 1, SZP_Eval::start_week() ); $i < $current; $i++ ) {
			if ( empty( $rows[ $i ] ) || (float) $rows[ $i ]->target <= 0 ) {
				$missing_targets[] = $i;
			}
		}
		$overdue = count( $missing_targets ) + count( $missing_results );
		$base    = array(
			'tone' => 'neutral', 'icon' => 'target', 'mode' => 'wait', 'session' => $current,
			'timestamp' => 0, 'countdown_label' => '', 'overdue' => $overdue, 'overdue_access' => false,
		);

		if ( ! $profile_complete ) {
			return array_merge( $base, array(
				'tone' => 'warning', 'icon' => 'user', 'mode' => 'profile',
				'label' => szp_ui_text( 'dashboard_profile_status', 'اطلاعات پایه تکمیل نشده' ),
				'detail' => szp_ui_text( 'dashboard_profile_detail', 'مرحله اول شروع ارزیابی' ),
				'button' => szp_ui_text( 'dashboard_profile_button', 'تکمیل اطلاعات پایه' ),
			) );
		}

		if ( $overdue > 0 ) {
			$missing_items = array();
			foreach ( $missing_targets as $session ) {
				$missing_items[] = array( 'session' => (int) $session, 'mode' => 'target' );
			}
			foreach ( $missing_results as $week ) {
				$missing_items[] = array( 'session' => (int) $week->week_no, 'mode' => 'result' );
			}
			usort( $missing_items, static function ( $a, $b ) {
				return $a['session'] <=> $b['session'];
			} );
			$next_missing = reset( $missing_items );
			$is_result    = 'result' === $next_missing['mode'];
			return array_merge( $base, array(
				'tone'          => 'danger',
				'icon'          => $is_result ? 'report' : 'target',
				'mode'          => $next_missing['mode'],
				'session'       => $next_missing['session'],
				'overdue_access'=> true,
				'label'         => $is_result
					? szp_ui_text( 'dashboard_missing_result_status', 'نتیجه ثبت نشده' )
					: szp_ui_text( 'dashboard_overdue_status', 'تارگت ثبت نشده' ),
				'detail'        => sprintf(
					'%s · %s %s',
					SZP_Eval::session_label( $next_missing['session'] ),
					szp_fa_digits( $overdue ),
					szp_ui_text( 'dashboard_overdue_count_unit', 'مورد ناقص' )
				),
				'button'        => $is_result
					? szp_ui_text( 'dashboard_missing_result_button', 'تکمیل نتیجه عقب‌افتاده' )
					: szp_ui_text( 'dashboard_overdue_button', 'تکمیل تارگت عقب‌افتاده' ),
			) );
		}

		$due_row = $due >= 1 && isset( $rows[ $due ] ) ? $rows[ $due ] : null;
		if ( $due_row && (float) $due_row->target > 0 && empty( $due_row->has_result ) &&
			( SZP_Eval::result_open( $due ) || $bypass ) ) {
			return array_merge( $base, array(
				'tone' => 'action', 'icon' => 'report', 'mode' => 'result', 'session' => $due,
				'label' => szp_ui_text( 'dashboard_result_status', 'زمان ثبت نتیجه است' ),
				'detail' => SZP_Eval::session_label( $due ),
				'button' => szp_ui_text( 'dashboard_result_button', 'ثبت نتیجه هفته قبل' ),
			) );
		}

		$current_row = isset( $rows[ $current ] ) ? $rows[ $current ] : null;
		if ( ( ! $current_row || (float) $current_row->target <= 0 ) &&
			( SZP_Eval::target_open( $current ) || $bypass ) ) {
			return array_merge( $base, array(
				'tone' => 'action', 'icon' => 'target', 'mode' => 'target',
				'label' => szp_ui_text( 'dashboard_target_status', 'زمان ثبت تارگت است' ),
				'detail' => SZP_Eval::session_label( $current ),
				'button' => szp_ui_text( 'dashboard_target_button', 'ثبت تارگت این هفته' ),
			) );
		}

		if ( $current_row && (float) $current_row->target > 0 && empty( $current_row->result_complete ) ) {
			return array_merge( $base, array(
				'tone' => 'action', 'icon' => 'report', 'mode' => 'result', 'session' => $current,
				'label' => szp_ui_text( 'dashboard_result_status', 'ثبت نتیجه در دسترس است' ),
				'detail' => SZP_Eval::session_label( $current ),
				'button' => szp_ui_text( 'dashboard_current_result_button', 'ثبت نتیجه این هفته' ),
			) );
		}

		$target_session = SZP_Eval::target_open_timestamp( $current ) > time() ? $current : $current + 1;
		return array_merge( $base, array(
			'tone' => 'waiting', 'icon' => 'lock', 'mode' => 'wait-target', 'session' => $target_session,
			'timestamp' => SZP_Eval::target_open_timestamp( $target_session ),
			'countdown_label' => szp_ui_text( 'dashboard_target_countdown', 'تا بازشدن ثبت تارگت' ),
			'label' => szp_ui_text( 'dashboard_wait_target_status', 'در انتظار ثبت تارگت' ),
			'detail' => SZP_Eval::session_date_fa( $target_session ),
			'button' => szp_ui_text( 'dashboard_wait_target_button', 'در انتظار ثبت تارگت' ),
		) );
	}

	protected static function countdown_markup( $timestamp, $label ) {
		ob_start(); ?>
		<div class="szp-growth-dashboard-countdown">
			<small><?php echo esc_html( $label ); ?></small>
			<span class="szp-growth-countdown" data-growth-countdown="<?php echo esc_attr( $timestamp ); ?>" role="timer" aria-live="polite">
				<span><b data-countdown-part="days">۰</b><small><?php echo esc_html( szp_ui_text( 'growth_countdown_days', 'روز' ) ); ?></small></span>
				<span><b data-countdown-part="hours">۰۰</b><small><?php echo esc_html( szp_ui_text( 'growth_countdown_hours', 'ساعت' ) ); ?></small></span>
				<span><b data-countdown-part="minutes">۰۰</b><small><?php echo esc_html( szp_ui_text( 'growth_countdown_minutes', 'دقیقه' ) ); ?></small></span>
			</span>
		</div>
		<?php return ob_get_clean();
	}

	protected static function profile_wizard_form( $user_id ) {
		$p = self::profile( $user_id );
		ob_start(); ?>
		<div class="szp-growth-profile-body szp-growth-wizard-profile" data-growth-profile-form>
			<div class="szp-growth-grid">
				<label><span><?php echo esc_html( szp_ui_text( 'profile_full_name_label', 'نام و نام خانوادگی' ) ); ?></span><input type="text" data-profile="full_name" value="<?php echo esc_attr( $p['full_name'] ); ?>" autocomplete="name"></label>
				<label><span><?php echo esc_html( szp_ui_text( 'profile_business_label', 'نام کسب‌وکار' ) ); ?></span><input type="text" data-profile="business_name" value="<?php echo esc_attr( $p['business_name'] ); ?>"></label>
				<label><span><?php echo esc_html( szp_ui_text( 'profile_position_label', 'سمت در مجموعه' ) ); ?></span><input type="text" data-profile="position" value="<?php echo esc_attr( $p['position'] ); ?>" placeholder="<?php echo esc_attr( szp_ui_text( 'profile_position_hint', 'مثلاً مدیر مجموعه، مدیر فروش یا تیم فروش' ) ); ?>"></label>
				<label><span><?php echo esc_html( szp_ui_text( 'profile_industry_label', 'حوزه فعالیت' ) ); ?></span><input type="text" data-profile="industry" value="<?php echo esc_attr( $p['industry'] ); ?>" placeholder="<?php echo esc_attr( szp_ui_text( 'profile_industry_hint', 'مثلاً آموزش، پوشاک یا خدمات' ) ); ?>"></label>
			</div>
			<div class="szp-growth-actions">
				<button type="button" class="szp-growth-btn primary" data-growth-action="save-profile"><?php echo esc_html( szp_ui_text( 'profile_save_button', 'ذخیره و ادامه' ) ); ?></button>
				<span class="szp-growth-msg" aria-live="polite"></span>
			</div>
		</div>
		<?php return ob_get_clean();
	}

	protected static function wizard_modal( $user_id, $currency, $current, $bypass, $state, $current_actions = array() ) {
		$title       = $state['label'];
		$form_access = $bypass || ! empty( $state['overdue_access'] );
		ob_start(); ?>
		<div id="szp-growth-wizard" class="szp-growth-wizard" data-growth-wizard hidden>
			<button type="button" class="szp-growth-wizard-backdrop" data-growth-wizard-close aria-label="بستن"></button>
			<section class="szp-growth-wizard-panel" role="dialog" aria-modal="true" aria-labelledby="szp-growth-wizard-title" tabindex="-1">
				<header>
					<span><small><?php echo esc_html( szp_ui_text( 'wizard_step_label', 'مرحله فعلی' ) ); ?></small><b id="szp-growth-wizard-title"><?php echo esc_html( $title ); ?></b></span>
					<button type="button" class="szp-growth-wizard-close" data-growth-wizard-close aria-label="<?php echo esc_attr( szp_ui_text( 'wizard_close_label', 'بستن پنجره' ) ); ?>">×</button>
				</header>
				<div class="szp-growth-wizard-body">
					<div class="szp-growth-wizard-view" data-growth-wizard-view-panel="primary" data-growth-wizard-view-title="<?php echo esc_attr( $title ); ?>">
						<?php
						if ( $state['mode'] === 'profile' ) {
							echo self::profile_wizard_form( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput
						} elseif ( $state['mode'] === 'target' ) {
							echo self::target_card( $user_id, $state['session'], $currency, $form_access ); // phpcs:ignore WordPress.Security.EscapeOutput
						} elseif ( $state['mode'] === 'result' ) {
							echo self::result_card( $user_id, $state['session'], $currency, $form_access ); // phpcs:ignore WordPress.Security.EscapeOutput
						} else {
							?>
							<div class="szp-growth-wizard-wait">
								<span class="szp-growth-icon"><?php echo self::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<b><?php echo esc_html( $state['label'] ); ?></b>
								<small><?php echo esc_html( $state['detail'] ); ?></small>
								<?php if ( ! empty( $state['timestamp'] ) ) { echo self::countdown_markup( $state['timestamp'], $state['countdown_label'] ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
							<?php
						}
						?>
					</div>
					<?php foreach ( $current_actions as $action ) :
						if ( 'primary' === $action['view'] ) {
							continue;
						}
						$action_title = 'result' === $action['mode']
							? szp_ui_text( 'dashboard_current_result_button', 'ثبت نتیجه این هفته' )
							: szp_ui_text( 'dashboard_current_target_button', 'ثبت تارگت این هفته' );
						?>
						<div class="szp-growth-wizard-view" data-growth-wizard-view-panel="<?php echo esc_attr( $action['view'] ); ?>" data-growth-wizard-view-title="<?php echo esc_attr( $action_title ); ?>" hidden>
							<?php
							$action_access = $bypass || ! empty( $action['access'] );
							echo 'result' === $action['mode']
								? self::result_card( $user_id, $action['session'], $currency, $action_access )
								: self::target_card( $user_id, $action['session'], $currency, $action_access ); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php return ob_get_clean();
	}

	protected static function result_quick_action( $user_id, $current, $due, $bypass ) {
		$due_row = $due >= 1 ? SZP_Eval::get_week( $user_id, $due ) : null;
		if ( $due_row && (float) $due_row->target > 0 && empty( $due_row->result_complete ) &&
			( SZP_Eval::result_open( $due ) || $bypass ) ) {
			ob_start(); ?>
			<div class="szp-growth-result-action is-open">
				<span class="szp-growth-icon"><?php echo self::icon( 'report' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="szp-growth-result-action-title"><small><?php echo esc_html( szp_ui_text( 'growth_result_ready_label', 'نتیجه تارگت هفته قبل' ) ); ?></small><b><?php echo esc_html( SZP_Eval::session_label( $due ) ); ?></b></span>
				<a href="#szp-growth-result-<?php echo esc_attr( $due ); ?>"><?php echo esc_html( szp_ui_text( 'growth_result_ready_button', 'ثبت نتیجه تارگت' ) ); ?></a>
			</div>
			<?php return ob_get_clean();
		}

		$current_row = SZP_Eval::get_week( $user_id, $current );
		if ( ! $current_row || (float) $current_row->target <= 0 || ! empty( $current_row->result_complete ) ) {
			return '';
		}

		$open_at = SZP_Eval::result_open_timestamp( $current );
		if ( $open_at <= time() ) {
			return '';
		}

		ob_start(); ?>
		<div class="szp-growth-result-action is-waiting">
			<span class="szp-growth-icon"><?php echo self::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="szp-growth-result-action-title">
				<small><?php echo esc_html( szp_ui_text( 'growth_result_waiting_label', 'زمان بازشدن ثبت نتیجه' ) ); ?></small>
				<b><?php echo esc_html( SZP_Eval::result_date_fa( $current ) ); ?></b>
			</span>
			<span class="szp-growth-countdown" data-growth-countdown="<?php echo esc_attr( $open_at ); ?>" role="timer" aria-live="polite">
				<span><b data-countdown-part="days">۰</b><small><?php echo esc_html( szp_ui_text( 'growth_countdown_days', 'روز' ) ); ?></small></span>
				<span><b data-countdown-part="hours">۰۰</b><small><?php echo esc_html( szp_ui_text( 'growth_countdown_hours', 'ساعت' ) ); ?></small></span>
				<span><b data-countdown-part="minutes">۰۰</b><small><?php echo esc_html( szp_ui_text( 'growth_countdown_minutes', 'دقیقه' ) ); ?></small></span>
			</span>
		</div>
		<?php return ob_get_clean();
	}

	protected static function target_card( $user_id, $session, $currency, $bypass ) {
		$row      = SZP_Eval::get_week( $user_id, $session );
		$complete = $row && ! empty( $row->target_complete );
		$open     = SZP_Eval::target_open( $session ) || $bypass;
		$actions  = $row && ! empty( $row->target_actions ) ? json_decode( $row->target_actions, true ) : array();
		$actions  = is_array( $actions ) ? array_values( array_filter( $actions, 'strlen' ) ) : array();
		$actions  = $actions ? $actions : array( '' );
		ob_start(); ?>
		<article class="szp-growth-card target <?php echo $complete ? 'is-complete' : ''; ?>">
			<header><span class="szp-growth-icon"><?php echo self::icon( 'target' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b><?php echo esc_html( szp_ui_text( 'growth_target_title', 'مرحله ۱: برنامه این هفته' ) ); ?></b><small><?php echo esc_html( SZP_Eval::session_label( $session ) . ' · ' . SZP_Eval::session_date_fa( $session ) ); ?></small></div></header>
			<?php if ( $complete ) : ?>
				<div class="szp-growth-done"><b><?php echo esc_html( szp_money( $row->target, $currency ) ); ?></b><span><?php echo esc_html( szp_ui_text( 'target_saved_text', 'تارگت ثبت شده است.' ) ); ?></span></div>
			<?php elseif ( $open ) : ?>
				<div class="szp-growth-form szp-ev-form" data-growth-step-form data-mode="target" data-session="<?php echo esc_attr( $session ); ?>">
					<section class="szp-growth-form-step" data-growth-form-step="1">
					<?php echo self::money_field( szp_ui_text( 'target_q1', '۱. تارگت مالی این هفته' ), 'amount', $row ? $row->target : '', $currency ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="szp-ev-preview" aria-live="polite"></div>
					<?php echo self::textarea_field( szp_ui_text( 'target_q2', '۲. نتیجه مطلوب هفته' ), 'success', $row->target_success ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo self::number_field( szp_ui_text( 'target_q3', '۳. تارگت تعداد مشتری جدید' ), 'customers', $row->target_customers ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
					<section class="szp-growth-form-step" data-growth-form-step="2" hidden>
					<fieldset class="szp-growth-actions-fieldset"><legend><?php echo esc_html( szp_ui_text( 'target_q4', '۴. اقدام‌های اصلی برای رسیدن به هدف' ) ); ?></legend>
						<p class="szp-growth-help"><?php echo esc_html( szp_ui_text( 'target_action_help', 'اقدام اول را با توضیح کامل بنویسید. اگر لازم بود، اقدام‌های بعدی را اضافه کنید.' ) ); ?></p>
						<div class="szp-growth-action-list" data-growth-action-list>
							<?php foreach ( $actions as $i => $action ) : ?>
								<div class="szp-growth-action-row">
									<label><span><?php echo esc_html( szp_ui_text( 'target_action_label', 'اقدام' ) ); ?> <b data-growth-action-number><?php echo esc_html( szp_fa_digits( $i + 1 ) ); ?></b></span><textarea rows="4" data-growth-action-item placeholder="<?php echo esc_attr( szp_ui_text( 'target_action_placeholder', 'این اقدام را دقیق و قابل اجرا توضیح دهید' ) ); ?>"><?php echo esc_textarea( $action ); ?></textarea></label>
									<button type="button" class="szp-growth-remove-action" data-growth-remove-action aria-label="<?php echo esc_attr( szp_ui_text( 'target_action_remove', 'حذف اقدام' ) ); ?>"><?php echo esc_html( szp_ui_text( 'target_action_remove', 'حذف اقدام' ) ); ?></button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="szp-growth-add-action" data-growth-add-action><?php echo esc_html( szp_ui_text( 'target_action_add', 'افزودن اقدام دیگر' ) ); ?></button>
					</fieldset>
					<?php echo self::textarea_field( szp_ui_text( 'target_q5', '۵. بزرگ‌ترین مانع احتمالی این هفته' ), 'obstacle', $row->target_obstacle ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo self::textarea_field( szp_ui_text( 'target_q6', '۶. برنامه شما برای رفع این مانع' ), 'obstacle_plan', $row->target_obstacle_plan ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
					<section class="szp-growth-form-step" data-growth-form-step="3" hidden>
					<div class="szp-growth-scale-row">
						<?php echo self::scale_field( szp_ui_text( 'target_q7', '۷. میزان انگیزه' ), 'motivation', $row->motivation ?? 0 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo self::scale_field( szp_ui_text( 'target_q8', '۸. میزان تعهد به اجرا' ), 'commitment', $row->commitment ?? 0 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					</section>
					<div class="szp-growth-form-nav">
						<button type="button" data-growth-step-prev><?php echo esc_html( szp_ui_text( 'wizard_previous_button', 'مرحله قبل' ) ); ?></button>
						<span data-growth-step-progress></span>
						<button type="button" class="primary" data-growth-step-next><?php echo esc_html( szp_ui_text( 'wizard_next_button', 'مرحله بعد' ) ); ?></button>
					</div>
					<button type="button" class="szp-growth-btn primary szp-ev-submit" data-growth-final-submit hidden><?php echo esc_html( szp_ui_text( 'growth_target_button', 'ثبت تارگت هفتگی' ) ); ?></button>
					<div class="szp-ev-msg" aria-live="polite"></div>
				</div>
			<?php else : ?>
				<div class="szp-growth-locked"><?php echo self::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b><?php echo esc_html( szp_ui_text( 'target_locked_title', 'امروز کاری برای این بخش ندارید.' ) ); ?></b> <?php echo esc_html( szp_ui_text( 'target_locked_text', 'فرم برنامه هفته جدید، روز سه‌شنبه باز می‌شود.' ) ); ?></span></div>
			<?php endif; ?>
		</article>
		<?php return ob_get_clean();
	}

	protected static function result_card( $user_id, $session, $currency, $bypass ) {
		$row = SZP_Eval::get_week( $user_id, $session );
		if ( ! $row || (float) $row->target <= 0 ) {
			return '';
		}
		$complete = ! empty( $row->result_complete );
		$open     = SZP_Eval::result_open( $session ) || $bypass;
		ob_start(); ?>
		<article id="szp-growth-result-<?php echo esc_attr( $session ); ?>" class="szp-growth-card result <?php echo $complete ? 'is-complete' : ''; ?>">
			<header><span class="szp-growth-icon"><?php echo self::icon( 'report' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b><?php echo esc_html( szp_ui_text( 'growth_result_title', 'مرحله ۲: نتیجه این هفته' ) ); ?></b><small><?php echo esc_html( SZP_Eval::session_label( $session ) ); ?></small></div></header>
			<?php if ( $complete ) : ?>
				<div class="szp-growth-done"><b><?php echo esc_html( szp_money( $row->result, $currency ) ); ?></b><span><?php echo esc_html( szp_ui_text( 'result_saved_text', 'گزارش عملکرد ثبت شده است.' ) ); ?></span></div>
			<?php elseif ( $open ) : ?>
				<div class="szp-growth-form szp-ev-form" data-growth-step-form data-mode="result" data-session="<?php echo esc_attr( $session ); ?>" data-target="<?php echo esc_attr( $row->target ); ?>">
					<section class="szp-growth-form-step" data-growth-form-step="1">
					<div class="szp-growth-readonly"><span><?php echo esc_html( szp_ui_text( 'result_q1', '۱. هدف این هفته' ) ); ?></span><b><?php echo esc_html( szp_money( $row->target, $currency ) ); ?></b></div>
					<?php echo self::number_field( szp_ui_text( 'result_q2', '۲. ارزیابی خودت از درصد تحقق' ), 'percent', ! empty( $row->result_complete ) ? $row->result_percent : '', 'درصد', 'szp-growth-result-percent' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo self::money_field( szp_ui_text( 'result_q3', '۳. فروش واقعی این هفته' ), 'amount', $row->has_result ? $row->result : '', $currency, true, 'szp-growth-result-amount' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="szp-ev-preview" aria-live="polite"></div>
					<?php echo self::number_field( szp_ui_text( 'result_q4', '۴. تعداد مشتری واقعی' ), 'customers', $row->result_customers ?? '', '', 'szp-growth-result-customers' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
					<section class="szp-growth-form-step" data-growth-form-step="2" hidden>
					<?php echo self::textarea_field( szp_ui_text( 'result_q5', '۵. بزرگ‌ترین موفقیت این هفته' ), 'success', $row->result_success ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo self::textarea_field( szp_ui_text( 'result_q6', '۶. مهم‌ترین اشتباه یا نقطه ضعف' ), 'weakness', $row->result_weakness ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo self::textarea_field( szp_ui_text( 'result_q7', '۷. مهم‌ترین یادگیری این هفته' ), 'learning', $row->result_learning ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
					<div class="szp-growth-form-nav">
						<button type="button" data-growth-step-prev><?php echo esc_html( szp_ui_text( 'wizard_previous_button', 'مرحله قبل' ) ); ?></button>
						<span data-growth-step-progress></span>
						<button type="button" class="primary" data-growth-step-next><?php echo esc_html( szp_ui_text( 'wizard_next_button', 'مرحله بعد' ) ); ?></button>
					</div>
					<button type="button" class="szp-growth-btn success szp-ev-submit" data-growth-final-submit hidden><?php echo esc_html( szp_ui_text( 'growth_result_button', 'ثبت نتیجه این هفته' ) ); ?></button>
					<div class="szp-ev-msg" aria-live="polite"></div>
				</div>
			<?php elseif ( ! $row->has_result ) : ?>
				<div class="szp-growth-locked"><?php echo self::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b><?php echo esc_html( szp_ui_text( 'result_locked_title', 'ثبت نتیجه بسته است' ) ); ?></b><?php echo esc_html( SZP_Eval::result_date_fa( $session ) ); ?></span></div>
			<?php else : ?>
				<div class="szp-growth-done"><b><?php echo esc_html( szp_money( $row->result, $currency ) ); ?></b><span><?php echo esc_html( szp_ui_text( 'result_preserved_text', 'نتیجه قدیمی شما محفوظ است.' ) ); ?></span></div>
			<?php endif; ?>
		</article>
		<?php return ob_get_clean();
	}

	protected static function money_field( $label, $name, $value, $currency, $allow_zero = false, $class = '' ) {
		return sprintf(
			'<label class="%s"><span>%s</span><span class="szp-growth-input-unit"><input class="szp-ev-amount" type="text" inputmode="numeric" data-growth-field="%s" value="%s" data-allow-zero="%s"><i>%s</i></span></label>',
			esc_attr( $class ), esc_html( $label ), esc_attr( $name ), esc_attr( $value ), $allow_zero ? '1' : '0', esc_html( $currency )
		);
	}

	protected static function number_field( $label, $name, $value, $unit = '', $class = '' ) {
		return sprintf(
			'<label class="%s"><span>%s</span><span class="szp-growth-input-unit"><input type="number" min="0" step="any" data-growth-field="%s" value="%s">%s</span></label>',
			esc_attr( $class ), esc_html( $label ), esc_attr( $name ), esc_attr( $value ), $unit ? '<i>' . esc_html( $unit ) . '</i>' : ''
		);
	}

	protected static function textarea_field( $label, $name, $value ) {
		return sprintf( '<label><span>%s</span><textarea rows="3" data-growth-field="%s">%s</textarea></label>', esc_html( $label ), esc_attr( $name ), esc_textarea( $value ) );
	}

	protected static function scale_field( $label, $name, $value ) {
		$options = '<option value="">' . esc_html( szp_ui_text( 'select_placeholder', 'انتخاب کنید' ) ) . '</option>';
		for ( $i = 1; $i <= 10; $i++ ) {
			$options .= '<option value="' . $i . '"' . selected( (int) $value, $i, false ) . '>' . szp_fa_digits( $i ) . '</option>';
		}
		return '<label><span>' . esc_html( $label ) . ' (۱ تا ۱۰)</span><select data-growth-field="' . esc_attr( $name ) . '">' . $options . '</select></label>';
	}

	public static function summary_cards( $weeks ) {
		if ( ! $weeks ) {
			return '';
		}
		$reported = 0;
		$sum      = 0;
		foreach ( $weeks as $week ) {
			if ( empty( $week->has_result ) ) {
				continue;
			}
			$reported++;
			$pct  = SZP_Eval::pct( $week->target, $week->result );
			$sum += $pct;
		}
		$avg = $reported ? (int) round( $sum / $reported ) : 0;
		ob_start(); ?>
		<section class="szp-growth-overview" data-mobile-panel="summary" aria-label="خلاصه عملکرد">
			<div><span><?php echo esc_html( szp_ui_text( 'summary_target_label', 'تارگت ثبت‌شده' ) ); ?></span><b><?php echo esc_html( szp_fa_digits( count( $weeks ) ) ); ?> هفته</b></div>
			<div><span><?php echo esc_html( szp_ui_text( 'summary_result_label', 'نتیجه ثبت‌شده' ) ); ?></span><b><?php echo esc_html( szp_fa_digits( $reported ) . ' از ' . szp_fa_digits( count( $weeks ) ) ); ?></b></div>
			<div><span><?php echo esc_html( szp_ui_text( 'summary_average_label', 'میانگین تحقق' ) ); ?></span><b><?php echo $reported ? esc_html( szp_fa_digits( $avg ) ) . '٪' : '—'; ?></b></div>
		</section>
		<?php return ob_get_clean();
	}

	public static function history_panel( $weeks, $currency, $limit = 12, $interactive = false, $completed_only = false ) {
		$heading_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'szp-growth-history-' ) : 'szp-growth-history-title';
		$items      = array_slice( array_reverse( $weeks ), 0, $limit );
		ob_start(); ?>
		<section class="szp-growth-history" data-mobile-panel="history" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>">
			<div class="szp-growth-section-head">
				<div><h4 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $completed_only ? szp_ui_text( 'completed_history_title', 'هفته‌های تکمیل‌شده' ) : szp_ui_text( 'growth_history_title', 'هفته‌های گذشته' ) ); ?></h4></div>
			</div>
			<?php if ( ! $weeks ) : ?>
				<div class="szp-growth-empty"><b><?php echo esc_html( $completed_only ? szp_ui_text( 'completed_history_empty_title', 'هنوز هفته کاملی ثبت نشده است.' ) : szp_ui_text( 'history_empty_title', 'هنوز سابقه‌ای ثبت نشده است.' ) ); ?></b><span><?php echo esc_html( $completed_only ? szp_ui_text( 'completed_history_empty_text', 'پس از ثبت تارگت و نتیجه، هفته کامل اینجا نمایش داده می‌شود.' ) : szp_ui_text( 'history_empty_text', 'بعد از اولین ثبت هفتگی، سوابق شما اینجا نمایش داده می‌شود.' ) ); ?></span></div>
			<?php else : ?>
				<div class="szp-growth-history-list">
					<?php foreach ( $items as $week ) :
						$status = SZP_Eval::compute_status( $week->target, $week->result, $week->has_result );
						$meta   = SZP_Eval::status_meta( $status );
						?>
						<details class="szp-growth-history-item">
							<summary>
								<span class="szp-growth-week"><?php echo esc_html( SZP_Eval::session_label( $week->week_no ) ); ?><small><?php echo esc_html( SZP_Eval::session_date_fa( $week->week_no ) ); ?></small></span>
								<span class="szp-growth-money"><small><?php echo esc_html( szp_ui_text( 'history_target_label', 'هدف مالی' ) ); ?></small><b><?php echo esc_html( szp_money( $week->target, $currency ) ); ?></b></span>
								<span class="szp-growth-money"><small><?php echo esc_html( szp_ui_text( 'history_actual_label', 'فروش واقعی' ) ); ?></small><b><?php echo $week->has_result ? esc_html( szp_money( $week->result, $currency ) ) : esc_html( szp_ui_text( 'history_not_registered', 'ثبت نشده' ) ); ?></b></span>
								<span class="szp-growth-status" style="--status-color:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['label'] ); ?></span>
							</summary>
							<?php echo self::history_details( $week ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php return ob_get_clean();
	}

	public static function team_panel( $user_id, $currency ) {
		$is_coach = class_exists( 'SZP_Eval_Roles' ) && SZP_Eval_Roles::is_coaching( $user_id );
		$ids      = $is_coach ? SZP_Eval_Roles::all_people_ids() : SZP_Eval_Roles::subordinates( $user_id );
		$ids      = array_values( array_filter( array_unique( array_map( 'intval', $ids ) ), function ( $id ) use ( $user_id ) {
			return $id > 0 && $id !== (int) $user_id;
		} ) );
		$current  = SZP_Eval::current_session();
		$members  = array();
		$stats    = array(
			'total' => 0, 'target_done' => 0, 'result_done' => 0, 'incomplete' => 0,
			'target_sum' => 0, 'due_target_sum' => 0, 'result_sum' => 0,
		);
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			if ( ! $info ) {
				continue;
			}
			$profile = self::profile( $id );
			$weeks   = SZP_Eval::weeks( $id );
			$state   = self::team_member_state( $id, $current, $weeks );
			$current_row = SZP_Eval::get_week( $id, $current );
			$due_row     = $current > 1 ? SZP_Eval::get_week( $id, $current - 1 ) : null;
			$manager_id  = $is_coach && SZP_Eval_Roles::is_org_manager( $id )
				? $id
				: ( $is_coach ? SZP_Eval_Roles::manager_id( $id ) : (int) $user_id );
			$stats['total']++;
			$stats['target_done'] += $current_row && ! empty( $current_row->target_complete ) ? 1 : 0;
			$stats['result_done'] += $due_row && ! empty( $due_row->result_complete ) ? 1 : 0;
			$stats['incomplete']  += in_array( $state['key'], array( 'profile', 'missing_target', 'missing_result' ), true ) ? 1 : 0;
			$stats['target_sum']  += $current_row ? (float) $current_row->target : 0;
			$stats['due_target_sum'] += $due_row ? (float) $due_row->target : 0;
			$stats['result_sum']  += $due_row && ! empty( $due_row->has_result ) ? (float) $due_row->result : 0;
			$members[] = compact( 'id', 'info', 'profile', 'weeks', 'state', 'current_row', 'due_row', 'manager_id' );
		}
		$achievement = $stats['due_target_sum'] > 0 ? round( ( $stats['result_sum'] / $stats['due_target_sum'] ) * 100, 1 ) : 0;
		$team_groups = array();
		if ( $is_coach ) {
			foreach ( SZP_Eval_Roles::manager_choices() as $manager_id => $manager_name ) {
				$key = (string) (int) $manager_id;
				$team_groups[ $key ] = array(
					'id'         => $key,
					'manager_id' => (int) $manager_id,
					'name'       => $manager_name,
					'count'      => 0,
					'incomplete' => 0,
				);
			}
			foreach ( $members as $member ) {
				$manager_id = (int) $member['manager_id'];
				$key        = $manager_id > 0 ? (string) $manager_id : 'unassigned';
				if ( ! isset( $team_groups[ $key ] ) ) {
					$manager_info = $manager_id > 0 ? SZP_Groups::user_info( $manager_id ) : null;
					$team_groups[ $key ] = array(
						'id'         => $key,
						'manager_id' => $manager_id,
						'name'       => $manager_info ? $manager_info['name'] : 'بدون مدیر سازمان',
						'count'      => 0,
						'incomplete' => 0,
					);
				}
				$team_groups[ $key ]['count']++;
				if ( in_array( $member['state']['key'], array( 'profile', 'missing_target', 'missing_result' ), true ) ) {
					$team_groups[ $key ]['incomplete']++;
				}
			}
			uasort( $team_groups, static function ( $a, $b ) {
				if ( ! $a['manager_id'] ) {
					return 1;
				}
				if ( ! $b['manager_id'] ) {
					return -1;
				}
				return strnatcasecmp( $a['name'], $b['name'] );
			} );
		}
		$has_team_view = $is_coach ? ! empty( $team_groups ) : ! empty( $members );
		ob_start(); ?>
		<section class="szp-growth-team szp-growth-manager" data-mobile-panel="team" aria-labelledby="szp-growth-team-title" data-growth-team>
			<div class="szp-growth-section-head">
				<div><span class="szp-growth-kicker"><?php echo esc_html( $is_coach ? szp_ui_text( 'team_coach_kicker', 'نمای کوچ' ) : szp_ui_text( 'team_manager_kicker', 'نمای مدیر' ) ); ?></span><h4 id="szp-growth-team-title"><?php echo esc_html( $is_coach ? szp_ui_text( 'team_coach_title', 'تیم‌های سازمانی' ) : szp_ui_text( 'team_manager_title', 'زیرمجموعه‌های من' ) ); ?></h4></div>
				<span class="szp-growth-count"><?php echo esc_html( szp_fa_digits( $stats['total'] ) ); ?> نفر</span>
			</div>
			<?php if ( ! $has_team_view ) : ?>
				<div class="szp-growth-empty szp-growth-team-empty">
					<b><?php echo esc_html( szp_ui_text( 'team_empty_title', 'هنوز عضوی به این مدیر متصل نشده است.' ) ); ?></b>
					<span><?php echo esc_html( szp_ui_text( 'team_empty_text', 'در «نقش‌ها و تیم‌ها»، برای کاربران تیم فروش همین مدیر سازمان را انتخاب کنید.' ) ); ?></span>
				</div>
			<?php else : ?>
				<div class="szp-growth-team-kpis" aria-label="خلاصه عملکرد تیم">
					<div><small>اعضای تیم</small><b><?php echo esc_html( szp_fa_digits( $stats['total'] ) ); ?></b></div>
					<div><small>تارگت این هفته</small><b><?php echo esc_html( szp_fa_digits( $stats['target_done'] ) ); ?> از <?php echo esc_html( szp_fa_digits( $stats['total'] ) ); ?></b></div>
					<div><small>نتیجه ثبت‌شده</small><b><?php echo esc_html( szp_fa_digits( $stats['result_done'] ) ); ?> از <?php echo esc_html( szp_fa_digits( $stats['total'] ) ); ?></b></div>
					<div class="<?php echo $stats['incomplete'] ? 'is-alert' : 'is-good'; ?>"><small>نیازمند پیگیری</small><b><?php echo esc_html( szp_fa_digits( $stats['incomplete'] ) ); ?></b></div>
					<div><small>مجموع تارگت</small><b><?php echo esc_html( szp_money( $stats['target_sum'], $currency ) ); ?></b></div>
					<div><small>فروش واقعی</small><b><?php echo esc_html( szp_money( $stats['result_sum'], $currency ) ); ?></b></div>
					<div><small>تحقق تیم</small><b><?php echo esc_html( szp_fa_digits( $achievement ) ); ?>٪</b></div>
				</div>
				<nav class="szp-growth-mobile-quick szp-growth-team-quick" data-mobile-quick data-mobile-quick-count="1" aria-label="دید سریع تیم">
					<span><?php echo esc_html( szp_ui_text( 'mobile_quick_title', 'دید سریع' ) ); ?></span>
					<button type="button" data-mobile-quick-target="team-members" aria-expanded="false">
						<?php echo self::icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><b><?php echo esc_html( $is_coach ? 'تیم‌های سازمانی' : szp_ui_text( 'mobile_quick_members', 'اعضای تیم' ) ); ?></b><small><?php echo esc_html( $is_coach ? 'انتخاب مدیر و مشاهده اعضای تیم' : szp_ui_text( 'mobile_quick_members_hint', 'جست‌وجو و مشاهده گزارش اعضا' ) ); ?></small></span>
						<em><?php echo esc_html( szp_fa_digits( $stats['total'] ) ); ?> نفر</em>
					</button>
				</nav>
				<div class="szp-growth-team-browser" data-mobile-panel="team-members">
				<?php if ( $is_coach ) : ?>
					<div class="szp-growth-team-groups" data-team-groups>
						<div class="szp-growth-team-groups-head"><b>انتخاب تیم</b><span><?php echo esc_html( szp_fa_digits( count( $team_groups ) ) ); ?> تیم سازمانی</span></div>
						<div class="szp-growth-team-groups-grid">
							<?php foreach ( $team_groups as $group ) : ?>
								<button type="button" class="szp-growth-team-group-card" data-team-group-open="<?php echo esc_attr( $group['id'] ); ?>" data-team-group-name="<?php echo esc_attr( 'تیم ' . $group['name'] ); ?>">
									<span class="szp-growth-avatar"><?php echo $group['manager_id'] ? get_avatar( $group['manager_id'], 48 ) : self::icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span><b><?php echo esc_html( $group['manager_id'] ? 'تیم ' . $group['name'] : $group['name'] ); ?></b><small><?php echo esc_html( szp_fa_digits( $group['count'] ) ); ?> عضو</small></span>
									<em class="<?php echo $group['incomplete'] ? 'is-alert' : 'is-good'; ?>"><?php echo esc_html( szp_fa_digits( $group['incomplete'] ) ); ?> نیازمند پیگیری</em>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
				<div class="szp-growth-team-members-view" data-team-members-view <?php echo $is_coach ? 'hidden' : ''; ?>>
				<?php if ( $is_coach ) : ?>
					<div class="szp-growth-team-selected-head">
						<button type="button" data-team-groups-back aria-label="بازگشت به فهرست تیم‌ها">←</button>
						<span><small>اعضای</small><b data-team-selected-name>تیم انتخاب‌شده</b></span>
					</div>
				<?php endif; ?>
				<div class="szp-growth-team-toolbar">
					<label class="szp-growth-team-search">
						<span class="screen-reader-text">جست‌وجوی اعضای تیم</span>
						<input type="search" data-team-search placeholder="جست‌وجوی نام، کسب‌وکار یا سمت" autocomplete="off">
					</label>
					<label>
						<span class="screen-reader-text">فیلتر وضعیت اعضا</span>
						<select data-team-filter>
							<option value="all">همه وضعیت‌ها</option>
							<option value="missing_target">تارگت ثبت‌نشده</option>
							<option value="missing_result">نتیجه ثبت‌نشده</option>
							<option value="profile">اطلاعات پایه ناقص</option>
							<option value="waiting_result">در انتظار نتیجه</option>
							<option value="complete">هفته کامل</option>
						</select>
					</label>
					<span class="szp-growth-team-found" data-team-found aria-live="polite"><?php echo esc_html( szp_fa_digits( $stats['total'] ) ); ?> نفر</span>
				</div>
				<div class="szp-growth-people">
					<?php foreach ( $members as $member ) :
						$id          = $member['id'];
						$info        = $member['info'];
						$profile     = $member['profile'];
						$state       = $member['state'];
						$current_row = $member['current_row'];
						$due_row     = $member['due_row'];
						$achievement = $due_row && ! empty( $due_row->result_complete )
							? szp_fa_digits( $due_row->result_percent ) . '٪'
							: '—';
						$search_text = trim( $info['name'] . ' ' . $profile['business_name'] . ' ' . $profile['position'] . ' ' . $profile['industry'] );
						?>
						<article class="szp-growth-person szp-growth-team-member" data-team-member data-team-group-id="<?php echo esc_attr( $member['manager_id'] > 0 ? $member['manager_id'] : 'unassigned' ); ?>" data-team-status="<?php echo esc_attr( $state['key'] ); ?>" data-team-search-text="<?php echo esc_attr( $search_text ); ?>">
							<div class="szp-growth-person-main">
								<span class="szp-growth-avatar"><?php echo get_avatar( $id, 48 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<span class="szp-growth-person-name"><b><?php echo esc_html( $info['name'] ); ?></b><small><?php echo esc_html( trim( $profile['business_name'] . ( $profile['position'] ? ' · ' . $profile['position'] : '' ) ) ?: 'اطلاعات پایه تکمیل نشده' ); ?></small></span>
								<span class="szp-growth-team-state is-<?php echo esc_attr( $state['key'] ); ?>"><i aria-hidden="true"></i><?php echo esc_html( $state['label'] ); ?></span>
							</div>
							<div class="szp-growth-member-achievement">
								<small>درصد تحقق</small>
								<b><?php echo esc_html( $achievement ); ?></b>
							</div>
							<button type="button" class="szp-growth-team-report-button" data-team-report-open="<?php echo esc_attr( $id ); ?>" aria-haspopup="dialog" aria-controls="szp-growth-team-dialog">مشاهده گزارش کامل</button>
						</article>
						<div hidden data-team-report-template="<?php echo esc_attr( $id ); ?>">
							<?php echo self::team_member_report( $member, $currency, $current ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="szp-growth-empty szp-growth-team-no-result" data-team-no-result hidden><b>عضوی با این مشخصات پیدا نشد.</b><span>عبارت جست‌وجو یا فیلتر وضعیت را تغییر دهید.</span></div>
				</div>
				</div>
				<div id="szp-growth-team-dialog" class="szp-growth-team-dialog" data-team-dialog hidden>
					<button type="button" class="szp-growth-team-dialog-backdrop" data-team-dialog-close aria-label="بستن گزارش"></button>
					<section role="dialog" aria-modal="true" aria-labelledby="szp-growth-team-dialog-title" tabindex="-1">
						<header><b id="szp-growth-team-dialog-title">گزارش کامل عضو تیم</b><button type="button" data-team-dialog-close aria-label="بستن گزارش">×</button></header>
						<div class="szp-growth-team-dialog-body" data-team-dialog-body></div>
					</section>
				</div>
			<?php endif; ?>
		</section>
		<?php return ob_get_clean();
	}

	protected static function team_member_state( $user_id, $current, $weeks ) {
		if ( ! self::profile_complete( $user_id ) ) {
			return array( 'key' => 'profile', 'label' => 'اطلاعات پایه ناقص' );
		}
		$rows = array();
		foreach ( $weeks as $week ) {
			$rows[ (int) $week->week_no ] = $week;
		}
		for ( $i = max( 1, SZP_Eval::start_week() ); $i < $current; $i++ ) {
			if ( empty( $rows[ $i ] ) || (float) $rows[ $i ]->target <= 0 || empty( $rows[ $i ]->target_complete ) ) {
				return array( 'key' => 'missing_target', 'label' => 'تارگت ثبت‌نشده' );
			}
			if ( empty( $rows[ $i ]->result_complete ) ) {
				return array( 'key' => 'missing_result', 'label' => 'نتیجه ثبت‌نشده' );
			}
		}
		$current_row = isset( $rows[ $current ] ) ? $rows[ $current ] : null;
		if ( ! $current_row || (float) $current_row->target <= 0 || empty( $current_row->target_complete ) ) {
			return array( 'key' => 'missing_target', 'label' => 'تارگت این هفته ثبت نشده' );
		}
		if ( $current > 1 && isset( $rows[ $current - 1 ] ) && ! empty( $rows[ $current - 1 ]->result_complete ) ) {
			return array( 'key' => 'complete', 'label' => 'هفته قبل کامل شده' );
		}
		return array( 'key' => 'waiting_result', 'label' => 'در انتظار نتیجه' );
	}

	protected static function team_member_report( $member, $currency, $current ) {
		$info        = $member['info'];
		$profile     = $member['profile'];
		$weeks       = array_reverse( $member['weeks'] );
		$current_row = $member['current_row'];
		$due_row     = $member['due_row'];
		ob_start(); ?>
		<div class="szp-growth-team-report">
			<div class="szp-growth-team-report-head">
				<span class="szp-growth-avatar"><?php echo get_avatar( $member['id'], 56 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div><h4><?php echo esc_html( $info['name'] ); ?></h4><span><?php echo esc_html( $profile['business_name'] ?: 'نام کسب‌وکار ثبت نشده' ); ?></span></div>
				<span class="szp-growth-team-state is-<?php echo esc_attr( $member['state']['key'] ); ?>"><i aria-hidden="true"></i><?php echo esc_html( $member['state']['label'] ); ?></span>
			</div>
			<div class="szp-growth-team-profile-grid">
				<div><small>سمت در مجموعه</small><b><?php echo esc_html( $profile['position'] ?: 'ثبت نشده' ); ?></b></div>
				<div><small>حوزه فعالیت</small><b><?php echo esc_html( $profile['industry'] ?: 'ثبت نشده' ); ?></b></div>
				<div><small>جلسه جاری</small><b><?php echo esc_html( SZP_Eval::session_label( $current ) ); ?></b></div>
				<div><small>تعداد سوابق</small><b><?php echo esc_html( szp_fa_digits( count( $weeks ) ) ); ?> هفته</b></div>
			</div>
			<div class="szp-growth-team-report-kpis">
				<div><small>تارگت این هفته</small><b><?php echo $current_row && (float) $current_row->target > 0 ? esc_html( szp_money( $current_row->target, $currency ) ) : 'ثبت نشده'; ?></b></div>
				<div><small>فروش واقعی هفته قبل</small><b><?php echo $due_row && ! empty( $due_row->has_result ) ? esc_html( szp_money( $due_row->result, $currency ) ) : 'ثبت نشده'; ?></b></div>
				<div><small>تحقق اعلامی</small><b><?php echo $due_row && ! empty( $due_row->result_complete ) ? esc_html( szp_fa_digits( $due_row->result_percent ) . '٪' ) : '—'; ?></b></div>
				<div><small>مشتری واقعی</small><b><?php echo $due_row && ! empty( $due_row->result_complete ) ? esc_html( szp_fa_digits( (int) $due_row->result_customers ) ) : '—'; ?></b></div>
			</div>
			<div class="szp-growth-team-report-history">
				<div class="szp-growth-section-head"><div><h4>تمام گزارش‌های هفتگی</h4><span>برای دیدن پاسخ‌ها، هر هفته را باز کنید.</span></div></div>
				<?php if ( ! $weeks ) : ?>
					<div class="szp-growth-empty"><b>هنوز گزارشی ثبت نشده است.</b></div>
				<?php else : foreach ( $weeks as $week ) :
					$status = SZP_Eval::compute_status( $week->target, $week->result, $week->has_result );
					$meta   = SZP_Eval::status_meta( $status ); ?>
					<details class="szp-growth-team-week">
						<summary>
							<span><b><?php echo esc_html( SZP_Eval::session_label( $week->week_no ) ); ?></b><small><?php echo esc_html( SZP_Eval::session_date_fa( $week->week_no ) ); ?></small></span>
							<span><small>تارگت</small><b><?php echo (float) $week->target > 0 ? esc_html( szp_money( $week->target, $currency ) ) : 'ثبت نشده'; ?></b></span>
							<span><small>فروش واقعی</small><b><?php echo ! empty( $week->has_result ) ? esc_html( szp_money( $week->result, $currency ) ) : 'ثبت نشده'; ?></b></span>
							<em style="--status-color:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['label'] ); ?></em>
						</summary>
						<?php echo self::history_details( $week ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</details>
				<?php endforeach; endif; ?>
			</div>
		</div>
		<?php return ob_get_clean();
	}

	public static function coaching_panel( $user_id ) {
		$is_coaching_role = class_exists( 'SZP_Eval_Roles' ) && SZP_Eval_Roles::is_coaching( $user_id );
		if ( $is_coaching_role || ! self::has_coaching_access( $user_id ) ) {
			return '';
		}
		$is_coach = SZP_Coach::is_coach( $user_id );
		ob_start(); ?>
		<section class="szp-growth-coaching-new" data-mobile-panel="coaching" aria-labelledby="szp-growth-coaching-title">
			<div class="szp-growth-section-head">
				<div><span class="szp-growth-kicker"><?php echo esc_html( szp_ui_text( 'coaching_kicker', 'همراهی و اجرا' ) ); ?></span><h4 id="szp-growth-coaching-title"><?php echo esc_html( szp_ui_text( 'coaching_title', 'کوچینگ کسب‌وکار' ) ); ?></h4></div>
			</div>
			<?php if ( $is_coach ) :
				$courses = SZP_Coach::coach_course_ids( $user_id ); ?>
				<div class="szp-growth-coach-courses">
					<?php foreach ( $courses as $course_id ) :
						$journeys = SZP_Coach::course_journeys( $course_id ); ?>
						<div class="szp-growth-coach-course">
							<div><b><?php echo esc_html( get_the_title( $course_id ) ); ?></b><span><?php echo esc_html( szp_fa_digits( count( $journeys ) ) ); ?> دانشجو</span></div>
							<div class="szp-growth-coach-roster">
								<?php foreach ( $journeys as $journey ) :
									$info = SZP_Groups::user_info( $journey->user_id ); ?>
									<span><?php echo esc_html( $info ? $info['name'] : ( '#' . $journey->user_id ) ); ?><small>هفته <?php echo esc_html( szp_fa_digits( SZP_Coach::last_week_no( $journey->id ) ) ); ?></small></span>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="szp-growth-help"><?php echo esc_html( szp_ui_text( 'coaching_admin_help', 'مدیریت تخصصی برنامه‌ها، شاخص‌ها و بازخوردها از صفحه مدیریت کوچینگ در پیشخوان انجام می‌شود.' ) ); ?></p>
			<?php else :
				$courses = SZP_Coach::student_course_ids( $user_id ); ?>
				<div class="szp-growth-student-coaching">
					<?php foreach ( $courses as $course_id ) :
						$journey = SZP_Coach::ensure_journey( $course_id, $user_id );
						$weeks   = $journey ? SZP_Coach::get_weeks( $journey->id ) : array();
						$active  = null;
						foreach ( $weeks as $week ) { if ( $week->status !== 'draft' ) { $active = $week; } }
						$actions = $active ? SZP_Coach::decode( $active->actions ) : array();
						$tasks   = $active ? SZP_Coach::decode( $active->tasks ) : array();
						?>
						<article class="szp-growth-coaching-card">
							<header><b><?php echo esc_html( get_the_title( $course_id ) ); ?></b><span><?php echo $active ? esc_html( 'هفته ' . szp_fa_digits( $active->week_no ) ) : 'در انتظار برنامه کوچ'; ?></span></header>
							<?php if ( $active ) : ?>
								<div class="szp-growth-coaching-columns">
									<div><b>اقدامات این هفته</b><?php echo self::simple_items( $actions, 'هنوز اقدامی ثبت نشده است.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
									<div><b>تکالیف این هفته</b><?php echo self::simple_items( $tasks, 'هنوز تکلیفی ثبت نشده است.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								</div>
								<?php if ( ! empty( $active->coach_feedback ) ) : ?><div class="szp-growth-feedback"><b>بازخورد کوچ</b><p><?php echo nl2br( esc_html( $active->coach_feedback ) ); ?></p></div><?php endif; ?>
							<?php else : ?>
								<div class="szp-growth-empty"><span>پس از ثبت برنامه توسط کوچ، اقدامات و بازخورد همین‌جا نمایش داده می‌شوند.</span></div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php return ob_get_clean();
	}

	protected static function simple_items( $items, $empty ) {
		if ( ! $items ) {
			return '<p class="szp-growth-help">' . esc_html( $empty ) . '</p>';
		}
		$out = '<ul class="szp-growth-simple-list">';
		foreach ( $items as $item ) {
			$title = isset( $item['t'] ) ? $item['t'] : '';
			if ( $title !== '' ) {
				$out .= '<li>' . esc_html( $title ) . '</li>';
			}
		}
		return $out . '</ul>';
	}

	public static function history_details( $row ) {
		if ( empty( $row->target_complete ) && empty( $row->result_complete ) ) {
			return '';
		}
		$actions = ! empty( $row->target_actions ) ? json_decode( $row->target_actions, true ) : array();
		ob_start(); ?>
		<div class="szp-growth-history-details">
			<?php if ( ! empty( $row->target_complete ) ) : ?>
				<div><b>برنامه هفته</b>
					<?php if ( ! empty( $row->target_success ) ) : ?><p><span>نتیجه مطلوب:</span> <?php echo esc_html( $row->target_success ); ?></p><?php endif; ?>
					<?php if ( $actions ) : ?><p><span>اقدامات:</span> <?php echo esc_html( implode( '، ', $actions ) ); ?></p><?php endif; ?>
					<?php if ( ! empty( $row->target_obstacle ) ) : ?><p><span>بزرگ‌ترین مانع:</span> <?php echo esc_html( $row->target_obstacle ); ?></p><?php endif; ?>
					<?php if ( ! empty( $row->target_obstacle_plan ) ) : ?><p><span>برنامه رفع مانع:</span> <?php echo esc_html( $row->target_obstacle_plan ); ?></p><?php endif; ?>
					<p><span>مشتری جدید:</span> <?php echo esc_html( szp_fa_digits( (int) $row->target_customers ) ); ?> · <span>انگیزه:</span> <?php echo esc_html( szp_fa_digits( (int) $row->motivation ) ); ?>/۱۰ · <span>تعهد:</span> <?php echo esc_html( szp_fa_digits( (int) $row->commitment ) ); ?>/۱۰</p>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $row->result_complete ) ) : ?>
				<div><b>گزارش عملکرد</b>
					<p><span>تحقق اعلامی:</span> <?php echo esc_html( szp_fa_digits( $row->result_percent ) ); ?>٪ · <span>مشتری واقعی:</span> <?php echo esc_html( szp_fa_digits( (int) $row->result_customers ) ); ?></p>
					<?php if ( ! empty( $row->result_success ) ) : ?><p><span>موفقیت:</span> <?php echo esc_html( $row->result_success ); ?></p><?php endif; ?>
					<?php if ( ! empty( $row->result_weakness ) ) : ?><p><span>نقطه ضعف:</span> <?php echo esc_html( $row->result_weakness ); ?></p><?php endif; ?>
					<?php if ( ! empty( $row->result_learning ) ) : ?><p><span>یادگیری:</span> <?php echo esc_html( $row->result_learning ); ?></p><?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php return ob_get_clean();
	}

	public static function has_coaching_access( $user_id ) {
		return class_exists( 'SZP_Coach' ) && ( SZP_Coach::student_course_ids( $user_id ) || SZP_Coach::coach_course_ids( $user_id ) );
	}
}
