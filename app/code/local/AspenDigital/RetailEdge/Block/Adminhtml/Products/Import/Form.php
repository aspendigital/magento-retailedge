<?php
class AspenDigital_RetailEdge_Block_Adminhtml_Products_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

		$fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => $this->__('Import Options')
        ));

        $creationDate = $fieldset->addField('creation_date', 'date', array(
            'label'     => $this->__('Products Created After'),
			'title'     => $this->__('Products Created After'),
            'name'      => 'creation_date',
			'image'		=> $this->getSkinUrl('images/grid-cal.gif'),
			'format'	=> Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)
        ));

		$modifiedDate = $fieldset->addField('modified_date', 'date', array(
            'label'     => $this->__('Products Modified After'),
			'title'     => $this->__('Products Modified After'),
            'name'      => 'modified_date',
			'image'		=> $this->getSkinUrl('images/grid-cal.gif'),
			'format'	=> Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)
        ));

		$dryRun = $fieldset->addField('dry_run', 'checkbox', array(
			'label'		=> $this->__('Perform Dry Run'),
			'title'		=> $this->__('Perform Dry Run'),
			'name'		=> $this->__('dry_run'),
			'value'		=> 1
		));

        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setMethod('post');
        $form->setAction($this->getSaveUrl());
        $this->setForm($form);
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
