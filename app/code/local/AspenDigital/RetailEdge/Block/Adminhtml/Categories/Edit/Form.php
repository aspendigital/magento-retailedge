<?php
class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $department = $this->getDepartment();

		$fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => $this->__('Map to Category')
        ));

        $categorySelect = $fieldset->addField('select_category', 'select', array(
            'label'     => $this->__('Category'),
            'title'     => $this->__('Category'),
            'name'      => 'category',
            'required'  => false,
            'options'   => $this->_getCategoriesArray(),
			'value'		=> $department->getCategoryId()
        ));

        $form->addValues($department->getData());
        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setMethod('post');
        $form->setAction($this->getSaveUrl());
        $this->setForm($form);
    }

    protected function _getCategoriesArray()
    {
		return array(0=>$this->__('No Mapping')) + Mage::helper('retailedge/functions')->getCategoryNames();
    }

    public function getDepartment()
    {
        return Mage::registry('current_department');
    }

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('retailedge_list_id' => $this->getDepartment()->getId()));
    }
}

?>
