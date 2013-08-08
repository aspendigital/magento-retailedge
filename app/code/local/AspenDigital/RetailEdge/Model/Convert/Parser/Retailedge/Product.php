<?php

/*
 * A lot of Dataflow is unclear, so I'm sure this could be done better, but it works for now.  We do "cheat"
 * by displaying informational messages directly if a flag is set. This lets us give better real-time feedback in interactive
 * situations
 */

abstract class AspenDigital_RetailEdge_Model_Convert_Parser_RetailEdge_Product extends AspenDigital_CommonImport_Model_Convert_Parser_Product_Abstract
{
	protected $_helper;
	protected $_params;

	protected $_products;
	protected $_skipped = 0;
	protected $_existing_map = array();
	protected $_remove_map = array();

	// Set by customer module if necessary -- added to array passed to RE API client when getting products
	protected $global_product_params = array();

	
	public function parse()
	{
		$this->_helper = Mage::helper('retailedge');
		$params = $this->_getParams();

		// Getting date format doesn't seem to actually give us something we can use from date() function, so hard-code for now
		$date_format = "M. d, Y"; //Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);

		if (!empty($params['created']))
			$this->addInfoException($this->_helper->__('Importing products created after %s', Mage::getModel('core/date')->date($date_format, $params['created'])));
		if (!empty($params['modified']))
			$this->addInfoException($this->_helper->__('Importing products modified after %s', Mage::getModel('core/date')->date($date_format, $params['modified'])));
		if ($this->_isDryRun())
			$this->addInfoException($this->_helper->__('Import is a dry run'));

		$this->addInfoException($this->_helper->__('Contacting RetailEdge API Server'));
		$api_client = Mage::getSingleton('retailedge/api_client');
		$api_client->setTimeout(1800); // Give API server an extended time to put together a response

		$start = microtime(true);
		$this->_products = $api_client->getProducts($params);
		$total_products = count($this->_products);

		$this->addInfoException($this->_helper->__('API Server responded in %.2fs. with %d product record(s)', microtime(true) - $start, $total_products));
		if (empty($this->_products))
			return $this;

		Mage::getSingleton('catalog/product')->startCacheCapture();

		$this->_beginSync();
		$this->_getExistingProducts();
		$this->_importProducts();
		$this->_markMissingProducts();
		$this->_removeMarkedProducts();

		$start = microtime(true);
		$this->addInfoException($this->_helper->__('Performing deferred cache cleaning'));
		Mage::getSingleton('catalog/product')->endCacheCapture();
		$this->addInfoException($this->_helper->__('Cache cleaning done (%.2f s.)', microtime(true) - $start));
	}

	protected function _getExistingProducts()
	{
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToFilter('retailedge_list_id', array('neq'=>''))
					->addAttributeToSelect('retailedge_list_id');

		foreach ($collection as $product)
			$this->_existing_map[$product->retailedge_list_id] = 1;
	}

	protected function _productExists($retailedge_id)
	{
		return isset($this->_existing_map[$retailedge_id]);
	}

	protected function _importProducts()
	{
		// Get department mapping so we can add to appropriate category
		$department_map = array();
		foreach (Mage::getModel('retailedge/category_map')->getCollection()->load() as $record)
			$department_map[$record->retailedge_list_id] = $record->category_id;

		$store = Mage::app()->getStore(0); 
		$store_code = $store->getCode();
		
		$website_ids = Mage::getStoreConfig('retailedge/product_import/websites');
		$websites = array();
		foreach (explode(',', $website_ids) as $id)
			$websites[] = Mage::app()->getWebsite($id)->getCode();
		$websites = join(',', $websites);

		$categories = Mage::helper('retailedge/functions')->getCategoryNames();


		$count = 0;
		foreach ($this->_products as $product)
		{
			$count++;
			$start_time = microtime(true);

			$dept_id = $product['department_id'];
			$product['category_ids'] = (isset($department_map[$dept_id])) ? $department_map[$dept_id] : 0;

			$product['store'] = $store_code;
			$product['retailedge_list_id'] = $product['list_id'];
			$product['websites'] = $websites;

			try {
				$exists = $this->_productExists($product['retailedge_list_id']);
				$product = $this->_saveRow($product, $exists);

				$duration = microtime(true) - $start_time;
				if ($exists)
					$msg = $this->_helper->__('#%d: Updated (%s) "%s" (%.2f s.)', $count, $product['sku'], $product['name'], $duration);
				else
				{
					$category_names = array();
					foreach (explode(',', $product['category_ids']) as $id)
					{
						if (isset($categories[$id]))
							$category_names[] = '"' . $categories[$id] . '"';
					}
					$category_names = (empty($category_names)) ? '' : join(', ', $category_names);
					
					$msg = $this->_helper->__('#%d: Added (%s) "%s" to %s (%.2f s.)', $count, $product['sku'], $product['name'], $category_names, $duration);
				}

				$this->addInfoException($msg);
			} catch (Exception $e) {
				if ($e->getMessage())
					$this->addException($product['name'] . ': ' . $e->getMessage(), Mage_Dataflow_Model_Convert_Exception::WARNING);
			}
		}

		$this->addInfoException($this->_helper->__('%d record(s) skipped', $this->_skipped));
	}

	// Get a quick list from RetailEdge of all products so we can remove anything that has been deleted from RetailEdge
	protected function _markMissingProducts()
	{
		$start = microtime(true);
		$params = array('quicklist'=>1) + $this->global_product_params;

		$products = Mage::getSingleton('retailedge/api_client')->getProducts($params);
		$this->addInfoException($this->_helper->__('Marking deleted products: API Server responded in %.2fs. with quick list of %d product record(s) -- (currently %d RE products in Magento)', microtime(true) - $start, count($products), count($this->_existing_map)));

		foreach (array_diff_key($this->_existing_map, $products) as $retailedge_list_id=>$name)
			$this->_remove_map[$retailedge_list_id] = "Deleted from RetailEdge";
	}

	// Remove products that are no longer stocked in RetailEdge (marked by customer module)
	protected function _removeMarkedProducts()
	{
		if (empty($this->_remove_map))
			return;

		$deleted = 0;

		foreach ($this->_removeProductCollection() as $product)
		{
			$start = microtime(true);
			if (!$this->_isDryRun())
				$this->_removeProduct($product);

			$delete_reason = $this->_remove_map[$product->sku];
			$this->addInfoException($this->_helper->__('Deleted (%s) "%s" (%.2f s): %s', $product->sku, $product->name, microtime(true) - $start, $delete_reason), Mage_Dataflow_Model_Convert_Exception::WARNING);
			$deleted++;
		}

		$this->addInfoException($this->_helper->__('%d records deleted', $deleted));
	}
	
	protected function _removeProductCollection()
	{
		return Mage::getModel('catalog/product')
					->getCollection()
					->addAttributeToFilter('retailedge_list_id', array('in'=>array_keys($this->_remove_map)))
					->addAttributeToSelect('retailedge_list_id')
					->addAttributeToSelect('name');
	}
	
	// By default, completely remove the product record when not stocked in RetailEdge; customer module can of course override this behavior
	protected function _removeProduct($product)
	{
		$product->delete();
	}

	
	// Implemented by customer module
	protected function _saveRow($data, $exists = false)
	{
		return $data;
	}

	// Implemented by customer module if necessary
	protected function _beginSync()
	{

	}

	public function unparse()
	{
		// Not implemented
	}

	// If variables were not set in XML, check global registry
	protected function _getParams()
	{
		if (!$this->_params)
		{
			$creation_time = $this->getVar('creation_time');
			if (!$creation_time)
				$creation_time = Mage::registry('retailedge_product_created_time');

			$modified_time = $this->getVar('modified_time');
			if (!$modified_time)
				$modified_time = Mage::registry('retailedge_product_modified_time');

			$result = array(
					'created'=>$creation_time,
					'modified'=>$modified_time
				) + $this->global_product_params;
		
			$this->_params = array_filter($result);
		}

		return $this->_params;
	}

}

?>
