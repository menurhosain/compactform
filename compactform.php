<?php
/**
 * Plugin Name: CompactForm – Drag & Drop Form Builder for Contact Form 7
 * Description: Drag-and-drop visual form builder for Contact Form 7 — add fields, layouts, and conditional logic without touching shortcode syntax.
 * Plugin URI: https://rstheme.com/compactform
 * Version: 1.0.0
 * Author: RSTheme
 * Author URI: https://rstheme.com/
 * Requires PHP: 7.4
 * Text Domain: compactform
 * Requires at least: 6.4
 * Tested up to: 7.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: contact-form-7
 */

use CompactForm\Classes\Admin_Dashboard;
use CompactForm\Classes\CF7_Init;
use CompactForm\Mail_Templates\Autop_Guard;

defined('ABSPATH') || die();

final class CompactForm {
	private static ?self $instance = null;

	public const VERSION = '1.0.0';

	public const MINIMUM_PHP_VERSION = '7.4';

	public static function instance(): self {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->define_constants();

		add_action('plugins_loaded', [ $this, 'init_plugin' ]);
	}

	private function define_constants(): void {
		define('FCF7_VERSION', self::VERSION);
		define('FCF7_FILE', __FILE__);
		define('FCF7_PATH', plugin_dir_path(FCF7_FILE));
		define('FCF7_URL', plugin_dir_url(FCF7_FILE));
		define('FCF7_INCLUDES', untrailingslashit(FCF7_PATH) . '/includes');
		define('FCF7_ASSETS', trailingslashit(FCF7_URL . 'assets'));
	}

	public function init_plugin(): void {
		// load_plugin_textdomain( 'compactform', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if (! $this->is_compatible()) {
			return;
		}

		$this->include_files();
	}

	public function is_compatible(): bool {
		if (! class_exists('WPCF7')) {
			add_action('admin_notices', [ $this, 'cf7_missing_notice' ]);

			return false;
		}

		if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
			return false;
		}

		return true;
	}

	private function include_files(): void {
		require_once FCF7_INCLUDES . '/helpers/class-config.php';
		require_once FCF7_INCLUDES . '/helpers/class-utils.php';
		require_once FCF7_INCLUDES . '/classes/class-cf7-init.php';
		require_once FCF7_INCLUDES . '/mail-templates/class-mail-templates-manager.php';
		require_once FCF7_INCLUDES . '/mail-templates/class-autop-guard.php';
		new Autop_Guard();
		// Boot the addon registrar (config-driven loading + vendor assets).
		CF7_Init::instance();

		if (is_admin()) {
			require_once FCF7_INCLUDES . '/classes/class-admin-dashboard.php';
			new Admin_Dashboard();
		}
	}

	public function cf7_missing_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<?php printf(
					/* translators: 1: plugin name, 2: CF7 plugin name, 3: link */
					esc_html__('%1$s requires %2$s to be installed and active. You can install it from %3$s.', 'compactform'),
					'<strong>CompactForm</strong>',
					'<strong>Contact Form 7</strong>',
					'<a href="' . esc_url(admin_url('plugin-install.php?tab=search&s=contact+form+7')) . '">' . esc_html__('here', 'compactform') . '</a>'
				); ?>
			</p>
		</div>
		<?php
	}
}

CompactForm::instance();
