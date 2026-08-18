<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** صفحه‌های ایمپورت، قالب‌های پیامک و تنظیمات. */
class SZC_Admin_Pages {

	public static function init() {
		add_action( 'admin_post_szc_import_upload',   array( __CLASS__, 'handle_import_upload' ) );
		add_action( 'admin_post_szc_import_run',      array( __CLASS__, 'handle_import_run' ) );
		add_action( 'admin_post_szc_template_save',   array( __CLASS__, 'handle_template_save' ) );
		add_action( 'admin_post_szc_template_delete', array( __CLASS__, 'handle_template_delete' ) );
		add_action( 'admin_post_szc_settings_save',   array( __CLASS__, 'handle_settings_save' ) );
		add_action( 'admin_post_szc_settings_section_save', array( __CLASS__, 'handle_settings_section_save' ) );
		add_action( 'admin_post_szc_settings_reset_section', array( __CLASS__, 'handle_settings_reset_section' ) );
		add_action( 'admin_post_szc_broadcast',       array( __CLASS__, 'handle_broadcast' ) );
		add_action( 'admin_post_szc_delivery_refresh', array( __CLASS__, 'handle_delivery_refresh' ) );
		add_action( 'admin_post_szc_agent_save',      array( __CLASS__, 'handle_agent_save' ) );
		add_action( 'admin_post_szc_agent_delete',    array( __CLASS__, 'handle_agent_delete' ) );
		add_action( 'admin_post_szc_agent_toggle',    array( __CLASS__, 'handle_agent_toggle' ) );
		add_action( 'admin_post_szc_bulk',            array( __CLASS__, 'handle_bulk' ) );
		add_action( 'admin_post_szc_save_segment',    array( __CLASS__, 'handle_save_segment' ) );
		add_action( 'admin_post_szc_delete_segment',  array( __CLASS__, 'handle_delete_segment' ) );
		add_action( 'admin_post_szc_sequence_save',   array( __CLASS__, 'handle_sequence_save' ) );
		add_action( 'admin_post_szc_sequence_delete', array( __CLASS__, 'handle_sequence_delete' ) );
		add_action( 'admin_post_szc_blacklist_add',   array( __CLASS__, 'handle_blacklist_add' ) );
		add_action( 'admin_post_szc_blacklist_remove', array( __CLASS__, 'handle_blacklist_remove' ) );
		add_action( 'admin_post_szc_export',          array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_szc_activity_export', array( __CLASS__, 'handle_activity_export' ) );
		add_action( 'admin_post_szc_merge_group',     array( __CLASS__, 'handle_merge_group' ) );
		add_action( 'admin_post_szc_pipeline_save',   array( __CLASS__, 'handle_pipeline_save' ) );
		add_action( 'admin_post_szc_goal_summary_send', array( __CLASS__, 'handle_goal_summary_send' ) );
		add_action( 'admin_post_szc_contact_restore', array( __CLASS__, 'handle_contact_restore' ) );
		add_action( 'admin_post_szc_contact_permanent_delete', array( __CLASS__, 'handle_contact_permanent_delete' ) );
		add_action( 'admin_post_szc_agent_revoke_sessions', array( __CLASS__, 'handle_agent_revoke_sessions' ) );
		add_action( 'admin_post_szc_smart_distribute', array( __CLASS__, 'handle_smart_distribute' ) );
		add_action( 'wp_ajax_szc_test_sms',           array( __CLASS__, 'ajax_test_sms' ) );
		add_action( 'wp_ajax_szc_broadcast_count',    array( __CLASS__, 'ajax_broadcast_count' ) );
		add_action( 'wp_ajax_nopriv_szc_broadcast_count', array( __CLASS__, 'ajax_broadcast_count' ) );
	}

	/** آرگومان‌های فیلتر از فیلدهای f_* (با اعمال محدوده‌ی کارشناس). */
	protected static function filter_args_from_post() {
		$a = array(
			'search'   => sanitize_text_field( wp_unslash( $_POST['f_s'] ?? '' ) ),
			'stage'    => sanitize_key( $_POST['f_stage'] ?? '' ),
			'priority' => sanitize_key( $_POST['f_priority'] ?? '' ),
			'due'      => sanitize_key( $_POST['f_due'] ?? '' ),
			'owner'    => absint( $_POST['f_owner'] ?? 0 ),
		);
		if ( ! SZC_Auth::can_see_all() ) {
			$a['owner'] = SZC_Auth::actor_id();
		}
		return $a;
	}

	protected static function guard( $post = false ) {
		if ( ! SZC_Settings::can_access() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
	}

	protected static function url( $page, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	protected static function delim_char( $key ) {
		$map = array( 'comma' => ',', 'semicolon' => ';', 'tab' => "\t" );
		return isset( $map[ $key ] ) ? $map[ $key ] : ',';
	}

	public static function page_trash() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) { wp_die( 'فقط مدیر به سطل زباله دسترسی دارد.' ); }
		$result = SZC_Contacts::query( array( 'trash' => 1, 'per_page' => 200, 'orderby' => 'updated_at' ) );
		?>
		<div class="wrap szc-wrap"><h1>سطل زباله مخاطبان</h1>
			<p class="szc-muted">حذف معمولی قابل بازیابی است. حذف دائمی اطلاعات وابسته را نیز پاک می‌کند.</p>
			<table class="widefat striped"><thead><tr><th>مخاطب</th><th>موبایل</th><th>زمان حذف</th><th>حذف‌کننده</th><th>اقدام</th></tr></thead><tbody>
			<?php if ( empty( $result['items'] ) ) : ?><tr><td colspan="5">سطل زباله خالی است.</td></tr>
			<?php else : foreach ( $result['items'] as $c ) : ?>
				<tr><td><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></td><td dir="ltr"><?php echo esc_html( $c->mobile ); ?></td>
				<td><?php echo esc_html( szc_format_mysql( $c->deleted_at ) ); ?></td><td><?php echo esc_html( SZC_Auth::display_name( $c->deleted_by ) ); ?></td>
				<td><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_contact_restore&id=' . (int) $c->id ), 'szc_contact_restore_' . (int) $c->id ) ); ?>">بازیابی</a>
				<a class="button button-link-delete" onclick="return confirm('برای همیشه حذف شود؟ این کار قابل بازگشت نیست.')" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_contact_permanent_delete&id=' . (int) $c->id ), 'szc_contact_permanent_delete_' . (int) $c->id ) ); ?>">حذف دائمی</a></td></tr>
			<?php endforeach; endif; ?></tbody></table>
		</div><?php
	}

	public static function page_audit() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) { wp_die( 'فقط مدیر به گزارش تغییرات دسترسی دارد.' ); }
		$rows = SZC_Audit::recent( 300 );
		?>
		<div class="wrap szc-wrap"><h1>گزارش تغییرات و امنیت</h1>
			<p class="szc-muted">۳۰۰ رویداد آخر؛ شامل ایجاد، ویرایش، حذف، بازیابی و رویدادهای ورود.</p>
			<table class="widefat striped"><thead><tr><th>زمان</th><th>کاربر</th><th>رویداد</th><th>نوع/شناسه</th><th>شرح</th><th>IP</th></tr></thead><tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="6">رویدادی ثبت نشده است.</td></tr>
			<?php else : foreach ( $rows as $r ) : ?><tr>
				<td><?php echo esc_html( szc_format_mysql( $r->created_at ) ); ?></td><td><?php echo esc_html( SZC_Auth::display_name( $r->actor_id ) ?: 'سیستم/ناشناس' ); ?></td>
				<td><code><?php echo esc_html( $r->action ); ?></code></td><td><?php echo esc_html( $r->object_type . ' #' . $r->object_id ); ?></td>
				<td><?php echo esc_html( $r->summary ); ?></td><td dir="ltr"><?php echo esc_html( $r->ip ); ?></td>
			</tr><?php endforeach; endif; ?></tbody></table>
		</div><?php
	}

	public static function handle_contact_restore() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) { wp_die( 'دسترسی غیرمجاز' ); }
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_contact_restore_' . $id );
		SZC_Contacts::restore( $id );
		wp_safe_redirect( self::url( 'szc-trash' ) ); exit;
	}

	public static function handle_contact_permanent_delete() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) { wp_die( 'دسترسی غیرمجاز' ); }
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_contact_permanent_delete_' . $id );
		SZC_Contacts::permanent_delete( $id );
		wp_safe_redirect( self::url( 'szc-trash' ) ); exit;
	}

	public static function handle_smart_distribute() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) { wp_die( 'دسترسی غیرمجاز' ); }
		check_admin_referer( 'szc_smart_distribute' );
		global $wpdb;
		$ids = $wpdb->get_col( 'SELECT id FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL AND owner_id=0 ORDER BY created_at ASC LIMIT 5000' );
		$res = SZC_Contacts::smart_distribute( $ids );
		set_transient( 'szc_distribution_' . SZC_Auth::actor_id(), $res, 60 );
		wp_safe_redirect( self::url( 'szc-reports' ) ); exit;
	}

	/* ==================== ایمپورت ==================== */

	public static function page_import() {
		self::guard();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$file  = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';
		$delim = isset( $_GET['delim'] ) ? sanitize_key( $_GET['delim'] ) : 'comma';
		// phpcs:enable
		$stats = get_transient( 'szc_import_' . SZC_Auth::actor_id() );
		if ( $stats ) {
			delete_transient( 'szc_import_' . SZC_Auth::actor_id() );
		}
		?>
		<div class="wrap szc-wrap">
			<h1>ایمپورت شماره‌ها</h1>

			<?php if ( $stats ) : ?>
				<div class="notice notice-success"><p>
					ایمپورت انجام شد —
					افزوده: <b><?php echo esc_html( szc_fa_digits( $stats['created'] ) ); ?></b>،
					به‌روزرسانی: <b><?php echo esc_html( szc_fa_digits( $stats['updated'] ) ); ?></b>،
					نامعتبر: <b><?php echo esc_html( szc_fa_digits( $stats['invalid'] ) ); ?></b>،
					کل ردیف: <b><?php echo esc_html( szc_fa_digits( $stats['total'] ) ); ?></b>.
					<?php if ( ! empty( $stats['capped'] ) ) : ?><br>توجه: به سقف <?php echo esc_html( szc_fa_digits( SZC_Import::MAX_ROWS ) ); ?> ردیف رسید؛ بقیه را در فایل جداگانه ایمپورت کنید.<?php endif; ?>
				</p></div>
				<p><a href="<?php echo esc_url( self::url( 'szc-contacts' ) ); ?>" class="button button-primary">مشاهده مخاطبین</a> <a href="<?php echo esc_url( self::url( 'szc-import' ) ); ?>" class="button">ایمپورت فایل دیگر</a></p>
				<?php return; endif; ?>

			<?php
			$preview = $file ? SZC_Import::preview( $file, self::delim_char( $delim ) ) : null;
			if ( $file && ( ! $preview || empty( $preview['ok'] ) ) ) :
				echo '<div class="notice notice-error"><p>' . esc_html( $preview['msg'] ?? 'خطا در خواندن فایل.' ) . '</p></div>';
				$file = '';
			endif;

			if ( ! $file ) : ?>
				<div class="szc-card" style="max-width:640px">
					<h2>گام ۱: بارگذاری فایل CSV</h2>
					<p class="szc-muted">فایل اکسل را با فرمت <b>CSV UTF-8</b> ذخیره کنید. ردیف اول می‌تواند سرستون باشد.</p>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'szc_import_upload' ); ?>
						<input type="hidden" name="action" value="szc_import_upload">
						<p><input type="file" name="csv" accept=".csv,.txt" required></p>
						<p>
							<label>جداکننده:
								<select name="delim">
									<option value="comma">ویرگول (,)</option>
									<option value="semicolon">نقطه‌ویرگول (;)</option>
									<option value="tab">Tab</option>
								</select>
							</label>
						</p>
						<p><button class="button button-primary">بارگذاری و ادامه</button></p>
					</form>
				</div>
			<?php else :
				$header = $preview['header'];
				$sample = $preview['sample'];
				$fields = SZC_Import::fields();
				?>
				<div class="szc-card">
					<h2>گام ۲: نگاشت ستون‌ها</h2>
					<p class="szc-muted">مشخص کنید هر فیلد به کدام ستون فایل مربوط است. شماره‌های نامعتبر و تکراری خودکار حذف/ادغام می‌شوند.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'szc_import_run' ); ?>
						<input type="hidden" name="action" value="szc_import_run">
						<input type="hidden" name="file" value="<?php echo esc_attr( $file ); ?>">
						<input type="hidden" name="delim" value="<?php echo esc_attr( $delim ); ?>">

						<table class="form-table"><tbody>
						<?php foreach ( $fields as $fk => $flabel ) :
							$guess = self::guess_column( $fk, $header ); ?>
							<tr>
								<th><?php echo esc_html( $flabel ); ?></th>
								<td>
									<select name="map[<?php echo esc_attr( $fk ); ?>]">
										<option value="-1">— نادیده بگیر —</option>
										<?php foreach ( $header as $i => $h ) : ?>
											<option value="<?php echo (int) $i; ?>" <?php selected( $guess, $i ); ?>><?php echo esc_html( ( $h !== '' ? $h : 'ستون ' . ( $i + 1 ) ) ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody></table>

						<h3>پیش‌نمایش</h3>
						<div style="overflow:auto"><table class="widefat striped"><thead><tr>
							<?php foreach ( $header as $h ) : ?><th><?php echo esc_html( $h ); ?></th><?php endforeach; ?>
						</tr></thead><tbody>
							<?php foreach ( $sample as $r ) : ?>
								<tr><?php foreach ( $r as $cell ) : ?><td><?php echo esc_html( $cell ); ?></td><?php endforeach; ?></tr>
							<?php endforeach; ?>
						</tbody></table></div>

						<h3>تنظیمات ایمپورت</h3>
						<table class="form-table"><tbody>
							<tr><th>ردیف اول سرستون است؟</th><td><label><input type="checkbox" name="has_header" value="1" checked> بله، ردیف اول را نادیده بگیر</label></td></tr>
							<tr><th>منبع (برای همه)</th><td><input type="text" name="def_source" class="regular-text" placeholder="مثلاً: لیست نمایشگاه"></td></tr>
							<tr><th>برچسب (برای همه)</th><td><input type="text" name="def_tags" class="regular-text" placeholder="مثلاً: سرد-۱۴۰۵"></td></tr>
							<tr><th>اولویت پیش‌فرض</th><td>
								<select name="def_priority">
									<?php foreach ( SZC_Settings::priorities() as $k => $m ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, 'cold' ); ?>><?php echo esc_html( $m['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</td></tr>
							<tr><th>مخاطبین موجود</th><td><label><input type="checkbox" name="update_existing" value="1" checked> فیلدهای خالیِ مخاطبین موجود با داده‌ی جدید پر شود</label></td></tr>
						</tbody></table>

						<p><button class="button button-primary">شروع ایمپورت</button></p>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	protected static function guess_column( $field, $header ) {
		$keys = array(
			'mobile'     => array( 'mobile', 'phone', 'موبایل', 'همراه', 'تلفن', 'شماره' ),
			'first_name' => array( 'first', 'name', 'نام' ),
			'last_name'  => array( 'last', 'family', 'خانوادگی', 'فامیل' ),
			'job'        => array( 'job', 'شغل', 'occupation' ),
			'company'    => array( 'company', 'شرکت', 'سازمان' ),
			'city'       => array( 'city', 'شهر' ),
			'email'      => array( 'email', 'ایمیل', 'mail' ),
			'source'     => array( 'source', 'منبع' ),
			'tags'       => array( 'tag', 'برچسب' ),
		);
		$needles = $keys[ $field ] ?? array();
		foreach ( $header as $i => $h ) {
			$h = strtolower( trim( (string) $h ) ); // byte-level؛ برای فارسی هم درست کار می‌کند
			foreach ( $needles as $n ) {
				if ( $h !== '' && strpos( $h, strtolower( $n ) ) !== false ) {
					return $i;
				}
			}
		}
		return -1;
	}

	public static function handle_import_upload() {
		self::guard();
		check_admin_referer( 'szc_import_upload' );
		$delim = isset( $_POST['delim'] ) ? sanitize_key( $_POST['delim'] ) : 'comma';
		$res   = SZC_Import::store_upload( $_FILES['csv'] ?? array() );
		if ( empty( $res['ok'] ) ) {
			wp_die( esc_html( $res['msg'] ?? 'خطا در بارگذاری.' ) );
		}
		wp_safe_redirect( self::url( 'szc-import', array( 'file' => $res['file'], 'delim' => $delim ) ) );
		exit;
	}

	public static function handle_import_run() {
		self::guard();
		check_admin_referer( 'szc_import_run' );
		$file  = isset( $_POST['file'] ) ? sanitize_file_name( wp_unslash( $_POST['file'] ) ) : '';
		$delim = isset( $_POST['delim'] ) ? sanitize_key( $_POST['delim'] ) : 'comma';
		$map   = isset( $_POST['map'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['map'] ) ) : array();
		$res   = SZC_Import::run( $file, $map, array(
			'has_header'      => ! empty( $_POST['has_header'] ),
			'delimiter'       => self::delim_char( $delim ),
			'source'          => sanitize_text_field( wp_unslash( $_POST['def_source'] ?? '' ) ),
			'tags'            => sanitize_text_field( wp_unslash( $_POST['def_tags'] ?? '' ) ),
			'priority'        => sanitize_key( $_POST['def_priority'] ?? 'cold' ),
			'update_existing' => ! empty( $_POST['update_existing'] ),
		) );
		if ( empty( $res['ok'] ) ) {
			wp_die( esc_html( $res['msg'] ?? 'خطا در ایمپورت.' ) );
		}
		set_transient( 'szc_import_' . SZC_Auth::actor_id(), $res['stats'], 60 );
		wp_safe_redirect( self::url( 'szc-import' ) );
		exit;
	}

	/* ==================== قالب‌های پیامک ==================== */

	public static function page_templates() {
		self::guard();
		$edit = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$row  = $edit ? SZC_Templates::get( $edit ) : null;
		?>
		<div class="wrap szc-wrap">
			<h1>قالب‌های پیامک</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?><div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div><?php endif; ?>

			<div class="szc-single-grid">
				<div class="szc-col">
					<div class="szc-card">
						<h2><?php echo $row ? 'ویرایش قالب' : 'قالب جدید'; ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'szc_template_save' ); ?>
							<input type="hidden" name="action" value="szc_template_save">
							<input type="hidden" name="id" value="<?php echo (int) ( $row->id ?? 0 ); ?>">
							<p><label>نام قالب<br><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $row->name ?? '' ); ?>"></label></p>
							<p><label>متن پیامک<br><textarea name="body" rows="4" class="large-text" required><?php echo esc_textarea( $row->body ?? '' ); ?></textarea></label></p>
							<p class="szc-muted">متغیرها: <code>%first%</code> نام، <code>%last%</code> نام خانوادگی، <code>%name%</code> نام کامل، <code>%company%</code> شرکت، <code>%job%</code> شغل، <code>%city%</code> شهر، <code>%mini%</code> لینک مینی‌دوره، <code>%intro%</code> لینک معارفه.</p>
							<p><label>کد پترن SMS.ir (الزامی)<br><input type="text" name="pattern_code" dir="ltr" class="regular-text" required value="<?php echo esc_attr( $row->pattern_code ?? '' ); ?>"></label>
							<br><span class="szc-muted">همه پیامک‌ها فقط با پترن خدماتی ارسال می‌شوند. این مقدار همان Template ID عددی در SMS.ir است.</span></p>
							<p><button class="button button-primary"><?php echo $row ? 'ذخیره تغییرات' : 'ایجاد قالب'; ?></button>
								<?php if ( $row ) : ?><a class="button" href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">قالب جدید</a><?php endif; ?></p>
						</form>
					</div>
				</div>
				<div class="szc-col">
					<div class="szc-card">
						<h2>قالب‌های موجود</h2>
						<?php $all = SZC_Templates::all(); if ( ! $all ) : ?>
							<p class="szc-muted">هنوز قالبی نساخته‌اید.</p>
						<?php else : ?>
							<ul class="szc-tpl-list">
								<?php foreach ( $all as $t ) : ?>
									<li>
										<div><b><?php echo esc_html( $t->name ); ?></b><?php if ( trim( (string) $t->pattern_code ) === '' ) : ?> <span class="szc-pattern-missing">نیازمند کد پترن</span><?php endif; ?><p class="szc-muted"><?php echo esc_html( wp_trim_words( $t->body, 20 ) ); ?></p></div>
										<div class="szc-tpl-ops">
											<a class="button-link" href="<?php echo esc_url( self::url( 'szc-templates', array( 'edit' => $t->id ) ) ); ?>">ویرایش</a>
											<a class="button-link szc-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_template_delete&id=' . $t->id ), 'szc_template_delete_' . $t->id ) ); ?>" onclick="return confirm('این قالب حذف شود؟')">حذف</a>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function handle_template_save() {
		self::guard();
		check_admin_referer( 'szc_template_save' );
		$id   = absint( $_POST['id'] ?? 0 );
		$name = wp_unslash( $_POST['name'] ?? '' );
		$body = wp_unslash( $_POST['body'] ?? '' );
		$pat  = wp_unslash( $_POST['pattern_code'] ?? '' );
		if ( trim( (string) $pat ) === '' ) {
			wp_die( esc_html__( 'کد پترن برای همه قالب‌های پیامک الزامی است.', 'sazan-crm' ), '', array( 'response' => 400 ) );
		}
		if ( $id ) {
			SZC_Templates::update( $id, $name, $body, $pat );
		} else {
			SZC_Templates::create( $name, $body, $pat );
		}
		if ( isset( $_POST['return_to'] ) && sanitize_key( wp_unslash( $_POST['return_to'] ) ) === 'settings_sms' ) {
			self::settings_center_redirect( 'sms', $id ? 'پترن پیامک به‌روزرسانی شد.' : 'پترن پیامک ساخته شد.' );
		}
		wp_safe_redirect( self::url( 'szc-templates', array( 'msg' => 1 ) ) );
		exit;
	}

	public static function handle_template_delete() {
		self::guard();
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_template_delete_' . $id );
		SZC_Templates::delete( $id );
		wp_safe_redirect( self::url( 'szc-templates' ) );
		exit;
	}

	/* ==================== تنظیمات ==================== */

	public static function page_settings_center() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'فقط مدیر CRM به مرکز تنظیمات دسترسی دارد.' );
		}
		global $wpdb;
		$s         = SZC_Settings::all();
		$templates = SZC_Templates::all_patterned();
		$staff     = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'shop_manager', 'contributor' ), 'number' => 500, 'fields' => array( 'ID', 'display_name', 'user_login' ) ) );
		$managers  = SZC_Settings::manager_ids();
		$agents    = SZC_Agents::all();
		$active_agents = count( array_filter( $agents, function ( $agent ) { return (int) $agent->active === 1; } ) );
		$sms_ok    = SZC_SMS::enabled();
		$cron_ok   = (bool) wp_next_scheduled( SZC_SMS::HOOK_SWEEP );
		$contacts_total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL' );
		$queue_pending = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . SZC_SMS::queue_table() . " WHERE status IN ('pending','processing')" );
		$db_version = (string) get_option( 'szc_db_version', '—' );
		$otp_domain = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$setup = array(
			array( $active_agents > 0, 'حداقل یک کارشناس فعال تعریف شده است.', self::url( 'szc-agents' ) ),
			array( $sms_ok, 'پنل SMS.ir فعال و دارای کلید API است.', '#sms' ),
			array( count( SZC_Settings::stages() ) > 1, 'مراحل فرایند فروش آماده است.', self::url( 'szc-pipeline' ) ),
			array( ! empty( $s['pass_login'] ), 'ورود کارشناسان به پرتال فعال است.', '#access' ),
		);
		$setup_done = count( array_filter( $setup, function ( $item ) { return ! empty( $item[0] ); } ) );
		$nav = array(
			'overview'      => array( 'نمای کلی و راه‌اندازی', 'home', 'وضعیت کلی CRM و کارهای باقی‌مانده' ),
			'access'        => array( 'کاربران و دسترسی‌ها', 'users', 'مدیران، ورود و مجوز کارشناسان' ),
			'sales'         => array( 'فرایند فروش', 'target', 'قیف، اولویت‌ها، نتایج تماس و فیلدها' ),
			'conversation'  => array( 'اسکریپت مکالمه', 'phone', 'راهنمای تماس، کشف نیاز و پاسخ اعتراض' ),
			'assignment'    => array( 'تخصیص مخاطبان', 'shuffle', 'مالکیت، ظرفیت و توزیع هوشمند' ),
			'sms'           => array( 'پیامک و پترن‌ها', 'mail', 'اتصال پنل، همه پترن‌ها، نمونه‌ها و محدودیت‌ها' ),
			'automation'    => array( 'اتوماسیون‌ها', 'sparkles', 'قوانین پیامک پس از تماس' ),
			'notifications' => array( 'پیگیری و اعلان‌ها', 'bell', 'گزارش هدف و یادآوری کارشناسان' ),
			'data'          => array( 'داده‌ها و نگهداری', 'history', 'ورودی، خروجی، بازیابی و گزارش تغییرات' ),
			'advanced'      => array( 'تنظیمات پیشرفته', 'settings', 'سلامت سیستم، Cron و ابزارهای تخصصی' ),
		);
		$flash = get_transient( 'szc_settings_center_' . SZC_Auth::actor_id() );
		if ( $flash ) {
			delete_transient( 'szc_settings_center_' . SZC_Auth::actor_id() );
		}
		?>
		<div class="wrap szc-wrap szc-settings-center" id="szc-settings-main">
			<a class="szc-skip-link" href="#szc-settings-content">رفتن به محتوای تنظیمات</a>
			<header class="szc-settings-center__hero">
				<div>
					<p class="szc-eyebrow">مدیریت و پیکربندی</p>
					<h1>مرکز تنظیمات سازان CRM</h1>
					<p>هر بخش فقط تنظیمات مرتبط با همان موضوع را نشان می‌دهد. توضیح زیر هر گزینه می‌گوید تغییر آن چه اثری روی تیم فروش دارد.</p>
				</div>
				<div class="szc-health-summary" aria-label="وضعیت سریع سیستم">
					<span class="szc-status <?php echo $sms_ok ? 'is-success' : 'is-warning'; ?>"><?php echo szc_icon( $sms_ok ? 'check-circle' : 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> پیامک: <?php echo $sms_ok ? 'آماده' : 'نیازمند تنظیم'; ?></span>
					<span class="szc-status <?php echo $active_agents ? 'is-success' : 'is-warning'; ?>"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( szc_fa_digits( $active_agents ) ); ?> کارشناس فعال</span>
				</div>
			</header>

			<?php if ( $flash ) : ?>
				<div class="notice notice-<?php echo esc_attr( $flash['type'] ?? 'success' ); ?> is-dismissible" role="status"><p><?php echo esc_html( $flash['message'] ?? 'انجام شد.' ); ?></p></div>
			<?php endif; ?>

			<div class="szc-settings-search">
				<?php echo szc_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<label for="szc-settings-search">دنبال چه تنظیمی هستید؟</label>
				<input id="szc-settings-search" type="search" data-settings-search placeholder="مثلاً دسترسی حذف، خط ارسال، یادآوری یا قیف فروش…" autocomplete="off">
				<button type="button" class="button" data-settings-search-clear hidden>پاک‌کردن</button>
				<p class="szc-settings-search__empty" data-settings-search-empty hidden role="status">تنظیمی با این عبارت پیدا نشد. عبارت کوتاه‌تری امتحان کنید.</p>
			</div>

			<div class="szc-settings-layout">
				<nav class="szc-settings-nav" aria-label="بخش‌های مرکز تنظیمات">
					<?php foreach ( $nav as $key => $item ) : ?>
						<button type="button" class="szc-settings-nav__item" data-settings-nav="<?php echo esc_attr( $key ); ?>" data-settings-item="<?php echo esc_attr( $item[0] . ' ' . $item[2] ); ?>">
							<?php echo szc_icon( $item[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><b><?php echo esc_html( $item[0] ); ?></b><small><?php echo esc_html( $item[2] ); ?></small></span>
						</button>
					<?php endforeach; ?>
				</nav>

				<main class="szc-settings-content" id="szc-settings-content" tabindex="-1">
					<section class="szc-settings-pane" data-settings-pane="overview" aria-labelledby="szc-title-overview">
						<div class="szc-pane-heading"><div><h2 id="szc-title-overview">نمای کلی و راه‌اندازی</h2><p>در یک نگاه ببینید CRM برای استفاده روزمره آماده است یا کدام بخش نیاز به تکمیل دارد.</p></div><span class="szc-progress-label"><?php echo esc_html( szc_fa_digits( $setup_done ) ); ?> از <?php echo esc_html( szc_fa_digits( count( $setup ) ) ); ?> مورد آماده</span></div>
						<div class="szc-setup-progress" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo (int) count( $setup ); ?>" aria-valuenow="<?php echo (int) $setup_done; ?>"><span style="width:<?php echo (int) round( $setup_done / count( $setup ) * 100 ); ?>%"></span></div>
						<div class="szc-overview-grid">
							<article class="szc-overview-card"><span>مخاطبان فعال</span><strong><?php echo esc_html( szc_fa_digits( $contacts_total ) ); ?></strong><a href="<?php echo esc_url( self::url( 'szc-contacts' ) ); ?>">مشاهده مخاطبان</a></article>
							<article class="szc-overview-card"><span>کارشناسان فعال</span><strong><?php echo esc_html( szc_fa_digits( $active_agents ) ); ?></strong><a href="<?php echo esc_url( self::url( 'szc-agents' ) ); ?>">مدیریت تیم</a></article>
							<article class="szc-overview-card"><span>پیامک در صف</span><strong><?php echo esc_html( szc_fa_digits( $queue_pending ) ); ?></strong><a href="<?php echo esc_url( self::url( 'szc-delivery' ) ); ?>">گزارش پیامک</a></article>
							<article class="szc-overview-card"><span>سلامت زمان‌بندی</span><strong class="<?php echo $cron_ok ? 'is-good' : 'is-bad'; ?>"><?php echo $cron_ok ? 'فعال' : 'نیازمند بررسی'; ?></strong><button type="button" data-settings-jump="advanced">جزئیات فنی</button></article>
						</div>
						<div class="szc-scard">
							<div class="szc-scard-h"><h3>چک‌لیست شروع به کار</h3><p>موارد ناقص مانع ورود اطلاعات نیستند، اما تکمیل آن‌ها تجربه تیم فروش را بهتر می‌کند.</p></div>
							<ul class="szc-checklist">
								<?php foreach ( $setup as $item ) : ?><li class="<?php echo $item[0] ? 'is-done' : ''; ?>"><?php echo szc_icon( $item[0] ? 'check-circle' : 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $item[1] ); ?></span><a href="<?php echo esc_url( $item[2] ); ?>"><?php echo $item[0] ? 'بررسی' : 'تکمیل'; ?></a></li><?php endforeach; ?>
							</ul>
						</div>
					</section>

					<section class="szc-settings-pane" data-settings-pane="access" aria-labelledby="szc-title-access" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-access">کاربران، نقش‌ها و دسترسی‌ها</h2><p>مشخص کنید چه کسانی مدیر CRM هستند و کارشناسان به کدام عملیات دسترسی دارند.</p></div><a class="button" href="<?php echo esc_url( self::url( 'szc-agents' ) ); ?>">مدیریت کارشناسان</a></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-section-form" data-settings-form>
							<?php wp_nonce_field( 'szc_settings_section_save_access' ); ?><input type="hidden" name="action" value="szc_settings_section_save"><input type="hidden" name="section" value="access">
							<div class="szc-scard" data-settings-item="مدیران نقش دسترسی ورود پرتال مجوز کارشناسان حذف ویرایش پیامک خروجی">
								<div class="szc-scard-h"><h3>مدیران و ورود کارشناسان</h3><p>مدیران سایت همیشه دسترسی کامل دارند. مدیر CRM نیز همه مخاطبان و گزارش‌ها را می‌بیند.</p></div>
								<div class="szc-field"><label for="szc-managers">مدیران CRM</label><div class="szc-field-c"><select id="szc-managers" name="managers[]" multiple size="6" class="szc-multi"><?php foreach ( $staff as $u ) : ?><option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $managers, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name . ' (' . $u->user_login . ')' ); ?></option><?php endforeach; ?></select><p class="szc-hint">برای انتخاب چند نفر، کلید Ctrl در ویندوز یا Cmd در مک را نگه دارید.</p></div></div>
								<div class="szc-field szc-field--toggle"><label for="szc-pass-login">فعال‌سازی ورود کارشناسان</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-pass-login" type="checkbox" name="pass_login" value="1" <?php checked( ! empty( $s['pass_login'] ) ); ?>><span></span></label><p class="szc-hint">هر کارشناس می‌تواند با شماره موبایل و کد یک‌بارمصرف یا رمز ثابت وارد شود.</p></div></div>
								<div class="szc-grid2" data-settings-item="کد یکبار مصرف OTP اعتبار کد ارسال مجدد تلاش ورود">
									<div class="szc-field szc-field--col"><label for="szc-otp-expiry">مدت اعتبار کد</label><div class="szc-field-c"><select id="szc-otp-expiry" name="otp_expiry"><option value="60" <?php selected( (int) $s['otp_expiry'], 60 ); ?>>۱ دقیقه</option><option value="120" <?php selected( (int) $s['otp_expiry'], 120 ); ?>>۲ دقیقه ـ پیشنهادی</option><option value="180" <?php selected( (int) $s['otp_expiry'], 180 ); ?>>۳ دقیقه</option><option value="300" <?php selected( (int) $s['otp_expiry'], 300 ); ?>>۵ دقیقه</option></select><span class="szc-hint">بعد از این زمان، کد خودکار منقضی می‌شود.</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-otp-resend">فاصله ارسال مجدد</label><div class="szc-field-c"><select id="szc-otp-resend" name="otp_resend"><option value="30" <?php selected( (int) $s['otp_resend'], 30 ); ?>>۳۰ ثانیه</option><option value="60" <?php selected( (int) $s['otp_resend'], 60 ); ?>>۶۰ ثانیه ـ پیشنهادی</option><option value="90" <?php selected( (int) $s['otp_resend'], 90 ); ?>>۹۰ ثانیه</option><option value="120" <?php selected( (int) $s['otp_resend'], 120 ); ?>>۲ دقیقه</option></select><span class="szc-hint">از ارسال چند پیام پشت سرهم جلوگیری می‌کند.</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-otp-attempts">حداکثر تلاش هر کد</label><div class="szc-field-c"><input id="szc-otp-attempts" type="number" name="otp_max_attempts" min="3" max="10" value="<?php echo esc_attr( $s['otp_max_attempts'] ); ?>" inputmode="numeric"><span class="szc-hint">پیشنهاد امنیتی: ۵ تلاش.</span></div></div>
								</div>
								<div class="szc-callout"><b>پترن ورود کجاست؟</b> همه کدهای پترن، پارامترها و نمونه متن‌ها به بخش یکپارچه «پیامک و پترن‌ها» منتقل شده‌اند. <button type="button" class="button-link" data-settings-jump="sms">رفتن به تنظیمات پترن ورود</button></div>
							</div>
							<fieldset class="szc-scard" data-settings-item="مجوز مشاهده ویرایش حذف یادداشت تماس پیگیری پیامک لیست سیاه پوشه ادغام دنباله خروجی"><legend class="screen-reader-text">مجوزهای پیش‌فرض کارشناسان</legend>
								<div class="szc-scard-h"><h3>مجوزهای پیش‌فرض کارشناسان</h3><p>این موارد برای همه کارشناسان اعمال می‌شود؛ در صفحه هر کارشناس می‌توانید استثنا تعریف کنید.</p></div>
								<div class="szc-permission-grid"><?php $agent_perms = SZC_Settings::agent_permissions(); foreach ( SZC_Settings::permission_labels() as $pk => $plabel ) : ?><label><input type="checkbox" name="agent_permissions[<?php echo esc_attr( $pk ); ?>]" value="1" <?php checked( ! empty( $agent_perms[ $pk ] ) ); ?>><span><?php echo esc_html( $plabel ); ?></span></label><?php endforeach; ?></div>
								<p class="szc-field-note">پیشنهاد امنیتی: حذف، خروجی، مدیریت پوشه و ارسال گروهی را فقط برای افراد موردنیاز فعال کنید.</p>
							</fieldset>
							<div class="szc-section-save"><span data-unsaved-label>همه تغییرات این بخش ذخیره شده‌اند.</span><button class="button button-primary" type="submit"><?php echo szc_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ذخیره کاربران و دسترسی‌ها</button></div>
						</form>
					</section>

					<section class="szc-settings-pane" data-settings-pane="sales" aria-labelledby="szc-title-sales" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-sales">فرایند فروش</h2><p>ساختار قیف و اطلاعاتی را که کارشناسان هنگام کار با مخاطب می‌بینند مدیریت کنید.</p></div></div>
						<div class="szc-tool-grid">
							<a class="szc-tool-card" data-settings-item="مراحل قیف فروش اولویت‌ها فیلد سفارشی" href="<?php echo esc_url( self::url( 'szc-pipeline' ) ); ?>"><?php echo szc_icon( 'target' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>مراحل قیف، اولویت‌ها و فیلدها</b><small>ترتیب مراحل فروش، رنگ اولویت‌ها و اطلاعات سفارشی مخاطب</small></span><i>بازکردن</i></a>
							<button type="button" class="szc-tool-card" data-settings-item="قالب پیامک متن متغیر" data-settings-jump="sms"><?php echo szc_icon( 'file-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>قالب‌های پیامک</b><small>همه پترن‌ها، نمونه متن‌ها و متغیرها در بخش یکپارچه پیامک</small></span><i>بازکردن</i></button>
							<a class="szc-tool-card" data-settings-item="بخش بندی فیلتر گروه مخاطب" href="<?php echo esc_url( self::url( 'szc-segments' ) ); ?>"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>بخش‌بندی مخاطبان</b><small>فیلترهای ذخیره‌شده برای کمپین‌ها و عملیات گروهی</small></span><i>بازکردن</i></a>
						</div>
						<div class="szc-callout"><b>قاعده روشن:</b> تغییر عنوان یک مرحله، مخاطبان آن مرحله را حذف نمی‌کند. حذف مرحله‌ای که مخاطب دارد نیز مجاز نیست.</div>
					</section>

					<section class="szc-settings-pane" data-settings-pane="conversation" aria-labelledby="szc-title-conversation" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-conversation">اسکریپت مکالمه هوشمند</h2><p>چارچوب مکالمه را یک‌بار تعریف کنید؛ دستیار آن را با مرحله فروش و سابقه هر مخاطب هماهنگ می‌کند.</p></div><span class="szc-badge-recommended">بدون ارسال داده به بیرون</span></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-section-form" data-settings-form>
							<?php wp_nonce_field( 'szc_settings_section_save_conversation' ); ?><input type="hidden" name="action" value="szc_settings_section_save"><input type="hidden" name="section" value="conversation">
							<div class="szc-scard" data-settings-item="فعال اسکریپت مکالمه هوشمند شروع تماس متغیر نام شرکت مرحله منبع">
								<div class="szc-scard-h"><h3>شروع و چارچوب تماس</h3><p>کارشناس این راهنما را در پرونده مخاطب و تماس پشت‌سرهم می‌بیند. متن باید طبیعی و کوتاه باشد.</p></div>
								<div class="szc-field szc-field--toggle"><label for="szc-conversation-enabled">نمایش دستیار مکالمه</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-conversation-enabled" type="checkbox" name="conversation_enabled" value="1" <?php checked( ! empty( $s['conversation_enabled'] ) ); ?>><span></span></label><p class="szc-hint">با خاموش‌کردن، اطلاعات ذخیره می‌ماند ولی دستیار برای کارشناسان نمایش داده نمی‌شود.</p></div></div>
								<div class="szc-field szc-field--col"><label for="szc-conversation-opening">شروع پیشنهادی مکالمه</label><div class="szc-field-c"><textarea id="szc-conversation-opening" name="conversation_opening" rows="3"><?php echo esc_textarea( $s['conversation_opening'] ); ?></textarea><span class="szc-hint">متغیرهای قابل استفاده: <code>%first%</code> نام، <code>%last%</code> نام خانوادگی، <code>%name%</code> نام کامل، <code>%company%</code> شرکت، <code>%stage%</code> مرحله و <code>%source%</code> منبع.</span></div></div>
							</div>

							<div class="szc-grid2 szc-conversation-config-grid">
								<div class="szc-scard" data-settings-item="سوال کشف نیاز نیازسنجی معیار تصمیم فوریت"><div class="szc-scard-h"><h3>سؤال‌های کشف نیاز</h3><p>هر سؤال را در یک خط بنویسید؛ کنار هر مورد برای کارشناس چک‌باکس نمایش داده می‌شود.</p></div><div class="szc-field szc-field--col"><label for="szc-conversation-questions">پرسش‌های پیشنهادی</label><div class="szc-field-c"><textarea id="szc-conversation-questions" name="conversation_questions" rows="9"><?php echo esc_textarea( $s['conversation_questions'] ); ?></textarea></div></div></div>
								<div class="szc-scard" data-settings-item="ارزش پیشنهادی مزیت نکات فروش"><div class="szc-scard-h"><h3>نکات ارزش پیشنهادی</h3><p>به‌جای متن تبلیغاتی، اصولی بنویسید که کارشناس را به پاسخ مرتبط با نیاز هدایت کند.</p></div><div class="szc-field szc-field--col"><label for="szc-conversation-values">نکات پیشنهادی</label><div class="szc-field-c"><textarea id="szc-conversation-values" name="conversation_value_points" rows="9"><?php echo esc_textarea( $s['conversation_value_points'] ); ?></textarea></div></div></div>
								<div class="szc-scard" data-settings-item="جمع بندی بستن فروش قدم بعدی"><div class="szc-scard-h"><h3>جمع‌بندی و قدم بعدی</h3><p>جمله‌هایی که تماس را به یک اقدام مشخص و زمان‌دار می‌رسانند.</p></div><div class="szc-field szc-field--col"><label for="szc-conversation-closings">جمله‌های پایانی</label><div class="szc-field-c"><textarea id="szc-conversation-closings" name="conversation_closings" rows="7"><?php echo esc_textarea( $s['conversation_closings'] ); ?></textarea></div></div></div>
								<div class="szc-scard" data-settings-item="خط قرمز نباید ممنوع قول تخفیف رقیب"><div class="szc-scard-h"><h3>خط قرمزهای مکالمه</h3><p>رفتارها یا قول‌هایی که کارشناس باید از آن‌ها دوری کند.</p></div><div class="szc-field szc-field--col"><label for="szc-conversation-guardrails">قواعد کنترل کیفیت</label><div class="szc-field-c"><textarea id="szc-conversation-guardrails" name="conversation_guardrails" rows="7"><?php echo esc_textarea( $s['conversation_guardrails'] ); ?></textarea></div></div></div>
							</div>

							<div class="szc-scard" data-settings-item="هدف مکالمه مرحله قیف لید پاسخ اطلاعات حضور ثبت نام بی علاقه">
								<div class="szc-scard-h"><h3>هدف مکالمه در هر مرحله فروش</h3><p>دستیار بر اساس مرحله فعلی مخاطب، هدف درست را بالای اسکریپت برجسته می‌کند.</p></div>
								<div class="szc-stage-goals">
									<?php $stage_goals = (array) $s['conversation_stage_goals']; foreach ( SZC_Settings::stages() as $stage_key => $stage_label ) : ?>
										<label><span><?php echo esc_html( $stage_label ); ?></span><textarea name="conversation_stage_goals[<?php echo esc_attr( $stage_key ); ?>]" rows="2" placeholder="هدف این مکالمه…"><?php echo esc_textarea( $stage_goals[ $stage_key ] ?? '' ); ?></textarea></label>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="szc-scard" data-settings-item="اعتراض پاسخ قیمت گران فکر کنم بعدا رقیب">
								<div class="szc-scard-h"><h3>کتابخانه پاسخ به اعتراض‌ها</h3><p>عبارت‌های نشانه به کارشناس کمک می‌کنند سریع اعتراض مناسب را باز کند. پاسخ باید همدلانه باشد و با یک سؤال باز ادامه پیدا کند.</p></div>
								<div class="szc-objection-editor">
									<?php
									$objections = array_values( (array) $s['conversation_objections'] );
									while ( count( $objections ) < 6 ) { $objections[] = array(); }
									foreach ( $objections as $oi => $objection ) :
									?>
										<fieldset>
											<legend>اعتراض <?php echo esc_html( szc_fa_digits( $oi + 1 ) ); ?></legend>
											<div class="szc-grid2">
												<label>عنوان<input type="text" name="conversation_objections[<?php echo (int) $oi; ?>][title]" value="<?php echo esc_attr( $objection['title'] ?? '' ); ?>" placeholder="مثلاً قیمت بالاست"></label>
												<label>عبارت‌های نشانه<input type="text" name="conversation_objections[<?php echo (int) $oi; ?>][signals]" value="<?php echo esc_attr( $objection['signals'] ?? '' ); ?>" placeholder="گران، بودجه، هزینه"></label>
											</div>
											<label>پاسخ پیشنهادی<textarea name="conversation_objections[<?php echo (int) $oi; ?>][response]" rows="2"><?php echo esc_textarea( $objection['response'] ?? '' ); ?></textarea></label>
											<label>سؤال بعدی<textarea name="conversation_objections[<?php echo (int) $oi; ?>][question]" rows="2"><?php echo esc_textarea( $objection['question'] ?? '' ); ?></textarea></label>
										</fieldset>
									<?php endforeach; ?>
								</div>
							</div>
							<div class="szc-callout"><b>پیشنهاد آموزشی:</b> از کارشناسان بخواهید متن را حفظ نکنند؛ هدف، سؤال‌ها و منطق پاسخ را یاد بگیرند تا مکالمه طبیعی بماند.</div>
							<div class="szc-section-save"><span data-unsaved-label>همه تغییرات این بخش ذخیره شده‌اند.</span><button class="button button-primary" type="submit"><?php echo szc_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ذخیره اسکریپت مکالمه</button></div>
						</form>
					</section>

					<section class="szc-settings-pane" data-settings-pane="assignment" aria-labelledby="szc-title-assignment" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-assignment">تخصیص مخاطبان</h2><p>نحوه مشاهده لیدها و توزیع بار کاری بین کارشناسان را تعیین کنید.</p></div><a class="button" href="<?php echo esc_url( self::url( 'szc-agents' ) ); ?>">ظرفیت کارشناسان</a></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-section-form" data-settings-form>
							<?php wp_nonce_field( 'szc_settings_section_save_assignment' ); ?><input type="hidden" name="action" value="szc_settings_section_save"><input type="hidden" name="section" value="assignment">
							<div class="szc-scard" data-settings-item="استخر مشترک مالکیت اختصاصی Claim لید توزیع هوشمند Round robin ظرفیت">
								<div class="szc-scard-h"><h3>مدل مالکیت مخاطب</h3><p>انتخاب شما مشخص می‌کند کارشناس فقط لیدهای خودش را ببیند یا کل استخر تیم را.</p></div>
								<div class="szc-choice-cards">
									<label><input type="radio" name="shared_pool" value="1" <?php checked( ! empty( $s['shared_pool'] ) ); ?>><span><b>استخر مشترک</b><small>همه کارشناسان همه لیدها را می‌بینند. مناسب تیم‌های کوچک و همکاری آزاد.</small></span></label>
									<label><input type="radio" name="shared_pool" value="0" <?php checked( empty( $s['shared_pool'] ) ); ?>><span><b>مالکیت اختصاصی ـ پیشنهادی</b><small>هر کارشناس فقط لیدهای تخصیص‌یافته به خودش را می‌بیند. کنترل و گزارش‌گیری دقیق‌تر است.</small></span></label>
								</div>
							</div>
							<div class="szc-scard">
								<div class="szc-scard-h"><h3>توزیع هوشمند و Claim</h3><p>توزیع هوشمند بر اساس ظرفیت، وزن و تعداد لید فعال هر کارشناس انجام می‌شود.</p></div>
								<div class="szc-info-steps"><span><b>۱</b> ظرفیت و وزن هر کارشناس را در «تیم فروش» تعیین کنید.</span><span><b>۲</b> در گزارش مدیریتی، لیدهای بدون مسئول را هوشمند توزیع کنید.</span><span><b>۳</b> کارشناس نیز در صورت داشتن مجوز می‌تواند لید آزاد را Claim کند.</span></div>
							</div>
							<div class="szc-section-save"><span data-unsaved-label>همه تغییرات این بخش ذخیره شده‌اند.</span><button class="button button-primary" type="submit"><?php echo szc_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ذخیره مدل تخصیص</button></div>
						</form>
					</section>

					<section class="szc-settings-pane" data-settings-pane="sms" aria-labelledby="szc-title-sms" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-sms">پیامک و پترن‌ها</h2><p>اتصال SMS.ir، همه کدهای پترن، پارامترها، نمونه متن‌ها و محدودیت ارسال را یک‌جا مدیریت کنید.</p></div><span class="szc-status <?php echo $sms_ok ? 'is-success' : 'is-warning'; ?>"><?php echo $sms_ok ? 'اتصال آماده است' : 'تنظیمات ناقص است'; ?></span></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-section-form" data-settings-form id="szc-sms-settings-form">
							<?php wp_nonce_field( 'szc_settings_section_save_sms' ); ?><input type="hidden" name="action" value="szc_settings_section_save"><input type="hidden" name="section" value="sms">
							<div class="szc-scard szc-sms-connect" data-settings-item="پنل پیامک sms.ir کلید API خط ارسال سرویس دهنده">
								<input type="hidden" name="sms_provider" value="smsir"><input type="hidden" name="sms_mode" value="mixed">
								<div class="szc-scard-h"><div><h3>اتصال SMS.ir</h3><p>کلید اتصال و خط ارسال را وارد کنید؛ متن آزاد از همین خط فرستاده می‌شود.</p></div><div class="szc-scard-tags"><span><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> SMS.ir</span><span>پترن + متن آزاد</span></div></div>
								<div class="szc-sms-enable"><div><b>ارسال پیامک</b><small>با خاموش‌کردن، همه ارسال‌های دستی و خودکار متوقف می‌شوند.</small></div><label class="szc-switch"><input id="szc-sms-enabled" type="checkbox" name="sms_enabled" value="1" <?php checked( ! empty( $s['sms_enabled'] ) ); ?> aria-label="فعال‌سازی پیامک"><span></span></label></div>
								<div class="szc-sms-compact-grid">
									<label><span>کلید API</span><div class="szc-secret-field"><input id="szc-sms-key" type="password" name="sms_apikey" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" dir="ltr" autocomplete="off"><button type="button" class="button" data-toggle-secret aria-controls="szc-sms-key">نمایش</button></div><small>محرمانه؛ فقط مدیران به آن دسترسی دارند.</small></label>
									<label><span>شماره خط ارسال‌کننده</span><input id="szc-sms-originator" type="text" name="sms_originator" value="<?php echo esc_attr( $s['sms_originator'] ); ?>" dir="ltr" inputmode="numeric"><small>برای متن آزاد الزامی است؛ پترن خدماتی بدون آن هم کار می‌کند.</small></label>
								</div>
								<details class="szc-inline-disclosure"><summary>تنظیمات اتصال پیشرفته</summary><label>آدرس پایه API <i>اختیاری</i><input id="szc-sms-base" type="url" name="sms_base" value="<?php echo esc_attr( $s['sms_base'] ); ?>" dir="ltr"><small>فقط با اعلام سرویس‌دهنده تغییر دهید.</small></label></details>
							</div>

							<div class="szc-pattern-pair">
								<div class="szc-scard szc-system-pattern" data-settings-item="پترن اعلان سیستمی گزارش هدف هشدار مدیر یادآوری نمونه متن message">
									<div class="szc-scard-h"><h3>اعلان‌های سیستمی</h3><p>گزارش هدف، هشدار مدیر و یادآوری پیگیری</p></div>
									<div class="szc-pattern-fields"><label>Template ID <b class="szc-required">الزامی</b><input id="szc-system-pattern" type="text" name="system_pattern_code" value="<?php echo esc_attr( $s['system_pattern_code'] ?? '' ); ?>" dir="ltr" required></label><label>نام پارامتر<input id="szc-system-param" type="text" name="system_pattern_param" value="<?php echo esc_attr( $s['system_pattern_param'] ?? 'message' ); ?>" dir="ltr" placeholder="message"></label></div>
									<div class="szc-inline-example"><b>نمونه:</b> اعلان سازان CRM: <code>#message#</code><small>مقدار نمونه: علی رضایی به ۸۰٪ هدف رسید.</small></div>
									<div class="szc-testbox"><input type="tel" id="szc-test-num" dir="ltr" inputmode="numeric" aria-label="شماره دریافت‌کننده تست" placeholder="شماره تست 0912..."><button type="button" class="button" id="szc-test-sms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szc_admin' ) ); ?>">ارسال تست</button><span id="szc-test-msg" role="status" aria-live="polite"></span></div>
								</div>
								<div class="szc-scard szc-otp-pattern" data-settings-item="پترن ورود OTP کد یکبار مصرف نمونه متن پارامتر code">
									<div class="szc-scard-h"><h3>ورود کارشناسان (OTP)</h3><p>کد یک‌بارمصرف ورود به پرتال</p></div>
									<div class="szc-pattern-fields"><label>Template ID <?php if ( ! empty( $s['pass_login'] ) ) : ?><b class="szc-required">الزامی</b><?php endif; ?><input id="szc-otp-pattern" type="text" name="otp_pattern_code" value="<?php echo esc_attr( $s['otp_pattern_code'] ); ?>" dir="ltr" <?php echo ! empty( $s['pass_login'] ) ? 'required' : ''; ?>></label><label>نام پارامتر<input id="szc-otp-param" type="text" name="otp_pattern_param" value="<?php echo esc_attr( $s['otp_pattern_param'] ); ?>" dir="ltr" placeholder="code"></label></div>
									<div class="szc-inline-example"><b>نمونه:</b> کد ورود: <code>#code#</code><small dir="ltr">@<?php echo esc_html( $otp_domain ); ?> #code#</small></div>
								</div>
							</div>
							<div class="szc-scard" data-settings-item="قالب های پیامک پترن مشتری نمونه متن متغیر نام لینک">
								<div class="szc-scard-h"><div><h3>پترن‌های پیامک مشتریان</h3><p>قالب‌های دستی، گروهی و اتوماسیون تماس در این فهرست قرار دارند.</p></div><a class="button button-secondary" href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">مدیریت قالب‌ها</a></div>
								<?php if ( ! $templates ) : ?>
									<div class="szc-pattern-empty"><p>هنوز پترن مشتری نساخته‌اید.</p><p class="szc-muted">نمونه: «سلام <code>%first%</code>، از گفت‌وگوی امروز خوشحال شدیم. اطلاعات بیشتر: <code>%mini%</code>»</p><a class="button" href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">ساخت اولین پترن</a></div>
								<?php else : ?>
									<div class="szc-pattern-list">
										<?php foreach ( $templates as $template ) : ?><article><header><b><?php echo esc_html( $template->name ); ?></b><code dir="ltr">ID: <?php echo esc_html( $template->pattern_code ); ?></code></header><p><?php echo nl2br( esc_html( $template->body ) ); ?></p><small>نمونه متغیرها: <code>%first%</code> ← سارا، <code>%name%</code> ← سارا محمدی، <code>%mini%</code> و <code>%intro%</code> ← لینک تنظیم‌شده</small></article><?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<details class="szc-scard szc-disclosure" data-settings-item="بازه مجاز ساعت ارسال سقف روزانه هزینه پیامک">
								<summary><span><b>محدودیت ارسال و برآورد هزینه</b><small>تنظیمات پیشنهادی برای جلوگیری از ارسال شبانه و کنترل مصرف</small></span><i>نمایش تنظیمات</i></summary>
								<div class="szc-grid2">
									<div class="szc-field szc-field--col"><label for="szc-send-from">شروع ارسال مجاز</label><div class="szc-field-c"><input id="szc-send-from" type="number" name="send_from" min="0" max="23" value="<?php echo esc_attr( $s['send_from'] ); ?>" inputmode="numeric"><span class="szc-hint">پیشنهاد: ساعت ۹</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-send-to">پایان ارسال مجاز</label><div class="szc-field-c"><input id="szc-send-to" type="number" name="send_to" min="1" max="24" value="<?php echo esc_attr( $s['send_to'] ); ?>" inputmode="numeric"><span class="szc-hint">پیشنهاد: ساعت ۲۱</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-max-run">سقف هر نوبت صف</label><div class="szc-field-c"><input id="szc-max-run" type="number" name="max_per_run" min="1" max="500" value="<?php echo esc_attr( $s['max_per_run'] ); ?>" inputmode="numeric"><span class="szc-hint">هر نوبت معمولاً هر پنج دقیقه اجرا می‌شود.</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-max-day">سقف روزانه</label><div class="szc-field-c"><input id="szc-max-day" type="number" name="max_per_day" min="0" value="<?php echo esc_attr( $s['max_per_day'] ); ?>" inputmode="numeric"><span class="szc-hint">صفر یعنی بدون سقف روزانه.</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-sms-cost">هزینه تقریبی هر بخش</label><div class="szc-field-c"><input id="szc-sms-cost" type="number" name="sms_cost_per_part" min="0" step="0.01" value="<?php echo esc_attr( $s['sms_cost_per_part'] ?? 0 ); ?>" inputmode="decimal"><span class="szc-hint">برای گزارش برآورد هزینه؛ مبلغ صورتحساب واقعی پنل نیست.</span></div></div>
								</div>
							</details>
							<div class="szc-section-save"><span data-unsaved-label>همه تغییرات این بخش ذخیره شده‌اند.</span><button class="button button-primary" type="submit"><?php echo szc_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ذخیره تنظیمات پیامک</button></div>
						</form>
						<details class="szc-scard szc-disclosure szc-pattern-quick-form" data-settings-item="ساخت پترن جدید قالب پیامک متن Template ID">
							<summary><span><b>ساخت پترن مشتری جدید</b><small>فرم ساخت فقط زمانی باز شود که پترن تازه‌ای دارید</small></span><i>بازکردن فرم</i></summary>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'szc_template_save' ); ?><input type="hidden" name="action" value="szc_template_save"><input type="hidden" name="id" value="0"><input type="hidden" name="return_to" value="settings_sms">
								<div class="szc-pattern-quick-grid"><label>نام پترن<input type="text" name="name" required placeholder="مثلاً پیگیری بعد از تماس"></label><label>Template ID<input type="text" name="pattern_code" dir="ltr" required placeholder="123456"></label><label class="is-wide">متن مرجع پیامک<textarea name="body" rows="3" required placeholder="سلام %first%، از گفت‌وگوی امروز خوشحال شدیم…"></textarea><small>متغیرها: <code>%first%</code>، <code>%last%</code>، <code>%name%</code>، <code>%company%</code>، <code>%job%</code>، <code>%city%</code>، <code>%mini%</code> و <code>%intro%</code></small></label></div>
								<div class="szc-pattern-quick-actions"><button class="button button-primary" type="submit"><?php echo szc_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> افزودن پترن</button><a class="button" href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">ویرایش و حذف پترن‌های موجود</a></div>
							</form>
						</details>
					</section>

					<section class="szc-settings-pane" data-settings-pane="automation" aria-labelledby="szc-title-automation" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-automation">اتوماسیون‌ها</h2><p>قانون پیامک پس از تماس را با مثال و خروجی روشن تنظیم کنید.</p></div><a class="button" href="<?php echo esc_url( self::url( 'szc-sequences' ) ); ?>">دنباله‌های پیامکی</a></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-section-form" data-settings-form>
							<?php wp_nonce_field( 'szc_settings_section_save_automation' ); ?><input type="hidden" name="action" value="szc_settings_section_save"><input type="hidden" name="section" value="automation">
							<div class="szc-scard" data-settings-item="پیامک پس از تماس قالب پیش فرض فاصله ارسال لینک مینی دوره جلسه معارفه">
								<div class="szc-scard-h"><h3>پیامک پس از تماس</h3><p>اگر کارشناس هنگام ثبت تماس، پیامک فوری انتخاب کند، پیامک پیش‌فرض دوم ارسال نمی‌شود.</p></div>
								<div class="szc-field szc-field--toggle"><label for="szc-auto-call">فعال باشد</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-auto-call" type="checkbox" name="auto_after_call" value="1" <?php checked( ! empty( $s['auto_after_call'] ) ); ?>><span></span></label><p class="szc-hint">فقط برای نتیجه‌هایی که در پایین قالب دارند اجرا می‌شود.</p></div></div>
								<div class="szc-grid2">
									<div class="szc-field szc-field--col"><label for="szc-auto-template">قالب پیش‌فرض</label><div class="szc-field-c"><select id="szc-auto-template" name="auto_template_id"><option value="0">اولین قالب موجود</option><?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>" <?php selected( (int) $s['auto_template_id'], (int) $t->id ); ?>><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?></select></div></div>
									<div class="szc-field szc-field--col"><label for="szc-auto-delay">فاصله ارسال</label><div class="szc-field-c"><input id="szc-auto-delay" type="number" name="auto_delay_min" min="1" value="<?php echo esc_attr( $s['auto_delay_min'] ); ?>" inputmode="numeric"><span class="szc-hint">دقیقه پس از ثبت تماس؛ پیشنهاد: ۶۰ دقیقه.</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-mini-link">لینک مینی‌دوره</label><div class="szc-field-c"><input id="szc-mini-link" type="url" name="mini_link" value="<?php echo esc_attr( $s['mini_link'] ); ?>" dir="ltr"><span class="szc-hint">در قالب با <code>%mini%</code> استفاده می‌شود.</span></div></div>
									<div class="szc-field szc-field--col"><label for="szc-intro-link">لینک جلسه معارفه</label><div class="szc-field-c"><input id="szc-intro-link" type="url" name="intro_link" value="<?php echo esc_attr( $s['intro_link'] ); ?>" dir="ltr"><span class="szc-hint">در قالب با <code>%intro%</code> استفاده می‌شود.</span></div></div>
								</div>
							</div>
							<div class="szc-scard" data-settings-item="نتیجه تماس پاسخ داد بی پاسخ مشغول بی علاقه شماره اشتباه قالب پیامک">
								<div class="szc-scard-h"><h3>قالب مناسب برای هر نتیجه تماس</h3><p>«بدون پیامک» یعنی پس از آن نتیجه هیچ پیام خودکاری ساخته نشود.</p></div>
								<div class="szc-outcome-grid"><?php $omap = (array) $s['outcome_templates']; foreach ( SZC_Settings::call_outcomes() as $ok => $olbl ) : ?><label><span><?php echo esc_html( $olbl ); ?></span><select name="outcome_templates[<?php echo esc_attr( $ok ); ?>]"><option value="0">بدون پیامک</option><?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>" <?php selected( (int) ( $omap[ $ok ] ?? 0 ), (int) $t->id ); ?>><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?></select></label><?php endforeach; ?></div>
							</div>
							<div class="szc-section-save"><span data-unsaved-label>همه تغییرات این بخش ذخیره شده‌اند.</span><button class="button button-primary" type="submit"><?php echo szc_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ذخیره اتوماسیون‌ها</button></div>
						</form>
					</section>

					<section class="szc-settings-pane" data-settings-pane="notifications" aria-labelledby="szc-title-notifications" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-notifications">پیگیری و اعلان‌ها</h2><p>مشخص کنید مدیریت چه گزارش‌هایی دریافت کند و کارشناس چگونه از پیگیری مطلع شود.</p></div></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-section-form" data-settings-form>
							<?php wp_nonce_field( 'szc_settings_section_save_notifications' ); ?><input type="hidden" name="action" value="szc_settings_section_save"><input type="hidden" name="section" value="notifications">
							<div class="szc-scard" data-settings-item="گزارش تارگت مدیریت موبایل مدیر جمع بندی پایان روز افت عملکرد پیگیری عقب افتاده">
								<div class="szc-scard-h"><h3>اعلان‌های مدیریتی</h3><p>گزارش هدف، افت عملکرد و افزایش پیگیری‌های عقب‌افتاده از پنل پیامک فعال ارسال می‌شوند.</p></div>
								<div class="szc-field"><label for="szc-manager-mobiles">موبایل مدیران دریافت‌کننده</label><div class="szc-field-c"><textarea id="szc-manager-mobiles" name="manager_alert_mobiles" rows="4" dir="ltr" inputmode="tel" placeholder="0912...&#10;0935..."><?php echo esc_textarea( $s['manager_alert_mobiles'] ); ?></textarea><p class="szc-hint">هر شماره در یک خط. شماره‌های پروفایل مدیران CRM نیز خودکار اضافه می‌شوند.</p></div></div>
								<div class="szc-field szc-field--toggle"><label for="szc-goal-alerts">پیشرفت و ریسک عملکرد</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-goal-alerts" type="checkbox" name="goal_alerts" value="1" <?php checked( ! empty( $s['goal_alerts'] ) ); ?>><span></span></label><p class="szc-hint">در ۵۰٪، ۸۰٪ و ۱۰۰٪ هدف و همچنین افت معنادار عملکرد هشدار می‌دهد.</p></div></div>
								<div class="szc-field szc-field--toggle"><label for="szc-daily-summary">جمع‌بندی پایان روز</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-daily-summary" type="checkbox" name="goal_daily_summary" value="1" <?php checked( ! empty( $s['goal_daily_summary'] ) ); ?>><span></span></label><p class="szc-hint">خلاصه عملکرد همه کارشناسان را روزی یک‌بار ارسال می‌کند.</p></div></div>
								<div class="szc-field"><label for="szc-summary-hour">ساعت جمع‌بندی</label><div class="szc-field-c"><input id="szc-summary-hour" type="number" name="goal_summary_hour" min="0" max="23" value="<?php echo esc_attr( $s['goal_summary_hour'] ); ?>" inputmode="numeric"><p class="szc-hint">بر اساس ساعت وردپرس؛ پیشنهاد: ساعت ۲۰.</p></div></div>
							</div>
							<div class="szc-scard" data-settings-item="یادآوری پیگیری کارشناس پیامک ایمیل">
								<div class="szc-scard-h"><h3>یادآوری پیگیری به کارشناس</h3><p>اعلان داخل CRM همیشه فعال است؛ کانال‌های بیرونی را در صورت نیاز روشن کنید.</p></div>
								<div class="szc-field szc-field--toggle"><label for="szc-followup-sms">پیامک یادآوری</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-followup-sms" type="checkbox" name="followup_remind" value="1" <?php checked( ! empty( $s['followup_remind'] ) ); ?>><span></span></label><p class="szc-hint">به موبایل ثبت‌شده برای کارشناس ارسال می‌شود.</p></div></div>
								<div class="szc-field szc-field--toggle"><label for="szc-followup-email">ایمیل یادآوری</label><div class="szc-field-c"><label class="szc-switch"><input id="szc-followup-email" type="checkbox" name="followup_remind_email" value="1" <?php checked( ! empty( $s['followup_remind_email'] ) ); ?>><span></span></label><p class="szc-hint">فقط برای کارشناسانی که حساب وردپرس و ایمیل معتبر دارند.</p></div></div>
							</div>
							<div class="szc-section-save"><span data-unsaved-label>همه تغییرات این بخش ذخیره شده‌اند.</span><button class="button button-primary" type="submit"><?php echo szc_icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ذخیره اعلان‌ها</button></div>
						</form>
					</section>

					<section class="szc-settings-pane" data-settings-pane="data" aria-labelledby="szc-title-data" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-data">داده‌ها و نگهداری</h2><p>ابزارهای ورود، پاک‌سازی، بازیابی و بررسی تغییرات در یک محل جمع شده‌اند.</p></div></div>
						<div class="szc-tool-grid">
							<a class="szc-tool-card" data-settings-item="ایمپورت ورود CSV اکسل" href="<?php echo esc_url( self::url( 'szc-import' ) ); ?>"><?php echo szc_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>ورود اطلاعات از CSV</b><small>نگاشت ستون‌ها، کنترل شماره و جلوگیری از ثبت تکراری</small></span><i>بازکردن</i></a>
							<a class="szc-tool-card" data-settings-item="خروجی CSV بکاپ مخاطبان" href="<?php echo esc_url( self::url( 'szc-contacts' ) ); ?>"><?php echo szc_icon( 'file-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>خروجی مخاطبان</b><small>از صفحه مخاطبان، فیلتر دلخواه را اعمال و خروجی CSV دریافت کنید</small></span><i>بازکردن</i></a>
							<a class="szc-tool-card" data-settings-item="مخاطب تکراری ادغام" href="<?php echo esc_url( self::url( 'szc-duplicates' ) ); ?>"><?php echo szc_icon( 'merge' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>مخاطبان تکراری</b><small>رکوردهای مشابه را بررسی و با حفظ سوابق ادغام کنید</small></span><i>بازکردن</i></a>
							<a class="szc-tool-card" data-settings-item="لیست سیاه لغو دریافت پیامک" href="<?php echo esc_url( self::url( 'szc-blacklist' ) ); ?>"><?php echo szc_icon( 'ban' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>لیست سیاه پیامک</b><small>شماره‌های مسدود و دلیل جلوگیری از ارسال</small></span><i>بازکردن</i></a>
							<a class="szc-tool-card" data-settings-item="سطل زباله بازیابی مخاطب حذف شده" href="<?php echo esc_url( self::url( 'szc-trash' ) ); ?>"><?php echo szc_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>سطل زباله و بازیابی</b><small>بازگرداندن مخاطب حذف‌شده یا حذف دائمی توسط مدیر</small></span><i>بازکردن</i></a>
							<a class="szc-tool-card" data-settings-item="گزارش تغییرات Audit Log مالکیت ورود حذف" href="<?php echo esc_url( self::url( 'szc-audit' ) ); ?>"><?php echo szc_icon( 'history' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b>گزارش کامل تغییرات</b><small>چه کسی، چه چیزی را و در چه زمانی تغییر داده است</small></span><i>بازکردن</i></a>
						</div>
						<div class="szc-callout is-warning"><b>پشتیبان‌گیری:</b> خروجی CSV برای انتقال مخاطبان مناسب است، اما جای بکاپ کامل دیتابیس را نمی‌گیرد. قبل از ارتقا یا عملیات گسترده، از پنل میزبانی بکاپ کامل بگیرید.</div>
					</section>

					<section class="szc-settings-pane" data-settings-pane="advanced" aria-labelledby="szc-title-advanced" hidden>
						<div class="szc-pane-heading"><div><h2 id="szc-title-advanced">تنظیمات پیشرفته</h2><p>اطلاعات فنی و ابزارهای کم‌استفاده؛ برای کار روزمره نیازی به تغییر این بخش نیست.</p></div><span class="szc-badge-recommended">ویژه مدیر فنی</span></div>
						<div class="szc-scard" data-settings-item="سلامت سیستم نسخه دیتابیس Cron صف پیامک">
							<div class="szc-scard-h"><h3>سلامت سیستم</h3><p>این اطلاعات برای عیب‌یابی در اختیار پشتیبانی قرار می‌گیرد و قابل ویرایش نیست.</p></div>
							<dl class="szc-system-health">
								<div><dt>نسخه افزونه</dt><dd><?php echo esc_html( SZC_VERSION ); ?></dd></div>
								<div><dt>نسخه دیتابیس CRM</dt><dd><?php echo esc_html( $db_version ); ?> <?php echo $db_version === SZC_Install::DB_VERSION ? '<span class="szc-ok">هماهنگ</span>' : '<span class="szc-danger">نیازمند ارتقا</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
								<div><dt>زمان‌بندی صف پیامک</dt><dd><?php echo $cron_ok ? '<span class="szc-ok">فعال</span>' : '<span class="szc-danger">زمان‌بندی پیدا نشد</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></dd></div>
								<div><dt>پیامک‌های در انتظار</dt><dd><?php echo esc_html( szc_fa_digits( $queue_pending ) ); ?></dd></div>
								<div><dt>ساعت وردپرس</dt><dd><?php echo esc_html( wp_date( 'Y-m-d H:i' ) ); ?></dd></div>
							</dl>
						</div>
						<div class="szc-danger-zone" data-settings-item="بازگردانی تنظیمات پیش فرض حذف تنظیمات ناحیه خطر">
							<h3>ناحیه حساس</h3>
							<p>بازگردانی فقط تنظیمات انتخاب‌شده را به مقدار اولیه برمی‌گرداند؛ مخاطبان، فعالیت‌ها و گزارش‌ها حذف نمی‌شوند.</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-settings-reset-form>
								<?php wp_nonce_field( 'szc_settings_reset_section' ); ?><input type="hidden" name="action" value="szc_settings_reset_section">
								<label for="szc-reset-section">بخش موردنظر</label>
								<select id="szc-reset-section" name="section"><option value="access">کاربران و دسترسی‌ها</option><option value="conversation">اسکریپت مکالمه</option><option value="assignment">تخصیص مخاطبان</option><option value="sms">پیامک</option><option value="automation">اتوماسیون‌ها</option><option value="notifications">اعلان‌ها</option></select>
								<button type="submit" class="button button-secondary">بازگردانی این بخش به پیش‌فرض</button>
							</form>
						</div>
					</section>
				</main>
			</div>
		</div>
		<?php
	}

	public static function page_settings() {
		self::guard();
		$s         = SZC_Settings::all();
		$templates = SZC_Templates::all_patterned();
		$staff     = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'shop_manager', 'contributor' ), 'number' => 500, 'fields' => array( 'ID', 'display_name', 'user_login' ) ) );
		$managers  = SZC_Settings::manager_ids();
		$sms_ok    = SZC_SMS::enabled();

		$tabs = array(
			'access'     => array( 'دسترسی و نقش‌ها', 'users' ),
			'sms'        => array( 'پنل پیامک', 'mail' ),
			'auto'       => array( 'اتوماسیون پیامک', 'sparkles' ),
			'remind'     => array( 'اعلان‌های مدیریتی', 'bell' ),
		);
		$status = array(
			'sms'        => $sms_ok,
		);
		?>
		<div class="wrap szc-wrap szc-settings">
			<div class="szc-settings-hero">
				<div>
					<h1>تنظیمات سازان CRM</h1>
					<p class="szc-muted">پیکربندیِ دسترسی، پنل پیامک و اتوماسیون‌ها. کارشناسانِ فروش از منوی «کارشناسان» مدیریت می‌شوند.</p>
				</div>
				<div class="szc-settings-badges">
					<span class="szc-pill <?php echo $sms_ok ? 'is-on' : 'is-off'; ?>">پیامک: <?php echo $sms_ok ? 'فعال' : 'غیرفعال'; ?></span>
				</div>
			</div>

			<?php if ( isset( $_GET['msg'] ) ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>

			<div class="szc-tabs" role="tablist">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<button type="button" class="szc-tab" data-tab="<?php echo esc_attr( $key ); ?>" role="tab">
						<?php echo szc_icon( $tab[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( $tab[0] ); ?></span>
						<?php if ( isset( $status[ $key ] ) ) : ?><i class="szc-tab-dot <?php echo $status[ $key ] ? 'is-on' : 'is-off'; ?>"></i><?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-settings-form">
				<?php wp_nonce_field( 'szc_settings_save' ); ?>
				<input type="hidden" name="action" value="szc_settings_save">

				<!-- ===== دسترسی و نقش‌ها ===== -->
				<div class="szc-tabpane" data-pane="access">
					<div class="szc-scard">
						<div class="szc-scard-h"><h2>دسترسی و نقش‌های تیمِ فروش</h2><p>مشخص کنید چه کسانی مدیر و چه کسانی کارشناس‌اند. مدیران سایت همیشه مدیرِ فروش‌اند.</p></div>
						<div class="szc-field">
							<label>مدیرانِ فروش</label>
							<div class="szc-field-c">
								<select name="managers[]" multiple size="6" class="szc-multi">
									<?php foreach ( $staff as $u ) : ?>
										<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $managers, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name . ' (' . $u->user_login . ')' ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="szc-hint">مدیر همه‌ی سرنخ‌ها را می‌بیند و می‌تواند تخصیص دهد. (Ctrl/Cmd برای انتخاب چندتایی)</p>
							</div>
						</div>
						<div class="szc-field szc-field--toggle">
								<label>ورودِ کارشناسان</label>
								<div class="szc-field-c">
									<label class="szc-switch"><input type="checkbox" name="pass_login" value="1" <?php checked( ! empty( $s['pass_login'] ) ); ?>><span></span></label>
									<p class="szc-hint">کارشناسان با موبایل و کد یک‌بارمصرف پیامکی وارد پرتال می‌شوند (بدون کاربر وردپرس).</p>
								</div>
							</div>
							<div class="szc-field szc-field--toggle">
								<label>استخرِ مشترکِ مخاطبین</label>
								<div class="szc-field-c">
									<label class="szc-switch"><input type="checkbox" name="shared_pool" value="1" <?php checked( ! empty( $s['shared_pool'] ) ); ?>><span></span></label>
									<p class="szc-hint">روشن: همه‌ی کارشناسان همه‌ی مخاطبین را می‌بینند (پیش‌فرض). خاموش: هر کارشناس فقط سرنخ‌های تخصیص‌یافته به خودش را می‌بیند.</p>
								</div>
							</div>
							<div class="szc-field">
								<label>مجوزهای کارشناسان</label>
								<div class="szc-field-c">
									<div class="szc-grid2">
										<?php $agent_perms = SZC_Settings::agent_permissions(); foreach ( SZC_Settings::permission_labels() as $pk => $plabel ) : ?>
											<label><input type="checkbox" name="agent_permissions[<?php echo esc_attr( $pk ); ?>]" value="1" <?php checked( ! empty( $agent_perms[ $pk ] ) ); ?>> <?php echo esc_html( $plabel ); ?></label>
										<?php endforeach; ?>
									</div>
									<p class="szc-hint">مجوزها در سمت سرور نیز کنترل می‌شوند و دورزدن رابط کاربری ممکن نیست.</p>
								</div>
							</div>
					</div>
				</div>

				<!-- ===== پنل پیامک ===== -->
				<div class="szc-tabpane" data-pane="sms" hidden>
					<div class="szc-scard">
						<div class="szc-scard-h"><h2>پنل پیامک (فراز/آی‌پی‌پنل یا sms.ir)</h2><p>کلید API و خطِ ارسال را وارد کنید تا ارسالِ پیامک و اتوماسیون‌ها کار کنند.</p></div>
						<div class="szc-field szc-field--toggle">
							<label>فعال‌سازیِ پیامک</label>
							<div class="szc-field-c">
								<label class="szc-switch"><input type="checkbox" name="sms_enabled" value="1" <?php checked( ! empty( $s['sms_enabled'] ) ); ?>><span></span></label>
								<p class="szc-hint">تا فعال نشود، هیچ پیامکی (دستی یا خودکار) ارسال نمی‌شود.</p>
							</div>
						</div>
						<div class="szc-field">
							<label>سرویس‌دهنده</label>
							<div class="szc-field-c">
								<select name="sms_provider">
									<option value="ippanel" <?php selected( $s['sms_provider'], 'ippanel' ); ?>>فراز / آی‌پی‌پنل (ippanel)</option>
									<option value="smsir" <?php selected( $s['sms_provider'], 'smsir' ); ?>>اس‌ام‌اس‌دات‌آی‌آر (sms.ir)</option>
								</select>
								<p class="szc-hint">برای <b>sms.ir</b>: «کلید API» همان API Key، «خط ارسال» شماره‌ی خط و در قالب‌ها «کد پترن» همان <code>templateId</code> عددی است. آدرس پایه را خالی بگذارید.</p>
							</div>
						</div>
						<div class="szc-grid2">
							<div class="szc-field szc-field--col"><label>کلید API</label><div class="szc-field-c"><input type="text" name="sms_apikey" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" dir="ltr" autocomplete="off"></div></div>
							<div class="szc-field szc-field--col"><label>خط ارسال</label><div class="szc-field-c"><input type="text" name="sms_originator" value="<?php echo esc_attr( $s['sms_originator'] ); ?>" dir="ltr" placeholder="+983000..."></div></div>
							<div class="szc-field szc-field--col"><label>آدرس پایه API</label><div class="szc-field-c"><input type="text" name="sms_base" value="<?php echo esc_attr( $s['sms_base'] ); ?>" dir="ltr" placeholder="برای sms.ir خالی بگذارید"></div></div>
							<div class="szc-field szc-field--col"><label>حالت ارسال</label><div class="szc-field-c"><input type="hidden" name="sms_mode" value="mixed"><span class="szc-pattern-lock">پترن خدماتی + متن آزاد</span></div></div>
							<div class="szc-field szc-field--col"><label>پترن اعلان سیستمی</label><div class="szc-field-c"><input type="text" name="system_pattern_code" value="<?php echo esc_attr( $s['system_pattern_code'] ?? '' ); ?>" dir="ltr" required></div></div>
							<div class="szc-field szc-field--col"><label>پارامتر اعلان</label><div class="szc-field-c"><input type="text" name="system_pattern_param" value="<?php echo esc_attr( $s['system_pattern_param'] ?? 'message' ); ?>" dir="ltr"></div></div>
						</div>
						<div class="szc-testbox">
							<span class="szc-testbox-t">تستِ اتصال</span>
							<input type="text" id="szc-test-num" dir="ltr" placeholder="۰۹۱۲...">
							<button type="button" class="button" id="szc-test-sms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szc_admin' ) ); ?>">ارسال پیامک تست</button>
							<span id="szc-test-msg"></span>
						</div>
					</div>

					<div class="szc-scard">
						<div class="szc-scard-h"><h2>محدودیت‌ها و نرخِ ارسال</h2><p>برای پرهیز از پیامکِ شبانه و فشار روی خط.</p></div>
						<div class="szc-field">
							<label>بازه‌ی مجاز ارسال</label>
							<div class="szc-field-c szc-inline-fields">
								از ساعت <input type="number" name="send_from" min="0" max="23" value="<?php echo esc_attr( $s['send_from'] ); ?>" class="szc-num">
								تا ساعت <input type="number" name="send_to" min="1" max="24" value="<?php echo esc_attr( $s['send_to'] ); ?>" class="szc-num">
								<p class="szc-hint">پیامک‌های زمان‌بندی‌شده و همگانی فقط در این بازه ارسال می‌شوند.</p>
							</div>
						</div>
						<div class="szc-grid2">
							<div class="szc-field szc-field--col"><label>سقف در هر نوبت</label><div class="szc-field-c"><input type="number" name="max_per_run" min="1" value="<?php echo esc_attr( $s['max_per_run'] ); ?>" class="szc-num"> <span class="szc-hint">هر اجرای صف (هر ۵ دقیقه)</span></div></div>
							<div class="szc-field szc-field--col"><label>سقف روزانه</label><div class="szc-field-c"><input type="number" name="max_per_day" min="0" value="<?php echo esc_attr( $s['max_per_day'] ); ?>" class="szc-num"> <span class="szc-hint">۰ = نامحدود</span></div></div>
							<div class="szc-field szc-field--col"><label>هزینه تقریبی هر بخش پیامک</label><div class="szc-field-c"><input type="number" name="sms_cost_per_part" min="0" step="0.01" value="<?php echo esc_attr( $s['sms_cost_per_part'] ?? 0 ); ?>"><span class="szc-hint">برای برآورد هزینه؛ مبلغ را طبق تعرفه پنل وارد کنید.</span></div></div>
						</div>
					</div>
				</div>

				<!-- ===== اتوماسیون ===== -->
				<div class="szc-tabpane" data-pane="auto" hidden>
					<div class="szc-scard">
						<div class="szc-scard-h"><h2>اتوماسیونِ پس از تماس</h2><p>پس از ثبتِ تماسِ موفق، یک پیامکِ تشکر/دعوت به‌صورت خودکار زمان‌بندی می‌شود.</p></div>
						<div class="szc-field szc-field--toggle">
							<label>فعال باشد</label>
							<div class="szc-field-c"><label class="szc-switch"><input type="checkbox" name="auto_after_call" value="1" <?php checked( ! empty( $s['auto_after_call'] ) ); ?>><span></span></label></div>
						</div>
						<div class="szc-grid2">
							<div class="szc-field szc-field--col"><label>قالبِ پیش‌فرض</label><div class="szc-field-c">
								<select name="auto_template_id">
									<option value="0">— اولین قالب —</option>
									<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>" <?php selected( (int) $s['auto_template_id'], (int) $t->id ); ?>><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
								</select>
							</div></div>
							<div class="szc-field szc-field--col"><label>فاصله‌ی ارسال</label><div class="szc-field-c"><input type="number" name="auto_delay_min" min="1" value="<?php echo esc_attr( $s['auto_delay_min'] ); ?>" class="szc-num"> دقیقه پس از تماس</div></div>
							<div class="szc-field szc-field--col"><label>لینک مینی‌دوره</label><div class="szc-field-c"><input type="url" name="mini_link" value="<?php echo esc_attr( $s['mini_link'] ); ?>" dir="ltr"><span class="szc-hint">متغیر <code>%mini%</code></span></div></div>
							<div class="szc-field szc-field--col"><label>لینک جلسه‌ی معارفه</label><div class="szc-field-c"><input type="url" name="intro_link" value="<?php echo esc_attr( $s['intro_link'] ); ?>" dir="ltr"><span class="szc-hint">متغیر <code>%intro%</code></span></div></div>
						</div>
					</div>

					<div class="szc-scard">
						<div class="szc-scard-h"><h2>پیامک بر اساس نتیجه‌ی تماس</h2><p>برای هر نتیجه‌ی تماس یک قالب مشخص کنید تا هنگام ثبتِ آن، خودکار ارسال شود. «— بدون پیامک —» یعنی پیامکی نرود.</p></div>
						<div class="szc-grid2">
							<?php $omap = (array) $s['outcome_templates']; foreach ( SZC_Settings::call_outcomes() as $ok => $olbl ) : ?>
								<div class="szc-field szc-field--col"><label><?php echo esc_html( $olbl ); ?></label><div class="szc-field-c">
									<select name="outcome_templates[<?php echo esc_attr( $ok ); ?>]">
										<option value="0">— بدون پیامک —</option>
										<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>" <?php selected( (int) ( $omap[ $ok ] ?? 0 ), (int) $t->id ); ?>><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
									</select>
								</div></div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- ===== یادآوری‌ها ===== -->
				<div class="szc-tabpane" data-pane="remind" hidden>
					<div class="szc-scard">
						<div class="szc-scard-h"><h2>گزارش تارگت برای مدیریت</h2><p>پیشرفت تماس‌های موفق کارشناسان از طریق همان پنل پیامک، از جمله SMS.ir، برای مدیر ارسال می‌شود.</p></div>
						<div class="szc-field">
							<label>موبایل مدیران دریافت‌کننده</label>
							<div class="szc-field-c">
								<textarea name="manager_alert_mobiles" rows="3" dir="ltr" placeholder="0912...&#10;0935..."><?php echo esc_textarea( $s['manager_alert_mobiles'] ); ?></textarea>
								<p class="szc-hint">هر شماره را در یک خط بنویسید. موبایل ثبت‌شده در پروفایل مدیران CRM نیز خودکار اضافه می‌شود.</p>
							</div>
						</div>
						<div class="szc-field szc-field--toggle">
							<label>اعلان پیشرفت تارگت</label>
							<div class="szc-field-c">
								<label class="szc-switch"><input type="checkbox" name="goal_alerts" value="1" <?php checked( ! empty( $s['goal_alerts'] ) ); ?>><span></span></label>
								<p class="szc-hint">در ۵۰٪، ۸۰٪ و ۱۰۰٪ هدف تماس موفق پیامک می‌فرستد. اگر کارشناس یک‌باره از چند آستانه عبور کند فقط یک پیام ارسال می‌شود.</p>
							</div>
						</div>
						<div class="szc-field szc-field--toggle">
							<label>جمع‌بندی پایان روز</label>
							<div class="szc-field-c">
								<label class="szc-switch"><input type="checkbox" name="goal_daily_summary" value="1" <?php checked( ! empty( $s['goal_daily_summary'] ) ); ?>><span></span></label>
								<p class="szc-hint">وضعیت همه‌ی کارشناسان را روزی یک‌بار برای مدیریت می‌فرستد.</p>
							</div>
						</div>
						<div class="szc-field">
							<label>ساعت جمع‌بندی</label>
							<div class="szc-field-c"><input type="number" name="goal_summary_hour" min="0" max="23" value="<?php echo esc_attr( $s['goal_summary_hour'] ); ?>" class="szc-num"> <span class="szc-hint">بر اساس ساعت وردپرس؛ پیشنهاد: ۲۰</span></div>
						</div>
					</div>
					<div class="szc-scard">
						<div class="szc-scard-h"><h2>یادآوریِ پیگیری به کارشناس</h2><p>سرِ زمانِ پیگیری، به کارشناسِ مسئول یادآوری فرستاده می‌شود.</p></div>
						<div class="szc-field szc-field--toggle">
							<label>پیامکِ یادآوری</label>
							<div class="szc-field-c"><label class="szc-switch"><input type="checkbox" name="followup_remind" value="1" <?php checked( ! empty( $s['followup_remind'] ) ); ?>><span></span></label><p class="szc-hint">به موبایلِ کاربرِ کارشناس (از پروفایلِ وردپرس) ارسال می‌شود.</p></div>
						</div>
						<div class="szc-field szc-field--toggle">
							<label>ایمیلِ یادآوری</label>
							<div class="szc-field-c"><label class="szc-switch"><input type="checkbox" name="followup_remind_email" value="1" <?php checked( ! empty( $s['followup_remind_email'] ) ); ?>><span></span></label></div>
						</div>
					</div>
				</div>

				<div class="szc-settings-save">
					<button class="button button-primary button-hero">ذخیره تنظیمات</button>
					<span class="szc-muted">تغییرات همه‌ی بخش‌ها با یک بار ذخیره اعمال می‌شوند.</span>
				</div>
			</form>

		</div>
		<?php
	}

	protected static function settings_center_redirect( $section, $message, $type = 'success' ) {
		set_transient( 'szc_settings_center_' . SZC_Auth::actor_id(), array( 'message' => $message, 'type' => $type ), 60 );
		wp_safe_redirect( self::url( 'szc-settings' ) . '#' . sanitize_key( $section ) );
		exit;
	}

	public static function handle_settings_section_save() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$p = wp_unslash( $_POST );
		$section = sanitize_key( $p['section'] ?? '' );
		if ( ! in_array( $section, array( 'access', 'conversation', 'assignment', 'sms', 'automation', 'notifications' ), true ) ) {
			wp_die( 'بخش تنظیمات نامعتبر است.' );
		}
		check_admin_referer( 'szc_settings_section_save_' . $section );
		$new = array();

		if ( $section === 'access' ) {
			$new['managers'] = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $p['managers'] ?? array() ) ) ) ) );
			$new['pass_login'] = empty( $p['pass_login'] ) ? 0 : 1;
			$new['otp_expiry'] = min( 600, max( 60, absint( $p['otp_expiry'] ?? 120 ) ) );
			$new['otp_resend'] = min( 300, max( 30, absint( $p['otp_resend'] ?? 60 ) ) );
			$new['otp_max_attempts'] = min( 10, max( 3, absint( $p['otp_max_attempts'] ?? 5 ) ) );
			$new['agent_permissions'] = array();
			foreach ( SZC_Settings::permission_labels() as $permission => $label ) {
				$new['agent_permissions'][ $permission ] = empty( $p['agent_permissions'][ $permission ] ) ? 0 : 1;
			}
		} elseif ( $section === 'conversation' ) {
			$stage_goals = array();
			foreach ( SZC_Settings::stages() as $stage_key => $stage_label ) {
				$stage_goals[ $stage_key ] = sanitize_textarea_field( $p['conversation_stage_goals'][ $stage_key ] ?? '' );
			}
			$objections = array();
			foreach ( array_slice( (array) ( $p['conversation_objections'] ?? array() ), 0, 12 ) as $item ) {
				$title    = sanitize_text_field( $item['title'] ?? '' );
				$response = sanitize_textarea_field( $item['response'] ?? '' );
				if ( $title === '' && $response === '' ) { continue; }
				if ( $title === '' || $response === '' ) {
					self::settings_center_redirect( 'conversation', 'برای هر اعتراض، «عنوان» و «پاسخ پیشنهادی» را کامل کنید.', 'error' );
				}
				$objections[] = array(
					'title'    => $title,
					'signals'  => sanitize_text_field( $item['signals'] ?? '' ),
					'response' => $response,
					'question' => sanitize_textarea_field( $item['question'] ?? '' ),
				);
			}
			$opening = sanitize_textarea_field( $p['conversation_opening'] ?? '' );
			if ( ! empty( $p['conversation_enabled'] ) && $opening === '' ) {
				self::settings_center_redirect( 'conversation', 'برای فعال‌بودن دستیار، متن «شروع پیشنهادی مکالمه» را وارد کنید.', 'error' );
			}
			$new = array(
				'conversation_enabled'      => empty( $p['conversation_enabled'] ) ? 0 : 1,
				'conversation_opening'      => $opening,
				'conversation_questions'    => sanitize_textarea_field( $p['conversation_questions'] ?? '' ),
				'conversation_value_points' => sanitize_textarea_field( $p['conversation_value_points'] ?? '' ),
				'conversation_closings'     => sanitize_textarea_field( $p['conversation_closings'] ?? '' ),
				'conversation_guardrails'   => sanitize_textarea_field( $p['conversation_guardrails'] ?? '' ),
				'conversation_stage_goals'  => $stage_goals,
				'conversation_objections'   => $objections,
			);
		} elseif ( $section === 'assignment' ) {
			$new['shared_pool'] = isset( $p['shared_pool'] ) && (string) $p['shared_pool'] === '1' ? 1 : 0;
		} elseif ( $section === 'sms' ) {
			$current   = SZC_Settings::all();
			$send_from = min( 23, max( 0, absint( $p['send_from'] ?? 9 ) ) );
			$send_to   = min( 24, max( 1, absint( $p['send_to'] ?? 21 ) ) );
			$enabled   = empty( $p['sms_enabled'] ) ? 0 : 1;
			$apikey    = sanitize_text_field( $p['sms_apikey'] ?? '' );
			$line      = sanitize_text_field( $p['sms_originator'] ?? '' );
			$system_pattern = sanitize_text_field( $p['system_pattern_code'] ?? '' );
			$system_param = preg_replace( '/[^A-Za-z0-9_]/', '', (string) ( $p['system_pattern_param'] ?? 'message' ) ) ?: 'message';
			$otp_pattern = sanitize_text_field( $p['otp_pattern_code'] ?? '' );
			$otp_param = preg_replace( '/[^A-Za-z0-9_]/', '', (string) ( $p['otp_pattern_param'] ?? 'code' ) ) ?: 'code';
			if ( $send_from >= $send_to ) {
				self::settings_center_redirect( 'sms', 'ساعت پایان ارسال باید بعد از ساعت شروع باشد.', 'error' );
			}
			if ( $enabled && $apikey === '' ) {
				self::settings_center_redirect( 'sms', 'برای فعال‌سازی SMS.ir، کلید API را وارد کنید.', 'error' );
			}
			if ( $enabled && $system_pattern === '' ) {
				self::settings_center_redirect( 'sms', 'برای فعال‌سازی پیامک، پترن اعلان‌های سیستمی را وارد کنید.', 'error' );
			}
			if ( ! empty( $current['pass_login'] ) && $otp_pattern === '' ) {
				self::settings_center_redirect( 'sms', 'برای ورود کارشناسان، Template ID پترن OTP الزامی است.', 'error' );
			}
			$new = array(
				'sms_enabled' => $enabled,
				'sms_provider' => 'smsir',
				'sms_base' => esc_url_raw( $p['sms_base'] ?? '' ),
				'sms_apikey' => $apikey,
				'sms_originator' => $line,
				'sms_mode' => 'mixed',
				'system_pattern_code' => $system_pattern,
				'system_pattern_param' => $system_param,
				'otp_pattern_code' => $otp_pattern,
				'otp_pattern_param' => $otp_param,
				'send_from' => $send_from,
				'send_to' => $send_to,
				'max_per_run' => min( 500, max( 1, absint( $p['max_per_run'] ?? 80 ) ) ),
				'max_per_day' => max( 0, absint( $p['max_per_day'] ?? 0 ) ),
				'sms_cost_per_part' => max( 0, (float) ( $p['sms_cost_per_part'] ?? 0 ) ),
			);
		} elseif ( $section === 'automation' ) {
			$omap = array();
			foreach ( SZC_Settings::call_outcomes() as $outcome => $label ) {
				$template_id = absint( $p['outcome_templates'][ $outcome ] ?? 0 );
				if ( $template_id > 0 ) {
					$omap[ $outcome ] = $template_id;
				}
			}
			$new = array(
				'auto_after_call' => empty( $p['auto_after_call'] ) ? 0 : 1,
				'auto_template_id' => absint( $p['auto_template_id'] ?? 0 ),
				'auto_delay_min' => max( 1, absint( $p['auto_delay_min'] ?? 60 ) ),
				'mini_link' => esc_url_raw( $p['mini_link'] ?? '' ),
				'intro_link' => esc_url_raw( $p['intro_link'] ?? '' ),
				'outcome_templates' => $omap,
			);
		} elseif ( $section === 'notifications' ) {
			$valid_mobiles = array();
			$invalid = array();
			foreach ( preg_split( '/[\r\n,]+/', (string) ( $p['manager_alert_mobiles'] ?? '' ) ) as $mobile ) {
				$mobile = trim( $mobile );
				if ( $mobile === '' ) { continue; }
				$normalized = szc_normalize_mobile( $mobile );
				if ( szc_is_valid_mobile( $normalized ) ) { $valid_mobiles[] = $normalized; }
				else { $invalid[] = $mobile; }
			}
			if ( $invalid ) {
				self::settings_center_redirect( 'notifications', 'شماره موبایل نامعتبر است: ' . implode( '، ', array_slice( $invalid, 0, 3 ) ), 'error' );
			}
			$new = array(
				'manager_alert_mobiles' => implode( "\n", array_values( array_unique( $valid_mobiles ) ) ),
				'goal_alerts' => empty( $p['goal_alerts'] ) ? 0 : 1,
				'goal_daily_summary' => empty( $p['goal_daily_summary'] ) ? 0 : 1,
				'goal_summary_hour' => min( 23, max( 0, absint( $p['goal_summary_hour'] ?? 20 ) ) ),
				'followup_remind' => empty( $p['followup_remind'] ) ? 0 : 1,
				'followup_remind_email' => empty( $p['followup_remind_email'] ) ? 0 : 1,
			);
		}

		SZC_Settings::save( $new );
		SZC_Audit::log( 'settings_update', 'settings', 0, 'بخش «' . $section . '» در مرکز تنظیمات به‌روزرسانی شد' );
		self::settings_center_redirect( $section, 'تغییرات این بخش با موفقیت ذخیره شد.' );
	}

	public static function handle_settings_reset_section() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		check_admin_referer( 'szc_settings_reset_section' );
		$section = sanitize_key( $_POST['section'] ?? '' );
		$keys = array(
			'access' => array( 'managers', 'pass_login', 'otp_expiry', 'otp_resend', 'otp_max_attempts', 'agent_permissions' ),
			'conversation' => array( 'conversation_enabled', 'conversation_opening', 'conversation_questions', 'conversation_value_points', 'conversation_closings', 'conversation_guardrails', 'conversation_stage_goals', 'conversation_objections' ),
			'assignment' => array( 'shared_pool' ),
			'sms' => array( 'max_per_run', 'max_per_day', 'sms_cost_per_part', 'sms_enabled', 'sms_provider', 'sms_base', 'sms_apikey', 'sms_originator', 'sms_mode', 'system_pattern_code', 'system_pattern_param', 'otp_pattern_code', 'otp_pattern_param', 'send_from', 'send_to' ),
			'automation' => array( 'auto_after_call', 'auto_template_id', 'auto_delay_min', 'outcome_templates', 'mini_link', 'intro_link' ),
			'notifications' => array( 'followup_remind', 'followup_remind_email', 'goal_alerts', 'goal_daily_summary', 'goal_summary_hour', 'manager_alert_mobiles' ),
		);
		if ( empty( $keys[ $section ] ) ) {
			wp_die( 'بخش تنظیمات نامعتبر است.' );
		}
		$defaults = SZC_Settings::defaults();
		$reset = array();
		foreach ( $keys[ $section ] as $key ) {
			$reset[ $key ] = $defaults[ $key ];
		}
		SZC_Settings::save( $reset );
		SZC_Audit::log( 'settings_reset', 'settings', 0, 'بخش «' . $section . '» به تنظیمات پیش‌فرض بازگردانده شد' );
		self::settings_center_redirect( 'advanced', 'بخش انتخاب‌شده به تنظیمات پیش‌فرض بازگردانده شد.' );
	}

	public static function handle_settings_save() {
		self::guard();
		check_admin_referer( 'szc_settings_save' );
		$p   = wp_unslash( $_POST );
		$new = array(
			'managers'         => array_map( 'intval', (array) ( $p['managers'] ?? array() ) ),
			'pass_login'       => empty( $p['pass_login'] ) ? 0 : 1,
			'shared_pool'      => empty( $p['shared_pool'] ) ? 0 : 1,
			'agent_permissions'=> array(),
			'max_per_run'      => max( 1, absint( $p['max_per_run'] ?? 80 ) ),
			'max_per_day'      => max( 0, absint( $p['max_per_day'] ?? 0 ) ),
			'sms_cost_per_part'=> max( 0, (float) ( $p['sms_cost_per_part'] ?? 0 ) ),
			'sms_enabled'      => empty( $p['sms_enabled'] ) ? 0 : 1,
			'sms_provider'     => 'smsir',
			'sms_base'         => esc_url_raw( $p['sms_base'] ?? '' ),
			'sms_apikey'       => sanitize_text_field( $p['sms_apikey'] ?? '' ),
			'sms_originator'   => sanitize_text_field( $p['sms_originator'] ?? '' ),
			'sms_mode'         => 'mixed',
			'system_pattern_code' => sanitize_text_field( $p['system_pattern_code'] ?? '' ),
			'system_pattern_param' => sanitize_key( $p['system_pattern_param'] ?? 'message' ),
			'send_from'        => min( 23, max( 0, absint( $p['send_from'] ?? 9 ) ) ),
			'send_to'          => min( 24, max( 1, absint( $p['send_to'] ?? 21 ) ) ),
			'auto_after_call'  => empty( $p['auto_after_call'] ) ? 0 : 1,
			'auto_template_id' => absint( $p['auto_template_id'] ?? 0 ),
			'auto_delay_min'   => max( 1, absint( $p['auto_delay_min'] ?? 60 ) ),
			'mini_link'        => esc_url_raw( $p['mini_link'] ?? '' ),
			'intro_link'       => esc_url_raw( $p['intro_link'] ?? '' ),
			'followup_remind'       => empty( $p['followup_remind'] ) ? 0 : 1,
			'followup_remind_email' => empty( $p['followup_remind_email'] ) ? 0 : 1,
			'goal_alerts'           => empty( $p['goal_alerts'] ) ? 0 : 1,
			'goal_daily_summary'    => empty( $p['goal_daily_summary'] ) ? 0 : 1,
			'goal_summary_hour'     => min( 23, max( 0, absint( $p['goal_summary_hour'] ?? 20 ) ) ),
			'manager_alert_mobiles' => sanitize_textarea_field( $p['manager_alert_mobiles'] ?? '' ),
		);
		foreach ( SZC_Settings::permission_labels() as $permission => $label ) {
			$new['agent_permissions'][ $permission ] = empty( $p['agent_permissions'][ $permission ] ) ? 0 : 1;
		}
		$omap = array();
		foreach ( (array) ( $p['outcome_templates'] ?? array() ) as $ok => $tid ) {
			$tid = absint( $tid );
			if ( $tid > 0 ) {
				$omap[ sanitize_key( $ok ) ] = $tid;
			}
		}
		$new['outcome_templates'] = $omap;
		SZC_Settings::save( $new );
		SZC_Audit::log( 'settings_update', 'settings', 0, 'تنظیمات CRM و مجوزهای کارشناسان به‌روزرسانی شد' );

		wp_safe_redirect( self::url( 'szc-settings', array( 'msg' => 1 ) ) );
		exit;
	}

	/* ==================== کارشناسان (موجودیتِ مستقلِ CRM) ==================== */

	public static function page_agents() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$edit   = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$row    = $edit ? SZC_Agents::get( $edit ) : null;
		$agents = SZC_Agents::all();
		$mmap   = array(
			'saved'   => array( 'success', 'کارشناس ذخیره شد.' ),
			'deleted' => array( 'success', 'کارشناس حذف شد (سرنخ‌هایش بدونِ تخصیص شدند).' ),
			'toggled' => array( 'success', 'وضعیتِ کارشناس تغییر کرد.' ),
			'dup'     => array( 'error', 'کارشناسی با این موبایل از قبل وجود دارد.' ),
			'bad'     => array( 'error', 'نام یا موبایلِ نامعتبر است (۰۹...).' ),
			'revoked' => array( 'success', 'همه‌ی نشست‌های کارشناس باطل شد.' ),
		);
		?>
		<div class="wrap szc-wrap szc-settings">
			<div class="szc-settings-hero">
				<div>
					<h1>کارشناسانِ فروش</h1>
					<p class="szc-muted">کارشناسان مستقل از وردپرس‌اند و با شماره موبایل، کد یک‌بارمصرف یا رمز ثابت وارد پورتال می‌شوند.</p>
				</div>
				<div class="szc-settings-badges">
					<span class="szc-pill is-on"><?php echo esc_html( szc_fa_digits( SZC_Agents::count() ) ); ?> کارشناس</span>
				</div>
			</div>

			<?php
			if ( isset( $_GET['m'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification
				$m = sanitize_key( wp_unslash( $_GET['m'] ) );
				if ( isset( $mmap[ $m ] ) ) :
					?><div class="notice notice-<?php echo esc_attr( $mmap[ $m ][0] ); ?> is-dismissible"><p><?php echo esc_html( $mmap[ $m ][1] ); ?></p></div><?php
				endif;
			endif;
			?>

			<div class="szc-single-grid">
				<div class="szc-col">
					<div class="szc-scard">
						<div class="szc-scard-h"><h2><?php echo $row ? 'ویرایشِ کارشناس' : 'افزودنِ کارشناسِ جدید'; ?></h2><p><?php echo $row ? 'اطلاعات ورود و رمز ثابت کارشناس را مدیریت کنید.' : 'نام، موبایل و رمز ثابت اولیه کارشناس را ثبت کنید.'; ?></p></div>
						<?php if ( $row ) : ?><div class="notice notice-info inline"><p>برای کارشناسانی که در نسخه‌های قبلی ساخته شده‌اند، یک‌بار «رمز ثابت جدید» تعیین کنید. ورود با کد یک‌بارمصرف همچنان فعال می‌ماند.</p></div><?php endif; ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'szc_agent_save' ); ?>
							<input type="hidden" name="action" value="szc_agent_save">
							<input type="hidden" name="id" value="<?php echo (int) ( $row->id ?? 0 ); ?>">
							<div class="szc-field szc-field--col"><label>نام کارشناس</label><div class="szc-field-c"><input type="text" name="name" required value="<?php echo esc_attr( $row->name ?? '' ); ?>" placeholder="مثلاً: مریم احمدی"></div></div>
							<div class="szc-field szc-field--col"><label>شماره موبایل ورود</label><div class="szc-field-c"><input type="tel" name="mobile" required dir="ltr" inputmode="numeric" value="<?php echo esc_attr( $row->mobile ?? '' ); ?>" placeholder="۰۹۱۲..."><p class="szc-hint">برای هر دو روش ورود، همین شماره موبایل استفاده می‌شود.</p></div></div>
							<div class="szc-field szc-field--col"><label><?php echo $row ? 'رمز ثابت جدید' : 'رمز ثابت اولیه'; ?></label><div class="szc-field-c szc-secret-field"><input id="szc-agent-password" type="password" name="password" <?php echo $row ? '' : 'required'; ?> minlength="10" maxlength="200" autocomplete="new-password"><button type="button" class="button" data-toggle-secret aria-controls="szc-agent-password">نمایش</button><span class="szc-hint"><?php echo $row ? 'برای حفظ رمز فعلی خالی بگذارید.' : 'حداقل ۱۰ نویسه و شامل حداقل یک حرف و یک عدد.'; ?></span></div></div>
							<div class="szc-grid2">
								<div class="szc-field szc-field--col"><label>تارگت تماس موفق</label><div class="szc-field-c"><input type="number" name="daily_answered_target" min="0" value="<?php echo esc_attr( $row->daily_answered_target ?? 40 ); ?>" class="szc-num"></div></div>
								<div class="szc-field szc-field--col"><label>تارگت پیگیری</label><div class="szc-field-c"><input type="number" name="daily_followup_target" min="0" value="<?php echo esc_attr( $row->daily_followup_target ?? 10 ); ?>" class="szc-num"></div></div>
								<div class="szc-field szc-field--col"><label>تارگت تبدیل/فروش</label><div class="szc-field-c"><input type="number" name="daily_conversion_target" min="0" value="<?php echo esc_attr( $row->daily_conversion_target ?? 2 ); ?>" class="szc-num"></div></div>
							</div>
							<div class="szc-grid2">
								<div class="szc-field szc-field--col"><label>ظرفیت لید فعال</label><div class="szc-field-c"><input type="number" name="capacity" min="0" value="<?php echo esc_attr( $row->capacity ?? 100 ); ?>" class="szc-num"><p class="szc-hint">صفر یعنی در توزیع خودکار لید دریافت نکند.</p></div></div>
								<div class="szc-field szc-field--col"><label>وزن توزیع</label><div class="szc-field-c"><input type="number" name="distribution_weight" min="1" value="<?php echo esc_attr( $row->distribution_weight ?? 1 ); ?>" class="szc-num"></div></div>
							</div>
							<?php $specific_perms = $row ? SZC_Agents::permissions( $row ) : array(); $shown_perms = $specific_perms ?: SZC_Settings::agent_permissions(); ?>
							<div class="szc-field"><label>مجوزهای اختصاصی</label><div class="szc-field-c"><div class="szc-grid2">
								<?php foreach ( SZC_Settings::permission_labels() as $pk => $pl ) : ?><label><input type="checkbox" name="permissions[<?php echo esc_attr( $pk ); ?>]" value="1" <?php checked( ! empty( $shown_perms[ $pk ] ) ); ?>> <?php echo esc_html( $pl ); ?></label><?php endforeach; ?>
							</div><p class="szc-hint">این تنظیم برای همین کارشناس است و بر مجوز عمومی تیم اولویت دارد.</p></div></div>
							<div class="szc-field szc-field--toggle"><label>فعال</label><div class="szc-field-c"><label class="szc-switch"><input type="checkbox" name="active" value="1" <?php checked( $row ? (int) $row->active === 1 : true ); ?>><span></span></label></div></div>
							<p style="padding:0 18px 16px">
								<button class="button button-primary button-hero"><?php echo $row ? 'ذخیره تغییرات' : 'ساخت کارشناس'; ?></button>
								<?php if ( $row ) : ?><a class="button" href="<?php echo esc_url( self::url( 'szc-agents' ) ); ?>">کارشناسِ جدید</a><?php endif; ?>
							</p>
						</form>
					</div>
				</div>

				<div class="szc-col">
					<div class="szc-scard">
						<div class="szc-scard-h"><h2>فهرستِ کارشناسان</h2><p>وضعیت، ویرایش و حذف. حذفِ کارشناس، سرنخ‌هایش را بدونِ تخصیص می‌کند (پاک نمی‌شوند).</p></div>
						<table class="widefat striped szc-passtable" style="margin:0">
							<thead><tr><th>نام</th><th>موبایل</th><th>تارگت تماس / پیگیری / تبدیل</th><th>وضعیت</th><th>اقدام</th></tr></thead>
							<tbody>
							<?php if ( ! $agents ) : ?>
								<tr><td colspan="5" class="szc-muted">هنوز کارشناسی ساخته نشده است.</td></tr>
							<?php else : foreach ( $agents as $a ) : ?>
								<tr>
									<td><b><?php echo esc_html( $a->name ); ?></b></td>
									<td dir="ltr"><?php echo esc_html( szc_fa_digits( $a->mobile ) ); ?></td>
									<td><?php echo esc_html( szc_fa_digits( (int) ( $a->daily_answered_target ?? 0 ) ) . ' / ' . szc_fa_digits( (int) ( $a->daily_followup_target ?? 0 ) ) . ' / ' . szc_fa_digits( (int) ( $a->daily_conversion_target ?? 0 ) ) ); ?></td>
									<td><?php echo (int) $a->active === 1 ? '<span class="szc-ok">فعال</span>' : '<span class="szc-muted">غیرفعال</span>'; ?></td>
									<td class="szc-agent-ops">
										<a class="button-link" href="<?php echo esc_url( self::url( 'szc-agents', array( 'edit' => $a->id ) ) ); ?>">ویرایش</a>
										<a class="button-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_agent_toggle&id=' . $a->id ), 'szc_agent_toggle_' . $a->id ) ); ?>"><?php echo (int) $a->active === 1 ? 'غیرفعال‌کن' : 'فعال‌کن'; ?></a>
										<a class="button-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_agent_revoke_sessions&id=' . $a->id ), 'szc_agent_revoke_sessions_' . $a->id ) ); ?>">خروج از همه دستگاه‌ها</a>
										<a class="button-link szc-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_agent_delete&id=' . $a->id ), 'szc_agent_delete_' . $a->id ) ); ?>" onclick="return confirm('این کارشناس حذف شود؟')">حذف</a>
									</td>
								</tr>
							<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function handle_agent_save() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		check_admin_referer( 'szc_agent_save' );
		$p      = wp_unslash( $_POST );
		$id     = absint( $p['id'] ?? 0 );
		$name   = sanitize_text_field( $p['name'] ?? '' );
		$mobile = szc_normalize_mobile( $p['mobile'] ?? '' );
		$password = (string) ( $p['password'] ?? '' );
		$active = empty( $p['active'] ) ? 0 : 1;
		$target = max( 0, absint( $p['daily_answered_target'] ?? 40 ) );
		$followup_target = max( 0, absint( $p['daily_followup_target'] ?? 10 ) );
		$conversion_target = max( 0, absint( $p['daily_conversion_target'] ?? 2 ) );
		$capacity = max( 0, absint( $p['capacity'] ?? 100 ) );
		$weight = max( 1, absint( $p['distribution_weight'] ?? 1 ) );
		$permissions = array();
		foreach ( SZC_Settings::permission_labels() as $pk => $pl ) { $permissions[ $pk ] = empty( $p['permissions'][ $pk ] ) ? 0 : 1; }

		// اعتبارسنجی رمز باید پیش از هرگونه نوشتن انجام شود تا فرم نیمه‌کاره ذخیره نشود.
		if ( ! $id && $password === '' ) {
			wp_die( 'برای کارشناس جدید، تعیین رمز ثابت اولیه الزامی است.', '', array( 'response' => 400 ) );
		}
		if ( $password !== '' ) {
			$password_valid = SZC_Agents::validate_password( $password );
			if ( empty( $password_valid['ok'] ) ) {
				wp_die( esc_html( $password_valid['msg'] ), '', array( 'response' => 400 ) );
			}
		}

		if ( $id ) {
			$res = SZC_Agents::update( $id, $name, $mobile );
			if ( empty( $res['ok'] ) ) {
				wp_safe_redirect( self::url( 'szc-agents', array( 'edit' => $id, 'm' => 'bad' ) ) );
				exit;
			}
			SZC_Agents::set_active( $id, $active );
			SZC_Agents::set_daily_targets( $id, $target, $followup_target, $conversion_target );
			SZC_Agents::set_management_profile( $id, $capacity, $weight, $permissions );
			if ( $password !== '' ) {
				$pass_result = SZC_Agents::set_password( $id, $password );
				if ( empty( $pass_result['ok'] ) ) {
					wp_die( esc_html( $pass_result['msg'] ), '', array( 'response' => 400 ) );
				}
			}
		} else {
			$res = SZC_Agents::create( $name, $mobile, $password, $active );
			if ( empty( $res['ok'] ) ) {
				$m = ( strpos( (string) $res['msg'], 'موبایل' ) !== false && strpos( (string) $res['msg'], 'وجود' ) !== false ) ? 'dup' : 'bad';
				wp_safe_redirect( self::url( 'szc-agents', array( 'm' => $m ) ) );
				exit;
			}
			SZC_Agents::set_daily_targets( (int) $res['id'], $target, $followup_target, $conversion_target );
			SZC_Agents::set_management_profile( (int) $res['id'], $capacity, $weight, $permissions );
		}
		wp_safe_redirect( self::url( 'szc-agents', array( 'm' => 'saved' ) ) );
		exit;
	}

	public static function handle_agent_delete() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_agent_delete_' . $id );
		SZC_Agents::delete( $id );
		wp_safe_redirect( self::url( 'szc-agents', array( 'm' => 'deleted' ) ) );
		exit;
	}

	public static function handle_agent_revoke_sessions() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) { wp_die( 'دسترسی غیرمجاز' ); }
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_agent_revoke_sessions_' . $id );
		SZC_Agents::revoke_sessions( $id );
		wp_safe_redirect( self::url( 'szc-agents', array( 'm' => 'revoked' ) ) );
		exit;
	}

	public static function handle_agent_toggle() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_agent_toggle_' . $id );
		$a = SZC_Agents::get( $id );
		if ( $a ) {
			SZC_Agents::set_active( $id, (int) $a->active === 1 ? 0 : 1 );
		}
		wp_safe_redirect( self::url( 'szc-agents', array( 'm' => 'toggled' ) ) );
		exit;
	}

	public static function ajax_test_sms() {
		if ( ! SZC_Settings::can_access() ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ), 403 );
		}
		check_ajax_referer( 'szc_admin', 'nonce' );
		if ( ! SZC_SMS::enabled() ) {
			wp_send_json_error( array( 'msg' => 'سرویس پیامک فعال نیست یا کلید/خط خالی است. ابتدا ذخیره کنید.' ) );
		}
		$to = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
		if ( ! szc_is_valid_mobile( szc_normalize_mobile( $to ) ) ) {
			wp_send_json_error( array( 'msg' => 'شماره موبایل معتبر وارد کنید.' ) );
		}
		$res = SZC_SMS::send_system_pattern( $to, 'پیامک آزمایشی سازان CRM' );
		if ( ! empty( $res['ok'] ) ) {
			wp_send_json_success( array( 'msg' => 'ارسال شد ✓' ) );
		}
		wp_send_json_error( array( 'msg' => $res['msg'] ?? 'ارسال ناموفق بود.' ) );
	}

	/* ==================== گزارشِ تحویل پیامک ==================== */

	public static function page_delivery() {
		self::guard();
		global $wpdb;
		$c    = SZC_SMS::delivery_counts( 7 );
		$rows = $wpdb->get_results( 'SELECT contact_id, mobile, delivery, provider_msgid, sent_at FROM ' . SZC_SMS::queue_table()
			. " WHERE status='sent' ORDER BY sent_at DESC LIMIT 80" );
		$lbl  = array(
			'delivered'   => array( 'رسید', 'szc-ok' ),
			'undelivered' => array( 'نرسید', 'szc-danger' ),
			'pending'     => array( 'در انتظار', 'szc-muted' ),
			'unknown'     => array( 'نامشخص', 'szc-muted' ),
			''            => array( 'بدونِ پیگیری', 'szc-muted' ),
		);
		$bmsg = get_transient( 'szc_delivery_' . SZC_Auth::actor_id() );
		if ( $bmsg ) {
			delete_transient( 'szc_delivery_' . SZC_Auth::actor_id() );
		}
		?>
		<div class="wrap szc-wrap">
			<h1>گزارشِ تحویلِ پیامک</h1>
			<p class="szc-muted">وضعیتِ واقعیِ رسیدنِ پیامک‌ها از سرویس‌دهنده (۷ روزِ اخیر). وضعیت‌ها هر ۵ دقیقه خودکار به‌روزرسانی می‌شوند؛ برای به‌روزرسانیِ فوری دکمه‌ی زیر را بزنید.</p>
			<?php if ( $bmsg ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $bmsg ); ?></p></div><?php endif; ?>
			<?php if ( ! SZC_SMS::enabled() ) : ?>
				<div class="notice notice-warning"><p>سرویس پیامک فعال نیست.</p></div>
			<?php endif; ?>

			<div class="szc-kpis">
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $c['sent'] ) ); ?></span><span class="szc-kpi-l">ارسال‌شده (۷ روز)</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n" style="color:#16a34a"><?php echo esc_html( szc_fa_digits( $c['delivered'] ) ); ?></span><span class="szc-kpi-l">رسیده</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n" style="color:#b91c1c"><?php echo esc_html( szc_fa_digits( $c['undelivered'] ) ); ?></span><span class="szc-kpi-l">نرسیده</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $c['pending'] ) ); ?></span><span class="szc-kpi-l">در انتظار</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $c['rate'] ) ); ?>٪</span><span class="szc-kpi-l">نرخِ تحویل</span></div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:8px 0 16px">
				<?php wp_nonce_field( 'szc_delivery_refresh' ); ?>
				<input type="hidden" name="action" value="szc_delivery_refresh">
				<button class="button button-primary">بروزرسانیِ فوریِ وضعیت‌ها</button>
			</form>

			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>مخاطب/موبایل</th><th>وضعیت تحویل</th><th>شناسه سرویس</th><th>زمان ارسال</th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="4" class="szc-muted">پیامکِ ارسال‌شده‌ای نیست.</td></tr>
				<?php else : foreach ( $rows as $r ) :
					$c2 = $r->contact_id ? SZC_Contacts::get( $r->contact_id ) : null;
					$who = $c2 ? SZC_Contacts::full_name( $c2 ) : szc_fa_digits( $r->mobile );
					$dl  = $lbl[ $r->delivery ] ?? $lbl[''];
					?>
					<tr>
						<td><?php echo esc_html( $who ); ?> <span class="szc-muted" dir="ltr"><?php echo esc_html( szc_fa_digits( $r->mobile ) ); ?></span></td>
						<td class="<?php echo esc_attr( $dl[1] ); ?>" style="font-weight:700"><?php echo esc_html( $dl[0] ); ?></td>
						<td dir="ltr" class="szc-muted"><?php echo esc_html( $r->provider_msgid ?: '—' ); ?></td>
						<td class="szc-muted"><?php echo esc_html( $r->sent_at ? szc_format_mysql( $r->sent_at ) : '—' ); ?></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function handle_delivery_refresh() {
		self::guard();
		check_admin_referer( 'szc_delivery_refresh' );
		$n = SZC_SMS::refresh_delivery( 200 );
		set_transient( 'szc_delivery_' . SZC_Auth::actor_id(), 'وضعیتِ ' . szc_fa_digits( $n ) . ' پیامک به‌روزرسانی شد.', 60 );
		wp_safe_redirect( self::url( 'szc-delivery' ) );
		exit;
	}

	/* ==================== ارسال همگانی (Broadcast) ==================== */

	/** آرگومان‌های فیلترِ مخاطبین از فیلدهای b_* (با اعمالِ محدوده‌ی کارشناس). */
	protected static function broadcast_filter_args( $src ) {
		$a = array(
			'search'   => sanitize_text_field( wp_unslash( $src['b_s'] ?? '' ) ),
			'stage'    => sanitize_key( $src['b_stage'] ?? '' ),
			'priority' => sanitize_key( $src['b_priority'] ?? '' ),
			'tag'      => sanitize_text_field( wp_unslash( $src['b_tag'] ?? '' ) ),
			'due'      => sanitize_key( $src['b_due'] ?? '' ),
			'owner'    => absint( $src['b_owner'] ?? 0 ),
			'opt_out'  => '0', // لغو دریافت‌کرده‌ها هرگز.
		);
		if ( ! SZC_Auth::can_see_all() ) {
			$a['owner'] = SZC_Auth::actor_id();
		}
		return $a;
	}

	public static function page_broadcast() {
		self::guard();
		if ( ! SZC_Auth::can( 'bulk_sms' ) ) {
			wp_die( 'دسترسی ارسال پیامک گروهی برای شما فعال نیست.' );
		}
		$s          = SZC_Settings::all();
		$templates  = SZC_Templates::all_patterned();
		$stages     = SZC_Settings::stages();
		$prios      = SZC_Settings::priorities();
		$is_manager = SZC_Settings::is_manager();
		$assignees  = $is_manager ? SZC_Settings::assignable_users() : array();
		$total      = SZC_Contacts::total( SZC_Settings::scope_owner() );
		$sms_on     = SZC_SMS::enabled();
		$res        = get_transient( 'szc_broadcast_' . SZC_Auth::actor_id() );
		if ( $res ) {
			delete_transient( 'szc_broadcast_' . SZC_Auth::actor_id() );
		}
		?>
		<div class="wrap szc-wrap szc-broadcast">
			<h1>ارسال همگانی پیام</h1>
			<p class="szc-muted">پیام آماده یا متن آزاد را یک‌جا برای مخاطبین بفرستید. ارسال از طریق صف و در «بازه مجاز ارسال» انجام می‌شود؛ لغو دریافت‌ها و لیست سیاه خودکار کنار گذاشته می‌شوند.</p>

			<?php if ( $res ) : ?>
				<div class="notice notice-success is-dismissible"><p>
					<b><?php echo esc_html( szc_fa_digits( $res['queued'] ) ); ?></b> پیام در صفِ ارسال قرار گرفت
					(<?php echo esc_html( szc_fa_digits( $res['skipped'] ) ); ?> مخاطب رد شد)<?php echo $res['scheduled'] ? '، زمان‌بندی: ' . esc_html( $res['scheduled'] ) : '، ارسال از هم‌اکنون'; ?>.
				</p></div>
			<?php endif; ?>

			<?php if ( ! $sms_on ) : ?>
				<div class="notice notice-warning"><p>سرویس پیامک فعال نیست. ابتدا از <a href="<?php echo esc_url( self::url( 'szc-settings' ) ); ?>">تنظیمات</a> پیامک را فعال و کلید/خط را وارد کنید.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="szc-broadcast-form" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szc_admin' ) ); ?>">
				<?php wp_nonce_field( 'szc_broadcast' ); ?>
				<input type="hidden" name="action" value="szc_broadcast">

				<div class="szc-single-grid">
					<div class="szc-col">
						<div class="szc-card">
							<h2>۱) نوع پیام</h2>
							<div class="szc-seg"><label class="szc-seg-opt"><input type="radio" name="msg_type" value="template" checked> پیام آماده</label><label class="szc-seg-opt"><input type="radio" name="msg_type" value="free"> متن آزاد</label></div>
							<div data-msg="template" style="margin-top:12px">
								<?php if ( ! $templates ) : ?>
									<p class="szc-muted">هنوز قالبی نساخته‌اید. از <a href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">قالب‌های پیامک</a> بسازید.</p>
								<?php else : ?>
									<select name="template_id" class="regular-text">
										<?php foreach ( $templates as $t ) : ?>
											<option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="szc-muted">متنِ قالب برای هر مخاطب با متغیرهای همان مخاطب پر می‌شود.</p>
								<?php endif; ?>
							</div>
							<div data-msg="free" hidden style="margin-top:12px">
								<textarea name="text" rows="6" class="large-text" maxlength="1000" placeholder="متن پیام را بنویسید…"></textarea>
								<p class="szc-muted"><b data-char-count>۰</b> نویسه · حدود <b data-sms-count>۱</b> بخش برای هر نفر</p>
								<p class="szc-muted">متغیرها: <code>%first%</code>، <code>%last%</code>، <code>%name%</code>، <code>%company%</code> و <code>%city%</code>. متن آزاد به خط ارسال‌کننده نیاز دارد.</p>
							</div>
						</div>


						<div class="szc-card">
							<h2>۲) زمانِ ارسال</h2>
							<div class="szc-seg">
								<label class="szc-seg-opt"><input type="radio" name="when" value="now" checked> از هم‌اکنون</label>
								<label class="szc-seg-opt"><input type="radio" name="when" value="schedule"> زمان‌بندی</label>
							</div>
							<p data-when="schedule" hidden style="margin-top:10px">
								<input type="datetime-local" name="schedule_at">
								<span class="szc-muted">پیام‌ها از این زمان به بعد و در بازه‌ی مجاز ارسال می‌شوند.</span>
							</p>
						</div>
					</div>

					<div class="szc-col">
						<div class="szc-card">
							<h2>۳) گیرندگان</h2>
							<div class="szc-form2">
								<label>مرحله
									<select name="b_stage">
										<option value="">همه مراحل</option>
										<?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
									</select>
								</label>
								<label>اولویت
									<select name="b_priority">
										<option value="">همه اولویت‌ها</option>
										<?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?>
									</select>
								</label>
								<?php if ( $is_manager ) : ?>
								<label>کارشناس
									<select name="b_owner">
										<option value="0">همه کارشناسان</option>
										<?php foreach ( $assignees as $uid => $name ) : ?><option value="<?php echo (int) $uid; ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; ?>
									</select>
								</label>
								<?php endif; ?>
								<label>برچسب
									<input type="text" name="b_tag" placeholder="مثلاً: نمایشگاه">
								</label>
								<label>پیگیری
									<select name="b_due">
										<option value="">—</option>
										<option value="today">سررسیدشده/امروز</option>
										<option value="overdue">معوق</option>
									</select>
								</label>
								<label>جستجو
									<input type="text" name="b_s" placeholder="نام، شرکت…">
								</label>
							</div>
							<div class="szc-broadcast-count">
								<span class="szc-bc-num" data-count-out>—</span>
								<span class="szc-muted">مخاطبِ منطبق با فیلترِ فعلی (بدونِ لغو دریافت). کلِ مخاطبینِ شما: <?php echo esc_html( szc_fa_digits( $total ) ); ?></span>
							</div>
						</div>

						<div class="szc-card szc-broadcast-send">
							<p class="szc-muted">پس از بررسی، ارسال را تأیید کنید. این کار پیام را برای همه‌ی گیرندگانِ بالا در صف قرار می‌دهد.</p>
							<button class="button button-primary button-hero" onclick="return confirm('پیام برای مخاطبینِ منطبق در صفِ ارسال قرار گیرد؟');">در صفِ ارسال قرار بده</button>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/** شمارشِ زنده‌ی گیرندگانِ ارسالِ همگانی (AJAX). */
	public static function ajax_broadcast_count() {
		if ( ! SZC_Settings::can_access() || ! SZC_Auth::can( 'bulk_sms' ) ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ), 403 );
		}
		check_ajax_referer( 'szc_admin', 'nonce' );
		$args  = self::broadcast_filter_args( $_POST );
		$count = count( SZC_Contacts::ids_matching( $args ) );
		wp_send_json_success( array( 'count' => $count, 'fa' => szc_fa_digits( $count ) ) );
	}

	public static function handle_broadcast() {
		self::guard();
		if ( ! SZC_Auth::can( 'bulk_sms' ) ) {
			wp_die( 'دسترسی ارسال پیامک گروهی برای شما فعال نیست.' );
		}
		check_admin_referer( 'szc_broadcast' );
		$p = wp_unslash( $_POST );

		if ( ! SZC_SMS::enabled() ) {
			set_transient( 'szc_broadcast_' . SZC_Auth::actor_id(), array( 'queued' => 0, 'skipped' => 0, 'scheduled' => '' ), 60 );
			wp_safe_redirect( self::url( 'szc-broadcast' ) );
			exit;
		}

		// زمانِ ارسال.
		$when = null;
		$scheduled_label = '';
		if ( ( $p['when'] ?? 'now' ) === 'schedule' ) {
			$ts = szc_ts_from_datetime( $p['schedule_at'] ?? '' );
			if ( $ts ) {
				$when = wp_date( 'Y-m-d H:i:s', $ts );
				$scheduled_label = szc_format_datetime( $ts );
			}
		}

		$ids = SZC_Contacts::ids_matching( self::broadcast_filter_args( $p ) );

		if ( ( $p['msg_type'] ?? 'template' ) === 'free' ) {
			$text = sanitize_textarea_field( $p['text'] ?? '' );
			if ( $text === '' ) {
				wp_die( esc_html__( 'متن پیام را وارد کنید.', 'sazan-crm' ), '', array( 'response' => 400 ) );
			}
			$r = SZC_SMS::enqueue_text_bulk( $ids, $text, $when );
		} else {
			$tid = absint( $p['template_id'] ?? 0 );
			$r   = SZC_SMS::enqueue_template_bulk( $ids, $tid, $when );
		}
		if ( ! empty( $r['error'] ) ) {
			wp_die( esc_html( $r['error'] ), '', array( 'response' => 400 ) );
		}

		set_transient( 'szc_broadcast_' . SZC_Auth::actor_id(), array(
			'queued'    => (int) $r['queued'],
			'skipped'   => (int) $r['skipped'],
			'scheduled' => $scheduled_label,
		), 60 );
		wp_safe_redirect( self::url( 'szc-broadcast' ) );
		exit;
	}

	/* ==================== اقدام گروهی ==================== */

	public static function handle_bulk() {
		self::guard();
		check_admin_referer( 'szc_bulk' );
		$is_manager = SZC_Settings::is_manager();
		$scope      = ( ( $_POST['scope'] ?? '' ) === 'all' ) ? 'all' : 'selected';
		$action     = sanitize_key( $_POST['bulk_action'] ?? '' );

		$fargs = array(
			'search'   => sanitize_text_field( wp_unslash( $_POST['f_s'] ?? '' ) ),
			'stage'    => sanitize_key( $_POST['f_stage'] ?? '' ),
			'priority' => sanitize_key( $_POST['f_priority'] ?? '' ),
			'due'      => sanitize_key( $_POST['f_due'] ?? '' ),
			'owner'    => absint( $_POST['f_owner'] ?? 0 ),
		);
		if ( ! $is_manager ) {
			$fargs['owner'] = SZC_Auth::actor_id();
		}

		if ( $scope === 'all' ) {
			$ids = SZC_Contacts::ids_matching( $fargs );
		} else {
			$ids = array_map( 'intval', (array) ( $_POST['ids'] ?? array() ) );
			if ( ! $is_manager ) {
				$self = SZC_Auth::actor_id();
				$ids  = array_values( array_filter( $ids, function ( $id ) use ( $self ) {
					$c = SZC_Contacts::get( $id );
					return $c && (int) $c->owner_id === $self;
				} ) );
			}
		}
		$ids = array_values( array_filter( $ids ) );
		$n   = count( $ids );
		$msg = '';

		switch ( $action ) {
			case 'stage':
				$v = sanitize_key( $_POST['p_stage'] ?? '' );
				foreach ( $ids as $id ) { SZC_Contacts::set_stage( $id, $v ); }
				$msg = 'مرحله‌ی ' . szc_fa_digits( $n ) . ' مخاطب تغییر کرد.';
				break;
			case 'priority':
				$v = sanitize_key( $_POST['p_priority'] ?? '' );
				foreach ( $ids as $id ) { SZC_Contacts::set_priority( $id, $v ); }
				$msg = 'اولویت ' . szc_fa_digits( $n ) . ' مخاطب تغییر کرد.';
				break;
			case 'tag':
				$tag = sanitize_text_field( wp_unslash( $_POST['p_tag'] ?? '' ) );
				foreach ( $ids as $id ) { SZC_Contacts::add_tag( $id, $tag ); }
				$msg = 'برچسب به ' . szc_fa_digits( $n ) . ' مخاطب افزوده شد.';
				break;
			case 'assign':
				if ( $is_manager ) {
					$owner = absint( $_POST['p_owner'] ?? 0 );
					foreach ( $ids as $id ) { SZC_Contacts::set_owner( $id, $owner ); }
					$msg = szc_fa_digits( $n ) . ' مخاطب تخصیص یافت.';
				}
				break;
			case 'blacklist':
				foreach ( $ids as $id ) {
					$c = SZC_Contacts::get( $id );
					if ( $c ) { SZC_Blacklist::add( $c->mobile, 'اقدام گروهی' ); }
				}
				$msg = szc_fa_digits( $n ) . ' مخاطب به لیست سیاه رفت.';
				break;
			case 'delete':
				foreach ( $ids as $id ) { SZC_Contacts::delete( $id ); }
				$msg = szc_fa_digits( $n ) . ' مخاطب حذف شد.';
				break;
			case 'send':
				$tid = absint( $_POST['p_template'] ?? 0 );
				$r   = SZC_SMS::enqueue_template_bulk( $ids, $tid );
				$msg = szc_fa_digits( $r['queued'] ) . ' پیامک در صف قرار گرفت (' . szc_fa_digits( $r['skipped'] ) . ' رد شد).';
				break;
			case 'enroll':
				$sid = absint( $_POST['p_sequence'] ?? 0 );
				$r   = SZC_Sequences::enroll_bulk( $sid, $ids );
				$msg = szc_fa_digits( $r['enrolled'] ) . ' مخاطب در دنباله ثبت شد (' . szc_fa_digits( $r['skipped'] ) . ' رد شد).';
				break;
			default:
				$msg = 'اقدامی انتخاب نشد.';
		}
		set_transient( 'szc_bulk_' . SZC_Auth::actor_id(), $msg, 60 );
		wp_safe_redirect( wp_get_referer() ?: self::url( 'szc-contacts' ) );
		exit;
	}

	/* ==================== بخش‌بندی ==================== */

	public static function handle_save_segment() {
		self::guard();
		check_admin_referer( 'szc_save_segment' );
		$name = sanitize_text_field( wp_unslash( $_POST['seg_name'] ?? '' ) );
		if ( $name !== '' ) {
			SZC_Segments::create( $name, array(
				'search'   => sanitize_text_field( wp_unslash( $_POST['f_s'] ?? '' ) ),
				'stage'    => sanitize_key( $_POST['f_stage'] ?? '' ),
				'priority' => sanitize_key( $_POST['f_priority'] ?? '' ),
				'due'      => sanitize_key( $_POST['f_due'] ?? '' ),
				'owner'    => absint( $_POST['f_owner'] ?? 0 ),
			) );
		}
		wp_safe_redirect( wp_get_referer() ?: self::url( 'szc-contacts' ) );
		exit;
	}

	public static function handle_delete_segment() {
		self::guard();
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_delete_segment_' . $id );
		SZC_Segments::delete( $id );
		wp_safe_redirect( self::url( 'szc-segments' ) );
		exit;
	}

	public static function page_segments() {
		self::guard();
		$segments = SZC_Segments::all();
		?>
		<div class="wrap szc-wrap">
			<h1>بخش‌بندی‌ها (فیلترهای ذخیره‌شده)</h1>
			<p class="szc-muted">در صفحه‌ی «مخاطبین» فیلتر بزنید و با دکمه‌ی «ذخیره بخش‌بندی» آن را این‌جا نگه دارید.</p>
			<table class="wp-list-table widefat fixed striped">
				<thead><tr><th>نام</th><th>فیلترها</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $segments ) : ?>
					<tr><td colspan="3" class="szc-muted">هنوز بخش‌بندی‌ای ذخیره نشده است.</td></tr>
				<?php else : foreach ( $segments as $sg ) :
					$f = SZC_Segments::filters( $sg ); ?>
					<tr>
						<td><a href="<?php echo esc_url( self::url( 'szc-contacts', $f ) ); ?>"><b><?php echo esc_html( $sg->name ); ?></b></a></td>
						<td class="szc-muted"><?php echo esc_html( implode( '، ', array_map( function ( $k, $v ) { return $k . '=' . $v; }, array_keys( $f ), $f ) ) ?: '—' ); ?></td>
						<td><a class="button-link szc-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_delete_segment&id=' . $sg->id ), 'szc_delete_segment_' . $sg->id ) ); ?>" onclick="return confirm('حذف شود؟')">حذف</a></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ==================== پیگیری‌ها ==================== */

	public static function page_followups() {
		self::guard();
		$tab   = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'due'; // phpcs:ignore WordPress.Security.NonceVerification
		$owner = SZC_Settings::scope_owner();
		$rows  = SZC_Activity::followups( $tab === 'upcoming' ? 'upcoming' : 'due', $owner, 300 );
		?>
		<div class="wrap szc-wrap">
			<h1>پیگیری‌ها</h1>
			<div class="szc-msg" aria-live="polite"></div>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab <?php echo $tab === 'due' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::url( 'szc-followups', array( 'tab' => 'due' ) ) ); ?>">امروز و عقب‌افتاده</a>
				<a class="nav-tab <?php echo $tab === 'upcoming' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( self::url( 'szc-followups', array( 'tab' => 'upcoming' ) ) ); ?>">آینده</a>
			</h2>
			<table class="wp-list-table widefat fixed striped" style="margin-top:12px">
				<thead><tr><th>مخاطب</th><th>موبایل</th><th>زمان</th><th>موضوع</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="5" class="szc-muted">موردی نیست.</td></tr>
				<?php else : foreach ( $rows as $r ) :
					$overdue = ( $tab === 'due' && strtotime( $r->due_at ) < strtotime( current_time( 'mysql' ) ) - DAY_IN_SECONDS ); ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'szc-contacts', 'contact' => $r->contact_id ), admin_url( 'admin.php' ) ) ); ?>"><b><?php echo esc_html( trim( $r->first_name . ' ' . $r->last_name ) ?: szc_fa_digits( $r->mobile ) ); ?></b></a></td>
						<td dir="ltr"><?php echo esc_html( szc_fa_digits( $r->mobile ) ); ?></td>
						<td class="<?php echo $overdue ? 'szc-danger' : ''; ?>"><?php echo esc_html( szc_format_mysql( $r->due_at ) ); ?></td>
						<td class="szc-muted"><?php echo esc_html( $r->body ?: '—' ); ?></td>
						<td><button class="button button-small" data-szc-act="done_followup" data-id="<?php echo (int) $r->id; ?>">انجام شد</button></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ==================== دنباله‌های پیامکی ==================== */

	public static function page_sequences() {
		self::guard();
		$edit      = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$seq       = $edit ? SZC_Sequences::get( $edit ) : null;
		$steps     = $seq ? SZC_Sequences::steps( $seq->id ) : array();
		$templates = SZC_Templates::all_patterned();
		?>
		<div class="wrap szc-wrap">
			<h1>دنباله‌های پیامکی (Drip)</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?><div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div><?php endif; ?>
			<?php if ( ! $templates ) : ?>
				<div class="notice notice-warning"><p>ابتدا از <a href="<?php echo esc_url( self::url( 'szc-templates' ) ); ?>">قالب‌های پیامک</a> چند قالب بسازید تا بتوانید گام‌های دنباله را تعریف کنید.</p></div>
			<?php endif; ?>

			<div class="szc-single-grid">
				<div class="szc-col">
					<div class="szc-card">
						<h2><?php echo $seq ? 'ویرایش دنباله' : 'دنباله‌ی جدید'; ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'szc_sequence_save' ); ?>
							<input type="hidden" name="action" value="szc_sequence_save">
							<input type="hidden" name="id" value="<?php echo (int) ( $seq->id ?? 0 ); ?>">
							<p><label>نام دنباله<br><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr( $seq->name ?? '' ); ?>"></label></p>
							<p><label><input type="checkbox" name="active" value="1" <?php checked( $seq ? $seq->active : 1 ); ?>> فعال</label></p>
							<h3>گام‌ها</h3>
							<p class="szc-muted">هر گام: چند روز پس از ثبت‌نام، ساعت چند، با کدام قالب. قالبِ «— بدون —» یعنی آن ردیف نادیده گرفته می‌شود.</p>
							<table class="widefat"><thead><tr><th>روز</th><th>ساعت</th><th>قالب</th></tr></thead><tbody>
							<?php for ( $i = 0; $i < 6; $i++ ) :
								$st = $steps[ $i ] ?? null; ?>
								<tr>
									<td><input type="number" name="step_day[]" min="0" value="<?php echo esc_attr( $st ? $st->day_offset : ( $i === 0 ? 0 : '' ) ); ?>" class="small-text"></td>
									<td><input type="number" name="step_hour[]" min="0" max="23" value="<?php echo esc_attr( $st ? $st->hour : 10 ); ?>" class="small-text"></td>
									<td>
										<select name="step_template[]">
											<option value="0">— بدون —</option>
											<?php foreach ( $templates as $t ) : ?>
												<option value="<?php echo (int) $t->id; ?>" <?php selected( $st ? (int) $st->template_id : 0, (int) $t->id ); ?>><?php echo esc_html( $t->name ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
							<?php endfor; ?>
							</tbody></table>
							<p style="margin-top:12px"><button class="button button-primary"><?php echo $seq ? 'ذخیره' : 'ایجاد دنباله'; ?></button>
								<?php if ( $seq ) : ?><a class="button" href="<?php echo esc_url( self::url( 'szc-sequences' ) ); ?>">دنباله‌ی جدید</a><?php endif; ?></p>
						</form>
					</div>
				</div>
				<div class="szc-col">
					<div class="szc-card">
						<h2>دنباله‌های موجود</h2>
						<?php $all = SZC_Sequences::all(); if ( ! $all ) : ?>
							<p class="szc-muted">هنوز دنباله‌ای نساخته‌اید.</p>
						<?php else : ?>
							<ul class="szc-tpl-list">
								<?php foreach ( $all as $s ) : $stt = SZC_Sequences::stats( $s->id ); ?>
									<li>
										<div><b><?php echo esc_html( $s->name ); ?></b> <?php echo $s->active ? '' : '<span class="szc-muted">(غیرفعال)</span>'; ?>
											<p class="szc-muted"><?php echo esc_html( szc_fa_digits( $stt['steps'] ) ); ?> گام · <?php echo esc_html( szc_fa_digits( $stt['active'] ) ); ?> ثبت‌نام فعال</p></div>
										<div class="szc-tpl-ops">
											<a class="button-link" href="<?php echo esc_url( self::url( 'szc-sequences', array( 'edit' => $s->id ) ) ); ?>">ویرایش</a>
											<a class="button-link szc-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_sequence_delete&id=' . $s->id ), 'szc_sequence_delete_' . $s->id ) ); ?>" onclick="return confirm('حذف دنباله و لغو گام‌های در صف؟')">حذف</a>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function handle_sequence_save() {
		self::guard();
		check_admin_referer( 'szc_sequence_save' );
		$id     = absint( $_POST['id'] ?? 0 );
		$name   = wp_unslash( $_POST['name'] ?? '' );
		$active = ! empty( $_POST['active'] );
		if ( $id ) {
			SZC_Sequences::update( $id, $name, $active );
		} else {
			$id = SZC_Sequences::create( $name );
			if ( ! $active ) { SZC_Sequences::update( $id, $name, false ); }
		}
		$days = array_map( 'intval', (array) ( $_POST['step_day'] ?? array() ) );
		$hrs  = array_map( 'intval', (array) ( $_POST['step_hour'] ?? array() ) );
		$tpls = array_map( 'intval', (array) ( $_POST['step_template'] ?? array() ) );
		$rows = array();
		foreach ( $tpls as $i => $tid ) {
			if ( $tid > 0 ) {
				$rows[] = array( 'day_offset' => $days[ $i ] ?? 0, 'hour' => $hrs[ $i ] ?? 10, 'template_id' => $tid );
			}
		}
		if ( $id ) {
			SZC_Sequences::set_steps( $id, $rows );
		}
		wp_safe_redirect( self::url( 'szc-sequences', array( 'edit' => $id, 'msg' => 1 ) ) );
		exit;
	}

	public static function handle_sequence_delete() {
		self::guard();
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_sequence_delete_' . $id );
		SZC_Sequences::delete( $id );
		wp_safe_redirect( self::url( 'szc-sequences' ) );
		exit;
	}

	/* ==================== لیست سیاه ==================== */

	public static function page_blacklist() {
		self::guard();
		$page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$rows = SZC_Blacklist::all( 100, $page );
		$cnt  = SZC_Blacklist::count();
		?>
		<div class="wrap szc-wrap">
			<h1>لیست سیاه (لغو دریافت سراسری)</h1>
			<p class="szc-muted">شماره‌های این فهرست هرگز پیامک دریافت نمی‌کنند (چه دستی، چه خودکار، چه دنباله). مجموع: <b><?php echo esc_html( szc_fa_digits( $cnt ) ); ?></b></p>

			<div class="szc-card" style="max-width:640px">
				<h2>افزودن شماره</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'szc_blacklist_add' ); ?>
					<input type="hidden" name="action" value="szc_blacklist_add">
					<p><textarea name="numbers" rows="4" class="large-text" dir="ltr" placeholder="هر خط یک شماره (۰۹...)"></textarea></p>
					<p><input type="text" name="reason" class="regular-text" placeholder="دلیل (اختیاری)"></p>
					<p><button class="button button-primary">افزودن به لیست سیاه</button></p>
				</form>
			</div>

			<table class="wp-list-table widefat fixed striped" style="margin-top:16px">
				<thead><tr><th>موبایل</th><th>دلیل</th><th>تاریخ</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="4" class="szc-muted">فهرست خالی است.</td></tr>
				<?php else : foreach ( $rows as $r ) : ?>
					<tr>
						<td dir="ltr"><?php echo esc_html( szc_fa_digits( $r->mobile ) ); ?></td>
						<td class="szc-muted"><?php echo esc_html( $r->reason ?: '—' ); ?></td>
						<td class="szc-muted"><?php echo esc_html( szc_format_mysql( $r->created_at, false ) ); ?></td>
						<td><a class="button-link szc-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_blacklist_remove&id=' . $r->id ), 'szc_blacklist_remove_' . $r->id ) ); ?>">حذف</a></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function handle_blacklist_add() {
		self::guard();
		check_admin_referer( 'szc_blacklist_add' );
		$n = SZC_Blacklist::add_bulk_text( wp_unslash( $_POST['numbers'] ?? '' ), sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) ) );
		set_transient( 'szc_bulk_' . SZC_Auth::actor_id(), szc_fa_digits( $n ) . ' شماره به لیست سیاه افزوده شد.', 60 );
		wp_safe_redirect( self::url( 'szc-blacklist' ) );
		exit;
	}

	public static function handle_blacklist_remove() {
		self::guard();
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'szc_blacklist_remove_' . $id );
		SZC_Blacklist::remove( $id );
		wp_safe_redirect( self::url( 'szc-blacklist' ) );
		exit;
	}

	/* ==================== گزارش‌ها ==================== */

	/* ==================== ریز فعالیت کارشناس ==================== */

	/** ورودی‌های فیلترِ گزارش ریز فعالیت (مشترکِ صفحه و خروجی CSV). */
	protected static function activity_request( $src ) {
		$is_manager = SZC_Settings::is_manager();
		$scope      = SZC_Settings::scope_owner();
		$agent      = isset( $src['agent'] ) ? absint( $src['agent'] ) : 0;
		if ( ! $is_manager ) {
			$agent = (int) $scope;
		}
		$range   = SZC_Reports::activity_range(
			isset( $src['period'] ) ? sanitize_text_field( wp_unslash( $src['period'] ) ) : '7',
			isset( $src['from'] ) ? sanitize_text_field( wp_unslash( $src['from'] ) ) : '',
			isset( $src['to'] ) ? sanitize_text_field( wp_unslash( $src['to'] ) ) : ''
		);
		$type    = isset( $src['type'] ) ? sanitize_key( wp_unslash( $src['type'] ) ) : 'call';
		$outcome = isset( $src['outcome'] ) ? sanitize_text_field( wp_unslash( $src['outcome'] ) ) : '';
		if ( $outcome !== '' && ! isset( SZC_Settings::call_outcomes()[ $outcome ] ) ) {
			$outcome = '';
		}
		return array(
			'agent'      => $agent,
			'is_manager' => $is_manager,
			'from'       => $range['from'],
			'to'         => $range['to'],
			'days'       => $range['days'],
			'period'     => $range['period'],
			'type'       => in_array( $type, array( 'call', 'followup', 'sms', 'stage', 'all' ), true ) ? $type : 'call',
			'outcome'    => $outcome,
		);
	}

	/** برچسبِ فارسیِ نوعِ فعالیت. */
	protected static function activity_type_label( $type ) {
		$labels = array( 'call' => 'تماس', 'followup' => 'پیگیری', 'sms' => 'پیامک', 'stage' => 'تغییر مرحله', 'note' => 'یادداشت', 'external' => 'تعامل واتساپ/تلگرام' );
		return $labels[ $type ] ?? $type;
	}

	/** صفحه‌ی «ریز فعالیت کارشناس»: تاریخ و ساعتِ دقیقِ هر تماس و نتیجه‌ی آن. */
	public static function page_agent_activity() {
		self::guard();
		$f = self::activity_request( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
		$agents  = SZC_Settings::assignable_users();
		$periods = SZC_Reports::activity_periods();
		$page_no = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$per     = 100;

		$summary = $daily = $hourly = $rows = array();
		$total   = 0;
		if ( $f['agent'] > 0 ) {
			$summary = SZC_Reports::agent_activity_summary( $f['agent'], $f['from'], $f['to'] );
			$daily   = SZC_Reports::agent_activity_daily( $f['agent'], $f['from'], $f['to'] );
			$hourly  = SZC_Reports::agent_activity_hourly( $f['agent'], $f['from'], $f['to'] );
			$total   = SZC_Reports::agent_activity_count( $f['agent'], $f['from'], $f['to'], $f['type'], $f['outcome'] );
			$rows    = SZC_Reports::agent_activity_log( $f['agent'], $f['from'], $f['to'], $f['type'], $f['outcome'], $per, ( $page_no - 1 ) * $per );
		}
		$max_day  = 1;
		foreach ( $daily as $d ) { $max_day = max( $max_day, (int) $d['calls'] ); }
		$max_hour = 1;
		foreach ( $hourly as $h ) { $max_hour = max( $max_hour, (int) $h['calls'] ); }
		$base = array_filter( array(
			'agent'   => $f['agent'],
			'period'  => $f['period'],
			'from'    => $f['from'],
			'to'      => $f['to'],
			'type'    => $f['type'],
			'outcome' => $f['outcome'],
		) );
		?>
		<div class="wrap szc-wrap szc-activity">
			<h1>ریز فعالیت کارشناس فروش</h1>
			<p class="szc-muted">تاریخ و ساعتِ دقیقِ هر تماس، نتیجه‌ی آن و مخاطبِ مربوطه — در بازه‌ی یک‌هفته، دوهفته، سه‌هفته، یک‌ماهه یا بازه‌ی دلخواه.</p>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="szc-report-filter szc-activity-filter">
				<input type="hidden" name="page" value="szc-agent-activity">
				<?php if ( $f['is_manager'] ) : ?>
					<label>کارشناس
						<select name="agent">
							<option value="0">— انتخاب کنید —</option>
							<?php foreach ( $agents as $oid => $name ) : ?>
								<option value="<?php echo (int) $oid; ?>" <?php selected( $f['agent'], $oid ); ?>><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php else : ?>
					<input type="hidden" name="agent" value="<?php echo (int) $f['agent']; ?>">
				<?php endif; ?>
				<label>بازه
					<select name="period">
						<?php foreach ( $periods as $key => $meta ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $f['period'], (string) $key ); ?>><?php echo esc_html( $meta['label'] ); ?></option>
						<?php endforeach; ?>
						<option value="custom" <?php selected( $f['period'], 'custom' ); ?>>بازه‌ی دلخواه</option>
					</select>
				</label>
				<label>از <input type="date" name="from" value="<?php echo esc_attr( $f['from'] ); ?>"></label>
				<label>تا <input type="date" name="to" value="<?php echo esc_attr( $f['to'] ); ?>"></label>
				<label>نوع
					<select name="type">
						<option value="call" <?php selected( $f['type'], 'call' ); ?>>تماس</option>
						<option value="followup" <?php selected( $f['type'], 'followup' ); ?>>پیگیری</option>
						<option value="sms" <?php selected( $f['type'], 'sms' ); ?>>پیامک</option>
						<option value="all" <?php selected( $f['type'], 'all' ); ?>>همه</option>
					</select>
				</label>
				<label>نتیجه
					<select name="outcome">
						<option value="">همه</option>
						<?php foreach ( SZC_Settings::call_outcomes() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $f['outcome'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<button class="button button-primary">نمایش گزارش</button>
			</form>

			<p class="szc-muted szc-activity-quick">
				بازه‌های آماده:
				<?php foreach ( $periods as $key => $meta ) : ?>
					<a class="button button-small<?php echo $f['period'] === (string) $key ? ' button-primary' : ''; ?>"
						href="<?php echo esc_url( self::url( 'szc-agent-activity', array( 'agent' => $f['agent'], 'period' => $key, 'type' => $f['type'], 'outcome' => $f['outcome'] ) ) ); ?>"><?php echo esc_html( $meta['label'] ); ?></a>
				<?php endforeach; ?>
			</p>

			<?php if ( $f['agent'] <= 0 ) : ?>
				<div class="szc-card"><p class="szc-muted">برای دیدن ریز فعالیت، یک کارشناس را انتخاب کنید.</p></div>
			<?php else : ?>
				<div class="szc-card">
					<h2><?php echo esc_html( SZC_Auth::display_name( $f['agent'] ) ?: 'کارشناس #' . $f['agent'] ); ?>
						<span class="szc-muted"> — <?php echo esc_html( szc_format_mysql( $f['from'] . ' 00:00:00', false ) . ' تا ' . szc_format_mysql( $f['to'] . ' 00:00:00', false ) ); ?>
							(<?php echo esc_html( szc_fa_digits( $f['days'] ) ); ?> روز)</span>
					</h2>
					<div class="szc-kpis szc-kpis-compact">
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['calls'] ) ); ?></span><span class="szc-kpi-l">کل تماس</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['answered'] ) ); ?></span><span class="szc-kpi-l">تماس موفق (پاسخ داد)</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['answer_rate'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ موفقیت</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['contacts'] ) ); ?></span><span class="szc-kpi-l">مخاطب یکتا</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['active_days'] ) ); ?></span><span class="szc-kpi-l">روز کاری فعال</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['per_active_day'] ) ); ?></span><span class="szc-kpi-l">میانگین تماس در روز فعال</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['followups'] ) ); ?></span><span class="szc-kpi-l">پیگیری ثبت‌شده</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $summary['sms'] ) ); ?></span><span class="szc-kpi-l">پیامک ارسالی</span></div>
					</div>
					<div class="szc-outcome-chips">
						<?php foreach ( $summary['outcomes'] as $key => $meta ) : ?>
							<a class="szc-chip<?php echo $f['outcome'] === $key ? ' is-on' : ''; ?>"
								href="<?php echo esc_url( self::url( 'szc-agent-activity', array_merge( $base, array( 'outcome' => $f['outcome'] === $key ? '' : $key, 'paged' => 1 ) ) ) ); ?>">
								<?php echo esc_html( $meta['label'] ); ?>: <b><?php echo esc_html( szc_fa_digits( $meta['count'] ) ); ?></b>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="szc-single-grid">
					<div class="szc-col szc-card">
						<h3>تفکیک روزانه</h3>
						<table class="widefat striped">
							<thead><tr><th>روز</th><th>تاریخ</th><th>تماس</th><th>موفق</th><th>نرخ</th><th></th></tr></thead>
							<tbody>
							<?php foreach ( $daily as $d ) : ?>
								<tr>
									<td><?php echo esc_html( $d['weekday'] ); ?></td>
									<td><?php echo esc_html( $d['label'] ); ?></td>
									<td><?php echo esc_html( szc_fa_digits( $d['calls'] ) ); ?></td>
									<td><b><?php echo esc_html( szc_fa_digits( $d['answered'] ) ); ?></b></td>
									<td><?php echo esc_html( szc_fa_digits( $d['rate'] ) ); ?>٪</td>
									<td class="szc-barcell"><i style="width:<?php echo (int) round( $d['calls'] / $max_day * 100 ); ?>%"></i></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div class="szc-col szc-card">
						<h3>پرکارترین ساعات روز</h3>
						<table class="widefat striped">
							<thead><tr><th>ساعت</th><th>تماس</th><th>موفق</th><th></th></tr></thead>
							<tbody>
							<?php foreach ( $hourly as $h ) : if ( ! $h['calls'] ) { continue; } ?>
								<tr>
									<td><?php echo esc_html( szc_fa_digits( sprintf( '%02d:00', $h['hour'] ) ) ); ?></td>
									<td><?php echo esc_html( szc_fa_digits( $h['calls'] ) ); ?></td>
									<td><b><?php echo esc_html( szc_fa_digits( $h['answered'] ) ); ?></b></td>
									<td class="szc-barcell"><i style="width:<?php echo (int) round( $h['calls'] / $max_hour * 100 ); ?>%"></i></td>
								</tr>
							<?php endforeach; ?>
							<?php if ( ! $summary['calls'] ) : ?><tr><td colspan="4" class="szc-muted">تماسی در این بازه ثبت نشده است.</td></tr><?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="szc-card">
					<div class="szc-report-head">
						<div><h2>ریز فعالیت‌ها</h2><p class="szc-muted"><?php echo esc_html( szc_fa_digits( $total ) ); ?> رکورد در این بازه و فیلتر.</p></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'szc_activity_export' ); ?>
							<input type="hidden" name="action" value="szc_activity_export">
							<?php foreach ( array( 'agent', 'period', 'from', 'to', 'type', 'outcome' ) as $key ) : ?>
								<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $f[ $key ] ); ?>">
							<?php endforeach; ?>
							<button class="button">خروجی CSV (اکسل)</button>
						</form>
					</div>
					<div class="szc-table-scroll">
						<table class="widefat striped szc-activity-table">
							<thead><tr><th>ردیف</th><th>تاریخ</th><th>روز</th><th>ساعت</th><th>نوع</th><th>نتیجه</th><th>مخاطب</th><th>موبایل</th><th>توضیح</th></tr></thead>
							<tbody>
							<?php if ( ! $rows ) : ?>
								<tr><td colspan="9" class="szc-muted">فعالیتی با این فیلترها ثبت نشده است.</td></tr>
							<?php else : $i = ( $page_no - 1 ) * $per; foreach ( $rows as $r ) :
								$i++;
								$name = trim( (string) $r->first_name . ' ' . (string) $r->last_name );
								if ( $name === '' ) { $name = szc_fa_digits( (string) $r->mobile ); }
								$ok = ( $r->type === 'call' && $r->outcome === 'answered' );
								?>
								<tr class="<?php echo $ok ? 'szc-row-ok' : ''; ?>">
									<td class="szc-muted"><?php echo esc_html( szc_fa_digits( $i ) ); ?></td>
									<td><?php echo esc_html( szc_format_mysql( $r->created_at, false ) ); ?></td>
									<td class="szc-muted"><?php echo esc_html( szc_weekday_fa( $r->created_at ) ); ?></td>
									<td><b><?php echo esc_html( szc_format_time( $r->created_at ) ); ?></b></td>
									<td><?php echo esc_html( self::activity_type_label( $r->type ) ); ?></td>
									<td>
										<?php if ( $r->type === 'call' ) : ?>
											<span class="szc-outcome <?php echo $ok ? 'is-ok' : 'is-no'; ?>"><?php echo esc_html( SZC_Settings::outcome_label( $r->outcome ) ); ?></span>
										<?php else : ?>
											<?php echo esc_html( $r->outcome ?: '—' ); ?>
										<?php endif; ?>
									</td>
									<td><?php if ( $r->contact_id ) : ?><a href="<?php echo esc_url( self::url( 'szc-contacts', array( 'contact' => (int) $r->contact_id ) ) ); ?>"><?php echo esc_html( $name ); ?></a><?php else : ?>—<?php endif; ?></td>
									<td class="szc-muted"><?php echo esc_html( szc_fa_digits( (string) $r->mobile ) ); ?></td>
									<td class="szc-muted"><?php echo esc_html( (string) $r->body ?: '—' ); ?></td>
								</tr>
							<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
					<?php
					$pages = (int) ceil( $total / $per );
					if ( $pages > 1 ) {
						echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( array(
							'base'      => self::url( 'szc-agent-activity', $base ) . '&paged=%#%',
							'format'    => '',
							'current'   => $page_no,
							'total'     => $pages,
							'prev_text' => '‹',
							'next_text' => '›',
						) ) . '</div></div>';
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/** خروجی CSV از ریز فعالیت‌های کارشناس (سازگار با اکسل فارسی). */
	public static function handle_activity_export() {
		self::guard();
		check_admin_referer( 'szc_activity_export' );
		$f = self::activity_request( $_POST );
		if ( $f['agent'] <= 0 ) {
			wp_die( 'کارشناس مشخص نشده است.' );
		}
		$rows = SZC_Reports::agent_activity_log( $f['agent'], $f['from'], $f['to'], $f['type'], $f['outcome'], 5000 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sazan-activity-' . (int) $f['agent'] . '-' . $f['from'] . '_' . $f['to'] . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'کارشناس', 'تاریخ شمسی', 'روز هفته', 'ساعت', 'تاریخ میلادی', 'نوع', 'نتیجه', 'موفق؟', 'مخاطب', 'موبایل', 'شرکت', 'منبع', 'توضیح' ) );
		$agent_name = SZC_Auth::display_name( $f['agent'] );
		foreach ( $rows as $r ) {
			$name = trim( (string) $r->first_name . ' ' . (string) $r->last_name );
			fputcsv( $out, array(
				$agent_name,
				szc_format_mysql( $r->created_at, false ),
				szc_weekday_fa( $r->created_at ),
				substr( (string) $r->created_at, 11, 5 ),
				$r->created_at,
				self::activity_type_label( $r->type ),
				$r->type === 'call' ? SZC_Settings::outcome_label( $r->outcome ) : $r->outcome,
				( $r->type === 'call' && $r->outcome === 'answered' ) ? 'بله' : 'خیر',
				$name,
				(string) $r->mobile,
				(string) $r->company,
				(string) $r->source,
				(string) $r->body,
			) );
		}
		fclose( $out );
		exit;
	}

	public static function page_reports() {
		self::guard();
		$is_manager = SZC_Settings::is_manager();
		$owner   = SZC_Settings::scope_owner();
		$funnel  = SZC_Reports::funnel( $owner );
		$byout   = SZC_Reports::calls_by_outcome( 7 );
		$board   = $is_manager ? SZC_Reports::agent_leaderboard() : array();
		$to      = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : wp_date( 'Y-m-d' ); // phpcs:ignore WordPress.Security.NonceVerification
		$from    = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS ); // phpcs:ignore WordPress.Security.NonceVerification
		$filter_owner = $is_manager ? absint( $_GET['agent'] ?? 0 ) : $owner; // phpcs:ignore WordPress.Security.NonceVerification
		$filter_source = sanitize_text_field( wp_unslash( $_GET['source'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$filter_campaign = sanitize_text_field( wp_unslash( $_GET['campaign'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$filter_group = absint( $_GET['group'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$management_filters = array( 'from' => $from, 'to' => $to, 'owner' => $filter_owner, 'source' => $filter_source, 'campaign' => $filter_campaign, 'group' => $filter_group );
		$management = SZC_Reports::management_metrics( $management_filters );
		$stage_conversion = SZC_Reports::stage_conversion( $management_filters );
		$historical_aging = SZC_Reports::historical_stage_aging( $management_filters );
		$filter_options = SZC_Reports::filter_options();
		$sms_analytics = SZC_SMS::analytics( $from, $to );
		$team    = $is_manager ? SZC_Reports::team_performance( $from, $to ) : array();
		if ( $filter_owner ) { $team = array_values( array_filter( $team, function ( $member ) use ( $filter_owner ) { return (int) $member['owner'] === (int) $filter_owner; } ) ); }
		$cohort  = SZC_Reports::cohort( $from, $to, $owner );
		$aging   = SZC_Reports::stage_aging( $owner );
		$sources = SZC_Reports::source_performance( $from, $to, $owner );
		$campaigns = SZC_Reports::campaign_performance( $from, $to, $owner );
		$recent_team = $is_manager ? SZC_Reports::recent_team_activity( 50 ) : array();
		$team_totals = array( 'calls' => 0, 'answered' => 0, 'followups' => 0, 'registered' => 0 );
		foreach ( $team as $member ) {
			foreach ( $team_totals as $key => $unused ) {
				$team_totals[ $key ] += (int) $member['range'][ $key ];
			}
		}
		$team_rate = $team_totals['calls'] > 0 ? round( $team_totals['answered'] / $team_totals['calls'] * 100, 1 ) : 0;
		$maxstage = 1;
		foreach ( $funnel['stages'] as $s ) { $maxstage = max( $maxstage, $s['count'] ); }
		?>
		<div class="wrap szc-wrap">
			<h1>گزارش‌ها</h1>
			<?php $goal_msg = get_transient( 'szc_goal_summary_' . SZC_Auth::actor_id() ); if ( $goal_msg ) { delete_transient( 'szc_goal_summary_' . SZC_Auth::actor_id() ); echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $goal_msg ) . '</p></div>'; } ?>
			<?php $distribution_msg = get_transient( 'szc_distribution_' . SZC_Auth::actor_id() ); if ( $distribution_msg ) { delete_transient( 'szc_distribution_' . SZC_Auth::actor_id() ); echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( szc_fa_digits( $distribution_msg['assigned'] ) . ' لید توزیع و ' . szc_fa_digits( $distribution_msg['skipped'] ) . ' مورد رد شد.' ) . '</p></div>'; } ?>

			<?php if ( $is_manager ) : ?>
			<div class="szc-card">
				<div class="szc-report-head"><div><h2>داشبورد مدیریتی فیلترپذیر</h2><p class="szc-muted">تمام شاخص‌های این بخش بر اساس فیلترهای زیر محاسبه می‌شوند.</p></div></div>
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="szc-report-filter">
					<input type="hidden" name="page" value="szc-reports">
					<label>از <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label>
					<label>تا <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label>
					<label>کارشناس <select name="agent"><option value="0">همه</option><?php foreach ( SZC_Settings::assignable_users() as $oid => $name ) : ?><option value="<?php echo (int) $oid; ?>" <?php selected( $filter_owner, $oid ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label>
					<label>منبع <select name="source"><option value="">همه</option><?php foreach ( $filter_options['sources'] as $value ) : ?><option <?php selected( $filter_source, $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select></label>
					<label>کمپین <select name="campaign"><option value="">همه</option><?php foreach ( $filter_options['campaigns'] as $value ) : ?><option <?php selected( $filter_campaign, $value ); ?>><?php echo esc_html( $value ); ?></option><?php endforeach; ?></select></label>
					<label>پوشه <select name="group"><option value="0">همه</option><?php foreach ( SZC_Groups::all() as $group ) : ?><option value="<?php echo (int) $group->id; ?>" <?php selected( $filter_group, $group->id ); ?>><?php echo esc_html( $group->name ); ?></option><?php endforeach; ?></select></label>
					<button class="button button-primary">اعمال فیلتر</button>
				</form>
				<div class="szc-kpis szc-kpis-compact">
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $management['leads'] ) ); ?></span><span class="szc-kpi-l">لید ورودی</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $management['avg_first_minutes'] ) ); ?></span><span class="szc-kpi-l">میانگین اولین تماس (دقیقه)</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $management['sla_rate'] ) ); ?>٪</span><span class="szc-kpi-l">SLA تماس زیر ۶۰ دقیقه</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $management['stagnant'] ) ); ?></span><span class="szc-kpi-l">لید راکد</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $management['unassigned'] ) ); ?></span><span class="szc-kpi-l">بدون مسئول</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $management['overdue'] ) ); ?></span><span class="szc-kpi-l">پیگیری عقب‌افتاده</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( number_format_i18n( $management['expected_value'], 0 ) ); ?></span><span class="szc-kpi-l">ارزش احتمالی</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( number_format_i18n( $management['forecast'], 0 ) ); ?></span><span class="szc-kpi-l">پیش‌بینی وزنی فروش</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( number_format_i18n( $management['final_value'], 0 ) ); ?></span><span class="szc-kpi-l">فروش نهایی</span></div>
				</div>
				<div class="szc-single-grid">
					<div class="szc-col"><h3>تبدیل مرحله‌به‌مرحله</h3><table class="widefat striped"><thead><tr><th>مرحله</th><th>ورودی</th><th>تبدیل از مرحله قبل</th></tr></thead><tbody><?php foreach ( $stage_conversion as $row ) : ?><tr><td><?php echo esc_html( $row['label'] ); ?></td><td><?php echo esc_html( szc_fa_digits( $row['count'] ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $row['rate'] ) ); ?>٪</td></tr><?php endforeach; ?></tbody></table></div>
					<div class="szc-col"><h3>ماندگاری تاریخی مرحله</h3><table class="widefat striped"><thead><tr><th>مرحله</th><th>خروج ثبت‌شده</th><th>میانگین روز</th></tr></thead><tbody><?php foreach ( $historical_aging as $row ) : ?><tr><td><?php echo esc_html( $row['label'] ); ?></td><td><?php echo esc_html( szc_fa_digits( $row['transitions'] ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $row['avg_days'] ) ); ?></td></tr><?php endforeach; ?></tbody></table></div>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:16px"><?php wp_nonce_field( 'szc_smart_distribute' ); ?><input type="hidden" name="action" value="szc_smart_distribute"><button class="button">توزیع هوشمند لیدهای بدون مسئول</button> <span class="szc-muted">بر اساس ظرفیت، وزن و بار فعال هر کارشناس</span></form>
			</div>

			<div class="szc-card"><h2>هزینه و کیفیت پیامک در بازه</h2><div class="szc-kpis szc-kpis-compact">
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $sms_analytics['sent'] ) ); ?></span><span class="szc-kpi-l">ارسال‌شده</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $sms_analytics['delivery_rate'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ تحویل</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $sms_analytics['error_rate'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ خطا</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $sms_analytics['cancel_rate'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ لغو دریافت</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( number_format_i18n( $sms_analytics['estimated_cost'], 0 ) ); ?></span><span class="szc-kpi-l">هزینه تقریبی</span></div>
			</div></div>
			<?php endif; ?>

			<div class="szc-kpis">
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( SZC_Reports::calls_today( $owner ) ) ); ?></span><span class="szc-kpi-l">تماس امروز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( SZC_Reports::sms_sent( 1 ) ) ); ?></span><span class="szc-kpi-l">پیامک امروز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( SZC_Reports::sms_sent( 7 ) ) ); ?></span><span class="szc-kpi-l">پیامک ۷ روز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $funnel['conversion'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ تبدیل</span></div>
			</div>

			<div class="szc-card">
				<div class="szc-report-head"><div><h2>تحلیل بازه و Cohort</h2><p class="szc-muted">این نرخ فقط لیدهای ایجادشده در بازه انتخابی را دنبال می‌کند؛ بنابراین با نرخ کل موجودی CRM اشتباه نمی‌شود.</p></div></div>
				<div class="szc-kpis szc-kpis-compact">
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $cohort['leads'] ) ); ?></span><span class="szc-kpi-l">لیدهای Cohort</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $cohort['won'] ) ); ?></span><span class="szc-kpi-l">تبدیل‌شده</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $cohort['conversion'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ تبدیل Cohort</span></div>
					<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( number_format_i18n( $cohort['value'], 0 ) ); ?></span><span class="szc-kpi-l">ارزش فروش (تومان)</span></div>
				</div>
				<div class="szc-single-grid">
					<div class="szc-col"><h3>زمان ماندگاری در مراحل</h3><table class="widefat striped"><thead><tr><th>مرحله</th><th>مخاطب جاری</th><th>میانگین روز</th></tr></thead><tbody>
					<?php foreach ( $aging as $row ) : ?><tr><td><?php echo esc_html( $row['label'] ); ?></td><td><?php echo esc_html( szc_fa_digits( $row['contacts'] ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $row['avg_days'] ) ); ?></td></tr><?php endforeach; ?>
					</tbody></table></div>
					<div class="szc-col"><h3>عملکرد منابع جذب در بازه</h3><table class="widefat striped"><thead><tr><th>منبع</th><th>لید</th><th>فروش</th><th>نرخ</th><th>ارزش</th></tr></thead><tbody>
					<?php if ( ! $sources ) : ?><tr><td colspan="5">داده‌ای نیست.</td></tr><?php else : foreach ( $sources as $src ) : $rate = $src->leads ? round( $src->won / $src->leads * 100, 1 ) : 0; ?>
						<tr><td><?php echo esc_html( $src->source ); ?></td><td><?php echo esc_html( szc_fa_digits( $src->leads ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $src->won ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $rate ) ); ?>٪</td><td><?php echo esc_html( number_format_i18n( $src->value, 0 ) ); ?></td></tr>
					<?php endforeach; endif; ?></tbody></table></div>
				</div>
				<div class="szc-table-scroll" style="margin-top:16px">
					<h3>بازده کمپین‌ها در بازه</h3>
					<p class="szc-muted">بازده عملیاتی از نرخ تبدیل و فروش نهایی محاسبه می‌شود. برای ROI مالی دقیق، هزینه واقعی کمپین نیز باید در CRM ثبت شود.</p>
					<table class="widefat striped"><thead><tr><th>کمپین</th><th>لید</th><th>فروش</th><th>نرخ تبدیل</th><th>ارزش احتمالی</th><th>فروش نهایی</th></tr></thead><tbody>
					<?php if ( ! $campaigns ) : ?><tr><td colspan="6">داده‌ای برای کمپین‌ها ثبت نشده است.</td></tr><?php else : foreach ( $campaigns as $campaign ) : $campaign_rate = $campaign->leads ? round( $campaign->won / $campaign->leads * 100, 1 ) : 0; ?>
						<tr><td><?php echo esc_html( $campaign->campaign ); ?></td><td><?php echo esc_html( szc_fa_digits( $campaign->leads ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $campaign->won ) ); ?></td><td><?php echo esc_html( szc_fa_digits( $campaign_rate ) ); ?>٪</td><td><?php echo esc_html( number_format_i18n( $campaign->expected_value, 0 ) ); ?></td><td><?php echo esc_html( number_format_i18n( $campaign->final_value, 0 ) ); ?></td></tr>
					<?php endforeach; endif; ?></tbody></table>
				</div>
			</div>

			<?php if ( $is_manager ) : ?>
				<div class="szc-card szc-team-analytics">
					<div class="szc-report-head">
						<div><h2>تحلیل تیم فروش</h2><p class="szc-muted">تماس موفق فقط نتیجه‌ی «پاسخ داد» است. تارگت و پیشرفت امروز مستقل از بازه‌ی گزارش نمایش داده می‌شود.</p></div>
						<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="szc-report-filter">
							<input type="hidden" name="page" value="szc-reports">
							<label>از <input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label>
							<label>تا <input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label>
							<button class="button">اعمال بازه</button>
						</form>
					</div>
					<div class="szc-kpis szc-kpis-compact">
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $team_totals['calls'] ) ); ?></span><span class="szc-kpi-l">کل تماس بازه</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $team_totals['answered'] ) ); ?></span><span class="szc-kpi-l">تماس موفق</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $team_rate ) ); ?>٪</span><span class="szc-kpi-l">نرخ پاسخ</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $team_totals['followups'] ) ); ?></span><span class="szc-kpi-l">پیگیری ثبت‌شده</span></div>
						<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $team_totals['registered'] ) ); ?></span><span class="szc-kpi-l">ثبت‌نام ثبت‌شده</span></div>
					</div>
					<div class="szc-table-scroll">
						<table class="widefat striped szc-team-table">
							<thead><tr><th>کارشناس</th><th>پیشرفت امروز</th><th>لید تخصیصی بازه</th><th>کل تماس</th><th>موفق</th><th>نرخ پاسخ</th><th>نرخ پیگیری موفق</th><th>نرخ تبدیل منصفانه</th><th>پیامک</th><th>مخاطب جاری</th><th>فروش بازه</th><th>ارزش فروش</th><th>عقب‌افتاده</th><th>آخرین فعالیت</th></tr></thead>
							<tbody>
							<?php if ( ! $team ) : ?>
								<tr><td colspan="14">کارشناسی تعریف نشده است.</td></tr>
							<?php else : foreach ( $team as $member ) :
								$a = $member['agent']; $r = $member['range']; $d = $member['today'];
								$pct = min( 100, max( 0, (int) $d['progress'] ) );
								$status = $d['target'] <= 0 ? 'بدون تارگت' : ( $d['progress'] >= 100 ? 'تکمیل‌شده' : ( $d['progress'] >= 80 ? 'نزدیک هدف' : ( $d['progress'] >= 50 ? 'در مسیر' : 'نیازمند توجه' ) ) );
								?>
								<tr>
									<td><b><?php echo esc_html( $a->name ); ?></b><?php echo (int) $a->active !== 1 ? '<br><span class="szc-muted">غیرفعال</span>' : ''; ?></td>
									<td>
										<div class="szc-goal-mini"><span><b><?php echo esc_html( szc_fa_digits( $d['answered'] ) ); ?></b> از <?php echo esc_html( szc_fa_digits( $d['target'] ) ); ?> تماس</span><i><u style="width:<?php echo (int) $pct; ?>%"></u></i><small><?php echo esc_html( $status ); ?><br>پیگیری <?php echo esc_html( szc_fa_digits( $d['followups'] ) . '/' . szc_fa_digits( $d['followup_target'] ) ); ?> · تبدیل <?php echo esc_html( szc_fa_digits( $d['won_contacts'] ) . '/' . szc_fa_digits( $d['conversion_target'] ) ); ?></small></div>
									</td>
									<td><?php echo esc_html( szc_fa_digits( $r['assigned_in_range'] ) ); ?></td>
									<td><?php echo esc_html( szc_fa_digits( $r['calls'] ) ); ?></td>
									<td><b><?php echo esc_html( szc_fa_digits( $r['answered'] ) ); ?></b></td>
									<td><?php echo esc_html( szc_fa_digits( $r['answer_rate'] ) ); ?>٪</td>
									<td><?php echo esc_html( szc_fa_digits( $r['followup_success_rate'] ) ); ?>٪</td>
									<td><?php echo esc_html( szc_fa_digits( $r['conversion_rate'] ) ); ?>٪</td>
									<td><?php echo esc_html( szc_fa_digits( $r['sms'] ) ); ?></td>
									<td><?php echo esc_html( szc_fa_digits( $r['assigned'] ) ); ?></td>
									<td><?php echo esc_html( szc_fa_digits( $r['won_contacts'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $r['revenue'], 0 ) ); ?></td>
									<td class="<?php echo $r['overdue'] > 0 ? 'szc-danger' : ''; ?>"><?php echo esc_html( szc_fa_digits( $r['overdue'] ) ); ?></td>
									<td class="szc-muted"><?php echo esc_html( $r['last_activity'] ? szc_format_mysql( $r['last_activity'] ) : '—' ); ?></td>
								</tr>
							<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
					<div class="szc-report-actions">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'szc_goal_summary_send' ); ?>
							<input type="hidden" name="action" value="szc_goal_summary_send">
							<button class="button button-primary">ارسال جمع‌بندی امروز برای مدیران</button>
						</form>
						<a class="button" href="<?php echo esc_url( self::url( 'szc-settings' ) . '#notifications' ); ?>">تنظیم اعلان‌ها و شماره مدیران</a>
						<a class="button" href="<?php echo esc_url( self::url( 'szc-agent-activity', array( 'period' => 7 ) ) ); ?>">ریز فعالیت کارشناسان</a>
					</div>
				</div>

				<div class="szc-card">
					<h2>جریان آخرین فعالیت‌های کارشناسان</h2>
					<p class="szc-muted">۵۰ فعالیت آخر تیم؛ برای بررسی جزئیات می‌توانید پرونده مخاطب را باز کنید.</p>
					<div class="szc-table-scroll">
						<table class="widefat striped szc-team-activity">
							<thead><tr><th>زمان</th><th>کارشناس</th><th>مخاطب</th><th>فعالیت</th><th>نتیجه/توضیح</th></tr></thead>
							<tbody>
							<?php if ( ! $recent_team ) : ?>
								<tr><td colspan="5">هنوز فعالیتی ثبت نشده است.</td></tr>
							<?php else :
								$type_labels = array( 'call' => 'تماس', 'followup' => 'پیگیری', 'sms' => 'پیامک', 'note' => 'یادداشت', 'stage' => 'تغییر مرحله', 'external' => 'تعامل واتساپ/تلگرام' );
								foreach ( $recent_team as $activity ) :
									$cname = trim( (string) $activity->first_name . ' ' . (string) $activity->last_name );
									if ( $cname === '' ) { $cname = szc_fa_digits( $activity->mobile ); }
									$outcome = $activity->type === 'call' ? SZC_Settings::outcome_label( $activity->outcome ) : $activity->outcome;
									?>
									<tr>
										<td class="szc-muted"><?php echo esc_html( szc_format_mysql( $activity->created_at ) ); ?></td>
										<td><b><?php echo esc_html( SZC_Auth::display_name( $activity->user_id ) ); ?></b></td>
										<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'szc-contacts', 'contact' => (int) $activity->contact_id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $cname ); ?></a></td>
										<td><?php echo esc_html( $type_labels[ $activity->type ] ?? $activity->type ); ?></td>
										<td><?php echo esc_html( trim( $outcome . ( $activity->body ? ' — ' . $activity->body : '' ) ) ?: '—' ); ?></td>
									</tr>
								<?php endforeach; endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			<?php endif; ?>

			<div class="szc-single-grid">
				<div class="szc-col">
					<div class="szc-card">
						<h2>قیف فروش</h2>
						<div class="szc-funnel">
							<?php foreach ( $funnel['stages'] as $s ) :
								$w = $maxstage > 0 ? round( $s['count'] / $maxstage * 100 ) : 0; ?>
								<div class="szc-funnel-row">
									<span class="szc-funnel-lbl"><?php echo esc_html( $s['label'] ); ?></span>
									<span class="szc-funnel-bar"><span style="width:<?php echo (int) $w; ?>%"></span></span>
									<span class="szc-funnel-n"><?php echo esc_html( szc_fa_digits( $s['count'] ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
						<p class="szc-muted">مجموع: <?php echo esc_html( szc_fa_digits( $funnel['total'] ) ); ?> · ثبت‌نام: <?php echo esc_html( szc_fa_digits( $funnel['registered'] ) ); ?></p>
					</div>
				</div>
				<div class="szc-col">
					<div class="szc-card">
						<h2>تماس‌های ۷ روز اخیر (بر اساس نتیجه)</h2>
						<ul class="szc-queue-stats">
							<?php foreach ( $byout as $o ) : ?>
								<li><?php echo esc_html( $o['label'] ); ?>: <b><?php echo esc_html( szc_fa_digits( $o['count'] ) ); ?></b></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php if ( $board ) : ?>
					<div class="szc-card">
						<h2>عملکرد کارشناسان (تماسِ امروز)</h2>
						<ul class="szc-queue-stats">
							<?php foreach ( $board as $b ) : ?>
								<li><?php echo esc_html( $b['name'] ); ?>: <b><?php echo esc_html( szc_fa_digits( $b['calls'] ) ); ?></b></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public static function handle_goal_summary_send() {
		self::guard();
		if ( ! SZC_Settings::is_manager() ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		check_admin_referer( 'szc_goal_summary_send' );
		$res = SZC_Goals::maybe_send_daily_summary( true );
		set_transient( 'szc_goal_summary_' . SZC_Auth::actor_id(), $res['message'], 60 );
		wp_safe_redirect( self::url( 'szc-reports' ) );
		exit;
	}

	/* ==================== خروجی CSV ==================== */

	public static function handle_export() {
		self::guard();
		check_admin_referer( 'szc_export' );
		$rows    = SZC_Contacts::rows_matching( self::filter_args_from_post() );
		$customs = SZC_Settings::custom_fields();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sazan-crm-' . wp_date( 'Y-m-d-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM برای اکسل فارسی
		$head = array( 'id', 'نام', 'نام خانوادگی', 'موبایل', 'شغل', 'شرکت', 'شهر', 'ایمیل', 'منبع', 'برچسب‌ها', 'اولویت', 'مرحله', 'کارشناس', 'لغو پیامک', 'آخرین تماس', 'پیگیری بعدی' );
		foreach ( $customs as $cf ) { $head[] = $cf['label']; }
		fputcsv( $out, $head );

		foreach ( $rows as $c ) {
			$owner = $c->owner_id ? get_userdata( $c->owner_id ) : null;
			$meta  = SZC_Contacts::get_meta( $c );
			$line  = array(
				$c->id, $c->first_name, $c->last_name, $c->mobile, $c->job, $c->company, $c->city, $c->email,
				$c->source, $c->tags, SZC_Settings::priority_meta( $c->priority )['label'], SZC_Settings::stage_label( $c->stage ),
				$owner ? $owner->display_name : '', $c->opt_out ? 'بله' : '', $c->last_contacted_at, $c->next_followup_at,
			);
			foreach ( $customs as $cf ) { $line[] = $meta[ $cf['key'] ] ?? ''; }
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}

	/* ==================== مخاطبین تکراری ==================== */

	public static function page_duplicates() {
		self::guard();
		$groups = SZC_Contacts::find_duplicate_groups( 100 );
		?>
		<div class="wrap szc-wrap">
			<h1>مخاطبین تکراری</h1>
			<?php $bmsg = get_transient( 'szc_bulk_' . SZC_Auth::actor_id() ); if ( $bmsg ) { delete_transient( 'szc_bulk_' . SZC_Auth::actor_id() ); echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $bmsg ) . '</p></div>'; } ?>
			<p class="szc-muted">گروه‌های زیر نامِ کاملِ یکسان دارند. برای هر گروه، رکوردِ «اصلی» را انتخاب کنید تا بقیه در آن ادغام شوند (سوابق منتقل و رکوردهای دیگر حذف می‌شوند).</p>
			<?php if ( ! $groups ) : ?>
				<div class="szc-card"><p class="szc-muted">مورد تکراریِ آشکاری پیدا نشد. 👌</p></div>
			<?php else : foreach ( $groups as $gi => $g ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="szc-card szc-dupgroup">
					<?php wp_nonce_field( 'szc_merge_group' ); ?>
					<input type="hidden" name="action" value="szc_merge_group">
					<h2><?php echo esc_html( SZC_Contacts::full_name( $g[0] ) ); ?> — <?php echo esc_html( szc_fa_digits( count( $g ) ) ); ?> رکورد</h2>
					<table class="widefat striped"><thead><tr><th>اصلی</th><th>موبایل</th><th>مرحله</th><th>شرکت</th><th>آخرین تماس</th></tr></thead><tbody>
						<?php foreach ( $g as $i => $c ) : ?>
							<tr>
								<td><label><input type="radio" name="primary" value="<?php echo (int) $c->id; ?>" <?php checked( $i, 0 ); ?>> #<?php echo (int) $c->id; ?></label>
									<input type="hidden" name="ids[]" value="<?php echo (int) $c->id; ?>"></td>
								<td dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></td>
								<td><?php echo esc_html( SZC_Settings::stage_label( $c->stage ) ); ?></td>
								<td><?php echo esc_html( $c->company ?: '—' ); ?></td>
								<td class="szc-muted"><?php echo esc_html( $c->last_contacted_at ? szc_format_mysql( $c->last_contacted_at ) : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody></table>
					<p style="margin-top:10px"><button class="button button-primary" onclick="return confirm('ادغام این گروه؟')">ادغام گروه</button></p>
				</form>
			<?php endforeach; endif; ?>
		</div>
		<?php
	}

	public static function handle_merge_group() {
		self::guard();
		check_admin_referer( 'szc_merge_group' );
		$primary = absint( $_POST['primary'] ?? 0 );
		$ids     = array_map( 'intval', (array) ( $_POST['ids'] ?? array() ) );
		$merged  = 0;
		foreach ( $ids as $id ) {
			if ( $id && $id !== $primary ) {
				$r = SZC_Contacts::merge( $primary, $id );
				if ( ! empty( $r['ok'] ) ) { $merged++; }
			}
		}
		set_transient( 'szc_bulk_' . SZC_Auth::actor_id(), szc_fa_digits( $merged ) . ' رکورد ادغام شد.', 60 );
		wp_safe_redirect( self::url( 'szc-duplicates' ) );
		exit;
	}

	/* ==================== مراحل، اولویت‌ها و فیلدهای سفارشی ==================== */

	public static function page_pipeline() {
		self::guard();
		$stages  = SZC_Settings::stages();
		$prios   = SZC_Settings::priorities();
		$customs = SZC_Settings::custom_fields();
		$counts  = SZC_Contacts::counts_by_stage();
		?>
		<div class="wrap szc-wrap">
			<h1>مراحل، اولویت‌ها و فیلدهای سفارشی</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?><div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'szc_pipeline_save' ); ?>
				<input type="hidden" name="action" value="szc_pipeline_save">

				<div class="szc-single-grid">
					<div class="szc-col">
						<div class="szc-card">
							<h2>مراحل قیف فروش</h2>
							<p class="szc-muted">عنوان‌ها را ویرایش کنید یا مرحله‌ی جدید در ردیف‌های خالی بیفزایید. حذفِ مرحله‌ای که مخاطب دارد ممکن نیست (تعداد نشان داده شده).</p>
							<table class="widefat"><thead><tr><th>عنوان مرحله</th><th>تعداد</th><th>حذف</th></tr></thead><tbody>
								<?php foreach ( $stages as $k => $lbl ) : $inuse = $counts[ $k ] ?? 0; ?>
									<tr>
										<td><input type="hidden" name="stage_key[]" value="<?php echo esc_attr( $k ); ?>"><input type="text" name="stage_label[]" value="<?php echo esc_attr( $lbl ); ?>" class="regular-text"></td>
										<td><?php echo esc_html( szc_fa_digits( $inuse ) ); ?></td>
										<td><?php if ( ! $inuse ) : ?><label><input type="checkbox" name="stage_del[]" value="<?php echo esc_attr( $k ); ?>"> حذف</label><?php else : ?><span class="szc-muted">—</span><?php endif; ?></td>
									</tr>
								<?php endforeach; ?>
								<?php for ( $i = 0; $i < 3; $i++ ) : ?>
									<tr><td><input type="hidden" name="stage_key[]" value=""><input type="text" name="stage_label[]" value="" class="regular-text" placeholder="مرحله‌ی جدید…"></td><td>—</td><td>—</td></tr>
								<?php endfor; ?>
							</tbody></table>
						</div>

						<div class="szc-card">
							<h2>اولویت‌ها</h2>
							<table class="widefat"><thead><tr><th>عنوان</th><th>رنگ</th><th>حذف</th></tr></thead><tbody>
								<?php foreach ( $prios as $k => $m ) : ?>
									<tr>
										<td><input type="hidden" name="prio_key[]" value="<?php echo esc_attr( $k ); ?>"><input type="text" name="prio_label[]" value="<?php echo esc_attr( $m['label'] ); ?>"></td>
										<td><input type="color" name="prio_color[]" value="<?php echo esc_attr( $m['color'] ); ?>"></td>
										<td><label><input type="checkbox" name="prio_del[]" value="<?php echo esc_attr( $k ); ?>"> حذف</label></td>
									</tr>
								<?php endforeach; ?>
								<?php for ( $i = 0; $i < 2; $i++ ) : ?>
									<tr><td><input type="hidden" name="prio_key[]" value=""><input type="text" name="prio_label[]" value="" placeholder="اولویت جدید…"></td><td><input type="color" name="prio_color[]" value="#888888"></td><td>—</td></tr>
								<?php endfor; ?>
							</tbody></table>
						</div>
					</div>

					<div class="szc-col">
						<div class="szc-card">
							<h2>فیلدهای سفارشی مخاطب</h2>
							<p class="szc-muted">فیلدهای اضافه‌ای که می‌خواهید برای هر مخاطب ثبت کنید (مثلاً «کد ملی»، «منطقه»). در فرم مخاطب، خروجی CSV و متغیر پیامک (<code>%key%</code>) در دسترس‌اند.</p>
							<table class="widefat"><thead><tr><th>عنوان</th><th>کلید (لاتین)</th></tr></thead><tbody>
								<?php $rowsn = max( 6, count( $customs ) + 3 );
								for ( $i = 0; $i < $rowsn; $i++ ) : $f = $customs[ $i ] ?? array( 'key' => '', 'label' => '' ); ?>
									<tr>
										<td><input type="text" name="cf_label[]" value="<?php echo esc_attr( $f['label'] ); ?>"></td>
										<td><input type="text" dir="ltr" name="cf_key[]" value="<?php echo esc_attr( $f['key'] ); ?>" placeholder="مثلاً national_id"></td>
									</tr>
								<?php endfor; ?>
							</tbody></table>
							<p class="szc-muted">اگر «کلید» خالی باشد، از روی عنوان ساخته می‌شود. تغییرِ کلیدِ یک فیلد، مقادیر قبلی را جدا می‌کند.</p>
						</div>
					</div>
				</div>
				<p><button class="button button-primary">ذخیره</button></p>
			</form>
		</div>
		<?php
	}

	public static function handle_pipeline_save() {
		self::guard();
		check_admin_referer( 'szc_pipeline_save' );
		$p = wp_unslash( $_POST );

		// مراحل.
		$del_stage = array_map( 'sanitize_key', (array) ( $p['stage_del'] ?? array() ) );
		$s_keys    = (array) ( $p['stage_key'] ?? array() );
		$s_labels  = (array) ( $p['stage_label'] ?? array() );
		$stages    = array();
		foreach ( $s_labels as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( $label === '' ) { continue; }
			$key = sanitize_key( $s_keys[ $i ] ?? '' );
			if ( $key === '' ) { $key = self::slug_key( $label, $stages ); }
			if ( in_array( $key, $del_stage, true ) ) { continue; }
			$stages[ $key ] = $label;
		}
		if ( ! $stages ) { $stages = SZC_Settings::default_stages(); }

		// اولویت‌ها.
		$del_prio = array_map( 'sanitize_key', (array) ( $p['prio_del'] ?? array() ) );
		$pk       = (array) ( $p['prio_key'] ?? array() );
		$pl       = (array) ( $p['prio_label'] ?? array() );
		$pc       = (array) ( $p['prio_color'] ?? array() );
		$prios    = array();
		foreach ( $pl as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( $label === '' ) { continue; }
			$key = sanitize_key( $pk[ $i ] ?? '' );
			if ( $key === '' ) { $key = self::slug_key( $label, $prios ); }
			if ( in_array( $key, $del_prio, true ) ) { continue; }
			$color = sanitize_hex_color( $pc[ $i ] ?? '' ) ?: '#888888';
			$prios[ $key ] = array( 'label' => $label, 'color' => $color );
		}
		if ( ! $prios ) { $prios = SZC_Settings::default_priorities(); }

		// فیلدهای سفارشی.
		$cf_key   = (array) ( $p['cf_key'] ?? array() );
		$cf_label = (array) ( $p['cf_label'] ?? array() );
		$customs  = array();
		$seen     = array();
		foreach ( $cf_label as $i => $label ) {
			$label = sanitize_text_field( $label );
			if ( $label === '' ) { continue; }
			$key = sanitize_key( $cf_key[ $i ] ?? '' );
			if ( $key === '' ) { $key = self::slug_key( $label, array_flip( $seen ) ); }
			if ( $key === '' || isset( $seen[ $key ] ) ) { continue; }
			$seen[ $key ] = true;
			$customs[]    = array( 'key' => $key, 'label' => $label );
		}

		SZC_Settings::save_pipeline( $stages, $prios, $customs );
		wp_safe_redirect( self::url( 'szc-pipeline', array( 'msg' => 1 ) ) );
		exit;
	}

	/** ساخت کلیدِ لاتینِ یکتا از یک عنوان (فارسی → transliterate ساده/تصادفی). */
	protected static function slug_key( $label, $existing ) {
		$slug = sanitize_key( str_replace( ' ', '_', $label ) );
		if ( $slug === '' ) {
			$slug = 'f_' . substr( md5( $label ), 0, 6 );
		}
		$base = $slug; $i = 2;
		while ( array_key_exists( $slug, (array) $existing ) ) {
			$slug = $base . '_' . $i; $i++;
		}
		return $slug;
	}
}
