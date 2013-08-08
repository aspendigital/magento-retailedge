<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Renderer_Department extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $list_id = $row->getData($this->getColumn()->getIndex());
		$departments = Mage::getSingleton('retailedge/api_client')->getDepartments();
		return $departments[$list_id];
    }
}

?>