<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Abstracts\Extension_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Spam_Protection_Field extends Extension_Field {

	use Label_Control;
	use Name_Control;
	use Description_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Description_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	private const NONCE = 'fcf7_spam_protection';
	private const DEFAULT_MAX_AGE = 3600;
	private const MAX_ATTEMPTS = 5;
	private const MAX_OPERAND_CEILING = 99;
	private const CANVAS_MAX_W        = 480;
	private const CANVAS_MAX_H        = 200;

	public function __construct() {
		parent::__construct();

		add_filter( 'wpcf7_validate_' . $this->get_tag(), [ $this, 'validate_answer' ], 10, 2 );
		add_filter( 'wpcf7_validate_' . $this->get_tag() . '*', [ $this, 'validate_answer' ], 10, 2 );
		add_action( 'wp_ajax_fcf7_spam_protection_refresh', [ $this, 'ajax_refresh' ] );
		add_action( 'wp_ajax_nopriv_fcf7_spam_protection_refresh', [ $this, 'ajax_refresh' ] );
	}

	public function get_type(): string {
		return 'fcf7_spam_protection';
	}

	public function get_title(): string {
		return __( 'Spam Protection', 'compactform' );
	}

	public function get_icon(): string {
		return 'ri-shield-keyhole-line';
	}

	public function get_category(): string {
		return 'security';
	}

	protected function get_tag(): string {
		return 'fcf7_spam_protection';
	}

	public function get_preview(): string {
		return 'spam_protection';
	}

	protected function star_supported(): bool {
		return false;
	}

	protected function label_group(): bool {
		return true;
	}

	protected function tag_features(): array {
		return [ 'name-attr' => true, 'do-not-store' => true ];
	}

	private function sign( int $answer, int $issued, int $form_id ): string {
		return hash_hmac( 'sha256', $answer . '|' . $issued . '|' . $form_id, wp_salt( 'nonce' ) );
	}

	private function make_token( int $answer, int $form_id ): string {
		$issued = time();

		return $issued . '|' . $this->sign( $answer, $issued, $form_id );
	}

	private function make_question( string $operations, int $max ): array {
		$max = max( 1, min( self::MAX_OPERAND_CEILING, $max ) );

		if ( 'mixed' === $operations ) {
			$operations = wp_rand( 0, 1 ) ? 'plus' : 'minus';
		}

		$a = wp_rand( 1, $max );
		$b = wp_rand( 1, $max );

		if ( 'minus' === $operations ) {
			if ( $b > $a ) {
				[ $a, $b ] = [ $b, $a ];
			}

			return [ $a, '-', $b, $a - $b ];
		}

		return [ $a, '+', $b, $a + $b ];
	}

	private function render_image( string $question, int $w, int $h, string $bg, string $fg, string $noise ): string {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return '';
		}

		$w = max( 60, min( self::CANVAS_MAX_W, $w ) );
		$h = max( 24, min( self::CANVAS_MAX_H, $h ) );

		$bw = (int) ceil( $w / 2 );
		$bh = (int) ceil( $h / 2 );

		$img = imagecreatetruecolor( $bw, $bh );

		[ $br, $bgc, $bb ] = $this->hex_to_rgb( $bg, [ 245, 246, 248 ] );
		[ $fr, $fgc, $fb ] = $this->hex_to_rgb( $fg, [ 32, 36, 44 ] );

		imagefilledrectangle( $img, 0, 0, $bw, $bh, imagecolorallocate( $img, $br, $bgc, $bb ) );

		$ink = imagecolorallocate( $img, $fr, $fgc, $fb );

		$counts = [
			'low'    => [ 1, 12 ],
			'medium' => [ 3, 40 ],
			'high'   => [ 6, 90 ],
		];
		[ $lines, $dots ] = $counts[ $noise ] ?? $counts['medium'];

		for ( $i = 0; $i < $lines; $i++ ) {
			$smudge = imagecolorallocatealpha( $img, $fr, $fgc, $fb, wp_rand( 70, 100 ) );
			imageline( $img, wp_rand( 0, $bw ), wp_rand( 0, $bh ), wp_rand( 0, $bw ), wp_rand( 0, $bh ), $smudge );
		}
		for ( $i = 0; $i < $dots; $i++ ) {
			$speck = imagecolorallocatealpha( $img, $fr, $fgc, $fb, wp_rand( 60, 110 ) );
			imagesetpixel( $img, wp_rand( 0, $bw - 1 ), wp_rand( 0, $bh - 1 ), $speck );
		}

		$font   = 5;
		$char_w = imagefontwidth( $font );
		$char_h = imagefontheight( $font );
		$chars  = str_split( $question );
		$x      = max( 2, (int) ( ( $bw - ( count( $chars ) * $char_w ) ) / 2 ) );
		$base_y = (int) ( ( $bh - $char_h ) / 2 );

		foreach ( $chars as $char ) {
			imagestring( $img, $font, $x, max( 0, $base_y + wp_rand( -2, 2 ) ), $char, $ink );
			$x += $char_w;
		}

		$scaled = imagescale( $img, $w, $h, IMG_BILINEAR_FIXED );
		if ( $scaled ) {
			imagedestroy( $img );
			$img = $scaled;
		}

		ob_start();
		imagepng( $img );
		$png = ob_get_clean();
		imagedestroy( $img );

		return 'data:image/png;base64,' . base64_encode( $png );
	}

	/** @return int[] */
	private function hex_to_rgb( string $hex, array $fallback ): array {
		$hex = ltrim( trim( $hex ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return $fallback;
		}

		return [ hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) ];
	}

	protected function register_style(): array {
		wp_register_style( 'fcf7-spam-protection', FCF7_ASSETS . 'css/spam-protection.min.css', [], FCF7_VERSION );
		return [ 'fcf7-spam-protection' ];
	}

	protected function register_preview_style(): array {
		wp_register_style( 'fcf7-spam-protection', FCF7_ASSETS . 'css/spam-protection.min.css', [], FCF7_VERSION );
		return [ 'fcf7-spam-protection' ];
	}

	protected function register_script(): array {
		wp_register_script( 'fcf7-spam-protection', FCF7_ASSETS . 'js/spam-protection.min.js', [], FCF7_VERSION, true );
		wp_localize_script(
			'fcf7-spam-protection',
			'fcf7SpamProtection',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE ),
			]
		);
		return [ 'fcf7-spam-protection' ];
	}

	public function render_front( $tag, $form_id = 0 ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$method     = 'canvas' === $tag->get_option( 'method', '', true ) ? 'canvas' : 'text';
		$operations = $tag->get_option( 'operations', '', true ) ?: 'plus';
		$max        = (int) ( $tag->get_option( 'max_operand', 'int', true ) ?: 10 );
		$max_age    = (int) ( $tag->get_option( 'max_age', 'int', true ) ?: self::DEFAULT_MAX_AGE );
		$refresh    = 'off' !== $tag->get_option( 'refresh', '', true );

		$width  = (int) ( $tag->get_option( 'canvas_width', 'int', true ) ?: 160 );
		$height = (int) ( $tag->get_option( 'canvas_height', 'int', true ) ?: 50 );
		$bg     = (string) ( $tag->get_option( 'canvas_bg', '', true ) ?: '#f5f6f8' );
		$fg     = (string) ( $tag->get_option( 'canvas_color', '', true ) ?: '#20242c' );
		$noise  = (string) ( $tag->get_option( 'noise', '', true ) ?: 'medium' );

		$values      = (array) $tag->values;
		$placeholder = trim( (string) ( $values[0] ?? '' ) );

		[ $left, $operator, $right, $answer ] = $this->make_question( $operations, $max );

		$question = $left . ' ' . $operator . ' ' . $right . ' =';
		$image    = 'canvas' === $method ? $this->render_image( $question, $width, $height, $bg, $fg, $noise ) : '';

		if ( 'canvas' === $method && '' === $image ) {
			$method = 'text';
		}

		$token  = $this->make_token( $answer, (int) $form_id );
		$issued = (int) explode( '|', $token )[0];

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = 'wpcf7-' . rtrim( $tag->type, '*' ) . ' fcf7-sp';

		$wrap_atts = [
			'class'         => $tag->get_class_option( $class ),
			'id'            => $tag->get_id_option(),
			'data-method'   => $method,
			'data-issued'   => (string) $issued,
			'data-max-age'  => (string) $max_age,
			'data-field'    => $tag->name,
			'data-ops'      => $operations,
			'data-max'      => (string) $max,
			'data-width'    => (string) $width,
			'data-height'   => (string) $height,
			'data-bg'       => $bg,
			'data-color'    => $fg,
			'data-noise'    => $noise,
			'data-form-id'  => (string) $form_id,
		];

		$input_class = 'wpcf7-form-control wpcf7-validates-as-required fcf7-sp-answer';
		if ( $validation_error ) {
			$input_class .= ' wpcf7-not-valid';
		}

		$input_atts = [
			'type'         => 'text',
			'name'         => $tag->name,
			'class'        => $input_class,
			'inputmode'    => 'numeric',
			'autocomplete' => 'off',
			'value'        => '',
			'placeholder'  => $placeholder,
			'aria-required' => 'true',
			'aria-invalid' => $validation_error ? 'true' : 'false',
		];
		if ( $validation_error ) {
			$input_atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
		}

		ob_start();
		?>
		<span class="wpcf7-form-control-wrap <?php echo esc_attr( $tag->name ); ?>" data-name="<?php echo esc_attr( $tag->name ); ?>">
			<span <?php echo wp_kses( wpcf7_format_atts( $wrap_atts ), array() ); ?>>
				<span class="fcf7-sp-challenge">
					<?php if ( 'canvas' === $method ) : ?>
						<canvas class="fcf7-sp-canvas" width="<?php echo esc_attr( (string) $width ); ?>" height="<?php echo esc_attr( (string) $height ); ?>" role="img" aria-label="<?php esc_attr_e( 'Arithmetic verification image', 'compactform' ); ?>" data-image="<?php echo esc_attr( $image ); ?>"></canvas>
						<noscript>
							<img class="fcf7-sp-fallback-image" src="<?php echo esc_attr( $image ); ?>" width="<?php echo esc_attr( (string) $width ); ?>" height="<?php echo esc_attr( (string) $height ); ?>" alt="<?php esc_attr_e( 'Arithmetic verification image', 'compactform' ); ?>" />
						</noscript>
					<?php else : ?>
						<span class="fcf7-sp-question"><?php echo esc_html( $question ); ?></span>
					<?php endif; ?>

					<?php if ( $refresh ) : ?>
						<button type="button" class="fcf7-sp-refresh" aria-label="<?php esc_attr_e( 'Get a new question', 'compactform' ); ?>" title="<?php esc_attr_e( 'Get a new question', 'compactform' ); ?>">
							<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 5V2L8 6l4 4V7a5 5 0 1 1-5 5H5a7 7 0 1 0 7-7z"/></svg>
						</button>
					<?php endif; ?>
				</span>

				<input <?php echo wp_kses( wpcf7_format_atts( $input_atts ), array() ); ?> />
				<input type="hidden" name="<?php echo esc_attr( $tag->name ); ?>_token" class="fcf7-sp-token" value="<?php echo esc_attr( $token ); ?>" />
			</span>
			<?php echo wp_kses( $validation_error, array( 'span' => array( 'class' => true, 'aria-hidden' => true ) ) ); ?>
		</span>
		<?php

		return apply_filters( 'fcf7_spam_protection_output', ob_get_clean(), $tag );
	}

	public function ajax_refresh(): void {
		$visitor = check_ajax_referer( self::NONCE, 'nonce', false );
		$author  = current_user_can( 'wpcf7_edit_contact_forms' ) && check_ajax_referer( 'fcf7_builder', 'nonce', false );

		if ( ! $visitor && ! $author ) {
			wp_send_json_error( [ 'message' => __( 'Expired. Please reload the page.', 'compactform' ) ], 403 );
		}

		$method     = 'canvas' === sanitize_text_field( wp_unslash( $_POST['method'] ?? '' ) ) ? 'canvas' : 'text';
		$operations = sanitize_key( (string) ( $_POST['ops'] ?? 'plus' ) );
		$form_id    = (int) sanitize_text_field( wp_unslash( $_POST['form_id'] ?? 0 ) );
		$max        = (int) sanitize_text_field( wp_unslash( $_POST['max'] ?? 10 ) );
		$width      = (int) sanitize_text_field( wp_unslash( $_POST['width'] ?? 160 ) );
		$height     = (int) sanitize_text_field( wp_unslash( $_POST['height'] ?? 50 ) );
		$bg         = sanitize_text_field( wp_unslash( (string) ( $_POST['bg'] ?? '' ) ) );
		$fg         = sanitize_text_field( wp_unslash( (string) ( $_POST['color'] ?? '' ) ) );
		$noise      = sanitize_key( (string) ( $_POST['noise'] ?? 'medium' ) );

		if ( ! in_array( $operations, [ 'plus', 'minus', 'mixed' ], true ) ) {
			$operations = 'plus';
		}

		[ $left, $operator, $right, $answer ] = $this->make_question( $operations, $max );

		$question = $left . ' ' . $operator . ' ' . $right . ' =';
		$image    = 'canvas' === $method ? $this->render_image( $question, $width, $height, $bg, $fg, $noise ) : '';
		$token    = $this->make_token( $answer, $form_id );

		wp_send_json_success(
			[
				'question' => '' === $image ? $question : '',
				'image'    => $image,
				'token'    => $token,
				'issued'   => (int) explode( '|', $token )[0],
			]
		);
	}

	public function validate_answer( $result, $tag ) {
		$values  = (array) $tag->values;
		$message = trim( (string) ( $values[1] ?? '' ) );
		if ( '' === $message ) {
			$message = __( 'Your answer to the verification question is not correct.', 'compactform' );
		}

		$answer = trim( sanitize_text_field( wpcf7_superglobal_post( $tag->name ) ) );
		$token  = sanitize_text_field( wpcf7_superglobal_post( $tag->name . '_token' ) );

		if ( '' === $answer ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			return $result;
		}

		$parts = explode( '|', $token );
		if ( 2 !== count( $parts ) || ! ctype_digit( $parts[0] ) ) {
			$result->invalidate( $tag, $message );
			return $result;
		}

		[ $issued, $signature ] = $parts;

		$max_age = (int) ( $tag->get_option( 'max_age', 'int', true ) ?: self::DEFAULT_MAX_AGE );
		$age     = time() - (int) $issued;

		if ( $age < 0 || $age > $max_age ) {
			$result->invalidate( $tag, __( 'The verification question expired. Please answer the new one.', 'compactform' ) );
			return $result;
		}

		$attempts_key = 'fcf7_sp_' . md5( $signature );
		$tried        = get_transient( $attempts_key );
		$tried        = is_array( $tried ) ? $tried : [];

		if ( count( $tried ) >= self::MAX_ATTEMPTS ) {
			$result->invalidate( $tag, __( 'Too many incorrect answers. Please answer the new question.', 'compactform' ) );
			return $result;
		}

		$contact_form = \WPCF7_ContactForm::get_current();
		$form_id      = $contact_form ? (int) $contact_form->id() : 0;

		if ( ! preg_match( '/^-?\d+$/', $answer ) || ! hash_equals( $this->sign( (int) $answer, (int) $issued, $form_id ), $signature ) ) {
			if ( ! in_array( $answer, $tried, true ) ) {
				$tried[] = $answer;
				set_transient( $attempts_key, $tried, $max_age );
			}

			$result->invalidate( $tag, $message );
		}

		return $result;
	}

	protected function register_controls(): void {
		$this->start_section(
			'general',
			[
				'label' => __( 'General', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->register_label_control();
			$this->register_name_control();
			$this->register_description_control();
			$this->register_width_control();
			$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section(
			'captcha',
			[
				'label' => __( 'Spam Protection', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->add_control(
				'method',
				[
					'type'        => Controls_Manager::SELECT,
					'label'       => __( 'Method', 'compactform' ),
					'default'     => 'text',
					'options'     => [
						'text'   => __( 'Arithmetic', 'compactform' ),
						'canvas' => __( 'Arithmetic in Canvas', 'compactform' ),
					],
					'description' => __( 'Canvas paints the question as an image, so the numbers are not readable as page text. Falls back to Arithmetic if the server has no GD image support.', 'compactform' ),
				]
			);
			$this->add_control(
				'operations',
				[
					'type'    => Controls_Manager::SELECT,
					'label'   => __( 'Operation', 'compactform' ),
					'default' => 'plus',
					'options' => [
						'plus'  => __( 'Addition', 'compactform' ),
						'minus' => __( 'Subtraction', 'compactform' ),
						'mixed' => __( 'Both', 'compactform' ),
					],
				]
			);
			$this->add_control(
				'max_operand',
				[
					'type'        => Controls_Manager::NUMBER,
					'label'       => __( 'Largest Number', 'compactform' ),
					'default'     => '10',
					'description' => __( 'Operands are picked between 1 and this value.', 'compactform' ),
				]
			);
			$this->add_control(
				'answer_placeholder',
				[
					'type'    => Controls_Manager::TEXT,
					'label'   => __( 'Placeholder', 'compactform' ),
					'default' => __( 'Your answer', 'compactform' ),
				]
			);
			$this->add_control(
				'hide_refresh',
				[
					'type'  => Controls_Manager::SWITCHER,
					'label' => __( 'Hide Refresh Button', 'compactform' ),
				]
			);
			$this->add_control(
				'max_age',
				[
					'type'        => Controls_Manager::NUMBER,
					'label'       => __( 'Question Lifetime (seconds)', 'compactform' ),
					'default'     => (string) self::DEFAULT_MAX_AGE,
					'description' => __( 'How long a question stays answerable. The visitor gets a fresh one automatically before it expires.', 'compactform' ),
				]
			);
			$this->add_control(
				'error_message',
				[
					'type'        => Controls_Manager::TEXTAREA,
					'label'       => __( 'Error Message', 'compactform' ),
					'description' => __( 'Shown when the answer is wrong.', 'compactform' ),
				]
			);
		$this->end_section();

		$this->start_section(
			'captcha_image',
			[
				'label' => __( 'Canvas Image', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->add_control(
				'canvas_width',
				[
					'type'      => Controls_Manager::NUMBER,
					'label'     => __( 'Image Width (px)', 'compactform' ),
					'default'   => '160',
					'condition' => [ 'method' => 'canvas' ],
				]
			);
			$this->add_control(
				'canvas_height',
				[
					'type'      => Controls_Manager::NUMBER,
					'label'     => __( 'Image Height (px)', 'compactform' ),
					'default'   => '50',
					'condition' => [ 'method' => 'canvas' ],
				]
			);
			$this->add_control(
				'canvas_bg',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Image Background', 'compactform' ),
					'condition' => [ 'method' => 'canvas' ],
				]
			);
			$this->add_control(
				'canvas_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Image Text Color', 'compactform' ),
					'condition' => [ 'method' => 'canvas' ],
				]
			);
			$this->add_control(
				'noise',
				[
					'type'      => Controls_Manager::SELECT,
					'label'     => __( 'Noise', 'compactform' ),
					'default'   => 'medium',
					'options'   => [
						'low'    => __( 'Low', 'compactform' ),
						'medium' => __( 'Medium', 'compactform' ),
						'high'   => __( 'High', 'compactform' ),
					],
					'condition' => [ 'method' => 'canvas' ],
				]
			);
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_description_style_controls();
		$this->register_challenge_style_controls();
		$this->register_answer_style_controls();
		$this->register_refresh_style_controls();
		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	private function register_challenge_style_controls(): void {
		$challenge = '{{WRAPPER}} .fcf7-sp-challenge';
		$question  = '{{WRAPPER}} .fcf7-sp-question';

		$this->start_section(
			'section_sp_challenge_style',
			[
				'label' => __( 'Question', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'sp_challenge_gap',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Spacing', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem' ],
					'min'        => 0,
					'max'        => 60,
					'selectors'  => [ $challenge => 'gap: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_challenge_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$challenge => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_question_typography',
				[
					'type'       => Controls_Manager::TYPOGRAPHY,
					'label'      => __( 'Typography', 'compactform' ),
					'selector'   => $question,
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_question_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Text Color', 'compactform' ),
					'selectors' => [ $question => 'color: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'sp_question_bg',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Background Color', 'compactform' ),
					'selectors' => [ $question => 'background-color: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'sp_question_border',
				[
					'type'     => Controls_Manager::BORDER,
					'label'    => __( 'Border', 'compactform' ),
					'selector' => $question,
				]
			);
			$this->add_control(
				'sp_question_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [ $question => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
		$this->end_section();
	}

	private function register_answer_style_controls(): void {
		$input = '{{WRAPPER}} .fcf7-sp-answer';

		$this->start_section(
			'section_sp_answer_style',
			[
				'label' => __( 'Answer Input', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'sp_answer_width',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Width', 'compactform' ),
					'units'      => [ 'px', '%', 'em', 'rem', 'custom' ],
					'ranges'     => [
						'px' => [ 'min' => 0, 'max' => 600 ],
						'%'  => [ 'min' => 0, 'max' => 100 ],
					],
					'min'        => 0,
					'max'        => 600,
					'selectors'  => [ $input => 'width: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_answer_typography',
				[
					'type'       => Controls_Manager::TYPOGRAPHY,
					'label'      => __( 'Typography', 'compactform' ),
					'selector'   => $input,
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_answer_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$input => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_answer_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Text Color', 'compactform' ),
					'selectors' => [ $input => 'color: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'sp_answer_bg',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Background Color', 'compactform' ),
					'selectors' => [ $input => 'background-color: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'sp_answer_border',
				[
					'type'     => Controls_Manager::BORDER,
					'label'    => __( 'Border', 'compactform' ),
					'selector' => $input,
				]
			);
			$this->add_control(
				'sp_answer_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [ $input => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
		$this->end_section();
	}

	private function register_refresh_style_controls(): void {
		$button = '{{WRAPPER}} .fcf7-sp-refresh';

		$this->start_section(
			'section_sp_refresh_style',
			[
				'label' => __( 'Refresh Button', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'sp_refresh_size',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Icon Size', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem' ],
					'min'        => 8,
					'max'        => 64,
					'selectors'  => [ $button . ' svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_refresh_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$button => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sp_refresh_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [ $button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
					'responsive' => true,
				]
			);

			$this->start_controls_tabs( 'sp_refresh_tabs', [ 'separator' => 'before' ] );

				$this->start_controls_tab( 'sp_refresh_tab_normal', [ 'label' => __( 'Normal', 'compactform' ) ] );
					$this->add_control(
						'sp_refresh_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Icon Color', 'compactform' ),
							'selectors' => [ $button => 'color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'sp_refresh_bg',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $button => 'background-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'sp_refresh_border',
						[
							'type'     => Controls_Manager::BORDER,
							'label'    => __( 'Border', 'compactform' ),
							'selector' => $button,
						]
					);
				$this->end_controls_tab();

				$this->start_controls_tab( 'sp_refresh_tab_hover', [ 'label' => __( 'Hover', 'compactform' ) ] );
					$this->add_control(
						'sp_refresh_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Icon Color', 'compactform' ),
							'selectors' => [ $button . ':hover' => 'color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'sp_refresh_bg_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $button . ':hover' => 'background-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'sp_refresh_border_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Border Color', 'compactform' ),
							'selectors' => [ $button . ':hover' => 'border-color: {{VALUE}} !important;' ],
							'condition' => [ 'sp_refresh_border[style]!' => '' ],
						]
					);
				$this->end_controls_tab();

			$this->end_controls_tabs();
		$this->end_section();
	}

	protected function option_map(): array {
		return [
			[ 'prop' => 'method', 'opt' => 'method' ],
			[ 'prop' => 'operations', 'opt' => 'operations' ],
			[ 'prop' => 'max_operand', 'opt' => 'max_operand' ],
			[ 'prop' => 'max_age', 'opt' => 'max_age' ],
			[ 'prop' => 'canvas_width', 'opt' => 'canvas_width' ],
			[ 'prop' => 'canvas_height', 'opt' => 'canvas_height' ],
			[ 'prop' => 'canvas_bg', 'opt' => 'canvas_bg' ],
			[ 'prop' => 'canvas_color', 'opt' => 'canvas_color' ],
			[ 'prop' => 'noise', 'opt' => 'noise' ],
			[ 'prop' => 'hide_refresh', 'opt' => 'refresh', 'type' => 'bool', 'on' => 'off' ],
		];
	}

	protected function tag_values( array $data ): array {
		$placeholder = trim( (string) ( $data['answer_placeholder'] ?? '' ) );

		return [
			'' !== $placeholder ? $placeholder : __( 'Your answer', 'compactform' ),
			trim( (string) ( $data['error_message'] ?? '' ) ),
		];
	}
}
