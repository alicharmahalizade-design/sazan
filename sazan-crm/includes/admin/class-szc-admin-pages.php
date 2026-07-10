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
		add_action( 'wp_ajax_szc_test_sms',           array( __CLASS__, 'ajax_test_sms' ) );
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
		$stats = get_transient( 'szc_import_' . get_current_user_id() );
		if ( $stats ) {
			delete_transient( 'szc_import_' . get_current_user_id() );
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
		set_transient( 'szc_import_' . get_current_user_id(), $res['stats'], 60 );
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
		$staff     = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'shop_manager' ), 'number' => 500, 'fields' => array( 'ID', 'display_name', 'user_login' ) ) );
		$allowed   = SZC_Settings::allowed_user_ids();
		?>
		<div class="wrap szc-wrap">
			<h1>تنظیمات سازان CRM</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?><div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div><?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'szc_settings_save' ); ?>
				<input type="hidden" name="action" value="szc_settings_save">

				<h2>دسترسی تیم فروش</h2>
				<table class="form-table"><tbody>
					<tr><th>کاربران دارای دسترسی</th><td>
						<select name="access_users[]" multiple size="6" style="min-width:320px">
							<?php foreach ( $staff as $u ) : ?>
								<option value="<?php echo (int) $u->ID; ?>" <?php echo in_array( (int) $u->ID, $allowed, true ) ? 'selected' : ''; ?>><?php echo esc_html( $u->display_name . ' (' . $u->user_login . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">مدیران سایت همیشه دسترسی دارند. این‌جا اعضای تیم فروش را انتخاب کنید (Ctrl/Cmd برای چندتایی).</p>
					</td></tr>
				</tbody></table>

				<h2>پنل پیامک (فراز/آی‌پی‌پنل)</h2>
				<table class="form-table"><tbody>
					<tr><th>فعال‌سازی</th><td><label><input type="checkbox" name="sms_enabled" value="1" <?php checked( ! empty( $s['sms_enabled'] ) ); ?>> ارسال پیامک فعال باشد</label></td></tr>
					<tr><th>آدرس پایه API</th><td><input type="text" name="sms_base" value="<?php echo esc_attr( $s['sms_base'] ); ?>" class="regular-text" dir="ltr"></td></tr>
					<tr><th>کلید API</th><td><input type="text" name="sms_apikey" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" class="regular-text" dir="ltr" autocomplete="off"></td></tr>
					<tr><th>خط ارسال</th><td><input type="text" name="sms_originator" value="<?php echo esc_attr( $s['sms_originator'] ); ?>" class="regular-text" dir="ltr" placeholder="+983000..."></td></tr>
					<tr><th>حالت ارسال</th><td>
						<select name="sms_mode">
							<option value="text" <?php selected( $s['sms_mode'], 'text' ); ?>>متن آزاد</option>
							<option value="pattern" <?php selected( $s['sms_mode'], 'pattern' ); ?>>پترن (خدماتی)</option>
						</select>
						<p class="description">اگر تنظیمات پیامک را در «سازان پنل» دارید، همان مقادیر این‌جا هم پیش‌فرض آمده است.</p>
					</td></tr>
					<tr><th>بازه‌ی مجاز ارسال</th><td>
						از ساعت <input type="number" name="send_from" min="0" max="23" value="<?php echo esc_attr( $s['send_from'] ); ?>" class="small-text">
						تا ساعت <input type="number" name="send_to" min="1" max="24" value="<?php echo esc_attr( $s['send_to'] ); ?>" class="small-text">
						<p class="description">پیامک‌های خودکار فقط در این بازه ارسال می‌شوند (برای پرهیز از پیامک شبانه).</p>
					</td></tr>
				</tbody></table>

				<h2>اتوماسیون پس از تماس</h2>
				<table class="form-table"><tbody>
					<tr><th>فعال باشد</th><td><label><input type="checkbox" name="auto_after_call" value="1" <?php checked( ! empty( $s['auto_after_call'] ) ); ?>> پس از ثبت تماسِ موفق، پیامک تشکر/دعوت زمان‌بندی شود</label></td></tr>
					<tr><th>قالب پیش‌فرض</th><td>
						<select name="auto_template_id">
							<option value="0">— اولین قالب —</option>
							<?php foreach ( $templates as $t ) : ?>
								<option value="<?php echo (int) $t->id; ?>" <?php selected( (int) $s['auto_template_id'], (int) $t->id ); ?>><?php echo esc_html( $t->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td></tr>
					<tr><th>فاصله‌ی ارسال</th><td><input type="number" name="auto_delay_min" min="1" value="<?php echo esc_attr( $s['auto_delay_min'] ); ?>" class="small-text"> دقیقه پس از تماس (پیش‌فرض ۶۰)</td></tr>
					<tr><th>لینک مینی‌دوره</th><td><input type="url" name="mini_link" value="<?php echo esc_attr( $s['mini_link'] ); ?>" class="regular-text" dir="ltr"> <span class="szc-muted">متغیر <code>%mini%</code></span></td></tr>
					<tr><th>لینک جلسه‌ی معارفه</th><td><input type="url" name="intro_link" value="<?php echo esc_attr( $s['intro_link'] ); ?>" class="regular-text" dir="ltr"> <span class="szc-muted">متغیر <code>%intro%</code></span></td></tr>
				</tbody></table>

				<p><button class="button button-primary">ذخیره تنظیمات</button></p>
			</form>

			<hr>
			<h2>تست پیامک</h2>
			<p>
				<input type="text" id="szc-test-num" class="regular-text" dir="ltr" placeholder="۰۹۱۲...">
				<button type="button" class="button" id="szc-test-sms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szc_admin' ) ); ?>">ارسال پیامک تست</button>
				<span id="szc-test-msg" style="margin-inline-start:8px;font-weight:600"></span>
			</p>
		</div>
		<?php
	}

	public static function handle_settings_save() {
		self::guard();
		check_admin_referer( 'szc_settings_save' );
		$p   = wp_unslash( $_POST );
		$new = array(
			'access_users'     => array_map( 'intval', (array) ( $p['access_users'] ?? array() ) ),
			'sms_enabled'      => empty( $p['sms_enabled'] ) ? 0 : 1,
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
		);
		SZC_Settings::save( $new );
		wp_safe_redirect( self::url( 'szc-settings', array( 'msg' => 1 ) ) );
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
}
