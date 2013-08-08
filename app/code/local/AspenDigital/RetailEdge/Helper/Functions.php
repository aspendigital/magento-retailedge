<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class AspenDigital_RetailEdge_Helper_Functions
{
	protected $_categories;
	protected $_category_separator = ' > ';

	// Return a sorted list of category names (with path)
	public function getCategoryNames($parent_id = null, $store = null)
	{
		if ($this->_categories)
			return $this->_categories;

		$category_api = Mage::getSingleton('catalog/category_api_v2');

		$categories = array();
		$tree = $category_api->tree($parent_id, $store);
		foreach ($tree['children'] as $node) // Don't include root category
			$this->_fullPathName($categories, $node);

		natcasesort($categories);
		$this->_categories = $categories;
		return $categories;
	}

	public function setCategorySeparator($sep = '')
	{
		$this->_category_separator = ' ' . trim($sep) . ' ';
		return $this;
	}

	protected function _fullPathName(&$categories, $node, $prefix='')
	{
		$categories[$node['category_id']] = $full_name = $prefix . $node['name'];
		foreach ($node['children'] as $child)
			$this->_fullPathName($categories, $child, $full_name . $this->_category_separator);
	}
}

?>
