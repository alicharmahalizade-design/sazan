<?php
/**
 * متاباکسِ «صفحه اختصاصی محصول» در صفحه‌ی ویرایش محصول.
 *
 * یک پنل تمام‌عرض با تب‌های عمودی؛ هر سکشنِ ثبت‌شده یک تب می‌گیرد.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Metabox {

	const NONCE = 'spp_metabox_nonce';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * پست‌تایپ‌هایی که متاباکس روی آن‌ها ظاهر می‌شود.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		return (array) apply_filters( 'spp_post_types', array( 'product' ) );
	}

	public static function add() {
		add_meta_box(
			'spp_sections',
			'صفحه اختصاصی محصول — سکشن‌ها',
			array( __CLASS__, 'render' ),
			self::post_types(),
			'normal',
			'high'
		);
	}

	/* =====================================================================
	 * رندر فرم
	 * =================================================================== */

	public static function render( $post ) {

		$sections = SPP_Sections::all();
		if ( empty( $sections ) ) {
			echo '<p>هیچ سکشنی ثبت نشده است.</p>';
			return;
		}

		wp_nonce_field( 'spp_save_' . $post->ID, self::NONCE );

		$first = array_key_first( $sections );
		?>
		<div class="spp-mb" dir="rtl">

			<div class="spp-mb__intro">
				محتوای هر سکشن را اینجا پر کنید، بعد در المنتور ویجت همان سکشن را روی قالبِ صفحه‌ی محصول بگذارید.
				همچنین می‌توانید از شورت‌کد <code>[spp_section id="hero"]</code> استفاده کنید.
			</div>

			<div class="spp-mb__layout">
				<ul class="spp-mb__tabs">
					<?php foreach ( $sections as $id => $section ) : ?>
						<li>
							<button type="button" class="spp-mb__tab<?php echo $id === $first ? ' is-active' : ''; ?>" data-spp-tab="<?php echo esc_attr( $id ); ?>">
								<?php echo esc_html( $section->title() ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="spp-mb__panels">
					<?php foreach ( $sections as $id => $section ) : ?>
						<div class="spp-mb__panel<?php echo $id === $first ? ' is-active' : ''; ?>" data-spp-panel="<?php echo esc_attr( $id ); ?>">
							<?php
							$data = spp_get_section_data( $post->ID, $id );
							SPP_Fields::render( 'spp[' . $id . ']', $section->fields(), $data );
							?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/* =====================================================================
	 * ذخیره
	 * =================================================================== */

	public static function save( $post_id, $post ) {

		if ( ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), 'spp_save_' . $post_id ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, self::post_types(), true ) ) {
			return;
		}

		$raw = isset( $_POST['spp'] ) && is_array( $_POST['spp'] ) ? $_POST['spp'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		foreach ( SPP_Sections::all() as $id => $section ) {
			$values = isset( $raw[ $id ] ) && is_array( $raw[ $id ] ) ? $raw[ $id ] : array();
			$clean  = spp_sanitize_values( $values, $section->fields() );

			update_post_meta( $post_id, spp_meta_key( $id ), $clean );
		}
	}
}
