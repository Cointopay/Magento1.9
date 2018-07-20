<?php
/**
 * Copyright © 2018 Cointopay. All rights reserved.
 * See COPYING.txt for license details.
 */
class Cointopay_Paymentgateway_Model_Payment extends Mage_Payment_Model_Method_Abstract {
    protected $_code = 'cointopaygateway';
    
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = false;

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $_curl;

    /**
    * @var $merchantId
    **/
    protected $merchantId;

    /**
    * @var $_curlUrl
    **/
    protected $_curlUrl;

    /**
    * Merchant ID
    */
    const XML_PATH_MERCHANT_ID = 'payment/cointopaygateway/merchant_gateway_id';

    /**
    * @var $storeId
    **/
    protected $storeId;

    public function toOptionArray()
    {
        $this->storeId = Mage::app()->getStore()->getStoreId();
        $this->merchantId = Mage::getStoreConfig('payment/cointopaygateway/merchant_gateway_id', $this->storeId);
        if (isset($this->merchantId))
        {
            return $this->getSupportedCoins();
        } else
        {
            return [];
        }
    }

    /**
    * @return available coins for merchant
    **/
    private function getSupportedCoins ()
    {
        $this->_curlUrl = 'https://app.cointopay.com/CloneMasterTransaction?MerchantID='.$this->merchantId.'&output=json';

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
                        'label' => $title,
                    );
                }
            } 
        }

        return $coins;
    }
}
?>