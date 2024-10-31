<?php
/*
	Plugin Name: Primary Feedburner
	Plugin URI: http://wordpress.org/plugins/primary-feedburner/
	Description: Redirect your website feeds to feedburner.
	Version: 3.1.3
	Author: iThemes
	Author URI: http://ithemes.com
	License: GPLv2
	Copyright 2014  iThemes  (email : updates@ithemes.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU tweaks Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU tweaks Public License for more details.

	You should have received a copy of the GNU tweaks Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Adds the admin menu pages
 * @return null 
 */
function pf_menu() {
	//Add main menu page
	add_submenu_page('options-general.php', __('Primary Feedburner Options'), __('Primary Feedburner'), 'manage_options', 'primary_feedburner', 'pf_options');
}

/**
 * Define the options page
 * @return null 
 */
function pf_options() {
	include(trailingslashit(WP_PLUGIN_DIR) . 'primary-feedburner/options.php');
}

add_action('admin_menu',  'pf_menu');

/**
 * Check $path and return whether it is writable
 * @return Boolean
 * @param String file 
 */	
function pf_canwrite($path) {		 
	if ($path{strlen($path)-1} == '/') { //if we have a dir with a trailing slash
		return pf_canwrite($path.uniqid(mt_rand()).'.tmp');
	} elseif (is_dir($path)) { //now make sure we have a directory
		return pf_canwrite($path.'/'.uniqid(mt_rand()).'.tmp');
	}

	$rm = file_exists($path);
	$f = @fopen($path, 'a');
	
	if ($f===false) { //if we can't open the file
		return false;
	}
	
	fclose($f);
	
	if (!$rm) { //make sure to delete any temp files
		unlink($path);
	}
	
	return true; //return true
}

/**
 * Remove a given section of code from .htaccess
 * @return Boolean 
 * @param String
 * @param String
 */
function pf_remove($filename, $marker) {
	if (!file_exists($filename) || pf_canwrite($filename)) { //make sure the file is valid and writable

		$markerdata = explode("\n", implode( '', file( $filename))); //parse each line of file into array

		$f = fopen($filename, 'w'); //open the file
		
		if ($markerdata) { //as long as there are lines in the file
			$state = true;
			
			foreach ($markerdata as $n => $markerline) { //for each line in the file
			
				if (strpos($markerline, '# BEGIN ' . $marker) !== false) { //if we're at the beginning of the section
					$state = false;
				}
				
				if ($state == true) { //as long as we're not in the section keep writing
					if ($n + 1 < count($markerdata)) //make sure to add newline to appropriate lines
						fwrite($f, "{$markerline}\n");
					else
						fwrite($f, "{$markerline}");
				}
				
				if (strpos($markerline, '# END ' . $marker) !== false) { //see if we're at the end of the section
					$state = true;
				}
			}
		}
		return true;
	} else {
		return false; //return false if we can't write the file
	}
}

add_filter('feed_link', 'pf_feed_filter', 10, 2);
function pf_feed_filter( $output, $feed ) {
	
	$feed_uri = get_option('pf_feed1');
	$comments_feed_uri = get_option('pf_feed2');
	$active = get_option('pf_enable');
	
	if ($active == 1) {
		if ( $feed_uri && !strpos($output, 'comments') ) {
			$output = esc_url( 'http://feeds.feedburner.com/' . $feed_uri );
		}
	
		if ( $comments_feed_uri && strpos($output, 'comments') ) {
			$output = esc_url( 'http://feeds.feedburner.com/' . $comments_feed_uri );
		}
	}
	
	return $output;
	
}
