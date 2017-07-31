<?php
namespace Xoogu\W3TC\Geotarget;

class Geotarget_Admin extends Geotarget {
    
    //@param array List of country codes and names
	protected $_aCountryList;
	//@param array List of errors
	protected $_aErrors;
	//@param array List of potential error messages
	protected $_aErrorMsgs;
	//@param string The path to the base directory of this plugin
	protected $_sBaseDir;

	public function __construct($sBaseDir)
	{
		parent::__construct();
		$this->_sBaseDir = $sBaseDir;

        load_plugin_textdomain('xoogu-geotarget-w3tc', false, $this->_sBaseDir.'/lang/');

        //settings page
		add_action( 'admin_init_w3tc_extensions', array($this, 'adminInit'));
    
        //Saving settings from admin page
        add_action( 'w3tc_config_ui_save-w3tc_extensions', array($this, 'saveExtensionSettings'), 10, 2 );

		//W3TC doesn't seem to actually apply the sanitization filters when saving the extension settings, so have to do it manually
        add_filter("w3tc_save_extension_settings-xoogu-geotarget-w3tc", array($this, 'save_extension_settings'), null, 2);

        //Add activation handler to setup initial extension settings
        add_action('w3tc_activate_extension_xoogu-geotarget-w3tc', array($this, 'activate'));
        //add_action('w3tc_deactivate_extension_xoogu-geotarget-w3tc', array($this, 'deleteExtensionSettings'));
        //Currently W3TC does not implement an extension activation hook, so have to look at URL to determine if extension has just been activated, then run activation based on that
        if (!empty($_GET['activated'])) {
            //have to check the url rather than using GET as W3TC includes multiple activated GET params (not indexed) when you bulk activate extensions
            if (strpos($_SERVER['REQUEST_URI'], 'activated=xoogu-geotarget-w3tc')) {
                do_action('w3tc_activate_extension_xoogu-geotarget-w3tc');
            }
        }

        //when cache for a post is cleared, clear all our geo-keyed versions of the post from the cache
		add_filter('w3tc_pagecache_flush_url_keys', array($this, 'flushURL'));

    }




  	/**
	 * Hook into the w3tc_pagecache_flush_url_keys action, so when the cache is cleared we can clear all country variations
	 * @param array $aPageKeys Page keys to remove cached items of
	 */ 
	public function flushURL($aPageKeys)
	{
		$aReturnPageKeys = array();
		$aCountries = $this->_aCountries;
		if (!in_array($this->_sDefaultCountry, $aCountries)) {
			$aCountries[] = $this->_sDefaultCountry;
		}
		foreach ($aPageKeys as $sPageKey) {
			$aReturnPageKeys[] = $sPageKey;
			foreach($aCountries as $sCountryCode) {
				$this->_flushCountryCode = $sCountryCode;
				$sNewPageKey = $this->modifyKey($sPageKey);
				if ($sNewPageKey != $sPageKey) {
					$aReturnPageKeys[] = $sNewPageKey;
				}
			}
		}
		$this->_flushCountryCode = null;
		return $aReturnPageKeys;
	}

	/**
	 * Override the filterCountryCode method from Geotarget class so that the generated page key will use the countrycode we currently want to flush the cache page for
	 * @return string The countrycode to flush the cache for (or the user's countrycode if we're not currently flushing the cache)
	 */
	public function filterCountryCode()
	{
		return $this->_flushCountryCode ? $this->_flushCountryCode : parent::filterCountryCode();
	}

    
    /**
	 *Initializes the settings for the admin page
	 */
	public function adminInit()
    {
		if ($_GET['extension'] != 'xoogu-geotarget-w3tc') {
			return;
		}
		//get the list of all country codes and names
        include(dirname(__FILE__).'/countrylist.php');
		$this->_aCountryList = $aCountryList;
		
		//potential error messages
		$this->_aErrorMsgs = array(
			'noCountries' => __('You must choose at least two countries that will see different content', 'xoogu-geotarget-w3tc'),
			'countryCodeInvalid' => __('%s is not a valid country code', 'xoogu-geotarget-w3tc'),
			'noDefaultCountry' => __('You must choose a default country', 'xoogu-geotarget-w3tc')
		);
		
		//get the extension settings
        /*if (!$this->_aCountries) {
        	$this->_aCountries = array_keys($this->_aCountryList);
        }*/
		
		//If there was an error message, then remove W3TC's automatically added note that the settings were updated (quite hacky)
		if (get_transient( 'anh_notices' )) {
			add_filter('w3tc_notes', array($this, 'removeSavedNoteOnError'));
		}

		wp_enqueue_style('xoogu_geotext_admin', plugins_url( 'admin.css' , $this->_sBaseDir.'/admin.css' ));
        
        wp_enqueue_script('xoogu-geotarget-w3tc-admin', plugins_url('admin.js', $this->_sBaseDir.'/admin.js'), null, null, true);
        wp_localize_script('xoogu-geotarget-w3tc-admin', 'xgtL10n', $this->_aErrorMsgs);

		add_action('w3tc_extension_page_xoogu-geotarget-w3tc', array($this, 'displayPage'));
	}
	
	
	/**
	 *Calls the functions for showing the settings form on the extension admin page
	 */
	public function displayPage()
	{
		include($this->_sBaseDir.'/tpl/admin-page.php');
    }
	
	
	/**
	 *Validates a new countries setting. Returns the new setting as an array of country codes if it was valid, otherwise returns the old setting
	 *@param string $sValue List of comma separated country codes to save as the value
	 *@return array Array of country codes to save as the value
	 */
	public function sanitize_countries($sValue)
	{
		//ensure country codes are uppercase
		$sValue = strtoupper(trim($sValue));
		//check the text field wasn't empty
		if (empty($sValue)) {
			$this->_aErrors['noCountries'] = $this->_aErrorMsgs['noCountries'];
			return $this->_aCountries;
		} else {
			$aValue = explode(',', $sValue);
			//holds any invalid country codes
			$aCountryCodeInvalid = array();
			//holds our list of country codes to save
			$aValidCountries = array();
			//loop through the country codes and check each one
			foreach ($aValue as $sCountryCode) {
				$sCountryCode = trim($sCountryCode);
				if (empty($sCountryCode)) {
					continue;
				}
				//check country code is valid
				if (empty($this->_aCountryList[$sCountryCode])) {
					$aCountryCodeInvalid[] = sprintf($this->_aErrorMsgs['countryCodeInvalid'], $sCountryCode);
				} else {
					//use $sCountryCode as key to ensure no country code added more than once
					$aValidCountries[$sCountryCode] = $sCountryCode;
				}
			}
			//check all country codes entered were valid
			if (!empty($aCountryCodeInvalid)) {
				$this->_aErrors['countryCodeInvalid'] = $aCountryCodeInvalid;
				return $this->_aCountries;
			}
			//check we have at least two country codes to save as the setting value
			if (count($aValidCountries) < 2) {
				$this->_aErrors['noCountries'] = $this->_aErrorMsgs['noCountries'];
				return $this->_aCountries;
			}
			//If everything was OK (no errors at all), then return our list of country codes to save as the setting value
			return $aValidCountries;
		}
	}
	
	
	/**
	 *Sanitizes a new default country setting. Returns the new setting if valid, otherwise returns the old setting.
	 *@param string $sValue The new default country setting to save (2 digit country code)
	 *@return string The default country setting to save
	 */
	public function sanitize_defaultCountry($sValue)
	{
		//make sure country code is uppercase
		$sValue = strtoupper(trim($sValue));
		//check a value was actually provided
		if (empty($sValue)) {
			$this->_aErrors['noDefaultCountry'] = $this->_aErrorMsgs['noDefaultCountry'];
			return $this->_sDefaultCountry;
		//check it's a valid country code
		} else if (empty($this->_aCountryList[$sValue])) {
			$this->_aErrors['defaultCountryCodeInvalid'] = sprintf($this->_aErrorMsgs['countryCodeInvalid'], $sValue);
			return $this->_sDefaultCountry;
		} else {
			return $sValue;
		}
	}
	
	
	/**
	 *Removes the 'Plugin configuration successfully updated' message from the list of notes to be printed
	 *(Call when there is an error saving the plugin settings)
	 *@param array $notes Array of notes to print
	 *@return array Array of notes to print
	 */
	public function removeSavedNoteOnError($notes)
	{
		//Notes is not an associative array, so we need to try and check each value to see if it is the updated message
		//TotalCacheAdmin.php ln 508
		if ( ($key = array_search(__('Plugin configuration successfully updated.', 'w3-total-cache'), $notes)) !== false) {
			unset($notes[$key]);
		}
		return $notes;
	}
	
	
	/**
	 *W3TC doesn't seem to actually apply the settings sanitization specified when registering the settings, so do it manually by hooking into the w3tc_save_extension_settings-xoogu-geotarget-w3tc filter
	 *@param W3_Config $extension_settings the new settings to be saved
	 *@param W3_Config $extension_settings the old settings
	 *@return W3_Config The sanitized new extension settings to save
	 */
	public function saveExtensionSettings($oConfig, $oOldConfig)
	{
		if ($_GET['extension'] != 'xoogu-geotarget-w3tc') {
			return;
		}
        //get the existing settings for all extensions
        $aExtensionsData = $oConfig->get_array('extensions.settings', array());
		$aExtensionsData["xoogu-geotarget-w3tc"]['default-country'] = $this->sanitize_defaultCountry($aExtensionsData["xoogu-geotarget-w3tc"]['default-country']);
		$aExtensionsData["xoogu-geotarget-w3tc"]['countries'] = $this->sanitize_countries($aExtensionsData["xoogu-geotarget-w3tc"]['countries']);
		$oConfig->set('extensions.settings', $aExtensionsData);
		//$oConfig->set('pgcache.late_caching', true);
		if (!empty($this->_aErrors)) {
			add_notice( $this->printErrors($this->_aErrors), 'error' );
		}
		return $oConfig;
	}
	
	
	/**
	 *Saves the default settings for the extension
	 */
	public function activate()
	{
		$aExtensionsData = $this->_oW3TCConfig->get_array('extensions.settings', array());
		//check the setting doesn't already exist
		if (empty($aExtensionsData['xoogu-geotarget-w3tc'])) {
			//add the settings
			$aExtensionsData['xoogu-geotarget-w3tc'] = array(
				'default-country' => $this->_sDefaultCountry,
				'countries' => $this->_aCountries
			);
			$this->_oW3TCConfig->set('extensions.settings', $aExtensionsData);
			$this->_oW3TCConfig->set('pgcache.late_caching', true);
			$this->_oW3TCConfig->save();
		}
	}
	
	
	/**
	 *Deletes the extension settings
	 *(Call manually to clear the settings when testing)
	 */
	public function deleteExtensionSettings()
	{
		$aExtensionsData = $this->_oW3TCConfig->get_array('extensions.settings', array());
		//check the setting isn't already empty
		if (!empty($aExtensionsData['xoogu-geotarget-w3tc'])) {
			//remove the setting
			unset($aExtensionsData['xoogu-geotarget-w3tc']);
			$this->_oW3TCConfig->set('extensions.settings', $aExtensionsData);
			$this->_oW3TCConfig->save();
		}
	}
	
	
	/**
	 *Creates an HTML unordered list filled with error messages
	 *@param array $aErrors An associative array with error messages as the values, or further arrays containing error messages as the values
	 *@return string HTML unordered list containing all the error messages
	 */
	public function printErrors($aErrors)
	{
		$sErrStr = '<ul>';
		foreach ($aErrors as $mError) {
			if (is_array($mError)) {
				$sErrStr .= '<li>'.$this->printErrors($mError).'</li>';
			} else {
				$sErrStr .= '<li>'.$mError.'</li>';
			}
		}
		return $sErrStr.'</ul>';
	}
}
