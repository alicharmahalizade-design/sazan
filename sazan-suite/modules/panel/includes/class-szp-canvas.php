<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * بوم «شناسایی روش‌های طراحی خدمت».
 * هر بوم سه زنجیره دارد (به ترتیب نمایش از بالا به پایین مطابق طرح اصلی):
 *   after  : زنجیره خدمات پس از فروش
 *   during : زنجیره خدمات حین خدمت
 *   before : زنجیره خدمات قبل از ارتباطات
 * کاربر در هر زنجیره می‌تواند چند «مورد» (روش خدمت) اضافه/حذف/ویرایش کند.
 * داده هر کاربر به‌صورت JSON در جدول اختصاصی نگه‌داری می‌شود.
 */
class SZP_Canvas {

	/** کلید پیش‌فرض بوم؛ امکان داشتن چند بوم مجزا روی یک سایت. */
	const KEY = 'service_design';

	/** سقف ایمنی تعداد موارد هر بخش تا داده‌ی مخرب ذخیره نشود. */
	const MAX_ITEMS = 100;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_canvas';
	}

	/** تعریف بخش‌های بوم به ترتیب نمایش (بالا → پایین). */
	public static function sections() {
		return array(
			'after'  => array(
				'tag'   => 'بعد',
				'title' => 'زنجیره خدمات پس از فروش',
				'desc'  => 'چگونه می‌توانیم با ادامه این زنجیره مشتریان هوادار ایجاد کنیم؟',
				'icon'  => 'flag',
			),
			'during' => array(
				'tag'   => 'حین',
				'title' => 'زنجیره خدمات حین خدمت',
				'desc'  => 'چگونه می‌توانیم خدمات قبلی را در این مرحله ادامه و توسعه دهیم؟',
				'icon'  => 'people',
			),
			'before' => array(
				'tag'   => 'قبل',
				'title' => 'زنجیره خدمات قبل از ارتباطات',
				'desc'  => 'چگونه می‌توانیم در راستای مزیت‌سازی، قبل از ورود مشتری خدمت‌رسانی انجام دهیم؟',
				'icon'  => 'rewind',
			),
		);
	}

	public static function valid_section( $key ) {
		return array_key_exists( $key, self::sections() );
	}

	/** بارگذاری داده ذخیره‌شده یک کاربر. خروجی: [section => [items...]] */
	public static function get_data( $canvas_key, $user_id ) {
		global $wpdb;
		$raw = $wpdb->get_var( $wpdb->prepare(
			'SELECT data FROM ' . self::table() . ' WHERE canvas_key=%s AND user_id=%d',
			$canvas_key, $user_id ) );
		$data = $raw ? json_decode( $raw, true ) : array();
		return self::normalize( is_array( $data ) ? $data : array() );
	}

	/** اطمینان از وجود همه بخش‌ها، آرایه‌بودن موارد و حذف موارد خالی. */
	public static function normalize( $data ) {
		$out = array();
		foreach ( array_keys( self::sections() ) as $key ) {
			$items = ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) ? array_values( $data[ $key ] ) : array();
			$clean = array();
			foreach ( $items as $item ) {
				// مورد می‌تواند رشته یا آرایه‌ای با کلید t باشد.
				$text = trim( (string) ( is_array( $item ) ? ( $item['t'] ?? '' ) : $item ) );
				if ( $text === '' ) { continue; }
				$clean[] = $text;
				if ( count( $clean ) >= self::MAX_ITEMS ) { break; }
			}
			$out[ $key ] = $clean;
		}
		return $out;
	}

	/** ذخیره کامل داده یک کاربر (جایگزینی کل بوم). */
	public static function save_data( $canvas_key, $user_id, $data ) {
		global $wpdb;
		$now  = current_time( 'mysql' );
		$json = wp_json_encode( self::normalize( $data ) );
		$wpdb->query( $wpdb->prepare(
			'INSERT INTO ' . self::table() . ' (canvas_key,user_id,data,created_at,updated_at)
			 VALUES (%s,%d,%s,%s,%s)
			 ON DUPLICATE KEY UPDATE data=VALUES(data), updated_at=VALUES(updated_at)',
			$canvas_key, (int) $user_id, $json, $now, $now ) );
	}

	/** همه بوم‌های ذخیره‌شده (برای مدیریت ادمین). خروجی هر ردیف: canvas_key,user_id,data,updated_at. */
	public static function all_rows() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT canvas_key,user_id,data,updated_at FROM ' . self::table() . ' ORDER BY updated_at DESC' );
	}

	/** بوم‌های یک کلید مشخص که حداقل یک مورد دارند (برای گالری عمومی). */
	public static function filled_rows( $canvas_key ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT user_id,data,updated_at FROM ' . self::table() . ' WHERE canvas_key=%s ORDER BY updated_at DESC',
			$canvas_key ) );
		$out = array();
		foreach ( (array) $rows as $r ) {
			$data  = self::normalize( json_decode( (string) $r->data, true ) ?: array() );
			$count = 0;
			foreach ( $data as $items ) { $count += count( $items ); }
			if ( $count < 1 ) { continue; }
			$out[] = array(
				'user_id'    => (int) $r->user_id,
				'data'       => $data,
				'count'      => $count,
				'updated_at' => $r->updated_at,
			);
		}
		return $out;
	}

	/* ==================== رندر گالری (کاروسل/شبکه) ==================== */

	/** $atts: key، view (carousel|grid)، count (۰=همه)، title. */
	public static function gallery( $atts = array() ) {
		$key   = ( isset( $atts['key'] ) && $atts['key'] !== '' ) ? sanitize_key( $atts['key'] ) : self::KEY;
		$view  = ( isset( $atts['view'] ) && $atts['view'] === 'carousel' ) ? 'carousel' : 'grid';
		$count = isset( $atts['count'] ) ? (int) $atts['count'] : 0;
		$title = isset( $atts['title'] ) ? (string) $atts['title'] : '';

		$rows = self::filled_rows( $key );
		if ( $count > 0 ) {
			$rows = array_slice( $rows, 0, $count );
		}

		ob_start();
		echo '<div class="szp">';
		if ( $title !== '' ) {
			echo '<h3 class="szp-cvg-title">' . esc_html( $title ) . '</h3>';
		}
		if ( ! $rows ) {
			echo '<div class="szp-empty">هنوز کسی این بوم را پر نکرده است.</div></div>';
			return ob_get_clean();
		}

		$cards = '';
		foreach ( $rows as $row ) {
			$cards .= self::gallery_card( $row );
		}

		if ( $view === 'carousel' ) {
			// RTL: دکمهٔ راست «بعدی» (جلو)، دکمهٔ چپ «قبلی» (عقب).
			echo '<div class="szp-carousel szp-cvg-carousel">'
				. '<button type="button" class="szp-car-btn szp-car-prev" aria-label="قبلی">‹</button>'
				. '<div class="szp-car-track">' . $cards . '</div>'
				. '<button type="button" class="szp-car-btn szp-car-next" aria-label="بعدی">›</button>'
				. '</div>';
		} else {
			echo '<div class="szp-cvg-grid">' . $cards . '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/** کارت یک شرکت‌کننده: آواتار + نام + شمارش + موارد هر زنجیره. */
	protected static function gallery_card( $row ) {
		$u    = get_userdata( $row['user_id'] );
		$name = $u ? $u->display_name : ( 'کاربر #' . $row['user_id'] );
		$secs = self::sections();

		ob_start(); ?>
		<div class="szp-cvg-card">
			<div class="szp-cvg-head">
				<span class="szp-cvg-avatar"><?php echo get_avatar( $row['user_id'], 44 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="szp-cvg-id">
					<span class="szp-cvg-name"><?php echo esc_html( $name ); ?></span>
					<span class="szp-cvg-count"><?php echo esc_html( szp_fa_digits( $row['count'] ) ); ?> مورد</span>
				</span>
			</div>
			<div class="szp-cvg-body">
				<?php foreach ( $secs as $skey => $sec ) : ?>
					<?php if ( empty( $row['data'][ $skey ] ) ) { continue; } ?>
					<details class="szp-cvg-sec">
						<summary class="szp-cvg-sum">
							<span class="szp-cvg-tag szp-cvg-tag-<?php echo esc_attr( $skey ); ?>"><?php echo esc_html( $sec['tag'] ); ?></span>
							<span class="szp-cvg-secnt"><?php echo esc_html( szp_fa_digits( count( $row['data'][ $skey ] ) ) ); ?> مورد</span>
							<span class="szp-cvg-chev" aria-hidden="true">▾</span>
						</summary>
						<ul class="szp-cvg-items">
							<?php foreach ( $row['data'][ $skey ] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php endforeach; ?>
			</div>
			<?php if ( ! empty( $row['updated_at'] ) ) : ?>
				<div class="szp-cvg-foot"><?php echo esc_html( szp_format_datetime( strtotime( $row['updated_at'] ) ) ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== رندر ==================== */

	/** $atts: key (کلید بوم)، title (عنوان دلخواه). */
	public static function render( $atts = array() ) {
		$key   = ( isset( $atts['key'] ) && $atts['key'] !== '' ) ? sanitize_key( $atts['key'] ) : self::KEY;
		$title = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'شناسایی روش‌های طراحی خدمت';

		$logged = is_user_logged_in();
		// مهمان: داده روی همان مرورگر (localStorage) نگه‌داری می‌شود؛ سرور خالی رندر می‌کند.
		$data = $logged ? self::get_data( $key, get_current_user_id() ) : self::normalize( array() );

		ob_start(); ?>
		<div class="szp">
			<div class="szp-canvas" data-canvas="<?php echo esc_attr( $key ); ?>" data-logged="<?php echo $logged ? '1' : '0'; ?>">
				<div class="szp-canvas-head">
					<h3 class="szp-canvas-title"><?php echo esc_html( $title ); ?></h3>
					<span class="szp-canvas-state" aria-live="polite"></span>
				</div>

				<div class="szp-canvas-body">
					<span class="szp-canvas-funnel" aria-hidden="true"></span>
					<?php foreach ( self::sections() as $skey => $sec ) : ?>
						<section class="szp-cv-row szp-cv-<?php echo esc_attr( $skey ); ?>" data-section="<?php echo esc_attr( $skey ); ?>">
							<div class="szp-cv-main">
								<ul class="szp-cv-items">
									<?php foreach ( $data[ $skey ] as $item ) : ?>
										<li class="szp-cv-item">
											<textarea class="szp-cv-text" rows="1" placeholder="یک روش خدمت بنویسید…"><?php echo esc_textarea( $item ); ?></textarea>
											<button type="button" class="szp-cv-del" aria-label="حذف مورد">×</button>
										</li>
									<?php endforeach; ?>
								</ul>
								<button type="button" class="szp-cv-add">＋ افزودن مورد</button>
							</div>
							<div class="szp-cv-side">
								<span class="szp-cv-tag"><?php echo esc_html( $sec['tag'] ); ?></span>
								<h4 class="szp-cv-name"><?php echo esc_html( $sec['title'] ); ?></h4>
								<p class="szp-cv-desc"><?php echo esc_html( $sec['desc'] ); ?></p>
								<span class="szp-cv-icon"><?php echo self::icon( $sec['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							</div>
						</section>
					<?php endforeach; ?>
				</div>

				<?php if ( ! $logged ) : ?>
					<p class="szp-canvas-note">برای ذخیره دائمی، وارد حساب کاربری شوید. (فعلاً موارد فقط روی همین مرورگر نگه‌داری می‌شود.)</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** آیکن SVG سبک هر بخش؛ رنگ از currentColor گرفته می‌شود. */
	protected static function icon( $name ) {
		switch ( $name ) {
			case 'flag': // پرچم/قله — مقصد نهایی و وفاداری
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21V4"/><path d="M5 4h10l-2 3 2 3H5"/></svg>';
			case 'people': // تعامل دو نفره — حین خدمت
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M3 20c0-3 2.2-5 5-5"/><path d="M21 20c0-3-2.2-5-5-5"/><path d="M9 12h6"/></svg>';
			case 'rewind': // عقب‌گرد — قبل از ارتباطات
			default:
				return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M14 9l-3 3 3 3"/><path d="M10 9v6"/></svg>';
		}
	}
}
