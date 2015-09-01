<?php

/**
 * Ecocode_Minify_Model_Design_Package
 *
 * @author "Justus Krapp <jk@ecocode.de>"
 * @author http://ecocode.de/
 */
class Ecocode_Minify_Model_Design_Package extends Mage_Core_Model_Design_Package
{
    private $suffix = null;

    /**
     * getMergedJsUrl
     *
     * Merge specified javascript files and return URL to the merged file on success
     *
     * @param array $files
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $targetDir = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        //we sum the size so we can determ if something changed
        $fileSizeTotal = $this->getSumFileSize($files);

        //if size changed a new file will be generated so browser caching wont be a problem
        $targetFilename = md5(implode(',', $files) . $fileSizeTotal) . '-' . $this->getSuffix('js') . '.js';
        $url = Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $targetFilename;
        if (file_exists($targetDir . DS . $targetFilename)) {
            return $url;
        } else {
            if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, null, 'js')) {
                //check if we want to minify. Dont minify if we are in the backend. Ext javascript files making trouble
                if (Mage::getStoreConfigFlag('dev/js/minify') && Mage::app()->getStore()->getId() != 0) {
                    $this->minifyJS($targetDir . DS . $targetFilename);
                }
                return $url;
            }
        }
        return '';
    }

    /**
     * cleanMergedJsCss
     *
     * will clean merged javascript and css files
     *
     * @author "Justus Krapp <jk@ecocode.de>"
     */
    public function cleanMergedJsCss()
    {
        Mage::getSingleton('ecocode_minify/log')->log('clean merged js css cache');
        Mage::dispatchEvent('clean_media_cache_before');
        return parent::cleanMergedJsCss();
    }

    /**
     * getMergedCssUrl
     *
     * Merge specified css files and return URL to the merged file on success
     *
     * @param array $files
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        //return parent::getMergedCssUrl($files);
        // secure or unsecure
        $isSecure = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);
        if (!$targetDir) {
            return '';
        }
        // base hostname & port
        $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        $hostname = parse_url($baseMediaUrl, PHP_URL_HOST);
        $port = parse_url($baseMediaUrl, PHP_URL_PORT);
        if (false === $port) {
            $port = $isSecure ? 443 : 80;
        }

        //we sum the size so we can determ if something changed
        $fileSizeTotal = $this->getSumFileSize($files);
        $targetFilename = md5(implode(',', $files) . "|{$hostname}|{$port}" . $fileSizeTotal) . '-' . $this->getSuffix('css') . '.css';
        $url = $baseMediaUrl . $mergerDir . '/' . $targetFilename;
        if (file_exists($targetDir . DS . $targetFilename)) {
            return $url;
        } else {
            if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, array($this, 'beforeMergeCss'), 'css')) {
                //check if we want to minify
                if (Mage::getStoreConfigFlag('dev/css/minify')) {
                    $this->minifyCSS($targetDir . DS . $targetFilename);
                }
                return $url;
            }
        }
        return '';
    }

    /**
     * isWarmUp
     *
     * will check if warm mode is active
     *
     * @return boolean
     */
    private function isWarmUp()
    {
        return Mage::app()->getRequest()->getParam('warmup') ? true : false;
    }

    /**
     * Make sure merger dir exists and writeable
     * Also can clean it up
     *
     * @param string $dirRelativeName
     * @param bool   $cleanup
     * @return bool
     */
    protected function _initMergerDir($dirRelativeName, $cleanup = false)
    {
        $mediaDir = Mage::getBaseDir('media');
        //inCase its warm up mode change dir
        if ($this->isWarmUp()) {
            $mediaDir = Mage::getBaseDir('tmp') . DS . Ecocode_Minify_Model_Observer::$tmpFolder;
            if (!is_dir($mediaDir)) mkdir($mediaDir);
        }

        try {
            $dir = $mediaDir . DS . $dirRelativeName;
            if ($cleanup) {
                Varien_Io_File::rmdirRecursive($dir);
                Mage::helper('core/file_storage_database')->deleteFolder($dir);
            }
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            return is_writeable($dir) ? $dir : false;
        } catch (Exception $e) {
            Mage::getSingleton('ecocode_minify/log')->logError($e->getMessage(), $e->getTraceAsString());
        }
        return false;
    }


    /**
     * getSuffix
     *
     * will return the current suffix to append to merged files
     * or generate one if not set
     *
     * @param string $type js|css
     * @return string|mixed
     */
    private function getSuffix($type)
    {
        if ($this->suffix) return $this->suffix;

        if ($this->isWarmUp()) {
            //do not use the cache in case of a warm up
            $config = Mage::getModel('core/config');
            $resourceModel = $config->getResourceModel();
            $db = $resourceModel->getReadConnection();
            $select = $db->select()
                ->from($resourceModel->getMainTable(), array('value'))
                ->where('path = ?', 'ecocode/minify/suffix')
                ->where('scope_id = 0');

            $this->suffix = $db->fetchOne($select);
        } else {
            $this->suffix = Mage::getStoreConfig('ecocode/minify/suffix');
        }

        if (!$this->suffix) {
            Mage::getSingleton('ecocode_minify/log')
                ->log('Generating new suffix (If you see this multiple times in a short time something is badly wrong!)');
            $this->suffix = time();
            //this flag is global
            Mage::app()->getConfig()->saveConfig('ecocode/minify/suffix', $this->suffix);
            //we need this to make sure the value is applied when cache is enabled, this should only happens once
            Mage::app()->getConfig()->reinit();
        }
        if (Mage::getStoreConfigFlag('dev/' . $type . '/minify')) $this->suffix .= '.min';

        return $this->suffix;
    }

    /**
     * minifyJS
     *
     * will minify a give js file with to google Closule Compiler
     *
     * @param string $targetFilename absolute path to the js file that should be minified
     * @return Ecocode_Minify_Model_Design_Package
     */
    public function minifyJS($targetFilename)
    {
        $tmpFile = str_replace('.js', '.tmp.js', $targetFilename);

        if (Mage::getModel('ecocode_minify/compiler_js')->minify($targetFilename, $tmpFile)) {
            if (!rename($tmpFile, $targetFilename)) {
                Mage::getSingleton('ecocode_minify/log')->log('Minify js file could not be renamed');
            }
        }
        return $this;
    }

    /**
     * minifyJS
     *
     * will minify a give CSS file with to yahoo Yui-Compressor
     *
     * @param string $targetFilename absolute path to the css file that should be minified
     * @return Ecocode_Minify_Model_Design_Package
     */
    private function minifyCSS($targetFilename)
    {
        $tmpFile = str_replace('.css', '.tmp.css', $targetFilename);

        if (Mage::getModel('ecocode_minify/compiler_css')->minify($targetFilename, $tmpFile)) {
            if (!rename($tmpFile, $targetFilename)) {
                Mage::getSingleton('ecocode_minify/log')->log('Minify css file could not be renamed');
            }
        }
        return $this;
    }

    /**
     * getSumFileSize
     *
     * will sum the size all given files
     *
     * @param array $files
     * @return int
     */
    private function getSumFileSize($files = array())
    {
        $sizeTotal = 0;
        foreach ($files AS $file) {
            $sizeTotal += filesize($file);
        }
        return $sizeTotal;
    }
}