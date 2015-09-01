<?php

/**
 * Class Ecocode_Minify_Model_Resource_Log_Collection
 */
class Ecocode_Minify_Model_Resource_Log_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('ecocode_minify/log');
    }
}
