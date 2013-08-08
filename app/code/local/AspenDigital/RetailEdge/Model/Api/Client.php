<?php

class AspenDigital_RetailEdge_Model_API_Client
{
	protected $uri;
	protected $db_name;
	protected $verify_ssl;
	protected $clerk_id;
	protected $clerk_password;

	protected $last_request;
	protected $last_response;
	protected $errors = array();

	protected $_departments;
	protected $_classes;
	protected $_tax_default_ids;
	protected $_payment_methods;
	protected $_clerk_list_id;
	protected $curl_timeout = 60;


	public function __construct()
	{
		$config = Mage::getStoreConfig('retailedge/api');

		$this->uri = 'https://' . $config['server_url'] . '/RetailEdge_API_Request';
		$this->db_name = $config['db_name'];
		$this->verify_ssl = $config['verify_ssl'];
		$this->clerk_id = $config['clerk_id'];
		$this->clerk_password = $config['clerk_password'];
	}

	public function getDepartments()
	{
		if (!$this->_departments)
		{
			$response = $this->_request('<Function><Name>Depart_Get</Name><Params/></Function>');

			$departments = array();
			foreach ($response->Table->Record as $record)
			{
				$id = (string) $record->Dept_ListID;
				if ($id != '-1')
					$departments[$id] = (string) $record->Dept_Name;
			}

			natcasesort($departments);
			$this->_departments = $departments;
		}

		return $this->_departments;
	}

	public function getClasses()
	{
		if (!$this->classes)
		{
			$response = $this->_request('<Function><Name>Class_Get</Name><Params/></Function>');

			$classes = array();
			foreach ($response->Table->Record as $record)
			{
				$id = (string) $record->Cla_ListID;
				if ($id != '-1')
					$classes[$id] = (string) $record->Cla_Name;
			}

			$this->_classes = $classes;
		}

		return $this->_classes;
	}

	protected function _productParamXML($params)
	{
		$where_conditions = array();

		if (isset($params['export_code']))
			$where_conditions[] = "Inv_ExpCode LIKE '" . $params['export_code'] . "'";
		foreach (array('created'=>'Inv_Created', 'modified'=>'Inv_Modified') as $index=>$xml_var)
		{
			if (!empty($params[$index]))
				$where_conditions[] = "$xml_var >= CAST('" . Mage::getSingleton('core/date')->gmtDate('Y-m-d h:i:s a', $params[$index]) . "' AS timestamp)";
		}

		if (!empty($where_conditions))
			$param_xml = '<Where>' . join(' AND ', $where_conditions) . '</Where>';

		if (isset($params['quicklist']))
			$param_xml .= "<QuickList>TRUE</QuickList>";

		if (isset($params['raw'])) // Allow raw XML to make certain tests easier
			$param_xml .= $params['raw'];

		$param_xml .= '<LocALL>TRUE</LocALL>';

		return $param_xml;
	}

	public function getProducts($params)
	{
		// Used for quicker testing
		if (empty($params) && file_exists('import-products.cache'))
		{
			return unserialize(file_get_contents('import-products.cache'));
		}

		$products = array();
		$response = $this->_request('<Function><Name>Inv_Get</Name><Params>'.$this->_productParamXML($params).'</Params></Function>');

		if (isset($params['quicklist'])) // Quick List
		{
			foreach ($response->Table->Record as $record)
				$products[(string) $record->Inv_ListID] = (string) $record->Inv_Name;
			unset($products['-1']);
		}
		else // Full Info
		{
			$quantities = $this->getQuantities($params);

			foreach ($response->Table->Record as $record)
			{
				$id = (string) $record->Inv_ListID;

				$products[] = array(
						'name'=>(string) $record->Inv_Name,
						'description'=>(string) $record->Inv_Desc,
						'vendor'=>(string) $record->Inv_Part_Vendor,
						'manufacturer'=>(string) $record->Inv_Part_Mfr,
						'list_id'=>$id,
						'item_id'=>(string) $record->Inv_ItemID,
						'department_id'=>(string) $record->Inv_DepartID,
						'class_id'=>(string) $record->Inv_ClassID,
						'price1'=> (string) $record->Inv_Loc_Price1,
						'price2'=> (string) $record->Inv_Loc_Price2,
						'price3'=> (string) $record->Inv_Loc_Price3,
						'price4'=> (string) $record->Inv_Loc_Price4,
						'price5'=> (string) $record->Inv_Loc_Price5,
						'user1'=> (string) $record->Inv_User1,
						'user2'=> (string) $record->Inv_User2,
						'user3'=> (string) $record->Inv_User3,
						'user4'=> (string) $record->Inv_User4,
						'user5'=> (string) $record->Inv_User5,
						'notes1'=> (string) $record->Inv_Notes1,
						'notes2'=> (string) $record->Inv_Notes2,
						'created'=> (string) $record->Inv_Created,
						'modified'=> (string) $record->Inv_Modified,
						'export_code'=> (string) $record->Inv_ExpCode,
						'weight'=> (string) $record->Inv_Weight,
						'stock'=> (string) $record->Inv_Type_Stock,
						'quantity'=> (isset($quantities[$id])) ? $quantities[$id] : 0
					);
			}
		}

		return $products;
	}

	public function getClerkListID()
	{
		if (!$this->_clerk_list_id)
		{
			$response = $this->_request("<Function><Name>Clerk_Get</Name><Params><Where>upper(Clk_Name) = '" . htmlspecialchars($this->clerk_id) . "'</Where></Params></Function>");
			$this->_clerk_list_id = (string) $response->Table->Record->Clk_ListID;
		}

		return $this->_clerk_list_id;
	}

	// Return array of tax IDs -- this would require more thought if our clients had multiple locations in different tax jurisdictions,
	//   but for now we'll just grab what's there for all locations
	public function getDefaultTaxIDs()
	{
		if (!$this->_tax_default_ids)
		{
			$response = $this->_request('<Function><Name>Tax_Get_DefaultList</Name><Params/></Function>');
			$this->_tax_default_ids = explode(',', $response->Value);
		}
		
		return $this->_tax_default_ids;
	}

	// Same as previous function -- we don't currently take multiple locations into account
	public function getPaymentMethods()
	{
		if (!$this->_payment_methods)
		{
			$this->_payment_methods = array();
			$response = $this->_request('<Function><Name>Payment_Get_List</Name><Params/></Function>');
			foreach ($response->Table->Record as $record)
				$this->_payment_methods[(string) $record->Pmt_ListID] = (string) $record->Pmt_Name;
		}

		return $this->_payment_methods;
	}

	public function mapCCTypeToRetailEdgeID($type)
	{
		if (empty($type))
			return '';

		$method_map = array_flip($this->getPaymentMethods());
		$type_map = array('VI'=>'VISA', 'MC'=>'MC', 'AE'=>'AMEX', 'DI'=>'DISC');
		if (array_key_exists('VISA-MC', $method_map))
			$type_map['VI'] = $type_map['MC'] = 'VISA-MC';

		if (!array_key_exists($type, $type_map))
			return '-1';
		return $method_map[ $type_map[$type] ];
	}

	public function postOrder($order)
	{
		$tax_ids = $this->getDefaultTaxIDs();
		$tax_id = $tax_ids[0]; // Take first tax option

		$shipping_address = $order->getShippingAddress();
		$payment_methods = $this->getPaymentMethods();

		$item_xml = '';
		foreach($order->getItemsCollection() as $item)
		{
			$product = Mage::getModel('catalog/product')->load($item->getProductId());
			if (!$product->getId()) // If the product ID doesn't work, see if we can find this item by SKU
				$product->load($product->getIdBySku($item->sku));
			
			if (!$product->getRetailedgeListId() || $product->getRetailedgeListId() == '-1')
				continue;

			// We add quantity to ship to quantity shipped just in case this is happening after the fact and the
			//  order has already been processed and items shipped
			$quantity = $item->getQtyToShip() + $item->getQtyShipped();
			if ($quantity <= 0) // Canceled or refunded
				continue;

			$item_xml .= "
<Record>
	<Sal_Itm_ItemID>".($product->getRetailedgeListId())."</Sal_Itm_ItemID>
	<Sal_Itm_Desc>".htmlspecialchars($item->getName())."</Sal_Itm_Desc>
	<Sal_Itm_Quan>".$quantity."</Sal_Itm_Quan>
	<Sal_Itm_Price_Unit>".$item->getBasePrice()."</Sal_Itm_Price_Unit>
	<Sal_Itm_Price_Ext>".$item->getBasePrice()."</Sal_Itm_Price_Ext>
	<Sal_Itm_Taxable>" . (($item->getTaxAmount() > 0) ? 'true' : 'false') . "</Sal_Itm_Taxable>
</Record>
";
		}

		if (empty($item_xml))
			return;

		
		// RetailEdge has to have something for the payment type, so map PayPal payments to VISA
		if ($order->getPayment()->getMethod() == 'paypal_express')
			$payment_type_id = $this->mapCCTypeToRetailEdgeID('VI');
		else
			$payment_type_id = $this->mapCCTypeToRetailEdgeID($order->getPayment()->getCcType());
		
		// Date is already GMT
		$xml = "
<Function>
	<Name>Sale_Post_Sale</Name>
	<Params>
		<Sal_Date>" . Mage::getSingleton('core/date')->date('Y-m-d h:i:s a', $order->getCreatedAt()) . "</Sal_Date>
		<Sal_Total_Sub>".$order->getSubtotal()."</Sal_Total_Sub>
		<Sal_Total_Tax>".$order->getTaxAmount()."</Sal_Total_Tax>
		<Sal_Clerk_ID>".$this->getClerkListID()."</Sal_Clerk_ID>
		<Tax_List>$tax_id</Tax_List>
		<IsShipped>true</IsShipped>
		<ShipTo>
			<Sal_Ship_S_LastName>".htmlspecialchars($shipping_address->getFirstname())."</Sal_Ship_S_LastName>
			<Sal_Ship_S_FirstName>".htmlspecialchars($shipping_address->getLastname())."</Sal_Ship_S_FirstName>
			<Sal_Ship_S_Comp>".htmlspecialchars($shipping_address->getCompany())."</Sal_Ship_S_Comp>
			<Sal_Ship_S_S1>".htmlspecialchars($shipping_address->getStreet1())."</Sal_Ship_S_S1>
			<Sal_Ship_S_S2>".htmlspecialchars($shipping_address->getStreet2())."</Sal_Ship_S_S2>
			<Sal_Ship_S_City>".htmlspecialchars($shipping_address->getCity())."</Sal_Ship_S_City>
			<Sal_Ship_S_State>".htmlspecialchars($shipping_address->getRegionCode())."</Sal_Ship_S_State>
			<Sal_Ship_S_Country>".htmlspecialchars($shipping_address->getCountry())."</Sal_Ship_S_Country>
			<Sal_Ship_S_PostalCode>".htmlspecialchars($shipping_address->getPostcode())."</Sal_Ship_S_PostalCode>
			<Sal_Ship_S_Phone>".htmlspecialchars($shipping_address->getTelephone())."</Sal_Ship_S_Phone>
		</ShipTo>
		<Table_Items>$item_xml</Table_Items>
		<Table_Pmt>
			<Record>
				<Sal_Pmt_PmtID>".$payment_type_id."</Sal_Pmt_PmtID>
				<Sal_Pmt_Amount>".$order->getGrandTotal()."</Sal_Pmt_Amount>
			</Record>
		</Table_Pmt>
	</Params>
</Function>";

		$response = $this->_request($xml);
	}

	public function getQuantities($params, $by_location = false)
	{
		$quantities = array();
		$response = $this->_request('<Function><Name>Inv_Get_Quan_OnHand</Name><Params>'.$this->_productParamXML($params).'</Params></Function>');
		if ($by_location)
		{
			foreach ($response->Table->Record as $record)
				$quantities[(string) $record->Inv_ListID] = (int) $record->Inv_Quan_Quan;
		}
		else
		{
			foreach ($response->Table->Record as $record)
			{
				$id = (string) $record->Inv_ListID;
				if (!isset($quantities[$id]))
					$quantities[$id] = 0;
				$quantities[$id] += (int) $record->Inv_Quan_Quan;
			}
		}

		return $quantities;
	}

	// Return SimpleXML object or throw an exception
	protected function _request($function_list_str)
	{
		$this->errors = array();

		$this->last_request = '<?xml version="1.0"?>
<RetailEdge_Request>
	<Settings>
		<DatabaseName>' . $this->db_name . '</DatabaseName>
		<ClerkID>' . $this->clerk_id . '</ClerkID>
		<Password>' . $this->clerk_password . '</Password>
	</Settings>
	<FunctionList>'.
	$function_list_str .
	'</FunctionList>
</RetailEdge_Request>';

		// Send request via HTTPS
		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->uri,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => 'RequestText=' . urlencode($this->last_request), // If an array is passed, CURL sets multipart/form-data, which the API server doesn't understand
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT=>$this->curl_timeout,

				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => $this->verify_ssl
			));
		$this->last_response = curl_exec($curl);

		if (curl_errno($curl))
			$this->throwError(Mage::helper('retailedge')->__("CURL Error: %s", curl_error($curl)));

		libxml_use_internal_errors(true);
		// The API server apparently doesn't handle any text encoding, so we need to make sure we have UTF-8
		$xml = simplexml_load_string(utf8_encode($this->last_response), "SimpleXMLElement", LIBXML_COMPACT);
		if ($xml === false)
		{
			foreach (libxml_get_errors() as $error)
				$this->throwError(Mage::helper('retailedge')->__("XML Error: %s (Line: %d, Column: %d)", $error->message, $error->line, $error->column));
		}

		if (!isset($xml->ResponseList->Response))
			$this->throwError(Mage::helper('retailedge')->__('Error parsing response: no Response tag'));

		$response = $xml->ResponseList->Response;
		if ($response->Error_Code != 0)
			$this->throwError(Mage::helper('retailedge')->__("API Error (#%s) %s", $response->Error_Code, $response->Error_Message));

		return $response;
	}

	public function getLastRequest()
	{
		return $this->last_request;
	}

	public function getLastResponse()
	{
		return $this->last_response;
	}

	public function setTimeout($new_timeout=0)
	{
		$this->curl_timeout = $new_timeout;
		return $this;
	}

	protected function throwError($msg)
	{
		$exception = new Mage_Dataflow_Model_Convert_Exception($msg);
		$exception->setLevel(Mage_Dataflow_Model_Convert_Exception::ERROR);
		throw $exception;
	}
}

?>
