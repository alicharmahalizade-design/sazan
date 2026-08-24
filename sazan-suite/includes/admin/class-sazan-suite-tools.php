<?php
/**
 * صفحه‌های «ماژول‌ها» و «ابزارها» کنترل پنل.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Tools {

	const SLUG_MODULES = 'sazan-suite-modules';
	const SLUG_TOOLS   = 'sazan-suite-tools';

	const ACTION_MODULES = 'sazan_suite_save_modules';
	const ACTION_EXPORT  = 'sazan_suite_export';
	const ACTION_IMPORT  = 'sazan_suite_import';
	const ACTION_RESET   = 'sazan_suite_reset_all';

	public static function init() {
		add_action( 'admin_post_' . self::ACTION_MODULES, array( __CLASS__, 'handle_modules' ) );
		add_action( 'admin_post_' . self::ACTION_EXPORT, array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_' . self::ACTION_IMPORT, array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_' . self::ACTION_RESET, array( __CLASS__, 'handle_reset_all' ) );
	}

	/* =====================================================================
	 * صفحه‌ی ماژول‌ها
	 * =================================================================== */

	public static function render_modules() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		$states = Sazan_Suite_Modules::states();
		?>
		<div class="wrap szs-wrap" dir="rtl">
			<div class="szs-hero szs-hero--slim">
				<div class="szs-hero__text">
					<span class="szs-hero__kicker">سازان سوئیت</span>
					<h1>ماژول‌ها</h1>
					<p>هر بخش از سوئیت را می‌توانید مستقل روشن یا خاموش کنید. خاموش‌کردن یک ماژول داده‌های آن را پاک نمی‌کند.</p>
				</div>
			</div>

			<?php if ( isset( $_GET['szs-modules'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p>وضعیت ماژول‌ها ذخیره شد.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_MODULES ); ?>">
				<?php wp_nonce_field( self::ACTION_MODULES ); ?>

				<div class="szs-modules">
					<?php foreach ( Sazan_Suite_Modules::all() as $slug => $module ) : ?>
						<?php
						$on      = ! empty( $states[ $slug ] );
						$loaded  = Sazan_Suite_Modules::is_loaded( $slug );
						$missing = Sazan_Suite_Modules::missing_dependencies( $slug );
						?>
						<div class="szs-module <?php echo $on ? 'is-on' : ''; ?>">
							<div class="szs-module__icon"><span class="dashicons <?php echo esc_attr( $module['icon'] ); ?>"></span></div>
							<div class="szs-module__body">
								<h3><?php echo esc_html( $module['label'] ); ?></h3>
								<p><?php echo esc_html( $module['description'] ); ?></p>

								<?php if ( $missing ) : ?>
									<p class="szs-module__warn">
										<span class="dashicons dashicons-warning"></span>
										نیازمند نصب و فعال‌بودن:
										<?php
										$labels = array_map( array( 'Sazan_Suite_Modules', 'dependency_label' ), $missing );
										echo esc_html( implode( '، ', $labels ) );
										?>
									</p>
								<?php elseif ( $on && ! $loaded ) : ?>
									<p class="szs-module__warn">
										<span class="dashicons dashicons-warning"></span>
										روشن است اما بارگذاری نشد — احتمالاً نسخه‌ی مستقل «<?php echo esc_html( $module['legacy_name'] ); ?>» هنوز فعال است.
									</p>
								<?php elseif ( $loaded ) : ?>
									<p class="szs-module__ok"><span class="dashicons dashicons-yes-alt"></span> فعال و در حال اجرا</p>
								<?php endif; ?>
							</div>
							<label class="szs-switch szs-switch--lg">
								<input type="hidden" name="modules[<?php echo esc_attr( $slug ); ?>]" value="0">
								<input type="checkbox" name="modules[<?php echo esc_attr( $slug ); ?>]" value="1" <?php checked( $on ); ?>>
								<span class="szs-switch__track"></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<p class="szs-actions"><button type="submit" class="button button-primary button-hero">ذخیره وضعیت ماژول‌ها</button></p>
			</form>
		</div>
		<?php
	}

	public static function handle_modules() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		check_admin_referer( self::ACTION_MODULES );

		$input = isset( $_POST['modules'] ) ? (array) wp_unslash( $_POST['modules'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		Sazan_Suite_Modules::save_states( $input );

		wp_safe_redirect( add_query_arg(
			array( 'page' => self::SLUG_MODULES, 'szs-modules' => 1 ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/* =====================================================================
	 * صفحه‌ی ابزارها
	 * =================================================================== */

	public static function render_tools() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}
		?>
		<div class="wrap szs-wrap" dir="rtl">
			<div class="szs-hero szs-hero--slim">
				<div class="szs-hero__text">
					<span class="szs-hero__kicker">سازان سوئیت</span>
					<h1>ابزارها و پشتیبان</h1>
					<p>گرفتن پشتیبان از تنظیمات، بازگردانی آن روی سایت دیگر و بررسی سلامت نصب.</p>
				</div>
			</div>

			<?php self::tools_notices(); ?>

			<div class="szs-tools">

				<section class="szs-card">
					<header class="szs-card__head"><div>
						<h3>خروجی گرفتن از تنظیمات</h3>
						<p>یک فایل JSON شامل همه‌ی تنظیمات چهار ماژول و وضعیت روشن/خاموش آن‌ها.</p>
					</div></header>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_EXPORT ); ?>">
						<?php wp_nonce_field( self::ACTION_EXPORT ); ?>
						<p><button type="submit" class="button button-primary">دانلود فایل پشتیبان</button></p>
						<p class="szs-field__hint">توجه: کلیدهای API نیز داخل این فایل هستند؛ آن را در جای امن نگه دارید.</p>
					</form>
				</section>

				<section class="szs-card">
					<header class="szs-card__head"><div>
						<h3>بازگردانی پشتیبان</h3>
						<p>فایل JSON خروجی‌گرفته‌شده را انتخاب کنید. مقادیر فعلی بازنویسی می‌شوند.</p>
					</div></header>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_IMPORT ); ?>">
						<?php wp_nonce_field( self::ACTION_IMPORT ); ?>
						<p><input type="file" name="backup" accept="application/json,.json" required></p>
						<p><button type="submit" class="button button-primary">بازگردانی</button></p>
					</form>
				</section>

				<section class="szs-card">
					<header class="szs-card__head"><div>
						<h3>وضعیت سیستم</h3>
						<p>بررسی سریع پیش‌نیازها و مخزن‌های تنظیمات.</p>
					</div></header>
					<?php self::health_table(); ?>
				</section>

				<section class="szs-card szs-card--danger">
					<header class="szs-card__head"><div>
						<h3>بازگردانی همه‌ی تنظیمات به پیش‌فرض</h3>
						<p>فقط تنظیمات پاک می‌شوند؛ دوره‌ها، جلسات، مخاطبین و سایر داده‌ها دست‌نخورده می‌مانند.</p>
					</div></header>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						onsubmit="return confirm('همه‌ی تنظیمات هر چهار ماژول پاک می‌شود و به پیش‌فرض برمی‌گردد. ادامه می‌دهید؟');">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_RESET ); ?>">
						<?php wp_nonce_field( self::ACTION_RESET ); ?>
						<p>
							<label>
								<input type="text" name="confirm" class="szs-input" placeholder="برای تأیید بنویسید: بازنشانی" required>
							</label>
						</p>
						<p><button type="submit" class="button button-link-delete">بازنشانی همه تنظیمات</button></p>
					</form>
				</section>
			</div>
		</div>
		<?php
	}

	/** پیام‌های صفحه‌ی ابزارها. */
	private static function tools_notices() {

		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['szs-imported'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf( 'پشتیبان بازگردانده شد (%s مخزن).', number_format_i18n( (int) $_GET['szs-imported'] ) ) )
			);
		}

		if ( isset( $_GET['szs-reset-all'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>همه‌ی تنظیمات به پیش‌فرض بازگشت.</p></div>';
		}

		if ( isset( $_GET['szs-error'] ) ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['szs-error'] ) ) )
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/** جدول وضعیت سیستم. */
	private static function health_table() {

		$rows = array(
			array( 'نسخه سوئیت', SAZAN_SUITE_VERSION, true ),
			array( 'نسخه PHP', PHP_VERSION, version_compare( PHP_VERSION, '7.4', '>=' ) ),
			array( 'نسخه وردپرس', get_bloginfo( 'version' ), true ),
			array( 'المنتور', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'نصب نیست', Sazan_Suite_Modules::dependency_met( 'elementor' ) ),
			array( 'ووکامرس', class_exists( 'WooCommerce' ) && defined( 'WC_VERSION' ) ? WC_VERSION : 'نصب نیست', Sazan_Suite_Modules::dependency_met( 'woocommerce' ) ),
			array( 'کرون وردپرس', defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'غیرفعال (DISABLE_WP_CRON)' : 'فعال', ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ),
		);

		foreach ( Sazan_Suite_Modules::all() as $slug => $module ) {
			$loaded = Sazan_Suite_Modules::is_loaded( $slug );
			$rows[] = array(
				'ماژول: ' . $module['label'],
				$loaded ? 'بارگذاری شد' : ( Sazan_Suite_Modules::is_enabled( $slug ) ? 'روشن اما بارگذاری نشد' : 'خاموش' ),
				$loaded,
			);
		}

		echo '<table class="szs-health"><tbody>';

		foreach ( $rows as $row ) {
			printf(
				'<tr><th>%1$s</th><td>%2$s</td><td class="szs-health__state">%3$s</td></tr>',
				esc_html( $row[0] ),
				esc_html( $row[1] ),
				$row[2]
					? '<span class="dashicons dashicons-yes-alt szs-ok"></span>'
					: '<span class="dashicons dashicons-warning szs-warn"></span>'
			);
		}

		echo '</tbody></table>';
	}

	/* =====================================================================
	 * خروجی / ورودی / بازنشانی
	 * =================================================================== */

	public static function handle_export() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		check_admin_referer( self::ACTION_EXPORT );

		$data     = Sazan_Suite_Store::export();
		$filename = 'sazan-suite-settings-' . gmdate( 'Ymd-His' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public static function handle_import() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		check_admin_referer( self::ACTION_IMPORT );

		if ( empty( $_FILES['backup']['tmp_name'] ) || ! is_uploaded_file( $_FILES['backup']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			self::bail( 'فایلی دریافت نشد.' );
		}

		$contents = file_get_contents( $_FILES['backup']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.Security.ValidatedSanitizedInput
		$data     = json_decode( (string) $contents, true );

		if ( ! is_array( $data ) || empty( $data['stores'] ) ) {
			self::bail( 'فایل پشتیبان معتبر نیست.' );
		}

		$count = Sazan_Suite_Store::import( $data );

		wp_safe_redirect( add_query_arg(
			array( 'page' => self::SLUG_TOOLS, 'szs-imported' => $count ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	public static function handle_reset_all() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		check_admin_referer( self::ACTION_RESET );

		$confirm = isset( $_POST['confirm'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) ) : '';

		if ( 'بازنشانی' !== $confirm ) {
			self::bail( 'برای تأیید باید دقیقاً کلمه‌ی «بازنشانی» را بنویسید.' );
		}

		foreach ( array_keys( Sazan_Suite_Store::map() ) as $store ) {
			Sazan_Suite_Store::reset( $store );
		}

		wp_safe_redirect( add_query_arg(
			array( 'page' => self::SLUG_TOOLS, 'szs-reset-all' => 1 ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/** بازگشت به صفحه‌ی ابزارها با پیام خطا. */
	private static function bail( $message ) {

		wp_safe_redirect( add_query_arg(
			array( 'page' => self::SLUG_TOOLS, 'szs-error' => rawurlencode( $message ) ),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
