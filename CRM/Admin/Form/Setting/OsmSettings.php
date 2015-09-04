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

require_once 'CRM/Admin/Form/Setting.php';

class CRM_Admin_Form_Setting_OsmSettings extends CRM_Admin_Form_Setting
{

    public function buildQuickForm( ) {
        CRM_Utils_System::setTitle(ts('de.systopia.osm - Settings'));

        if(CRM_Osm_Logic_Settings::isEnabled()){
          $checkbox_options = array('checked' => 'checked');
        }else{
          $checkbox_options = array();
        }

        $this->addElement('checkbox', 'enable_js_lookup', ts("Enable Address Lookup"), "", $checkbox_options);

        parent::buildQuickForm();
    }

    function postProcess() {
        $values = $this->controller->exportValues($this->_name);

        CRM_Core_BAO_Setting::setItem((isset($values['enable_js_lookup']) ? "1" : "0"), 'OSM Preferences', 'enable_js_lookup');

        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts("Settings successfully saved"), "", "success");

        CRM_Core_DAO::triggerRebuild();
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/osm'));
    }
}
