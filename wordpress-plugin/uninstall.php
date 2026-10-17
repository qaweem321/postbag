<?php

/**
 * Removes the plugin's settings when it is deleted.
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('postbag_signup_settings');
