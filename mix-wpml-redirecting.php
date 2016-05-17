<?php 
/*
Plugin Name: MIX WPML Redirecting
Version: 1.0
Description: Add-on to WPML plugin. Plugin redirects user to available site language according to user ip.
Author: Dmitriy Mikheev
Author URI: http://itb-inc.net
*/

define('MIX_WPML_PLUGIN_PATH', dirname(__FILE__));

require_once 'IP2Location-PHP-Module-master/IP2Location.php';

add_action('init', 'MIXWPMLgeoRedirect');
function MIXWPMLgeoRedirect(){
	$sessStatus = session_status();
	if($sessStatus !== 'PHP_SESSION_ACTIVE'&& $sessStatus !== 2){
		session_start();
	}
	if($_SESSION['mccvis']){
		return;
	}
	$_SESSION['mccvis'] = 1;
	global $sitepress;
	$langs = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
	$current_language = $sitepress->get_current_language();

	$records = MIXGetCountryByUserIp();
	if($records['countryCode']){
		$userCountry = getLangCodeByCountry($records['countryCode']);
		if(!$userCountry){
			return;
		}
		if($current_language == $userCountry){
			return;
		}
		foreach($langs as $lang){
			
			if($lang['code'] == $userCountry){
				$redirect = $sitepress->language_url( $lang['code'] );
				wp_redirect($redirect);
				die();
			}
		}
	}
}

function getLangCodeByCountry($countryCode){
	$path = MIX_WPML_PLUGIN_PATH.'/locale/locale.txt';
	$file = file_get_contents($path);
	$arr = explode("\n", $file);
	$output = array();
	foreach ($arr as $loc){
		$s = explode('-', $loc);
		$country = end($s);
		if($country == $countryCode){
			$lang = $s[0];
			return $lang;
		}

	}
	return false;
}

function MIXGetCountryByUserIp(){
	$databases = MIX_WPML_PLUGIN_PATH.'/IP2Location-PHP-Module-master/databases/IPV6-COUNTRY-SAMPLE.BIN';
	
	$db = new \IP2Location\Database($databases, \IP2Location\Database::FILE_IO);
	$records = $db->lookup($_SERVER['REMOTE_ADDR'], \IP2Location\Database::ALL);
	return $records;
}




?>
