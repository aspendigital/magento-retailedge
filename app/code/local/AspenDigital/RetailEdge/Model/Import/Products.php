<?php

class AspenDigital_RetailEdge_Model_Import_Products
{
	protected $_creation_time = 0;
	protected $_modified_time = 0;
	protected $_dry_run = false;
	protected $_show_info = false;

	protected $_profile;


	public function import($model='', $run=true)
	{
		if (!$model)
			Mage::throwException('No conversion model given');

		$convert = Mage::getModel($model);
		if (!$convert)
			Mage::throwException(Mage::helper('retailedge')->__('Unable to load conversion model "%s"', $model));

		$this->_profile = $convert->getProfile('import_products_from_retailedge');
		if (!$this->_profile)
			Mage::throwException(Mage::helper('retailedge')->__('Unable to find profile "%s"', 'import_products_from_retailedge'));

		Mage::register('retailedge_product_created_time', $this->getCreationTime());
		Mage::register('retailedge_product_modified_time', $this->getModifiedTime());
		Mage::register('import_product_dry_run', $this->getDryRun());
		Mage::register('import_product_show_info', $this->getShowInfo());

		if ($run)
			$this->_profile->run();
		else
			Mage::register('current_convert_profile', Mage::getModel('retailedge/import_profile')->setProfile($this->_profile));

		return $this;
	}

	public function getExceptions()
	{
		return $this->_profile->getExceptions();
	}

	public function getCreationTime()
	{
		return $this->_creation_time;
	}

	public function setCreationTime($time)
	{
		$this->_creation_time = ($time >= 0) ? $time : 0;

		return $this;
	}

	public function getModifiedTime()
	{
		return $this->_modified_time;
	}

	public function setModifiedTime($time)
	{
		$this->_modified_time = ($time >= 0) ? $time : 0;

		return $this;
	}
	
	public function getDryRun()
	{
		return $this->_dry_run;
	}

	public function setDryRun($new)
	{
		$this->_dry_run = ($new) ? true : false;

		return $this;
	}

	public function getShowInfo()
	{
		return $this->_show_info;
	}

	public function setShowInfo($new)
	{
		$this->_show_info = ($new) ? true : false;

		return $this;
	}
}

?>
