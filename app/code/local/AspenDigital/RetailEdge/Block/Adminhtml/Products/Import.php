<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Products_Import extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'retailedge';
        $this->_controller = 'adminhtml_products';
        $this->_mode = 'import';
        $this->_removeButton('reset');
        $this->_updateButton('save', 'label', $this->__('Import Products'));
		//$this->_updateButton('save', 'onclick', 'popup();');
		$this->_removeButton('delete');
		$this->_removeButton('back');
    }

	/*
	// Should probably be in a template, but since it's pretty simple, it's not that bad
	public function  getFormHtml()
	{
		return parent::getFormHtml() .

'<script type="text/javascript">
function popup()
{
	$(editForm.formId).target = \'_blank\';
	console.log($(editForm.formId));
}
</script>';

	}
	 * 
	 */

    public function getHeaderText()
    {
		return $this->__('Import RetailEdge Products into Magento');
    }

}

?>
