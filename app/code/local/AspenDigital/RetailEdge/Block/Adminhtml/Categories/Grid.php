<?php

class AspenDigital_RetailEdge_Block_Adminhtml_Categories_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	protected $_defaultSort = 'department';
	protected $_defaultDir = 'asc';
	
    public function __construct()
    {
        parent::__construct();
        $this->setId('categories_grid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('retailedge/category_map_collection');
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('department',
            array(
                'header'    => $this->__('RetailEdge Department'),
                'index'     => 'department_name'
        ));

        $this->addColumn('category',
            array(
                'header'    => $this->__('Magento Category Path'),
                'index'     => 'category_name'
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id'=>$row->getId(), '_current'=>true));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}

?>