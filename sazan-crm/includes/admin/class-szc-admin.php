<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** رابط مدیریت CRM: منو، داشبورد، لیست مخاطبین، صفحه‌ی تک‌مخاطب و AJAX. */
class SZC_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_find' ) );

		$ajax = array(
			'save_contact'  => 'save_contact',
			'add_note'      => 'add_note',
			'del_note'      => 'del_note',
			'log_call'      => 'log_call',
			'add_followup'  => 'add_followup',
			'done_followup' => 'done_followup',
			'set_field'     => 'set_field',
			'send_sms'      => 'send_sms',
			'schedule_sms'  => 'schedule_sms',
			'custom_sms'    => 'custom_sms',
			'del_activity'  => 'del_activity',
			'del_contact'   => 'del_contact',
			'add_contact'   => 'add_contact',
			'enroll'        => 'enroll',
			'blacklist'     => 'blacklist',
			'merge'         => 'merge',
			'group_create'  => 'group_create',
			'group_rename'  => 'group_rename',
			'group_delete'  => 'group_delete',
			'set_group'     => 'set_group',
			'group_bulk'    => 'group_bulk',
			'list_bulk'     => 'list_bulk',
			'dialer_call'   => 'dialer_call',
		);
		foreach ( $ajax as $action => $method ) {
			add_action( 'wp_ajax_szc_' . $action, array( __CLASS__, 'ajax_' . $method ) );
			// کارشناسانِ پورتال کاربرِ وردپرس نیستند (نشستِ اختصاصیِ CRM)، پس درخواست‌هایشان
			// به هندلرهای nopriv می‌رسد. امنیت با guard() (نشستِ کارشناس/مدیر + nonce) تأمین می‌شود.
			add_action( 'wp_ajax_nopriv_szc_' . $action, array( __CLASS__, 'ajax_' . $method ) );
		}
	}

	public static function menu() {
		$cap = SZC_Settings::CAP;
		add_menu_page( 'سازان CRM', 'سازان CRM', $cap, 'szc', array( __CLASS__, 'page_dashboard' ), 'dashicons-phone', 26 );
		add_submenu_page( 'szc', 'داشبورد', 'داشبورد', $cap, 'szc', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'szc', 'مخاطبین', 'مخاطبین', $cap, 'szc-contacts', array( __CLASS__, 'page_contacts' ) );
		add_submenu_page( 'szc', 'افزودن مخاطب', 'افزودن مخاطب', $cap, 'szc-add', array( __CLASS__, 'page_add' ) );
		add_submenu_page( 'szc', 'پیگیری‌ها', 'پیگیری‌ها', $cap, 'szc-followups', array( 'SZC_Admin_Pages', 'page_followups' ) );
		add_submenu_page( 'szc', 'مخاطبین تکراری', 'مخاطبین تکراری', $cap, 'szc-duplicates', array( 'SZC_Admin_Pages', 'page_duplicates' ) );
		add_submenu_page( 'szc', 'ایمپورت شماره‌ها', 'ایمپورت شماره‌ها', $cap, 'szc-import', array( 'SZC_Admin_Pages', 'page_import' ) );
		add_submenu_page( 'szc', 'ارسال همگانی', 'ارسال همگانی', $cap, 'szc-broadcast', array( 'SZC_Admin_Pages', 'page_broadcast' ) );
		add_submenu_page( 'szc', 'قالب‌های پیامک', 'قالب‌های پیامک', $cap, 'szc-templates', array( 'SZC_Admin_Pages', 'page_templates' ) );
		add_submenu_page( 'szc', 'دنباله‌های پیامکی', 'دنباله‌های پیامکی', $cap, 'szc-sequences', array( 'SZC_Admin_Pages', 'page_sequences' ) );
		add_submenu_page( 'szc', 'بخش‌بندی‌ها', 'بخش‌بندی‌ها', $cap, 'szc-segments', array( 'SZC_Admin_Pages', 'page_segments' ) );
		add_submenu_page( 'szc', 'لیست سیاه', 'لیست سیاه', $cap, 'szc-blacklist', array( 'SZC_Admin_Pages', 'page_blacklist' ) );
		add_submenu_page( 'szc', 'گزارش‌ها', 'گزارش‌ها', $cap, 'szc-reports', array( 'SZC_Admin_Pages', 'page_reports' ) );
		add_submenu_page( 'szc', 'گزارش تحویل پیامک', 'گزارش تحویل پیامک', $cap, 'szc-delivery', array( 'SZC_Admin_Pages', 'page_delivery' ) );
		add_submenu_page( 'szc', 'مراحل و فیلدها', 'مراحل و فیلدها', $cap, 'szc-pipeline', array( 'SZC_Admin_Pages', 'page_pipeline' ) );
		add_submenu_page( 'szc', 'کارشناسان', 'کارشناسان', $cap, 'szc-agents', array( 'SZC_Admin_Pages', 'page_agents' ) );
		add_submenu_page( 'szc', 'تنظیمات', 'تنظیمات', $cap, 'szc-settings', array( 'SZC_Admin_Pages', 'page_settings' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'szc' ) === false ) {
			return;
		}
		$css = SZC_DIR . 'assets/css/admin.css';
		$js  = SZC_DIR . 'assets/js/admin.js';
		wp_enqueue_style( 'szc-admin', SZC_URL . 'assets/css/admin.css', array(), file_exists( $css ) ? filemtime( $css ) : SZC_VERSION );
		wp_enqueue_script( 'szc-admin', SZC_URL . 'assets/js/admin.js', array(), file_exists( $js ) ? filemtime( $js ) : SZC_VERSION, true );
		wp_localize_script( 'szc-admin', 'SZC_ADMIN', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szc_admin' ),
		) );
	}

	protected static function url( $page, $args = array() ) {
		// در بافتِ پورتال فرانت‌اند، آدرس‌ها به همان پورتال اشاره کنند (نه پیشخوان).
		if ( class_exists( 'SZC_Portal' ) && SZC_Portal::is_portal_context() ) {
			$view = ( $page === 'szc' ) ? 'dashboard' : preg_replace( '/^szc-/', '', $page );
			if ( $view === 'contacts' && isset( $args['contact'] ) ) {
				$id = (int) $args['contact'];
				unset( $args['contact'] );
				return SZC_Portal::url( 'contact', array_merge( array( 'id' => $id ), $args ) );
			}
			return SZC_Portal::url( $view, $args );
		}
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	/** جستجوی سریع شماره: اگر مخاطبِ دقیق پیدا شد مستقیم بازش کن، وگرنه در فهرست جستجو کن. */
	public static function maybe_find() {
		if ( ! isset( $_GET['page'], $_GET['find'] ) || $_GET['page'] !== 'szc-contacts' ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( ! SZC_Settings::can_access() ) {
			return;
		}
		$raw = sanitize_text_field( wp_unslash( $_GET['find'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$m   = szc_normalize_mobile( $raw );
		if ( szc_is_valid_mobile( $m ) ) {
			$c = SZC_Contacts::get_by_mobile( $m );
			if ( $c && ( SZC_Settings::is_manager() || (int) $c->owner_id === SZC_Auth::actor_id() ) ) {
				wp_safe_redirect( self::contact_url( $c->id ) );
				exit;
			}
		}
		wp_safe_redirect( self::url( 'szc-contacts', array( 's' => $raw ) ) );
		exit;
	}

	protected static function contact_url( $id ) {
		return self::url( 'szc-contacts', array( 'contact' => (int) $id ) );
	}

	/* ==================== داشبورد ==================== */

	public static function page_dashboard() {
		if ( ! SZC_Settings::can_access() ) { wp_die( 'دسترسی غیرمجاز' ); }
		$owner  = SZC_Settings::scope_owner();
		$counts = SZC_Contacts::counts_by_stage( $owner );
		$total  = SZC_Contacts::total( $owner );
		$queue  = SZC_SMS::queue_counts();
		$due    = SZC_Activity::due_followups( 20, $owner );
		$calls  = SZC_Reports::calls_today( $owner ); // کارشناس: تماس‌های خودش
		$sms    = SZC_SMS::sent_today();
		$funnel = SZC_Reports::funnel( $owner );
		?>
		<div class="wrap szc-wrap">
			<h1>سازان CRM — داشبورد</h1>
			<?php if ( ! SZC_SMS::enabled() ) : ?>
				<div class="notice notice-warning"><p>سرویس پیامک هنوز فعال نیست. برای ارسال پیامک، از <a href="<?php echo esc_url( self::url( 'szc-settings' ) ); ?>">تنظیمات</a> کلید API و خط ارسال را وارد و فعال کنید.</p></div>
			<?php endif; ?>

			<div class="szc-kpis">
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $calls ) ); ?></span><span class="szc-kpi-l">تماس امروز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $sms ) ); ?></span><span class="szc-kpi-l">پیامک ارسالی امروز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( count( $due ) ) ); ?></span><span class="szc-kpi-l">پیگیری سررسیدشده</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $funnel['conversion'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ تبدیل (ثبت‌نام)</span></div>
			</div>

			<div class="szc-stats">
				<div class="szc-stat"><span class="szc-stat-n"><?php echo esc_html( szc_fa_digits( $total ) ); ?></span><span class="szc-stat-l">کل مخاطبین</span></div>
				<?php foreach ( SZC_Settings::stages() as $k => $lbl ) : ?>
					<a class="szc-stat" href="<?php echo esc_url( self::url( 'szc-contacts', array( 'stage' => $k ) ) ); ?>">
						<span class="szc-stat-n"><?php echo esc_html( szc_fa_digits( $counts[ $k ] ) ); ?></span>
						<span class="szc-stat-l"><?php echo esc_html( $lbl ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="szc-dash-cols">
				<div class="szc-card">
					<h2>پیگیری‌های سررسیدشده</h2>
					<?php if ( ! $due ) : ?>
						<p class="szc-muted">پیگیری معوقی نداری. 👌</p>
					<?php else : ?>
						<ul class="szc-due-list">
							<?php foreach ( $due as $d ) : ?>
								<li>
									<a href="<?php echo esc_url( self::contact_url( $d->contact_id ) ); ?>"><b><?php echo esc_html( trim( $d->first_name . ' ' . $d->last_name ) ?: szc_fa_digits( $d->mobile ) ); ?></b></a>
									<span class="szc-muted"><?php echo esc_html( szc_format_mysql( $d->due_at ) ); ?></span>
									<?php if ( $d->body ) : ?><em><?php echo esc_html( $d->body ); ?></em><?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="szc-card">
					<h2>صف پیامک</h2>
					<ul class="szc-queue-stats">
						<li>در انتظار ارسال: <b><?php echo esc_html( szc_fa_digits( $queue['pending'] ) ); ?></b></li>
						<li>ارسال‌شده: <b><?php echo esc_html( szc_fa_digits( $queue['sent'] ) ); ?></b></li>
						<li>ناموفق: <b><?php echo esc_html( szc_fa_digits( $queue['failed'] ) ); ?></b></li>
						<li>لغوشده: <b><?php echo esc_html( szc_fa_digits( $queue['canceled'] ) ); ?></b></li>
					</ul>
					<p class="szc-muted">پیامک‌های خودکار (تشکر/دعوت) طبق زمان‌بندی و در بازه‌ی مجاز ارسال، به‌صورت خودکار فرستاده می‌شوند.</p>
				</div>
			</div>
		</div>
		<?php
	}

	/* ==================== مخاطبین ==================== */

	public static function page_contacts() {
		if ( ! SZC_Settings::can_access() ) { wp_die( 'دسترسی غیرمجاز' ); }
		$contact_id = isset( $_GET['contact'] ) ? absint( $_GET['contact'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $contact_id ) {
			$c = SZC_Contacts::get( $contact_id );
			if ( $c ) {
				self::render_single( $c );
				return;
			}
		}
		self::render_list();
	}

	protected static function render_list() {
		$is_manager = SZC_Settings::is_manager();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args = array(
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'stage'    => isset( $_GET['stage'] ) ? sanitize_key( $_GET['stage'] ) : '',
			'priority' => isset( $_GET['priority'] ) ? sanitize_key( $_GET['priority'] ) : '',
			'due'      => isset( $_GET['due'] ) ? sanitize_key( $_GET['due'] ) : '',
			'owner'    => isset( $_GET['owner'] ) ? absint( $_GET['owner'] ) : '',
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'updated_at',
			'order'    => isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'DESC',
			'page'     => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
			'per_page' => 25,
		);
		// phpcs:enable
		// کارشناس فقط سرنخ‌های خودش را می‌بیند.
		if ( ! $is_manager ) {
			$args['owner'] = SZC_Auth::actor_id();
		}
		$res       = SZC_Contacts::query( $args );
		$items     = $res['items'];
		$total     = $res['total'];
		$pages     = (int) ceil( $total / $res['per_page'] );
		$stages    = SZC_Settings::stages();
		$prios     = SZC_Settings::priorities();
		$assignees = SZC_Settings::assignable_users();
		$sequences = SZC_Sequences::active_sequences();
		$templates = SZC_Templates::all();
		$segments  = SZC_Segments::all();
		$colspan   = $is_manager ? 8 : 7;
		$filter_hidden = array_filter( array(
			's' => $args['search'], 'stage' => $args['stage'], 'priority' => $args['priority'],
			'due' => $args['due'], 'owner' => $args['owner'],
		), 'strlen' );
		?>
		<div class="wrap szc-wrap">
			<h1 class="wp-heading-inline">مخاطبین</h1>
			<a href="<?php echo esc_url( self::url( 'szc-add' ) ); ?>" class="page-title-action">افزودن مخاطب</a>
			<a href="<?php echo esc_url( self::url( 'szc-import' ) ); ?>" class="page-title-action">ایمپورت شماره‌ها</a>
			<form method="get" class="szc-quickfind">
				<input type="hidden" name="page" value="szc-contacts">
				<input type="search" name="find" dir="ltr" placeholder="جستجوی سریع شماره…">
				<button class="button">یافتن</button>
			</form>
			<?php $bmsg = get_transient( 'szc_bulk_' . SZC_Auth::actor_id() ); if ( $bmsg ) { delete_transient( 'szc_bulk_' . SZC_Auth::actor_id() ); echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $bmsg ) . '</p></div>'; } ?>

			<form method="get" class="szc-filters">
				<input type="hidden" name="page" value="szc-contacts">
				<input type="search" name="s" value="<?php echo esc_attr( $args['search'] ); ?>" placeholder="جستجو: نام، موبایل، شرکت…">
				<select name="stage">
					<option value="">همه مراحل</option>
					<?php foreach ( $stages as $k => $lbl ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $args['stage'], $k ); ?>><?php echo esc_html( $lbl ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="priority">
					<option value="">همه اولویت‌ها</option>
					<?php foreach ( $prios as $k => $m ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $args['priority'], $k ); ?>><?php echo esc_html( $m['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( $is_manager ) : ?>
					<select name="owner">
						<option value="">همه کارشناسان</option>
						<?php foreach ( $assignees as $uid => $name ) : ?>
							<option value="<?php echo (int) $uid; ?>" <?php selected( (int) $args['owner'], (int) $uid ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
				<select name="due">
					<option value="">—</option>
					<option value="today" <?php selected( $args['due'], 'today' ); ?>>پیگیری امروز/سررسید</option>
					<option value="overdue" <?php selected( $args['due'], 'overdue' ); ?>>پیگیری معوق</option>
				</select>
				<button class="button">اعمال فیلتر</button>
				<span class="szc-muted"><?php echo esc_html( szc_fa_digits( $total ) ); ?> مخاطب</span>
			</form>

			<div class="szc-segbar">
				<?php if ( $segments ) : ?>
					<span class="szc-muted">بخش‌بندی‌ها:</span>
					<?php foreach ( $segments as $sg ) :
						$f = SZC_Segments::filters( $sg ); ?>
						<a class="button button-small" href="<?php echo esc_url( self::url( 'szc-contacts', $f ) ); ?>"><?php echo esc_html( $sg->name ); ?></a>
					<?php endforeach; ?>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-saveseg">
					<?php wp_nonce_field( 'szc_save_segment' ); ?>
					<input type="hidden" name="action" value="szc_save_segment">
					<?php foreach ( $filter_hidden as $k => $v ) : ?><input type="hidden" name="f_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?>
					<input type="text" name="seg_name" placeholder="ذخیره فیلتر فعلی به‌نام…">
					<button class="button button-small">ذخیره بخش‌بندی</button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'szc_export' ); ?>
					<input type="hidden" name="action" value="szc_export">
					<?php foreach ( $filter_hidden as $k => $v ) : ?><input type="hidden" name="f_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?>
					<button class="button button-small">⬇ خروجی CSV</button>
				</form>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="szc-bulk-form">
				<?php wp_nonce_field( 'szc_bulk' ); ?>
				<input type="hidden" name="action" value="szc_bulk">
				<?php foreach ( $filter_hidden as $k => $v ) : ?><input type="hidden" name="f_<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?>

				<div class="szc-bulkbar">
					<label>روی:
						<select name="scope">
							<option value="selected">انتخاب‌شده‌ها</option>
							<option value="all">همه‌ی نتایج فیلتر (<?php echo esc_html( szc_fa_digits( $total ) ); ?>)</option>
						</select>
					</label>
					<select name="bulk_action" class="szc-bulk-action">
						<option value="">— اقدام گروهی —</option>
						<option value="stage">تغییر مرحله به…</option>
						<option value="priority">تغییر اولویت به…</option>
						<option value="tag">افزودن برچسب…</option>
						<?php if ( $is_manager ) : ?><option value="assign">تخصیص به کارشناس…</option><?php endif; ?>
						<option value="blacklist">افزودن به لیست سیاه</option>
						<option value="delete">حذف</option>
						<?php if ( $templates ) : ?><option value="send">ارسال پیامک (قالب)…</option><?php endif; ?>
						<?php if ( $sequences ) : ?><option value="enroll">ثبت در دنباله…</option><?php endif; ?>
					</select>
					<select name="p_stage" class="szc-bp"><?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?></select>
					<select name="p_priority" class="szc-bp"><?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?></select>
					<input type="text" name="p_tag" class="szc-bp" placeholder="برچسب">
					<?php if ( $is_manager ) : ?><select name="p_owner" class="szc-bp"><?php foreach ( $assignees as $uid => $name ) : ?><option value="<?php echo (int) $uid; ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select><?php endif; ?>
					<select name="p_template" class="szc-bp"><?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?></select>
					<select name="p_sequence" class="szc-bp"><?php foreach ( $sequences as $sq ) : ?><option value="<?php echo (int) $sq->id; ?>"><?php echo esc_html( $sq->name ); ?></option><?php endforeach; ?></select>
					<button class="button" onclick="return confirm('اقدام گروهی روی مخاطبین اعمال شود؟')">اعمال</button>
				</div>

			<table class="wp-list-table widefat fixed striped szc-table">
				<thead><tr>
					<td class="check-column"><input type="checkbox" id="szc-check-all"></td>
					<th>نام</th><th>موبایل</th><th>شغل / شرکت</th><th>اولویت</th><th>مرحله</th>
					<?php if ( $is_manager ) : ?><th>کارشناس</th><?php endif; ?>
					<th>پیگیری بعدی</th>
				</tr></thead>
				<tbody>
				<?php if ( ! $items ) : ?>
					<tr><td colspan="<?php echo (int) $colspan; ?>" class="szc-muted">مخاطبی یافت نشد.</td></tr>
				<?php else : foreach ( $items as $c ) :
					$pm = SZC_Settings::priority_meta( $c->priority ); ?>
					<tr>
						<th class="check-column"><input type="checkbox" name="ids[]" value="<?php echo (int) $c->id; ?>" class="szc-row-check"></th>
						<td><a href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>"><b><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></b></a><?php echo $c->opt_out ? ' <span class="szc-optout">لغو پیامک</span>' : ''; ?></td>
						<td dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></td>
						<td><?php echo esc_html( trim( $c->job . ( $c->company ? ' — ' . $c->company : '' ) ) ?: '—' ); ?></td>
						<td><span class="szc-badge" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( $pm['label'] ); ?></span></td>
						<td><?php echo esc_html( SZC_Settings::stage_label( $c->stage ) ); ?></td>
						<?php if ( $is_manager ) : ?><td class="szc-muted"><?php echo esc_html( $c->owner_id ? ( $assignees[ (int) $c->owner_id ] ?? '#' . $c->owner_id ) : '—' ); ?></td><?php endif; ?>
						<td class="szc-muted"><?php echo esc_html( $c->next_followup_at ? szc_format_mysql( $c->next_followup_at ) : '—' ); ?></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
			</form>

			<?php if ( $pages > 1 ) :
				$pbase = self::url( 'szc-contacts', array_filter( array(
					's'        => $args['search'],
					'stage'    => $args['stage'],
					'priority' => $args['priority'],
					'due'      => $args['due'],
					'owner'    => $args['owner'],
					'orderby'  => $args['orderby'],
					'order'    => $args['order'],
				), 'strlen' ) );
				?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo paginate_links( array(
						'base'      => $pbase . '%_%',
						'format'    => '&paged=%#%',
						'current'   => $args['page'],
						'total'     => $pages,
						'prev_text' => '‹',
						'next_text' => '›',
					) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	protected static function render_single( $c ) {
		$pm          = SZC_Settings::priority_meta( $c->priority );
		$stages      = SZC_Settings::stages();
		$prios       = SZC_Settings::priorities();
		$outcomes    = SZC_Settings::call_outcomes();
		$templates   = SZC_Templates::all();
		$timeline    = SZC_Activity::timeline( $c->id );
		$is_manager  = SZC_Settings::is_manager();
		$assignees   = SZC_Settings::assignable_users();
		$sequences   = SZC_Sequences::active_sequences();
		$enrollments = SZC_Sequences::enrollments_for_contact( $c->id );
		$blocked     = SZC_Blacklist::is_blocked( $c->mobile );
		$customs     = SZC_Settings::custom_fields();
		$cmeta       = SZC_Contacts::get_meta( $c );
		?>
		<div class="wrap szc-wrap szc-single" data-contact="<?php echo (int) $c->id; ?>">
			<a href="<?php echo esc_url( self::url( 'szc-contacts' ) ); ?>" class="szc-back">‹ بازگشت به لیست</a>
			<div class="szc-msg" aria-live="polite"></div>

			<div class="szc-single-head">
				<h1><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></h1>
				<span class="szc-badge" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( $pm['label'] ); ?></span>
				<a href="tel:<?php echo esc_attr( $c->mobile ); ?>" class="szc-mobile" dir="ltr">☎ <?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></a>
				<button class="button button-primary szc-quickcall" data-szc-act="quick_call">تماس گرفتم ✓</button>
			</div>
			<p class="szc-muted szc-quickhint">«تماس گرفتم» یک تماسِ موفق ثبت می‌کند و پیامک تشکر خودکار را (در صورت فعال‌بودن) زمان‌بندی می‌کند.</p>

			<div class="szc-single-grid">
				<div class="szc-col">
					<div class="szc-card">
						<h2>اطلاعات مخاطب</h2>
						<div class="szc-form2">
							<label>نام<input type="text" data-f="first_name" value="<?php echo esc_attr( $c->first_name ); ?>"></label>
							<label>نام خانوادگی<input type="text" data-f="last_name" value="<?php echo esc_attr( $c->last_name ); ?>"></label>
							<label>موبایل<input type="text" dir="ltr" data-f="mobile" value="<?php echo esc_attr( $c->mobile ); ?>"></label>
							<label>شغل<input type="text" data-f="job" value="<?php echo esc_attr( $c->job ); ?>"></label>
							<label>شرکت<input type="text" data-f="company" value="<?php echo esc_attr( $c->company ); ?>"></label>
							<label>شهر<input type="text" data-f="city" value="<?php echo esc_attr( $c->city ); ?>"></label>
							<label>ایمیل<input type="email" dir="ltr" data-f="email" value="<?php echo esc_attr( $c->email ); ?>"></label>
							<label>منبع<input type="text" data-f="source" value="<?php echo esc_attr( $c->source ); ?>"></label>
							<label>برچسب‌ها<input type="text" data-f="tags" value="<?php echo esc_attr( $c->tags ); ?>" placeholder="با ویرگول جدا کنید"></label>
							<?php foreach ( $customs as $cf ) : ?>
								<label><?php echo esc_html( $cf['label'] ); ?><input type="text" data-cf="<?php echo esc_attr( $cf['key'] ); ?>" value="<?php echo esc_attr( $cmeta[ $cf['key'] ] ?? '' ); ?>"></label>
							<?php endforeach; ?>
						</div>
						<div class="szc-form-row">
							<label>اولویت
								<select data-field="priority" data-szc-act="set_field">
									<?php foreach ( $prios as $k => $m ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c->priority, $k ); ?>><?php echo esc_html( $m['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label>مرحله
								<select data-field="stage" data-szc-act="set_field">
									<?php foreach ( $stages as $k => $lbl ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c->stage, $k ); ?>><?php echo esc_html( $lbl ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="szc-check"><input type="checkbox" data-field="opt_out" data-szc-act="set_field" <?php checked( $c->opt_out ); ?>> لغو دریافت پیامک</label>
						</div>
						<?php if ( $is_manager ) : ?>
						<div class="szc-form-row">
							<label>کارشناس مسئول
								<select data-field="owner" data-szc-act="set_field">
									<option value="0">— تخصیص‌نیافته —</option>
									<?php foreach ( $assignees as $uid => $name ) : ?>
										<option value="<?php echo (int) $uid; ?>" <?php selected( (int) $c->owner_id, (int) $uid ); ?>><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>
						<?php endif; ?>
						<div class="szc-actions">
							<button class="button button-primary" data-szc-act="save_contact">ذخیره اطلاعات</button>
							<button class="button szc-danger" data-szc-act="del_contact">حذف مخاطب</button>
						</div>
					</div>

					<div class="szc-card">
						<h2>پیام (پیامک / بله / روبیکا)</h2>
						<?php if ( ! $templates ) : ?>
							<p class="szc-muted">هنوز قالبی نساخته‌اید. از <a href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">قالب‌های پیامک</a> یک قالب بسازید.</p>
						<?php else : ?>
							<select data-sms-template>
								<?php foreach ( $templates as $t ) : ?>
									<option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<div class="szc-actions">
								<button class="button" data-szc-act="send_sms">ارسال پیامک</button>
								<button class="button" data-szc-act="schedule_sms">زمان‌بندی (۱ ساعت بعد)</button>
							</div>
							<label class="szc-msg-lbl" style="margin-top:12px;display:block">متنِ دلخواه (اختیاری — بر قالب اولویت دارد)
									<textarea data-custom-sms rows="3" style="width:100%" placeholder="متنِ پیام… با متغیرهایی مثل %first%، %last% و %name%"></textarea>
								</label>
								<div class="szc-actions">
									<button class="button" data-szc-act="custom_sms">ارسال پیامکِ دلخواه</button>
								</div>
								<?php if ( $c->opt_out ) : ?><p class="szc-muted">این مخاطب لغو دریافت پیام دارد؛ ارسال انجام نمی‌شود.</p><?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="szc-col">
					<div class="szc-card">
						<h2>ثبت تماس</h2>
						<div class="szc-form-row">
							<select data-call-outcome>
								<?php foreach ( $outcomes as $k => $lbl ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option>
								<?php endforeach; ?>
							</select>
							<label class="szc-check"><input type="checkbox" data-call-sms checked> پیامک تشکر خودکار پس از تماس</label>
						</div>
						<textarea data-call-note rows="2" placeholder="یادداشت تماس (اختیاری)"></textarea>
						<div class="szc-actions"><button class="button button-primary" data-szc-act="log_call">ثبت تماس</button></div>
					</div>

					<div class="szc-card">
						<h2>پیگیری بعدی (Callback)</h2>
						<div class="szc-presets">
							<button type="button" class="button button-small" data-preset="tomorrow10">فردا ۱۰ صبح</button>
							<button type="button" class="button button-small" data-preset="today17">امروز ۱۷</button>
							<button type="button" class="button button-small" data-preset="d3">۳ روز دیگر</button>
							<button type="button" class="button button-small" data-preset="week">هفته‌ی بعد</button>
						</div>
						<div class="szc-form-row">
							<input type="datetime-local" data-followup-at>
							<input type="text" data-followup-note placeholder="موضوع پیگیری (اختیاری)">
							<button class="button" data-szc-act="add_followup">ثبت پیگیری</button>
						</div>
					</div>

					<div class="szc-card">
						<h2>دنباله و لیست سیاه</h2>
						<?php if ( $sequences ) : ?>
							<div class="szc-form-row">
								<select data-seq>
									<?php foreach ( $sequences as $sq ) : ?><option value="<?php echo (int) $sq->id; ?>"><?php echo esc_html( $sq->name ); ?></option><?php endforeach; ?>
								</select>
								<button class="button" data-szc-act="enroll">ثبت در دنباله</button>
							</div>
						<?php else : ?>
							<p class="szc-muted">هنوز دنباله‌ای نساخته‌اید. از <a href="<?php echo esc_url( self::url( 'szc-sequences' ) ); ?>">دنباله‌های پیامکی</a> بسازید.</p>
						<?php endif; ?>
						<?php if ( $enrollments ) : ?>
							<ul class="szc-enr-list">
								<?php foreach ( $enrollments as $en ) : ?>
									<li><b><?php echo esc_html( $en->name ); ?></b> — <?php echo esc_html( $en->status === 'active' ? 'فعال' : ( $en->status === 'canceled' ? 'لغوشده' : $en->status ) ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="szc-actions">
							<?php if ( $blocked ) : ?>
								<button class="button" data-szc-act="blacklist" data-op="remove">خروج از لیست سیاه</button>
								<span class="szc-optout">در لیست سیاه</span>
							<?php else : ?>
								<button class="button szc-danger" data-szc-act="blacklist" data-op="add">افزودن به لیست سیاه</button>
							<?php endif; ?>
						</div>
					</div>

					<div class="szc-card">
						<h2>ادغام رکورد تکراری</h2>
						<p class="szc-muted">اگر این شخص رکورد دیگری هم دارد، موبایلِ آن رکورد را وارد کنید تا در همین مخاطب ادغام شود (سوابق منتقل و رکورد دوم حذف می‌شود).</p>
						<div class="szc-form-row">
							<input type="text" dir="ltr" data-merge-mobile placeholder="۰۹... رکورد دوم">
							<button class="button szc-danger" data-szc-act="merge">ادغام در این مخاطب</button>
						</div>
					</div>

					<div class="szc-card">
						<h2>یادداشت</h2>
						<textarea data-note-body rows="2" placeholder="یادداشت درباره‌ی این مخاطب…"></textarea>
						<div class="szc-actions"><button class="button" data-szc-act="add_note">افزودن یادداشت</button></div>
					</div>

					<div class="szc-card">
						<h2>تاریخچه</h2>
						<?php self::render_timeline( $timeline ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	protected static function render_timeline( $items ) {
		if ( ! $items ) {
			echo '<p class="szc-muted">هنوز فعالیتی ثبت نشده است.</p>';
			return;
		}
		$type_lbl = array(
			'note'     => 'یادداشت',
			'call'     => 'تماس',
			'sms'      => 'پیامک',
			'stage'    => 'تغییر مرحله',
			'followup' => 'پیگیری',
		);
		$sms_lbl = array( 'sent' => 'ارسال شد', 'failed' => 'ناموفق', 'scheduled' => 'زمان‌بندی شد' );
		echo '<ul class="szc-timeline">';
		foreach ( $items as $it ) {
			$who  = $it['user_id'] ? SZC_Auth::display_name( $it['user_id'] ) : '';
			$head = $type_lbl[ $it['type'] ] ?? $it['type'];
			$extra = '';
			if ( $it['type'] === 'call' && $it['outcome'] ) {
				$extra = ' — ' . SZC_Settings::outcome_label( $it['outcome'] );
			} elseif ( $it['type'] === 'sms' && $it['outcome'] ) {
				$extra = ' — ' . ( $sms_lbl[ $it['outcome'] ] ?? $it['outcome'] );
			} elseif ( $it['type'] === 'followup' && $it['due_at'] ) {
				$extra = ' — سررسید: ' . szc_format_mysql( $it['due_at'] ) . ( $it['done'] ? ' (انجام شد)' : '' );
			}
			echo '<li class="szc-tl szc-tl-' . esc_attr( $it['type'] ) . '">';
			echo '<div class="szc-tl-head"><b>' . esc_html( $head . $extra ) . '</b>';
			echo '<span class="szc-muted">' . esc_html( szc_time_ago( $it['created_at'] ) ) . ( $who ? ' · ' . esc_html( $who ) : '' ) . '</span></div>';
			if ( $it['body'] !== '' ) {
				echo '<p>' . nl2br( esc_html( $it['body'] ) ) . '</p>';
			}
			if ( $it['type'] === 'followup' && ! $it['done'] ) {
				echo '<button class="button-link szc-inline" data-szc-act="done_followup" data-id="' . (int) $it['id'] . '">علامت انجام‌شده</button>';
			}
			$del_act = $it['kind'] === 'note' ? 'del_note' : 'del_activity';
			echo ' <button class="button-link szc-inline szc-danger" data-szc-act="' . esc_attr( $del_act ) . '" data-id="' . (int) $it['id'] . '">حذف</button>';
			echo '</li>';
		}
		echo '</ul>';
	}

	/* ==================== افزودن مخاطب ==================== */

	public static function page_add() {
		if ( ! SZC_Settings::can_access() ) { wp_die( 'دسترسی غیرمجاز' ); }
		$prios   = SZC_Settings::priorities();
		$customs = SZC_Settings::custom_fields();
		?>
		<div class="wrap szc-wrap">
			<h1>افزودن مخاطب</h1>
			<div class="szc-msg" aria-live="polite"></div>
			<div class="szc-card szc-add-form" style="max-width:640px">
				<div class="szc-form2">
					<label>نام<input type="text" data-f="first_name"></label>
					<label>نام خانوادگی<input type="text" data-f="last_name"></label>
					<label>موبایل *<input type="text" dir="ltr" data-f="mobile" placeholder="۰۹۱۲..."></label>
					<label>شغل<input type="text" data-f="job"></label>
					<label>شرکت<input type="text" data-f="company"></label>
					<label>شهر<input type="text" data-f="city"></label>
					<label>ایمیل<input type="email" dir="ltr" data-f="email"></label>
					<label>منبع<input type="text" data-f="source"></label>
					<label>اولویت
						<select data-f="priority">
							<?php foreach ( $prios as $k => $m ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, 'warm' ); ?>><?php echo esc_html( $m['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<?php foreach ( $customs as $cf ) : ?>
						<label><?php echo esc_html( $cf['label'] ); ?><input type="text" data-cf="<?php echo esc_attr( $cf['key'] ); ?>"></label>
					<?php endforeach; ?>
				</div>
				<div class="szc-actions"><button class="button button-primary" data-szc-act="add_contact">افزودن مخاطب</button></div>
			</div>
		</div>
		<?php
	}

	/* ==================== AJAX ==================== */

	protected static function guard() {
		if ( ! SZC_Settings::can_access() ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ), 403 );
		}
		check_ajax_referer( 'szc_admin', 'nonce' );
	}

	protected static function req_contact() {
		$id = isset( $_POST['contact'] ) ? absint( $_POST['contact'] ) : 0;
		$c  = $id ? SZC_Contacts::get( $id ) : null;
		if ( ! $c ) {
			wp_send_json_error( array( 'msg' => 'مخاطب یافت نشد.' ) );
		}
		return $c;
	}

	public static function ajax_add_contact() {
		self::guard();
		$in = self::posted_fields();
		if ( szc_normalize_mobile( $in['mobile'] ?? '' ) === '' ) {
			wp_send_json_error( array( 'msg' => 'موبایل معتبر وارد کنید.' ) );
		}
		$id = SZC_Contacts::create( $in );
		if ( ! $id ) {
			wp_send_json_error( array( 'msg' => 'این موبایل قبلاً ثبت شده یا نامعتبر است.' ) );
		}
		wp_send_json_success( array( 'msg' => 'مخاطب افزوده شد.', 'redirect' => self::contact_url( $id ) ) );
	}

	public static function ajax_save_contact() {
		self::guard();
		$c = self::req_contact();
		SZC_Contacts::update( (int) $c->id, self::posted_fields() );
		wp_send_json_success( array( 'msg' => 'ذخیره شد.' ) );
	}

	protected static function posted_fields() {
		$fields = array( 'first_name', 'last_name', 'mobile', 'job', 'company', 'city', 'email', 'source', 'tags', 'priority', 'stage' );
		$out    = array();
		foreach ( $fields as $f ) {
			if ( isset( $_POST[ $f ] ) ) {
				$out[ $f ] = wp_unslash( $_POST[ $f ] );
			}
		}
		if ( isset( $_POST['cf'] ) && is_array( $_POST['cf'] ) ) {
			$out['cf'] = (array) wp_unslash( $_POST['cf'] );
		}
		return $out;
	}

	public static function ajax_set_field() {
		self::guard();
		$c     = self::req_contact();
		$field = isset( $_POST['field'] ) ? sanitize_key( $_POST['field'] ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
		if ( $field === 'stage' ) {
			SZC_Contacts::set_stage( (int) $c->id, $value );
			SZC_Activity::log( (int) $c->id, 'stage', array( 'outcome' => $value, 'body' => 'مرحله به «' . SZC_Settings::stage_label( $value ) . '» تغییر کرد.' ) );
		} elseif ( $field === 'priority' ) {
			SZC_Contacts::set_priority( (int) $c->id, $value );
		} elseif ( $field === 'opt_out' ) {
			SZC_Contacts::set_opt_out( (int) $c->id, $value === '1' || $value === 'true' );
		} elseif ( $field === 'owner' ) {
			if ( ! SZC_Settings::is_manager() ) {
				wp_send_json_error( array( 'msg' => 'فقط مدیر می‌تواند تخصیص دهد.' ), 403 );
			}
			SZC_Contacts::set_owner( (int) $c->id, absint( $value ) );
		} else {
			wp_send_json_error( array( 'msg' => 'فیلد نامعتبر.' ) );
		}
		wp_send_json_success( array( 'msg' => 'به‌روزرسانی شد.' ) );
	}

	public static function ajax_enroll() {
		self::guard();
		$c   = self::req_contact();
		$sid = isset( $_POST['sequence'] ) ? absint( $_POST['sequence'] ) : 0;
		$res = SZC_Sequences::enroll( $sid, $c );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => $res['msg'] ?? 'ثبت‌نام ناموفق بود.' ) );
		}
		wp_send_json_success( array( 'msg' => $res['msg'], 'reload' => true ) );
	}

	public static function ajax_blacklist() {
		self::guard();
		$c  = self::req_contact();
		$op = isset( $_POST['op'] ) ? sanitize_key( $_POST['op'] ) : 'add';
		if ( $op === 'remove' ) {
			SZC_Blacklist::remove_mobile( $c->mobile );
			SZC_Contacts::set_opt_out( (int) $c->id, 0 );
			wp_send_json_success( array( 'msg' => 'از لیست سیاه خارج شد.', 'reload' => true ) );
		}
		SZC_Blacklist::add( $c->mobile, 'دستی از پرونده‌ی مخاطب' );
		wp_send_json_success( array( 'msg' => 'به لیست سیاه افزوده شد.', 'reload' => true ) );
	}

	public static function ajax_add_note() {
		self::guard();
		$c    = self::req_contact();
		$body = isset( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';
		if ( trim( $body ) === '' ) {
			wp_send_json_error( array( 'msg' => 'یادداشت خالی است.' ) );
		}
		SZC_Activity::add_note( (int) $c->id, $body );
		wp_send_json_success( array( 'msg' => 'یادداشت افزوده شد.', 'reload' => true ) );
	}

	public static function ajax_del_note() {
		self::guard();
		SZC_Activity::delete_note( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
		wp_send_json_success( array( 'reload' => true ) );
	}

	public static function ajax_del_activity() {
		self::guard();
		SZC_Activity::delete_activity( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
		wp_send_json_success( array( 'reload' => true ) );
	}

	public static function ajax_log_call() {
		self::guard();
		$c        = self::req_contact();
		$outcome  = isset( $_POST['outcome'] ) ? sanitize_key( $_POST['outcome'] ) : 'answered';
		$note     = isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '';
		$template = isset( $_POST['template'] ) ? absint( $_POST['template'] ) : 0;
		// sms: '1'=قالب پیش‌فرض/نگاشت، '0'=هیچ، مقدار مثبت = شناسه‌ی قالبِ انتخابی.
		$want = null;
		if ( isset( $_POST['sms'] ) ) {
			$want = ( $_POST['sms'] === '1' || $template > 0 );
			if ( $_POST['sms'] === '0' && $template === 0 ) {
				$want = false;
			}
		}
		$res = SZC_Activity::log_call( $c, $outcome, $note, $want, $template );
		$msg = 'تماس ثبت شد.';
		if ( ! empty( $res['sms_scheduled'] ) ) {
			$msg .= ' پیامک برای ' . szc_fa_digits( (int) SZC_Settings::get( 'auto_delay_min' ) ) . ' دقیقه بعد زمان‌بندی شد.';
		}
		wp_send_json_success( array( 'msg' => $msg, 'reload' => true ) );
	}

	/** ثبت تماس در حالت دایلر: مثل log_call ولی پیگیری‌های بازِ مخاطب هم بسته می‌شوند (تا از صف خارج شود). */
	public static function ajax_dialer_call() {
		self::guard();
		$c        = self::req_contact();
		$outcome  = isset( $_POST['outcome'] ) ? sanitize_key( $_POST['outcome'] ) : 'answered';
		$note     = isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '';
		$template = isset( $_POST['template'] ) ? absint( $_POST['template'] ) : 0;
		$want     = true;
		if ( isset( $_POST['sms'] ) && $_POST['sms'] === '0' && $template === 0 ) {
			$want = false;
		}
		SZC_Activity::log_call( $c, $outcome, $note, $want, $template );
		SZC_Activity::complete_contact_followups( (int) $c->id );
		wp_send_json_success( array( 'msg' => 'ثبت شد — مخاطب بعدی.', 'reload' => true ) );
	}

	/** ارسال پیامکِ دلخواه (متن آزاد) به مخاطب. */
	public static function ajax_custom_sms() {
		self::guard();
		$c    = self::req_contact();
		$text = isset( $_POST['text'] ) ? trim( (string) wp_unslash( $_POST['text'] ) ) : '';
		if ( $text === '' ) {
			wp_send_json_error( array( 'msg' => 'متن پیامک خالی است.' ) );
		}
		if ( ! SZC_SMS::enabled() ) {
			wp_send_json_error( array( 'msg' => 'سرویس پیامک فعال نیست.' ) );
		}
		if ( $c->opt_out ) {
			wp_send_json_error( array( 'msg' => 'این مخاطب لغو دریافت پیامک دارد.' ) );
		}
		$text = SZC_Templates::fill( wp_strip_all_tags( $text ), SZC_Contacts::vars( $c ) );
		$res  = SZC_SMS::send_text( $c->mobile, $text );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => $res['msg'] ?? 'ارسال ناموفق بود.' ) );
		}
		SZC_Activity::log( (int) $c->id, 'sms', array( 'outcome' => 'sent', 'body' => $text ) );
		wp_send_json_success( array( 'msg' => 'پیامک دلخواه ارسال شد ✓', 'reload' => true ) );
	}

	public static function ajax_add_followup() {
		self::guard();
		$c    = self::req_contact();
		$at   = isset( $_POST['at'] ) ? sanitize_text_field( wp_unslash( $_POST['at'] ) ) : '';
		$note = isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '';
		$ts   = szc_ts_from_datetime( $at );
		if ( ! $ts ) {
			wp_send_json_error( array( 'msg' => 'زمان پیگیری را مشخص کنید.' ) );
		}
		$mysql = wp_date( 'Y-m-d H:i:s', $ts );
		SZC_Activity::add_followup( (int) $c->id, $mysql, $note );
		wp_send_json_success( array( 'msg' => 'پیگیری ثبت شد.', 'reload' => true ) );
	}

	public static function ajax_done_followup() {
		self::guard();
		SZC_Activity::complete_followup( isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0 );
		wp_send_json_success( array( 'reload' => true ) );
	}

	public static function ajax_send_sms() {
		self::guard();
		$c   = self::req_contact();
		$tid = isset( $_POST['template'] ) ? absint( $_POST['template'] ) : 0;
		$res = SZC_SMS::send_template_now( $c, $tid );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => $res['msg'] ?? 'ارسال ناموفق بود.' ) );
		}
		wp_send_json_success( array( 'msg' => 'پیامک ارسال شد ✓', 'reload' => true ) );
	}

	public static function ajax_schedule_sms() {
		self::guard();
		$c   = self::req_contact();
		$tid = isset( $_POST['template'] ) ? absint( $_POST['template'] ) : 0;
		if ( ! SZC_SMS::enabled() ) {
			wp_send_json_error( array( 'msg' => 'سرویس پیامک فعال نیست.' ) );
		}
		if ( $c->opt_out ) {
			wp_send_json_error( array( 'msg' => 'این مخاطب لغو دریافت پیامک دارد.' ) );
		}
		if ( ! SZC_SMS::schedule_thanks( $c, $tid ) ) {
			wp_send_json_error( array( 'msg' => 'قالبی برای زمان‌بندی یافت نشد.' ) );
		}
		wp_send_json_success( array( 'msg' => 'پیامک زمان‌بندی شد.', 'reload' => true ) );
	}

	public static function ajax_del_contact() {
		self::guard();
		$c = self::req_contact();
		SZC_Contacts::delete( (int) $c->id );
		wp_send_json_success( array( 'msg' => 'مخاطب حذف شد.', 'redirect' => self::url( 'szc-contacts' ) ) );
	}

	/* ==================== فولدرها (گروه‌بندی) ==================== */

	public static function ajax_group_create() {
		self::guard();
		$name   = isset( $_POST['name'] ) ? (string) wp_unslash( $_POST['name'] ) : '';
		$parent = isset( $_POST['parent'] ) ? absint( $_POST['parent'] ) : 0;
		$id     = SZC_Groups::create( $name, $parent );
		if ( ! $id ) {
			wp_send_json_error( array( 'msg' => 'نام پوشه را وارد کنید.' ) );
		}
		wp_send_json_success( array( 'msg' => 'پوشه ساخته شد.', 'id' => $id, 'reload' => true ) );
	}

	public static function ajax_group_rename() {
		self::guard();
		$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$name = isset( $_POST['name'] ) ? (string) wp_unslash( $_POST['name'] ) : '';
		if ( ! SZC_Groups::rename( $id, $name ) ) {
			wp_send_json_error( array( 'msg' => 'تغییر نام ناموفق بود.' ) );
		}
		wp_send_json_success( array( 'msg' => 'نام پوشه تغییر کرد.', 'reload' => true ) );
	}

	public static function ajax_group_delete() {
		self::guard();
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		SZC_Groups::delete( $id );
		wp_send_json_success( array( 'msg' => 'پوشه حذف شد (مخاطبین به پوشه‌ی بالادست منتقل شدند).', 'reload' => true ) );
	}

	/** انتقال یک مخاطب به یک پوشه. */
	public static function ajax_set_group() {
		self::guard();
		$c   = self::req_contact();
		$gid = isset( $_POST['group'] ) ? absint( $_POST['group'] ) : 0;
		SZC_Groups::move_contact( (int) $c->id, $gid );
		wp_send_json_success( array( 'msg' => 'مخاطب به پوشه منتقل شد.', 'reload' => true ) );
	}

	/** اقدام گروهی روی یک پوشه (و زیرپوشه‌ها): تغییر مرحله یا ارسال قالب پیامک. */
	public static function ajax_group_bulk() {
		self::guard();
		$gid = isset( $_POST['group'] ) ? absint( $_POST['group'] ) : 0;
		$op  = isset( $_POST['op'] ) ? sanitize_key( $_POST['op'] ) : '';
		$ids = SZC_Contacts::ids_matching( array( 'group' => $gid ) );
		if ( ! $ids ) {
			wp_send_json_error( array( 'msg' => 'این پوشه مخاطبی ندارد.' ) );
		}
		if ( $op === 'stage' ) {
			$stage = isset( $_POST['value'] ) ? sanitize_key( $_POST['value'] ) : '';
			foreach ( $ids as $id ) {
				SZC_Contacts::set_stage( (int) $id, $stage );
			}
			wp_send_json_success( array( 'msg' => szc_fa_digits( count( $ids ) ) . ' مخاطب به مرحله‌ی «' . SZC_Settings::stage_label( $stage ) . '» رفتند.', 'reload' => true ) );
		} elseif ( $op === 'sms' ) {
			$tid = isset( $_POST['value'] ) ? absint( $_POST['value'] ) : 0;
			if ( ! SZC_SMS::enabled() ) {
				wp_send_json_error( array( 'msg' => 'سرویس پیامک فعال نیست.' ) );
			}
			$n = SZC_SMS::enqueue_template_bulk( $ids, $tid );
			wp_send_json_success( array( 'msg' => 'پیامک برای ' . szc_fa_digits( (int) $n ) . ' مخاطب در صف قرار گرفت.', 'reload' => true ) );
		}
		wp_send_json_error( array( 'msg' => 'اقدام نامعتبر.' ) );
	}

	/**
	 * اقدامِ گروهی روی مخاطبینِ انتخاب‌شده در فهرست (یا کلِ نتایجِ فیلتر):
	 * ارسالِ پیامکِ قالبی، انتقال به پوشه، یا تغییرِ مرحله. برای «ارسالِ همگانی از
	 * خودِ فهرست» و «انتقالِ یک‌جای انتخاب‌شده‌ها به پوشه».
	 */
	public static function ajax_list_bulk() {
		self::guard();
		$op    = isset( $_POST['op'] ) ? sanitize_key( $_POST['op'] ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
		$scope = ( ( $_POST['scope'] ?? '' ) === 'all' ) ? 'all' : 'selected';

		if ( $scope === 'all' ) {
			$args = array(
				'search'   => sanitize_text_field( wp_unslash( $_POST['f_s'] ?? '' ) ),
				'stage'    => sanitize_key( $_POST['f_stage'] ?? '' ),
				'priority' => sanitize_key( $_POST['f_priority'] ?? '' ),
				'due'      => sanitize_key( $_POST['f_due'] ?? '' ),
				'group'    => absint( $_POST['f_group'] ?? 0 ),
			);
			if ( ! SZC_Settings::is_manager() ) {
				$args['owner'] = SZC_Auth::actor_id();
			}
			$ids = SZC_Contacts::ids_matching( $args );
		} else {
			$ids = array_map( 'intval', (array) ( $_POST['ids'] ?? array() ) );
		}

		// کارشناس فقط روی سرنخ‌های خودش (یا بدونِ‌تخصیص) اقدام کند.
		if ( ! SZC_Settings::is_manager() ) {
			$self = SZC_Auth::actor_id();
			$ids  = array_values( array_filter( $ids, function ( $id ) use ( $self ) {
				$c = SZC_Contacts::get( $id );
				return $c && ( (int) $c->owner_id === $self || (int) $c->owner_id === 0 );
			} ) );
		}
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( ! $ids ) {
			wp_send_json_error( array( 'msg' => 'موردی برای اقدام نیست.' ) );
		}

		if ( $op === 'sms' ) {
			if ( ! SZC_SMS::enabled() ) {
				wp_send_json_error( array( 'msg' => 'سرویس پیامک فعال نیست.' ) );
			}
			$tid = absint( $value );
			if ( ! $tid ) {
				wp_send_json_error( array( 'msg' => 'قالب پیامک را انتخاب کنید.' ) );
			}
			$r = SZC_SMS::enqueue_template_bulk( $ids, $tid );
			wp_send_json_success( array( 'msg' => szc_fa_digits( $r['queued'] ) . ' پیامک در صف قرار گرفت (' . szc_fa_digits( $r['skipped'] ) . ' رد شد).', 'reload' => true ) );
		} elseif ( $op === 'move' ) {
			$gid = absint( $value );
			foreach ( $ids as $id ) {
				SZC_Groups::move_contact( (int) $id, $gid );
			}
			wp_send_json_success( array( 'msg' => szc_fa_digits( count( $ids ) ) . ' مخاطب به پوشه منتقل شد.', 'reload' => true ) );
		} elseif ( $op === 'stage' ) {
			$st = sanitize_key( $value );
			foreach ( $ids as $id ) {
				SZC_Contacts::set_stage( (int) $id, $st );
			}
			wp_send_json_success( array( 'msg' => szc_fa_digits( count( $ids ) ) . ' مخاطب تغییر مرحله داد.', 'reload' => true ) );
		}
		wp_send_json_error( array( 'msg' => 'اقدام نامعتبر.' ) );
	}

	/** ادغام مخاطب دیگر (فرعی) در مخاطب فعلی (اصلی). */
	public static function ajax_merge() {
		self::guard();
		$c     = self::req_contact();
		$other = isset( $_POST['other'] ) ? absint( $_POST['other'] ) : 0;
		if ( ! $other ) {
			// جستجو بر اساس موبایل اگر شناسه داده نشده.
			$m = isset( $_POST['other_mobile'] ) ? szc_normalize_mobile( wp_unslash( $_POST['other_mobile'] ) ) : '';
			$o = $m ? SZC_Contacts::get_by_mobile( $m ) : null;
			$other = $o ? (int) $o->id : 0;
		}
		if ( ! $other ) {
			wp_send_json_error( array( 'msg' => 'مخاطب دوم پیدا نشد.' ) );
		}
		$res = SZC_Contacts::merge( (int) $c->id, $other );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => $res['msg'] ?? 'ادغام ناموفق بود.' ) );
		}
		wp_send_json_success( array( 'msg' => $res['msg'], 'reload' => true ) );
	}
}
