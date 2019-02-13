VikingPayBundle
===============

Viking pay plugin adapter for JihelOmnipaySpreedlyBridge


1- Install
----------

Add plugin to your composer.json require:

    {
        "require": {
            "jihel/spreedly-bridge": "1.0",
            "jihel/spreedly-viking-pay-receiver": "1.0",
        }
    }

or

    php composer.phar require jihel/spreedly-viking-pay-receiver

Add bundle to your AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Jihel\OmnipaySpreedlyBridgeBundle\JihelOmnipaySpreedlyBridgeBundle(),
        );
    }


2- Configure
------------


config.yml
    
    jihel_viking_pay:
        accounts:
            default:
                password: %viking_pass%
                userId: %viking_user%
                entityId: %viking_entity_id%
    
    omnipay:
        default_gateway: SpreedlyBridge
        methods:
            SpreedlyBridge:
                user: %spreedly_user%
                secret: %spreedly_secret%
                testMode: "%kernel.debug%"
    
routing.yml

    JihelOmnipaySpreedlyBridgeBundle:
        resource: '@JihelOmnipaySpreedlyBridgeBundle/Resources/config/routing.yaml'
        prefix: /_jihel/omnipay

Generate entities

    php bin/console doctrine:schema:update --force


3- Usage
--------

Create a new receiver on /_jihel/omnipay/

The payment method is Omnipay standard BUT you do have to setup the receiver

/form-submit

    use Omnipay\SpreedlyBridge\Gateway;
    
    // ...

    /** @var ReceiverManager $receiverManager */
    $receiverManager = $this->get('jihel.omnipay.manager.receiver');
    /** @var TransactionManager $transactionManager */
    $transactionManager = $this->get('jihel.omnipay.manager.transaction');

    // Works with float, not cents
    $initialAmount /= 100;
    $gatewayName = Gateway::NAME;
    
    $paymentRequest = new \Jihel\OmnipaySpreedlyBridgeBundle\Model\PaymentRequest();
    $paymentRequest
        ->setAmount($initialAmount)
        ->setCurrency($order->getConfig()->getCurrency())
    ;
    
    $receiver = $receiverManager->findByDomain($domain->getName());
    
    $transaction = $transactionManager->create($paymentRequest, $receiver);
    $order->setTransaction($transaction);
    $m->persist($order->getTransaction());
    $m->persist($order);
    $m->flush();
    
    // Redirect to /create

/create

    // Finish to setup the gateway
    $gateway->setReceiver($payment->getTransaction()->getReceiver());
    $response = $gateway->purchase([
        'landingUrl' => $this->generateUrl('FrontTransactionBundle_payment_finish', [
            'uuid' => $order->getUuid(),
            'id' => $payment->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL),
        'bin' => $payment->getTransaction()->getCreditCard()->getFirstSixDigits(),
        'currency' => $payment->getTransaction()->getCurrency(),
        'amount' => $payment->getTransaction()->getAmount(),
        'cardReference' => $payment->getTransaction()->getCreditCard()->getToken(),
    ])->send();
    
    if ($response->isRedirect()) {
        $transactionManager->pendingPayment($payment);

        $response->redirect();
        die;
    } elseif (!$response->isSuccessful()) {
        $transactionManager->failPayment($payment);
        $this->addFlash('danger', $response->getMessage());

        // Redirect to form
    }

    // Else it's success
    $transactionManager->depositPayment($payment, $response);
    
    // Redirect to after payment page

/3ds

    $gateway->setReceiver($payment->getTransaction()->getReceiver());
    $response = $gateway->completePurchase([
        '3ds' => $request->query->get('id'),
    ])->send();

    Then handle as standard omnipay response

4- Thanks
---------

Thanks to my cat to keep meowing me.
Thanks to me for giving my free time doing class for lazy developers.
You can access read CV [here](http://www.joseph-lemoine.fr)
