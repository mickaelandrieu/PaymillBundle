<?php

/**
 * PaymillBundle for Symfony2
 *
 * This Bundle is part of Symfony2 Payment Suite
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @package PaymillBundle
 *
 * Marc Morera 2013
 */

namespace Mmoreram\PaymillBundle\Tests\Services;

use Mmoreram\PaymillBundle\Services\PaymillManager;

/**
 * Paymill manager
 */
class PaymillManagerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var string
     * 
     * Currency
     */
    const CURRENCY = 'EUR';

    /**
     * @var string
     * 
     * Currency
     */
    const API_TOKEN = '2374932748923';


    /**
     * @var integer
     * 
     * Card amount
     */
    const ORDER_AMOUNT = 10;


    /**
     * @var string
     * 
     * Card description
     */
    const ORDER_DESCRIPTION = 'This is my card description';


    /**
     * @var PaymentManager
     * 
     * Payment manager object
     */
    private $paymillManager;


    /**
     * @var PaymentEventDispatcher
     * 
     * Paymetn event dispatcher object
     */
    private $paymentEventDispatcher;


    /**
     * @var PaymillTransactionWrapper
     * 
     * Wrapper for Paypall Transaction instance
     */
    private $paymillTransactionWrapper;


    /**
     * @var CardWrapper
     * 
     * Card Wrapper object
     */
    private $paymentBridge;


    /**
     * @var PaymillMethod class
     * 
     * Paymill Method object
     */
    private $paymillMethod;


    /**
     * Setup method
     */
    public function setUp()
    {

        $this->paymentBridge = $this
            ->getMockBuilder('Mmoreram\PaymentCoreBundle\Services\Interfaces\PaymentBridgeInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymillTransactionWrapper = $this
            ->getMockBuilder('Mmoreram\PaymillBundle\Services\Wrapper\PaymillTransactionWrapper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentEventDispatcher = $this
            ->getMockBuilder('Mmoreram\PaymentCoreBundle\Services\PaymentEventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymillMethod = $this
            ->getMockBuilder('Mmoreram\PaymillBundle\PaymillMethod')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymillManager = new PaymillManager($this->paymentEventDispatcher, $this->paymillTransactionWrapper, $this->paymentBridge);
    }


    /**
     * Testing different ammunts
     * 
     * @expectedException \Mmoreram\PaymentCoreBundle\Exception\PaymentAmountsNotMatchException
     */
    public function testDifferentAmounts()
    {
        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getAmount')
            ->will($this->returnValue(500));

        $this
            ->paymentBridge
            ->expects($this->any())
            ->method('getOrder');

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderLoad');

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderCreated');

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderDone');

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderFail');

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderSuccess');

        $this->paymillManager->processPayment($this->paymillMethod, self::ORDER_AMOUNT * 100);
    }


    /**
     * Testing payment error
     * 
     * @expectedException \Mmoreram\PaymentCoreBundle\Exception\PaymentException
     */
    public function testPaymentError()
    {
        $this
            ->paymillMethod
            ->expects($this->once())
            ->method('getApiToken')
            ->will($this->returnValue(self::API_TOKEN));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue(1));

        $this
            ->paymillMethod
            ->expects($this->any())
            ->method('setTransactionId')
            ->with($this->equalTo('123'))
            ->will($this->returnValue($this->paymillMethod));

        $this
            ->paymillMethod
            ->expects($this->any())
            ->method('setTransactionStatus')
            ->with($this->equalTo('closed'))
            ->will($this->returnValue($this->paymillMethod));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getCurrency')
            ->will($this->returnValue(self::CURRENCY));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getAmount')
            ->will($this->returnValue(self::ORDER_AMOUNT));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getExtraData')
            ->will($this->returnValue(array(
                'order_description' =>  self::ORDER_DESCRIPTION
            )));

        $this
            ->paymillTransactionWrapper
            ->expects($this->once())
            ->method('create')
            ->with($this->equalTo(array(
                'amount' => self::ORDER_AMOUNT * 100,
                'currency' => self::CURRENCY,
                'token' => self::API_TOKEN,
                'description' => self::ORDER_DESCRIPTION
            )))
            ->will($this->returnValue(array(
                'status'    =>  'something_different_to_closed',
                'id'        =>  '123'
            )));

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderLoad')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderCreated')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderDone')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderFail')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderSuccess');

        $this->paymillManager->processPayment($this->paymillMethod, self::ORDER_AMOUNT * 100);
    }


    /**
     * Testing payment error
     * 
     */
    public function testPaymentSuccess()
    {
        $this
            ->paymillMethod
            ->expects($this->once())
            ->method('getApiToken')
            ->will($this->returnValue(self::API_TOKEN));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getOrder')
            ->will($this->returnValue(1));

        $this
            ->paymillMethod
            ->expects($this->any())
            ->method('setTransactionId')
            ->with($this->equalTo('123'))
            ->will($this->returnValue($this->paymillMethod));

        $this
            ->paymillMethod
            ->expects($this->any())
            ->method('setTransactionStatus')
            ->with($this->equalTo('closed'))
            ->will($this->returnValue($this->paymillMethod));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getCurrency')
            ->will($this->returnValue(self::CURRENCY));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getAmount')
            ->will($this->returnValue(self::ORDER_AMOUNT));

        $this
            ->paymentBridge
            ->expects($this->once())
            ->method('getExtraData')
            ->will($this->returnValue(array(
                'order_description' =>  self::ORDER_DESCRIPTION
            )));

        $this
            ->paymillTransactionWrapper
            ->expects($this->once())
            ->method('create')
            ->with($this->equalTo(array(
                'amount' => self::ORDER_AMOUNT * 100,
                'currency' => self::CURRENCY,
                'token' => self::API_TOKEN,
                'description' => self::ORDER_DESCRIPTION
            )))
            ->will($this->returnValue(array(
                'status'    =>  'closed',
                'id'        =>  '123'
            )));

         $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderLoad')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderCreated')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderDone')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this
            ->paymentEventDispatcher
            ->expects($this->any())
            ->method('notifyPaymentOrderFail');

        $this
            ->paymentEventDispatcher
            ->expects($this->once())
            ->method('notifyPaymentOrderSuccess')
            ->with($this->equalTo($this->paymentBridge), $this->equalTo($this->paymillMethod));

        $this->paymillManager->processPayment($this->paymillMethod, self::ORDER_AMOUNT * 100);
    }
}