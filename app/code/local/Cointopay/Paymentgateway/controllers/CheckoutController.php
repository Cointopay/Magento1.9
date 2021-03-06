<?php
/**
* Copyright © 2018 Cointopay. All rights reserved.
* See COPYING.txt for license details.
*/

class Cointopay_Paymentgateway_CheckoutController extends Mage_Core_Controller_Front_Action
{
	/**
    * @var Context
    */
	protected $_context;

	/**
    * @var PageFactory
    */
    protected $_pageFactory;
    
    /**
    * @var json
    */
    protected $_jsonEncoder;

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;

    /**
    * @var $merchantId
    **/
    protected $merchantId;

    /**
    * @var $type
    **/
    protected $type;

    /**
    * @var $_curlUrl
    **/
    protected $_curlUrl;

    /**
    * @var $response
    **/
    protected $response = [] ;

    /**
    *   @var $storeId
    **/
    protected $storeId;

    protected $currencyCode;

    protected $securityKey;

    protected $coinId;

    protected $orderTotal;

    public function indexAction()
    {
        $isAjax = Mage::app()->getRequest()->isAjax();
        if ($isAjax) {
            $this->type = $this->getRequest()->getParam('type');
            $this->coinId = $this->getRequest()->getParam('paymentaction');
            $this->storeId = Mage::app()->getStore()->getStoreId();
            $this->merchantId = Mage::getStoreConfig('payment/cointopaygateway/merchant_gateway_id', $this->storeId);
            $this->securityKey = trim(Mage::getStoreConfig('payment/cointopaygateway/merchant_gateway_security', $storeScope));
            $this->currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
            if ($this->type == 'status') {
                $response = $this->getStatus($this->coinId);
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody (
                    Mage::helper('core')->jsonEncode (
                        array (
                            'status' => $response
                        )
                    )
                );
            } else {
                Mage::getSingleton('core/session')->setCoinid($this->coinId);
                $isVerified = $this->verifyOrder();
                if ($isVerified == 'success') {
                    $this->getResponse()->setHeader('Content-type', 'application/json');
                    $this->getResponse()->setBody (
                        Mage::helper('core')->jsonEncode(
                            array (
                                'status' => 'success',
                                'coindid' => $this->coinId
                            )
                        )
                    );
                } else {
                    $this->getResponse()->setHeader('Content-type', 'application/json');
                    $this->getResponse()->setBody (
                        Mage::helper('core')->jsonEncode(
                            array (
                                'status' => 'error',
                                'message' => $isVerified
                            )
                        )
                    );
                }
            }
        }
        return;
    }

    /**
    * @return string payment status
    **/
    private function getStatus ($TransactionID) {
        $this->_curlUrl = 'https://app.cointopay.com/CloneMasterTransaction?MerchantID='.$this->merchantId.'&TransactionID='.$TransactionID.'&output=json';
        // Specify the POST data
        $fields = array();
        foreach ($fields as $key => $value) { $fields_string .= $key . '=' . $value . '&'; }
        $fields_string = "";
        rtrim($fields_string, '&');$ch = curl_init();curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_curlUrl);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $response = htmlspecialchars_decode(curl_exec($ch));
        $decoded = json_decode($response);
        return $decoded[1];
    }

    /**
    * @return available coins for merchant
    **/
    private function getSupportedCoins ()
    {
        $this->_curlUrl = 'https://app.cointopay.com/CloneMasterTransaction?MerchantID='.$this->merchantId.'&output=json';

        // Specify the POST data
        $fields = array();
        foreach ($fields as $key => $value) { $fields_string .= $key . '=' . $value . '&'; }
        $fields_string = "";
        rtrim($fields_string, '&');$ch = curl_init();curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_curlUrl);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $response = htmlspecialchars_decode(curl_exec($ch));
        curl_close($ch);
        $supportedCoins = @json_decode($response);
        $coins = [];
        if (count($supportedCoins) > 0)
        {
            foreach ($supportedCoins as $k => $title)
            {
                if ($k % 2 == 0)
                {
                    $coins[] = array (
                        'value' => $supportedCoins[$k+1],
                        'title' => $title,
                    );
                }
            } 
        }

        return $coins;
    }

    // verify security code
    private function verifyCode ()
    {
        $this->_curlUrl = 'https://app.cointopay.com/MerchantAPI?Checkout=true&MerchantID=123&Amount=1000&AltCoinID=1&CustomerReferenceNr=buy%20something%20from%20me&SecurityCode='.$this->merchantId.'&inputCurrency=EUR&output=json&testmerchant';

        // Specify the POST data
        $fields = array(
        );
        foreach ($fields as $key => $value) { $fields_string .= $key . '=' . $value . '&'; }
        $fields_string = "";
        rtrim($fields_string, '&');$ch = curl_init();curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_curlUrl);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $response = htmlspecialchars_decode(curl_exec($ch));
        
        if ($response == '"Invalid Merchant or SecurityCode. You can check the Merchant account section. Please correct."') {
            return false;
        } else {
            return true;
        }
    }

    /**
    * @return Total order amount from cart
    **/
    private function getCartAmount ()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteData= $quote->getData();
        return $quoteData['grand_total'];
    }

    // verify that if order can be placed or not
    private function verifyOrder ()
    {
        $this->orderTotal = $this->getCartAmount();
        $this->_curlUrl = 'https://app.cointopay.com/MerchantAPI?Checkout=true&MerchantID='.$this->merchantId.'&Amount='.$this->orderTotal.'&AltCoinID='.$this->coinId.'&CustomerReferenceNr=buy%20something%20from%20me&SecurityCode='.$this->securityKey.'&output=json&inputCurrency='.$this->currencyCode.'&testcheckout';

        // Specify the POST data
        $fields = array();
        foreach ($fields as $key => $value) { $fields_string .= $key . '=' . $value . '&'; }
        $fields_string = "";
        rtrim($fields_string, '&');$ch = curl_init();curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->_curlUrl);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        $response = htmlspecialchars_decode(curl_exec($ch));
        if ($response == '"testcheckout success"') {
            return 'success';
        }
        return $response;
    }
}