<?php
/*
Plugin Name: Easy Contact Forms Export
Plugin URI: http://www.1stdomains.co.uk/
Description: Provides functionality to export Easy Contact Forms into CSV format. NOTE: This pluggin requires: Easy Contact Forms
Version: 1.0.3
Author: Keith Jasper (1stDNS Limited)
Author URI: http://www.1stdomains.co.uk/
License: GPLv2

 
Copyright 2011  Keith Jasper  (email : hostmaster@1stdomains.co.uk)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Create Initial Function Call
add_action('admin_menu', 'Load_Plugin_ECF_Exporter');

// Add our stylesheet to the admin template.
add_action('admin_head', 'admin_register_head');

function Load_Plugin_ECF_Exporter() {
	global $wpdb;
	add_menu_page('Easy Contact Forms Exporter', 'ECF-Exporter', 'manage_options', 'easy-contact-forms-exporter', 'ecfe_main');
	add_submenu_page('easy-contact-forms-exporter', 'Download', 'ECF-Download', 'manage_options', 'easy-contact-forms-exporter-download', 'do_export');
}

function ecfe_main() {	
	global $wpdb;
	echo '<div class="wrap">';
	echo '<h2>Easy Contact Forms Exporter</h2>';
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$forms = $wpdb->get_results('SELECT id, description FROM ' . $wpdb->prefix . 'easycontactforms_customforms');
	echo '<form method="post" action="?page=easy-contact-forms-exporter-download">';
	if (isset($_POST)) {
		perform_query((int)$_POST['formid']);
	}
	echo '<select name="formid">';
	foreach ($forms as $form) {
		echo '<option value="' . $form->id . '">' . $form->description . '</option>';
	}
	echo '</select>';
	echo '<input type="submit">';
	echo '</div>';
	echo '<h5>Plugin Provided by <a href="http://www.1stdomains.co.uk">1stDNS Limited</a></h5>';
}

function perform_query($formid) {
	global $wpdb;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$results = $wpdb->get_results('SELECT date, content FROM ' . $wpdb->prefix . 'easycontactforms_customformsentries WHERE CustomForms = ' . (int)$formid);
	//var_dump($results);
	return $results;
}

function getrowHeaders($formid) {
	global $wpdb;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$results = $wpdb->get_results("SELECT Description FROM " . $wpdb->prefix . "easycontactforms_customformfields WHERE CustomForms=" . $formid . " AND Desctiption != 'Fieldset' ORDER BY ListPosition ASC");
	return $results;
}

function admin_register_head() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$siteurl = get_option('siteurl');
	$url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/style/exporter.css';
	echo '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
}

function do_export() {
	global $wpdb;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	if (!isset($_POST['formid'])) {
			echo '<div class="wrap">';
			echo '<h2>ECF Download</h2>';
			echo '<div class="updated">';
			echo '<p><strong>You have not selected what you wish to export!</strong></p>';
			echo '</div>';
	} else {
		$results = perform_query((int)$_POST['formid']);
		$outarray = array();
		foreach ($results as $row) {
			//var_dump($row->content);
			$dom = new DOMDocument();
			$dom->loadHTML($row->content);
			$domx = new DOMXPath($dom);
			$entries = $domx->evaluate("//h1");
			$arr = array();
			foreach ($entries as $entry) {
				$arr[] = '<' . $entry->tagName . '>' . $entry->nodeValue .  '</' . $entry->tagName . '>';
			}
			$count = count($arr);
			$i = 0;
			$lines = array();
			foreach ($arr as $string) {
				if ($i == $count - 1) {
					$end = ";\n";
				} else {
					$end = ",";
				}
				$outarray[] = '"' . strip_tags(htmlspecialchars_decode($string)) . '"' . $end;
				$i++;
			}
			$lines[] = implode('', $outarray);
		}
		$tmpdir = sys_get_temp_dir();
		$temp_file = md5(rand());
		$fh = fopen($tmpdir . '/' . $temp_file, 'w');
		//var_dump($temp_file, $tmpdir);
		
		foreach ($lines as $line) {
			echo '<pre>';
			
			//var_dump($line);
			echo '</pre>';
			fwrite($fh, $line);
		}
		fclose($fh);
		//var_dump(file_get_contents($tmpdir . '/' . $temp_file));
		echo '<div id="wrap">';
		echo '<h1>Download your results</h1>';
		echo '<div class="updated">';
		echo '<h2>Link below</h2>';
		echo '<p>Your file is available to download <a href="/wp-content/plugins/' . basename(dirname(__FILE__)) . '/downloadcsv.php?file=' . $temp_file . '">here</a></p>';
		echo '</div>';
		echo '</div>';
	}
}
