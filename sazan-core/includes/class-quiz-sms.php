<?php
/**
 * اتصال پنل پیامک فراز اس ام اس (ippanel) + صفحه‌ی تنظیمات سراسری آزمون.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quiz_SMS {

	const ENDPOINT_SIMPLE  = 'https://api2.ippanel.com/api/v1/sms/send/webservice/single';
	const ENDPOINT_PATTERN = 'https://api2.ippanel.com/api/v1/sms/pattern/normal/send';

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'sazan_quiz_send_sms', array( $this, 'send' ), 10, 4 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}
	}

	/* ===================== ارسال پیامک ===================== */

	/**
	 * @param string $mobile شماره گیرنده (09...).
	 * @param string $text   متن آماده (برای حالت ساده).
	 * @param array  $ctx    شامل percent, tier, name, link.
	 */
	public function send( $mobile, $text, $ctx = array(), $data = array() ) {
		$s = Quiz_Engine::settings();
		if ( empty( $s['sms_apikey'] ) || empty( $s['sms_sender'] ) ) {
			return;
		}

		if ( 'pattern' === $s['sms_mode'] && ! empty( $s['sms_pattern'] ) ) {
			$body = array(
				'code'      => $s['sms_pattern'],
				'sender'    => $s['sms_sender'],
				'recipient' => $mobile,
				'variable'  => array(
					'score' => (string) ( $ctx['percent'] ?? '' ),
					'tier'  => (string) ( $ctx['tier'] ?? '' ),
					'name'  => (string) ( $ctx['name'] ?? '' ),
					'link'  => (string) ( $ctx['link'] ?? '' ),
				),
			);
			$url = self::ENDPOINT_PATTERN;
		} else {
			$body = array(
				'recipient' => array( $mobile ),
				'sender'    => $s['sms_sender'],
				'message'   => $text,
			);
			$url = self::ENDPOINT_SIMPLE;
		}

		$res = wp_remote_post( $url, array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/json',
				'apikey'       => $s['sms_apikey'],
			),
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $res ) ) {
			error_log( 'Sazan Quiz SMS error: ' . $res->get_error_message() );
		}
		return $res;
	}

	/* ===================== تنظیمات ===================== */

	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Quiz_CPT::POST_TYPE,
			esc_html__( 'تنظیمات (پیامک/نتیجه)', 'sazan-core' ),
			esc_html__( 'تنظیمات', 'sazan-core' ),
			'manage_options',
			'sazan-quiz-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( 'sazan_quiz_settings_group', 'sazan_quiz_settings', array( $this, 'sanitize' ) );
	}

	public function sanitize( $in ) {
		return array(
			'sms_apikey'  => sanitize_text_field( $in['sms_apikey'] ?? '' ),
			'sms_sender'  => sanitize_text_field( $in['sms_sender'] ?? '' ),
			'sms_mode'    => in_array( ( $in['sms_mode'] ?? 'pattern' ), array( 'simple', 'pattern' ), true ) ? $in['sms_mode'] : 'pattern',
			'sms_pattern' => sanitize_text_field( $in['sms_pattern'] ?? '' ),
			'result_page' => absint( $in['result_page'] ?? 0 ),
		);
	}

	public function render_page() {
		$s = Quiz_Engine::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تنظیمات آزمون سازان', 'sazan-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'sazan_quiz_settings_group' ); ?>
				<h2><?php esc_html_e( 'پیامک فراز اس ام اس', 'sazan-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'کلید API (apikey)', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_quiz_settings[sms_apikey]" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" class="regular-text" style="direction:ltr">
						<p class="description"><?php esc_html_e( 'از پنل فراز: خدمات وب‌سرویس ← لیست کلیدهای دسترسی.', 'sazan-core' ); ?></p></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'شماره فرستنده', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_quiz_settings[sms_sender]" value="<?php echo esc_attr( $s['sms_sender'] ); ?>" class="regular-text" style="direction:ltr" placeholder="+983000505"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'حالت ارسال', 'sazan-core' ); ?></label></th>
						<td>
							<select name="sazan_quiz_settings[sms_mode]">
								<option value="pattern" <?php selected( $s['sms_mode'], 'pattern' ); ?>><?php esc_html_e( 'پترن (پیشنهادی برای خط خدماتی)', 'sazan-core' ); ?></option>
								<option value="simple" <?php selected( $s['sms_mode'], 'simple' ); ?>><?php esc_html_e( 'متن آزاد (نیازمند خط اختصاصی)', 'sazan-core' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'کد پترن', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_quiz_settings[sms_pattern]" value="<?php echo esc_attr( $s['sms_pattern'] ); ?>" class="regular-text" style="direction:ltr">
						<p class="description"><?php echo esc_html__( 'پترن باید با این متغیرها ساخته شود:', 'sazan-core' ); ?> <code>%name%</code> <code>%score%</code> <code>%tier%</code> <code>%link%</code></p></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'صفحه‌ی نتیجه', 'sazan-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'صفحه‌ی نمایش نتیجه', 'sazan-core' ); ?></label></th>
						<td>
							<?php wp_dropdown_pages( array(
								'name'             => 'sazan_quiz_settings[result_page]',
								'selected'         => $s['result_page'],
								'show_option_none' => esc_html__( '— انتخاب صفحه —', 'sazan-core' ),
							) ); ?>
							<p class="description"><?php echo esc_html__( 'یک صفحه بساز و این شورت‌کد را داخلش بگذار:', 'sazan-core' ); ?> <code>[sazan_quiz_result]</code></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
