<?php
	/*
	Plugin Name: OSD Exclude From Search Results
	Plugin URI: http://outsidesource.com
	Description: A plugin that excludes selected pages or posts from the search results.
	Version: 1.1
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
	
	//this method of adding an action to the bulk dropdown will have to work until wp is updated to accommodate
	function osd_add_filter_bulk_action() {
		global $post_type;
		if($post_type == 'post' || $post_type == 'page') {
			echo "<script type='text/javascript'>
					jQuery(document).ready(function() {
						jQuery('<option>').val('osdRemove').text('OSD Make Searchable').appendTo('select[name=action]');
						jQuery('<option>').val('osdEnable').text('OSD Make Non-Searchable').appendTo('select[name=action]');
						jQuery('<option>').val('osdRemove').text('OSD Remove Search').appendTo('select[name=action2]');
						jQuery('<option>').val('osdEnable').text('OSD Make Non-Searchable').appendTo('select[name=action2]');
					});
				</script>";
		}
	}
	add_action('admin_footer-edit.php', 'osd_add_filter_bulk_action');
	
 	//save the user selection
	function osd_save_filter_bulk_action() {
		$action = _get_list_table('WP_Posts_List_Table')->current_action();
		
		if(($action == 'osdRemove' || $action == 'osdEnable') && isset($_GET['post'])) {
			$post_ids = $_GET['post'];
			$checked = ($action == 'osdRemove') ? 1 : 0;
			
			foreach($post_ids as $post_id) {
				update_post_meta($post_id, 'exclude_from_search', $checked);
			}
			
			$url = (!wp_get_referer()) ? admin_url("edit.php?post_type=$post_type") : wp_get_referer();
			$url = remove_query_arg(array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $url);
		 	$url = add_query_arg('osdBulkFilter', '1', $url);

			wp_redirect($url);
			exit;
		}
	}
	add_action('load-edit.php', 'osd_save_filter_bulk_action');
	 
	// return a message stating the obvious 
	function osd_bulk_filter_admin_msg() {
		global $pagenow;
		
		if($pagenow == 'edit.php' && isset($_GET['osdBulkFilter'])) {
			echo "<div class='updated'><p>Posts have been updated.</p></div>";
		}
	}
	add_action('admin_notices', 'osd_bulk_filter_admin_msg');

	function osd_filtered_column($columns) {
		$newColumn = array('searchable' => __('Searchable'));
		//return array_splice($columns, 2, 0, $newColumn); //insert into position 2
		return array_merge($columns, $newColumn);
	}
	add_filter('manage_posts_columns' , 'osd_filtered_column');
	add_filter('manage_page_posts_columns' , 'osd_filtered_column');

	//add a column to the post list
	function osd_populate_filtered_column($column, $post_id) {
		if($column == 'searchable') {
			$values = get_post_meta($post_id);
			echo ($values['exclude_from_search'][0] == 1) ? "No":  "Yes";
		}
	}
	add_action('manage_posts_custom_column' , 'osd_populate_filtered_column', 10, 2);
	add_action('manage_pages_custom_column' , 'osd_populate_filtered_column', 10, 2);
	
	//style the new column
	function osd_filter_column_style() {
	   echo '<style type="text/css">
			   #searchable, .column-searchable {
					width: 10%;   
					text-align: center !important;
			   }
			 </style>';
	}
	add_action('admin_head', 'osd_filter_column_style');
?>