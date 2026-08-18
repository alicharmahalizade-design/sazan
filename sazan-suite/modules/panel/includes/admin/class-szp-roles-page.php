<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * پنل مدیریتیِ «نقش‌ها و تیم‌ها» — یک‌جا مدیریتِ کوچینگ، مدیر سازمان و تیم فروش
 * و رابطه‌ی مدیر ↔ زیرمجموعه، با نمای زنده‌ی ساختار و ذخیره‌ی خودکار.
 */
class SZP_Roles_Page {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 10 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_ajax_szp_role_save', array( __CLASS__, 'ajax_save' ) );
	}

	public static function menu() {
		add_submenu_page(
			'sazan-panel',
			'نقش‌ها و تیم‌ها',
			'نقش‌ها و تیم‌ها',
			'manage_options',
			'szp-roles',
			array( __CLASS__, 'render' )
		);
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'szp-roles' ) === false ) {
			return;
		}
		$css = SZP_DIR . 'assets/css/sazan-roles.css';
		$js  = SZP_DIR . 'assets/js/sazan-roles.js';
		wp_enqueue_style( 'szp-roles', SZP_URL . 'assets/css/sazan-roles.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
		wp_enqueue_script( 'szp-roles', SZP_URL . 'assets/js/sazan-roles.js', array(), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
		wp_localize_script( 'szp-roles', 'SZP_ROLES', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szp_admin' ),
			'roles' => SZP_Eval_Roles::roles_map(),
			'users' => self::managed_users(),
		) );
	}

	/** همه کاربران برای اینکه عضو جدید پیش از گرفتن نقش نیز قابل اتصال به مدیر باشد. */
	protected static function managed_user_ids() {
		$ids = get_users( array(
			'fields' => 'ID',
			'number' => 5000,
		) );
		$ids = array_map( 'intval', $ids );

		$role_ids = get_users( array(
			'role__in' => array_keys( SZP_Eval_Roles::roles_map() ),
			'fields'   => 'ID',
			'number'   => 3000,
		) );
		$ids = array_merge( $ids, array_map( 'intval', $role_ids ) );

		$with_manager = get_users( array(
			'meta_query' => array( array( 'key' => SZP_Eval_Roles::META_MANAGER, 'compare' => 'EXISTS' ) ),
			'fields'     => 'ID',
			'number'     => 3000,
		) );
		$ids = array_merge( $ids, array_map( 'intval', $with_manager ) );

		$ids = array_merge( $ids, SZP_Eval_Roles::coaching_setting_ids() );
		if ( class_exists( 'SZP_Eval' ) ) {
			$ids = array_merge( $ids, SZP_Eval::participants() );
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/** داده‌ی کاربر برای اپ فرانت. */
	public static function user_row( $uid ) {
		$info = SZP_Groups::user_info( $uid );
		if ( ! $info ) {
			return null;
		}
		return array(
			'id'        => (int) $uid,
			'name'      => $info['name'],
			'mobile'    => $info['mobile'] !== '' ? szp_fa_digits( $info['mobile'] ) : '',
			'email'     => $info['email'],
			'avatar'    => get_avatar_url( $uid, array( 'size' => 48 ) ),
			'role'      => SZP_Eval_Roles::primary_role( $uid ),
			'coaching'  => SZP_Eval_Roles::is_coaching( $uid ) ? 1 : 0,
			'manager'   => SZP_Eval_Roles::manager_id( $uid ),
		);
	}

	public static function managed_users() {
		$out = array();
		foreach ( self::managed_user_ids() as $uid ) {
			$row = self::user_row( $uid );
			if ( $row ) {
				$out[] = $row;
			}
		}
		// مرتب‌سازی: کوچینگ، مدیر، مدیر/فروش، فروش، سپس نام.
		$order = array(
			SZP_Eval_Roles::ROLE_COACHING      => 0,
			SZP_Eval_Roles::ROLE_ORG_MANAGER   => 1,
			SZP_Eval_Roles::ROLE_MANAGER_SALES => 2,
			SZP_Eval_Roles::ROLE_SALES         => 3,
			''                                 => 4,
		);
		usort( $out, function ( $a, $b ) use ( $order ) {
			$ra = isset( $order[ $a['role'] ] ) ? $order[ $a['role'] ] : 5;
			$rb = isset( $order[ $b['role'] ] ) ? $order[ $b['role'] ] : 5;
			if ( $ra !== $rb ) {
				return $ra <=> $rb;
			}
			return strcoll( $a['name'], $b['name'] );
		} );
		return $out;
	}

	public static function ajax_save() {
		check_ajax_referer( 'szp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ) );
		}
		$uid  = isset( $_POST['user'] ) ? absint( $_POST['user'] ) : 0;
		$role = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : '';
		$mid  = isset( $_POST['manager'] ) ? absint( $_POST['manager'] ) : 0;

		$u = $uid ? get_user_by( 'id', $uid ) : null;
		if ( ! $u ) {
			wp_send_json_error( array( 'msg' => 'کاربر یافت نشد.' ) );
		}

		$roles = SZP_Eval_Roles::roles_map();
		if ( $role !== '' && ! isset( $roles[ $role ] ) ) {
			wp_send_json_error( array( 'msg' => 'نقش نامعتبر است.' ) );
		}

		// حذف نقش‌های ارزیابیِ قبلی، افزودن نقش جدید (سایر نقش‌ها دست‌نخورده می‌مانند).
		foreach ( array_keys( $roles ) as $slug ) {
			if ( in_array( $slug, (array) $u->roles, true ) ) {
				$u->remove_role( $slug );
			}
		}
		if ( $role !== '' ) {
			$u->add_role( $role );
		}

		// مدیر سازمانِ مربوطه (فقط برای تیم فروش/مدیر-فروش معنا دارد).
		$is_sales = in_array( $role, array( SZP_Eval_Roles::ROLE_SALES, SZP_Eval_Roles::ROLE_MANAGER_SALES ), true );
		if ( $mid > 0 && $is_sales && $mid !== $uid ) {
			update_user_meta( $uid, SZP_Eval_Roles::META_MANAGER, $mid );
		} else {
			delete_user_meta( $uid, SZP_Eval_Roles::META_MANAGER );
		}

		wp_send_json_success( array(
			'msg'  => 'ذخیره شد',
			'user' => self::user_row( $uid ),
		) );
	}

	public static function render() {
		?>
		<div class="wrap">
			<div class="szp-roles-app" id="szp-roles-app">
				<div class="szp-roles-loading">در حال بارگذاری پنل…</div>
			</div>
			<noscript><p>برای استفاده از این پنل، جاوااسکریپت مرورگر باید فعال باشد.</p></noscript>
		</div>
		<?php
	}
}
