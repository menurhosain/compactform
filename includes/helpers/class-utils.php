<?php

namespace CompactForm\Helpers;

defined( 'ABSPATH' ) || die();

class Utils {

	public static function is_pro_active(): bool {
		return defined( 'FCF7_PRO_VERSION' ) && class_exists( 'FlexiForms_Pro' );
	}

	public static function repeater_duplicate_keys( int $form_id ): array {
		return (array) apply_filters( 'fcf7_repeater_duplicate_keys', [], $form_id );
	}

	public static function is_row_list( $value ): bool {
		if ( ! is_array( $value ) || ! $value || array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			return false;
		}

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) || ! $row ) {
				return false;
			}
			foreach ( array_keys( $row ) as $key ) {
				if ( ! is_string( $key ) ) {
					return false;
				}
			}
		}

		return true;
	}

	public static function rows_to_string( array $rows, string $separator = ', ', string $row_separator = ' · ' ): string {
		$out = [];

		foreach ( array_values( $rows ) as $i => $row ) {
			$parts = [];

			foreach ( (array) $row as $key => $value ) {
				$flat = is_array( $value )
					? implode( $separator, array_map( 'strval', array_filter( $value, 'is_scalar' ) ) )
					: ( is_scalar( $value ) ? (string) $value : '' );

				$parts[] = $key . ': ' . $flat;
			}

			$out[] = '#' . ( $i + 1 ) . ' ' . implode( ' | ', $parts );
		}

		return implode( $row_separator, $out );
	}

	private static $before_send_mail_backup = null;

	public static function dry_run_validate( \WPCF7_ContactForm $contact_form ): array {
		self::suspend_mail_side_effects();
		$result = $contact_form->submit( [ 'skip_mail' => true ] );
		self::restore_mail_side_effects();
		return $result;
	}

	private static function recaptcha_field() {
		return class_exists( '\CompactForm\Builder\Fields_Manager' )
			? \CompactForm\Builder\Fields_Manager::instance()->get( 'fcf7_google_recaptcha' )
			: null;
	}

	private static function suspend_mail_side_effects(): void {
		$recaptcha = self::recaptcha_field();
		if ( $recaptcha ) {
			remove_filter( 'wpcf7_validate_fcf7_google_recaptcha', [ $recaptcha, 'validate_recaptcha_response' ], 10 );
			remove_filter( 'wpcf7_validate_fcf7_google_recaptcha*', [ $recaptcha, 'validate_recaptcha_response' ], 10 );
		}

		global $wp_filter;
		self::$before_send_mail_backup = isset( $wp_filter['wpcf7_before_send_mail'] )
			? clone $wp_filter['wpcf7_before_send_mail']
			: null;
		remove_all_actions( 'wpcf7_before_send_mail' );
	}

	/** Re-hooks what suspend_mail_side_effects() removed, for the real final submit. */
	private static function restore_mail_side_effects(): void {
		$recaptcha = self::recaptcha_field();
		if ( $recaptcha ) {
			add_filter( 'wpcf7_validate_fcf7_google_recaptcha', [ $recaptcha, 'validate_recaptcha_response' ], 10, 2 );
			add_filter( 'wpcf7_validate_fcf7_google_recaptcha*', [ $recaptcha, 'validate_recaptcha_response' ], 10, 2 );
		}

		global $wp_filter;
		if ( null !== self::$before_send_mail_backup ) {
			$wp_filter['wpcf7_before_send_mail'] = self::$before_send_mail_backup;
		} else {
			unset( $wp_filter['wpcf7_before_send_mail'] );
		}
		self::$before_send_mail_backup = null;
	}

	/** @see WPCF7_REST_Controller's identical reshape in rest-api.php. */
	public static function format_invalide_fields_msg( array $invalid_fields, string $unit_tag ): array {
		$out = [];
		foreach ( $invalid_fields as $name => $field ) {
			if ( ! wpcf7_is_name( $name ) ) {
				continue;
			}
			$name  = strtr( $name, '.', '_' );
			$out[] = [
				'field'    => $name,
				'message'  => $field['reason'] ?? '',
				'idref'    => $field['idref'] ?? null,
				'error_id' => '' !== $unit_tag ? sprintf( '%1$s-ve-%2$s', $unit_tag, $name ) : '',
			];
		}
		return $out;
	}
}
