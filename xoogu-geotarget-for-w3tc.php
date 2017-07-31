<?php
/*
Plugin Name: Xoogu Geotarget for W3TC
Description: Allows caching pages separately based on the vistor's country
Version: 2017-01-01
Plugin URI: http://www.xoogu.com/geo-targeted-caching-extension-for-w3-total-cache/
Author: xoogu.com
Author URI: http://www.xoogu.com/
Network: True
*/

if ( !defined( 'ABSPATH' ) ) {
	die('No Abspath');
}

//register our extension
$sBaseDir = dirname(__FILE__);
include_once($sBaseDir.'/lib/Xoogu/W3TC/Geotarget/EnableExtension.php');
\Xoogu\W3TC\Geotarget\EnableExtension::setBaseDir($sBaseDir);
add_filter( 'w3tc_extensions', array('\Xoogu\W3TC\Geotarget\EnableExtension', 'registerExtension'), 10, 2 );
