<?php
/**
* Copyright © 2018 Cointopay. All rights reserved.
* See COPYING.txt for license details.
*/

class Cointopay_Paymentgateway_IndexController extends Mage_Core_Controller_Front_Action
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
    
    public function indexAction()
    {
        $isAjax = Mage::app()->getRequest()->isAjax();
        if ($isAjax) {
            $this->merchantId = $this->getRequest()->getParam('merchant');
            $this->type = $this->getRequest()->getParam('type');

            if ($this->type == 'merchant') {
                if (isset($this->merchantId))
                {
                    $this->response = $this->getSupportedCoins();
                }
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody (
                    Mage::helper('core')->jsonEncode(
                        $this->response
                    )
                );
            } else {
                $response = $this->verifyCode();
                if ($response) {
                    $this->getResponse()->setHeader('Content-type', 'application/json');
                    $this->getResponse()->setBody (
                        Mage::helper('core')->jsonEncode(
                            array ('status' => 'success')
                        )
                    );
                } else {
                    $this->getResponse()->setHeader('Content-type', 'application/json');
                    $this->getResponse()->setBody (
                        Mage::helper('core')->jsonEncode(
                            array ('status' => 'error')
                        )
                    );
                }
            }
        }
        return;
    }

    /**
    * @return available coins for merchant
    **/
    private function getSupportedCoins ()
    {
        $this->_curlUrl = 'https://cointopay.com/CloneMasterTransaction?MerchantID='.$this->merchantId.'&output=json';

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
        $this->_curlUrl = 'https://cointopay.com/MerchantAPI?Checkout=true&MerchantID=123&Amount=1000&AltCoinID=1&CustomerReferenceNr=buy%20something%20from%20me&SecurityCode='.$this->merchantId.'&inputCurrency=EUR&output=json&testmerchant';

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
}