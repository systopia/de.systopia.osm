<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM OSM Geocoding module (SYS-OSM)                             |
  +--------------------------------------------------------------------+
  | Copyright SYSTOPIA (c) 2014-2015                                   |
  +--------------------------------------------------------------------+
  | This is free software; you can copy, modify, and distribute it     |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | SYS-OSM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the SYS-OSM Licensing Exception along                  |
  | with this program; if not, contact SYSTOPIA                        |
  | at info[AT]systopia[DOT]de. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright SYSTOPIA (c) 2014-2015
 *
 */

/**
 * Class that uses OpenStreetMap (OSM) API to retrieve the lat/long of an address
 *
 * This CiviCRM extension requests geodata from nominatim.osm.org, 
 * a service of OpenStreetMap Foundation. We have been advised that OSM servers 
 * might not be suitable for massive data requests, but so far could not 
 * get precise information on what this exactly means. Also, we don't know what 
 * the consequences of requests too massive for the OSM infrastructure might be. 
 * Therefore we recommend that you consider using the 'throtte' option for the 
 * "Geocode and Parse Adresses" cronjob if processing data sets containing more 
 * than 10.000 addresses.
 */
class CRM_Utils_Geocode_OpenStreetMapCoding {

  /**
   * OSM Nominatim server
   *
   * @var string
   * @static
   */
  static protected $_server = 'nominatim.openstreetmap.org';

  /**
   * uri of service
   *
   * @var string
   * @static
   */
  static protected $_uri = '/search';

  /**
   * function that takes an address array and gets the latitude / longitude
   * and postal code for this address. Note that at a later stage, we could
   * make this function also clean up the address into a more valid format
   *
   * @param array $values associative array of address data: country, street_address, city, state_province, postal code
   * @param boolean $stateName this params currently has no function
   *
   * @return boolean true if we modified the address, false otherwise
   * @static
   */
  static function format(&$values, $stateName = FALSE) {
    CRM_Utils_System::checkPHPVersion(5, TRUE);

    $config = CRM_Core_Config::singleton();

    $params = array();

    // TODO: is there a more failsafe format for street and street-number?
    // Ryver #HELP-284: Strip out all characters prior to a street number (for example the unit number or level)
    // before passing to Open Street Map, as OSM has difficulty returning the correct results with this information.
    if (CRM_Utils_Array::value('street_address', $values)) {
      $params['street'] = preg_replace("/^.*[^0-9]([0-9]+[^0-9]*)$/", "$1", $values['street_address']);
    }

    if ($city = CRM_Utils_Array::value('city', $values)) {
      $params['city'] = $city;
    }

    if (CRM_Utils_Array::value('state_province', $values)) {
      if (CRM_Utils_Array::value('state_province_id', $values)) {
        $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince', $values['state_province_id']);
      }
      else {
        if (!$stateName) {
          $stateProvince = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
            $values['state_province'],
            'name',
            'abbreviation'
          );
        }
        else {
          $stateProvince = $values['state_province'];
        }
      }

      // TODO: do we need this? This originated from CRM-2632 / Google geocoder
      if ($stateProvince != $city) {
        $params['state'] = $stateProvince;
      }
    }

    if (CRM_Utils_Array::value('postal_code', $values)) {
      $params['postalcode'] = $values['postal_code'];
    }

    if (CRM_Utils_Array::value('country', $values)) {
      $params['country'] = $values['country'];
    }

    // There should be at least a city or postal_code, a street and a country
    if (!(array_key_exists('street', $params)
        && array_key_exists('country', $params)
        && (array_key_exists('city', $params) || array_key_exists('postalcode', $params)))) {
      // the error logging is disabled, because it potentially produces a lot of log messages
      //CRM_Core_Error::debug_log_message('Geocoding failed. Address data is incomplete.');
      $values['geo_code_1'] = $values['geo_code_2'] = 'null';
      return FALSE;
    }

    $params['addressdetails'] = '1';
    $url = "https://" . self::$_server . self::$_uri;
    $url .= '?format=json';
    foreach ($params as $key => $value) {
      $url .= '&' . urlencode($key) . '=' . urlencode($value);
    }

    $result = $request = null;
    try {
      require_once 'HTTP/Request.php';
      $request = new HTTP_Request($url);
      $result = $request->sendRequest();
    } catch(Exception $e) {
      // this could happen if there is a connection error or the server shut you out
      //  see: https://github.com/systopia/de.systopia.osm/issues/8
      CRM_Core_Error::debug_log_message('Geocoding failed: ' . $e->getMessage());
      return FALSE;
    }

    // check if request was successful
    if (PEAR::isError($result)) {
      CRM_Core_Error::debug_log_message('Geocoding failed: ' . $result->getMessage());
      return FALSE;
    }
    if ($request->getResponseCode() != 200) {
      CRM_Core_Error::debug_log_message('Geocoding failed, invalid response code ' . $request->getResponseCode());
      if ($request->getResponseCode() == 429) {
        // provider says 'TOO MANY REQUESTS'
        $values['geo_code_error'] = 'OVER_QUERY_LIMIT';
      } else {
        $values['geo_code_error'] = $request->getResponseCode();
      }
      return FALSE; 
    }

    // process results
    $string = $request->getResponseBody();
    $json = json_decode($string);

    if (is_null($json) || !is_array($json)) {
      // $string could not be decoded; maybe the service is down...
      CRM_Core_Error::debug_log_message('Geocoding failed. "' . $string . '" is no valid json-code. (' . $url . ')');
      return FALSE;

    } elseif (count($json) == 0) {
      // array is empty; address is probably invalid...
      // the error logging is disabled, because it potentially reveals address data to the log
      // CRM_Core_Error::debug_log_message('Geocoding failed.  No results for: ' . $url);
      $values['geo_code_1'] = $values['geo_code_2'] = 'null';
      return FALSE;

    } elseif (array_key_exists('lat', $json[0]) && array_key_exists('lon', $json[0])) {
      // TODO: Process other relevant data to update address
      $values['geo_code_1'] = substr($json[0]->lat, 0, 12);
      $values['geo_code_2'] = substr($json[0]->lon, 0, 12);
      return TRUE;

    } else {
      // don't know what went wrong... we got an array, but without lat and lon.
      CRM_Core_Error::debug_log_message('Geocoding failed. Response was positive, but no coordinates were delivered.');
      return FALSE;
    }
  }
}
