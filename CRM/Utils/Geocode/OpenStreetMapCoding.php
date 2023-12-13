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
  protected static $_server = 'nominatim.openstreetmap.org';

  /**
   * uri of service
   *
   * @var string
   * @static
   */
  protected static $_uri = '/search';

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
  public static function format(&$values, $stateName = FALSE) {
    CRM_Utils_System::checkPHPVersion(5, TRUE);

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

    $coord = self::makeRequest($url);

    $values['geo_code_1'] = $coord['geo_code_1'] ?? 'null';
    $values['geo_code_2'] = $coord['geo_code_2'] ?? 'null';

    if (isset($coord['geo_code_error'])) {
      $values['geo_code_error'] = $coord['geo_code_error'];
    }

    return isset($coord['geo_code_1'], $coord['geo_code_2']);
  }

  public static function getCoordinates($address): array {
    return self::makeRequest(urlencode($address));
  }

  /**
   * @param string $url
   *   Url-encoded address
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private static function makeRequest($url): array {
    // Nominatim requires that we cache lookups, since they're donating this
    // service for free.
    $cache = CRM_Utils_Cache::create(['type' => ['SqlGroup'], 'name' => 'geocode_osm']);
    $cacheKey = substr(sha1($url), 0, 12);
    $json = $cache->get($cacheKey);
    $foundInCache = !empty($json);
    if (!$foundInCache) {
      // No valid value found in cache.

      $client = new GuzzleHttp\Client();
      // Nominatim's terms of use require us to submit a real user agent to
      // identify ourselves.  Rate limiting may be done using this. We use the
      // configured API key if set, otherwise we use a unique hash.  We use the
      // unique has instead of the domain name since sending the addresses of
      // everybody to do with the organisation along with an identifier for the
      // organisation could be sensitive.
      // @see https://operations.osmfoundation.org/policies/nominatim/
      $appName =  CRM_Core_Config::singleton()->geoAPIKey ?: substr(sha1(CRM_Core_BAO_Domain::getDomain()->name . CIVICRM_SITE_KEY), 0, 12);
      $request = $client->request('GET', $url, ['headers' => ['User-Agent' => "CiviCRM instance ($appName)"]]);

      // check if request was successful
      if ($request->getStatusCode() != 200) {
        CRM_Core_Error::debug_log_message('Geocoding failed, invalid response code ' . $request->getStatusCode());
        return ['geo_code_error' => 'Geocoding failed, invalid response code ' . $request->getStatusCode()];
        if ($request->getStatusCode() == 429) {
          // provider says 'TOO MANY REQUESTS'
          return ['geo_code_error' => 'OVER_QUERY_LIMIT'];
        }
        else {
          return ['geo_code_error' => $request->getStatusCode()];
        }
      }

      // Process results
      $string = $request->getBody();
      $json = json_decode($string, TRUE);
    }

    if (is_null($json) || !is_array($json)) {
      // $string could not be decoded; maybe the service is down...
      // We don't save this in the cache.
      CRM_Core_Error::debug_log_message('Geocoding failed. "' . $string . '" is no valid json-code. (' . $url . ')');
      return ['geo_code_error' => 'Geocoding failed. "' . $string . '" is no valid json-code. (' . $url . ')'];

    }
    elseif (count($json) == 0) {
      // Array is empty; address is probably invalid...
      // Error logging is disabled, because it potentially reveals address data to the log
      // CRM_Core_Error::debug_log_message('Geocoding failed.  No results for: ' . $url);
      // Save in cache so we don't keep repeating the same failed query.
      $cache->set($cacheKey, $json);
      return [];
    }
    elseif (is_array($json[0]) && array_key_exists('lat', $json[0]) && array_key_exists('lon', $json[0])) {
      // TODO: Process other relevant data to update address
      // Save in cache.
      $cache->set($cacheKey, $json);
      return [
        'geo_code_1' => (float) substr($json[0]['lat'], 0, 12),
        'geo_code_2' => (float) substr($json[0]['lon'], 0, 12),
      ];

    }
    else {
      // Don't know what went wrong... we got an array, but without lat and lon.
      // We don't save this in the cache.
      CRM_Core_Error::debug_log_message('Geocoding failed. Response was positive, but no coordinates were delivered.');
      return [];
    }
  }

}
