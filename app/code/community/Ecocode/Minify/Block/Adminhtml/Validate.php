<?php

/**
 * Class Ecocode_Minify_Block_Adminhtml_Validate
 */
class Ecocode_Minify_Block_Adminhtml_Validate extends Mage_Adminhtml_Block_Abstract
{

    public function __construct()
    {
        $this->_headerText = $this->__('Validate Javascript');
        parent::__construct();
    }

}