<?php
/**
 * Plugin Name: Universal Phone Input
 * Description: Automatically adds international phone input with auto country detection to all forms across the site.
 * Version: 1.0.0
 * Author: Mohamed Ibrahim
 * Author URI: muhammedibrim97@gmail.com
 * Text Domain: universal-phone
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Universal_Phone_Input {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance has not been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Contact Form 7 Validation Hooks
		add_filter( 'wpcf7_validate_tel', array( $this, 'cf7_validation' ), 10, 2 );
		add_filter( 'wpcf7_validate_tel*', array( $this, 'cf7_validation' ), 10, 2 );
		add_filter( 'wpcf7_validate_text', array( $this, 'cf7_validation' ), 10, 2 );
		add_filter( 'wpcf7_validate_text*', array( $this, 'cf7_validation' ), 10, 2 );

		// Elementor Pro Forms Validation Hook
		add_action( 'elementor_pro/forms/validation', array( $this, 'elementor_validation' ), 10, 2 );

		// WPForms Validation Hooks
		add_action( 'wpforms_process_validate_phone', array( $this, 'wpforms_validation' ), 10, 3 );
		add_action( 'wpforms_process_validate_text', array( $this, 'wpforms_validation' ), 10, 3 );
	}
	/**
	 * Sanitize and validate an E.164 phone number string.
	 *
	 * This is a pure utility method with no side effects — it does NOT modify
	 * $_POST or any superglobals. Sanitization happens at the point of consumption
	 * inside each form-builder validation hook.
	 *
	 * @param string $raw_value The raw value from $_POST (may still have WP slashes).
	 * @return string Sanitized E.164 number, or empty string if invalid.
	 */
	public static function sanitize_e164( $raw_value ) {
		$clean = sanitize_text_field( wp_unslash( $raw_value ) );

		if ( ! empty( $clean ) && preg_match( '/^\+[1-9]\d{1,14}$/', $clean ) ) {
			return $clean;
		}

		return '';
	}

	/**
	 * Check if a sanitized E.164 value is invalid (non-empty but failed regex).
	 *
	 * @param string $raw_value The raw value from $_POST.
	 * @return bool True if the value is present but does not match E.164 format.
	 */
	public static function is_invalid_e164( $raw_value ) {
		$clean = sanitize_text_field( wp_unslash( $raw_value ) );

		return ! empty( $clean ) && ! preg_match( '/^\+[1-9]\d{1,14}$/', $clean );
	}

	/**
	 * Contact Form 7 Specific Validation Hook.
	 * Provides a nice UX error message if the hidden field data is tampered with.
	 *
	 * Nonce verification is handled by Contact Form 7 before this hook is called.
	 * See: WPCF7_Submission::__construct() which verifies '_wpnonce' → 'wpcf7-form'.
	 */
	public function cf7_validation( $result, $tag ) {
		$name      = $tag->name;
		$e164_name = $name . '_e164';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by CF7 core before this hook fires.
		$original_value = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';

		// If the user typed a phone number, the _e164 field MUST exist and be valid.
		if ( ! empty( $original_value ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$e164_value = isset( $_POST[ $e164_name ] ) ? self::sanitize_e164( $_POST[ $e164_name ] ) : '';

			if ( empty( $e164_value ) ) {
				if ( method_exists( $result, 'invalidate' ) ) {
					$result->invalidate( $tag, __( 'Please enter a valid international phone number.', 'universal-phone' ) );
				}
			}
		}

		return $result;
	}

	/**
	 * Elementor Pro Specific Validation Hook.
	 *
	 * Nonce verification is handled by Elementor Pro before this hook is called.
	 * See: Elementor\Core\Common\Modules\Ajax\Module which verifies its own nonce.
	 */
	public function elementor_validation( $record, $ajax_handler ) {
		$form_fields = $record->get_form_settings( 'form_fields' );
		if ( empty( $form_fields ) ) {
			return;
		}

		foreach ( $form_fields as $field ) {
			if ( empty( $field['custom_id'] ) ) {
				continue;
			}
			$id = $field['custom_id'];

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by Elementor Pro before this hook fires.
			$original_value = isset( $_POST['form_fields'][ $id ] ) ? sanitize_text_field( wp_unslash( $_POST['form_fields'][ $id ] ) ) : '';

			// If the user typed a phone number, the _e164 field MUST exist and be valid.
			if ( ! empty( $original_value ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$e164_value = isset( $_POST['form_fields'][ $id . '_e164' ] ) ? self::sanitize_e164( $_POST['form_fields'][ $id . '_e164' ] ) : '';

				if ( empty( $e164_value ) ) {
					$ajax_handler->add_error( $id, __( 'Please enter a valid international phone number.', 'universal-phone' ) );
				}
			}
		}
	}

	/**
	 * WPForms Specific Validation Hook.
	 *
	 * Nonce verification is handled by WPForms before this hook is called.
	 * See: WPForms_Process::process() which verifies 'wpforms[nonce]'.
	 */
	public function wpforms_validation( $field_id, $field_submit, $form_data ) {
		// $field_submit contains the original field value provided by WPForms.
		$original_value = is_string( $field_submit ) ? sanitize_text_field( $field_submit ) : '';

		// If the user typed a phone number, the _e164 field MUST exist and be valid.
		if ( ! empty( $original_value ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WPForms core before this hook fires.
			$e164_value = isset( $_POST['wpforms']['fields'][ $field_id . '_e164' ] ) ? self::sanitize_e164( $_POST['wpforms']['fields'][ $field_id . '_e164' ] ) : '';

			if ( empty( $e164_value ) ) {
				wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = __( 'Please enter a valid international phone number.', 'universal-phone' );
			}
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_assets() {
		// Enqueue intl-tel-input CSS from CDN
		wp_enqueue_style( 'intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css', array(), '17.0.19' );

		// Custom CSS for flag dropdown to ensure it overlays correctly on existing forms
		$custom_css = "
			.iti { width: 100%; }
			.iti__flag { background-image: url('https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags.png'); }
			@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
			  .iti__flag { background-image: url('https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags@2x.png'); }
			}
		";
		wp_add_inline_style( 'intl-tel-input', $custom_css );

		// Enqueue intl-tel-input JS from CDN
		wp_enqueue_script( 'intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js', array( 'jquery' ), '17.0.19', true );
		
		// Enqueue utils.js locally (bundled with plugin to prevent dynamic script injection)
		wp_enqueue_script( 'intl-tel-input-utils', esc_url( plugin_dir_url( __FILE__ ) . 'assets/utils.js' ), array( 'intl-tel-input' ), '17.0.19', true );

		// Enqueue our custom initialization script
		wp_enqueue_script( 'universal-iti-init', esc_url( plugin_dir_url( __FILE__ ) . 'assets/universal-iti-init.js' ), array( 'intl-tel-input', 'intl-tel-input-utils', 'jquery' ), '1.0.0', true );

		// Localize script with data (validate filter outputs to prevent tampering by rogue plugins)
		$default_country = apply_filters( 'universal_phone_default_country', 'us' );
		$default_country = preg_match( '/^[a-z]{2}$/i', $default_country ) ? strtolower( $default_country ) : 'us';

		$overwrite_input = apply_filters( 'universal_phone_overwrite_input', false );
		$overwrite_input = (bool) $overwrite_input;

		$data = array(
			'defaultCountry'  => $default_country,
			'overwriteInput'  => $overwrite_input,
		);
		wp_localize_script( 'universal-iti-init', 'UniversalPhoneData', $data );
	}

}

Universal_Phone_Input::get_instance();
