<?php
/**
 * پست‌تایپ پادکست + فیلدهای سفارشی.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Podcast_CPT {

	const POST_TYPE = 'sazan_podcast';

	/** @var Podcast_CPT|null */
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	public function register_cpt() {
		// اگر پست‌تایپی به همین نام از قبل وجود دارد، دوباره ثبت نکن
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}
		$labels = array(
			'name'          => esc_html__( 'پادکست‌ها', 'sazan-core' ),
			'singular_name' => esc_html__( 'پادکست', 'sazan-core' ),
			'add_new'       => esc_html__( 'افزودن پادکست', 'sazan-core' ),
			'add_new_item'  => esc_html__( 'افزودن پادکست جدید', 'sazan-core' ),
			'edit_item'     => esc_html__( 'ویرایش پادکست', 'sazan-core' ),
			'all_items'     => esc_html__( 'همه پادکست‌ها', 'sazan-core' ),
			'menu_name'     => esc_html__( 'پادکست‌ها', 'sazan-core' ),
		);
		register_post_type( self::POST_TYPE, array(
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'menu_icon'    => 'dashicons-microphone',
			'rewrite'      => array( 'slug' => 'podcast-sazan' ),
			'supports'     => array( 'title', 'excerpt', 'thumbnail', 'editor' ),
			'show_in_rest' => true,
		) );
	}

	public function add_meta_box() {
		add_meta_box(
			'sazan_podcast_meta',
			esc_html__( 'مشخصات پادکست', 'sazan-core' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'sazan_podcast_meta', 'sazan_podcast_nonce' );
		$external = get_post_meta( $post->ID, '_sazan_pod_external', true );
		$local    = get_post_meta( $post->ID, '_sazan_pod_local', true );
		$duration = get_post_meta( $post->ID, '_sazan_pod_duration', true );
		$speaker  = get_post_meta( $post->ID, '_sazan_pod_speaker', true );
		?>
		<style>
			.sazan-meta-row{ margin:14px 0; }
			.sazan-meta-row label{ display:block; font-weight:600; margin-bottom:5px; }
			.sazan-meta-row input[type=text],.sazan-meta-row input[type=url]{ width:100%; }
			.sazan-meta-local{ display:flex; gap:8px; }
			.sazan-meta-local input{ flex:1; }
		</style>
		<p style="color:#666"><?php esc_html_e( 'عنوان از فیلد عنوان نوشته و «چکیده» از باکس چکیده‌ی وردپرس خوانده می‌شود.', 'sazan-core' ); ?></p>

		<div class="sazan-meta-row">
			<label><?php esc_html_e( 'منبع خارجی (لینک فایل صوتی)', 'sazan-core' ); ?></label>
			<input type="url" name="sazan_pod_external" value="<?php echo esc_attr( $external ); ?>" placeholder="https://example.com/audio.mp3">
		</div>

		<div class="sazan-meta-row">
			<label><?php esc_html_e( 'منبع محلی (آپلود فایل صوتی)', 'sazan-core' ); ?></label>
			<div class="sazan-meta-local">
				<input type="text" id="sazan_pod_local" name="sazan_pod_local" value="<?php echo esc_attr( $local ); ?>" placeholder="<?php esc_attr_e( 'آدرس فایل آپلودشده', 'sazan-core' ); ?>">
				<button type="button" class="button sazan-upload-audio"><?php esc_html_e( 'آپلود / انتخاب', 'sazan-core' ); ?></button>
			</div>
		</div>

		<div class="sazan-meta-row">
			<label><?php esc_html_e( 'مدت زمان', 'sazan-core' ); ?></label>
			<input type="text" name="sazan_pod_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="<?php esc_attr_e( 'مثلاً ۳۲:۱۵', 'sazan-core' ); ?>">
		</div>

		<div class="sazan-meta-row">
			<label><?php esc_html_e( 'سخنران', 'sazan-core' ); ?></label>
			<input type="text" name="sazan_pod_speaker" value="<?php echo esc_attr( $speaker ); ?>" placeholder="<?php esc_attr_e( 'نام سخنران', 'sazan-core' ); ?>">
		</div>

		<script>
		( function( $ ) {
			$( '.sazan-upload-audio' ).on( 'click', function( e ) {
				e.preventDefault();
				var frame = wp.media( { title: '<?php echo esc_js( __( 'انتخاب فایل صوتی', 'sazan-core' ) ); ?>', library: { type: 'audio' }, multiple: false } );
				frame.on( 'select', function() {
					var url = frame.state().get( 'selection' ).first().toJSON().url;
					$( '#sazan_pod_local' ).val( url );
				} );
				frame.open();
			} );
		} )( jQuery );
		</script>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['sazan_podcast_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sazan_podcast_nonce'] ) ), 'sazan_podcast_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$map = array(
			'_sazan_pod_external' => array( 'sazan_pod_external', 'esc_url_raw' ),
			'_sazan_pod_local'    => array( 'sazan_pod_local', 'esc_url_raw' ),
			'_sazan_pod_duration' => array( 'sazan_pod_duration', 'sanitize_text_field' ),
			'_sazan_pod_speaker'  => array( 'sazan_pod_speaker', 'sanitize_text_field' ),
		);
		foreach ( $map as $meta_key => $cfg ) {
			$field = $cfg[0];
			$fn    = $cfg[1];
			if ( isset( $_POST[ $field ] ) ) {
				$val = call_user_func( $fn, wp_unslash( $_POST[ $field ] ) );
				update_post_meta( $post_id, $meta_key, $val );
			}
		}
	}

	public function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			wp_enqueue_media();
		}
	}
}
