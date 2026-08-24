<?php
/** Product metabox for Product Page v2. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Metabox {

	const NONCE = 'spp_v2_course_nonce';

	public static function init() {
		add_action( 'add_meta_boxes_product', array( __CLASS__, 'add' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function add() {
		add_meta_box( 'spp_v2_course', 'تنظیمات صفحه دوره سازان', array( __CLASS__, 'render' ), 'product', 'normal', 'high' );
	}

	public static function render( $post ) {
		$tabs  = SPP_V2_Data::tabs();
		$data  = SPP_V2_Data::get( $post->ID );
		$first = array_key_first( $tabs );
		wp_nonce_field( 'spp_v2_save_' . $post->ID, self::NONCE );
		?>
		<div class="spp-v2-admin" dir="rtl">
			<div class="spp-v2-admin__intro">
				<strong>صفحه دوره نسل جدید</strong>
				<span>اطلاعات WooCommerce مثل عنوان، تصویر شاخص، قیمت، موجودی و افزودن به سبد خرید به‌صورت پویا استفاده می‌شوند. اینجا فقط اطلاعات اختصاصی هر دوره را وارد کنید.</span>
			</div>
			<div class="spp-v2-admin__layout">
				<nav class="spp-v2-admin__tabs" aria-label="بخش‌های تنظیمات دوره">
					<?php foreach ( $tabs as $tab_id => $tab ) : ?>
						<button type="button" class="spp-v2-admin__tab<?php echo $tab_id === $first ? ' is-active' : ''; ?>" data-spp-v2-tab="<?php echo esc_attr( $tab_id ); ?>" aria-selected="<?php echo $tab_id === $first ? 'true' : 'false'; ?>">
							<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span><?php echo esc_html( $tab['label'] ); ?>
						</button>
					<?php endforeach; ?>
				</nav>
				<div class="spp-v2-admin__panels">
					<?php foreach ( $tabs as $tab_id => $tab ) : ?>
						<section class="spp-v2-admin__panel<?php echo $tab_id === $first ? ' is-active' : ''; ?>" data-spp-v2-panel="<?php echo esc_attr( $tab_id ); ?>"<?php echo $tab_id === $first ? '' : ' hidden'; ?>>
							<header><h3><?php echo esc_html( $tab['label'] ); ?></h3></header>
							<?php SPP_V2_Fields::render( 'spp_v2[' . $tab_id . ']', $tab['fields'], $data[ $tab_id ] ); ?>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), 'spp_v2_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || 'product' !== $post->post_type ) {
			return;
		}

		$raw = isset( $_POST['spp_v2'] ) && is_array( $_POST['spp_v2'] ) ? $_POST['spp_v2'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_post_meta( $post_id, SPP_V2_Data::META_KEY, SPP_V2_Data::sanitize( $raw ) );
	}

	public static function assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_media();
		if ( function_exists( 'wp_enqueue_editor' ) ) {
			wp_enqueue_editor();
		}
		wp_enqueue_style( 'spp-v2-admin', SPP_URL . 'assets/css/spp-v2-admin.css', array(), SPP_VERSION );
		wp_enqueue_script( 'spp-v2-admin', SPP_URL . 'assets/js/spp-v2-admin.js', array( 'jquery', 'jquery-ui-sortable' ), SPP_VERSION, true );
	}
}
