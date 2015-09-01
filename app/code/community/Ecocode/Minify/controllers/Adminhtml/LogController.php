<?php

class Ecocode_Minify_Adminhtml_LogController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/ecocode_minify/log');
        $this->renderLayout();
    }

}