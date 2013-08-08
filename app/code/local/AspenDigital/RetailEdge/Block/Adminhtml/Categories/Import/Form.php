<?php
class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

		$fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => $this->__('Import Options')
        ));

        $categorySelect = $fieldset->addField('select_category', 'select', array(
            'label'     => $this->__('Root Category'),
            'title'     => $this->__('Root Category'),
            'name'      => 'category',
            'required'  => true,
            'values'   => $this->_getCategoriesArray()
        ));

		$typeSelect = $fieldset->addField('select_import_type', 'select', array(
			'label'		=> $this->__('Filter'),
			'title'		=> $this->__('Filter'),
			'name'		=> 'import_type',
			'options'	=> $this->_getFilterArray(),
			'note'		=> $this->__("If importing all departments, any existing mapping will be removed, and the department will be mapped to the newly-created category")
		));

		$addAsSelect = $fieldset->addField('select_add_as', 'select', array(
			'label'		=> $this->__('Add Categories As'),
			'title'		=> $this->__('Add Categories As'),
			'name'		=> 'add_as_active',
			'options'	=> $this->_getAddAsOptionsArray(),
			'value'		=> 1
		));


        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setMethod('post');
        $form->setAction($this->getSaveUrl());
        $this->setForm($form);
    }

    protected function _getCategoriesArray()
    {
		return array(''=>'Select One') + Mage::helper('retailedge/functions')->getCategoryNames();
    }

	protected function _getFilterArray()
	{
		return array('all'=>'Import all departments', 'unmatched'=>'Only import currently unmapped departments');
	}

	protected function _getAddAsOptionsArray()
	{
		return array(1=>'Active', 0=>'Inactive');
	}

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/import');
    }
}

?>
