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
  
require_once 'osm.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function osm_civicrm_config(&$config) {
  _osm_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 */
function osm_civicrm_install() {
  return _osm_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 */
function osm_civicrm_enable() {
  return _osm_civix_civicrm_enable();
}
