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
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function osm_civicrm_xmlMenu(&$files) {
  _osm_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function osm_civicrm_install() {
  return _osm_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function osm_civicrm_uninstall() {
  return _osm_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function osm_civicrm_enable() {
  return _osm_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function osm_civicrm_disable() {
  return _osm_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function osm_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _osm_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function osm_civicrm_managed(&$entities) {
  return _osm_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_buildForm
 */
function osm_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_Contact' ||
      $formName == 'CRM_Contact_Form_Inline_Address') {

    if(CRM_Osm_Logic_Settings::isEnabled()) {
      CRM_Core_Region::instance('form-bottom')->add(array(
      'template' => 'CRM/Osm/Form/Contact.tpl',
      ));
    }
    
  }
}

/**
* Implementation of hook_civicrm_config
*/
function osm_civicrm_alterSettingsFolders(&$metaDataFolders = NULL){
  _osm_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
