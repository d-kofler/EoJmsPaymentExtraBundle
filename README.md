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

You can now use Doctrine Mongodb ODM documents provided:

`Eo\JmsPaymentExtraBundle\Document\Credit`
`Eo\JmsPaymentExtraBundle\Document\ExtendedData`
`Eo\JmsPaymentExtraBundle\Document\FinancialTransaction`
`Eo\JmsPaymentExtraBundle\Document\Payment`
`Eo\JmsPaymentExtraBundle\Document\PaymentInstruction`