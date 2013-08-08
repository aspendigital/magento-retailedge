<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Import extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'retailedge';
        $this->_controller = 'adminhtml_categories';
        $this->_mode = 'import';
        $this->_removeButton('reset');
        $this->_updateButton('save', 'label', $this->__('Import Departments'));
		$this->_removeButton('delete');
		$this->_removeButton('back');
    }

    public function getHeaderText()
    {
		return $this->__('Import RetailEdge Departments as Magento Categories');
    }

}


?>
