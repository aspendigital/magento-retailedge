<?php

class AspenDigital_RetailEdge_Model_Observer
{
	public function checkoutSuccess(Varien_Event_Observer $observer)
	{
		$order_id = Mage::getSingleton('checkout/session')->getLastOrderId();

		// Not sure if this really happens, but the Google Analytics observer
		// makes it seem like something that could, so we'll log it if it happens
		if (!$order_id)
		{
			Mage::log('No order ID in RetailEdge checkoutSuccess observer', Zend_Log::WARN);
			return;
		}

		$order = Mage::getModel('sales/order')->load($order_id);

		try {
			Mage::getSingleton('retailedge/api_client')->postOrder($order);
		} catch (Exception $e) {
			Mage::logException($e);
		}

	}
}

?>
