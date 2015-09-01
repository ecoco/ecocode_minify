<?php

class Ecocode_Minify_Block_Adminhtml_Log extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_log';
        $this->_blockGroup = 'ecocode_minify';

        $this->_headerText = $this->__('Minify Log');
        parent::__construct();
        $this->_removeButton('add');
    }
}