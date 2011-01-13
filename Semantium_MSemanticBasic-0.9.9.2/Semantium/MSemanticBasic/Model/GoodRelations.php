<?php
/**
 * MSemanticBasic Magento Extension
 * @package Semantium_MSemanticBasic
 * @copyright (c) 2010 Semantium, Uwe Stoll <stoll@semantium.de>
 * @author Michael Lambertz <michael@digitallifedesign.net>
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>
**/
class Semantium_MSemanticBasic_Model_GoodRelations extends Mage_Core_Model_Abstract
{
	private $PAYMENT_METHODS = array (
		"mastercard"			=> "http://purl.org/goodrelations/v1#MasterCard",
		"paypal"				=> "http://purl.org/goodrelations/v1#PayPal",
		"directdebit"			=> "http://purl.org/goodrelations/v1#DirectDebit",
		"discover"				=> "http://purl.org/goodrelations/v1#Discover",
		"americanexpress"		=> "http://purl.org/goodrelations/v1#AmericanExpress",
		"banktransferinadvance"	=> "http://purl.org/goodrelations/v1#ByBankTransferInAdvance",
		"openinvoice"			=> "http://purl.org/goodrelations/v1#ByInvoice",
		"visa"					=> "http://purl.org/goodrelations/v1#VISA",
		"checkinadvance"		=> "http://purl.org/goodrelations/v1#CheckInAdvance",
		"cod"					=> "http://purl.org/goodrelations/v1#COD",
		"cash"					=> "http://purl.org/goodrelations/v1#Cash",
		"dinersclub"			=> "http://purl.org/goodrelations/v1#DinersClub"
	);
	private $DELIVERY_METHODS = array(
		"dhl"					=> "http://purl.org/goodrelations/v1#DHL",
		"ups"					=> "http://purl.org/goodrelations/v1#UPS",
		"mail"					=> "http://purl.org/goodrelations/v1#DeliveryModeMail",
		"fedex"					=> "http://purl.org/goodrelations/v1#FederalExpress",
		"directdownload"		=> "http://purl.org/goodrelations/v1#DeliveryModeDirectDownload",
		"pickup"				=> "http://purl.org/goodrelations/v1#DeliveryModePickUp",
		"vendorfleet"			=> "http://purl.org/goodrelations/v1#DeliveryModeOwnFleet",
		"freight"				=> "http://purl.org/goodrelations/v1#DeliveryModeFreight"
	);
	private $CUSTOMER_TYPES = array (
		"enduser"				=> "http://purl.org/goodrelations/v1#Enduser",
		"reseller"				=> "http://purl.org/goodrelations/v1#Reseller",
		"business"				=> "http://purl.org/goodrelations/v1#Business",
		"publicinstitution"		=> "http://purl.org/goodrelations/v1#PublicInstitution"
	);
	
	private $BUSINESS_FUNCTIONS = array(
		"sell"					=> "http://purl.org/goodrelations/v1#Sell"
	);
	
	protected $_data = array();				// for vCards etc.
	protected $_URI = array();				// stores arrays of URIs
	protected $_attributeCodes = array();	// attribute codes of the product (ean, description...)
	
	// Cache
	protected $cacheAddressVCard = NULL;	// VCard object cache
	protected $cachePOSAddressVCard = NULL;	// VCard object cache
	
	// Models
	private $RDFS = NULL;
	private $VCARD = NULL;
	private $Business = NULL;	// Business Model
	// Helper
	public $rdffClassName = "";	
	private $rdff = NULL;		// RDF Format Helper
	private $div = NULL;		// DIV functions
	private $sysinfo = NULL;	// Magento System Information Helper
	
	public function __call($method, $args)
	{
		$output = "GR->". $method." (";
		foreach ($args as $arg)
		{
			$output .= $arg.",";
		}
		$output .= ")";
		webdirx_div::message($output);
	}
	
	protected function _construct()
	{
		$this->_init('msemanticbasic/goodrelations');
		
		// helper
		$this->div = Mage::app()->getHelper('msemanticbasic/Div');
		$this->sysinfo = Mage::app()->getHelper('msemanticbasic/Sysinfo');
		// other ontologies
		$this->RDFS = Mage::getModel('msemanticbasic/RDFs');
		$this->VCARD = Mage::getModel('msemanticbasic/VCard');
	}
	
	/**
	 * rdf format helper
	 * @param $rdfformatHelpe string
	 * @return unknown_type
	 */
	public function setRdff($rdffClassName)
	{
		$this->rdffClassName = $rdffClassName;
		$this->rdff = Mage::app()->getHelper($this->rdffClassName);
		$this->RDFS->setRdff($this->rdffClassName);
		$this->VCARD->setRdff($this->rdffClassName);
	}
	
	/**
	 * connect to business
	 * @param $business object
	 * @return self
	 */
	public function setBusiness($business)
	{
		$this->Business = $business;
		$this->initBusinessURIs();
		$this->initBusinessData();	// creates all necessary data (e.g. vcard code)
		return $this;
	}
	public function getBusiness()
	{
		return $this->Business;
	}
	/**
	 * connect to product
	 * @param $product object
	 * @return selft
	 */
	public function setProduct($product)
	{
		$this->Product = $product;
		$this->initProductURIs();
		$this->initProductAttributeCodes();
		$this->initProductData();	//creates all necessary data
		return $this;
	}
	public function getProduct()
	{
		return $this->Business;
	}
	
	//setter for address vcard code
	public function setAddressVCard($val)
	{
		$this->_data['addressvcard'] = $val;
		return $this;
	}
	public function getAddressVCard()
	{
		return $this->_data['addressvcard'];
	}
	// setter for pos address vcard code
	public function setPOSAddressVCard($val)
	{
		$this->_data['posaddressvcard'] = $val;
		return $this;
	}
	public function getPOSAddressVCard()
	{
		return $this->_data['posaddressvcard'];
	}
	
	public function setURI($key, $val)
	{
		$this->_URI[$key] = $val;
		return $this;
	}
	public function getURI($key)
	{
		return $this->_URI[$key];
	}
	
	public function setAttributeCode($what, $val)
	{
		$this->_attributeCodes[$what] = $val;
		return $this;
	}
	public function getAttributeCode($what)
	{
		return $this->_attributeCodes[$what];
	}
	public function getReplacements()
	{
		$replacements = array(
			"{lang}"	=> $this->sysinfo->getLocaleCode()
		);
		return $replacements;
	}
	
	
	
	protected function initBusinessData()
	{
		$this->initAddressVCard();
		$this->initPOSAddressVCard();
	}
	
	/**
	 * creates a vcard for business address
	 * @param $URI
	 * @return void
	 */
	protected function initAddressVCard( $URI=NULL )
	{
		if (!isset($URI)) $URI = $this->getURI("address");
		
		if (!isset($this->cacheAddressVCard))	// cachable
		{
			$address = $this->Business->getAddress();
			$this->cacheAddressVCard = $this->getEmptyVCard();
			$this->cacheAddressVCard	-> setStreetaddress ( @$address["streetaddress"] )
										-> setPostalcode ( @$address["postalcode"] )
										-> setLocality ( @$address["locality"] )
										-> setCountryname ( @$address["countryname"] )
										-> setTel ( @$address["tel"] )
										-> setEmail ( @$address["email"] )
										;
		}
		$addressVCardCode = $this->cacheAddressVCard->toXHTML( $URI );
		$this->setAddressVCard($addressVCardCode);
	}
	
	/**
	 * creates vcard for pos address
	 * @param $URI
	 * @return void
	 */
	protected function initPOSAddressVCard( $URI=NULL )
	{
		if (!$this->Business->hasPOS()) return;	// company has no POS
		if (!isset($URI)) $URI = $this->getURI("posAddress");
		
		if (!isset($this->cachePOSAddressVCard))	// cachable
		{
			$address = $this->Business->getPOSAddress();
			$this->cachePOSAddressVCard = $this->getEmptyVCard();
			$this->cachePOSAddressVCard	-> setStreetaddress ( @$address["streetaddress"] )
										-> setPostalcode ( @$address["postalcode"] )
										-> setLocality ( @$address["locality"] )
										-> setCountryname ( @$address["countryname"] )
										-> setTel ( @$address["tel"] )
										-> setEmail ( @$address["email"] )
										;
		}
		$POSAddressVCardCode = $this->cachePOSAddressVCard->toXHTML( $URI );
		$this->setPOSAddressVCard($POSAddressVCardCode);
	}
	
	/**
	 * returns a new vCard instance with our formater
	 * @return unknown_type
	 */
	protected function getEmptyVCard()
	{
		$vCardModel = Mage::getModel('msemanticbasic/VCard');
		$vCardModel->setRdff($this->rdffClassName);
		return $vCardModel;
	}
	
	protected function initBusinessURIs()
	{
		$this->setURI('businessEntity', $this->Business->getBaseURL() . '#businessEntity');
		$this->setURI('posAddress', $this->Business->getBaseURL() . "#shopaddress" );
		$this->setURI('address', $this->Business->getBaseURL() . "#address" );
		$this->setURI('locationOfSale', $this->Business->getBaseURL() . "#shop");
	}
	
	protected function initProductData()
	{
		return;
	}
	
	protected function initProductURIs()
	{
		$this->setURI('offering', $this->Product->getProductUrl(). '#offering_' . $this->Product->getID() );
		$this->setURI("unitPriceSpecification", $this->Product->getProductUrl() . "#UnitPriceSpecification_" . $this->Product->getID());
		$this->setURI("typeAndQuantityNode", $this->Product->getProductUrl() . "#TypeAndQuantityNode_" . $this->Product->getID());
		$this->setURI("product", "#product_" . $this->Product->getID());
	}
	
	protected function initProductAttributeCodes()
	{
		$this->setAttributeCode("ean", "gr_ean");
		$this->setAttributeCode("name_en", "gr_name_en");
		$this->setAttributeCode("description_en", "gr_description_en");
		$this->setAttributeCode("valid_through", "gr_valid_through");
	}
	
	/**
	 * ***********************************
	 *  public good relations functions
	 *  **********************************
	 *  
	 *  (1) Business
	 */
	
	public function businessEntity()
	{
		$statements = "";
		$statements .= $this->RDFS->seeAlso( "" );
		$statements .= $this->bLegalName();
		$statements .= $this->getAddressVCard();	// gets complete vcard Code
		$statements .= $this->VCARD->url($this->Business->getBaseURL());
		$statements .= $this->businessOffers();
		$statements .= $this->hasPOS();
		
		// about the Business Entity
		$grBusinessEntity = $this->rdff->wrapStatements($statements, '#businessEntity', "gr:BusinessEntity");
		
		return $grBusinessEntity;
	}
	
	protected function bLegalName()
	{
		//gr:legalName, rdfs:label, vcard:fn for gr:BusinessEntity
		$labelPropArray = array(
			"gr:legalName"	=> $this->Business->getLegalName(),
			"rdfs:label"	=> $this->Business->getLegalName(),
			"vcard:fn"		=> $this->Business->getLegalName()
		);
		$grLegalNameDefinitions = "";
		$grLegalNameDefinitions .= $this->rdff->properties($labelPropArray, $this->getReplacements());
		return $grLegalNameDefinitions;
	}
	
	protected function hasPOS()
	{
		if ($this->Business->hasPOS())
		{
			$statements = "";
			$statements .= $this->getPOSAddressVCard();
			
			$class = "gr:LocationOfSalesOrServiceProvisioning";
			$hasPOS = $this->rdff->wrapStatements($statements ,$this->getURI('locationOfSale') , $class);
			#rel
			$hasPOSRel = $this->rdff->wrapRel($hasPOS, "gr:hasPOS");
			return $hasPOSRel;
		}
		else return;
	}
	
	protected function businessOffers($suffix="")
	{
		$statements = "";
		$statements .= $this->RDFS->comment( $this->Business->getOfferingDescription(), $this->sysinfo->getLocaleCode() );
		$statements .= $this->RDFS->isDefinedBy( $this->Business->getBaseURL() );
		$statements .= $this->availableAtOrFrom();
		$statements .= $this->validFromThrough();
		//
		$statements .= $this->eligibleRegions();
		$statements .= $this->eligibleCustomerTypes();
		$statements .= $this->acceptedPaymentMethods();
		$statements .= $this->deliveryMethods();
		
		
		#about
		$grOfferingAbout = $this->Business->getBaseURL() . "#offering".$suffix;
		$grOfferingDescription = $this->rdff->wrapStatements($statements, $grOfferingAbout, "gr:Offering");
		
		$grOffering = $this->rdff->wrapRel($grOfferingDescription ,"gr:offers");
		
		return $grOffering;
	}
	
	protected function availableAtOrFrom()
	{
		$grAvailableAtOrFrom = $this->rdff->rel("gr:availableAtOrFrom", $this->getURI("locationOfSale"));
		return $grAvailableAtOrFrom;
	}
	
	protected function validFromThrough()
	{
		$properties = array(
			"gr:validFrom"		=> $this->div->dateToIso8601( $this->Business->getValidFrom() ),
			"gr:validThrough"	=> $this->div->dateToIso8601( $this->Business->getValidThrough() )
		);
		$validFromThrough = $this->rdff->properties($properties, $this->getReplacements());
		return $validFromThrough;
	}
	
	protected function eligibleRegions()
	{
		$countries = $this->Business->getAllowedCountries();
		$grEligibleRegions = "";
		if (!is_array($countries)) $grEligibleRegions = "";
		else
		{
			foreach ($countries as $countrycode)
			{
				$properties = array("gr:eligibleRegions" => $countrycode);
				$grEligibleRegions .= $this->rdff->properties($properties,$this->getReplacements());
			}
		}
		return $grEligibleRegions;
	}
	
	
	protected function eligibleCustomerTypes()
	{
		$grEligibleCustomerTypes = "";
		forEach ($this->Business->getCustomerTypes() as $cTypeCode)
		{
			$customerTypeRel = @$this->CUSTOMER_TYPES[$cTypeCode];
			$grEligibleCustomerTypes .= $this->rdff->rel("gr:eligibleCustomerTypes", $customerTypeRel);
		}
		return $grEligibleCustomerTypes;
	}
	
	protected function acceptedPaymentMethods()
	{
		$grAcceptedPaymentMethods = "";
		forEach ($this->Business->getPaymentMethods() as $pMCode)
		{
			$paymentMethodRel = @$this->PAYMENT_METHODS[$pMCode];
			$grAcceptedPaymentMethods .= $this->rdff->rel("gr:acceptedPaymentMethods", $paymentMethodRel);
		}
		return $grAcceptedPaymentMethods;
	}
	
	protected function deliveryMethods()
	{
		$deliveryMethods = $this->Business->getDeliveryMethods();
		$grAvailableDeliveryMethods = "";
		foreach ($deliveryMethods as $deliveryMethodCode)
		{
			$deliveryMethod = @$this->DELIVERY_METHODS[$deliveryMethodCode];
			$grAvailableDeliveryMethods .= $this->rdff->rel("gr:availableDeliveryMethods", $deliveryMethod);
		}
		return $grAvailableDeliveryMethods;
	}
	
	/**
	 * (2) Products
	 */
	// for just 1 product on a page
	// ------------------------------------------------------------------------------------------------------------------------
	
	public function pOffering()
	{
		$statements = "";
		$grOffering = "";
		$statements .= $this->pOffers();
		$statements .= $this->RDFS->seeAlso("");
		$statements .= $this->pValidFromThrough();
		$statements .= $this->pHasBusinessFunction();
		$statements .= $this->pHasPriceSpecification();
		$statements .= $this->availableAtOrFrom();
		$statements .= $this->pIncludesObject();
		$localeCode = $this->sysinfo->getLocaleCode();
			// product name
		$pName = $this->Product->getName();
		$statements .= $this->RDFS->pName( $pName, $localeCode );
		// product description
		$pDescr = $this->Product->getDescription();
		$pDescr = $this->div->removeTagsAndNls($pDescr); // no HTML-Tags, no newlines
	    $statements .= $this->RDFS->pDesc( $pDescr, $localeCode );
        //$statements .=  Mage::getStoreConfig('catalog/review');
		//review stuff
	 	$array =  Mage::getStoreConfig('catalog/review');
		if ($array['allow_guest'] == 1) // reviews active
            $statements .= $this->pReview();
		$grOffering .= $this->rdff->wrapStatements($statements, $this->getURI('offering'), "gr:Offering");
		return $grOffering;
		
	}
	
	
	protected function pReview()
	{
		$productId = $this->Product->getId();
	    $product = Mage::getModel('catalog/product')->load($productId);
		$storeId = Mage::app()->getStore()->getId();
		
		Mage::getModel('review/review')->getEntitySummary($product, $storeId);
		if ($product->getRatingSummary()->getReviewsCount())
		   $count = $product->getRatingSummary()->getReviewsCount();
        else $count = 0;
       
        
        $ratings = $product->getRatingSummary()->getRatingSummary();
		$rating = $ratings['rating_summary'] / 2;
		$statements = "<div rel=\"v:hasReview\"><div typeof=\"v:Review-aggregate\" about=\"";
		$statements .=  $this->Product->getProductUrl();
		$statements .= "#review_data\"><div property=\"v:rating\" datatype=\"xsd:string\" content=\"$rating\"></div> <div property=\"v:count\" datatype=\"xsd:string\" content=\"$count\"></div> 
		</div> 
	</div> ";
	
		
		return $statements;
	}
	
	protected function pOffers()
	{
		$pOffers = $this->rdff->rev("gr:offers", $this->getURI('businessEntity'));
		return $pOffers;
	}
	
	protected function pHasBusinessFunction($function="sell")
	{
		$pHasBusinessFunction = $this->rdff->rel("gr:hasBusinessFunction", $this->BUSINESS_FUNCTIONS[$function]);
		return $pHasBusinessFunction;
	}
	
	protected function pHasPriceSpecification()
	{
		$priceSpecifications = "";

		$propArray = array(
			"gr:hasCurrencyValue"		=> round($this->Product->getFinalPrice(1), 2), // 1 unit
			"gr:hasCurrency"			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
			"gr:hasUnitOfMeasurement"	=> "C62"	// price per unit
		); 
		//check if tax is in price
		$tax = Mage::helper('tax');
		if ($tax->displayPriceIncludingTax()) { $propArray["gr:valueAddedTaxIncluded"] = 'true'; }
		else { $propArray["gr:valueAddedTaxIncluded"] = 'false'; }
		
		$statements = $this->rdff->properties($propArray,$this->getReplacements());
		
		$attributes = $this->rdff->wrapStatements($statements, $this->getURI("unitPriceSpecification"), "gr:UnitPriceSpecification" );
		
		//$attributes .= $this->pValidFromThrough();
		
		$priceSpecifications .= $this->rdff->wrapRel($attributes, "gr:hasPriceSpecification");
		return $priceSpecifications;
	}
	
	protected function pValidFromThrough()
	{
		$validFrom =  date("Y-m-d", strtotime("now"));
		$validThrough = "";
		
		$now = strtotime("now");
		// 1: Special Price valid from through
		$specialFrom = strtotime( $this->Product->getSpecialFromDate() );
		$specialTo = strtotime( $this->Product->getSpecialToDate() );
		if ( $this->Product->getSpecialPrice() && ($specialFrom <= $now) && ($now <= $specialTo))	// for special prices check validity
		{
			$validFrom =  $this->Product->getSpecialFromDate();
			$validThrough = $this->Product->getSpecialToDate();
			if ($validFrom) $validFrom =  date("Y-m-d H:i:s", strtotime("now"));
			if (!$validThrough) $validThrough = date("Y-m-d H:i:s", strtotime("now")); // valid till now
		}
		// 2: product valid through
		elseif (($validThrough = $this->pValidThroughProduct()) != "")
		{
			if (strtotime($validThrough) < $now) $validFrom = $validThrough;	// expired
			else $validFrom =  date("Y-m-d", strtotime("now"));
		}
		// 3: business valid through
		else
		{
			$validThrough = $this->Business->getValidThrough();
		}
		$validFrom = $this->div->dateToIso8601($validFrom);
		$validThrough = $this->div->dateToIso8601($validThrough);
		$propArray = array(
			"gr:validFrom"		=> $validFrom,
			"gr:validThrough"	=> $validThrough
		);
		$pValidFromThrough = $this->rdff->properties($propArray,$this->getReplacements());
		return $pValidFromThrough;
	}
	
	protected function pValidThroughProduct()
	{
		$pValidThrough = $this->productGetAttributeValue('valid_through');
		return $pValidThrough;
	}
	
	protected function pIncludesObject()
	{
		$attributes = "";
		$attributes .= $this->pTypeAndQuantityNode();
		
		$pIncludesObject = $this->rdff->wrapRel($attributes, "gr:includesObject");
		return $pIncludesObject;
	}
	
	protected function pTypeAndQuantityNode()
	{
		$propArray = array(
			"gr:amountOfThisGood"		=> "1.0",	// 1
			"gr:hasUnitOfMeasurement"	=> "C62"	// price per unit
		);
		$statements = "";
		$statements .= $this->rdff->properties($propArray,$this->getReplacements());
		$statements .= $this->pTypeOfGood();
		
		$taqn = $this->rdff->wrapStatements($statements, $this->getURI("typeAndQuantityNode"), "gr:TypeAndQuantityNode" );
		return $taqn;
	}
	
	protected function pTypeOfGood()
	{
		$attributes = "";
		$attributes .= $this->pProduct();
		
		$pTypeOfGood = $this->rdff->wrapRel($attributes, "gr:typeOfGood");
		return $pTypeOfGood;
	}
	
	protected function pProduct()
	{
		$localeCode = $this->sysinfo->getLocaleCode();
		
		$statements = "";
		// link
		$statements .= $this->RDFS->seeAlso($this->Product->getProductUrl()); 
		// product name
		$pName = $this->product->getName();
		$statements .= $this->RDFS->label( $pName, $localeCode );
		// product description
		$pDescr = $this->Product->getDescription();
		$pDescr = $this->div->removeTagsAndNls($pDescr); // no HTML-Tags, no newlines
		$statements .= $this->RDFS->comment( $pDescr, $localeCode );
		// EAN Code if isset
		$eanCode = $this->productGetAttributeValue('ean');
		if ($eanCode) $statements .= $this->rdff->propertyAuto("gr:hasEAN_UCC-13", $eanCode, $this->getReplacements() );
		// image
		$statements .= $this->rdff->rel("rdfs:seeAlso media:image foaf:depiction", $this->Product->getImageURL());
		// all
		$pProduct = "";
		//$pProduct .= $this->rdff->rel("product:Product", $this->getURI("product"));
		$pProduct .= $this->rdff->wrapStatements($statements, $this->Product->getProductUrl() . $this->getURI("product"), "gr:ProductOrServicesSomeInstancesPlaceholder");
		return $pProduct;
	}
	
	protected function productGetAttributeValue($what)
	{
		$attributeCode = $this->getAttributeCode($what);
		$attributeValue = $this->Product->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($this->Product);
		return $attributeValue;
	}
	
	
	/**
	 * 
	 * @param $productCollection Products
	 * @return string
	 */
	public function businessEntityWithProducts($productCollection)
	{
		$statements = "";
		$statements .= $this->RDFS->seeAlso("");
		$statements .= $this->bLegalName();
		$statements .= $this->getAddressVCard();	// gets complete vcard Code
		$statements .= $this->VCARD->url($this->Business->getBaseURL());
		$statements .= $this->hasPOS();
		$statements .= $this->businessOffersWithProducts($productCollection);
		
		// about the Business Entity
		$grBusinessEntity = $this->rdff->wrapStatements($statements, $this->getURI('businessEntity'), "gr:BusinessEntity");
		
		return $grBusinessEntity;
	}
	
	protected function businessOffersWithProducts($productCollection)
	{
		# generate multiple Offerings
		$grOfferings = "";
		forEach ($productCollection as $Product)
		{
			$this->setProduct($Product);
			$grOfferingDescription = $this->pOfferingDump();
			$grOffering = $this->rdff->wrapRel($grOfferingDescription ,"gr:offers");
			$grOfferings .= $grOffering;
		}
		
		
		return $grOfferings;
		
		return $output;
	}
	// for multiple products on 1 page
	public function pOfferingDump()
	{
		$statements = "";
		
		$statements .= $this->RDFS->isDefinedBy( $this->Business->getBaseURL() );
		
		$statements .= $this->RDFS->seeAlso( $this->Product->getProductUrl() );
		$statements .= $this->pValidFromThrough();
		$statements .= $this->pHasBusinessFunction();
		$statements .= $this->pHasPriceSpecification();
		$statements .= $this->availableAtOrFrom();
		$statements .= $this->pIncludesObject();
		
		// common information
		//$statements .= $this->eligibleRegions();	// no eligible Regions -> to much data
		$statements .= $this->eligibleCustomerTypes();
		$statements .= $this->acceptedPaymentMethods();
		$statements .= $this->deliveryMethods();
		
		// about the Offering
		$grOffering = $this->rdff->wrapStatements($statements, $this->getURI('offering'), "gr:Offering");
		
		return $grOffering;
	}
}