<?php 
/*
Plugin Name: MIX WPML Redirecting
Version: 1.0
Description: Add-on to WPML plugin. Plugin redirects user to available site language according to user ip.
Author: Dmitriy Mikheev
Author URI: http://itb-inc.net
*/

define('MIX_WPML_PLUGIN_PATH', dirname(__FILE__));

global $mixWpmlCountryFlags;
$mixWpmlCountryFlags = require_once( MIX_WPML_PLUGIN_PATH.'/locale/flags.php');

add_action('init', 'geoRedirect');

function geoRedirect(){
	if(!class_exists('SitePress')){
		return;
	}
	global $sitepress;
	if($_SERVER['REQUEST_URI'] === '/en/'||$_SERVER['REQUEST_URI'] === '/en'){

		$redirect = $sitepress->language_url( 'en' );
		wp_redirect($redirect);
		die();

	}
	
	$sessStatus = session_status();
	if($sessStatus !== 'PHP_SESSION_ACTIVE'&& $sessStatus !== 2){
		session_start();
	}

	if($_SESSION['mccvis']){
		return;
	}

	$_SESSION['mccvis'] = 1;
	
	$langs = icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
	$current_language = $sitepress->get_current_language();

	$records = MIXGetCountryByUserIp();

	if($records['countryCode']){

		//remove after
		if($records['countryCode'] == 'IL'){
			return;
		}
		/**/
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

function getCountryByLangCode($lang){
	$path = MIX_WPML_PLUGIN_PATH.'/locale/locale.txt';
	$file = file_get_contents($path);
	$arr = explode("\n", $file);
	$output = array();
	foreach ($arr as $loc){
		$s = explode('-', $loc);

		$slang = $s[0];

		if($slang != $lang){
			continue;
		}
		foreach($s as $ccode){
			if($ccode ==$lang){
				continue;
			}
			$output[] = $ccode;
		}
	}
	return $output;
}

function MIXGetCountryByUserIp(){
	$query = unserialize(file_get_contents('http://ip-api.com/php/'.$_SERVER['REMOTE_ADDR']));
	return $query;
}

function MIXGetCountryByIp($ip){
	$query = unserialize(file_get_contents('http://ip-api.com/php/'.$ip));
	return $query;
}

function forbidenCountry($countryCode){
	$userCountry = MIXGetCountryByUserIp();
	
	if($countryCode !== $userCountry['countryCode']){
		return;
	}
	global $sitepress;
	$current_language = $sitepress->get_current_language();
	if($current_language != 'he'){
		return;
	}
	$redirect = $sitepress->language_url( 'en' );
	wp_redirect($redirect);
	die();

}

?>