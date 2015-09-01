<?php

class Ecocode_Minify_Model_Compiler_Abstract extends Mage_Core_Model_Abstract
{
    protected $_compileOptions = array();

    public function log($message, $details = null)
    {
        if (!Mage::getStoreConfigFlag('ecocode_minify/settings/debug_log')) {
            return;
        }

        Mage::getSingleton('ecocode_minify/log')->log($message, $details);
    }

    public function logError($message, $details = null)
    {
        Mage::getSingleton('ecocode_minify/log')->logError($message, $details);
    }

    /**
     *
     * @param int $minVersion
     * @return bool
     */
    protected function _isJavaUseable($minVersion = null)
    {
        return Mage::helper('ecocode_minify')->canRunJava($minVersion);
    }
}