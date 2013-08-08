<?php

Class AspenDigital_RetailEdge_CategoriesController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
    {
        $this->loadLayout()
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Catalog'), Mage::helper('adminhtml')->__('Catalog'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('RetailEdge POS'), Mage::helper('adminhtml')->__('RetailEdge POS'))
            ->_setActiveMenu('catalog/retailedge/categories');
        return $this;
    }

	// Populate category mapping database with RetailEdge departments
	protected function _populateDepartments()
	{
		try
		{
			Mage::getModel('retailedge/import_categories')->populateMapping();
		}
		catch (Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		}

		return $this;
	}

	public function indexAction()
    {
		$this->_title($this->__('Catalog'))
			->_title($this->__('RetailEdge POS'))
			->_title($this->__('Map Categories'));

        $this->_populateDepartments()
			->_initAction()
			->_addBreadcrumb(Mage::helper('retailedge')->__('Category Mapping'), Mage::helper('retailedge')->__('Category Mapping'))
			->_addContent($this->getLayout()->createBlock('retailedge/adminhtml_categories'))
			->renderLayout();
    }

	public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('retailedge/adminhtml_categories_grid')->toHtml()
        );
    }

	public function editAction()
    {
        $this->_title($this->__('Catalog'))
			->_title($this->__('RetailEdge POS'))
			->_title($this->__('Map Categories'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('retailedge/category_map');

        try {
            if ($id) {
                $model->load($id);
            }

            $this->_title($this->__('Edit Department'));

            Mage::register('current_department', $model);
			$helper = Mage::helper('retailedge');

            $this->_initAction()
                ->_addBreadcrumb($helper->__('Edit Department'), $helper->__('Edit Department'))
                ->_addContent($this->getLayout()->createBlock('retailedge/adminhtml_categories_edit'))
                ->renderLayout();
        }
		catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/index');
        }
    }

	public function saveAction()
    {
        $mappingModel = Mage::getModel('retailedge/category_map');
        $id = $this->getRequest()->getParam('retailedge_list_id');

		$mappingModel->load($id);
		if (is_null($mappingModel->getId()))
		{
			$this->_getSession()->addError('Unable to load record for save');
            $this->_redirect('*/*/index');
        }

        try {
			$mappingModel->setCategoryId($this->getRequest()->getParam('category'));
			$mappingModel->save();

            $this->_getSession()->addSuccess(Mage::helper('retailedge')->__('The department mapping has been saved.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

	public function setupImportAction()
	{
		$this->_title($this->__('Catalog'))
			->_title($this->__('RetailEdge POS'))
			->_title($this->__('Import Departments'));

		$helper = Mage::helper('retailedge');

		$this->_initAction()
                ->_addBreadcrumb($helper->__('Import Departments'), $helper->__('Import Departments'))
                ->_addContent($this->getLayout()->createBlock('retailedge/adminhtml_categories_import'))
                ->renderLayout();
	}

	public function importAction()
	{
		$this->_title($this->__('Catalog'))
			->_title($this->__('RetailEdge POS'))
			->_title($this->__('Import Departments'));

		$request = $this->getRequest();

		$messages = array();
		try
		{
			$messages = Mage::getModel('retailedge/import_categories')
				->setCategoryId($request->getParam('category'))
				->setAddAsActive($request->getParam('add_as_active'))
				->setImportType($request->getParam('import_type'))
				->import()
				->getMessages();
		} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
		}

		foreach (array('notice'=>'addNotice', 'success'=>'addSuccess', 'warning'=>'addWarning', 'error'=>'addError') as $type=>$func)
		{
			if (!empty($messages[$type]))
			{
				$list = '<ul><li>' . join('</li><li>', array_map('htmlspecialchars', $messages[$type])) . '</li></ul>';
				$this->_getSession()->$func($list);
			}
		}

		$this->_initAction()
				->renderLayout();
	}
}

?>
