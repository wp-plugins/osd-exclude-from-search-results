<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

if (get_option('osd_exclude_from_search_options') === false) {
    $default_options = array(
        'show_on' => array(
            'post' => 'Posts',
            'page' => 'Pages'
        ),
        'exclude_all' => array()
    );

    add_option('osd_social_share_options', $default_options, '', 'no');
}