<?php
	/*
	Plugin Name: OSD Exclude From Search Results
	Plugin URI: http://outsidesource.com
	Description: A plugin that excludes selected pages or posts from the search results.
	Version: 1.6
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
		$selectedSearch = ($values['exclude_from_search'][0] == 1) ? "checked='checked'" : '';
		$selectedMenu = ($values['osd_exclude_from_menu'][0] == 1) ? "checked='checked'" : '';

    	echo "<input type='checkbox' ".$selectedSearch." name='exclude_from_search' id='exclude_from_search' />";		
    	echo "<label for='exclude_from_search'>Exclude this page / post from the site search results? </label>";
		echo "<br /><br />";
		echo "<input type='checkbox' ".$selectedMenu." name='osd_exclude_from_menu' id='osd_exclude_from_menu' />";		
    	echo "<label for='osd_exclude_from_menu'>Exclude this page / post from menus auto-generated from page structure? </label>";
	}
	
	//save our custom page as footer box
	function osd_exclude_from_search_box_save($post_id) {
		// Bail if we're doing an auto save
		// if our current user can't edit this post, bail
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			|| (defined('DOING_AJAX') && DOING_AJAX)
			|| ($_POST['post_type'] == 'page' && !current_user_can('edit_page', $post_id)) 
			|| !current_user_can('edit_post', $post_id)) {
			return;
        }

		// Make sure your data is set before trying to save it
		$checkedSearch = (isset($_POST['exclude_from_search'])) ? 1 : 0;
		$checkedMenu = (isset($_POST['osd_exclude_from_menu'])) ? 1 : 0;
		
		update_post_meta($post_id, 'exclude_from_search', $checkedSearch);
		update_post_meta($post_id, 'osd_exclude_from_menu', $checkedMenu);
	}
	add_action('save_post', 'osd_exclude_from_search_box_save');
	
	//remove any posts / pages from the search results that are marked to be excluded
	function osd_exclude_from_search_filter($query) {
		if($query->is_search && !is_admin()) {
			global $wpdb;
			$prefix = $wpdb->base_prefix;
			
			$sql = "SELECT * 
					FROM ".$prefix."postmeta 
					WHERE meta_value = 1
					AND meta_key = 'exclude_from_search'";
					
			$results = $wpdb->get_results($sql, ARRAY_A);
			
			$excludeArray = array();
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
						jQuery('<option>').val('osdSearchable').text('OSD Make Searchable').appendTo('select[name=action]');
						jQuery('<option>').val('osdNonSearchable').text('OSD Make Non-Searchable').appendTo('select[name=action]');
						jQuery('<option>').val('osdSearchable').text('OSD Make Searchable').appendTo('select[name=action2]');
						jQuery('<option>').val('osdNonSearchable').text('OSD Make Non-Searchable').appendTo('select[name=action2]');
					});
				</script>";
		}
	}
	add_action('admin_footer-edit.php', 'osd_add_filter_bulk_action');
	
 	//save the user selection
	function osd_save_filter_bulk_action() {
		$action = _get_list_table('WP_Posts_List_Table')->current_action();
		
		if(($action == 'osdNonSearchable' || $action == 'osdSearchable') && isset($_GET['post'])) {
			$post_ids = $_GET['post'];
			$checked = ($action == 'osdNonSearchable') ? 1 : 0;
			
			foreach($post_ids as $post_id) {
				update_post_meta($post_id, 'exclude_from_search', $checked);
			}
			
			$url = wp_get_referer();
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
					width: 12%;   
					text-align: center !important;
			   }
			 </style>';
	}
	add_action('admin_head', 'osd_filter_column_style');

	//make sortably by search filter status
	function osd_register_searchable_sort($columns) {
		$columns['searchable'] = 'searchable';
		return $columns;
	}
	add_filter('manage_edit-post_sortable_columns', 'osd_register_searchable_sort');
	add_filter('manage_edit-page_sortable_columns', 'osd_register_searchable_sort');
	
	//sort
	function osd_searchable_orderby($vars) {
		if(isset($vars['orderby']) && 'searchable' == $vars['orderby']) {
			$vars = array_merge($vars, array(
				'meta_key' => 'exclude_from_search',
				'orderby' => 'meta_value_num'
			));
		}
	 
		return $vars;
	}
	add_filter('request', 'osd_searchable_orderby');
	
	//removes items that are marked non searchable from menus generated by page structure
	function osd_exclude_from_menu($args) {
		global $wpdb;
		$prefix = $wpdb->base_prefix;
		
		$sql = "SELECT * 
				FROM ".$prefix."postmeta 
				WHERE meta_value = 1
				AND meta_key = 'osd_exclude_from_menu'";
				
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		$excludeArray = NULL;
		foreach($results as $result) {
			$excludeArray .= $result['post_id'] . ",";
		}
		$args['exclude'] = rtrim($excludeArray, ",");
		
		return $args;
	}
	add_filter('wp_page_menu_args', 'osd_exclude_from_menu');
?>