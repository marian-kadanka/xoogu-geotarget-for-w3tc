<?php
namespace Xoogu\W3TC\Geotarget;

if (!defined('\W3TC')) {
    die('W3TC not defined');
}

spl_autoload_register (function($sClass)
{
    $sFilename = dirname(__FILE__).'/lib/'.str_replace('\\', '/', $sClass).'.php';
    // if the file exists, require it
    if (file_exists($sFilename)) {
        include_once $sFilename;
    }
});

if (is_admin()) {

	new Geotarget_Admin(dirname(__FILE__));

	//After activating / deactivating the extension W3TC does a redirect. So to get any error messages to show up we'll use this helper class
	\Admin_Notice_Helper::get_singleton(); // Create the instance immediately to make sure hook callbacks are registered in time

	if (!function_exists('add_notice')) {
		function add_notice($message, $type = 'update') {
			\Admin_Notice_Helper::get_singleton()->enqueue($message, $type);
		}
	}
} else {
	new Geotarget();
}
