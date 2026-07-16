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
		add_action( 'admin_post_szc_merge_group',     array( __CLASS__, 'handle_merge_group' ) );
		add_action( 'admin_post_szc_pipeline_save',   array( __CLASS__, 'handle_pipeline_save' ) );
		add_action( 'wp_ajax_szc_test_sms',           array( __CLASS__, 'ajax_test_sms' ) );
		add_action( 'wp_ajax_szc_broadcast_count',    array( __CLASS__, 'ajax_broadcast_count' ) );
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
		if ( ! SZC_Settings::is_manager() ) {
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
		$mode = SZC_Settings::get( 'sms_mode' );
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
							<p class="szc-muted">متغیرها: <code>%first%</code> نام، <code>%name%</code> نام کامل، <code>%company%</code> شرکت، <code>%job%</code> شغل، <code>%city%</code> شهر، <code>%mini%</code> لینک مینی‌دوره، <code>%intro%</code> لینک معارفه.</p>
							<?php if ( $mode === 'pattern' ) : ?>
								<p><label>کد پترن (اختیاری، برای خط خدماتی)<br><input type="text" name="pattern_code" dir="ltr" class="regular-text" value="<?php echo esc_attr( $row->pattern_code ?? '' ); ?>"></label>
								<br><span class="szc-muted">در حالت پترن، مقادیر با نام متغیرهای بالا ارسال می‌شوند.</span></p>
							<?php endif; ?>
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
										<div><b><?php echo esc_html( $t->name ); ?></b><p class="szc-muted"><?php echo esc_html( wp_trim_words( $t->body, 20 ) ); ?></p></div>
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
		if ( $id ) {
			SZC_Templates::update( $id, $name, $body, $pat );
		} else {
			SZC_Templates::create( $name, $body, $pat );
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

	public static function page_settings() {
		self::guard();
		$s         = SZC_Settings::all();
		$templates = SZC_Templates::all();
		$staff     = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'shop_manager', 'contributor' ), 'number' => 500, 'fields' => array( 'ID', 'display_name', 'user_login' ) ) );
		$managers  = SZC_Settings::manager_ids();
		$sms_ok    = SZC_SMS::enabled();

		$tabs = array(
			'access'     => array( 'دسترسی و نقش‌ها', 'users' ),
			'sms'        => array( 'پنل پیامک', 'mail' ),
			'auto'       => array( 'اتوماسیون پیامک', 'sparkles' ),
			'remind'     => array( 'یادآوری‌ها', 'bell' ),
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
									<p class="szc-hint">کارشناسان با «موبایل + رمز» واردِ پورتال می‌شوند (بدونِ کاربرِ وردپرس). ساخت و مدیریتِ کارشناسان از منوی «کارشناسان».</p>
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
							<div class="szc-field szc-field--col"><label>حالت ارسال</label><div class="szc-field-c">
								<select name="sms_mode">
									<option value="text" <?php selected( $s['sms_mode'], 'text' ); ?>>متن آزاد</option>
									<option value="pattern" <?php selected( $s['sms_mode'], 'pattern' ); ?>>پترن (خدماتی)</option>
								</select>
							</div></div>
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

	public static function handle_settings_save() {
		self::guard();
		check_admin_referer( 'szc_settings_save' );
		$p   = wp_unslash( $_POST );
		$new = array(
			'managers'         => array_map( 'intval', (array) ( $p['managers'] ?? array() ) ),
			'pass_login'       => empty( $p['pass_login'] ) ? 0 : 1,
			'max_per_run'      => max( 1, absint( $p['max_per_run'] ?? 80 ) ),
			'max_per_day'      => max( 0, absint( $p['max_per_day'] ?? 0 ) ),
			'sms_enabled'      => empty( $p['sms_enabled'] ) ? 0 : 1,
			'sms_provider'     => ( ( $p['sms_provider'] ?? 'ippanel' ) === 'smsir' ) ? 'smsir' : 'ippanel',
			'sms_base'         => esc_url_raw( $p['sms_base'] ?? '' ),
			'sms_apikey'       => sanitize_text_field( $p['sms_apikey'] ?? '' ),
			'sms_originator'   => sanitize_text_field( $p['sms_originator'] ?? '' ),
			'sms_mode'         => ( ( $p['sms_mode'] ?? 'text' ) === 'pattern' ) ? 'pattern' : 'text',
			'send_from'        => min( 23, max( 0, absint( $p['send_from'] ?? 9 ) ) ),
			'send_to'          => min( 24, max( 1, absint( $p['send_to'] ?? 21 ) ) ),
			'auto_after_call'  => empty( $p['auto_after_call'] ) ? 0 : 1,
			'auto_template_id' => absint( $p['auto_template_id'] ?? 0 ),
			'auto_delay_min'   => max( 1, absint( $p['auto_delay_min'] ?? 60 ) ),
			'mini_link'        => esc_url_raw( $p['mini_link'] ?? '' ),
			'intro_link'       => esc_url_raw( $p['intro_link'] ?? '' ),
			'followup_remind'       => empty( $p['followup_remind'] ) ? 0 : 1,
			'followup_remind_email' => empty( $p['followup_remind_email'] ) ? 0 : 1,
		);
		$omap = array();
		foreach ( (array) ( $p['outcome_templates'] ?? array() ) as $ok => $tid ) {
			$tid = absint( $tid );
			if ( $tid > 0 ) {
				$omap[ sanitize_key( $ok ) ] = $tid;
			}
		}
		$new['outcome_templates'] = $omap;
		SZC_Settings::save( $new );

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
		);
		?>
		<div class="wrap szc-wrap szc-settings">
			<div class="szc-settings-hero">
				<div>
					<h1>کارشناسانِ فروش</h1>
					<p class="szc-muted">کارشناسان مستقل از وردپرس‌اند: با «موبایل + رمز» واردِ پورتال می‌شوند و هیچ کاربرِ وردپرسی ساخته نمی‌شود.</p>
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
						<div class="szc-scard-h"><h2><?php echo $row ? 'ویرایشِ کارشناس' : 'افزودنِ کارشناسِ جدید'; ?></h2><p><?php echo $row ? 'نام/موبایل را ویرایش کنید یا رمزِ تازه بگذارید.' : 'با نام، موبایل و رمز یک کارشناس بسازید. موبایل، نام‌کاربریِ ورودِ اوست.'; ?></p></div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'szc_agent_save' ); ?>
							<input type="hidden" name="action" value="szc_agent_save">
							<input type="hidden" name="id" value="<?php echo (int) ( $row->id ?? 0 ); ?>">
							<div class="szc-field szc-field--col"><label>نام کارشناس</label><div class="szc-field-c"><input type="text" name="name" required value="<?php echo esc_attr( $row->name ?? '' ); ?>" placeholder="مثلاً: مریم احمدی"></div></div>
							<div class="szc-field szc-field--col"><label>موبایل (ورود)</label><div class="szc-field-c"><input type="text" name="mobile" required dir="ltr" value="<?php echo esc_attr( $row->mobile ?? '' ); ?>" placeholder="۰۹۱۲..."></div></div>
							<div class="szc-field szc-field--col"><label><?php echo $row ? 'رمزِ تازه (اختیاری)' : 'رمز ورود'; ?></label><div class="szc-field-c"><input type="text" name="pass" autocomplete="off" <?php echo $row ? '' : 'required'; ?> placeholder="<?php echo $row ? 'خالی = بدون تغییر' : 'رمز دلخواه'; ?>"></div></div>
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
							<thead><tr><th>نام</th><th>موبایل</th><th>وضعیت</th><th>اقدام</th></tr></thead>
							<tbody>
							<?php if ( ! $agents ) : ?>
								<tr><td colspan="4" class="szc-muted">هنوز کارشناسی ساخته نشده است.</td></tr>
							<?php else : foreach ( $agents as $a ) : ?>
								<tr>
									<td><b><?php echo esc_html( $a->name ); ?></b></td>
									<td dir="ltr"><?php echo esc_html( szc_fa_digits( $a->mobile ) ); ?></td>
									<td><?php echo (int) $a->active === 1 ? '<span class="szc-ok">فعال</span>' : '<span class="szc-muted">غیرفعال</span>'; ?></td>
									<td class="szc-agent-ops">
										<a class="button-link" href="<?php echo esc_url( self::url( 'szc-agents', array( 'edit' => $a->id ) ) ); ?>">ویرایش</a>
										<a class="button-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=szc_agent_toggle&id=' . $a->id ), 'szc_agent_toggle_' . $a->id ) ); ?>"><?php echo (int) $a->active === 1 ? 'غیرفعال‌کن' : 'فعال‌کن'; ?></a>
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
		$pass   = (string) ( $p['pass'] ?? '' );
		$active = empty( $p['active'] ) ? 0 : 1;

		if ( $id ) {
			$res = SZC_Agents::update( $id, $name, $mobile );
			if ( empty( $res['ok'] ) ) {
				wp_safe_redirect( self::url( 'szc-agents', array( 'edit' => $id, 'm' => 'bad' ) ) );
				exit;
			}
			SZC_Agents::set_active( $id, $active );
			if ( $pass !== '' ) {
				SZC_Agents::set_password( $id, $pass );
			}
		} else {
			$res = SZC_Agents::create( $name, $mobile, $pass, $active );
			if ( empty( $res['ok'] ) ) {
				$m = ( strpos( (string) $res['msg'], 'موبایل' ) !== false && strpos( (string) $res['msg'], 'وجود' ) !== false ) ? 'dup' : 'bad';
				wp_safe_redirect( self::url( 'szc-agents', array( 'm' => $m ) ) );
				exit;
			}
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
		$res = SZC_SMS::send_text( $to, 'پیامک آزمایشی سازان CRM ✓' );
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
		if ( ! SZC_Settings::is_manager() ) {
			$a['owner'] = SZC_Auth::actor_id();
		}
		return $a;
	}

	public static function page_broadcast() {
		self::guard();
		$s          = SZC_Settings::all();
		$templates  = SZC_Templates::all();
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
			<p class="szc-muted">یک پیام (قالبی یا متنِ آزاد) را یک‌جا برای گروهی از مخاطبین بفرستید. ارسال از طریقِ صف و در «بازه‌ی مجاز ارسال» انجام می‌شود؛ مخاطبینِ «لغو دریافت» و «لیست سیاه» به‌صورت خودکار کنار گذاشته می‌شوند.</p>

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
							<h2>۱) متنِ پیام</h2>
							<div class="szc-seg" role="tablist">
								<label class="szc-seg-opt"><input type="radio" name="msg_type" value="text" checked> متنِ آزاد</label>
								<label class="szc-seg-opt"><input type="radio" name="msg_type" value="template" <?php disabled( ! $templates ); ?>> قالبِ آماده</label>
							</div>

							<div data-msg="text" style="margin-top:12px">
								<textarea name="text" rows="5" class="large-text" placeholder="متنِ پیام… می‌توانید از متغیرها استفاده کنید."></textarea>
								<p class="szc-muted">متغیرها: <code>%first%</code> نام، <code>%name%</code> نام کامل، <code>%company%</code> شرکت، <code>%job%</code> شغل، <code>%city%</code> شهر، <code>%mini%</code> لینک مینی‌دوره، <code>%intro%</code> لینک معارفه.</p>
								<p class="szc-muted"><span data-char-count>۰</span> نویسه · حدود <span data-sms-count>۱</span> پیامک</p>
							</div>

							<div data-msg="template" hidden style="margin-top:12px">
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
		if ( ! SZC_Settings::can_access() ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ), 403 );
		}
		check_ajax_referer( 'szc_admin', 'nonce' );
		$args  = self::broadcast_filter_args( $_POST );
		$count = count( SZC_Contacts::ids_matching( $args ) );
		wp_send_json_success( array( 'count' => $count, 'fa' => szc_fa_digits( $count ) ) );
	}

	public static function handle_broadcast() {
		self::guard();
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

		if ( ( $p['msg_type'] ?? 'text' ) === 'template' ) {
			$tid = absint( $p['template_id'] ?? 0 );
			$r   = SZC_SMS::enqueue_template_bulk( $ids, $tid, $when );
		} else {
			$text = (string) ( $p['text'] ?? '' );
			$r    = SZC_SMS::enqueue_text_bulk( $ids, $text, $when );
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
		$templates = SZC_Templates::all();
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

	public static function page_reports() {
		self::guard();
		$owner   = SZC_Settings::scope_owner();
		$funnel  = SZC_Reports::funnel( $owner );
		$byout   = SZC_Reports::calls_by_outcome( 7 );
		$board   = SZC_Settings::is_manager() ? SZC_Reports::agent_leaderboard() : array();
		$maxstage = 1;
		foreach ( $funnel['stages'] as $s ) { $maxstage = max( $maxstage, $s['count'] ); }
		?>
		<div class="wrap szc-wrap">
			<h1>گزارش‌ها</h1>

			<div class="szc-kpis">
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( SZC_Reports::calls_today( $owner ) ) ); ?></span><span class="szc-kpi-l">تماس امروز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( SZC_Reports::sms_sent( 1 ) ) ); ?></span><span class="szc-kpi-l">پیامک امروز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( SZC_Reports::sms_sent( 7 ) ) ); ?></span><span class="szc-kpi-l">پیامک ۷ روز</span></div>
				<div class="szc-kpi"><span class="szc-kpi-n"><?php echo esc_html( szc_fa_digits( $funnel['conversion'] ) ); ?>٪</span><span class="szc-kpi-l">نرخ تبدیل</span></div>
			</div>

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
