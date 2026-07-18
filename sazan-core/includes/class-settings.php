<?php
/**
 * صفحه‌ی تنظیمات هسته‌ی سازان.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private static $instance = null;

	const OPTION = 'sazan_core_settings';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/** مقادیر پیش‌فرض. */
	public static function defaults() {
		return array(
			'font_resizer' => 1, // فعال‌سازی دکمه‌ی شناور تغییر سایز فونت
		);
	}

	/** دریافت یک گزینه. */
	public static function get( $key ) {
		$opts = wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
		return isset( $opts[ $key ] ) ? $opts[ $key ] : null;
	}

	public function menu() {
		add_menu_page(
			esc_html__( 'هسته سازان', 'sazan-core' ),
			esc_html__( 'هسته سازان', 'sazan-core' ),
			'manage_options',
			'sazan-core-settings',
			array( $this, 'render_page' ),
			'dashicons-editor-textcolor',
			58
		);
	}

	public function register() {
		register_setting( 'sazan_core_settings_group', self::OPTION, array( $this, 'sanitize' ) );
	}

	public function sanitize( $input ) {
		$out = self::defaults();
		$out['font_resizer'] = ! empty( $input['font_resizer'] ) ? 1 : 0;
		return $out;
	}

	public function render_page() {
		$font = (int) self::get( 'font_resizer' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تنظیمات هسته سازان', 'sazan-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'sazan_core_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'دکمه تغییر سایز فونت', 'sazan-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[font_resizer]" value="1" <?php checked( 1, $font ); ?> />
								<?php esc_html_e( 'نمایش دکمه‌ی شناور تغییر سایز فونت در سمت چپ سایت', 'sazan-core' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'به کاربر اجازه می‌دهد فونت کل سایت را درشت‌تر، ریزتر یا ریست کند. تنظیم در مرورگر کاربر ذخیره می‌شود.', 'sazan-core' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
