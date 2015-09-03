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
 * This function will close a transaction group,
 * and perform the necessary logical changes to the mandates contained
 */
function civicrm_api3_osm_lookup_call($params) {
  $result = CRM_Osm_Logic_Lookup::format($params);
  if ($result) {
    return civicrm_api3_create_success($params['result']);
  } else {
    return civicrm_api3_create_error('de.systopia.osm: geocoding failed');
  }
}



 /**
  * This API call will try to normalise the given address
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
function civicrm_api3_osm_lookup_normalise($params) {
  return CRM_Osm_Logic_Lookup::normalise($params);
}

