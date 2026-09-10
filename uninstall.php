<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

/**
 * Removes the generated per-form CSS cache written under the uploads
 */
function compactform_delete_css_cache(): void {
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
}

if (is_multisite()) {
	$com_site_ids = get_sites(['fields' => 'ids']);

	foreach ($com_site_ids as $site_id) {
		switch_to_blog($site_id);
		compactform_delete_css_cache();
		restore_current_blog();
	}
} else {
	compactform_delete_css_cache();
}
