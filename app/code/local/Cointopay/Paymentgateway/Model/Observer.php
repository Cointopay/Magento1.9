<?php
/**
* Copyright © 2018 Cointopay. All rights reserved.
* See COPYING.txt for license details.
*/

class Cointopay_Paymentgateway_Model_Observer
{
    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;
    protected $_coreSession;

    /**
   * @var \Magento\Framework\App\Config\ScopeConfigInterface
   */
   protected $scopeConfig;

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;

    /**
    * @var $merchantId
    **/
    protected $merchantId;

    /**
    * @var $merchantKey
    **/
    protected $merchantKey;

    /**
    * @var $coinId
    **/
    protected $coinId;

    /**
    * @var $type
    **/
    protected $type;

    /**
    * @var $orderTotal
    **/
    protected $orderTotal;

    /**
    * @var $_curlUrl
    **/
    protected $_curlUrl;

    /**
    * @var currencyCode
    **/
    protected $currencyCode;

    /**
    * @var $_storeManager
    **/
    protected $_storeManager;
    
    /**
    * @var $securityKey
    **/
    protected $securityKey;

    /**
    * Merchant ID
    */
    const XML_PATH_MERCHANT_ID = 'payment/cointopaygateway/merchant_gateway_id';

    /**
    * Merchant COINTOPAY SECURITY Key
    */
    const XML_PATH_MERCHANT_SECURITY = 'payment/cointopaygateway/merchant_gateway_security';

    /**
    * API URL
    **/
    const COIN_TO_PAY_API = 'https://cointopay.com/MerchantAPI';

    /**
    * @var $response
    **/
    protected $response = [] ;

    protected $_request;

    protected $_historyFactory;
    
    protected $_orderFactory;
    
    function cointopayOrder($observer){
        $order = $observer->getEvent()->getOrder();
        $lastOrderId = $observer->getOrder()->getIncrementId();
        $this->orderTotal = $observer->getOrder()->getGrandTotal();
        $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
        $this->coinId =  Mage::getSingleton('core/session')->getCoinid();
        if ($payment_method_code == 'cointopaygateway') {
            $response = $this->sendCoins($lastOrderId);
            Mage::getSingleton('core/session')->setCointopayresponse($response);
            Mage::getSingleton('core/session')->setCoinresponse($response);
            $orderresponse = @json_decode($response);
            $order->setExtOrderId($orderresponse->TransactionID);
            $order->save();
        }
    }

    /**
    * @return json response
    **/
    private function sendCoins ($orderId = 0) {
    	$this->storeId = Mage::app()->getStore()->getStoreId();
        $this->merchantId = trim(Mage::getStoreConfig(self::XML_PATH_MERCHANT_ID, $this->storeId));
        $this->securityKey = trim(Mage::getStoreConfig(self::XML_PATH_MERCHANT_SECURITY, $this->storeId));
        $this->currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        $this->_curlUrl = 'https://cointopay.com/MerchantAPI?Checkout=true&MerchantID='.$this->merchantId.'&Amount='.$this->orderTotal.'&AltCoinID='.$this->coinId.'&CustomerReferenceNr='.$orderId.'&SecurityCode='.$this->securityKey.'&output=json&inputCurrency='.$this->currencyCode.'&transactionconfirmurl=http://magento1.cointopay.com/cointopaygateway/order/&transactionfailurl=http://magento1.cointopay.com/cointopaygateway/order/';
        $fields = array();
        foreach ($fields as $key => $value) { $fields_string .= $key . '=' . $value . '&'; }
        $fields_string = "";
        rtrim($fields_string, '&');$ch = curl_init();curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_curlUrl);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $response = htmlspecialchars_decode(curl_exec($ch));
        return $response;
    }
}