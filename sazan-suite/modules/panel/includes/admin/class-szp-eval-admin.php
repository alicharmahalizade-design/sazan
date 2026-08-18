<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** پیشخوان «ارزیابی من» — مدیریت تارگت/نتیجه‌ی هفتگی هر کاربر (به‌ویژه هفته‌های گذشته). */
class SZP_Eval_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 11 );
		add_action( 'admin_post_szp_eval_admin_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_szp_eval_export', array( __CLASS__, 'handle_export' ) );
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
			self::overview();
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

			<table class="widefat striped" style="max-width:980px;margin-top:10px">
				<thead><tr>
					<th style="width:80px">هفته</th>
					<th>تارگت (عدد)</th>
					<th>نتیجه (اختیاری)</th>
					<th>یادداشت</th>
					<th style="width:150px">وضعیت</th>
					<th style="width:50px">حذف</th>
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
						<td><input type="text" name="rows[<?php echo $i; ?>][note]" value="<?php echo esc_attr( (string) $w->note ); ?>" class="regular-text" placeholder="—"></td>
						<td><span style="color:<?php echo esc_attr( $meta['color'] ); ?>;font-weight:700"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span><?php echo $w->has_result ? ' <small>(' . esc_html( szp_fa_digits( $pct ) ) . '٪)</small>' : ''; ?></td>
						<td style="text-align:center"><input type="checkbox" name="rows[<?php echo $i; ?>][del]" value="1"></td>
					</tr>
					<?php
					$i++;
				endforeach;

				// سه ردیف خالی برای افزودن هفته‌های جدید (از جمله هفته‌های بعدی).
				for ( $k = 1; $k <= 3; $k++ ) :
					$suggest = $max + $k;
					?>
					<tr>
						<td><input type="number" name="rows[<?php echo $i; ?>][week]" value="<?php echo (int) $suggest; ?>" class="small-text" min="1"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][target]" value="" class="regular-text" inputmode="numeric" placeholder="تارگت"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][result]" value="" class="regular-text" inputmode="numeric" placeholder="نتیجه (اختیاری)"></td>
						<td><input type="text" name="rows[<?php echo $i; ?>][note]" value="" class="regular-text" placeholder="یادداشت (اختیاری)"></td>
						<td><span style="color:#9ca3af">ردیف جدید</span></td>
						<td></td>
					</tr>
					<?php
					$i++;
				endfor;
				?>
				</tbody>
			</table>
			<p class="description" style="max-width:980px">هفته‌های گذشته (۱ تا ۵) و حتی هفته‌های بعدی را می‌توانید این‌جا دستی وارد کنید. برای «حذف» تیک ستون حذف را بزنید. ردیف‌های خالی نادیده گرفته می‌شوند.</p>
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
				$note       = isset( $r['note'] ) ? (string) $r['note'] : '';
				if ( $target <= 0 && $result === null && trim( $note ) === '' ) {
					continue; // ردیف خالی
				}
				SZP_Eval::upsert( $uid, $week, $target, $result, $note );
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-eval', 'user' => $uid, 'msg' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/** نمای کلی: همه‌ی اشخاص دارای تارگت + دکمه خروجی اکسل. */
	protected static function overview() {
		$ids = SZP_Eval::participants();
		echo '<hr><h2 style="display:flex;align-items:center;gap:12px">نمای کلی اعضا';
		$export = wp_nonce_url( add_query_arg( array( 'action' => 'szp_eval_export' ), admin_url( 'admin-post.php' ) ), 'szp_eval_export' );
		echo ' <a class="button" href="' . esc_url( $export ) . '">خروجی اکسل (CSV)</a></h2>';

		if ( ! $ids ) {
			echo '<p>هنوز هیچ کاربری تارگتی ندارد.</p>';
			return;
		}
		$cur = SZP_Eval::currency();
		echo '<table class="widefat striped" style="max-width:1000px"><thead><tr>'
			. '<th>کاربر</th><th>موبایل</th><th>هفته جاری</th><th>آخرین وضعیت</th><th>تعداد هفته</th><th>محقق‌شده</th><th>میانگین تحقق</th><th></th>'
			. '</tr></thead><tbody>';
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			if ( ! $info ) {
				continue;
			}
			$sum  = SZP_Eval::user_summary( $id );
			$lw   = $sum['latest'];
			$st   = $lw ? SZP_Eval::compute_status( $lw->target, $lw->result, $lw->has_result ) : 'pending';
			$meta = SZP_Eval::status_meta( $st );
			$url  = add_query_arg( array( 'page' => 'szp-eval', 'user' => $id ), admin_url( 'admin.php' ) );
			printf(
				'<tr><td><strong>%s</strong></td><td>%s</td><td>%s</td><td><span style="color:%s;font-weight:700">%s</span></td><td>%s</td><td>%s</td><td>%s٪</td><td><a class="button button-small" href="%s">مدیریت</a></td></tr>',
				esc_html( $info['name'] ),
				esc_html( $info['mobile'] !== '' ? szp_fa_digits( $info['mobile'] ) : '—' ),
				$lw ? esc_html( 'هفته ' . szp_fa_digits( $lw->week_no ) ) : '—',
				esc_attr( $meta['color'] ), esc_html( $meta['label'] ),
				esc_html( szp_fa_digits( $sum['weeks'] ) ),
				esc_html( szp_fa_digits( $sum['hit'] ) ),
				esc_html( szp_fa_digits( $sum['avg'] ) ),
				esc_url( $url )
			);
		}
		echo '</tbody></table>';
	}

	/** خروجی CSV همه‌ی هفته‌های همه‌ی کاربران (با BOM برای نمایش صحیح فارسی در اکسل). */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_eval_export' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . SZP_Eval::table() . ' ORDER BY user_id ASC, week_no ASC' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sazan-eval-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM
		fputcsv( $out, array(
			'کاربر', 'موبایل', 'ایمیل', 'هفته', 'تارگت مالی', 'فروش واقعی', 'درصد تحقق سیستمی',
			'وضعیت', 'تاریخ ثبت نتیجه', 'یادداشت', 'نتیجه مطلوب هفته', 'تارگت مشتری جدید',
			'اقدام‌های اصلی', 'مانع احتمالی', 'برنامه رفع مانع', 'انگیزه', 'تعهد',
			'درصد تحقق اعلامی', 'مشتری واقعی', 'بزرگ‌ترین موفقیت', 'نقطه ضعف', 'یادگیری',
		) );
		foreach ( (array) $rows as $r ) {
			$info = SZP_Groups::user_info( $r->user_id );
			$st   = SZP_Eval::compute_status( $r->target, $r->result, $r->has_result );
			$meta = SZP_Eval::status_meta( $st );
			$pct  = $r->has_result ? SZP_Eval::pct( $r->target, $r->result ) : '';
			$date = ( $r->has_result && $r->result_set_at ) ? szp_format_datetime( strtotime( $r->result_set_at ) ) : '';
			fputcsv( $out, array(
				$info ? $info['name'] : ( '#' . $r->user_id ),
				$info ? $info['mobile'] : '',
				$info ? $info['email'] : '',
				$r->week_no,
				(int) $r->target,
				$r->has_result ? (int) $r->result : '',
				$pct,
				$meta['label'],
				$date,
				$r->note,
				$r->target_success ?? '',
				$r->target_customers ?? '',
				! empty( $r->target_actions ) ? implode( ' | ', (array) json_decode( $r->target_actions, true ) ) : '',
				$r->target_obstacle ?? '',
				$r->target_obstacle_plan ?? '',
				$r->motivation ?? '',
				$r->commitment ?? '',
				$r->result_percent ?? '',
				$r->result_customers ?? '',
				$r->result_success ?? '',
				$r->result_weakness ?? '',
				$r->result_learning ?? '',
			) );
		}
		fclose( $out );
		exit;
	}
}
