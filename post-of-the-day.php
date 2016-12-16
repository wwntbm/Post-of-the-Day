<?php
/**
 * Plugin Name: Post of the Day
 * Plugin URI: http://andrewrminion.com
 * Description: Display a random post per chosen interval from chosen categories or post types.
 * Author: Andrew Minion
 * Version: 1.1
 * Author URI: http://www.andrewrminion.com
 */

/**
 * Copyright 2011-2012 Morgan Davison and Andrew Minion
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define('POTD_VERSION', '1.1');
define('POTD_PLUGIN_URL', plugin_dir_url( __FILE__ ));

function potd_admin() {
	include('potd-admin.php');
}

function potd_admin_actions() {
	add_options_page("Post of the Day", "Post of the Day", "manage_options", "Post-of-the-Day", "potd_admin");
}

add_action('init', 'potd_init');
add_action('plugins_loaded', 'potd_check_rotate');
add_action('admin_menu', 'potd_admin_actions');
add_action('update_option_potd_categories', 'potd_rotate_post'); // When user changes the category

function potd_init() {
	if (is_admin()) {
		wp_register_script('post-of-the-day.js', POTD_PLUGIN_URL . 'post-of-the-day.js', array('jquery'));
		wp_enqueue_script('post-of-the-day.js');
	}
}

function potd_install() { 
	global $wpdb;
	
	// Create default options 
	// Get id of uncategorized
	$post_type = get_post_types('','objects');
	add_option('potd_post_types',$post_type->name);
	$category = get_category_by_slug('uncategorized');
	add_option('potd_categories', $category->term_id);
	add_option('potd_amount', '24');
	add_option('potd_interval', 'hours');
	
	$table_name = $wpdb->prefix . "postoftheday";
	
	// Create postoftheday table if not existing
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		
		$sql = "
			CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
				`curr_post_id` BIGINT(20) NOT NULL,
				`prev_post_id` BIGINT(20) NOT NULL,
				`curr_post_dts` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
			);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		// Initial insert
		$args = array(
		    'numberposts'     => 1,
		    'offset'          => 0,
		    'category'        => '',
		    'orderby'         => 'post_date',
		    'order'           => 'ASC',
		    'include'         => '',
		    'exclude'         => '',
		    'meta_key'        => '',
		    'meta_value'      => '',
		    'post_type'       => 'post',
		    'post_mime_type'  => '',
		    'post_parent'     => '',
		    'post_status'     => 'publish' 
		);
		$selected_posts = get_posts( $args );
		
		$rows_affected = $wpdb->insert( 
			$table_name, 
			array( 
				'curr_post_id' => !empty($selected_posts) ? $selected_posts[0]->ID : 0,
				'prev_post_id' => 0,	
				'curr_post_dts' => date('Y-m-d H:i:s', time())	
			) 
		);
	}
}

/**
 * 
 * Displays the post to the page
 * @return string
 */
function potd_display_post($display) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "postoftheday";
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$qry = "
			SELECT 
				`curr_post_id`
			FROM 
				`{$table_name}`
			WHERE 
				`id` = '1'";
		$potd_post = $wpdb->get_row( $qry );
		$curr_post = get_post($potd_post->curr_post_id);
		//$potd_post_content = do_shortcode($curr_post->post_content);
		
		if ( $potd_post->curr_post_id == 0 ) {
			return '<p>There are currently no posts to display here.</p>';
		} 

        if ($display == 'default') {
			return '<h2 class="potd_title">' . do_shortcode($curr_post->post_title) . '</h2>' . 
				'<div class="potd_content">' . do_shortcode($curr_post->post_content) . '</div>';
		}
		elseif ($display == 'thumbnail') {
			return '<div class="potd_thumbnail">' . get_the_post_thumbnail($curr_post->ID, 'missionary_of_the_day', array('title' => $curr_post->post_title) ) . '</div>';
		} else {
			return '<div class="potd_' . $display . '">' . do_shortcode($curr_post->$display) . '</div>';
        }
	} 
}

/**
 * 
 * Switches out the old post to a new post
 */
function potd_rotate_post() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "postoftheday";
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$qry = "
			SELECT 
				`curr_post_id`, 
				`prev_post_id`, 
				`curr_post_dts` 
			FROM 
				`{$table_name}`
			WHERE 
				`id` = '1'";
		$potd_posts = $wpdb->get_row( $qry );
		
		// Get random post, excluding current post and previous post
		$new_post_id = potd_get_random_post($potd_posts->curr_post_id, $potd_posts->prev_post_id);
	
		if ( !empty($new_post_id) ) {
			$wpdb->update( 
				$table_name, 
				array ( 
					'curr_post_id' => $new_post_id, 
					'prev_post_id' => $potd_posts->curr_post_id,
					'curr_post_dts' => date('Y-m-d H:i:s', time())
				), 
				array ( 'id' => '1' ),
				array ( '%d', '%d', '%s' ) 
			);
		}
	} 
}

/**
 * 
 * Gets a random post id using mt_rand()
 * @param int $curr_post
 * @param int $prev_post
 * @return int
 */
function potd_get_random_post($curr_post, $prev_post) {
	// Validate 
	$curr_post = intval($curr_post);
	$prev_post = intval($prev_post);
	
	$potd_post_types = get_option('potd_post_types');
	$potd_categories = get_option('potd_categories');
	$selected_posts_id_array = array();
	if ( ( !empty($potd_categories) ) OR ( !empty($potd_post_types) ) ) {
		if ((!empty($potd_categories)) AND (empty($potd_post_types))) { // categories no post_types
			foreach ($potd_categories as $potd_category) {
				$args = array(
				    'numberposts'     => 0,
				    'offset'          => 0,
				    'category'        => $potd_category,
				    'orderby'         => 'post_date',
				    'order'           => 'ASC',
				    'include'         => '',
				    'exclude'         => '',
				    'meta_key'        => '',
				    'meta_value'      => '',
				    'post_type'       => 'post',
				    'post_mime_type'  => '',
				    'post_parent'     => '',
				    'post_status'     => 'publish' 
				);
				$selected_posts = get_posts( $args );
				foreach ( $selected_posts as $selected_post ) {
					$selected_posts_id_array[] = $selected_post->ID;
				}
			} // end $potd_categories
		} // end categories no post_types
		elseif ((empty($potd_categories)) AND (!empty($potd_post_types))) { // post_types no categories
			foreach ($potd_post_types as $potd_post_type) {
				$args = array(
				    'numberposts'     => 0,
				    'offset'          => 0,
				    'category'        => '',
				    'orderby'         => 'post_date',
				    'order'           => 'ASC',
				    'include'         => '',
				    'exclude'         => '',
				    'meta_key'        => '',
				    'meta_value'      => '',
				    'post_type'       => $potd_post_type,
				    'post_mime_type'  => '',
				    'post_parent'     => '',
				    'post_status'     => 'publish' 
				);
				$selected_posts = get_posts( $args );
				foreach ( $selected_posts as $selected_post ) {
					$selected_posts_id_array[] = $selected_post->ID;
				}
			} // end $potd_categories
		} // end post_types no categories
		elseif ((!empty($potd_categories)) AND (!empty($potd_post_types))) { // post_types and categories
			foreach ($potd_post_types as $potd_post_type) {
				foreach ($potd_categories as $potd_category) {
					$args = array(
					    'numberposts'     => 0,
					    'offset'          => 0,
					    'category'        => $potd_category,
					    'orderby'         => 'post_date',
					    'order'           => 'ASC',
					    'include'         => '',
					    'exclude'         => '',
					    'meta_key'        => '',
					    'meta_value'      => '',
					    'post_type'       => $potd_post_type,
					    'post_mime_type'  => '',
					    'post_parent'     => '',
					    'post_status'     => 'publish' 
					);
					$selected_posts = get_posts( $args );
					foreach ( $selected_posts as $selected_post ) {
						$selected_posts_id_array[] = $selected_post->ID;
					}
				} // end $potd_categories
			} // end $potd_post_types
		} // end post_types and categories
		
		$eligible_posts_id_array = array(); // Posts that are not last one or current one
		foreach( $selected_posts_id_array as $selected_post_id ){
			if( $selected_post_id != $curr_post && $selected_post_id != $prev_post ){
				$eligible_posts_id_array[] = $selected_post_id;
			}
		}
		// In case we have 2 or fewer posts
		if ( empty($eligible_posts_id_array) ) {
			if ( in_array($prev_post, $selected_posts_id_array) ) { // 2 posts
				$eligible_posts_id_array[] = $prev_post;
			} else {
				if (!empty($selected_posts_id_array)) { // 1 post
					$eligible_posts_id_array[] = $selected_posts_id_array[0];
				} else {
					return 0; // no posts
				}
			}
		}
		
		$random_post_id = mt_rand(0, count($eligible_posts_id_array) - 1);
		if ( !empty($eligible_posts_id_array) ) {
			return $eligible_posts_id_array[$random_post_id];
		} 
	}
}

/**
 * 
 * Compares timestamp of now minus $interval with last ts from db
 * and calls potd_rotate_post() if $interval time has elapsed
 */
function potd_check_rotate() {
	global $wpdb;
	// Get the amount and interval options into a format that strtotime() can use
	$interval = '-' . get_option('potd_amount') . ' ' . get_option('potd_interval');
	
	$table_name = $wpdb->prefix . "postoftheday";
	
	// Compare previous timestamp with curr_post_dts timestamp
	// and if curr_post_dts is earlier than previous, call potd_rotate_post()
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$qry = "
			SELECT  
				`curr_post_dts` 
			FROM 
				`{$table_name}`
			WHERE 
				`id` = '1'";
		$curr_post = $wpdb->get_row( $qry );
		
		$previous_ts = strtotime($interval);
		$curr_post_ts = strtotime($curr_post->curr_post_dts);
		
		if( $curr_post_ts <= $previous_ts ){
			potd_rotate_post();
		} 
	}
}

/**
 * 
 * Replaces shortcode "[potd]" with random post
 * Can be called anywhere in template with 
 * <?php echo do_shortcode('[potd]'); ?>
 * @param array $atts ('default' shows the post title and content; "thumbnail" shows just the fetaured image; other items as returned in the $curr_post object)
 * @param string $content (not used at this time)
 * @param string $code (not used at this time)
 */
function potd_shortcode($atts = array(), $content=null, $code="") {
	extract(shortcode_atts(array(
		"display" => 'default'
	), $atts));

	$display = sanitize_text_field($display);

	return potd_display_post($display);
}

/**
 * 
 * Removes postoftheday table and all options when user clicks "delete" in Plugins section
 */
function potd_uninstall() {
	global $wpdb;
	$table_name = $wpdb->prefix . "postoftheday";
	
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
		$qry = "
			DROP TABLE `{$table_name}`
		";
		$wpdb->query($qry);
	}
	
	delete_option('potd_post_types');
	delete_option('potd_categories');
	delete_option('potd_amount');
	delete_option('potd_interval');
}

register_activation_hook( __FILE__, 'potd_install' );
add_shortcode( 'potd', 'potd_shortcode' );
register_uninstall_hook( __FILE__, 'potd_uninstall' );
