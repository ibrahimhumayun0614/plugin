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

		// Global validation/sanitization loop for all $_POST data containing _e164
		add_action( 'init', array( $this, 'global_e164_sanitization' ), 1 );

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
	 * Globally sanitize incoming POST data specifically for phone_e164 fields.
	 * This acts as a universal fallback for all form builders to prevent payload injection.
	 */
	public function global_e164_sanitization() {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';

		if ( $request_method !== 'POST' || empty( $_POST ) ) {
			return;
		}

		if ( ! isset( $_POST['universal_phone_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['universal_phone_nonce'] ), 'universal_phone_action' ) ) {
			return; // Stop execution if nonce is missing or invalid (CSRF protection)
		}

		array_walk_recursive( $_POST, function ( &$value, $key ) {
			if ( is_string( $key ) && strpos( $key, '_e164' ) !== false ) {
				$clean_val = wp_unslash( $value );
				// Must be strictly empty or match E.164 format
				if ( ! empty( $clean_val ) && ! preg_match( '/^\+[1-9]\d{1,14}$/', $clean_val ) ) {
					$value = ''; // Strip malicious data completely
				}
			}
		} );
	}

	/**
	 * Contact Form 7 Specific Validation Hook
	 * Provides a nice UX error message if the hidden field data is tampered with.
	 */
	public function cf7_validation( $result, $tag ) {
		$name      = $tag->name;
		$e164_name = $name . '_e164';

		if ( isset( $_POST[$e164_name] ) ) {
			$phone_value = sanitize_text_field( wp_unslash( $_POST[$e164_name] ) );

			if ( ! empty( $phone_value ) && ! preg_match( '/^\+[1-9]\d{1,14}$/', $phone_value ) ) {
				if ( method_exists( $result, 'invalidate' ) ) {
					$result->invalidate( $tag, __( 'Please enter a valid international phone number.', 'universal-phone' ) );
				}
			}
		}

		return $result;
	}

	/**
	 * Elementor Pro Specific Validation Hook
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
			
			if ( isset( $_POST['form_fields'][ $id . '_e164' ] ) ) {
				$val = sanitize_text_field( wp_unslash( $_POST['form_fields'][ $id . '_e164' ] ) );
				if ( ! empty( $val ) && ! preg_match( '/^\+[1-9]\d{1,14}$/', $val ) ) {
					$ajax_handler->add_error( $id, __( 'Please enter a valid international phone number.', 'universal-phone' ) );
				}
			}
		}
	}

	/**
	 * WPForms Specific Validation Hook
	 */
	public function wpforms_validation( $field_id, $field_submit, $form_data ) {
		if ( isset( $_POST['wpforms']['fields'][ $field_id . '_e164' ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['wpforms']['fields'][ $field_id . '_e164' ] ) );
			if ( ! empty( $val ) && ! preg_match( '/^\+[1-9]\d{1,14}$/', $val ) ) {
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
		
		// Enqueue utils script. We load it separately to ensure it is available, though intlTelInput can load it dynamically if configured.
		// However, loading via wp_enqueue_script is more reliable for dependencies. 
		// Actually, intl-tel-input usually loads utils.js via a URL option. We will pass the URL in localization.
		
		// Enqueue our custom initialization script
		wp_enqueue_script( 'universal-iti-init', esc_url( plugin_dir_url( __FILE__ ) . 'assets/universal-iti-init.js' ), array( 'intl-tel-input', 'jquery' ), '1.0.0', true );

		// Localize script with data (validate filter outputs to prevent tampering by rogue plugins)
		$default_country = apply_filters( 'universal_phone_default_country', 'us' );
		$default_country = preg_match( '/^[a-z]{2}$/i', $default_country ) ? strtolower( $default_country ) : 'us';

		$overwrite_input = apply_filters( 'universal_phone_overwrite_input', false );
		$overwrite_input = (bool) $overwrite_input;

		$data = array(
			'utilsScript'     => 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js',
			'defaultCountry'  => $default_country,
			'overwriteInput'  => $overwrite_input,
			'nonce'           => wp_create_nonce( 'universal_phone_action' ), // CSRF Protection nonce
		);
		wp_localize_script( 'universal-iti-init', 'UniversalPhoneData', $data );
	}

}

Universal_Phone_Input::get_instance();
