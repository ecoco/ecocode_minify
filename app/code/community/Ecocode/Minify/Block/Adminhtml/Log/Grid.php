<?php

/**
 * Class Ecocode_Minify_Block_Adminhtml_Log_Grid
 */
class Ecocode_Minify_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid{

    public function __construct(){
        parent::__construct();
        $this->setId('ecocode_minify_log_grid');
        $this->setTemplate('ecocode/minify/log/grid.phtml');
        $this->setRowClickCallback(null);
        $this->setDefaultSort('timestamp');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection(){
        $collection = Mage::getModel('ecocode_minify/log')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns(){
		$this->addColumn('timestamp', array(
            'header'    => $this->__('Date'),
            'align'     =>'left',
            'width'     => '125px',
            'index'     => 'timestamp',
			'type'        =>'datetime'
        ));
		$this->addColumn('type', array(
            'header'    => $this->__('Type'),
            'align'     =>'left',
            'width'     => '125px',
            'index'     => 'type',
			'column_css_class' => 'type',
			'type'  	=> 'options',
			'options' => Mage::getSingleton('ecocode_minify/log/')->getTypes(),
        ));
		$this->addColumn('message', array(
            'header'    => $this->__('Message'),
            'align'     =>'left',
            'width'     => 'auto',
			'index'     => 'message',
			'filter' 	=> false
        ));
		
        parent::_prepareColumns();
        return $this;
    }
}