<?php
/**
 * Ecocode_Minify_Model_Compiler_Js
 *
 * @author "Justus Krapp <jk@ecocode.de>"
 * @author http://ecocode.de/
 */
class Ecocode_Minify_Model_Compiler_Js extends Ecocode_Minify_Model_Compiler_Abstract
{
    const COMPILER_FILENAME = 'compiler.jar';
    const MIN__REQUIRED_JAVA_VERSION = 7;
	
	protected $_defaultCompileOptions = array(
		'compilation_level' => 'SIMPLE_OPTIMIZATIONS'
	);
	
	/**
	 * Options that are currently allowed to use. 
	 * There are a lot more options feel free to and them
	 */
	
	protected $_compileOptionsWhitelist = array(
		'compilation_level' => array(
			'SIMPLE_OPTIMIZATIONS', 
			'WHITESPACE_ONLY'
		),
		'formatting' => array(
			'PRINT_INPUT_DELIMITER',
			'PRETTY_PRINT',
			'SINGLE_QUOTES'
		),
		'warning_level' => array(
			'QUIET',
			'DEFAULT',
			'VERBOSE'
		)
	);
	
	/**
	 * minify
	 * 
	 * @param string $inputFile
	 * @param string $outputFile
	 * @param array $options
	 * @return boolean
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function minify($inputFile, $outputFile, $options = array())
    {
		$url =  Mage::app()->getHelper('core/url')->getCurrentUrl();
		try {
			$sizeBefore = filesize($inputFile);
			list($status, $output) = $this->compile($inputFile, $outputFile, $options);
			$details = array(
				array('line' => '', 'type' => 'File', 'message' => $inputFile),	
				array('line' => '', 'type' => 'Url', 'message' => $url)		
			);
            
			if(Mage::getStoreConfigFlag('ecocode_minify/settings/debug_log') && count($output)){
				$helper = Mage::app()->getHelper('ecocode_minify');
				$output = array_merge($details, $this->parseOutput($output));
				$details = $helper->arrayToTable(
					$output, 
					array('line' => $helper->__('Line'), 'type' => $helper->__('Type'), 'message' => $helper->__('Message'))
				);
			}
			
			if(!file_exists($outputFile)){
				$this->logError(' Minifing JS for ' . $url . 'failed, maybe java is not installed!', $details);
				return false;
			}
            
			if($sizeBefore == filesize($outputFile) || filesize($outputFile) == 0){
				$this->logError('Minifing JS failed! Serving uncompiled file', $details);
				return false;
			}
            
			return true;
			
		} catch(Exception $e){
			$this->logError($e->getMessage(), $e->getTraceAsString());
			return false;
		}
	}
    
    /**
     * Get Closure compiler file path
     * 
     * @return string
     */
    public function getCompilerPath()
    {
        $path = Mage::getBaseDir() . DS . 'lib' . DS . 'Closure' . DS . self::COMPILER_FILENAME;
        return $path;
    }
	
	/**
	 * compile
	 * 
	 * @param string $inputFile
	 * @param string $outputFile
	 * @param array $options
	 * @throws Exception
	 * @return multitype:multitype: number 
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function compile($inputFile, $outputFile, array $options = array())
    {
		$compilerJAR = $this->getCompilerPath();
		if(!file_exists($compilerJAR)) {
            throw new Exception("Can't Minify: Javascript Compiler not found! Was expecting: " . $compilerJAR);
        }
        
        if(!$this->_isJavaUseable(self::MIN__REQUIRED_JAVA_VERSION)) {
            throw new Exception(
                    sprintf('Java is not available, or is wrong version. Minimal Java version required is %s. Please review your server configuration.', self::MIN__REQUIRED_JAVA_VERSION)
                );
        }
		
		$optionsString = $this->createOptionsString($options);
		$output = array();
		$status = 0;
		exec(escapeshellcmd('java -jar ' . $compilerJAR . ' ' . $optionsString . ' --js ' . $inputFile . ' --js_output_file ' . $outputFile)  . ' 2>&1', $output, $status);
		
        return array($status, $output);
	}
	
	/**
	 * createOptionsString
	 * 
	 * will create to options string for the compiler
	 * 
	 * @param array $options
	 * @return string
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	private function createOptionsString(array $options = array())
    {
		$options = array_merge($this->_defaultCompileOptions, $options);
		$optionString = array();
		foreach($options AS $key => $option){
			if(!isset($this->_compileOptionsWhitelist[$key])) {
                continue;
            }
			
			if(!is_array($option)){
				$option = strtoupper($option);
				if(in_array($option, $this->_compileOptionsWhitelist[$key])){
					$optionString[] = '--' . $key . ' ' . $option;
				}
			} else {
				foreach($option AS $value){
					$value = strtoupper($value);
					if(!in_array($value, $this->_compileOptionsWhitelist[$key])) {
                        continue;
                    }
					
					$optionString[] = '--' . $key . ' ' . $value;
				}
			}
		}
		return implode(' ', $optionString);
	}
	
	/**
	 * getGroupedOutput
	 * 
	 * will take the output of the compiler and group
	 * the messages into errors, warnings, additional
	 * 
	 * @param array $outputArray
	 * @return array
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	public function getGroupedOutput(array $outputArray = array()){
		$data = array(
				'errors' => array(),
				'warnings' => array(),
				'additional' => array()
		);
		
		$outputArray = $this->parseOutput($outputArray);
		foreach($outputArray AS $entry){
			$type = strtolower($entry['type']) . 's';
			if(isset($data[$type])){
				$data[$type][] = $entry;
			} else {
				$data['additional'][] = $entry;
			}
		}
		return $data;
	}
	/**
	 * parseOutput
	 * 
	 * will parse to output of the compiler and split the
	 * information into line, message and type
	 * 
	 * @param array $outputArray
	 * @return array
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function parseOutput(array $outputArray = array()){
		$entries = array();
		$messages = array();
		//"^" is the delimeter for google
		$current = array();
		foreach($outputArray AS $line){
			if(!$line) continue;
			if(trim($line) == '^'){
				$entries[] = implode("<br />", $current);
				$current = array();
			} else {
				//we need to limit output in case we recompile a already compiled 1 line file
				if (strlen($line) > 2500) $line = substr($line, 0, 2500);
				$current[] = $line;
			}
		}
		if(count($current)) $entries[] = implode("<br />", $current);
		if(!count($entries)) $messages;
		
		//last line contains stats like "18 error(s), 5 warning(s)"
		$lastEntry = array_pop($entries);
		foreach($entries AS $entry){
			$entryData = array(
				'line' => '?',
				'message' => '',
				'type' => 'unkown'
			);
			preg_match('/(.*?):([0-9]+)[: ]+(.*?)[- ]+(.+)/s', $entry, $matches);
			if(count($matches) != 5){
				$entryData['message'] = trim($entry);
				if(!empty($entryData['message'])) $messages[] = $entryData;
				continue;
			}
			array_shift($matches); //remove full entry
			array_shift($matches); //remove full filename
			
			//trim all whitespaces
			$matches = array_map('trim', $matches);
			list($entryData['line'], $entryData['type'], $entryData['message']) = $matches;
			//check type
			$messages[] = $entryData;
		}
		return $messages;
	}
}