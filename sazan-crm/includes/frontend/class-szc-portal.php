<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * پورتال فرانت‌اندِ CRM: یک صفحه‌ی اختصاصی برای تیم فروش، بدون نیاز به ورود به پیشخوان وردپرس.
 *
 * با شورت‌کد [sazan_crm] در یک برگه قرار می‌گیرد. همه‌ی اکشن‌ها از هندلرهای AJAXِ موجود
 * (wp_ajax_szc_*) و اسکریپت admin.js استفاده می‌کنند؛ رابط کاربری کاملاً اختصاصی است.
 *
 * دسترسی: کاربران دارای szc_access. کارشناسان (نه‌مدیر) از پیشخوان وردپرس مسدود و به پورتال
 * هدایت می‌شوند.
 */
class SZC_Portal {

	const PAGE_OPTION = 'szc_portal_page_id';
	protected static $rendering = false;

	public static function init() {
		add_shortcode( 'sazan_crm', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'lock_admin' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'maybe_hide_admin_bar' ) );
		add_action( 'wp_ajax_szc_portal_view', array( __CLASS__, 'ajax_view' ) );
	}

	/** بارگذاری AJAXِ یک نما (SPA). خروجی: html فرگمنت. */
	public static function ajax_view() {
		if ( ! is_user_logged_in() || ! SZC_Settings::can_access() ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ), 403 );
		}
		check_ajax_referer( 'szc_admin', 'nonce' );
		self::$rendering = true;
		$view   = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'dashboard';
		$params = ( isset( $_POST['params'] ) && is_array( $_POST['params'] ) ) ? (array) wp_unslash( $_POST['params'] ) : array();
		$_GET   = array_merge( $_GET, $params ); // متدهای نما پارامترها را از $_GET می‌خوانند و خودشان پاک‌سازی می‌کنند.
		$html   = self::render_view( $view );
		self::$rendering = false;
		wp_send_json_success( array( 'html' => $html, 'view' => $view ) );
	}

	/* ==================== صفحه و URL ==================== */

	/** شناسه‌ی برگه‌ی پورتال (کش‌شده؛ در صورت نبود، اسکنِ محتوای برگه‌ها). */
	public static function portal_page_id() {
		$id = (int) get_option( self::PAGE_OPTION, 0 );
		if ( $id && get_post_status( $id ) === 'publish' ) {
			return $id;
		}
		global $wpdb;
		$found = (int) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('page','post') AND post_content LIKE '%[sazan_crm%' ORDER BY ID ASC LIMIT 1"
		);
		if ( $found ) {
			update_option( self::PAGE_OPTION, $found, false );
		}
		return $found;
	}

	public static function page_url() {
		$id = self::portal_page_id();
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	/** آدرس یک نمای پورتال. */
	public static function url( $view = 'dashboard', $args = array() ) {
		$args = array_merge( array( 'szc_view' => $view ), $args );
		unset( $args['page'] );
		return add_query_arg( $args, self::page_url() );
	}

	public static function contact_url( $id ) {
		return self::url( 'contact', array( 'id' => (int) $id ) );
	}

	/** آیا در بافتِ پورتال هستیم؟ (هنگام رندر شورت‌کد یا درخواست AJAX از صفحه‌ی پورتال) */
	public static function is_portal_context() {
		if ( self::$rendering ) {
			return true;
		}
		$id = self::portal_page_id();
		if ( ! $id ) {
			return false;
		}
		$ref = wp_get_referer();
		return $ref && strpos( $ref, get_permalink( $id ) ) !== false;
	}

	/* ==================== قفل پیشخوان ==================== */

	protected static function is_agent_only() {
		return SZC_Settings::can_access() && ! SZC_Settings::is_manager();
	}

	/** کارشناسِ نه‌مدیر نباید وارد پیشخوان شود؛ به پورتال هدایت می‌شود. */
	public static function lock_admin() {
		if ( ! self::is_agent_only() ) {
			return;
		}
		if ( wp_doing_ajax() ) {
			return; // admin-ajax باید کار کند
		}
		$self = isset( $_SERVER['PHP_SELF'] ) ? basename( $_SERVER['PHP_SELF'] ) : '';
		if ( in_array( $self, array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
			return;
		}
		wp_safe_redirect( self::page_url() );
		exit;
	}

	public static function maybe_hide_admin_bar() {
		if ( self::is_agent_only() ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/* ==================== assets ==================== */

	public static function assets() {
		if ( ! is_singular() ) {
			return;
		}
		global $post;
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'sazan_crm' ) ) {
			return;
		}
		$css = SZC_DIR . 'assets/css/portal.css';
		$js  = SZC_DIR . 'assets/js/admin.js';
		$pjs = SZC_DIR . 'assets/js/portal.js';
		wp_enqueue_style( 'szc-portal', SZC_URL . 'assets/css/portal.css', array(), file_exists( $css ) ? filemtime( $css ) : SZC_VERSION );
		wp_enqueue_script( 'szc-admin', SZC_URL . 'assets/js/admin.js', array(), file_exists( $js ) ? filemtime( $js ) : SZC_VERSION, true );
		wp_enqueue_script( 'szc-portal', SZC_URL . 'assets/js/portal.js', array( 'szc-admin' ), file_exists( $pjs ) ? filemtime( $pjs ) : SZC_VERSION, true );
		wp_localize_script( 'szc-admin', 'SZC_ADMIN', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szc_admin' ),
		) );
		wp_localize_script( 'szc-portal', 'SZC_PORTAL', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szc_admin' ),
			'base'  => self::page_url(),
		) );
	}

	/* ==================== نماها ==================== */

	protected static function nav_items() {
		$items = array(
			'dashboard' => array( 'داشبورد', 'home' ),
			'dialer'    => array( 'تماس پشت‌سرهم', 'phone' ),
			'contacts'  => array( 'مخاطبین', 'users' ),
			'add'       => array( 'افزودن مخاطب', 'user-plus' ),
			'followups' => array( 'پیگیری‌ها', 'bell' ),
			'reports'   => array( 'گزارش‌ها', 'chart' ),
		);
		return $items;
	}

	public static function shortcode( $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="szc-portal"><div class="szc-p-login">برای ورود به پنل فروش ابتدا وارد حساب کاربری شوید. <a href="' . esc_url( wp_login_url( self::page_url() ) ) . '">ورود</a></div></div>';
		}
		if ( ! SZC_Settings::can_access() ) {
			return '<div class="szc-portal"><div class="szc-p-login">شما به پنل فروش دسترسی ندارید. با مدیر تماس بگیرید.</div></div>';
		}

		// ثبت برگه‌ی پورتال برای هدایت‌ها.
		$pid = get_the_ID();
		if ( $pid && (int) get_option( self::PAGE_OPTION, 0 ) !== $pid ) {
			update_option( self::PAGE_OPTION, $pid, false );
		}

		self::$rendering = true;
		$view = isset( $_GET['szc_view'] ) ? sanitize_key( wp_unslash( $_GET['szc_view'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification
		$fs   = ! isset( $atts['fullscreen'] ) || ! in_array( (string) $atts['fullscreen'], array( '0', 'no', 'false' ), true );

		ob_start(); ?>
		<div class="szc-portal<?php echo $fs ? ' is-fullscreen' : ''; ?>" dir="rtl">
			<?php echo self::topbar_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="szc-p-body">
				<?php echo self::sidebar_html( $view ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<main class="szc-p-main">
					<?php echo self::render_view( $view ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</main>
			</div>
			<?php echo self::bottomnav_html( $view ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php
		self::$rendering = false;
		return ob_get_clean();
	}

	protected static function topbar_html() {
		$u = wp_get_current_user();
		ob_start(); ?>
		<header class="szc-p-top">
			<div class="szc-p-brand">
				<span class="szc-p-logo"><?php echo szc_icon( 'idcard' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="szc-p-title">سازان CRM</span>
			</div>
			<form class="szc-p-search" method="get" action="<?php echo esc_url( self::page_url() ); ?>">
				<input type="hidden" name="szc_view" value="contacts">
				<span class="szc-p-searchico"><?php echo szc_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<input type="search" name="s" placeholder="جستجوی نام یا شماره…" value="<?php echo isset( $_GET['s'] ) ? esc_attr( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore ?>">
			</form>
			<div class="szc-p-user">
				<span class="szc-p-avatar"><?php echo get_avatar( $u->ID, 34 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="szc-p-uname"><?php echo esc_html( $u->display_name ); ?></span>
				<a class="szc-p-logout" href="<?php echo esc_url( wp_logout_url( self::page_url() ) ); ?>" aria-label="خروج"><?php echo szc_icon( 'log-out' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</header>
		<?php
		return ob_get_clean();
	}

	protected static function sidebar_html( $active ) {
		ob_start(); ?>
		<aside class="szc-p-side">
			<nav class="szc-p-nav">
				<?php foreach ( self::nav_items() as $key => $it ) : ?>
					<a class="szc-p-navlink<?php echo $active === $key ? ' is-active' : ''; ?>" data-view="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( self::url( $key ) ); ?>">
						<span class="szc-p-navico"><?php echo szc_icon( $it[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><?php echo esc_html( $it[0] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
			<?php if ( SZC_Settings::is_manager() ) : ?>
				<a class="szc-p-adminlink" href="<?php echo esc_url( admin_url( 'admin.php?page=szc-settings' ) ); ?>"><?php echo szc_icon( 'settings' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تنظیمات (مدیر)</span></a>
			<?php endif; ?>
		</aside>
		<?php
		return ob_get_clean();
	}

	/** نوار پایینِ اپلیکیشنی برای موبایل (۵ آیتم اصلی). */
	protected static function bottomnav_html( $active ) {
		$main = array( 'dashboard', 'dialer', 'contacts', 'followups', 'reports' );
		$all  = self::nav_items();
		// برچسبِ کوتاه برای تب‌بارِ باریکِ موبایل (برچسبِ بلندِ ساید‌بار دست‌نخورده می‌ماند).
		$short = array( 'dialer' => 'تماس‌ها' );
		ob_start(); ?>
		<nav class="szc-p-bottomnav">
			<?php foreach ( $main as $key ) : if ( ! isset( $all[ $key ] ) ) { continue; }
				$label = isset( $short[ $key ] ) ? $short[ $key ] : $all[ $key ][0]; ?>
				<a class="szc-p-bnitem<?php echo $active === $key ? ' is-active' : ''; ?>" data-view="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( self::url( $key ) ); ?>">
					<span class="szc-p-bnico"><?php echo szc_icon( $all[ $key ][1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="szc-p-bnlbl"><?php echo esc_html( $label ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	protected static function render_view( $view ) {
		switch ( $view ) {
			case 'contacts':
				return self::view_contacts();
			case 'contact':
				return self::view_contact();
			case 'add':
				return self::view_add();
			case 'dialer':
				return self::view_dialer();
			case 'followups':
				return self::view_followups();
			case 'reports':
				return self::view_reports();
			case 'dashboard':
			default:
				return self::view_dashboard();
		}
	}

	/* ---------- داشبورد ---------- */

	protected static function view_dashboard() {
		$owner  = SZC_Settings::scope_owner();
		$counts = SZC_Contacts::counts_by_stage( $owner );
		$total  = SZC_Contacts::total( $owner );
		$queue  = SZC_SMS::queue_counts();
		$due    = SZC_Activity::due_followups( 15, $owner );
		$calls  = SZC_Reports::calls_today( $owner );
		$funnel = SZC_Reports::funnel( $owner );

		ob_start(); ?>
		<h1 class="szc-p-h1">داشبورد</h1>

		<div class="szc-p-stats">
			<?php
			echo self::stat_card( 'کل مخاطبین', szc_fa_digits( $total ), 'users' );
			echo self::stat_card( 'تماس‌های امروز', szc_fa_digits( $calls ), 'phone' );
			echo self::stat_card( 'پیگیری سررسیده', szc_fa_digits( count( $due ) ), 'bell' );
			echo self::stat_card( 'پیامک در صف', szc_fa_digits( $queue['pending'] ), 'mail' );
			echo self::stat_card( 'نرخ تبدیل', szc_fa_digits( $funnel['conversion'] ) . '٪', 'target' );
			?>
		</div>

		<div class="szc-p-grid2">
			<section class="szc-p-card">
				<h2 class="szc-p-h2">قیف فروش</h2>
				<div class="szc-p-funnel">
					<?php
					$max = 1;
					foreach ( $funnel['stages'] as $st ) { $max = max( $max, $st['count'] ); }
					foreach ( $funnel['stages'] as $k => $st ) :
						$w = (int) round( $st['count'] / $max * 100 );
						?>
						<a class="szc-p-frow" href="<?php echo esc_url( self::url( 'contacts', array( 'stage' => $k ) ) ); ?>">
							<span class="szc-p-flabel"><?php echo esc_html( $st['label'] ); ?></span>
							<span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $w ) ); ?>%"></span></span>
							<span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $st['count'] ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="szc-p-card">
				<h2 class="szc-p-h2">پیگیری‌های سررسیده</h2>
				<?php if ( ! $due ) : ?>
					<p class="szc-p-empty szc-p-empty-ok"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> پیگیری سررسیده‌ای نیست.</p>
				<?php else : ?>
					<ul class="szc-p-duelist">
						<?php foreach ( $due as $r ) :
							$name    = trim( $r->first_name . ' ' . $r->last_name ) ?: szc_fa_digits( $r->mobile );
							$overdue = strtotime( $r->due_at ) < current_time( 'timestamp' );
							?>
							<li class="szc-p-dueitem">
								<button type="button" class="szc-p-donebtn" data-szc-act="done_followup" data-id="<?php echo (int) $r->id; ?>" title="انجام شد" aria-label="علامت انجام‌شده"><?php echo szc_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
								<span class="szc-p-dueinfo">
									<a href="<?php echo esc_url( self::contact_url( $r->contact_id ) ); ?>"><b><?php echo esc_html( $name ); ?></b></a>
									<?php if ( ! empty( $r->body ) ) : ?><span class="szc-p-duenote"><?php echo esc_html( $r->body ); ?></span><?php endif; ?>
								</span>
								<span class="szc-p-due<?php echo $overdue ? ' is-overdue' : ''; ?>"><?php echo esc_html( szc_format_mysql( $r->due_at ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<a class="szc-p-morelink" href="<?php echo esc_url( self::url( 'followups' ) ); ?>">همه‌ی پیگیری‌ها <?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
			</section>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function stat_card( $label, $value, $icon ) {
		return '<div class="szc-p-stat"><span class="szc-p-stat-ico szc-p-stat-ico--' . esc_attr( $icon ) . '">' . szc_icon( $icon ) . '</span>'
			. '<span class="szc-p-stat-v">' . esc_html( $value ) . '</span>'
			. '<span class="szc-p-stat-l">' . esc_html( $label ) . '</span></div>';
	}

	/* ---------- فهرست مخاطبین ---------- */

	/** رندر بازگشتیِ درختِ پوشه‌ها. */
	protected static function folder_tree_html( $nodes, $active ) {
		if ( ! $nodes ) {
			return '';
		}
		$out = '<ul class="szc-p-ftree">';
		foreach ( $nodes as $n ) {
			$on   = ( (int) $active === (int) $n['id'] );
			$out .= '<li class="szc-p-fnode">';
			$out .= '<div class="szc-p-frow' . ( $on ? ' is-active' : '' ) . '">';
			$out .= '<a class="szc-p-flink" data-drop-group="' . (int) $n['id'] . '" href="' . esc_url( self::url( 'contacts', array( 'group' => $n['id'] ) ) ) . '">' . szc_icon( 'folder' ) . '<span class="szc-p-fname">' . esc_html( $n['name'] ) . '</span><span class="szc-p-fcount">' . esc_html( szc_fa_digits( $n['count'] ) ) . '</span></a>';
			$out .= '<span class="szc-p-fops">';
			$out .= '<button type="button" class="szc-p-iconbtn" data-folder-act="create" data-parent="' . (int) $n['id'] . '" title="زیرپوشه" aria-label="زیرپوشه">' . szc_icon( 'plus' ) . '</button>';
			$out .= '<button type="button" class="szc-p-iconbtn" data-folder-act="rename" data-id="' . (int) $n['id'] . '" data-name="' . esc_attr( $n['name'] ) . '" title="تغییر نام" aria-label="تغییر نام">' . szc_icon( 'edit' ) . '</button>';
			$out .= '<button type="button" class="szc-p-iconbtn szc-p-danger" data-folder-act="delete" data-id="' . (int) $n['id'] . '" title="حذف" aria-label="حذف">' . szc_icon( 'trash' ) . '</button>';
			$out .= '</span></div>';
			$out .= self::folder_tree_html( $n['children'], $active );
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}

	protected static function view_contacts() {
		$owner  = SZC_Settings::scope_owner();
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$stage  = isset( $_GET['stage'] ) ? sanitize_key( wp_unslash( $_GET['stage'] ) ) : ''; // phpcs:ignore
		$prio   = isset( $_GET['priority'] ) ? sanitize_key( wp_unslash( $_GET['priority'] ) ) : ''; // phpcs:ignore
		$group  = isset( $_GET['group'] ) ? absint( $_GET['group'] ) : 0; // phpcs:ignore
		$page   = isset( $_GET['pn'] ) ? max( 1, absint( $_GET['pn'] ) ) : 1; // phpcs:ignore

		$res = SZC_Contacts::query( array(
			'search' => $search, 'stage' => $stage, 'priority' => $prio, 'group' => $group,
			'owner' => $owner, 'page' => $page, 'per_page' => 25,
		) );
		$stages    = SZC_Settings::stages();
		$prios     = SZC_Settings::priorities();
		$templates = SZC_Templates::all();
		$pages     = max( 1, (int) ceil( $res['total'] / $res['per_page'] ) );
		$title     = $group ? SZC_Groups::name( $group ) : 'مخاطبین';

		ob_start(); ?>
		<div class="szc-p-folderlayout">
			<aside class="szc-p-folders">
				<div class="szc-p-folders-head">
					<span>پوشه‌ها</span>
					<button type="button" class="szc-p-iconbtn szc-p-iconbtn--text" data-folder-act="create" data-parent="0" title="پوشه‌ی جدید"><?php echo szc_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پوشه</span></button>
				</div>
				<a class="szc-p-flink szc-p-frootlink<?php echo $group === 0 ? ' is-active' : ''; ?>" data-drop-group="0" href="<?php echo esc_url( self::url( 'contacts' ) ); ?>"><?php echo szc_icon( 'idcard' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="szc-p-fname">همه‌ی مخاطبین</span></a>
				<?php echo self::folder_tree_html( SZC_Groups::tree(), $group ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</aside>

			<div class="szc-p-contactsmain">
				<div class="szc-p-listhead">
					<h1 class="szc-p-h1"><?php echo esc_html( $title ); ?> <span class="szc-p-count"><?php echo esc_html( szc_fa_digits( $res['total'] ) ); ?></span></h1>
					<a class="szc-p-btn szc-p-btn-primary" href="<?php echo esc_url( self::url( 'add' ) ); ?>"><?php echo szc_icon( 'user-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>افزودن مخاطب</span></a>
				</div>

				<form class="szc-p-filters" method="get" action="<?php echo esc_url( self::page_url() ); ?>">
					<input type="hidden" name="szc_view" value="contacts">
					<?php if ( $group ) : ?><input type="hidden" name="group" value="<?php echo (int) $group; ?>"><?php endif; ?>
					<input type="search" name="s" placeholder="جستجو…" value="<?php echo esc_attr( $search ); ?>">
					<select name="stage">
						<option value="">همه‌ی مراحل</option>
						<?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $stage, $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
					</select>
					<select name="priority">
						<option value="">همه‌ی اولویت‌ها</option>
						<?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $prio, $k ); ?>><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?>
					</select>
					<button class="szc-p-btn" type="submit">فیلتر</button>
				</form>

				<?php if ( $group ) : ?>
					<div class="szc-p-bulkbar" data-group="<?php echo (int) $group; ?>">
						<span class="szc-p-bulklabel">اقدام گروهی روی «<?php echo esc_html( $title ); ?>»:</span>
						<select data-bulk-stage>
							<?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
						</select>
						<button type="button" class="szc-p-btn" data-folder-act="bulk-stage">تغییر مرحله‌ی همه</button>
						<?php if ( $templates ) : ?>
							<select data-bulk-tpl>
								<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
							</select>
							<button type="button" class="szc-p-btn" data-folder-act="bulk-sms">پیامک به همه</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! $res['items'] ) : ?>
						<div class="szc-p-empty-state">
							<span class="szc-p-empty-ico"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<p class="szc-p-empty">مخاطبی یافت نشد.</p>
							<a class="szc-p-btn szc-p-btn-primary" href="<?php echo esc_url( self::url( 'add' ) ); ?>"><?php echo szc_icon( 'user-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>افزودن مخاطب</span></a>
						</div>
					<?php else : ?>
						<ul class="szc-p-clist">
							<?php foreach ( $res['items'] as $c ) :
								$pm    = SZC_Settings::priority_meta( $c->priority );
								$cname = SZC_Contacts::full_name( $c );
								?>
								<li class="szc-p-crow" draggable="true" data-contact="<?php echo (int) $c->id; ?>">
									<span class="szc-p-grip" title="بکشید و روی یک پوشه رها کنید" aria-hidden="true"><?php echo szc_icon( 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<a class="szc-p-cmain" draggable="false" href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>">
										<span class="szc-p-cav" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( mb_substr( $cname, 0, 1 ) ); ?></span>
										<span class="szc-p-cbody">
											<span class="szc-p-cname"><?php echo esc_html( $cname ); ?><?php echo $c->opt_out ? ' <span class="szc-p-tag szc-p-tag-red">لغو پیامک</span>' : ''; ?></span>
											<span class="szc-p-csub">
												<span class="szc-p-cnum" dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></span>
												<span class="szc-p-stage"><?php echo esc_html( SZC_Settings::stage_label( $c->stage ) ); ?></span>
												<span class="szc-p-prio" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( $pm['label'] ); ?></span>
											</span>
										</span>
									</a>
									<a draggable="false" class="szc-p-callbtn" href="tel:<?php echo esc_attr( $c->mobile ); ?>" title="تماس با <?php echo esc_attr( $cname ); ?>" aria-label="تماس"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
								</li>
							<?php endforeach; ?>
						</ul>

					<?php if ( $pages > 1 ) : ?>
						<div class="szc-p-pager">
							<?php for ( $i = 1; $i <= $pages; $i++ ) :
								$url = self::url( 'contacts', array_filter( array( 's' => $search, 'stage' => $stage, 'priority' => $prio, 'group' => $group, 'pn' => $i ) ) );
								?>
								<a class="<?php echo $i === $page ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( szc_fa_digits( $i ) ); ?></a>
							<?php endfor; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ---------- پرونده‌ی مخاطب ---------- */

	protected static function view_contact() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		$c  = $id ? SZC_Contacts::get( $id ) : null;
		if ( ! $c ) {
			return '<p class="szc-p-empty">مخاطب یافت نشد. <a href="' . esc_url( self::url( 'contacts' ) ) . '">بازگشت</a></p>';
		}
		if ( ! SZC_Settings::is_manager() && (int) $c->owner_id !== get_current_user_id() && (int) $c->owner_id !== 0 ) {
			return '<p class="szc-p-empty">به این مخاطب دسترسی ندارید.</p>';
		}

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

		ob_start(); ?>
		<div class="szc-single" data-contact="<?php echo (int) $c->id; ?>">
			<div class="szc-p-crumb"><a href="<?php echo esc_url( self::url( 'contacts' ) ); ?>"><?php echo szc_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>مخاطبین</span></a></div>

			<div class="szc-p-chead">
				<div class="szc-p-cavatar" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( mb_substr( SZC_Contacts::full_name( $c ), 0, 1 ) ); ?></div>
				<div class="szc-p-cinfo">
					<h1 class="szc-p-h1"><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></h1>
					<div class="szc-p-cmeta">
						<a href="tel:<?php echo esc_attr( $c->mobile ); ?>" dir="ltr" class="szc-p-phone"><?php echo szc_icon( 'smartphone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></span></a>
						<?php if ( $c->company ) : ?><span><?php echo szc_icon( 'briefcase' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->company ); ?></span><?php endif; ?>
						<?php if ( $c->city ) : ?><span><?php echo szc_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->city ); ?></span><?php endif; ?>
					</div>
				</div>
				<div class="szc-p-cquick">
					<a class="szc-p-callbig" href="tel:<?php echo esc_attr( $c->mobile ); ?>"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تماس</span></a>
					<select data-szc-act="set_field" data-field="stage" class="szc-p-sel">
						<?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c->stage, $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
					</select>
					<select data-szc-act="set_field" data-field="priority" class="szc-p-sel">
						<?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c->priority, $k ); ?>><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?>
					</select>
					<?php $folders = SZC_Groups::all(); if ( $folders ) : ?>
						<select data-szc-act="set_group" class="szc-p-sel" title="انتقال به پوشه">
							<option value="0" <?php selected( (int) $c->group_id, 0 ); ?>>بدون پوشه</option>
							<?php foreach ( $folders as $g ) : ?><option value="<?php echo (int) $g->id; ?>" <?php selected( (int) $c->group_id, (int) $g->id ); ?>><?php echo esc_html( $g->name ); ?></option><?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
			</div>

			<div class="szc-p-grid2">
				<div class="szc-p-col">
					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ثبت تماس</span></h2>
						<div class="szc-p-row">
							<select data-call-outcome>
								<?php foreach ( $outcomes as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
							</select>
							<select data-call-template title="پیامکِ همراهِ این تماس (موفق یا ناموفق)">
								<option value="0">پیامک: پیش‌فرض خودکار</option>
								<option value="-1">بدون پیامک</option>
								<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
							</select>
						</div>
						<textarea data-call-note rows="2" placeholder="یادداشت تماس (اختیاری)"></textarea>
						<button class="szc-p-btn szc-p-btn-primary" data-szc-act="log_call">ثبت تماس</button>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیگیری بعدی</span></h2>
						<div class="szc-p-presets">
							<button type="button" class="szc-p-chip" data-preset="tomorrow10">فردا ۱۰ صبح</button>
							<button type="button" class="szc-p-chip" data-preset="today17">امروز ۱۷</button>
							<button type="button" class="szc-p-chip" data-preset="d3">۳ روز دیگر</button>
							<button type="button" class="szc-p-chip" data-preset="week">هفته بعد</button>
						</div>
						<div class="szc-p-row">
							<span class="szc-jp" data-jp="datetime">
								<input type="text" class="szc-jp-disp" readonly placeholder="زمان پیگیری (تقویم شمسی)">
								<input type="hidden" class="szc-jp-val" data-followup-at>
							</span>
							<input type="text" data-followup-note placeholder="موضوع (اختیاری)">
						</div>
						<button class="szc-p-btn" data-szc-act="add_followup">ثبت پیگیری</button>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیامک</span></h2>
						<?php if ( $templates ) : ?>
							<div class="szc-p-row">
								<select data-sms-template>
									<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
								</select>
							</div>
							<div class="szc-p-btnrow">
								<button class="szc-p-btn szc-p-btn-primary" data-szc-act="send_sms">ارسال فوری</button>
								<button class="szc-p-btn" data-szc-act="schedule_sms">زمان‌بندی</button>
							</div>
						<?php else : ?>
							<p class="szc-p-muted">قالبی تعریف نشده است.</p>
						<?php endif; ?>
						<div class="szc-p-customsms">
							<label class="szc-p-cslabel">پیامک دلخواه</label>
							<textarea data-custom-sms rows="2" placeholder="متن دلخواه… (می‌توانید از %first% و %name% استفاده کنید)"></textarea>
							<button class="szc-p-btn" data-szc-act="custom_sms">ارسال پیامک دلخواه</button>
						</div>
						<?php if ( $c->opt_out ) : ?><p class="szc-p-muted">این مخاطب لغو دریافت پیامک دارد.</p><?php endif; ?>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'file-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>یادداشت</span></h2>
						<textarea data-note-body rows="2" placeholder="یادداشت درباره‌ی این مخاطب…"></textarea>
						<button class="szc-p-btn" data-szc-act="add_note">افزودن یادداشت</button>
					</section>
				</div>

				<div class="szc-p-col">
					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>اطلاعات</span></h2>
						<div class="szc-p-form2">
							<label>نام<input type="text" data-f="first_name" value="<?php echo esc_attr( $c->first_name ); ?>"></label>
							<label>نام خانوادگی<input type="text" data-f="last_name" value="<?php echo esc_attr( $c->last_name ); ?>"></label>
							<label>موبایل<input type="text" dir="ltr" data-f="mobile" value="<?php echo esc_attr( $c->mobile ); ?>"></label>
							<label>شغل<input type="text" data-f="job" value="<?php echo esc_attr( $c->job ); ?>"></label>
							<label>شرکت<input type="text" data-f="company" value="<?php echo esc_attr( $c->company ); ?>"></label>
							<label>شهر<input type="text" data-f="city" value="<?php echo esc_attr( $c->city ); ?>"></label>
							<label>ایمیل<input type="email" dir="ltr" data-f="email" value="<?php echo esc_attr( $c->email ); ?>"></label>
							<label>منبع<input type="text" data-f="source" value="<?php echo esc_attr( $c->source ); ?>"></label>
							<?php foreach ( $customs as $cf ) : ?>
								<label><?php echo esc_html( $cf['label'] ); ?><input type="text" data-cf="<?php echo esc_attr( $cf['key'] ); ?>" value="<?php echo esc_attr( $cmeta[ $cf['key'] ] ?? '' ); ?>"></label>
							<?php endforeach; ?>
							<?php if ( $is_manager && $assignees ) : ?>
								<label>کارشناس مسئول
									<select data-szc-act="set_field" data-field="owner">
										<option value="0">— بدون تخصیص —</option>
										<?php foreach ( $assignees as $uid => $nm ) : ?><option value="<?php echo (int) $uid; ?>" <?php selected( (int) $c->owner_id, (int) $uid ); ?>><?php echo esc_html( $nm ); ?></option><?php endforeach; ?>
									</select>
								</label>
							<?php endif; ?>
						</div>
						<div class="szc-p-btnrow">
							<button class="szc-p-btn szc-p-btn-primary" data-szc-act="save_contact">ذخیره</button>
							<?php if ( $blocked ) : ?>
								<button class="szc-p-btn" data-szc-act="blacklist" data-op="remove">خروج از لیست سیاه</button>
							<?php else : ?>
								<button class="szc-p-btn szc-p-btn-ghost" data-szc-act="blacklist" data-op="add">لیست سیاه</button>
							<?php endif; ?>
							<button class="szc-p-btn szc-p-btn-danger" data-szc-act="del_contact">حذف</button>
						</div>
					</section>

					<?php if ( $sequences ) : ?>
						<section class="szc-p-card">
							<h2 class="szc-p-h2"><?php echo szc_icon( 'repeat' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>دنباله پیامکی</span></h2>
							<div class="szc-p-row">
								<select data-seq>
									<?php foreach ( $sequences as $sq ) : ?><option value="<?php echo (int) $sq->id; ?>"><?php echo esc_html( $sq->name ); ?></option><?php endforeach; ?>
								</select>
								<button class="szc-p-btn" data-szc-act="enroll">ثبت در دنباله</button>
							</div>
							<?php if ( $enrollments ) : ?>
								<ul class="szc-p-enr">
									<?php foreach ( $enrollments as $en ) : ?><li><b><?php echo esc_html( $en->name ); ?></b> — <?php echo esc_html( $en->status === 'active' ? 'فعال' : ( $en->status === 'canceled' ? 'لغوشده' : $en->status ) ); ?></li><?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</section>
					<?php endif; ?>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'history' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تاریخچه</span></h2>
						<?php echo self::timeline_html( $timeline ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function timeline_html( $items ) {
		if ( ! $items ) {
			return '<p class="szc-p-muted">هنوز فعالیتی ثبت نشده است.</p>';
		}
		$type_lbl = array( 'note' => 'یادداشت', 'call' => 'تماس', 'sms' => 'پیامک', 'stage' => 'تغییر مرحله', 'followup' => 'پیگیری' );
		$sms_lbl  = array( 'sent' => 'ارسال شد', 'failed' => 'ناموفق', 'scheduled' => 'زمان‌بندی شد' );
		$ico      = array( 'note' => 'file-text', 'call' => 'phone', 'sms' => 'mail', 'stage' => 'shuffle', 'followup' => 'calendar' );
		ob_start();
		echo '<ul class="szc-p-timeline">';
		foreach ( $items as $it ) {
			$who   = $it['user_id'] ? get_the_author_meta( 'display_name', $it['user_id'] ) : '';
			$head  = $type_lbl[ $it['type'] ] ?? $it['type'];
			$extra = '';
			if ( $it['type'] === 'call' && $it['outcome'] ) {
				$extra = ' — ' . SZC_Settings::outcome_label( $it['outcome'] );
			} elseif ( $it['type'] === 'sms' && $it['outcome'] ) {
				$extra = ' — ' . ( $sms_lbl[ $it['outcome'] ] ?? $it['outcome'] );
			} elseif ( $it['type'] === 'followup' && $it['due_at'] ) {
				$extra = ' — سررسید: ' . szc_format_mysql( $it['due_at'] ) . ( $it['done'] ? ' (انجام شد)' : '' );
			}
			echo '<li class="szc-p-tl">';
			echo '<span class="szc-p-tlico">' . szc_icon( $ico[ $it['type'] ] ?? 'check' ) . '</span>';
			echo '<div class="szc-p-tlbody">';
			echo '<div class="szc-p-tlhead"><b>' . esc_html( $head . $extra ) . '</b><span class="szc-p-muted">' . esc_html( szc_time_ago( $it['created_at'] ) ) . ( $who ? ' · ' . esc_html( $who ) : '' ) . '</span></div>';
			if ( $it['body'] !== '' ) {
				echo '<p>' . nl2br( esc_html( $it['body'] ) ) . '</p>';
			}
			echo '<div class="szc-p-tlacts">';
			if ( $it['type'] === 'followup' && ! $it['done'] ) {
				echo '<button class="szc-p-linkbtn" data-szc-act="done_followup" data-id="' . (int) $it['id'] . '">علامت انجام‌شده</button>';
			}
			$del_act = $it['kind'] === 'note' ? 'del_note' : 'del_activity';
			echo '<button class="szc-p-linkbtn szc-p-danger" data-szc-act="' . esc_attr( $del_act ) . '" data-id="' . (int) $it['id'] . '">حذف</button>';
			echo '</div></div></li>';
		}
		echo '</ul>';
		return ob_get_clean();
	}

	/* ---------- افزودن مخاطب ---------- */

	protected static function view_add() {
		$prios   = SZC_Settings::priorities();
		$customs = SZC_Settings::custom_fields();
		ob_start(); ?>
		<h1 class="szc-p-h1">افزودن مخاطب</h1>
		<section class="szc-p-card szc-add-form" style="max-width:680px">
			<div class="szc-p-form2">
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
						<?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, 'warm' ); ?>><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?>
					</select>
				</label>
				<?php foreach ( $customs as $cf ) : ?>
					<label><?php echo esc_html( $cf['label'] ); ?><input type="text" data-cf="<?php echo esc_attr( $cf['key'] ); ?>"></label>
				<?php endforeach; ?>
			</div>
			<button class="szc-p-btn szc-p-btn-primary" data-szc-act="add_contact">افزودن مخاطب</button>
		</section>
		<?php
		return ob_get_clean();
	}

	/* ---------- تماس پشت‌سرهم (Power Dialer) ---------- */

	protected static function view_dialer() {
		$owner = SZC_Settings::scope_owner();
		$keys  = SZC_Settings::funnel_stage_keys();
		$lead  = $keys ? $keys[0] : 'lead';
		$i     = isset( $_GET['i'] ) ? max( 0, absint( $_GET['i'] ) ) : 0; // phpcs:ignore

		// صف: ابتدا پیگیری‌های سررسیده (نزدیک‌ترین اول)، سپس لیدهای تماس‌نگرفته.
		$queue = array();
		$seen  = array();
		$why   = array();
		foreach ( SZC_Activity::due_followups( 100, $owner ) as $r ) {
			$cid = (int) $r->contact_id;
			if ( isset( $seen[ $cid ] ) ) { continue; }
			$c = SZC_Contacts::get( $cid );
			if ( ! $c ) { continue; }
			$seen[ $cid ] = true;
			$queue[]      = $c;
			$why[ $cid ]  = array( 'type' => 'followup', 'due' => $r->due_at, 'note' => $r->body );
		}
		$leads = SZC_Contacts::query( array( 'stage' => $lead, 'owner' => $owner, 'orderby' => 'last_contacted_at', 'order' => 'ASC', 'per_page' => 100 ) );
		foreach ( $leads['items'] as $c ) {
			if ( isset( $seen[ (int) $c->id ] ) ) { continue; }
			$seen[ (int) $c->id ] = true;
			$queue[]              = $c;
			$why[ (int) $c->id ]  = array( 'type' => 'lead' );
		}
		$total = count( $queue );

		ob_start(); ?>
		<h1 class="szc-p-h1"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تماس پشت‌سرهم</span> <span class="szc-p-count"><?php echo esc_html( szc_fa_digits( $total ) ); ?></span></h1>
		<?php if ( ! $queue ) : ?>
			<div class="szc-p-dialer-empty">
				<span class="szc-p-empty-ico"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<p class="szc-p-empty">صف تماسِ امروز خالی است — پیگیری سررسیده یا لیدِ تازه‌ای نیست.</p>
				<a class="szc-p-btn" href="<?php echo esc_url( self::url( 'contacts' ) ); ?>">مشاهده‌ی مخاطبین</a>
			</div>
		<?php else :
			$i = min( $i, count( $queue ) - 1 );
			$c = $queue[ $i ];
			$w = $why[ (int) $c->id ] ?? array( 'type' => 'lead' );
			$outcomes = SZC_Settings::call_outcomes();
			$templates = SZC_Templates::all();
			$pm = SZC_Settings::priority_meta( $c->priority );
			?>
			<div class="szc-single szc-p-dialer" data-contact="<?php echo (int) $c->id; ?>">
				<div class="szc-p-dialer-card">
					<div class="szc-p-dialer-progress"><?php echo esc_html( szc_fa_digits( $i + 1 ) . ' از ' . szc_fa_digits( $total ) ); ?></div>
					<?php if ( $w['type'] === 'followup' ) : ?>
						<div class="szc-p-dialer-why is-followup"><?php echo szc_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>پیگیری سررسیده: <?php echo esc_html( szc_format_mysql( $w['due'] ) ); ?><?php echo ! empty( $w['note'] ) ? ' — ' . esc_html( $w['note'] ) : ''; ?></div>
					<?php else : ?>
						<div class="szc-p-dialer-why is-lead"><?php echo szc_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>لید جدید</div>
					<?php endif; ?>
					<div class="szc-p-dialer-avatar" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( mb_substr( SZC_Contacts::full_name( $c ), 0, 1 ) ); ?></div>
					<h2 class="szc-p-dialer-name"><a href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>"><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></a></h2>
					<div class="szc-p-dialer-meta">
						<?php if ( $c->company ) : ?><span><?php echo szc_icon( 'briefcase' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->company ); ?></span><?php endif; ?>
						<?php if ( $c->city ) : ?><span><?php echo szc_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->city ); ?></span><?php endif; ?>
					</div>
					<a class="szc-p-dialer-call" href="tel:<?php echo esc_attr( $c->mobile ); ?>"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></span></a>

					<div class="szc-p-dialer-log">
						<div class="szc-p-row">
							<select data-call-outcome>
								<?php foreach ( $outcomes as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
							</select>
							<select data-call-template>
								<option value="0">پیامک: پیش‌فرض</option>
								<option value="-1">بدون پیامک</option>
								<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
							</select>
						</div>
						<textarea data-call-note rows="2" placeholder="یادداشت تماس (اختیاری)"></textarea>
						<div class="szc-p-btnrow">
							<button class="szc-p-btn szc-p-btn-primary" data-szc-act="dialer_call">ثبت تماس و بعدی</button>
							<a class="szc-p-btn" href="<?php echo esc_url( self::url( 'dialer', array( 'i' => $i + 1 ) ) ); ?>"><span>رد کردن</span><?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/* ---------- پیگیری‌ها ---------- */

	protected static function view_followups() {
		$owner    = SZC_Settings::scope_owner();
		$tab      = ( isset( $_GET['tab'] ) && $_GET['tab'] === 'upcoming' ) ? 'upcoming' : 'due'; // phpcs:ignore
		$rows     = SZC_Activity::followups( $tab, $owner, 200 );
		ob_start(); ?>
		<h1 class="szc-p-h1">پیگیری‌ها</h1>
		<div class="szc-p-tabs">
			<a class="<?php echo $tab === 'due' ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( 'followups', array( 'tab' => 'due' ) ) ); ?>">امروز و عقب‌افتاده</a>
			<a class="<?php echo $tab === 'upcoming' ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( 'followups', array( 'tab' => 'upcoming' ) ) ); ?>">آینده</a>
		</div>
		<?php if ( ! $rows ) : ?>
			<p class="szc-p-empty">پیگیری‌ای در این بخش نیست.</p>
		<?php else : ?>
			<div class="szc-p-tablewrap">
				<table class="szc-p-table">
					<thead><tr><th>مخاطب</th><th>موبایل</th><th>سررسید</th><th>موضوع</th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $r ) :
						$name = trim( $r->first_name . ' ' . $r->last_name ) ?: szc_fa_digits( $r->mobile );
						?>
						<tr>
							<td data-th="مخاطب"><a href="<?php echo esc_url( self::contact_url( $r->contact_id ) ); ?>"><b><?php echo esc_html( $name ); ?></b></a></td>
							<td data-th="موبایل" dir="ltr"><?php echo esc_html( szc_fa_digits( $r->mobile ) ); ?></td>
							<td data-th="سررسید"><?php echo esc_html( szc_format_mysql( $r->due_at ) ); ?></td>
							<td data-th="موضوع"><?php echo esc_html( $r->body ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/* ---------- گزارش‌ها ---------- */

	protected static function view_reports() {
		$owner   = SZC_Settings::scope_owner();
		$funnel  = SZC_Reports::funnel( $owner );
		$byout   = SZC_Reports::calls_by_outcome( 7 );
		$board   = SZC_Settings::is_manager() ? SZC_Reports::agent_leaderboard() : array();
		ob_start(); ?>
		<h1 class="szc-p-h1">گزارش‌ها</h1>
		<div class="szc-p-grid2">
			<section class="szc-p-card">
				<h2 class="szc-p-h2">قیف تبدیل</h2>
				<div class="szc-p-kpi"><b><?php echo esc_html( szc_fa_digits( $funnel['conversion'] ) ); ?>٪</b> نرخ تبدیل · <?php echo esc_html( szc_fa_digits( $funnel['registered'] ) ); ?> ثبت‌نام از <?php echo esc_html( szc_fa_digits( $funnel['total'] ) ); ?> سرنخ</div>
				<div class="szc-p-funnel">
					<?php $max = 1; foreach ( $funnel['stages'] as $st ) { $max = max( $max, $st['count'] ); }
					foreach ( $funnel['stages'] as $st ) :
						$w = (int) round( $st['count'] / $max * 100 ); ?>
						<div class="szc-p-frow">
							<span class="szc-p-flabel"><?php echo esc_html( $st['label'] ); ?></span>
							<span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $w ) ); ?>%"></span></span>
							<span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $st['count'] ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<section class="szc-p-card">
				<h2 class="szc-p-h2">تماس‌ها (۷ روز اخیر)</h2>
				<div class="szc-p-funnel">
					<?php $max = 1; foreach ( $byout as $o ) { $max = max( $max, $o['count'] ); }
					foreach ( $byout as $o ) :
						$w = (int) round( $o['count'] / $max * 100 ); ?>
						<div class="szc-p-frow">
							<span class="szc-p-flabel"><?php echo esc_html( $o['label'] ); ?></span>
							<span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $w ) ); ?>%"></span></span>
							<span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $o['count'] ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php if ( $board ) : ?>
			<section class="szc-p-card">
				<h2 class="szc-p-h2">عملکرد کارشناسان (تماس‌های امروز)</h2>
				<div class="szc-p-tablewrap">
					<table class="szc-p-table">
						<thead><tr><th>کارشناس</th><th>تماس امروز</th></tr></thead>
						<tbody>
						<?php foreach ( $board as $b ) : ?>
							<tr><td data-th="کارشناس"><?php echo esc_html( $b['name'] ); ?></td><td data-th="تماس امروز"><?php echo esc_html( szc_fa_digits( $b['calls'] ) ); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
}
