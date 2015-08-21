=== Plugin Name ===
Contributors: osdwebdev
Tags: wordpress, search results, exclude from search, posts, pages, hide from search
Requires at least: 3.4
Tested up to: 4.3
Stable tag: 2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OSD Exclude From Search Results plugin allows you to check a box on the edit screen or list screen for a page, post, or ANY custom post type (including ACF) that will exclude it in your site search results.

== Description ==

OSD Exclude From Search Results plugin allows you to quickly remove a page, blog post, or ANY custom post type (including ACF) from showing up in your site's search results.  This only changes the search results for the end user, not in the admin pages. The plugin adds a simple check box to the edit / create post screen and a new bulk action to the list view to remove the post or page from the search results. Now includes an admin screen to customize what post types you would like to manage with this plugin. Remove an entire post type from search results with a click of a button. It is extremely light weight, only 60 lines of code including the checkbox, so your site search will not slow down at all. Now you can exclude pages from menus!

== Installation ==

1. Upload the osd-exclude-from-search-results directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit the page that you want to remove from the search / menus, and check the new box

== Frequently Asked Questions ==

Q: Hi, I see under "other notes" you've said:
   "Can be modified with one line of code to include custom post types"
   Unless I'm being stupid (always a possibility!) I can't see anywhere that tells you how to do that?
   
A: Version 2.0 was released with a new administration screen to include custom post types!!

== Screenshots ==

1. Edit Post Screen with Checkboxes
1. Post List Screen with sortable column and new bulk action

== Changelog ==

= 2.0 =
* New version released YOU MUST SAVE THE NEW ADMIN SCREEN, OR DE-ACTIVATE AND THEN RE-ACTIVATE THE PLUGIN AFTER UPGRADE
* Your settings will not be lost, however you will need to save the post types you want to be managed in the new admin screen
* Now manages all post types including any custom non-hierarchical or hierarchical post type
* Exclude an entire post type from search results with one check box

= 1.6 =
* Fixed bug with quick edit overwriting plugin settings

= 1.5 =
* User permission updates

= 1.4 =
* Bug fix with database table prefix

= 1.3 =
* Add the ability to remove a page / post from menu 
* Only removes from menus created with wp_page_menu()

= 1.2 =
* Make the column sortable in the admin view of pages / posts
* Tweak styling

= 1.1 =
* Add bulk updating options in the pages / posts list screens
* Add column to quickly see status in pages / posts list screens

= 1.0 =
* Initial creation of the Exclude from Search Results plugin


== Upgrade Notice ==

= 2.4 =
Now Compatible with other custom searches on post types

= 2.3 =
Now works correctly with Media / Attachments post type

= 2.2 =
Bug fixes

= 2.1 =
Bug fixes

= 1.5 =
User permission updates

= 1.3 =
Ability to remove from menus as well

= 1.0 =
Adds quick and easy search result control

== A brief Feature List ==

1. Removes posts from search results
2. Removes pages from search results
3. NOW remove ANY post type including custom post types from search results
Removes pages / posts from menus generated with wp_page_menu()
4. Lightweight

Link to plugin page [Wordpress plugin page](http://wordpress.org/plugins/osd-exclude-from-search-results/ "Link").

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax