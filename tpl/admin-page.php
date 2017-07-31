<?php
if (!defined('\W3TC')) {
    exit();
}
\W3TC\Util_Ui::postbox_header( 'Xoogu Geotarget' );

echo '<p class="description">'.__("Allows pages to be cached / retrieved on a per-country basis, using the user's IP address to determine which country they come from.",'xoogu-geotarget-w3tc').'</p>';

?><table class="form-table"><tbody><tr valign="top"><?php

//countries
$sId = $sName = 'extensions_settings_xoogu-geotarget-w3tc_countries';
//get the current value
$sValue = '';
if (isset($_POST[$sName])) {
    $sValue = $_POST[$sName];
} elseif (!empty($this->_aCountries)) {
    $sValue = implode(',', $this->_aCountries);
}
echo '<th scope="row"><label for="'.$sId.'">'.__('Countries that pages should be cached separately for','xoogu-geotarget-w3tc').'</label></th>';
echo '<td>';
//text input
echo '<input type="text" name="'.$sName.'" id="'.$sId.'" value="'.$sValue.'" autocomplete="off" />';
//drop down helper
echo '<select id="'.$sId.'-helper" autocomplete="off">';
echo '<option value="">Add a country</option>';
echo '<option value="all">Add all countries</option>';
foreach ($this->_aCountryList as $sCountryCode => $sCountryName) {
    echo '<option value="'.$sCountryCode.'">'.$sCountryName.'</option>';
}
echo '</select>';
echo '</td></tr>';


echo '<tr valign="top">';
//default country
$sId = $sName = 'extensions_settings_xoogu-geotarget-w3tc_default-country';
//get the current value
$sValue = '';
if (isset($_POST[$sName])) {
    $sValue = $_POST[$sName];
} elseif (!empty($this->_sDefaultCountry)) {
    $sValue = $this->_sDefaultCountry;
}
echo '<th scope="row"><label for="'.$sId.'">'.__('Default country','xoogu-geotarget-w3tc').'</label></th>';
//text input
echo '<td><input type="text" name="'.$sName.'" id="'.$sId.'" value="'.$sValue.'" /></td>';


//Late caching
?></tr><tr valign="top">
    <th scope="row"><label for="pgcache_late_caching"><?php _e("Late caching:", 'xoogu-geotarget-w3tc');?></label></th>
    <td>
        <input name="pgcache__late_caching" value="0" type="hidden">
        <input id="pgcache_late_caching" name="pgcache__late_caching" value="1" <?php if ($this->_oW3TCConfig->get_boolean('pgcache.late_caching')) {echo 'checked="checked"';} ?>type="checkbox">
        <p class="description"><?php _e("Late caching must be enabled to allow the cache key to be filtered on cache retrieval. You can leave it disabled if the cache key only needs modifying on cache storage (i.e. if you are using a rule at the webserver level that takes account of the user's country to retrieve from cache).", 'xoogu-geotarget-w3tc');?></p>
    </td>
</tr></tbody></table><?php



\W3TC\Util_Ui::button_config_save('extension_xoogu-geotarget-w3tc');

\W3TC\Util_Ui::postbox_footer();
