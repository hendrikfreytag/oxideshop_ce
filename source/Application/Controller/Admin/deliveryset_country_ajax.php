<?php
/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version   OXID eShop CE
 */

/**
 * Class manages deliveryset countries
 */
class deliveryset_country_ajax extends ajaxListComponent
{

    /**
     * Columns array
     *
     * @var array
     */
    protected $_aColumns = array('container1' => array( // field , table,         visible, multilanguage, ident
        array('oxtitle', 'oxcountry', 1, 1, 0),
        array('oxisoalpha2', 'oxcountry', 1, 0, 0),
        array('oxisoalpha3', 'oxcountry', 0, 0, 0),
        array('oxunnum3', 'oxcountry', 0, 0, 0),
        array('oxid', 'oxcountry', 0, 0, 1)
    ),
                                 'container2' => array(
                                     array('oxtitle', 'oxcountry', 1, 1, 0),
                                     array('oxisoalpha2', 'oxcountry', 1, 0, 0),
                                     array('oxisoalpha3', 'oxcountry', 0, 0, 0),
                                     array('oxunnum3', 'oxcountry', 0, 0, 0),
                                     array('oxid', 'oxobject2delivery', 0, 0, 1)
                                 )
    );

    /**
     * Returns SQL query for data to fetc
     *
     * @return string
     */
    protected function _getQuery()
    {
        $oDb = oxDb::getDb();
        $sId = $this->getConfig()->getRequestParameter('oxid');
        $sSynchId = $this->getConfig()->getRequestParameter('synchoxid');

        $sCountryTable = $this->_getViewName('oxcountry');

        // category selected or not ?
        if (!$sId) {
            $sQAdd = " from {$sCountryTable} where {$sCountryTable}.oxactive = '1' ";
        } else {
            $sQAdd = " from oxobject2delivery, {$sCountryTable} " .
                     "where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sId) .
                     " and oxobject2delivery.oxobjectid = {$sCountryTable}.oxid " .
                     "and oxobject2delivery.oxtype = 'oxdelset' ";
        }

        if ($sSynchId && $sSynchId != $sId) {
            $sQAdd .= "and {$sCountryTable}.oxid not in ( select {$sCountryTable}.oxid " .
                      "from oxobject2delivery, {$sCountryTable} " .
                      "where oxobject2delivery.oxdeliveryid = " . $oDb->quote($sSynchId) .
                      "and oxobject2delivery.oxobjectid = {$sCountryTable}.oxid " .
                      "and oxobject2delivery.oxtype = 'oxdelset' ) ";
        }

        return $sQAdd;
    }

    /**
     * Removes chosen countries from delivery list
     */
    public function removeCountryFromSet()
    {
        $aChosenCntr = $this->_getActionIds('oxobject2delivery.oxid');
        // removing all
        if ($this->getConfig()->getRequestParameter('all')) {

            $sQ = $this->_addFilter("delete oxobject2delivery.* " . $this->_getQuery());
            oxDb::getDb()->Execute($sQ);

        } elseif (is_array($aChosenCntr)) {
            $sChosenCountries = implode(", ", oxDb::getInstance()->quoteArray($aChosenCntr));
            $sQ = "delete from oxobject2delivery where oxobject2delivery.oxid in (" . $sChosenCountries . ") ";
            oxDb::getDb()->Execute($sQ);
        }
    }

    /**
     * Adds chosen countries to delivery list
     */
    public function addCountryToSet()
    {
        $aChosenCntr = $this->_getActionIds('oxcountry.oxid');
        $soxId = $this->getConfig()->getRequestParameter('synchoxid');

        // adding
        if ($this->getConfig()->getRequestParameter('all')) {
            $sCountryTable = $this->_getViewName('oxcountry');
            $aChosenCntr = $this->_getAll($this->_addFilter("select $sCountryTable.oxid " . $this->_getQuery()));
        }

        if ($soxId && $soxId != "-1" && is_array($aChosenCntr)) {
            foreach ($aChosenCntr as $sChosenCntr) {
                $oObject2Delivery = oxNew('oxBase');
                $oObject2Delivery->init('oxobject2delivery');
                $oObject2Delivery->oxobject2delivery__oxdeliveryid = new oxField($soxId);
                $oObject2Delivery->oxobject2delivery__oxobjectid = new oxField($sChosenCntr);
                $oObject2Delivery->oxobject2delivery__oxtype = new oxField("oxdelset");
                $oObject2Delivery->save();
            }
        }
    }
}
