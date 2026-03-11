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
	 * Validate a value against E.164 international phone number format.
	 *
	 * @param string $value The value to validate.
	 * @return bool True if the value matches E.164 format, false otherwise.
	 */
	private function is_valid_e164( $value ) {
		return (bool) preg_match( '/^\+[1-9]\d{1,14}$/', $value );
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

			if ( ! empty( $phone_value ) && ! $this->is_valid_e164( $phone_value ) ) {
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
				if ( ! empty( $val ) && ! $this->is_valid_e164( $val ) ) {
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
			if ( ! empty( $val ) && ! $this->is_valid_e164( $val ) ) {
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
		wp_enqueue_script( 'universal-iti-init', plugin_dir_url( __FILE__ ) . 'assets/universal-iti-init.js', array( 'intl-tel-input', 'jquery' ), '1.0.0', true );

		// Localize script with data
		$data = array(
			'utilsScript'     => 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js',
			'defaultCountry'  => apply_filters( 'universal_phone_default_country', 'us' ),
			'overwriteInput'  => apply_filters( 'universal_phone_overwrite_input', false ), // Set to true to overwrite visible input with E.164
		);
		wp_localize_script( 'universal-iti-init', 'UniversalPhoneData', $data );
	}

}

Universal_Phone_Input::get_instance();
