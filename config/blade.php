<?php

return [
	/**
	 * The file extension to treat as blade templates.
	 */
	'file_extension' => '.blade.php',

	/**
	 * Set to true to always compile a template during render, and to use the key as the file name, instead of the sha1.
	 *
	 * If false, then the sha1 filename is used, and cached templates are only compiled if the source has changed since creation.
	 */
	'development_mode' => \defined('WP_DEBUG') && WP_DEBUG,
];