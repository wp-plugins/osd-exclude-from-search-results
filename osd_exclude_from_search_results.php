<?php
/*
Plugin Name: OSD Exclude From Search Results
Plugin URI: http://outsidesource.com
Description: A plugin that excludes selected pages or posts from the search results.
Version: 2.4
Author: OSD Web Development Team
Author URI: http://outsidesource.com
License: GPL2v2
*/

defined('ABSPATH') or die("No script kiddies please!");

include_once('includes/OSDExcludeFromSearchResults.php');
new OSDExcludeFromSearchResults();

if (is_admin()) {
	include_once('includes/global_settings.php');
}

// Activation functions
function osd_exclude_from_search_activate() {
    include_once('includes/installation_actions.php');
}
register_activation_hook(__FILE__, 'osd_exclude_from_search_activate');

// Add settings page link to plugins page
function osd_exclude_from_search_link_generate($links) { 
	$settings_link = '<a href="admin.php?page=osd-exclude-from-search-options">Settings</a>'; 
	array_unshift($links, $settings_link); 
	return $links; 
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), 'osd_exclude_from_search_link_generate' );