<?php

namespace CompactForm\Builder;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Fields;

defined( 'ABSPATH' ) || die();

class Fields_Manager {

	private static ?self $instance = null;

	private array $fields = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		Controls_Manager::instance();
		$this->register_defaults();

		do_action( 'fcf7_register_fields', $this );
	}

	private function register_defaults(): void {
		$this->register_if_active( 'text', Fields\Text_Field::class );
		$this->register_if_active( 'email', Fields\Email_Field::class );
		$this->register_if_active( 'tel', Fields\Tel_Field::class );
		$this->register_if_active( 'url', Fields\Url_Field::class );
		$this->register_if_active( 'number', Fields\Number_Field::class );
		$this->register_if_active( 'date', Fields\Date_Field::class );
		$this->register_if_active( 'textarea', Fields\Textarea_Field::class );
		$this->register_if_active( 'quiz', Fields\Quiz_Field::class );
		$this->register_if_active( 'file', Fields\File_Field::class );
		$this->register_if_active( 'submit', Fields\Submit_Field::class );

		// Choice.
		$this->register_if_active( 'select', Fields\Select_Field::class );
		$this->register_if_active( 'radio', Fields\Radio_Field::class );
		$this->register_if_active( 'checkbox', Fields\Checkbox_Field::class );
		$this->register_if_active( 'acceptance', Fields\Acceptance_Field::class );

		$this->register_if_active( 'heading', Fields\Heading_Field::class );
		$this->register_if_active( 'divider', Fields\Divider_Field::class );
		$this->register_if_active( 'html', Fields\Html_Field::class );

		$this->register_if_active( 'container', Fields\Container_Field::class );
		$this->register_if_active( 'star-rating', Fields\Star_Rating_Field::class );
		$this->register_if_active( 'time-picker', Fields\Time_Picker_Field::class );
		$this->register_if_active( 'date-picker', Fields\Date_Picker_Field::class );
		$this->register_if_active( 'datetime-picker', Fields\Datetime_Picker_Field::class );
		$this->register_if_active( 'country-dropdown', Fields\Country_Dropdown_Field::class );
		$this->register_if_active( 'signature', Fields\Signature_Field::class );
		$this->register_if_active( 'range-slider', Fields\Range_Slider_Field::class );
		$this->register_if_active( 'google-recaptcha', Fields\Google_Recaptcha_Field::class );
		$this->register_if_active( 'spam-protection', Fields\Spam_Protection_Field::class );
	}

	private function register_if_active( string $slug, string $field_class ): void {
		if ( \CompactForm\Helpers\Config::is_extension_active( $slug ) ) {
			$this->register( new $field_class() );
		}
	}

	public function register( Base_Field $field ): void {
		$this->fields[ $field->get_type() ] = $field;
	}

	public function get( string $type ): ?Base_Field {
		return $this->fields[ $type ] ?? null;
	}

	public function get_types(): array {
		return array_keys( $this->fields );
	}

	public function get_config(): array {
		$config = [];
		foreach ( $this->fields as $field ) {
			$config[] = $field->get_config();
		}
		return $config;
	}

	private static function drop_stranded( array $fields ): array {
		$kept = $fields;

		do {
			$ids  = [];
			$next = [];

			foreach ( $kept as $field ) {
				if ( isset( $field['id'] ) ) {
					$ids[ (string) $field['id'] ] = true;
				}
			}

			foreach ( $kept as $field ) {
				$parent = (string) ( $field['parentId'] ?? '' );

				if ( '' === $parent || isset( $ids[ $parent ] ) ) {
					$next[] = $field;
				}
			}

			$shrank = count( $next ) < count( $kept );
			$kept   = $next;
		} while ( $shrank );

		return $kept;
	}

	public function sanitize_schema( array $schema ): array {
		$controls = Controls_Manager::instance();
		$clean    = [];

		foreach ( (array) ( $schema['fields'] ?? [] ) as $data ) {
			if ( ! is_array( $data ) ) {
				continue;
			}

			$type = (string) ( $data['type'] ?? '' );
			if ( '' === $type ) {
				continue; 
			}
			$field = $this->get( $type );

			if ( ! $field ) {
				$out = [];
				foreach ( $data as $k => $v ) {
					$out[ sanitize_key( (string) $k ) ] = is_scalar( $v ) ? sanitize_text_field( (string) $v ) : ( is_array( $v ) ? array_map( 'sanitize_text_field', array_filter( $v, 'is_scalar' ) ) : '' );
				}
				$clean[] = $out;
				continue;
			}

			$out = [ 'type' => $type ];

			if ( isset( $data['id'] ) ) {
				$out['id'] = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $data['id'] );
			}
			if ( isset( $data['customWidth'] ) ) {
				$out['customWidth'] = preg_replace( '/[^0-9.a-z%]/i', '', (string) $data['customWidth'] );
			}

			if ( ! empty( $data['parentId'] ) ) {
				$out['parentId'] = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $data['parentId'] );
			}

			if ( ! empty( $data['stepId'] ) ) {
				$out['stepId'] = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $data['stepId'] );
			}

			if ( ! empty( $data['isGlobal'] ) ) {
				$out['isGlobal'] = true;
			}

			foreach ( $field->get_config()['controls'] as $ctrl ) {
				$key   = $ctrl['key'];
				$value = array_key_exists( $key, $data ) ? $data[ $key ] : $controls->get_default( $ctrl['type'], $ctrl );

				if ( ! empty( $ctrl['responsive'] ) ) {
					$devices = [ 'desktop', 'tablet', 'mobile' ];
					$source  = ( is_array( $value ) && array_intersect_key( $value, array_flip( $devices ) ) )
						? $value
						: [ 'desktop' => $value ];

					$per_device = [];
					foreach ( $devices as $device ) {
						if ( array_key_exists( $device, $source ) ) {
							$per_device[ $device ] = $controls->sanitize( $ctrl['type'], $source[ $device ], $ctrl );
						}
					}
					$out[ $key ] = $per_device ? $per_device : [ 'desktop' => $controls->sanitize( $ctrl['type'], null, $ctrl ) ];
					continue;
				}

				$out[ $key ] = $controls->sanitize( $ctrl['type'], $value, $ctrl );

				if ( ! empty( $ctrl['create'] ) || ! empty( $ctrl['insert_option'] ) ) {
					$pool_key = $key . '__opts';
					$pool     = $data[ $pool_key ] ?? [];
					$out[ $pool_key ] = is_array( $pool )
						? array_values( array_filter(
							array_map( 'sanitize_text_field', array_filter( $pool, 'is_scalar' ) ),
							static function ( $s ) {
								return '' !== $s;
							}
						) )
						: [];
				}
			}

			$clean[] = $out;
		}

		$out_schema = [
			'version' => isset( $schema['version'] ) ? (int) $schema['version'] : 1,
			'fields'  => self::drop_stranded( $clean ),
		];

		// Per-form responsive breakpoints (max-width px), set from the builder.
		if ( isset( $schema['breakpoints'] ) && is_array( $schema['breakpoints'] ) ) {
			$breakpoints = [];
			foreach ( [ 'tablet', 'mobile' ] as $device ) {
				$value = $schema['breakpoints'][ $device ] ?? null;
				if ( is_numeric( $value ) && (int) $value >= 320 && (int) $value <= 2560 ) {
					$breakpoints[ $device ] = (int) $value;
				}
			}
			if ( $breakpoints ) {
				$out_schema['breakpoints'] = $breakpoints;
			}
		}

		$gap_map = static function ( $raw, int $max ): array {
			$raw = is_array( $raw ) ? $raw : [ 'desktop' => $raw ];
			$out = [];
			foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
				$value = $raw[ $device ] ?? null;
				if ( is_numeric( $value ) && (int) $value >= 0 && (int) $value <= $max ) {
					$out[ $device ] = (int) $value;
				}
			}
			return $out;
		};

		foreach ( [ 'fieldGap' => 200, 'containerColumnGap' => 120, 'containerRowGap' => 120 ] as $key => $max ) {
			if ( ! isset( $schema[ $key ] ) ) {
				continue;
			}
			$gaps = $gap_map( $schema[ $key ], $max );
			if ( $gaps ) {
				$out_schema[ $key ] = $gaps;
			}
		}

		if ( isset( $schema['containerPadding'] ) && is_array( $schema['containerPadding'] ) ) {
			$padding = [];
			foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
				if ( ! isset( $schema['containerPadding'][ $device ] ) ) {
					continue;
				}
				$clean_pad = $controls->sanitize( 'dimensions', $schema['containerPadding'][ $device ], [] );
				$dimensions = $controls->get( 'dimensions' );
				if ( is_array( $clean_pad ) && $dimensions && '' !== $dimensions->get_css( $clean_pad, 'padding: {{TOP}}{{UNIT}};' ) ) {
					$padding[ $device ] = $clean_pad;
				}
			}
			if ( $padding ) {
				$out_schema['containerPadding'] = $padding;
			}
		}

		if ( isset( $schema['integrations'] ) && is_array( $schema['integrations'] ) ) {
			$integrations = [];

			if ( isset( $schema['integrations']['googleSheet'] ) && is_array( $schema['integrations']['googleSheet'] ) ) {
				$gs = $schema['integrations']['googleSheet'];
				$integrations['googleSheet'] = [
					'enabled' => ! empty( $gs['enabled'] ),
					'sheetId' => isset( $gs['sheetId'] ) ? sanitize_text_field( (string) $gs['sheetId'] ) : '',
					'tabId'   => isset( $gs['tabId'] ) ? sanitize_text_field( (string) $gs['tabId'] ) : '',
				];
			}

			if ( isset( $schema['integrations']['webhook'] ) && is_array( $schema['integrations']['webhook'] ) ) {
				$integrations['webhook'] = $this->sanitize_webhook( $schema['integrations']['webhook'] );
			}

			if ( $integrations ) {
				$out_schema['integrations'] = $integrations;
			}
		}

		{
			$raw_configuration = is_array( $schema['configuration'] ?? null ) ? $schema['configuration'] : [];
			$configuration     = [];

			if ( isset( $schema['configuration']['spamProtection'] ) && is_array( $schema['configuration']['spamProtection'] ) ) {
				$configuration['spamProtection'] = $this->sanitize_spam_protection( $schema['configuration']['spamProtection'] );
			}

			if ( isset( $schema['configuration']['postSubmission'] ) && is_array( $schema['configuration']['postSubmission'] ) ) {
				$configuration['postSubmission'] = $this->sanitize_post_submission( $schema['configuration']['postSubmission'] );
			}

			if ( isset( $schema['configuration']['defaultMail'] ) && is_array( $schema['configuration']['defaultMail'] ) ) {
				$dm        = $schema['configuration']['defaultMail'];
				$template  = sanitize_key( (string) ( $dm['template'] ?? '' ) );
				$template2 = sanitize_key( (string) ( $dm['template2'] ?? '' ) );

				$configuration['defaultMail'] = [
					'template'  => '' !== $template ? $template : 'html-table',
					'template2' => '' !== $template2 ? $template2 : 'html-table',
				];
			}

			$configuration = apply_filters( 'fcf7_builder_sanitize_configuration', $configuration, $raw_configuration, $schema );

			if ( is_array( $configuration ) && $configuration ) {
				$out_schema['configuration'] = $configuration;
			}
		}

		return apply_filters( 'fcf7_builder_sanitize_schema', $out_schema, $schema );
	}

	private function sanitize_spam_protection( array $sp ): array {
		$fill = isset( $sp['minTimeSeconds'] ) ? (int) $sp['minTimeSeconds'] : 3;

		return [
			'honeypotEnabled' => ! array_key_exists( 'honeypotEnabled', $sp ) || ! empty( $sp['honeypotEnabled'] ),
			'minTimeEnabled'  => ! empty( $sp['minTimeEnabled'] ),
			'minTimeSeconds'  => ( $fill >= 1 && $fill <= 300 ) ? $fill : 3,
			'botMessage'      => isset( $sp['botMessage'] ) ? sanitize_text_field( (string) $sp['botMessage'] ) : '',
			'bannedEnabled'   => ! empty( $sp['bannedEnabled'] ),
			'bannedWords'     => isset( $sp['bannedWords'] ) ? sanitize_textarea_field( (string) $sp['bannedWords'] ) : '',
			'bannedMessage'   => isset( $sp['bannedMessage'] ) ? sanitize_text_field( (string) $sp['bannedMessage'] ) : '',
		];
	}

	private function sanitize_post_submission( array $ps ): array {
		$max = 50; // Post_Submission::MAX_ROWS.

		$field = static function ( $value ): string {
			return is_scalar( $value )
				? trim( (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $value ) )
				: '';
		};

		$taxonomies = [];
		foreach ( array_slice( (array) ( $ps['taxonomies'] ?? [] ), 0, $max ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$taxonomies[] = [
				'taxonomy' => sanitize_key( (string) ( $row['taxonomy'] ?? '' ) ),
				'field'    => $field( $row['field'] ?? '' ),
			];
		}

		$author = (string) ( $ps['author'] ?? 'admin' );
		$status = (string) ( $ps['status'] ?? 'draft' );

		return [
			'enabled'      => ! empty( $ps['enabled'] ),
			'status'       => in_array( $status, [ 'draft', 'pending', 'publish', 'private' ], true ) ? $status : 'draft',
			'author'       => in_array( $author, [ 'admin', 'current', 'specific' ], true ) ? $author : 'admin',
			'authorId'     => isset( $ps['authorId'] ) ? max( 0, (int) $ps['authorId'] ) : 0,
			'guests'       => ! isset( $ps['guests'] ) || ! empty( $ps['guests'] ),
			'guestMessage' => isset( $ps['guestMessage'] ) ? sanitize_text_field( (string) $ps['guestMessage'] ) : '',
			'title'        => $field( $ps['title'] ?? '' ),
			'content'      => $field( $ps['content'] ?? '' ),
			'excerpt'      => $field( $ps['excerpt'] ?? '' ),
			'thumbnail'    => $field( $ps['thumbnail'] ?? '' ),
			'createTerms'  => ! empty( $ps['createTerms'] ),
			'taxonomies'   => $taxonomies,
		];
	}

	private function sanitize_webhook( array $wh ): array {
		$methods = [ 'POST', 'GET', 'PUT', 'PATCH', 'DELETE' ];
		$method  = strtoupper( (string) ( $wh['method'] ?? 'POST' ) );
		$format  = (string) ( $wh['format'] ?? 'json' );
		$timeout = isset( $wh['timeout'] ) ? (int) $wh['timeout'] : 10;
		$max     = 50; 

		$key = static function ( $value ): string {
			return trim( preg_replace( '/[\x00-\x1F\x7F"\'\\\\]/', '', (string) $value ) );
		};

		$source = static function ( $value ): string {
			return trim( preg_replace( '/[^A-Za-z0-9_:\-\[\]]/', '', (string) $value ) );
		};

		$body = [];
		foreach ( array_slice( (array) ( $wh['body'] ?? [] ), 0, $max ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$sources = [];
			foreach ( array_slice( (array) ( $row['source'] ?? [] ), 0, $max ) as $one ) {
				$one = $source( $one );

				if ( '' !== $one ) {
					$sources[] = $one;
				}
			}

			$body[] = [ 'key'    => $key( $row['key'] ?? '' ), 'source' => array_values( array_unique( $sources ) ) ];
		}

		$headers = [];
		foreach ( array_slice( (array) ( $wh['headers'] ?? [] ), 0, $max ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$mode = 'field' === ( $row['mode'] ?? 'value' ) ? 'field' : 'value';
			$headers[] = [
				'name'  => trim( preg_replace( '/[^A-Za-z0-9\-_]/', '', (string) ( $row['name'] ?? '' ) ) ),
				'mode'  => $mode,
				'value' => 'field' === $mode ? $source( $row['value'] ?? '' ) : trim( str_replace( [ "\r", "\n" ], '', (string) ( $row['value'] ?? '' ) ) ),
			];
		}

		return [
			'enabled'  => ! empty( $wh['enabled'] ),
			'url'      => isset( $wh['url'] ) ? esc_url_raw( trim( (string) $wh['url'] ), [ 'http', 'https' ] ) : '',
			'method'   => in_array( $method, $methods, true ) ? $method : 'POST',
			'format'   => in_array( $format, [ 'json', 'form' ], true ) ? $format : 'json',
			'bodyMode' => 'mapped' === ( $wh['bodyMode'] ?? 'all' ) ? 'mapped' : 'all',
			'body'     => $body,
			'headers'  => $headers,
			'meta'     => ! empty( $wh['meta'] ),
			'timeout'  => max( 1, min( 30, $timeout ) ),
			'blocking' => ! empty( $wh['blocking'] ),
		];
	}

}
