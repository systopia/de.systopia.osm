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
 * Class that uses OpenStreetMap (OSM) API to look up contact address data
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
class CRM_Osm_Logic_Lookup {

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
    if (CRM_Utils_Array::value('street_address', $values)) {
      $params['street'] = $values['street_address'];
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

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($url);
    $result = $request->sendRequest();

    // check if request was successful
    if (PEAR::isError($result)) {
      error_log('Geocoding failed: ' . $result->getMessage());
      return FALSE;
    }
    if ($request->getResponseCode() != 200) {
      error_log('Geocoding failed, invalid response code ' . $request->getResponseCode());
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
      error_log('Geocoding failed. "' . $string . '" is no valid json-code. (' . $url . ')');
      return FALSE;

    } elseif (count($json) == 0) {
      // array is empty; address is probably invalid...
      // the error logging is disabled, because it potentially reveals address data to the log
      // CRM_Core_Error::debug_log_message('Geocoding failed.  No results for: ' . $url);
      $values['geo_code_1'] = $values['geo_code_2'] = 'null';
      return FALSE;

    } else {
      $values['result'] = $json;
      return TRUE;
    }
  }


/**
  * This function will try to normalise the given address
  *
  * @param street_address   address street and number 
  * @param postal_code      address postal code
  * @param city             address postal city
  * @param country_id       address country ID (default is CiviCRM default country)
  * 
  * @return the params above if they could be normalised.
  *         also, the key 'query' return the original values
  *         '_street_address_not_normalised' is set to '1' if the street couldn't be parsed and processed
  *         'is_error', 'error_code', 'error_msg' is set upon an error
  *         error_code  is    0 => no error
  *                           1 => bad domain (currently this only works for Germany)
  *                           2 => query limit exceeded
  *                           3 => other nominatim error
  */
  public static function normalise($query) {
    $query_params      = array('street_address', 'postal_code', 'city', 'country_id');
    $osm_parameter_map = array('road' => 'street_address', 'postcode' => 'postal_code', 'city' => 'city', 'house_number' => 'house_number');
    $countryIsoCodes = CRM_Core_PseudoConstant::countryIsoCode();
    $street_number   = '';

    // build result structure
    $result = array('query' => array());
    foreach ($query_params as $query_param) {
      if (isset($query[$query_param])) {
        $result['query'][$query_param] = $query[$query_param];
      }
    }

    $config = CRM_Core_Config::singleton();
    if (empty($query['country_id'])) {
      $query['country_id'] = $config->defaultContactCountry;
    }

    // currently, we only support German addresses
    if ('DE' != CRM_Utils_Array::value($query['country_id'], $countryIsoCodes)) {
      $result['is_error']   = 1;
      $result['error_code'] = 1;
      $result['error_msg']  = ts("Address formatting currently only works for Germany.");
      return $result;
    } else {
      $query['country'] = 'Germany';
    }

    // parse street_address
    if (!empty($query['street_address'])) {
      $match = array();
      if (preg_match("#(?P<street_name>.*)\s(?P<street_number>[0-9]+[a-zA-Z]?)\s*$#", $query['street_address'], $match)) {
        $street_number = $match['street_number'];
      }
    }

    // query OSM server
    self::format($query);
    if (!empty($query['geo_code_error'])) {
      $result['is_error']     = 1;
      if ($query['geo_code_error'] == 'OVER_QUERY_LIMIT') {
        $result['error_code'] = 2;
        $result['error_msg']  = ts("Query limit exceeded");
      } else {
        $result['error_code'] = 3;
        $result['error_msg']  = ts("Cannot query server.");        
      }
      return $result;
    }

    // loop through the items
    foreach ($query['result'] as $entry) {
      foreach ($osm_parameter_map as $osm_key => $civi_key) {
        if (!empty($entry->address->$osm_key)) {
          // this is returned.
          if (isset($result[$civi_key])) {
            if ($result[$civi_key] != '' && $result[$civi_key] != $entry->address->$osm_key) {
              // CONFLICT! SET TO EMPTY, TO INDICATE THAT
              $result[$civi_key] = '';
            }
          } else {
            $result[$civi_key] = $entry->address->$osm_key;
          }
        }
      }
    }

    // POST-PROCESSING
    
    // POSTAL CODE
    if (!empty($result['postal_code'])) {
      if (!preg_match("#^[0-9]{5}$#", $query['postal_code'])) {
        // only accept German (5 digit) postal codes
        $query['postal_code'] = '';
      }
    }

    // STREET_ADDRESS
    if (empty($street_number)) {
      // address couldn't be parsed
      $result['street_address'] = $query['street_address'];
      $result['_street_address_not_normalised'] = 1;
    } else {
      // address was parsed -> reassemble
      if (!empty($result['house_number']) && !empty($result['street_address'])) {
        $result['street_address'] .= ' ' . $result['house_number'];
      } elseif (empty($result['house_number']) && !empty($result['street_address'])) {
        $result['street_address'] .= ' ' . $street_number;
      } else {
        $result['street_address'] = $query['street_address'];
        $result['_street_address_not_normalised'] = 1;
      }
    }
    if (isset($result['house_number'])) unset($result['house_number']);

    return $result;
  }
}
