<?php
namespace Xoogu\W3TC\Geotarget;

class EnableExtension {

    protected static $_sBaseDir;

    public static function setBaseDir($sBaseDir)
    {
        self::$_sBaseDir = $sBaseDir;
    }

    /**
     * Register the extension with W3TC
     * @param array $aExtensions The extensions available
     * @param W3_Config $oConfig
     * @return array Extensions available
     */
    public static function registerExtension($aExtensions, $oConfig) {
        $aEnabled = self::isEnabled($oConfig);
        $aExtensions['xoogu-geotarget-w3tc'] = array (
            'name' => __('Geo targeted caching', 'xoogu-geotarget-w3tc'),
            'author' => 'xoogu.com',
            'description' => __('Cache pages on a per country basis', 'xoogu-geotarget-w3tc'),
            'author uri' => 'http://www.xoogu.com/',
            'extension uri' => 'http://www.xoogu.com/geo-targeted-caching-extension-for-w3-total-cache/',
            'extension id' => 'xoogu-geotarget-w3tc',
            'version' => '2017-01-01',
            'enabled' => $aEnabled['bEnabled'],
            'settings_exists' => true,
            'requirements' => implode('<br />',$aEnabled['aMsg']),
            'path' => plugin_basename(self::$_sBaseDir).'/run-extension.php' //This is an empty file since we load the actual extension much earlier via advanced_cache.php
        );
        return $aExtensions;
    }
    
    
    /**
     *Detects whether the extension should be available to activate or not
     * @return array Array with two items:
     *   'bEnabled' => bool Whether the extension should be available to activate or not
     *   'aMsg' => array List of reasons why the extension is not available to activate
     */
    protected static function isEnabled($oConfig)
    {
        $bEnabled = true;
        $aMsg = array();
        if (!$oConfig->get_boolean('pgcache.enabled')) {
            $aMsg[] = __('Page cache is not enabled.', 'xoogu-geotarget-w3tc');
            $bEnabled = false;
        } elseif ($oConfig->get_string('pgcache.engine') !== 'file_generic') {
            $aMsg[] = __('Page cache method must be \'Disk: Enhanced\'.', 'xoogu-geotarget-w3tc');
            $bEnabled = false;
        }
        if (!self::detectGeoMethod()) {
            $aMsg[] =__('Requires your web server has GeoIP functionality available or the PHP GeoIP extension is installed. It does not appear that either are available.', 'xoogu-geotarget-w3tc');
            $bEnabled = false;
        }
        return array('bEnabled' => $bEnabled, 'aMsg' => $aMsg);
    }
    
    
    /**
     *Detects the geomethod available (server or php extension)
     *@return string The geomethod or an empty string if none found
     */
    public static function detectGeoMethod()
    {
        if (isset($_SERVER['GEOIP_COUNTRY_CODE'])) {
            $sGeoMethod = 'server';
        } elseif (function_exists('geoip_country_code_by_name')) {
            $sGeoMethod = 'php';
        } else {
            $sGeoMethod = '';
        }
        return $sGeoMethod;
    }
}
