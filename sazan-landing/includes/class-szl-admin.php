<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Admin list table + CSV export for course requests. */
class SZL_Admin {

	public static function init() {
		$cpt = SZL_Requests::CPT;
		add_filter( "manage_{$cpt}_posts_columns", array( __CLASS__, 'columns' ) );
		add_action( "manage_{$cpt}_posts_custom_column", array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
		add_action( 'admin_footer-edit.php', array( __CLASS__, 'export_button' ) );
		add_action( 'admin_post_szl_export', array( __CLASS__, 'export' ) );
	}

	public static function columns( $cols ) {
		return array(
			'cb'        => $cols['cb'] ?? '',
			'szl_name'  => 'نام',
			'szl_phone' => 'موبایل',
			'szl_biz'   => 'کسب‌وکار',
			'szl_note'  => 'چالش',
			'date'      => 'تاریخ ثبت',
		);
	}

	public static function column( $col, $post_id ) {
		$map = array(
			'szl_name'  => '_szl_name',
			'szl_phone' => '_szl_phone',
			'szl_biz'   => '_szl_business',
			'szl_note'  => '_szl_note',
		);
		if ( ! isset( $map[ $col ] ) ) {
			return;
		}
		$val = (string) get_post_meta( $post_id, $map[ $col ], true );
		if ( 'szl_note' === $col ) {
			$val = wp_trim_words( $val, 12, '…' );
		}
		echo esc_html( $val !== '' ? $val : '—' );
	}

	public static function metabox() {
		add_meta_box( 'szl_req', 'جزئیات درخواست', array( __CLASS__, 'metabox_html' ), SZL_Requests::CPT, 'normal', 'high' );
	}

	public static function metabox_html( $post ) {
		$rows = array(
			'نام و نام خانوادگی' => '_szl_name',
			'شماره موبایل'       => '_szl_phone',
			'کسب‌وکار'           => '_szl_business',
			'مهم‌ترین چالش'      => '_szl_note',
			'صفحه ثبت'           => '_szl_source',
			'IP'                 => '_szl_ip',
		);
		echo '<table class="widefat striped"><tbody>';
		foreach ( $rows as $label => $key ) {
			$val = (string) get_post_meta( $post->ID, $key, true );
			echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . esc_html( $val !== '' ? $val : '—' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	public static function export_button() {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== SZL_Requests::CPT ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=szl_export' ), 'szl_export' );
		?>
		<script>
		jQuery(function ($) {
			$('.wrap .wp-heading-inline').after(
				' <a href="<?php echo esc_url( $url ); ?>" class="page-title-action">خروجی CSV</a>'
			);
		});
		</script>
		<?php
	}

	public static function export() {
		if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'szl_export' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		$posts = get_posts( array(
			'post_type'      => SZL_Requests::CPT,
			'posts_per_page' => -1,
			'post_status'    => 'any',
		) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=course-requests-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // BOM so Excel reads UTF-8 Persian correctly.
		fputcsv( $out, array( 'تاریخ', 'نام', 'موبایل', 'کسب‌وکار', 'چالش', 'صفحه' ) );
		foreach ( $posts as $p ) {
			fputcsv( $out, array(
				get_the_date( 'Y-m-d H:i', $p ),
				get_post_meta( $p->ID, '_szl_name', true ),
				"\t" . get_post_meta( $p->ID, '_szl_phone', true ),
				get_post_meta( $p->ID, '_szl_business', true ),
				get_post_meta( $p->ID, '_szl_note', true ),
				get_post_meta( $p->ID, '_szl_source', true ),
			) );
		}
		fclose( $out );
		exit;
	}
}
