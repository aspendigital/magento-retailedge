<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Categories extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'retailedge';
        $this->_controller = 'adminhtml_categories';
        $this->_headerText = Mage::helper('retailedge')->__('Manage Department <-> Category Mapping');
        parent::__construct();
		$this->_removeButton('add'); // parent constructor adds it, but we don't need an add button
    }
}

?>
