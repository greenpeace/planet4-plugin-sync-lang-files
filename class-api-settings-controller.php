<?php
/**
 * Class API_Settings_Controller
 *
 * Author: Greenpeace International
 * Author URI: http://www.greenpeace.org/
 *
 * License:     GPLv3
 * Copyright (C) 2018 Greenpeace International
 */
class API_Settings_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
	}

	/**
	 * Create menu/submenu entry.
	 */
	public function create_admin_menu() {
		$current_user = wp_get_current_user();

		if ( in_array( 'administrator', $current_user->roles, true ) || in_array( 'editor', $current_user->roles, true ) ) {
			add_menu_page(
				__( 'Planet4 Sync Lang Setting - Circle CI Token setting', 'planet4-sync-lang-files' ),
				__( 'Sync Language Files Setting', 'planet4-sync-lang-files' ),
				'manage_options',
				'cisyncsetting',
				[ $this, 'prepare_settings' ]
			);
		}

		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Render the settings page of the plugin.
	 */
	public function prepare_settings() {
		$settings = get_option( 'p4_sync_ci_token' );
		?>
		<div id="settings_div" class="wrap">
			<h2><?php esc_html_e( 'Planet4 Sync Language Files - Circle CI API token setting', 'planet4-sync-lang-files' ); ?></h2>
		</div>


		<span><?php esc_html_e( 'Please enter Circle CI API token.', 'planet4-sync-lang-files' ); ?></span>

		<form id="p4_sync_ci_token_form" name="p4_sync_ci_token_form" method="post" action="options.php">

			<?php
			settings_fields( 'p4_sync_ci_token_group' );
			do_settings_sections( 'p4_sync_ci_token_group' );
			?>

			<br />

			<table class="form-table">
				<tr valign="top">
					<th><?php esc_html_e( 'Circle CI API token', 'planet4-sync-lang-files' ); ?>:</th>
					<td>
						<input id="p4ci_api_token" name="p4_sync_ci_token[p4ci_api_token]" value="<?php echo esc_attr( $settings['p4ci_api_token'] ); ?>" size="50" placeholder="<?php esc_html_e( 'Enter Circle CI API token', 'planet4-sync-lang-files' ); ?>" type="password" required />
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Changes', 'planet4-sync-lang-files' ), 'primary', 'p4_sync_ci_token_save_button' ); ?>
		</form>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Register and store the settings and their data.
	 */
	public function register_settings() {
		$args = [
			'type'              => 'string',
			'group'             => 'p4_sync_ci_token_group',
			'description'       => __( 'Sync Lang Settings - Circle CI Token setting', 'planet4-sync-lang-files' ),
			'sanitize_callback' => [ $this, 'valitize' ],
			'show_in_rest'      => false,
		];
		register_setting( 'p4_sync_ci_token_group', 'p4_sync_ci_token', $args );
	}

	/**
	 * Validates and sanitizes the settings input.
	 *
	 * @param array $settings The associative array with the settings that are registered for the plugin.
	 *
	 * @return mixed Array if validation is ok, false if validation fails.
	 */
	public function valitize( $settings ) {
		if ( $this->validate( $settings ) ) {
			$this->sanitize( $settings );
		}

		return $settings;
	}

	/**
	 * Validates the settings input.
	 *
	 * @param array $settings The associative array with the settings that are registered for the plugin.
	 *
	 * @return bool
	 */
	public function validate( $settings ) : bool {
		$has_errors = false;

		if ( $settings ) {
			if ( isset( $settings['p4ci_api_token'] ) && '' === $settings['p4ci_api_token'] ) {
				add_settings_error(
					'p4_sync_ci_token-p4ci_api_token',
					esc_attr( 'p4_sync_ci_token-p4ci_api_token' ),
					__( 'Invalid value for API token', 'planet4-sync-lang-files' ),
					'error'
				);
				$has_errors = true;
			}
		}

		return ! $has_errors;
	}

	/**
	 * Sanitizes the settings input.
	 *
	 * @param array $settings The associative array with the settings that are registered for the plugin.
	 */
	public function sanitize( &$settings ) {
		if ( $settings ) {
			foreach ( $settings as $name => $setting ) {
				$settings[ $name ] = sanitize_text_field( $setting );
			}
		}
	}
}
