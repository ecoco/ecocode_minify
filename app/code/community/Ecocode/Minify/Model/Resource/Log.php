<?php

/**
 * Class Ecocode_Minify_Model_Resource_Log
 */
class Ecocode_Minify_Model_Resource_Log extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Define main table
     *
     */
    protected function _construct()
    {
        $this->_init('ecocode_minify/minify_log', 'id');
    }

    public function log($message, $details = null, $type = 'debug')
    {
        $data = array('message' => $message, 'type' => $type);
        if ($details) $data['details'] = $details;
        $this->_getWriteAdapter()->insert($this->getMainTable(), $data);
    }

    public function getTypes()
    {
        $sql = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('type' => new Zend_Db_Expr('DISTINCT type')));
        return $this->_getReadAdapter()->fetchCol($sql);
    }
}
