<?php
/*
 * @category    Devopensource
 * @package		Devopensource_All
 * @copyright   Copyright (c) 2012 Devopensource
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Devopensource_All_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getConfigData($path){

        return Mage::getStoreConfig($path);
    }

}