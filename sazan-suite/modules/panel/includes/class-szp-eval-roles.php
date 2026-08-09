<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * نقش‌های ارزیابی و سلسله‌مراتب «مدیر سازمان ← تیم فروش».
 *
 * چهار نقش (نقش‌های وردپرس):
 *   szp_coaching       — کوچینگ: همه‌ی نفرات و همه‌ی گروه‌ها را می‌بیند.
 *   szp_org_manager    — مدیر سازمان: فقط زیرمجموعه‌ی خودش را می‌بیند (ثبت نمی‌کند).
 *   szp_sales          — تیم فروش: تارگت/نتیجه‌ی خودش را ثبت می‌کند و می‌داند زیر گروه کدام مدیر است.
 *   szp_manager_sales  — مدیر سازمان/تیم فروش: هم تیمش را می‌بیند، هم خودش ثبت می‌کند.
 *
 * رابطه‌ی «چه کسی زیر کدام مدیر است» با متادیتای کاربر `szp_manager_id` نگه‌داری می‌شود.
 */
class SZP_Eval_Roles {

	const ROLE_COACHING      = 'szp_coaching';
	const ROLE_ORG_MANAGER   = 'szp_org_manager';
	const ROLE_SALES         = 'szp_sales';
	const ROLE_MANAGER_SALES = 'szp_manager_sales';
	const META_MANAGER       = 'szp_manager_id';
	const ROLES_VER          = '1';

	public static function init() {
		self::ensure_roles();
		// فیلد «مدیر سازمان مربوطه» روی صفحه‌ی ویرایش کاربر.
		add_action( 'show_user_profile', array( __CLASS__, 'profile_field' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'profile_field' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_profile_field' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_profile_field' ) );
	}

	/* ==================== ثبت نقش‌ها ==================== */

	public static function roles_map() {
		return array(
			self::ROLE_COACHING      => 'کوچینگ',
			self::ROLE_ORG_MANAGER   => 'مدیر سازمان',
			self::ROLE_SALES         => 'تیم فروش',
			self::ROLE_MANAGER_SALES => 'مدیر سازمان/تیم فروش',
		);
	}

	/** ثبت نقش‌ها (بی‌خطر اگر از قبل باشند). روی فعال‌سازی و ارتقا صدا زده می‌شود. */
	public static function ensure_roles() {
		if ( get_option( 'szp_eval_roles_ver' ) === self::ROLES_VER ) {
			return;
		}
		foreach ( self::roles_map() as $slug => $label ) {
			if ( ! get_role( $slug ) ) {
				add_role( $slug, $label, array( 'read' => true ) );
			}
		}
		update_option( 'szp_eval_roles_ver', self::ROLES_VER );
	}

	/* ==================== تشخیص نقش ==================== */

	public static function has_role( $uid, $role ) {
		$u = get_userdata( (int) $uid );
		return $u && in_array( $role, (array) $u->roles, true );
	}

	/** آیا کاربر «کوچینگ» است؟ کوچ‌های دوره + ادمین + لیست تنظیمات + نقش کوچینگ. */
	public static function is_coaching( $uid ) {
		$uid = (int) $uid;
		if ( ! $uid ) {
			return false;
		}
		if ( user_can( $uid, 'manage_options' ) ) {
			return true;
		}
		if ( self::has_role( $uid, self::ROLE_COACHING ) ) {
			return true;
		}
		if ( class_exists( 'SZP_Coach' ) && SZP_Coach::is_coach( $uid ) ) {
			return true;
		}
		if ( in_array( $uid, self::coaching_setting_ids(), true ) ) {
			return true;
		}
		return false;
	}

	/** آیا مدیر سازمان است (زیرمجموعه دارد و تیمش را می‌بیند)؟ */
	public static function is_org_manager( $uid ) {
		return self::has_role( $uid, self::ROLE_ORG_MANAGER ) || self::has_role( $uid, self::ROLE_MANAGER_SALES );
	}

	/** آیا تیم فروش است (خودش ثبت می‌کند)؟ */
	public static function is_sales( $uid ) {
		return self::has_role( $uid, self::ROLE_SALES ) || self::has_role( $uid, self::ROLE_MANAGER_SALES );
	}

	/** آیا این کاربر خودش تارگت/نتیجه ثبت می‌کند؟ (تیم فروش یا مدیر/فروش) */
	public static function registers_own( $uid ) {
		return self::is_sales( $uid );
	}

	/** آیا این کاربر تیمی برای مشاهده دارد؟ (کوچینگ یا مدیر سازمان) */
	public static function sees_team( $uid ) {
		return self::is_coaching( $uid ) || self::is_org_manager( $uid );
	}

	/** بالاترین نقشِ قابل‌نمایش کاربر (برای نشان/برچسب). */
	public static function primary_role( $uid ) {
		if ( self::is_coaching( $uid ) ) {
			return self::ROLE_COACHING;
		}
		if ( self::has_role( $uid, self::ROLE_MANAGER_SALES ) ) {
			return self::ROLE_MANAGER_SALES;
		}
		if ( self::has_role( $uid, self::ROLE_ORG_MANAGER ) ) {
			return self::ROLE_ORG_MANAGER;
		}
		if ( self::has_role( $uid, self::ROLE_SALES ) ) {
			return self::ROLE_SALES;
		}
		return '';
	}

	public static function role_label( $slug ) {
		$m = self::roles_map();
		return isset( $m[ $slug ] ) ? $m[ $slug ] : '';
	}

	/* ==================== سلسله‌مراتب مدیر ↔ فروش ==================== */

	/** شناسه‌ی مدیر سازمانِ این کاربر (۰ اگر تعیین نشده). */
	public static function manager_id( $uid ) {
		return (int) get_user_meta( (int) $uid, self::META_MANAGER, true );
	}

	/** نام مدیر سازمانِ این کاربر (خالی اگر نباشد). */
	public static function manager_name( $uid ) {
		$mid = self::manager_id( $uid );
		if ( ! $mid ) {
			return '';
		}
		$info = SZP_Groups::user_info( $mid );
		return $info ? $info['name'] : '';
	}

	/** شناسه‌ی زیرمجموعه‌های یک مدیر (کاربرانی که مدیرشان این فرد است). */
	public static function subordinates( $manager_id ) {
		$manager_id = (int) $manager_id;
		if ( ! $manager_id ) {
			return array();
		}
		$ids = get_users( array(
			'meta_key'   => self::META_MANAGER,
			'meta_value' => $manager_id,
			'fields'     => 'ID',
			'number'     => 500,
		) );
		return array_map( 'intval', $ids );
	}

	/** کاربرانی که می‌توانند به‌عنوان «مدیر سازمان» انتخاب شوند: [id => name]. */
	public static function manager_choices() {
		$out = array();
		$ids = get_users( array(
			'role__in' => array( self::ROLE_ORG_MANAGER, self::ROLE_MANAGER_SALES ),
			'fields'   => 'ID',
			'number'   => 500,
		) );
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			$out[ (int) $id ] = $info ? $info['name'] : ( '#' . $id );
		}
		return $out;
	}

	/* ==================== کوچینگ: گیرندگان و مشاهده ==================== */

	/** شناسه‌ی کاربرانِ لیستِ «کوچینگ» در تنظیمات (نام‌کاربری/ایمیل/شناسه). */
	public static function coaching_setting_ids() {
		return self::parse_user_list( (string) SZP_Eval::opt( 'coaching_users' ) );
	}

	/** همه‌ی نفراتِ قابل‌مشاهده برای کوچینگ (کل تیم فروش + هرکس که تارگت دارد). */
	public static function all_people_ids() {
		$ids = get_users( array(
			'role__in' => array( self::ROLE_SALES, self::ROLE_MANAGER_SALES ),
			'fields'   => 'ID',
			'number'   => 2000,
		) );
		$ids = array_map( 'intval', $ids );
		if ( class_exists( 'SZP_Eval' ) ) {
			$ids = array_merge( $ids, SZP_Eval::participants() );
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/** گیرندگان پیامکِ «کوچ» هنگام ثبت تارگت/نتیجه (نقش کوچینگ + لیست تنظیمات + کوچ‌های دوره). */
	public static function coach_recipient_ids() {
		$ids = get_users( array(
			'role'   => self::ROLE_COACHING,
			'fields' => 'ID',
			'number' => 200,
		) );
		$ids = array_map( 'intval', $ids );
		$ids = array_merge( $ids, self::coaching_setting_ids() );
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/* ==================== نمای پروفایل کاربر (مدیر مربوطه) ==================== */

	public static function profile_field( $user ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		$current  = (int) get_user_meta( $user->ID, self::META_MANAGER, true );
		$choices  = self::manager_choices();
		?>
		<h2>ارزیابی سازان — سلسله‌مراتب</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="szp_manager_id">مدیر سازمان مربوطه</label></th>
				<td>
					<select name="szp_manager_id" id="szp_manager_id">
						<option value="0">— بدون مدیر —</option>
						<?php foreach ( $choices as $id => $name ) : ?>
							<option value="<?php echo (int) $id; ?>" <?php selected( $current, (int) $id ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">اگر این کاربر «تیم فروش» است، مدیر سازمانی که زیرمجموعه‌ی اوست را انتخاب کنید. نقش کاربر (کوچینگ / مدیر سازمان / تیم فروش) را از بخش «نقش» بالای همین صفحه تعیین کنید.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function save_profile_field( $user_id ) {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		$mid = isset( $_POST['szp_manager_id'] ) ? absint( $_POST['szp_manager_id'] ) : 0;
		if ( $mid ) {
			update_user_meta( $user_id, self::META_MANAGER, $mid );
		} else {
			delete_user_meta( $user_id, self::META_MANAGER );
		}
	}

	/* ==================== کمکی ==================== */

	/** رشته‌ی «نام‌کاربری/ایمیل/شناسه» (جدا با کاما/فاصله/خط‌جدید) → آرایه‌ی شناسه‌ها. */
	public static function parse_user_list( $str ) {
		$ids = array();
		foreach ( preg_split( '/[\s,،؛]+/u', (string) $str ) as $tok ) {
			$tok = trim( $tok );
			if ( $tok === '' ) {
				continue;
			}
			if ( ctype_digit( $tok ) ) {
				$ids[] = (int) $tok;
				continue;
			}
			$u = is_email( $tok ) ? get_user_by( 'email', $tok ) : get_user_by( 'login', $tok );
			if ( ! $u ) {
				$u = get_user_by( 'slug', $tok );
			}
			if ( $u ) {
				$ids[] = (int) $u->ID;
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}
}
