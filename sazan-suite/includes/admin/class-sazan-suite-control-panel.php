<?php
/**
 * کنترل پنل یکپارچه‌ی تنظیمات سازان.
 *
 * همه‌ی تنظیمات چهار ماژول، دسته‌بندی‌شده و در یک صفحه.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Control_Panel {

	/** اسلاگ صفحه‌ی کنترل پنل. */
	const SLUG = 'sazan-suite';

	/** اکشن ذخیره. */
	const ACTION_SAVE = 'sazan_suite_save';

	/** اکشن بازنشانی یک بخش. */
	const ACTION_RESET = 'sazan_suite_reset_section';

	public static function init() {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_' . self::ACTION_RESET, array( __CLASS__, 'handle_reset' ) );
	}

	/* =====================================================================
	 * کمکی‌ها
	 * =================================================================== */

	/** نشانی صفحه‌ی کنترل پنل (اختیاری روی یک دسته). */
	public static function url( $tab = '', $args = array() ) {

		$args = array_merge(
			array( 'page' => self::SLUG ),
			$tab ? array( 'tab' => $tab ) : array(),
			$args
		);

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/** دسته‌ی فعال. */
	private static function current_tab() {

		$categories = Sazan_Suite_Schema::categories();
		$requested  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $requested && isset( $categories[ $requested ] ) ) {
			return $requested;
		}

		return (string) array_key_first( $categories );
	}

	/**
	 * آیا بخش قابل ویرایش است؟ (ماژولش بارگذاری شده باشد)
	 *
	 * @param array $section
	 * @return bool
	 */
	private static function section_available( array $section ) {

		if ( empty( $section['module'] ) ) {
			return true;
		}

		return Sazan_Suite_Modules::is_loaded( $section['module'] );
	}

	/**
	 * تعداد فیلدهای هر دسته (برای نمایش در فهرست کناری).
	 *
	 * @return array<string,int>
	 */
	private static function field_counts() {

		$out = array();

		foreach ( Sazan_Suite_Schema::categories() as $cat_id => $cat ) {
			$count = 0;
			foreach ( $cat['sections'] as $section ) {
				$count += count( $section['fields'] );
			}
			$out[ $cat_id ] = $count;
		}

		return $out;
	}

	/**
	 * فهرست جست‌وجوی سراسری برای جاوااسکریپت.
	 *
	 * @return array
	 */
	private static function search_index() {

		$index = array();

		foreach ( Sazan_Suite_Schema::categories() as $cat_id => $cat ) {
			foreach ( $cat['sections'] as $sec_id => $section ) {
				foreach ( $section['fields'] as $key => $def ) {
					$index[] = array(
						'c'  => $cat_id,
						'cl' => $cat['label'],
						's'  => $sec_id,
						'sl' => $section['label'],
						'k'  => $key,
						'l'  => $def['label'] ?? $key,
						'u'  => self::url( $cat_id ) . '#section-' . $sec_id,
					);
				}
			}
		}

		return $index;
	}

	/* =====================================================================
	 * رندر صفحه
	 * =================================================================== */

	public static function render() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		$categories = Sazan_Suite_Schema::categories();
		$tab        = self::current_tab();
		$category   = $categories[ $tab ];
		$counts     = self::field_counts();
		?>
		<div class="wrap szs-wrap" dir="rtl">

			<?php self::header( $tab ); ?>
			<?php self::notices(); ?>

			<div class="szs-layout">

				<aside class="szs-nav" aria-label="دسته‌های تنظیمات">
					<div class="szs-search">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input type="search" id="szs-search" class="szs-search__input" placeholder="جست‌وجو در همه تنظیمات…" autocomplete="off">
						<div class="szs-search__results" id="szs-search-results" hidden></div>
					</div>

					<ul class="szs-nav__list">
						<?php foreach ( $categories as $cat_id => $cat ) : ?>
							<li>
								<a class="szs-nav__item <?php echo $cat_id === $tab ? 'is-active' : ''; ?>"
									href="<?php echo esc_url( self::url( $cat_id ) ); ?>">
									<span class="dashicons <?php echo esc_attr( $cat['icon'] ); ?>" aria-hidden="true"></span>
									<span class="szs-nav__label"><?php echo esc_html( $cat['label'] ); ?></span>
									<span class="szs-nav__count"><?php echo esc_html( number_format_i18n( $counts[ $cat_id ] ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

					<ul class="szs-nav__list szs-nav__list--extra">
						<li>
							<a class="szs-nav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Sazan_Suite_Tools::SLUG_MODULES ) ); ?>">
								<span class="dashicons dashicons-screenoptions" aria-hidden="true"></span>
								<span class="szs-nav__label">ماژول‌ها</span>
							</a>
						</li>
						<li>
							<a class="szs-nav__item" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Sazan_Suite_Tools::SLUG_TOOLS ) ); ?>">
								<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
								<span class="szs-nav__label">ابزارها و پشتیبان</span>
							</a>
						</li>
					</ul>
				</aside>

				<div class="szs-main">

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="szs-form">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SAVE ); ?>">
						<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
						<?php wp_nonce_field( self::ACTION_SAVE ); ?>

						<div class="szs-cat-head">
							<h2><?php echo esc_html( $category['label'] ); ?></h2>
							<?php if ( ! empty( $category['description'] ) ) : ?>
								<p><?php echo esc_html( $category['description'] ); ?></p>
							<?php endif; ?>
						</div>

						<?php if ( count( $category['sections'] ) > 1 ) : ?>
							<nav class="szs-jump" aria-label="پرش به بخش">
								<?php foreach ( $category['sections'] as $sec_id => $section ) : ?>
									<a href="#section-<?php echo esc_attr( $sec_id ); ?>"><?php echo esc_html( $section['label'] ); ?></a>
								<?php endforeach; ?>
							</nav>
						<?php endif; ?>

						<div class="szs-sections">
							<?php
							foreach ( $category['sections'] as $sec_id => $section ) {
								self::render_section( $sec_id, $section, $tab );
							}
							?>
						</div>

						<p class="szs-empty-search" hidden>هیچ تنظیمی با این عبارت در این دسته پیدا نشد.</p>

						<div class="szs-savebar">
							<button type="submit" class="button button-primary button-hero">ذخیره تنظیمات</button>
							<span class="szs-savebar__hint">تغییرات این دسته ذخیره می‌شود.</span>
						</div>
					</form>
				</div>
			</div>
		</div>

		<script type="application/json" id="szs-search-index">
			<?php echo wp_json_encode( self::search_index() ); ?>
		</script>
		<?php
	}

	/** سربرگ صفحه. */
	private static function header( $tab ) {

		$states  = Sazan_Suite_Modules::states();
		$enabled = count( array_filter( $states ) );
		?>
		<div class="szs-hero">
			<div class="szs-hero__text">
				<span class="szs-hero__kicker">سازان سوئیت — نسخه <?php echo esc_html( SAZAN_SUITE_VERSION ); ?></span>
				<h1>کنترل پنل تنظیمات</h1>
				<p>همه‌ی تنظیمات پنل کاربری، ارزیابی، CRM، صفحه‌ی محصول و ویجت‌ها — دسته‌بندی‌شده و در یک جا.</p>
			</div>
			<div class="szs-hero__modules">
				<?php foreach ( Sazan_Suite_Modules::all() as $slug => $module ) : ?>
					<?php
					$loaded  = Sazan_Suite_Modules::is_loaded( $slug );
					$on      = ! empty( $states[ $slug ] );
					$state   = $loaded ? 'is-on' : ( $on ? 'is-warn' : 'is-off' );
					$title   = $loaded ? 'فعال' : ( $on ? 'روشن است اما بارگذاری نشد' : 'خاموش' );
					?>
					<span class="szs-chip <?php echo esc_attr( $state ); ?>" title="<?php echo esc_attr( $module['label'] . ' — ' . $title ); ?>">
						<span class="dashicons <?php echo esc_attr( $module['icon'] ); ?>" aria-hidden="true"></span>
						<?php echo esc_html( $module['label'] ); ?>
					</span>
				<?php endforeach; ?>
				<span class="szs-chip szs-chip--plain"><?php echo esc_html( sprintf( '%s از %s ماژول روشن', number_format_i18n( $enabled ), number_format_i18n( count( $states ) ) ) ); ?></span>
			</div>
		</div>
		<?php
	}

	/** پیام‌های نتیجه‌ی عملیات. */
	private static function notices() {

		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['szs-saved'] ) ) {
			$count = (int) $_GET['szs-saved'];
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf( 'تنظیمات ذخیره شد (%s بخش به‌روزرسانی شد).', number_format_i18n( $count ) ) )
			);
		}

		if ( isset( $_GET['szs-reset'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>تنظیمات این بخش به مقادیر پیش‌فرض بازگشت.</p></div>';
		}

		if ( isset( $_GET['szs-error'] ) ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['szs-error'] ) ) )
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification
	}

	/** رندر یک بخش. */
	private static function render_section( $sec_id, array $section, $tab ) {

		$available = self::section_available( $section );
		$values    = $available ? Sazan_Suite_Store::all( $section['store'] ) : array();
		?>
		<section class="szs-card <?php echo $available ? '' : 'is-unavailable'; ?>"
			id="section-<?php echo esc_attr( $sec_id ); ?>"
			data-section="<?php echo esc_attr( $sec_id ); ?>">

			<header class="szs-card__head">
				<div>
					<h3><?php echo esc_html( $section['label'] ); ?></h3>
					<?php if ( ! empty( $section['description'] ) ) : ?>
						<p><?php echo wp_kses_post( $section['description'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $available ) : ?>
					<a class="szs-card__reset"
						href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION_RESET . '&section=' . rawurlencode( $sec_id ) . '&tab=' . rawurlencode( $tab ) ), self::ACTION_RESET . $sec_id ) ); ?>"
						onclick="return confirm('همه‌ی تنظیمات این بخش به پیش‌فرض برمی‌گردد. مطمئن هستید؟');">
						<span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
						بازگردانی پیش‌فرض
					</a>
				<?php endif; ?>
			</header>

			<?php if ( ! $available ) : ?>
				<p class="szs-unavailable">
					ماژول «<?php echo esc_html( Sazan_Suite_Modules::label( $section['module'] ) ); ?>» هم‌اکنون بارگذاری نشده است؛
					برای ویرایش این تنظیمات آن را از صفحه‌ی
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Sazan_Suite_Tools::SLUG_MODULES ) ); ?>">ماژول‌ها</a>
					روشن کنید.
				</p>
			<?php else : ?>
				<input type="hidden" name="szs_sections[]" value="<?php echo esc_attr( $sec_id ); ?>">
				<?php
				Sazan_Suite_Fields::render_section( $sec_id, $section, $values );

				// ابزار اختیاریِ همان بخش (مثل ارسال پیامک آزمایشی).
				if ( ! empty( $section['after'] ) && is_callable( $section['after'] ) ) {
					call_user_func( $section['after'] );
				}
				?>
			<?php endif; ?>
		</section>
		<?php
	}

	/* =====================================================================
	 * ذخیره
	 * =================================================================== */

	public static function handle_save() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		check_admin_referer( self::ACTION_SAVE );

		$tab      = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		$posted   = isset( $_POST['szs_sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['szs_sections'] ) ) : array();
		$raw      = isset( $_POST[ Sazan_Suite_Fields::PREFIX ] ) ? (array) wp_unslash( $_POST[ Sazan_Suite_Fields::PREFIX ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$sections = Sazan_Suite_Schema::sections();
		$buckets  = array();
		$count    = 0;

		foreach ( $posted as $sec_id ) {

			if ( ! isset( $sections[ $sec_id ] ) ) {
				continue;
			}

			$section = $sections[ $sec_id ];

			if ( ! self::section_available( $section ) ) {
				continue;
			}

			$store   = $section['store'];
			$current = Sazan_Suite_Store::all( $store );
			$input   = isset( $raw[ $store ] ) && is_array( $raw[ $store ] ) ? $raw[ $store ] : array();

			$clean = Sazan_Suite_Sanitizer::section( $section['fields'], $input, $current );

			if ( ! $clean ) {
				continue;
			}

			$buckets[ $store ] = isset( $buckets[ $store ] ) ? array_merge( $buckets[ $store ], $clean ) : $clean;
			$count++;
		}

		foreach ( $buckets as $store => $values ) {
			Sazan_Suite_Store::update( $store, $values );
		}

		/**
		 * پس از ذخیره‌ی تنظیمات از کنترل پنل.
		 *
		 * @param array $buckets مقادیر ذخیره‌شده به تفکیک مخزن.
		 */
		do_action( 'sazan_suite_settings_saved', $buckets );

		wp_safe_redirect( self::url( $tab, array( 'szs-saved' => $count ) ) );
		exit;
	}

	/* =====================================================================
	 * بازگردانی پیش‌فرض
	 * =================================================================== */

	public static function handle_reset() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		$sec_id = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		check_admin_referer( self::ACTION_RESET . $sec_id );

		$section = Sazan_Suite_Schema::section( $sec_id );

		if ( ! $section || ! self::section_available( $section ) ) {
			wp_safe_redirect( self::url( $tab, array( 'szs-error' => 'بخش موردنظر پیدا نشد.' ) ) );
			exit;
		}

		$store    = $section['store'];
		$defaults = Sazan_Suite_Store::defaults( $store );
		$reset    = array();

		foreach ( $section['fields'] as $key => $def ) {
			$reset[ $key ] = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : self::blank_for( $def );
		}

		Sazan_Suite_Store::update( $store, $reset );

		wp_safe_redirect( self::url( $tab, array( 'szs-reset' => 1 ) ) . '#section-' . $sec_id );
		exit;
	}

	/** مقدار خالی متناسب با نوع فیلد (وقتی ماژول پیش‌فرضی ندارد). */
	private static function blank_for( array $def ) {

		switch ( $def['type'] ?? 'text' ) {
			case 'switch':
			case 'number':
			case 'page':
			case 'image':
				return 0;
			case 'users':
			case 'permissions':
			case 'repeater':
			case 'kv_rows':
			case 'kv_select':
			case 'kv_textarea':
				return array();
		}

		return '';
	}
}
