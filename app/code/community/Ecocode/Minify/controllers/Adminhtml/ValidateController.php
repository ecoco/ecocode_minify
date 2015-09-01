<?php

class Ecocode_Minify_Adminhtml_ValidateController extends Mage_Adminhtml_Controller_Action
{
    private $_functionWhiteList = array(
        'getJsDir'      => 'getJsDir',
        'compile'       => 'compile',
        'checkFilePath' => 'checkFilePath',
        'compileCustom' => 'compileCustom'
    );

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/ecocode_minify/validate');
        $this->renderLayout();
    }

    public function runfuncAction()
    {
        $returnData = array('status' => true);
        try {
            $callName = $this->getRequest()->getParam('callname');

            if (!$callName) throw new Exception('missing callname');

            $functionData = $this->{$this->getFunctionName($callName)}();
            if (is_array($functionData))
                $returnData = array_merge($returnData, $functionData);
            else
                $returnData['data'] = $functionData;
        } catch (Exception $e) {
            $returnData = array(
                'status' => false,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString()
            );
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($returnData));
    }

    private function getFunctionName($callName)
    {
        if (!isset($this->_functionWhiteList[$callName])) throw new Exception('invalid callname');
        return $this->_functionWhiteList[$callName];
    }

    /**
     * getJsDirsAction
     *
     * will return a json with all js files matching the directory
     *
     * @author "Justus Krapp <jk@ecocode.de>"
     */

    private function getJsDir()
    {
        $baseDir = Mage::getBaseDir() . '/';
        $option = $this->getRequest()->getParam('option');
        $dirs = array(
            'all'               => $baseDir,
            'main_js'           => $baseDir . 'js/',
            'skin_frontend_js'  => $baseDir . 'skin/frontend/',
            'skin_adminhtml_js' => $baseDir . 'skin/adminhtml/',
        );
        if (!isset($dirs[$option])) $option = 'all';

        $files = $this->collectFilesData(Mage::helper('ecocode_minify')->rglob('*.js', $dirs[$option]));
        return array(
            'files' => $files
        );
    }

    private function checkFilePath()
    {
        $baseDir = Mage::getBaseDir() . '/';


        $path = trim($this->getRequest()->getParam('path'));
        if (!$path || !file_exists($baseDir . $path)) {
            throw new Exception('File not found ' . $baseDir . $path);
        }
        $files = $this->collectFilesData(array($baseDir . $path));
        return array(
            'files' => $files
        );
    }

    private function compileCustom()
    {
        $code = $this->getRequest()->getParam('source-code');
        if (!$code) throw new Exception('Invalid Code');
        $tmpFile = Mage::getBaseDir('tmp') . '/ecocode_minify_custom' . time() . '.js';
        file_put_contents($tmpFile, $code);

        $returnData = $this->compile($tmpFile);
        $returnData['files'] = $this->collectFilesData(array($tmpFile), true);
        unlink($tmpFile);
        return $returnData;
    }

    private function compile($file = null)
    {
        //we dont need sessions here, so to prevent session blocking and session race conditions
        session_write_close();
        $start = microtime(true);
        $result = array(
            'status'         => true,
            'size'           => '?',
            'compiling_time' => '?',
        );
        if (!$file) $file = Mage::getBaseDir() . '/' . $this->getRequest()->getParam('file');
        if (!file_exists($file)) throw new Exception('File not found!');
        $sizeBefore = filesize($file);
        if (strpos($file, '.js') === false) {
            throw new Exception('Only JS files are allowed');
        }
        $filename = basename($file);
        $tmpFile = Mage::getBaseDir('tmp') . '/' . str_replace('.js', '.tmp.js', $filename);

        $jsCompiler = Mage::getModel('ecocode_minify/compiler_js');
        list($status, $output) = $jsCompiler->compile($file, $tmpFile, $this->getRequest()->getParam('options', array()));
        if (!file_exists($tmpFile)) {
            $result['errors'][] = ' Minifing JS ' . $filename . ' failed, maybe java is not installed!';
        } else {
            $result = array_merge($result, $jsCompiler->getGroupedOutput($output));
            $result['size'] = number_format((filesize($tmpFile) / 1024), 2);
            $result['compiling_time'] = number_format((microtime(true) - $start), 2);
            $result['compressed_code'] = nl2br(htmlentities(file_get_contents($tmpFile)));
            $result['tab_content'] = array(
                array('tab' => 'errors', 'key' => 'errors', 'html' => Mage::helper('ecocode_minify')->arrayToTable($result['errors'], array('line' => 'Line', 'message' => 'Error'))),
                array('tab' => 'warnings', 'key' => 'warnings', 'html' => Mage::helper('ecocode_minify')->arrayToTable($result['warnings'], array('line' => 'Line', 'message' => 'Warning'))),
                array('tab' => 'additional', 'key' => 'additional', 'html' => Mage::helper('ecocode_minify')->arrayToTable($result['additional'], array('message' => 'Message')))
            );
            //remove tmp file when we are done
        }
        if (file_exists($tmpFile)) unlink($tmpFile);
        return $result;
    }

    private function collectFilesData($paths = array(), $force = false)
    {
        $baseDir = Mage::getBaseDir() . '/';
        $files = array();
        foreach ($paths AS $path) {
            //we dont want to scan the tmp folder or media folder in any case
            if (!$force && (strpos($path, DS . 'var' . DS . 'tmp' . DS) !== false || strpos($path, DS . 'media' . DS) !== false)) continue;
            array_push($files, array(
                'path' => str_replace($baseDir, '', $path),
                'size' => number_format((filesize($path) / 1024), 2)
            ));
        }
        return $files;
    }
}