<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Groups_Page {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 11 );
		add_action( 'admin_post_szp_save_group', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_szp_delete_group', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_szp_group_add_all', array( __CLASS__, 'handle_add_all' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'گروه‌ها', 'گروه‌ها', 'manage_options', 'szp-groups', array( __CLASS__, 'render' ) );
	}

	public static function render() {
		$edit = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		echo '<div class="wrap szp-groups-wrap"><h1>گروه‌ها</h1>';
		if ( isset( $_GET['msg'] ) ) {
			$texts = array(
				'added_all' => 'همه کاربران به گروه اضافه شدند.',
			);
			$key = sanitize_key( wp_unslash( $_GET['msg'] ) );
			$txt = isset( $texts[ $key ] ) ? $texts[ $key ] : 'با موفقیت ذخیره شد.';
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $txt ) . '</p></div>';
		}
		if ( $edit ) {
			self::edit_form( $edit );
		} else {
			self::new_form();
			self::list_table();
		}
		echo '</div>';
	}

	protected static function new_form() {
		?>
		<h2>ساخت گروه جدید</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'szp_save_group' ); ?>
			<input type="hidden" name="action" value="szp_save_group">
			<input type="hidden" name="group_id" value="0">
			<input type="text" name="group_name" placeholder="نام گروه" required class="regular-text">
			<button class="button button-primary">ساخت گروه</button>
		</form>
		<hr>
		<?php
	}

	protected static function list_table() {
		$groups = SZP_Groups::all();
		echo '<h2>لیست گروه‌ها</h2>';
		if ( ! $groups ) {
			echo '<p>هنوز گروهی ساخته نشده است.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>نام گروه</th><th>تعداد اعضا</th><th>عملیات</th></tr></thead><tbody>';
		foreach ( $groups as $g ) {
			$count    = count( SZP_Groups::members( $g->id ) );
			$edit_url = add_query_arg( array( 'page' => 'szp-groups', 'edit' => $g->id ), admin_url( 'admin.php' ) );
			$del_url  = wp_nonce_url( add_query_arg( array( 'action' => 'szp_delete_group', 'group_id' => $g->id ), admin_url( 'admin-post.php' ) ), 'szp_delete_group' );
			printf(
				'<tr><td><strong>%1$s</strong></td><td>%2$d</td><td><a class="button" href="%3$s">مدیریت اعضا</a> <a class="button" href="%4$s" onclick="return confirm(\'این گروه حذف شود؟\')">حذف</a></td></tr>',
				esc_html( $g->name ), (int) $count, esc_url( $edit_url ), esc_url( $del_url )
			);
		}
		echo '</tbody></table>';
	}

	protected static function edit_form( $id ) {
		$g = SZP_Groups::get( $id );
		if ( ! $g ) {
			echo '<p>گروه یافت نشد.</p>';
			return;
		}
		$members = SZP_Groups::members( $id );
		?>
		<h2>مدیریت گروه: <?php echo esc_html( $g->name ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:8px 0 18px"
			onsubmit="return confirm('همه کاربران سایت به این گروه اضافه شوند؟');">
			<?php wp_nonce_field( 'szp_group_add_all' ); ?>
			<input type="hidden" name="action" value="szp_group_add_all">
			<input type="hidden" name="group_id" value="<?php echo (int) $id; ?>">
			<button class="button">افزودن همه کاربران به گروه</button>
			<span class="description">تعداد کل کاربران سایت: <?php echo esc_html( szp_fa_digits( count_users()['total_users'] ) ); ?></span>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'szp_save_group' ); ?>
			<input type="hidden" name="action" value="szp_save_group">
			<input type="hidden" name="group_id" value="<?php echo (int) $id; ?>">
			<p><label><strong>نام گروه</strong></label><br>
				<input type="text" name="group_name" value="<?php echo esc_attr( $g->name ); ?>" class="regular-text"></p>

			<p><label><strong>اعضا</strong> <span class="szp-members-count">(<?php echo esc_html( szp_fa_digits( count( $members ) ) ); ?> نفر)</span></label></p>
			<div class="szp-user-search szp-user-search-table" data-target="group_members">
				<input type="text" class="regular-text szp-user-q" placeholder="جستجوی کاربر (نام، ایمیل یا موبایل)...">
				<div class="szp-user-results"></div>
				<table class="widefat striped szp-members-table">
					<thead><tr>
						<th style="width:40px">#</th>
						<th>نام</th>
						<th>نام خانوادگی</th>
						<th>موبایل</th>
						<th>ایمیل</th>
						<th style="width:60px">حذف</th>
					</tr></thead>
					<tbody class="szp-members-body">
					<?php
					$row = 0;
					foreach ( $members as $uid ) {
						$info = SZP_Groups::user_info( $uid );
						if ( ! $info ) {
							continue;
						}
						$row++;
						printf(
							'<tr data-id="%1$d"><td class="szp-mrow-n">%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td>'
							. '<td><input type="hidden" name="group_members[]" value="%1$d"><a href="#" class="szp-member-del" title="حذف از گروه">×</a></td></tr>',
							(int) $uid,
							esc_html( szp_fa_digits( $row ) ),
							esc_html( $info['first'] !== '' ? $info['first'] : $info['name'] ),
							esc_html( $info['last'] ),
							esc_html( $info['mobile'] !== '' ? szp_fa_digits( $info['mobile'] ) : '—' ),
							esc_html( $info['email'] )
						);
					}
					?>
					</tbody>
				</table>
				<p class="szp-members-empty"<?php echo $members ? ' style="display:none"' : ''; ?>>هنوز عضوی در این گروه نیست.</p>
			</div>
			<p style="margin-top:14px">
				<button class="button button-primary">ذخیره</button>
				<a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'szp-groups', admin_url( 'admin.php' ) ) ); ?>">بازگشت</a>
			</p>
		</form>
		<?php
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_save_group' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$id   = absint( $_POST['group_id'] ?? 0 );
		$name = sanitize_text_field( wp_unslash( $_POST['group_name'] ?? '' ) );
		if ( $id ) {
			SZP_Groups::rename( $id, $name );
			$members = isset( $_POST['group_members'] ) ? array_map( 'intval', (array) $_POST['group_members'] ) : array();
			SZP_Groups::set_members( $id, $members );
		} else {
			$id = SZP_Groups::create( $name );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-groups', 'edit' => $id, 'msg' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_delete_group' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		SZP_Groups::delete( absint( $_GET['group_id'] ?? 0 ) );
		wp_safe_redirect( add_query_arg( 'page', 'szp-groups', admin_url( 'admin.php' ) ) );
		exit;
	}

	/** Bulk: add every user on the site to this group (keeps existing members). */
	public static function handle_add_all() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_group_add_all' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$id = absint( $_POST['group_id'] ?? 0 );
		if ( $id ) {
			SZP_Groups::add_members( $id, SZP_Groups::all_user_ids() );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-groups', 'edit' => $id, 'msg' => 'added_all' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
