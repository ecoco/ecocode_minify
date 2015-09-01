<?php
/**
 * Ecocode_Minify_Model_Observer
 *
 * @author "Justus Krapp <jk@ecocode.de>"
 * @author http://ecocode.de/
 */
class Ecocode_Minify_Model_Observer{
	
	static $tmpFolder = 'eco_minify';
	
	/**
	 * createNewSuffix
	 * 
	 * will refresh the suffix for js and css files
	 * 
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function createNewSuffix(){
		Mage::getSingleton('ecocode_minify/log')->log('Creating new suffix for css and js files');
		$suffix = time();
		//this flag is global
		Mage::app()->getConfig()->saveConfig('ecocode/minify/suffix', $suffix);
		//we need this to make sure the value is applied when cache is enabled
		$this->warmUpJsCssCache();
		Mage::app()->getConfig()->reinit();		
	}
	
	/**
	 * copyCache
	 * 
	 * will copy the warmup cache files into media folder
	 * 
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function copyCache(){
		if(!Mage::getStoreConfigFlag('ecocode_minify/settings/warmup')) return;
		shell_exec('cp -r ' . $this->getTmpDir() . '/* ' . Mage::getBaseDir('media') . '/');
		Varien_Io_File::rmdirRecursive($this->getTmpDir());
	}
	
	/**
	 * warumpCache
	 * 
	 * will premerge the most important page to prevent initial long loading times
	 * 
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	public function warmUpJsCssCache(){
		//depending on your system and the number of urls this could take some time
		if(!Mage::getStoreConfigFlag('ecocode_minify/settings/warmup')) return;
		Mage::getSingleton('ecocode_minify/log')->log('Try cache warump');
		ini_set('max_execution_time', 600);
		$helper = Mage::helper('ecocode_minify');
		try{
			if(!$this->createTmpDir()) throw new Exception('Ecocode Minify:' . $helper->__('Unable to create tmp dir (%s)!', $this->getTmpDir()));
			
			$urls = $this->getWarmUpUrls();
			foreach($urls AS $url){
				$this->crawlPage($url);
			}
			Mage::getSingleton('ecocode_minify/log')->log('Cache warump successful (urls hit in details)', implode($urls, "<br />"));
			Mage::getSingleton('adminhtml/session')->addSuccess($helper->__('Minify: Cache warm up successfull. (Urls hit: %s)', count($urls)));
		} catch(Exception $e){
			Mage::getSingleton('adminhtml/session')->addError($helper->__($e->getMessage()));
		}
	}
	
	/**
	 * getWarmUpUrls
	 * 
	 * will collect urls for each store for warump process
	 * 
	 * @return array
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	private function getWarmUpUrls(){
		$urls = array();
		foreach(Mage::app()->getStores() AS $store){
			if(!$store->getIsActive()) continue;
			
			$urls = array_merge($urls, $this->getStoreBaseUrls($store));
			$urls = array_merge($urls, $this->getCustomUrls($store));
			$urls = array_merge($urls, $this->getCategoryUrls($store));
			$urls = array_merge($urls, $this->getProductUrls($store));
		}
		//we dont need to process urls twice
		$urls = array_unique($urls);
		return $urls;
	}
	
	/**
	 * getStoreBaseUrls
	 * 
	 * will add some basic urls to warmup progress
	 * 
	 * @param Mage_Core_App $store
	 * @return array
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	private function getStoreBaseUrls($store){
		return array(
			$store->getBaseUrl(),
			$store->getBaseUrl() . $store->getConfig('web/default/cms_home_page'),
			$store->getBaseUrl() . $store->getConfig('web/default/cms_no_route'),
			$store->getBaseUrl() . $store->getConfig('web/default/cms_no_cookies'),
			$store->getBaseUrl() . 'customer/account/login/',
			$store->getBaseUrl() . 'checkout/cart/',
			$store->getBaseUrl() . 'checkout/onepage/',
		);
	}
	
	private function getCategoryUrls($store){
		$id = $store->getRootCategoryId();
		if(!$id) return array();
		$categoryModel = Mage::getModel('catalog/category')->load($id);
		if(!$categoryModel) return array();
		
		$urls = array();
		foreach($categoryModel->getCategories($id) AS $category){
			if(!$category->getIsActive()) continue;
			array_push($urls, $store->getBaseUrl() . $category->getUrlKey());
		}
		//5 category urls should be enough
		return array_slice($urls, 0, 5);
	}
	
	/**
	 * getCustomUrls
	 * 
	 * will get user defined urls for warmup
	 * 
	 * @param unknown_type $store
	 * @return multitype:|unknown
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	private function getCustomUrls($store){
		$customUrls = Mage::getStoreConfig('ecocode_minify/settings/warmup_urls');
		if(!$customUrls) return array();
		$customUrls = explode(',', $customUrls);
		array_walk($customUrls, function(&$value, $key, $baseUrl) { 
			$value = $baseUrl . trim($value, '/'); 
		}, $store->getBaseUrl());
		return $customUrls;
	}
	
	public function getProductUrls($store){
		$urls = array();
		//we will use 2 products of each type for warmup
		$typeIds = array('bundle', 'configurable', 'grouped', 'simple', 'virtual');	
		foreach($typeIds AS $type){
			$productsCollection = Mage::getResourceModel('catalog/product_collection')
				->addAttributeToFilter('status', array('eq' => 1))
				->addAttributeToFilter('type_id', array('eq' => $type))
				->setStore($store);
			$productsCollection->getSelect()
				->order('rand()')
				->limit(2);
			
			foreach($productsCollection AS $product){
				$product->setStoreId($store->getStoreId());
				array_push($urls, $product->getProductUrl());
			}
		}
		return $urls;
	}
	
	private function crawlPage($url){
		//we add warmup flag so the new js/css is saved in a new place
		$url .= ((parse_url($url, PHP_URL_QUERY)) ? '&' : '?') . 'warmup=1';
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($curl);
	}
	
	/**
	 * createTmpDir
	 * 
	 * @return Ambigous <boolean, string>|boolean
	 * @author "Justus Krapp <jk@ecocode.de>"
	 */
	
	private function createTmpDir(){
		try {
			$dir = $this->getTmpDir();
			Varien_Io_File::rmdirRecursive($dir);
			Mage::helper('core/file_storage_database')->deleteFolder($dir);
			
			if (!is_dir($dir)) {
				mkdir($dir);
			}
			return is_writeable($dir) ? $dir : false;
		} catch (Exception $e) {
			Mage::getSingleton('ecocode_minify/log')->logError($e->getMessage(), $e->getTraceAsString());
		}
		return false;		
	}
	
	private function getTmpDir(){
		return Mage::getBaseDir('tmp') . DS . self::$tmpFolder;
	}
}