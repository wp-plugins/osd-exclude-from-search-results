<?php
defined('ABSPATH') or die("No script kiddies please!");

class OSDExcludeFromSearchResults {
	private $user_settings = array(
        'show_on' => array(),
        'exclude_all' => array()
    );
    public $attachment = false;

	function __construct() { 
		$user_settings = get_option('osd_exclude_from_search_options');
		$this->user_settings = ($user_settings === false) ? $this->user_settings : $user_settings;
		$current_post_type = (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post';
		$media_post_type = (get_post_type($_GET['post']) == 'attachment') ? true : false;

		// Add actions to only pages that are included in the user's settings
		global $pagenow;
		if (is_admin()) {
			if ($pagenow == 'post.php') {
				add_action('add_meta_boxes', array($this, 'osd_exclude_from_search_box_add')); 
				add_action('save_post', array($this, 'osd_exclude_from_search_box_save'));
				// Attachments
				add_action('edit_attachment', array($this, 'osd_exclude_from_search_box_save'));
				add_action('add_attachment', array($this, 'osd_exclude_from_search_box_save'));
			}

			// Attachments only
			if ($pagenow == 'upload.php') {
				$this->attachment = true;
				add_action('admin_footer-upload.php', array($this, 'osd_add_filter_bulk_action'));
				add_action('load-upload.php', array($this, 'osd_save_filter_bulk_action'));
				add_action('admin_notices', array($this, 'osd_bulk_filter_admin_msg'));
				add_filter('manage_media_columns', array($this, 'osd_filtered_column'));
				add_action('manage_media_custom_column' , array($this, 'osd_populate_filtered_column'), 10, 2);
				add_filter("manage_upload_sortable_columns", array($this, 'osd_register_searchable_sort'));
			}

			// Posts / Custom post types
			if ($pagenow == 'edit.php' && isset($this->user_settings['show_on'][$current_post_type])) {
				add_action('admin_footer-edit.php', array($this, 'osd_add_filter_bulk_action'));
				add_action('load-edit.php', array($this, 'osd_save_filter_bulk_action'));
				add_action('admin_notices', array($this, 'osd_bulk_filter_admin_msg'));

				if ($current_post_type == 'posts' && $pagenow != 'upload.php') {
					add_filter('manage_posts_columns' , array($this, 'osd_filtered_column')); // Posts only
					add_action('manage_posts_custom_column' , array($this, 'osd_populate_filtered_column'), 10, 2); // Posts only
				} else {
					add_filter("manage_{$current_post_type}_posts_columns", array($this, 'osd_filtered_column')); // All but posts
					if (is_post_type_hierarchical($current_post_type)) {
						add_action('manage_pages_custom_column' , array($this, 'osd_populate_filtered_column'), 10, 2); // Pages and hierarchical customs
					} else {
						add_action("manage_{$current_post_type}_posts_custom_column" , array($this, 'osd_populate_filtered_column'), 10, 2); // Non-hierarchical customs
					}
				}
				add_filter("manage_edit-{$current_post_type}_sortable_columns", array($this, 'osd_register_searchable_sort')); // All 				

				add_action('admin_head', array($this, 'osd_filter_column_style'));
				add_filter('request', array($this, 'osd_searchable_orderby'));
			}
		} else {
			add_filter('pre_get_posts', array($this, 'osd_exclude_from_search_filter'));
			add_filter('wp_page_menu_args', array($this, 'osd_exclude_from_menu'));
		}
    }

	//register meta box to be able to use page as footer for ease of management
	function osd_exclude_from_search_box_add() {
		foreach($this->user_settings['show_on'] as $name => $label) {
			add_meta_box('osd_exclude_from_search', 'OSD Exclude From Search', array($this, 'osd_efs_cb'), $name, 'side', 'default');
	    }
	}

	//custom metabox call back
	function osd_efs_cb() {
		global $post;
		$values = get_post_meta($post->ID);
		$selectedSearch = ($values['exclude_from_search'][0] == 1) ? "checked='checked'" : '';
		$selectedMenu = ($values['osd_exclude_from_menu'][0] == 1) ? "checked='checked'" : '';

		echo "<input type='checkbox' ".$selectedSearch." name='exclude_from_search' id='exclude_from_search' />";		
		echo "<label for='exclude_from_search'>Exclude this post from the site search results? </label>";
		echo "<br /><br />";
		echo "<input type='checkbox' ".$selectedMenu." name='osd_exclude_from_menu' id='osd_exclude_from_menu' />";		
		echo "<label for='osd_exclude_from_menu'>Exclude this post from menus auto-generated from page structure? </label>";
	}

	//save our custom page as footer box
	function osd_exclude_from_search_box_save($post_id) {
		// Bail if we're doing an auto save
		// if our current user can't edit this post, bail
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			|| (defined('DOING_AJAX') && DOING_AJAX) 
			|| !current_user_can('edit_post', $post_id)) {
			return;
	    }

		// Make sure your data is set before trying to save it
		$checkedSearch = (isset($_POST['exclude_from_search'])) ? 1 : 0;
		$checkedMenu = (isset($_POST['osd_exclude_from_menu'])) ? 1 : 0;
		
		update_post_meta($post_id, 'exclude_from_search', $checkedSearch);
		update_post_meta($post_id, 'osd_exclude_from_menu', $checkedMenu);
	}

	//remove any posts / pages from the search results that are marked to be excluded
	function osd_exclude_from_search_filter($query) {
		if($query->is_search) {
			// Entire post types
			if (!isset($query->query_vars['post_type'])) {
				$acceptable_post_types = array();
				$all_post_types_array = get_post_types(array('public' => 1), 'array');
		        foreach($all_post_types_array as $post_type) {
		        	if (!isset($this->user_settings['exclude_all'][$post_type->name])) {
			            $acceptable_post_types[] = $post_type->name;
			        }
		        }
		        
		        $query->set('post_type', $acceptable_post_types);
	    	}

	        // Individual posts
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

	//this method of adding an action to the bulk dropdown will have to work until wp is updated to accommodate
	function osd_add_filter_bulk_action() {
		echo "<script type='text/javascript'>
				jQuery(document).ready(function() {
					jQuery('<option>').val('osdSearchable').text('OSD Make Searchable').appendTo('select[name=action]');
					jQuery('<option>').val('osdNonSearchable').text('OSD Make Non-Searchable').appendTo('select[name=action]');
					jQuery('<option>').val('osdSearchable').text('OSD Make Searchable').appendTo('select[name=action2]');
					jQuery('<option>').val('osdNonSearchable').text('OSD Make Non-Searchable').appendTo('select[name=action2]');
				});
			</script>";
	}

	//save the user selection
	function osd_save_filter_bulk_action() {
		$action = _get_list_table('WP_Posts_List_Table')->current_action();
		$post_id_key = ($this->attachment) ? 'media' : 'post';

		if(($action == 'osdNonSearchable' || $action == 'osdSearchable') && isset($_GET[$post_id_key])) {
			$post_ids = $_GET[$post_id_key];
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
	 
	// return a message stating the obvious 
	function osd_bulk_filter_admin_msg() {
		if(isset($_GET['osdBulkFilter'])) {
			echo "<div class='updated'><p>Posts have been updated.</p></div>";
		}
	}

	function osd_filtered_column($columns) {
		$newColumn = array('searchable' => __('Searchable'));
		//return array_splice($columns, 2, 0, $newColumn); //insert into position 2
		return array_merge($columns, $newColumn);
	}

	//add a column to the post list
	function osd_populate_filtered_column($column, $post_id) {
		if($column == 'searchable') {
			$values = get_post_meta($post_id);
			echo ($values['exclude_from_search'][0] == 1) ? "No":  "Yes";
		}
	}

	//style the new column
	function osd_filter_column_style() {
	   echo '<style type="text/css">
			   #searchable, .column-searchable {
					width: 12%;   
					text-align: center !important;
			   }
			 </style>';
	}

	//make sortably by search filter status
	function osd_register_searchable_sort($columns) {
		$columns['searchable'] = 'searchable';
		return $columns;
	}

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
}