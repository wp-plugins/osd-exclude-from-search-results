<?php
// Prevent direct access to file
defined('ABSPATH') or die("No script kiddies please!");

//SETTINGS PAGE
$settingsPage = new OSDExcludeFromSearchSettings();

class OSDExcludeFromSearchSettings {
    private $options;
    private $available_post_types = array();

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_init', array($this, 'page_init'));
    }

    //add options page to wp
    public function add_menu_item() {
        add_options_page(
            'OSD Exclude From Search Results', 
            'OSD Search Results', 
            'manage_options', 
            'osd-exclude-from-search-options', 
            array($this, 'create_admin_page')
        ); 
    }

    //create options page
    public function create_admin_page() {
        //add styling to the page
        $this->addStyle();

        // Set class property
        $this->options = get_option('osd_exclude_from_search_options');
        $post_types = get_post_types(array('public' => 1), 'array');
        foreach($post_types as $post_type) {
            $this->available_post_types[$post_type->name] = $post_type->label;
        }
        ?>
        <div class="wrap">
            <h2>OSD Exclude From Search Results</h2>   
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields('osd-exclude-from-search-options');   
                do_settings_sections('osd-exclude-from-search-options');
                submit_button(); 
            ?>
            </form>
        </div>
        <?php

        //add js to the page
        $this->addJS();
    }

    //register / add options 
    public function page_init() {        
        register_setting(
            'osd-exclude-from-search-options', // Option group
            'osd_exclude_from_search_options', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'main_settings', // ID
            'Global Exclude From Search Results Settings', // Title
            array($this, 'print_section_info'), // Callback
            'osd-exclude-from-search-options' // Page
        );  

        add_settings_field(
            'show-on', // ID
            'Show Settings on Post Types', // Title 
            array($this, 'show_on_callback'), // Callback
            'osd-exclude-from-search-options', // Page
            'main_settings' // Section           
        );      

        add_settings_field(
            'exclude-all', 
            'Exclude All Posts From Post Type', 
            array($this, 'exclude_all_callback'), 
            'osd-exclude-from-search-options', 
            'main_settings'
        );      
    }

    //sanitize  
    public function sanitize($input) {
        return $input;
    }

    //section text
    public function print_section_info() {
        echo "The first setting defines what posts will have the plugin metabox.<br />
            The second setting will remove all posts of a certain type from search results / generated menus.";
    }

    /**** output to admin settings screen ****/
    public function show_on_callback() {
       echo "<ul class='show-on-post-types'>";
        foreach($this->available_post_types as $name => $label) {
            $checked = '';
            if(isset($this->options['show_on'][$name])) {
                $checked = " checked='checked'";
            }
            echo 
                "<li>
                    <input type='checkbox' id='{$name}' name='osd_exclude_from_search_options[show_on][{$name}]'{$checked} value='{$label}' />
                    <label for='{$name}'>{$label}</label>
                </li>";
        }
        echo "</ul>";
    }

    public function exclude_all_callback() {
        echo "<ul class='show-on-post-types'>";
        foreach($this->available_post_types as $name => $label) {
            $checked = '';
            if(isset($this->options['exclude_all'][$name])) {
                $checked = " checked='checked'";
            }
            echo 
                "<li>
                    <input type='checkbox' value='1' id='{$name}' name='osd_exclude_from_search_options[exclude_all][{$name}]'{$checked} value='{$label}' />
                    <label for='{$name}'>{$label}</label>
                </li>";
        }
        echo "</ul>";
    }
    /**** end output to admin settings screen ****/

    private function addJS() {
        ?>
        <script type='text/javascript'>
            document.onready = function() {
                
            }
        </script>
        <?php
    }

    private function addStyle() {
        ?>
        <style type="text/css">
            
        </style>    
        <?php
    }
}