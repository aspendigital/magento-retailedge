<?php

class AspenDigital_RetailEdge_Model_Mysql4_Category_Map extends Mage_Core_Model_Mysql4_Abstract
{
	protected $_isPkAutoIncrement = false;

	protected function _construct()
	{
		$this->_init('retailedge/category_map', 'retailedge_list_id');
	}
}

?>
