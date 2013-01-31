<?php

/**
 * Ecocode_Minify_Helper_Data
 *
 * @author "Justus Krapp <jk@ecocode.de>"
 * @author http://ecocode.de/
 */
class Ecocode_Minify_Helper_Data extends Mage_Core_Helper_Abstract{

	public function rglob($pattern='*',  $path=''){
		$paths=glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
		$files=glob($path.$pattern);
		foreach ($paths as $path) {
			$files = array_merge($files, $this->rglob($pattern, $path));
		}
		return $files;
	}
	
	public function arrayToTable($arrayData, $head = array()){
		$html = '<table cellspacing="0">';
		if($head){
			$html .= '<thead><tr class="headings"><th>' . implode($head, '</th><th>') . '</th></tr></thead>';
		}
		$html .= '<tbody>';
		if(count($arrayData)){
			foreach($arrayData AS $row){
				if($head){
					//we want to preserve the head order
					$row = array_merge(
						array_fill_keys(array_keys($head), null), 
						array_intersect_key($row, $head)
					);
				} 
				$html .= '<tr><td>' . implode($row, '</td><td>') . '</td></tr>';
			}
		} else {
			$html .= '<tr><td colspan="0">' . $this->__('No entries') . '</td></tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}
}