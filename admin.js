(function ()
{
	"use strict";
	var dSelect = document.getElementById('extensions_settings_xoogu-geotarget-w3tc_countries-helper'),
		dInput = document.getElementById('extensions_settings_xoogu-geotarget-w3tc_countries'),
		dDefaultCountry = document.getElementById('extensions_settings_xoogu-geotarget-w3tc_default-country');
	dSelect.addEventListener('change', selectChangeHandler);
	document.getElementById('w3tc_extensions').addEventListener('submit', validateForm);
	
	function selectChangeHandler(eEvent)
	{
		var sVal = this[this.selectedIndex].value;
		if (!sVal) {
			return;
		}
		if (sVal == 'all') {
			var i=2, dOption, aCountries=[];
			for (; (dOption = this[i]); i++) {
				aCountries.push(dOption.value);
			}
			dInput.value = aCountries.join(',');
		} else {
			var sInputVal = dInput.value;
			if (!sInputVal || /,\s*$/.test(sInputVal)) {
				dInput.value = sInputVal + sVal;
			} else {
				dInput.value = sInputVal + ',' + sVal;
			}
		}
		dInput.scrollLeft = dInput.scrollWidth; 
		
	}
	
	function validateForm(eEvent)
	{
		//We need Object.keys to check if our error object is empty
		//If browser doesn't support it, then let form submit normally and PHP handle errors
		if (!Object.keys) {
			return;
		}
		
		
		//remove any existing success or error messages
		var dMsgDivs = document.querySelectorAll('div.error, div.updated'),
			dMsgDiv,
			i=0;
		for (;dMsgDiv=dMsgDivs[i];i++) {
			dMsgDiv.parentNode.removeChild(dMsgDiv);
		}
		//remove error classes from form items
		dDefaultCountry.className = dInput.className = '';
		
		
		//countries
		var sInputVal = dInput.value,
		oErrors = {};
		//check not empty
		if (!sInputVal) {
			oErrors.sNoCountries = xgtL10n.noCountries;
			dInput.className = 'error';
		} else {
			var aSelectedCountries = sInputVal.split(','),
				i,
				sSelectedCountry,
				aValidCountries = [],
				aCountryList = [],
				dOption;
			oErrors.aCountryCodeInvalid = [];
			//generate the country list
			for (i=2; (dOption = dSelect[i]); i++) {
				aCountryList.push(dOption.value);
			}
			//loop through the chosen countries
			for (i=0; typeof aSelectedCountries[i] == 'string'; i++) {
				sSelectedCountry = aSelectedCountries[i].trim().toUpperCase();
				//check not empty
				if (!sSelectedCountry) {
					continue;
				}
				//check a valid country code
				if ( sSelectedCountry.length !== 2 || aCountryList.indexOf(sSelectedCountry) === -1 ) {
					oErrors.aCountryCodeInvalid.push(xgtL10n.countryCodeInvalid.replace('%s', sSelectedCountry));
					dInput.className = 'error';
				} else {
					aValidCountries.push(sSelectedCountry);
				}
			}
			//check we have at least 2 valid country codes
			if (aValidCountries.length < 2) {
				oErrors.sNoCountries = xgtL10n.noCountries;
				dInput.className = 'error';
			}
			//If there weren't any invalid country codes, we can remove the oErrors.aCountryCodeInvalid property
			if (oErrors.aCountryCodeInvalid.length === 0) {
				delete oErrors.aCountryCodeInvalid;
			}
		}
		
		
		//default country
		var sDefaultCountry = dDefaultCountry.value.trim().toUpperCase();
		if (!sDefaultCountry.length) {
			oErrors.noDefaultCountry = xgtL10n.noDefaultCountry;
			dDefaultCountry.className = 'error';
		} else if ( sDefaultCountry.length !== 2 || aCountryList.indexOf(sDefaultCountry) === -1 ) {
			oErrors.defaultCountryCodeInvalid = xgtL10n.countryCodeInvalid.replace('%s', sDefaultCountry);
			dDefaultCountry.className = 'error';
		}
		
		
		//submit form if no errors, otherwise display error messages
		if (Object.keys(oErrors).length) {
			//show errors on screen
			var dErrorContainer = document.createElement('div');
			dErrorContainer.className = 'error';
			dErrorContainer.appendChild(printErrors(oErrors));
			var dContainer = document.getElementById('w3tc');
			dContainer.insertBefore(dErrorContainer, dContainer.querySelector('h2').nextSibling);
			
			//don't submit the form
			eEvent.preventDefault();
			eEvent.returnValue = false;
			return false;
		}
	}
	
	
	/**
	 *Creates an HTML unordered list filled with error messages
	 *@param object oErrors An object with error messages as the property values, or further objects / arrays containing error messages as the property values
	 *@return DOMElement HTML unordered list containing all the error messages
	 */
	function printErrors(oErrors)
	{
		var dContainer = document.createElement('ul');
		var i, msg, dLi;
		for (i in oErrors) {
			msg = oErrors[i];
			dLi = document.createElement('li');
			if (typeof msg == 'object') {
				dLi.appendChild(printErrors(msg))
			} else {
				dLi.appendChild(document.createTextNode(msg));
			}
			dContainer.appendChild(dLi);
		}
		return dContainer;
	}
})();
