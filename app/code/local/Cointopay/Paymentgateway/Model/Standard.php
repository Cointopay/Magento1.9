<?php
/**
 * Copyright © 2018 Cointopay. All rights reserved.
 * See COPYING.txt for license details.
 */
class Cointopay_Paymentgateway_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'cointopaygateway';
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = false;
    protected $_formBlockType    = 'cointopaygateway/form';
	
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('cointopaygateway/payment/success', array('_secure' => true));
	}

	public function toOptionArray()
    {
        return array (
            array (
                'value' => 'key1',
                'label' => 'Label 1',
            ),
            array (
                'value' => 'key2',
                'label' => 'label 2',
            )
        );
    }
}
?>