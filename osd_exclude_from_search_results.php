<?php
	/*
	Plugin Name: OSD Exclude From Search Results
	Plugin URI: http://outsidesource.com
	Description: A plugin that excludes selected pages or posts from the search results.
	Version: 1.0
	Author: OSD Web Development Team
	Author URI: http://outsidesource.com
	License: GPL2v2
	*/
	
	//register meta box to be able to use page as footer for ease of management
	function osd_exclude_from_search_box_add() {
		//add_meta_box( $id, $title, $callback, $page, $context, $priority, $callback_args ); 
		add_meta_box('osd_exclude_from_search', 'OSD Exclude From Search', 'osd_efs_cb', 'page', 'side', 'default');
		add_meta_box('osd_exclude_from_search', 'OSD Exclude From Search', 'osd_efs_cb', 'post', 'side', 'default');
	}
	add_action('add_meta_boxes', 'osd_exclude_from_search_box_add');
	
	//custom metabox call back
	function osd_efs_cb() {
		global $post;
		$values = get_post_meta($post->ID);
		$selected = ($values['exclude_from_search'][0] == 1) ? "checked='checked'" : '';
		
    	echo "<input type='checkbox' ".$selected." name='exclude_from_search' id='exclude_from_search' />";		
    	echo "<label for='exclude_from_search'>Exclude this page / post from the site search results? </label>";
	}
	
	//save our custom page as footer box
	function osd_exclude_from_search_box_save($post_id) {
		// Bail if we're doing an auto save
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { 
			return;
		}
		
		// if our current user can't edit this post, bail
		if(!current_user_can('edit_post')) {
			return;
		}

		// Make sure your data is set before trying to save it
		if(isset($_POST['exclude_from_search'])) {
			$checked = 1;
		} else {
			$checked = 0;	
		}
		
		update_post_meta($post_id, 'exclude_from_search', $checked);
	}
	add_action('save_post', 'osd_exclude_from_search_box_save');
	
	//remove any posts / pages from the search results that are marked to be excluded
	function osd_exclude_from_search_filter($query) {
		if($query->is_search && !is_admin()) {
			global $wpdb;
			$sql = "SELECT * 
					FROM wp_postmeta 
					WHERE meta_value = 1
					AND meta_key = 'exclude_from_search'";
					
			$results = $wpdb->get_results($sql, ARRAY_A);
			
			foreach($results as $result) {
				$excludeArray[] = $result['post_id'];
			}
			
			$query->set('post__not_in', $excludeArray);
		}
		return $query;
	}
	add_filter('pre_get_posts','osd_exclude_from_search_filter');
?>