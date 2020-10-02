<?php

/**
 * Plugin Name: Planet4 - Sync Language Files
 * Description: Monitors changes to PO files during save operations in Loco Translate plugin & sync the language files.
 * Plugin URI: http://github.com/greenpeace/planet4-plugin-sync-lang-files
 * Version: 1.0
 *
 * Author: Greenpeace International
 * Author URI: http://www.greenpeace.org/
 * Text Domain: planet4-sync-lang-files
 *
 * License:     GPLv3
 * Copyright (C) 2018 Greenpeace International
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || die( 'Direct access is forbidden !' );

/**
 * Class LocoWebhook
 */
class LocoWebhook {
	/**
	 * Language file path.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Hash of language file path.
	 *
	 * @var string
	 */
	private $hash;

	/**
	 * Circle CI api token.
	 *
	 * @var $cci_auth_token string
	 */
	private $cci_auth_token = '';

	const CCI_BASE_URL     = 'https://circleci.com/api/v1.1/project';
	const CCI_AUTH_URL     = self::CCI_BASE_URL . '/github/greenpeace/planet4-handbook/build';
	const CCI_CALL_TIMEOUT = 10;              // Seconds after which the api call will timeout if not responded.

	/**
	 * Constructor.
	 *
	 * @param string $path language file path.
	 */
	public function __construct( $path ) {
		$this->path = $path;
		if ( file_exists( $path ) ) {
			$this->hash = md5_file( $path );
		}
	}

	/**
	 * Sync language file in GCS bucket.
	 */
	public function ping() {
		if ( file_exists( $this->path ) ) {
			$hash = md5_file( $this->path );
			if ( $hash !== $this->hash ) {
				$this->hash = $hash;

				// Get Circel CI API token from DB.
				$settings          = get_option( 'p4_sync_ci_token' );
				$cci_private_token = $settings['p4ci_api_token'] ?? '';
				// Consume Circle CI API.
				$this->api_call( $cci_private_token );
			}
		}
	}

	/**
	 * Hook on the loco translate save action.
	 */
	public static function init() {
		if ( 'save' === $_POST['route'] && 0 === validate_file( $_POST['path'] ) ) {
			$path = WP_CONTENT_DIR . '/' . $_POST['path'];
			$inst = new LocoWebhook( $path );
			add_action( 'loco_admin_shutdown', [ $inst, 'ping' ] );
		}
	}

	/**
	 * Consume Circle CI API.
	 *
	 * @param string $cci_private_token The private api token to be used in order to authenticate for Circle CI API.
	 */
	private function api_call( $cci_private_token ) {

		if ( $cci_private_token ) {
			$url        = self::CCI_AUTH_URL;
			$auth_token = base64_encode( $cci_private_token . ':' );
			$json       = wp_json_encode(
				[
					'tag' => 'lang.master8',
				]
			);

			// With the safe version of wp_remote_{VERB) functions, the URL is validated to avoid redirection and request forgery attacks.
			$response = wp_safe_remote_post(
				$url,
				[
					'headers' => [
						'Content-Type'  => 'application/json;',
						'Authorization' => 'Basic ' . $auth_token,
					],
					'body'    => $json,
					'timeout' => self::CCI_CALL_TIMEOUT,
				]
			);

			// Notify webmaster in case of language file sync fail.
			$to      = get_option( 'admin_email' );
			$subject = 'P4 Handbook - Sync language files failed!';
			$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

			if ( is_array( $response ) && \WP_Http::OK === $response['response']['code'] && $response['body'] ) {
				// Communication with CCI API is authenticated.
				$body = json_decode( $response['body'], true );
				if ( \WP_Http::OK !== $body['status'] || 'Build created' !== $body['body'] ) {
					$body = 'URL: ' . get_site_url() . ' \nResponse status = ' . $body['status'] . ' - ' . $body['body'];
					wp_mail( $to, $subject, $body, $headers );
				}
			} else {
				$body = 'URL: ' . get_site_url() . ' \nResponse status = ' . $response['response']['code'] . ' \n( Note: Please kindly check "Circle CI API token" value in "Sync Language Files Setting". )';
				wp_mail( $to, $subject, $body, $headers );
			}
		}
	}
}
add_action( 'wp_ajax_loco_json', [ 'LocoWebhook', 'init' ] );

require_once 'class-api-settings-controller.php';

if ( is_admin() ) {
	$settings_page = new API_Settings_Controller();
}
