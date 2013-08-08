<?php

class AspenDigital_RetailEdge_Model_Import_Categories
{
	protected $_category_id;
	protected $_add_as_active = false;
	protected $_import_type = 'all';

	protected $_messages = array();


	public function populateMapping()
	{
		$api_client = Mage::getSingleton('retailedge/api_client');
		$model = Mage::getModel('retailedge/category_map');
		$collection = $model->getCollection()->load();

		$departments = $api_client->getDepartments();
		$categories = Mage::helper('retailedge/functions')->getCategoryNames();

		// See what's already there and update department and category names
		foreach ($collection as $record)
		{
			$id = $record->getId();
			if (isset($departments[$id]))
			{
				$record->setDepartmentName($departments[$id]);
				if (isset($categories[$record->getCategoryId()]))
					$record->setCategoryName($categories[$record->getCategoryId()]);
				else
					$record->setCategoryName('');
				
				$record->save();
				unset($departments[$id]);
			}
			else
				$record->delete();
		}

		// Insert new records
		foreach ($departments as $list_id=>$name)
		{
			$model->setId($list_id);
			$model->setDepartmentName($name);
			$model->save();
		}

		return $this;
	}

	public function import()
	{
		$root_cat_id = $this->getCategoryId();
		if ($root_cat_id <= 0)
			Mage::throwException(Mage::helper('retailedge')->__('No category set'));
		$category = Mage::getModel('catalog/category')->load($root_cat_id);
		if ($category->getId() != $root_cat_id)
			Mage::throwException(Mage::helper('retailedge')->__('Unable to load root category'));
		$category_path = join('/', $category->getPathIds());

		$this->populateMapping();

		// Read in current store categories
		$category_api = Mage::getModel('catalog/category_api_v2');
		$tree = $category_api->tree($root_cat_id);
		$current_map = $this->_getNames($tree);

		// Get current department mapping
		$collection = Mage::getModel('retailedge/category_map')->getCollection()->load();

		$messages = array(
				'info'=>array(),
				'error'=>array(),
				'success'=>array()
			);

		$category = Mage::getModel('catalog/category')
            ->setStoreId(0);

        $category->setAttributeSetId($category->getDefaultAttributeSetId());

		foreach ($collection as $record)
		{
			$name = ucwords(strtolower($record->getDepartmentName()));

			if (isset($current_map[strtolower($name)]))
			{
				$messages['notice'][] = Mage::helper('retailedge')->__("Category already exists: skipping department '%s'", $name);
				continue;
			}

			if ($this->_import_type != 'all' && $record->getCategoryId() > 0)
			{
				$messages['notice'][] = Mage::helper('retailedge')->__("Only importing unmapped departments: skipping department '%s'", $name);
				continue;
			}

			try {
				$data = array(
						'name'=>$name,
						'parent_id'=>$root_cat_id,
						'is_active'=>$this->getAddAsActive(),
						'path'=>$category_path
					);

				$category->setData($data);
				$category->save();
				
				// Update mapping
				$record->setCategoryName($name);
				$record->setCategoryId($category->getId());
				$record->save();
				
				$messages['success'][] = Mage::helper('retailedge')->__("Added category '%s': ID #%d", $name, $category->getId());
			} catch(Mage_Api_Exception $e) {
				$messages['error'][] = Mage::helper('retailedge')->__("Error adding category '%s': %s", $name, $e->getCustomMessage());
			}	catch (Exception $e) {
				$messages['error'][] = Mage::helper('retailedge')->__("Error adding category '%s': %s", $name, $e->getMessage());
			}
		}

		$this->_messages = $messages;
		return $this;
	}

	protected function _getNames($node, $first=true)
	{
		$name_map = array();
		
		if (!$first)
			$name_map[strtolower($node['name'])] = 1;
		
		foreach ($node['children'] as $child)
			$name_map = $name_map + $this->_getNames($child, false);

		return $name_map;
	}

	public function getMessages()
	{
		return $this->_messages;
	}

	public function setCategoryId($new=0)
	{
		$this->_category_id = $new;

		return $this;
	}

	public function getCategoryId()
	{
		return $this->_category_id;
	}

	public function setImportType($new='')
	{
		$this->_import_type = ($new === 'unmatched') ? $new : 'all';

		return $this;
	}

	public function getImportType()
	{
		return $this->_import_type;
	}

	public function setAddAsActive($new)
	{
		$this->_add_as_active = ($new) ? true : false;

		return $this;
	}

	public function getAddAsActive()
	{
		return $this->_add_as_active;
	}
}

?>
