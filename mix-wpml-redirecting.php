<?php 
/*
Plugin Name: MIX WPML Redirecting
Version: 1.0
Description: Add-on to WPML plugin. Plugin redirects user to available site language according to user ip.
Author: Dmitriy Mikheev
*/

define('MIX_WPML_PLUGIN_PATH', dirname(__FILE__));
require_once MIX_WPML_PLUGIN_PATH.'/IP2Location-PHP-Module-master/IP2Location.php';

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

	if($records){

		//remove after
		if($records == 'IL'){
			return;
		}
		/**/
		$userCountry = getLangCodeByCountry($records);
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
	}else{
		return;
	}
}

function getLangCodeByCountry($countryCode){
	$path = MIX_WPML_PLUGIN_PATH.'/locale/locale.txt';
	$file = file_get_contents($path);
	$arr = explode("\r\n", $file);
	$count = count($arr);
	if($count === 1){
		$arr = explode("\n", $file);
	}
	$countryCode = trim($countryCode);
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
	$arr = explode("\r\n", $file);
	$count = count($arr);
	if($count === 1){
		$arr = explode("\n", $file);
	}
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
	$sessStatus = session_status();
	if($sessStatus !== 'PHP_SESSION_ACTIVE'&& $sessStatus !== 2){
		session_start();
	}
	if(isset($_SESSION['MIXuserCountry'])){
		return $_SESSION['MIXuserCountry'];
	}

	$ch = curl_init();
	$userIp = getUserHostAddress();
	curl_setopt( $ch, CURLOPT_URL, 'http://ipinfo.io/'.$userIp.'/json' );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
	$result = curl_exec( $ch );
	if(!$result){
		$country = IP2LocGetCountryByIp($userIp);
		$_SESSION['MIXuserCountry'] = $country;
		return $country;
	}
	$output = json_decode($result);
	if(!is_object($output)){
		$country = IP2LocGetCountryByIp($userIp);
		$_SESSION['MIXuserCountry'] = $country;
		return $country;
	}
	curl_close($ch);
	$_SESSION['MIXuserCountry'] = $output->country;
	return $output->country;
}

function MIXGetCountryByIp($ip){

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, 'http://ipinfo.io/'.$ip.'/json' );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
	$result = curl_exec( $ch );
	if(!$result){
		$country = IP2LocGetCountryByIp($ip);
		return $country;
	}
	$output = json_decode($result);
	if(!is_object($output)){
		$country = IP2LocGetCountryByIp($ip);
		return $country;
	}
	curl_close($ch);
	return $output->country;
}

function forbidenCountry($countryCode){
	$userCountry = MIXGetCountryByUserIp();
	if(!$userCountry){
		return;
	}
	if($countryCode !== $userCountry){
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

function getUserHostAddress(){
    if (!empty($_SERVER['HTTP_X_REAL_IP']))   //check ip from share internet
    {
        $ip=$_SERVER['HTTP_X_REAL_IP'];
    }
    elseif (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function IP2LocGetCountryByIp($ip){
	$db = new \IP2Location\Database(MIX_WPML_PLUGIN_PATH.'/IP2Location-PHP-Module-master/databases/IP2LOCATION-LITE-DB1.BIN', \IP2Location\Database::FILE_IO);

	$records = $db->lookup($ip, \IP2Location\Database::ALL);
	if(!is_array($records)||!isset($records['countryCode'])){
		return false;
	}
	return $records['countryCode'];
}

?>
