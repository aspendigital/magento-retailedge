<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'retailedge';
        $this->_controller = 'adminhtml_categories';
        $this->_mode = 'edit';
        $this->_removeButton('reset');
        $this->_updateButton('save', 'label', $this->__('Save Mapping'));
		$this->_removeButton('delete');
    }

    public function getHeaderText()
    {
		return $this->__('Edit Department "%s"', $this->htmlEscape(Mage::registry('current_department')->getDepartmentName()));
    }

}


?>
