<?php

class Ecocode_Minify_Model_Compiler_Css extends Ecocode_Minify_Model_Compiler_Abstract
{
    const COMPILER_FILENAME = 'yuicompressor-2.4.8.jar';

    /**
     * minify
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param array  $options
     * @return boolean
     * @author "Justus Krapp <jk@ecocode.de>"
     */

    public function minify($inputFile, $outputFile, $options = array())
    {
        $url = Mage::app()->getHelper('core/url')->getCurrentUrl();
        try {
            $sizeBefore = filesize($inputFile);
            $this->compile($inputFile, $outputFile, $options);

            if (!file_exists($outputFile)) {
                $this->logError('Minifing CSS for ' . $url . ' failed, maybe java is not installed!');
                return false;
            }

            if ($sizeBefore == filesize($outputFile) || filesize($outputFile) == 0) {
                $this->logError('Minifing CSS failed! Serving compiled file');
                //deleted to empty or corrupt tmp file
                unlink($outputFile);
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->logError($e->getMessage(), $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Get path for the YUI compressor jar
     *
     * @return string
     */
    public function getCompilerPath()
    {
        $path = Mage::getBaseDir() . DS . 'lib' . DS . 'Yui' . DS . self::COMPILER_FILENAME;
        return $path;
    }

    /**
     * compile
     *
     * will try to compile the given input file and save it into the new filepath
     *
     * @param string $inputFile
     * @param string $outputFile
     * @param array  $options
     * @throws Exception
     * @return array(int status, array compiler output)
     * @author "Justus Krapp <jk@ecocode.de>"
     */

    public function compile($inputFile, $outputFile, $options = array())
    {
        $compilerJAR = $this->getCompilerPath();

        if (!file_exists($compilerJAR)) {
            throw new Exception('Cant minify css. Compiler not found! Tried running: ' . $compilerJAR);
        }

        if (!$this->_isJavaUseable()) {
            throw new Exception('Java is not available. Please review your server configuration.');
        }

        $output = array();
        $status = 0;
        $cmd = sprintf('java -jar %s --type css --line-break 500 -o %s %s', $compilerJAR, $outputFile, $inputFile);
        exec(escapeshellcmd($cmd) . ' 2>&1', $output, $status);
        return array($status, $output);
    }
}
