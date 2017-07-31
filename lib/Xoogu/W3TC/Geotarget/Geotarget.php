<?php
namespace Xoogu\W3TC\Geotarget;

class Geotarget
{
	//@param array List of countries we are serving different content to
    protected $_aCountries;
    //@param string ISO 3166 alpha-2 country code to use as default country for storing / retrieving cache items for when user is in a country not in the list of countries we are serving different content to
    protected $_sDefaultCountry;
    //@param W3_Config holding the various W3TC settings
    protected $_oW3TCConfig;
	
	
	public function __construct()
	{
		$this->_oW3TCConfig = w3tc_config();
        $aSettings = $this->_oW3TCConfig->get_array(array( 'extensions.settings', 'xoogu-geotarget-w3tc' ));
        $this->_aCountries = isset($aSettings['countries']) ? $aSettings['countries'] : array();
        $this->_sDefaultCountry = isset($aSettings['default-country']) ? $aSettings['default-country'] : 'US';
		add_filter('w3tc_page_extract_key', array($this, 'modifyKey'));
	}
	
	
	/**
	 * Gets the country code the page should be cached / retrieved for based on the user's location.
	 * Additionally sets the $_SERVER['GEOIP_COUNTRY_CODE'] variable to the same country code so that when generating a page for a user who should be served content for the default country, any plugins relying on this variable for generating their content will generate it for the correct default country, and not for the user's actual country
	 * @return string ISO 3166 alpha-2 country code the page should be cached / retrieved for
	 */
	public function filterCountryCode()
	{
		if (isset($_SERVER['GEOIP_COUNTRY_CODE'])){
			$sCountryCode = $_SERVER['GEOIP_COUNTRY_CODE'];
		} else if (function_exists('geoip_country_code_by_name')) {
			$sIP = !empty($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER['REMOTE_ADDR'];
			$sCountryCode = geoip_country_code_by_name($sIP);
		}
		if (function_exists('apply_filters')) {
			$sCountryCode = apply_filters('xoogu-geotarget-w3tc-countrycode', $sCountryCode);
		}
		return $_SERVER['GEOIP_COUNTRY_CODE'] = in_array($sCountryCode, $this->_aCountries) ? $sCountryCode : $this->_sDefaultCountry;
	}
	

	/**
	 * Modifies the page key used for storing / retrieving cached pages so that they are cached on a per country basis
	 * @param string The current page key
	 * @return string The modified page key with the user's country code added
	 */
	public function modifyKey($sKey)
	{
		$iPos = strrpos($sKey, '.');
		$sExt = substr($sKey, $iPos);
		$sKey = substr($sKey, 0, $iPos);
		if ( $sExt === '.html' || $sExt === '.html_gzip' ) {
			$sKey .= '_'.$this->filterCountryCode();
		}
		$sKey = $sKey.$sExt;
		return $sKey;
	}
}
