<?php
/**
 * Ecocode_Minify_Model_Log
 *
 * we need this logger class to save our logs to the database.
 * this is needed in case you have more than one frontend/backend servers
 *
 * @author "Justus Krapp <jk@ecocode.de>"
 * @author http://ecocode.de/
 */

class Ecocode_Minify_Model_Log extends Mage_Core_Model_Abstract
{
	private $_logger = null;
    protected function _construct()
    {
        $this->_init('ecocode_minify/log');
    }
    
    /**
     * log
     * 
     * will cause a log entry if debug mode is on
     * 
     * @param mixed $message
     * @param mixed $details
     * @param string $type
     * @author "Justus Krapp <jk@ecocode.de>"
     */    
    
	public function log($message, $details = null, $type = 'debug')
    {
		if(!Mage::getStoreConfigFlag('ecocode_minify/settings/debug_log') && $type != 'error') return;
		if(!is_string($message)) $message = print_r($message, true);
		if(!is_string($details)) $details = print_r($details, true);
		
		if($this->isWarmUp()) $type .= '(warmup)';
		$this->getLogger()->log($message, $details, $type);
	}
	
	/**
	 * logError
	 * 
	 * will cause a log entry in any case
	 * 
	 * @param unknown_type $message
	 * @param unknown_type $details
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function logError($message, $details = null)
    {
		$this->log($message, $details, 'error');
	}

	public function getTypes()
    {
		$helper = Mage::helper('ecocode_minify');
		$options = array();
		
		foreach($this->getResource()->getTypes() AS $type){
			$options[$type] = $helper->__($type);
		}
		return $options;
	}
	
	private function getLogger()
    {
		if(is_null($this->_logger)) $this->_logger = $this->getResource();
		return $this->_logger;
	}
	
	public function isWarmUp()
    {
		return Mage::app()->getRequest()->getParam('warmup') ? true : false;
	}
}
