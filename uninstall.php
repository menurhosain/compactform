<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// IIFE: keeps the cleanup helper out of the global function table so it can't
// collide with another plugin's uninstall.php (WP runs them all in one request
// during a bulk delete) or with itself if this file is ever included twice.
( function (): void {

	/**
	 * Removes the generated per-form CSS cache written under the uploads dir.
	 */
	$delete_css_cache = function (): void {
		$uploads = wp_upload_dir();

		if (! empty($uploads['error'])) {
			return;
		}

		$dir = trailingslashit($uploads['basedir']) . 'compactform/forms';

		if (! is_dir($dir)) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		global $wp_filesystem;

		if (! $wp_filesystem) {
			WP_Filesystem();
		}

		if ($wp_filesystem) {
			$wp_filesystem->delete($dir, true);
		}
	};

	if (is_multisite()) {
		$com_site_ids = get_sites(['fields' => 'ids']);

		foreach ($com_site_ids as $site_id) {
			switch_to_blog($site_id);
			$delete_css_cache();
			restore_current_blog();
		}
	} else {
		$delete_css_cache();
	}

} )();
