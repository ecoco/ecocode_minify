<?php

/**
 * Ecocode_Minify_Helper_Data
 *
 * @author "Justus Krapp <jk@ecocode.de>"
 * @author http://ecocode.de/
 */
class Ecocode_Minify_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $canRunJava = array();
    protected $isExecAvailable = null;
    /**
     *
     * @param string $pattern
     * @param string $path
     * @return array
     */
    public function rglob($pattern = '*', $path = '')
    {
        $paths = glob($path . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern);
        foreach ($paths as $path) {
            $files = array_merge($files, $this->rglob($pattern, $path));
        }
        return $files;
    }

    /**
     *
     * @param array $arrayData
     * @param array $head
     * @return string
     */
    public function arrayToTable($arrayData, $head = array())
    {
        $html = '<table cellspacing="0">';
        if ($head) {
            $html .= '<thead><tr class="headings"><th>' . implode($head, '</th><th>') . '</th></tr></thead>';
        }
        $html .= '<tbody>';
        if (count($arrayData)) {
            foreach ($arrayData AS $row) {
                if ($head) {
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

    /**
     * Can find a java bin and run a command?
     *
     * @param $minJavaVersion string What minimal java version is required
     * @return boolean
     */
    public function canRunJava($minJavaVersion = 'default')
    {
        if (isset($this->canRunJava[$minJavaVersion])) {
            return $this->canRunJava[$minJavaVersion];
        }
        //is exec available?
        if (!$this->isExecAvailable()) {
            return $this->canRunJava[$minJavaVersion] = false;
        }

        //can find and run java?
        $output = array();
        $result = null;
        exec("java -version 2>&1", $output, $result);

        if (empty($output) || !isset($output[0]) || (isset($output[0]) && preg_match("/java(.)+not found/", $output[0]) === 1)) {
            return $this->canRunJava[$minJavaVersion] = false;
        }

        $versionString = $output[0];

        //the command should return a version number. did we get it?
        if (preg_match("/java version/i", $versionString) !== 1) {
            return $this->canRunJava[$minJavaVersion] = false;
        }

        if ($minJavaVersion) {
            $parsedVersion = substr($versionString, strpos($versionString, "\"") + 1);
            if (strpos($parsedVersion, ".") === false) {
                return $this->canRunJava[$minJavaVersion] = false;
            }

            $versionArray = (explode(".", $parsedVersion));
            $majorVersion = $versionArray[0];
            $minorVersion = $versionArray[1];

            if ($majorVersion < 2 && $minorVersion < $minJavaVersion) {
                return $this->canRunJava[$minJavaVersion] = false; //the current java version is too old
            }
        }
        $this->canRunJava[$minJavaVersion] = true;
        return true;
    }

    /**
     * Can the exec command be run in this environment?
     *
     * @return boolean
     */
    public function isExecAvailable()
    {
        if ($this->isExecAvailable) {
            return $this->isExecAvailable;
        }
        $available = true;

        if (ini_get('safe_mode')) {
            $available = false;
        } else {
            $disabledFunction = ini_get('disable_functions');
            $suhosinBlacklist = ini_get('suhosin.executor.func.blacklist');
            if ("$disabledFunction$suhosinBlacklist") {
                $array = preg_split('/,\s*/', "$disabledFunction,$suhosinBlacklist");
                if (in_array('exec', $array)) {
                    $available = false;
                }
            }
        }

        return $this->isExecAvailable = $available;
    }
}