EoJmsPaymentExtraBundle
=======================

Provides doctrine mongodb odm support for JMSPaymentCoreBundle. 

> Use at your own risk:
> Recently there have been two fairly high profile field reports on MongoDB that show it in a very unfavorable light. 
> The majority of the criticism centers on a combination of performance problems and data loss.

## Prerequisites
This version of the bundle requires Symfony 2.1+, JMS Payment Core and Doctrine MongoDB.

## Installation

### Step 1: Download EoJmsPaymentExtraBundle using composer
Add EoJmsPaymentExtraBundle in your composer.json:
```
{
    "require": {
        "eo/jms-payment-extra-bundle": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:
```
$ php composer.phar update eo/jms-payment-extra-bundle
```
Composer will install the bundle to your project's vendor/eo directory.

### Step 2: Enable the bundle
Enable the bundle in the kernel:
```
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Eo\JmsPaymentExtraBundle\EoJmsPaymentExtraBundle(),
    );
}
```

## Usage

We will assume that you already have created an order object or equivalent. This could look like:

```
<?php

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Eo\JmsPaymentExtraBundle\Document\PaymentInstruction;

class Order
{
    /**
     * @ODM\ReferenceOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\PaymentInstruction")
     */
    protected $paymentInstruction;

    /**
     * @ODM\String
     * @ODM\Index(options={"unique"=true, "safe"=true, "sparse"=true})
     */
    protected $orderNumber;

    /**
     * @ODM\Int
     */
    private $amount;

    // ...

    public function __construct($amount, $orderNumber)
    {
        $this->amount = $amount;
        $this->orderNumber = $orderNumber;
    }

    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }

    public function setPaymentInstruction(PaymentInstruction $instruction)
    {
        $this->paymentInstruction = $instruction;
    }

    // ...
}
```

> An order object, or the like is not strictly necessary, but since it is
> regularly available, we will be using it in this chapter for demonstration
> purposes.

### Choosing the Payment Method
Usually, you want to give a potential customer some options on how to pay. For
this, JMSPaymentCoreBundle ships with a special form type, ``jms_choose_payment_method``,
which we will leverage.

```
<?php

use Eo\JmsPaymentExtraBundle\Document\Payment;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/payments")
 */
class PaymentController
{
    /**
     * @Route("/{orderNumber}/details", name = "payment_details")
     * @Template
     */
    public function detailsAction(Order $order)
    {
        $request = $this->get('request');
        $router = $this->get('router');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $ppc = $this->get('eo_jms_payment_extra.ppc_document');

        $form = $this->get('form.factory')->create('jms_choose_payment_method', null, array(
            'amount'   => $order->getAmount(),
            'currency' => 'EUR',
            'default_method' => 'payment_paypal', // Optional
            'predefined_data' => array(
                'paypal_express_checkout' => array(
                    'return_url' => $router->generate('payment_complete', array(
                        'orderNumber' => $order->getOrderNumber(),
                    ), true),
                    'cancel_url' => $router->generate('payment_cancel', array(
                        'orderNumber' => $order->getOrderNumber(),
                    ), true)
                ),
            ),
        ));

        if ('POST' === $request->getMethod()) {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $ppc->createPaymentInstruction($instruction = $form->getData());

                $order->setPaymentInstruction($instruction);
                $dm->persist($order);
                $dm->flush($order);

                return new RedirectResponse($router->generate('payment_complete', array(
                    'orderNumber' => $order->getOrderNumber(),
                )));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }
}
```

### Depositing Money
In the previous section, we have created our PaymentInstruction. 
Now, we will see how we can actually deposit money in our account. 
As you saw above in the detailsAction, we redirected the user to 
the payment_complete route for which we will now create the 
corresponding action in our controller:

```
<?php

use Eo\JmsPaymentExtraBundle\Document\Payment;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route("/payments")
 */
class PaymentController
{
    // ... see previous section

    /**
     * @Route("/{orderNumber}/complete", name = "payment_complete")
     */
    public function completeAction(Order $order)
    {
        $request = $this->get('request');
        $router = $this->get('router');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $ppc = $this->get('eo_jms_payment_extra.ppc_document');

        $instruction = $order->getPaymentInstruction();
        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
            $payment = $ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
        } else {
            $payment = $pendingTransaction->getPayment();
        }

        $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
        if (Result::STATUS_PENDING === $result->getStatus()) {
            $ex = $result->getPluginException();

            if ($ex instanceof ActionRequiredException) {
                $action = $ex->getAction();

                if ($action instanceof VisitUrl) {
                    return new RedirectResponse($action->getUrl());
                }

                throw $ex;
            }
        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            throw new \RuntimeException('Transaction was not successful: '.$result->getReasonCode());
        }

        // payment was successful, do something interesting with the order
    }
}
```

Available services:

`eo_jms_payment_extra.ppc_document`

Doctrine MongoDB ODM documents provided:

`Eo\JmsPaymentExtraBundle\Document\Credit`

`Eo\JmsPaymentExtraBundle\Document\ExtendedData`

`Eo\JmsPaymentExtraBundle\Document\FinancialTransaction`

`Eo\JmsPaymentExtraBundle\Document\Payment`

`Eo\JmsPaymentExtraBundle\Document\PaymentInstruction`
