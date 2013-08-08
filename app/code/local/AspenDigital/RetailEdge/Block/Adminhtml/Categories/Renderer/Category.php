<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Renderer_Category extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $category_id = $row->getData($this->getColumn()->getIndex());
		$categories = Mage::helper('retailedge/functions')->getCategoryNames();
		if (isset($categories[$category_id]))
			return $categories[$category_id];
		return '';
    }
}

?>