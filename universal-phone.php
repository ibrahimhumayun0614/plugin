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
		// Only validate fields that are actually phone-related. The hooks fire for all
		// text and tel fields, so skip any field that is not a phone field to avoid
		// unexpected validation errors on unrelated text inputs.
		$is_tel_basetype = isset( $tag->basetype ) && 'tel' === $tag->basetype;
		$field_name_lower = strtolower( sanitize_key( $tag->name ) );
		$phone_keywords   = array( 'phone', 'mobile', 'tel', 'whatsapp' );
		$name_has_keyword = false;
		foreach ( $phone_keywords as $keyword ) {
			if ( false !== strpos( $field_name_lower, $keyword ) ) {
				$name_has_keyword = true;
				break;
			}
		}

		if ( ! $is_tel_basetype && ! $name_has_keyword ) {
			return $result;
		}

		$name      = sanitize_key( $tag->name );
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
			$id = sanitize_key( $field['custom_id'] );

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
		$field_id = absint( $field_id );
		$form_id  = absint( $form_data['id'] );

		// $field_submit contains the original field value provided by WPForms.
		$original_value = is_string( $field_submit ) ? sanitize_text_field( $field_submit ) : '';

		// If the user typed a phone number, the _e164 field MUST exist and be valid.
		if ( ! empty( $original_value ) ) {
			// The JS hidden-field name is built from the original input name, e.g.
			// `wpforms[fields][5]`, which produces `wpforms[fields][5]_e164`.
			// PHP parses that as $_POST['wpforms']['fields']['5_e164'], so the
			// string-keyed lookup below correctly matches what the JS submitted.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WPForms core before this hook fires.
			$e164_value = isset( $_POST['wpforms']['fields'][ $field_id . '_e164' ] ) ? self::sanitize_e164( $_POST['wpforms']['fields'][ $field_id . '_e164' ] ) : '';

			if ( empty( $e164_value ) ) {
				wpforms()->process->errors[ $form_id ][ $field_id ] = __( 'Please enter a valid international phone number.', 'universal-phone' );
			}
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_assets() {
		// Allow site owners to disable asset loading on pages without forms.
		// Usage: add_filter( 'universal_phone_should_enqueue', '__return_false' );
		if ( ! apply_filters( 'universal_phone_should_enqueue', true ) ) {
			return;
		}

		$assets_url = plugin_dir_url( __FILE__ ) . 'assets/';

		// Enqueue intl-tel-input CSS locally
		wp_enqueue_style( 'intl-tel-input', esc_url( $assets_url . 'intlTelInput.min.css' ), array(), '25.4.3' );

		// Custom CSS for layout consistency
		$custom_css = ".iti { width: 100%; }";
		wp_add_inline_style( 'intl-tel-input', $custom_css );

		// Enqueue intl-tel-input JS locally
		wp_enqueue_script( 'intl-tel-input', esc_url( $assets_url . 'intlTelInput.min.js' ), array(), '25.4.3', true );
		
		// Enqueue utils.js locally
		wp_enqueue_script( 'intl-tel-input-utils', esc_url( $assets_url . 'utils.js' ), array( 'intl-tel-input' ), '25.4.3', true );

		// Enqueue our custom initialization script
		wp_enqueue_script( 'universal-iti-init', esc_url( $assets_url . 'universal-iti-init.js' ), array( 'intl-tel-input', 'intl-tel-input-utils', 'jquery' ), '1.0.0', true );

		// Localize script with data
		$default_country = apply_filters( 'universal_phone_default_country', 'us' );
		$default_country = preg_match( '/^[a-z]{2}$/i', $default_country ) ? strtolower( $default_country ) : 'us';

		$overwrite_input = apply_filters( 'universal_phone_overwrite_input', false );
		$overwrite_input = (bool) $overwrite_input;

		// Allow site owners to narrow the MutationObserver to a specific container
		// (e.g. '#content') to reduce exposure to injected elements.
		// Defaults to 'body' for full backward-compatible coverage.
		// Usage: add_filter( 'universal_phone_observe_selector', function() { return '#main'; } );
		// Note: wp_strip_all_tags() is used (rather than sanitize_text_field) to preserve
		// CSS selector characters such as >, +, ~, [ and ] that the latter would strip.
		$observe_selector = apply_filters( 'universal_phone_observe_selector', 'body' );
		$observe_selector = is_string( $observe_selector ) ? wp_strip_all_tags( $observe_selector ) : 'body';

		$data = array(
			'defaultCountry'   => $default_country,
			'overwriteInput'   => $overwrite_input,
			'observeSelector'  => $observe_selector,
		);
		wp_localize_script( 'universal-iti-init', 'UniversalPhoneData', $data );
	}

}

Universal_Phone_Input::get_instance();
