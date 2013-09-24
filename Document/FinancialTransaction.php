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

use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 * @ODM\ChangeTrackingPolicy("DEFERRED_IMPLICIT")
 * @Gedmo\Loggable()
 */
class FinancialTransaction implements FinancialTransactionInterface
{
    /**
     * @ODM\ReferenceOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\Credit", inversedBy="transactions")
     */
    private $credit;

    /**
     * @ODM\EmbedOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\ExtendedData")
     */
    private $extendedData;

    /**
     * @var object
     */
    private $extendedDataOriginal = array();

    /**
     * @var MongoId $id
     *
     * @ODM\Id(strategy="AUTO")
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\Payment", inversedBy="transactions")
     */
    private $payment;

    /**
     * @ODM\Float
     */
    private $processedAmount;

    /**
     * @ODM\String
     */
    private $reasonCode;

    /**
     * @ODM\String
     */
    private $referenceNumber;

    /**
     * @ODM\Float
     */
    private $requestedAmount;

    /**
     * @ODM\String
     */
    private $responseCode;

    /**
     * @ODM\Int
     */
    private $state;

    /**
     * @ODM\Date
     */
    private $createdAt;

    /**
     * @ODM\Date
     */
    private $updatedAt;

    /**
     * @ODM\String
     */
    private $trackingId;

    /**
     * @ODM\Int
     */
    private $transactionType;

    public function __construct()
    {
        $this->state = self::STATE_NEW;
        $this->createdAt = new \DateTime();
        $this->processedAmount = 0.0;
        $this->requestedAmount = 0.0;
    }

    /**
     * @ODM\PostLoad
     */
    public function postLoad()
    {
        if (null !== $this->extendedData) {
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

    public function getCredit()
    {
        return $this->credit;
    }

    public function getExtendedData()
    {
        if (null !== $this->extendedData) {
            return $this->extendedData;
        }

        if (null !== $this->payment) {
            return $this->payment->getPaymentInstruction()->getExtendedData();
        } else if (null !== $this->credit) {
            return $this->credit->getPaymentInstruction()->getExtendedData();
        }

        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function getProcessedAmount()
    {
        return $this->processedAmount;
    }

    public function getReasonCode()
    {
        return $this->reasonCode;
    }

    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    public function getRequestedAmount()
    {
        return $this->requestedAmount;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getTrackingId()
    {
        return $this->trackingId;
    }

    public function getTransactionType()
    {
        return $this->transactionType;
    }

    public function setCredit(CreditInterface $credit)
    {
        $this->credit = $credit;
    }

    public function setExtendedData(ExtendedDataInterface $data)
    {
        $this->extendedData = $data;
    }

    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    public function setProcessedAmount($amount)
    {
        $this->processedAmount = $amount;
    }

    public function setReasonCode($code)
    {
        $this->reasonCode = $code;
    }

    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function setRequestedAmount($amount)
    {
        $this->requestedAmount = $amount;
    }

    public function setResponseCode($code)
    {
        $this->responseCode = $code;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function setTrackingId($id)
    {
        $this->trackingId = $id;
    }

    public function setTransactionType($type)
    {
        $this->transactionType = $type;
    }
}