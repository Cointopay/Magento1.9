<?php
/**
* Copyright © 2018 Cointopay. All rights reserved.
* See COPYING.txt for license details.
*/

class Cointopay_Paymentgateway_OrderController extends Mage_Core_Controller_Front_Action
{
    protected $_context;

    protected $_pageFactory;

    protected $_jsonEncoder;

    protected $orderManagement;

    protected $resultJsonFactory;

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
    * Merchant COINTOPAY API Key
    */
    const XML_PATH_MERCHANT_KEY = 'payment/cointopaygateway/merchant_gateway_key';

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


    public function indexAction()
    {
        try {
            $customerReferenceNr = $this->getRequest()->getParam('CustomerReferenceNr');
            $status = $this->getRequest()->getParam('status');
            $ConfirmCode = $this->getRequest()->getParam('ConfirmCode');
            $SecurityCode = $this->getRequest()->getParam('SecurityCode');
            $notenough = $this->getRequest()->getParam('notenough');
            $this->storeId = Mage::app()->getStore()->getStoreId();
            $this->securityKey = trim(Mage::getStoreConfig(self::XML_PATH_MERCHANT_SECURITY, $this->storeId));
            if (is_numeric($customerReferenceNr)) {
                if ($this->securityKey == $SecurityCode) {
                    $order = Mage::getModel('sales/order')->loadByIncrementId($customerReferenceNr);
                    if (count($order->getData()) > 0) {
                        if ($status == 'paid' && $notenough == 1) {
                            $order->setState('pending_payment')->setStatus('pending_payment');
                            $order->save();
                        } else if ($status == 'paid') {
                            $order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
                            $order->setData('status', Mage_Sales_Model_Order::STATE_COMPLETE);
                            $order->save();
                        } else if ($status == 'failed') {
                            if ($order->getStatus() == 'complete') {
                                $this->getResponse()->setHeader('Content-type', 'application/json');
                                $this->getResponse()->setBody (
                                    Mage::helper('core')->jsonEncode (
                                        array (
                                            'CustomerReferenceNr' => $customerReferenceNr,
                                            'status' => 'error',
                                            'message' => 'Order cannot be cancel now, because it is completed now.'
                                        )
                                    )
                                );
                                return;
                            } else {
                                $this->orderManagement->cancel($order->getId());
                            }
                        } else {
                            $this->getResponse()->setHeader('Content-type', 'application/json');
                            $this->getResponse()->setBody (
                                Mage::helper('core')->jsonEncode (
                                    array (
                                        'CustomerReferenceNr' => $customerReferenceNr,
                                        'status' => 'error',
                                        'message' => 'Order status should have valid value.'
                                    )
                                )
                            );
                            return;
                        }
                        $this->getResponse()->setHeader('Content-type', 'application/json');
                        $this->getResponse()->setBody (
                            Mage::helper('core')->jsonEncode (
                                array (
                                    'CustomerReferenceNr' => $customerReferenceNr,
                                    'status' => 'success',
                                    'message' => 'Order status successfully updated.'
                                )
                            )
                        );
                        return;
                    } else {
                        $this->getResponse()->setHeader('Content-type', 'application/json');
                        $this->getResponse()->setBody (
                            Mage::helper('core')->jsonEncode (
                                array (
                                    'CustomerReferenceNr' => $customerReferenceNr,
                                    'status' => 'error',
                                    'message' => 'No order found.'
                                )
                            )
                        );
                        return;
                    }
                } else {
                    $this->getResponse()->setHeader('Content-type', 'application/json');
                    $this->getResponse()->setBody (
                        Mage::helper('core')->jsonEncode (
                            array (
                                'CustomerReferenceNr' => $customerReferenceNr,
                                'status' => 'error',
                                'message' => 'Security key is not valid.'
                            )
                        )
                    );
                    return;
                }
            } else {
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody (
                    Mage::helper('core')->jsonEncode (
                        array (
                            'CustomerReferenceNr' => $customerReferenceNr,
                            'status' => 'error',
                            'message' => 'CustomerReferenceNr should be an integer.'
                        )
                    )
                );
                return;
            }
        } catch (\Exception $e) {
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody (
                Mage::helper('core')->jsonEncode (
                    array (
                        'CustomerReferenceNr' => $customerReferenceNr,
                        'status' => 'error',
                        'message' => 'General error'
                    )
                )
            );
            return;
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody (
            Mage::helper('core')->jsonEncode (
                array (
                    'status' => 'error'
                )
            )
        );
        return;
    }
}