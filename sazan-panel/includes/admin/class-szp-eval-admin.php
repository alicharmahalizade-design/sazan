<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** پیشخوان «ارزیابی من» — مدیریت تارگت/نتیجه‌ی هفتگی هر کاربر (به‌ویژه هفته‌های گذشته). */
class SZP_Eval_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 11 );
		add_action( 'admin_post_szp_eval_admin_save', array( __CLASS__, 'handle_save' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'ارزیابی (تارگت‌ها)', 'ارزیابی', 'manage_options', 'szp-eval', array( __CLASS__, 'render' ) );
	}

	protected static function amount_attr( $n ) {
		$n = (float) $n;
		return ( $n == floor( $n ) ) ? (string) (int) $n : (string) $n;
	}

	public static function render() {
		$uid = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
		echo '<div class="wrap szp-groups-wrap szp-eval-admin"><h1>ارزیابی — مدیریت تارگت‌ها</h1>';

		if ( isset( $_GET['msg'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div>';
		}

		echo '<p class="description">کاربری را انتخاب کنید تا تارگت‌ها و نتایج هفتگی‌اش را وارد یا ویرایش کنید. هفته‌های گذشته (مثلاً ۱ تا ۵) را همین‌جا برای هر شخص ثبت کنید؛ از هفته‌ی جاری به بعد، خود کاربر از ویجت «ارزیابی من» وارد می‌کند.</p>';

		$go = add_query_arg( array( 'page' => 'szp-eval' ), admin_url( 'admin.php' ) );
		echo '<div class="szp-user-search" data-go="' . esc_url( $go ) . '" style="max-width:480px;margin:10px 0 18px">';
		echo '<input type="text" class="regular-text szp-user-q" placeholder="جستجوی کاربر (نام، ایمیل یا موبایل)...">';
		echo '<div class="szp-user-results"></div></div>';

		if ( ! $uid ) {
			echo '</div>';
			return;
		}
		$u = get_userdata( $uid );
		if ( ! $u ) {
			echo '<p>کاربر یافت نشد.</p></div>';
			return;
		}

		$info   = SZP_Groups::user_info( $uid );
		$mobile = $info && $info['mobile'] !== '' ? szp_fa_digits( $info['mobile'] ) : '—';
		printf(
			'<h2 style="margin-top:6px">%s <span style="font-weight:400;color:#666;font-size:14px">(موبایل: %s — %s)</span></h2>',
			esc_html( $info ? $info['name'] : $u->display_name ), esc_html( $mobile ), esc_html( $u->user_email )
		);

		self::edit_table( $uid );
		echo '</div>';
	}

	protected static function edit_table( $uid ) {
		$weeks = SZP_Eval::weeks( $uid );
		$max   = 0;
		foreach ( $weeks as $w ) {
			$max = max( $max, (int) $w->week_no );
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'szp_eval_admin' ); ?>
			<input type="hidden" name="action" value="szp_eval_admin_save">
			<input type="hidden" name="eval_user" value="<?php echo (int) $uid; ?>">

			<table class="widefat striped" style="max-width:820px;margin-top:10px">
				<thead><tr>
					<th style="width:90px">هفته</th>
					<th>تارگت (عدد)</th>
					<th>نتیجه (اختیاری)</th>
					<th style="width:150px">وضعیت</th>
					<th style="width:60px">حذف</th>
				</tr></thead>
				<tbody>
				<?php
				$i = 0;
				foreach ( $weeks as $w ) :
					$st   = SZP_Eval::compute_status( $w->target, $w->result, $w->has_result );
					$meta = SZP_Eval::status_meta( $st );
					$pct  = $w->has_result ? SZP_Eval::pct( $w->target, $w->result ) : 0;
					?>
					<tr>
						<td><input type="number" name="rows[<?php echo $i; ?>][week]" value="<?php echo (int) $w->week_no; ?>" class="small-text" min="1"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][target]" value="<?php echo esc_attr( self::amount_attr( $w->target ) ); ?>" class="regular-text" inputmode="numeric"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][result]" value="<?php echo $w->has_result ? esc_attr( self::amount_attr( $w->result ) ) : ''; ?>" class="regular-text" inputmode="numeric" placeholder="—"></td>
						<td><span style="color:<?php echo esc_attr( $meta['color'] ); ?>;font-weight:700"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span><?php echo $w->has_result ? ' <small>(' . esc_html( szp_fa_digits( $pct ) ) . '٪)</small>' : ''; ?></td>
						<td style="text-align:center"><input type="checkbox" name="rows[<?php echo $i; ?>][del]" value="1"></td>
					</tr>
					<?php
					$i++;
				endforeach;

				// سه ردیف خالی برای افزودن هفته‌های جدید.
				for ( $k = 1; $k <= 3; $k++ ) :
					$suggest = $max + $k;
					?>
					<tr>
						<td><input type="number" name="rows[<?php echo $i; ?>][week]" value="<?php echo (int) $suggest; ?>" class="small-text" min="1"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][target]" value="" class="regular-text" inputmode="numeric" placeholder="تارگت"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][result]" value="" class="regular-text" inputmode="numeric" placeholder="نتیجه (اختیاری)"></td>
						<td><span style="color:#9ca3af">ردیف جدید</span></td>
						<td></td>
					</tr>
					<?php
					$i++;
				endfor;
				?>
				</tbody>
			</table>
			<p class="description" style="max-width:820px">برای «حذف» یک هفته تیک ستون حذف را بزنید. ردیف‌های جدید خالی نادیده گرفته می‌شوند مگر تارگت یا نتیجه داشته باشند.</p>
			<p><button class="button button-primary">ذخیره تغییرات</button></p>
		</form>
		<?php
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_eval_admin' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$uid  = absint( $_POST['eval_user'] ?? 0 );
		$rows = isset( $_POST['rows'] ) ? (array) wp_unslash( $_POST['rows'] ) : array();
		if ( $uid ) {
			foreach ( $rows as $r ) {
				$week = isset( $r['week'] ) ? absint( $r['week'] ) : 0;
				if ( ! $week ) {
					continue;
				}
				if ( ! empty( $r['del'] ) ) {
					SZP_Eval::delete_week( $uid, $week );
					continue;
				}
				$target     = szp_parse_amount( $r['target'] ?? '' );
				$result_raw = isset( $r['result'] ) ? trim( (string) $r['result'] ) : '';
				$result     = ( $result_raw === '' ) ? null : szp_parse_amount( $result_raw );
				if ( $target <= 0 && $result === null ) {
					continue; // ردیف خالی
				}
				SZP_Eval::upsert( $uid, $week, $target, $result );
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-eval', 'user' => $uid, 'msg' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
