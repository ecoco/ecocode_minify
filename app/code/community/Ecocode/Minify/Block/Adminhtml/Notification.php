<?php

/**
 * Class Ecocode_Minify_Block_Adminhtml_Notification
 */
class Ecocode_Minify_Block_Adminhtml_Notification extends Mage_Core_Block_Template
{
    const CONFIG_PATH_MESSAGE_DISMISSED = "ecocode/minify/java_warning_msg_dismissed";

    protected $_isJavaUseable;
    protected $_isExecUseable;

    protected function _isMessageDismissed()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_MESSAGE_DISMISSED);
    }

    public function _construct()
    {
        if (!$this->_isMessageDismissed() && $this->isExecUseable() && $this->isJavaUseable()) {
            //dismiss error message, system is ok
            $config = new Mage_Core_Model_Config();
            $config->saveConfig(self::CONFIG_PATH_MESSAGE_DISMISSED, 1);
        }
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_isMessageDismissed() == 1) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * @return mixed
     */
    public function isJavaUseable()
    {
        if (is_null($this->_isJavaUseable)) {
            $this->_isJavaUseable = $this->_getMinifyHelper()
                ->canRunJava($this->getMinJavaVersion());
        }

        return $this->_isJavaUseable;
    }

    /**
     * @return int
     */
    public function getMinJavaVersion()
    {
        return Ecocode_Minify_Model_Compiler_Js::MIN__REQUIRED_JAVA_VERSION;
    }

    /**
     * @return bool
     */
    public function isExecUseable()
    {
        if (is_null($this->_isExecUseable)) {
            $this->_isExecUseable = $this->_getMinifyHelper()
                ->isExecAvailable();
        }

        return $this->_isExecUseable;
    }

    /**
     * @return Ecocode_Minify_Helper_Data
     */
    protected function _getMinifyHelper()
    {
        return Mage::helper('ecocode_minify');
    }
}