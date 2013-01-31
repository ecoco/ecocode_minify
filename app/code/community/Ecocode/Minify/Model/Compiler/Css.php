<?php
class Ecocode_Minify_Model_Compiler_Css extends Ecocode_Minify_Model_Compiler_Abstract{
	
	/**
	 * minify
	 * 
	 * @param string $inputFile
	 * @param string $outputFile
	 * @param array $options
	 * @return boolean
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function minify($inputFile, $outputFile, $options = array()){
		$url = Mage::app()->getHelper('core/url')->getCurrentUrl();
		try {
			$sizeBefore = filesize($inputFile);
			list($status, $output) = $this->compile($inputFile, $outputFile, $options);

			if(!file_exists($outputFile)){
				$this->logError('Minifing CSS for ' . $url . 'failed, maybe java is not installed!', $details);
				return FALSE;
			}
			
			if($sizeBefore == filesize($outputFile) || filesize($outputFile) == 0){
				$this->logError('Minifing CSS failed! Serving compiled file', $details);
				//deleted to empty or corrupt tmp file
				unlink($outputFile);
				return FALSE;
			}
			return TRUE;			
		} catch(Exception $e){
			$this->logError($e->getMessage(), $e->getTraceAsString());
			return FALSE;
		}
	}
	
	/**
	 * compile
	 * 
	 * will try to compile the given input file and save it into the new filepath
	 * 
	 * @param string $inputFile
	 * @param string $outputFile
	 * @param array $options 
	 * @throws Exception
	 * @return array(int status, array compiler output)
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function compile($inputFile, $outputFile, $options = array()){
		$compilerJAR = Mage::getBaseDir() . DS . 'lib' . DS . 'Yui' . DS . 'yuicompressor-2.4.7.jar';
		if(!file_exists($compilerJAR)) throw new Exception('Cant minify css Compiler not found! ' . $compilerJAR);
		
		$output = array();
		$status = 0;
		exec(escapeshellcmd('java -jar ' . $compilerJAR . ' ' . $inputFile . ' --type css --line-break 500 -o ' . $outputFile)  . ' 2>&1', $output, $status);
		return array($status, $output);
	}
}