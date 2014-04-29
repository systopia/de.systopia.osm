<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM OSM Geocoding module (SYS-OSM)                             |
  +--------------------------------------------------------------------+
  | Copyright SYSTOPIA (c) 2014                                        |
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
 * @copyright SYSTOPIA (c) 2014
 *
 */

/**
 * Class that uses OpenStreetMaps (OSM) API to retrieve the lat/long of an address
 */
class CRM_Utils_Geocode_OpenStreetMapsCoding {

  /**
   * server to retrieve the lat/long
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

      // dont add state twice if replicated in city (happens in NZ and other countries, CRM-2632)
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

    $params['addressdetails'] = '1';
    $url = "http://" . self::$_server . self::$_uri;
    $url .= '?format=json';
    foreach ($params as $key => $value) {
      $url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    error_log($url);

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($url);
    $request->sendRequest();
    $string = $request->getResponseBody();
    // see CRM-11359 for why we suppress errors with @
    $json = json_decode($string);
    // error_log($string);

    // $string couldn't be decoded
    if (!$json) {
      CRM_Core_Error::debug_var('Geocoding failed.  No results for: ' . $url);
      return FALSE;
    }
    $ret = $json[0];

    // TODO: Process other relevant data to update address
    if (!empty($ret)) {
      $values['geo_code_1'] = $ret->lat;
      $values['geo_code_2'] = $ret->lon;
      return TRUE;
    }


    // if (is_a($xml->results->Result, 'SimpleXMLElement')) {
    //   $result = array();
    //   $result = get_object_vars($xml->results->Result);
    //   foreach ($result as $key => $val) {
    //     if (is_scalar($val) &&
    //       strlen($val)
    //     ) {
    //       $ret[(string) $key] = (string) $val;
    //     }
    //   }

    //   $values['geo_code_1'] = $ret['latitude'];
    //   $values['geo_code_2'] = $ret['longitude'];

    //   if ($ret['postal']) {
    //     $current_pc = CRM_Utils_Array::value('postal_code', $values);
    //     $skip_postal = FALSE;

    //     if ($current_pc) {
    //       $current_pc_suffix = CRM_Utils_Array::value('postal_code_suffix', $values);
    //       $current_pc_complete = $current_pc . $current_pc_suffix;
    //       $new_pc_complete = preg_replace("/[+-]/", '', $ret['postal']);

    //       // if a postal code was already entered, don't change it, except to make it more precise
    //       if (strpos($new_pc_complete, $current_pc_complete) !== 0) {
    //         // Don't bother anonymous users with the message - they can't change a form they just submitted anyway
    //         if (CRM_Utils_System::isUserLoggedIn()) {
    //           $msg = ts('The Yahoo Geocoding system returned a different postal code (%1) than the one you entered (%2). If you want the Yahoo value, please delete the current postal code and save again.', array(
    //             1 => $ret['postal'],
    //             2 => $current_pc_suffix ? "$current_pc-$current_pc_suffix" : $current_pc
    //           ));

    //           CRM_Core_Session::setStatus($msg, ts('Postal Code Mismatch'), 'error');
    //         }
    //         $skip_postal = TRUE;
    //       }
    //     }

    //     if (!$skip_postal) {
    //       $values['postal_code'] = $ret['postal'];

    //       /* the following logic to split the string was borrowed from
    //          CRM/Core/BAO/Address.php -- CRM_Core_BAO_Address::fixAddress.
    //          This is actually the function that calls the geocoding
    //          script to begin with, but the postal code business takes
    //          place before geocoding gets called.
    //       */

    //       if (preg_match('/^(\d{4,5})[+-](\d{4})$/',
    //         $ret['postal'],
    //         $match
    //       )
    //       ) {
    //         $values['postal_code'] = $match[1];
    //         $values['postal_code_suffix'] = $match[2];
    //       }
    //     }
    //   }
    //   return TRUE;
    // }

    // reset the geo code values if we did not get any good values
    $values['geo_code_1'] = $values['geo_code_2'] = 'null';
    return FALSE;
  }
}
