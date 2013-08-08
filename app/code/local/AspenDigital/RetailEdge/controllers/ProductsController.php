<?php

class AspenDigital_RetailEdge_ProductsController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
    {
        $this->loadLayout()
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Catalog'), Mage::helper('adminhtml')->__('Catalog'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('RetailEdge POS'), Mage::helper('adminhtml')->__('RetailEdge POS'))
            ->_setActiveMenu('catalog/retailedge/products');
        return $this;
    }

	function setupImportAction()
	{
		$this->_title($this->__('Catalog'))
			->_title($this->__('RetailEdge POS'))
			->_title($this->__('Import Products'));

		$this->_initAction()
			->_addBreadcrumb(Mage::helper('retailedge')->__('Import Products'), Mage::helper('retailedge')->__('Import Products'))
			->_addContent($this->getLayout()->createBlock('retailedge/adminhtml_products_import'))
			->renderLayout();
    }

	public function importAction()
	{
		$request = $this->getRequest();

		// We have to check for blank date values, as timestamp() won't handle those
		$times = array('creation'=>0, 'modified'=>0);
		foreach ($times as $index=>$value)
		{
			$date = $request->getParam("{$index}_date");
			if (!empty($date))
				$times[$index] = Mage::getSingleton('core/date')->timestamp($date);
		}

		Mage::getModel('retailedge/import_products')
			->setCreationTime($times['creation'])
			->setModifiedTime($times['modified'])
			->setDryRun($request->getParam('dry_run'))
			->setShowInfo(true)
			->import($this->getImportModel(), false); // Do not automatically run profile -- will run via block
		
		$this->loadLayout('adminhtml_system_convert_gui_run');
		$this->renderLayout();
	}

	protected function getImportModel()
	{
		return '';
	}
}

?>
