<?php

// Wrapper to provide dataflow/profile functions using convert class (so we can take advantage of system import block)
class AspenDigital_RetailEdge_Model_Import_Profile
{
	protected $_profile;

	function setProfile($profile)
	{
		$this->_profile = $profile;
		return $this;
	}

	function getId()
	{
		return true;
	}

	function getName()
	{
		return Mage::helper('retailedge')->__('RetailEdge Product Import');
	}

	function run()
	{
		return $this->_profile->run();
	}

	function getExceptions()
	{
		return $this->_profile->getExceptions();
	}

	function getEntityType()
	{
		return 'product';
	}

	function getDirection()
	{
		return 'import';
	}
}

?>
