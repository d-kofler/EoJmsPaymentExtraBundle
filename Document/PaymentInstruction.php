<?php

/*
 * This file is part of the EoJmsPaymentExtraBundle package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\JmsPaymentExtraBundle\Document;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 * @ODM\ChangeTrackingPolicy("DEFERRED_IMPLICIT")
 * @Gedmo\Loggable()
 */
class PaymentInstruction implements PaymentInstructionInterface
{
    private $account;

    /**
     * @ODM\Float
     */
    private $amount;

    /**
     * @ODM\Float
     */
    private $approvedAmount;

    /**
     * @ODM\Float
     */
    private $approvingAmount;

    /**
     * @ODM\Date
     */
    private $createdAt;

    /**
     * @ODM\Float
     */
    private $creditedAmount;

    /**
     * @ODM\Float
     */
    private $creditingAmount;

    /**
     * @ODM\ReferenceMany(targetDocument="Eo\JmsPaymentExtraBundle\Document\Credit")
     */
    private $credits;

    /**
     * @ODM\String
     */
    private $currency;

    /**
     * @ODM\Float
     */
    private $depositedAmount;

    /**
     * @ODM\Float
     */
    private $depositingAmount;

    /**
     * @ODM\EmbedOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\ExtendedData")
     */
    private $extendedData;

    /**
     * @var mixed
     */
    private $extendedDataOriginal;

    /**
     * @ODM\Id(strategy="AUTO")
     */
    private $id;

    /**
     * @ODM\ReferenceMany(targetDocument="Eo\JmsPaymentExtraBundle\Document\Payment", mappedBy="paymentInstruction")
     */
    private $payments;

    /**
     * @ODM\String
     */
    private $paymentSystemName;

    /**
     * @ODM\Float
     */
    private $reversingApprovedAmount;

    /**
     * @ODM\Float
     */
    private $reversingCreditedAmount;

    /**
     * @ODM\Float
     */
    private $reversingDepositedAmount;

    /**
     * @ODM\Int
     */
    private $state;

    /**
     * @ODM\Date
     */
    private $updatedAt;

    public function __construct($amount, $currency, $paymentSystemName, ExtendedData $data = null)
    {
        if (null === $data) {
            $data = new ExtendedData();
        }

        $this->amount = $amount;
        $this->approvedAmount = 0.0;
        $this->approvingAmount = 0.0;
        $this->createdAt = new \DateTime;
        $this->creditedAmount = 0.0;
        $this->creditingAmount = 0.0;
        $this->credits = new ArrayCollection();
        $this->currency = $currency;
        $this->depositingAmount = 0.0;
        $this->depositedAmount = 0.0;
        $this->extendedData = $data;
        $this->extendedDataOriginal = clone $data;
        $this->payments = new ArrayCollection();
        $this->paymentSystemName = $paymentSystemName;
        $this->reversingApprovedAmount = 0.0;
        $this->reversingCreditedAmount = 0.0;
        $this->reversingDepositedAmount = 0.0;
        $this->state = self::STATE_NEW;
    }

    /**
     * @ODM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->getExtendedData() && is_object($this->getExtendedData()) ) {
            $this->extendedDataOriginal = clone $this->extendedData;
        }
    }

    /**
     * @ODM\PrePersist
     */
    public function prePersist()
    {
        $this->updatedAt = new \DateTime;

        if (null !== $this->extendedDataOriginal
                 && null !== $this->extendedData
                 && false === $this->extendedData->equals($this->extendedDataOriginal)) {
            $this->extendedData = clone $this->extendedData;
        }
    }

    /**
     * @ODM\PreUpdate
     */
    public function preUpdate()
    {
        $this->prePersist();
    }

    /**
     * This method adds a Credit container to this PaymentInstruction.
     *
     * This method is called automatically from Credit::__construct().
     *
     * @param Credit $credit
     * @return void
     */
    public function addCredit(Credit $credit)
    {
        if ($credit->getPaymentInstruction() !== $this) {
            throw new \InvalidArgumentException('This credit container belongs to another instruction.');
        }

        $this->credits->add($credit);
    }

    /**
     * This method adds a Payment container to this PaymentInstruction.
     *
     * This method is called automatically from Payment::__construct().
     *
     * @param Payment $payment
     * @return void
     */
    public function addPayment(Payment $payment)
    {
        if ($payment->getPaymentInstruction() !== $this) {
            throw new \InvalidArgumentException('This payment container belongs to another instruction.');
        }

        $this->payments->add($payment);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getPaymentSystemName()
    {
        return $this->paymentSystemName;
    }

    public function getExtendedData()
    {
        return $this->extendedData;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getApprovingAmount()
    {
        return $this->approvingAmount;
    }

    public function getApprovedAmount()
    {
        return $this->approvedAmount;
    }

    public function getCreditedAmount()
    {
        return $this->creditedAmount;
    }

    public function getCreditingAmount()
    {
        return $this->creditingAmount;
    }

    public function getDepositedAmount()
    {
        return $this->depositedAmount;
    }

    public function getDepositingAmount()
    {
        return $this->depositingAmount;
    }

    public function getCredits()
    {
        return $this->credits;
    }

    public function getPayments()
    {
        return $this->payments;
    }

    public function getPendingTransaction()
    {
        foreach ($this->payments as $payment) {
            if (null !== $transaction = $payment->getPendingTransaction()) {
                return $transaction;
            }
        }

        foreach ($this->credits as $credit) {
            if (null !== $transaction = $credit->getPendingTransaction()) {
                return $transaction;
            }
        }

        return null;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getReversingApprovedAmount()
    {
        return $this->reversingApprovedAmount;
    }

    public function getReversingCreditedAmount()
    {
        return $this->reversingCreditedAmount;
    }

    public function getReversingDepositedAmount()
    {
        return $this->reversingDepositedAmount;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
    }

    public function onPreSave()
    {
        $this->updatedAt = new \Datetime;

        // this is necessary until Doctrine adds an interface for comparing
        // value objects. Right now this is done by referential equality
        if (null !== $this->extendedDataOriginal && false === $this->extendedData->equals($this->extendedDataOriginal)) {
            $this->extendedData = clone $this->extendedData;
        }
    }

    public function setApprovingAmount($amount)
    {
        $this->approvingAmount = $amount;
    }

    public function setApprovedAmount($amount)
    {
        $this->approvedAmount = $amount;
    }

    public function setCreditedAmount($amount)
    {
        $this->creditedAmount = $amount;
    }

    public function setCreditingAmount($amount)
    {
        $this->creditingAmount = $amount;
    }

    public function setDepositedAmount($amount)
    {
        $this->depositedAmount = $amount;
    }

    public function setDepositingAmount($amount)
    {
        $this->depositingAmount = $amount;
    }

    public function setReversingApprovedAmount($amount)
    {
        $this->reversingApprovedAmount = $amount;
    }

    public function setReversingCreditedAmount($amount)
    {
        $this->reversingCreditedAmount = $amount;
    }

    public function setReversingDepositedAmount($amount)
    {
        $this->reversingDepositedAmount = $amount;
    }

    public function setState($state)
    {
        $this->state = $state;
    }
}
