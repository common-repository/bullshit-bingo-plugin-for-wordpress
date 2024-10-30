<?php
/*
Plugin Name: Bullshit Bingo Plugin for WordPress
Plugin URI: http://wordpress.org/extend/plugins/bullshit-bingo-plugin-for-wordpress/
Description: Displays a Bullshit Bingo (also known as Buzzword Bingo) card in any of your posts or pages using a shortcode.
Version: 0.3 (alpha)
Author: Lars C. Bernstein
Author URI: http://wordpress.org/extend/plugins/profile/semper-tiro
*/
/*  Copyright 2009  Lars C. Bernstein  (email : info@semper-ti.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('BULLSHITBINGO_DB_VERSION', 0.3, false);
define('BULLSHITBINGO_DB_TABLE', 'bullshitbingo_buzzwords', false);
define('BULLSHITBINGO_SHORTCODE', 'bullshitbingo', false);
define('BULLSHITBINGO_ROWS', 5, false);
define('BULLSHITBINGO_COLUMNS', 5, false);

include_once(dirname(__FILE__) . '/bullshitbingo-adminmenu.php');

class BullshitBingo {
	var $admin_menu = null;

	function BullshitBingo() {
		add_action('init', array($this, 'i18n'), 10, 0);
		
		if (function_exists('add_shortcode')) {
			add_shortcode(BULLSHITBINGO_SHORTCODE,
				array($this, 'shortcode_handler'));
		}

		if (class_exists('BullshitBingoAdminMenu'))
		{
			$this->admin_menu = new BullshitBingoAdminMenu();
		}
	}

	function create_db_table() {
		global $wpdb;

		$installed_version = get_option('bullshitbingo_db_version');

		$table_name = $wpdb->prefix . BULLSHITBINGO_DB_TABLE;		
		if (($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
		|| ($installed_version != BULLSHITBINGO_DB_VERSION)){
			$sql = "CREATE TABLE " . $table_name . " (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
				tag varchar(32) NOT NULL,
				buzzword varchar(32) NOT NULL,
				url varchar(128) DEFAULT NULL,
				active tinyint(1) NOT NULL DEFAULT 0,
				inserted datetime NOT NULL,
				updated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (ID),
				UNIQUE KEY buzzword (tag, buzzword)
				);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			if (false === get_option('bullshitbingo_db_version')) {
				add_option('bullshitbingo_db_version', BULLSHITBINGO_DB_VERSION,
					'', 'no');
			} else {
				update_option('bullshitbingo_db_version',
					BULLSHITBINGO_DB_VERSION);
			}
		}
	}

	function i18n()
	{
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain('bullshitbingo',
			'wp-content/plugins/' . $plugin_dir, $plugin_dir);
	}

	function shortcode_handler($atts, $content = null) {
		if (is_null($content)) {
			$content = '';
		}
		extract(shortcode_atts(array(
			'tag' => '',
			'rows' => BULLSHITBINGO_ROWS,
			'columns' => BULLSHITBINGO_COLUMNS
			), $atts));
		
		return $this->create_card($tag, $rows, $columns);
	}
	
	function create_card($tag, $rows, $columns) {
		global $wpdb;
		
		// First check whether there is enough relevant buzzwords in the
		// database
		$query = "SELECT COUNT(buzzword) FROM " . $wpdb->prefix
			. BULLSHITBINGO_DB_TABLE . " WHERE active > 0";
		if (strlen($tag) > 0) {
			$query .= " AND LOWER(tag) IN ('" 
				. implode("','", explode(',', strtolower($tag))) . "')";
		}
		// If there is not enough buzzwords, we just exit gracefully
		if ($wpdb->get_var($query) < ($rows * $columns)) {
			return '';
		}
		// Select the appropriate number of buzzwords from the database
		// Buzzwords are weighed by their age and a random factor
		$query = "SELECT buzzword, url FROM " . $wpdb->prefix
			. BULLSHITBINGO_DB_TABLE . " WHERE active > 0";
		if (strlen($tag) > 0) {
			$query .= " AND LOWER(tag) IN ('" 
				. implode("','", explode(',', strtolower($tag))) . "')";
		}
		$query .= " ORDER BY UNIX_TIMESTAMP(updated) * RAND(UNIX_TIMESTAMP()) "
			. "DESC LIMIT " . $rows * $columns;
		$result = $wpdb->get_results($query, OBJECT);
		// Build the table
		$content = "\n";
		$content .= '<!-- Start Bullshit Bingo Plugin for WordPress Output -->'
			. "\n";
		$content .= '<table border="1" rules="all" style="width:100%; '
			. 'font-size:100%;>' . "\n";
		$content .= '<colgroup>' . "\n";
		for ($i = 0; $i < $columns; $i++) {
			$content .= '<col width="' . (100 / $columns) .'%">';
		}
		$content .= "\n";
		for ($row = 0; $row < $rows; $row++) {
			$content .= '<tr style="height:4em; vertical-align:center;">'
				. "\n";
			for ($column = 0; $column < $columns; $column++) {
				// Determine the longest part of a buzzword
				$buzzword = $result[$row * $columns + $column]->buzzword;
				$url = $result[$row * $columns + $column]->url;
				$words = explode(' ', $buzzword);
				$length = 0;
				foreach ($words as $word) {
					if (strlen($word) > $length) {
						$length = strlen($word);
					}
				}				
				$font_size = 100 - (int)((int)($length / 8) * 15);
				$content .= '<td style="text-align:center; font-size:'
					. $font_size . '%">';
				if (strlen($url) > 0) {
					$content .= '<a href="' . $url . '" target="_blank">';
				}	
				$content .= $buzzword;
				if (strlen($url) > 0) {
					$content .= '</a>';
				}	
				$content .= '</td>' . "\n";
			}
			$content .= '</tr>' . "\n";
		}
		$content .= '</colgroup>' . "\n";
		$content .= '</table>' . "\n";
		$content .= '<!-- End Bullshit Bingo Plugin for WordPress Output -->'
			. "\n";
		return $content;
	}
}

if (function_exists('register_activation_hook')) {
	register_activation_hook(__FILE__,
		array('BullshitBingo', 'create_db_table'));
}

if (function_exists('add_action')) {
	add_action('plugins_loaded', create_function('$dummy',
		'global $BullshitBingo; $BullshitBingo = new BullshitBingo();'));
}
?>