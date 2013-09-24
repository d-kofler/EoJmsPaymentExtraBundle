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

use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 * @ODM\ChangeTrackingPolicy("DEFERRED_IMPLICIT")
 * @Gedmo\Loggable()
 */
class Credit
{
    /**
     * @ODM\Field(type="boolean")
     */
    private $attentionRequired;

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
     * @var MongoId $id
     *
     * @ODM\Id(strategy="AUTO")
     */
    protected $id;

    /**
     * @ODM\ReferenceOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\Payment")
     */
    private $payment;

    /**
     * @ODM\ReferenceOne(targetDocument="Eo\JmsPaymentExtraBundle\Document\PaymentInstruction", inversedBy="credits")
     */
    private $paymentInstruction;

    /**
     * @ODM\ReferenceMany(targetDocument="Eo\JmsPaymentExtraBundle\Document\FinancialTransaction", mappedBy="credit")
     */
    private $transactions;

    /**
     * @ODM\Float
     */
    private $reversingAmount;

    /**
     * @ODM\Int
     */
    private $state;

    /**
     * @ODM\Float
     */
    private $targetAmount;

    /**
     * @ODM\Date
     */
    private $updatedAt;

    public function __construct(PaymentInstructionInterface $paymentInstruction, $amount)
    {
        $this->attentionRequired = false;
        $this->creditedAmount = 0.0;
        $this->creditingAmount = 0.0;
        $this->paymentInstruction = $paymentInstruction;
        $this->transactions = new ArrayCollection;
        $this->reversingAmount = 0.0;
        $this->state = self::STATE_NEW;
        $this->targetAmount = $amount;
        $this->createdAt = new \DateTime;

        $this->paymentInstruction->addCredit($this);
    }

    public function addTransaction(FinancialTransaction $transaction)
    {
        $this->transactions->add($transaction);
        $transaction->setCredit($this);
    }

    public function getCreditedAmount()
    {
        return $this->creditedAmount;
    }

    public function getCreditingAmount()
    {
        return $this->creditingAmount;
    }

    public function getCreditTransaction()
    {
        foreach ($this->transactions as $transaction) {
            if (FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT === $transaction->getTransactionType()) {
                return $transaction;
            }
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

    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }

    public function getPendingTransaction()
    {
        foreach ($this->transactions as $transaction) {
            if (FinancialTransactionInterface::STATE_PENDING === $transaction->getState()) {
                return $transaction;
            }
        }

        return null;
    }

    public function getReverseCreditTransactions()
    {
        return $this->transactions->filter(function($transaction) {
            return FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT === $transaction->getTransactionType();
        });
    }

    public function getReversingAmount()
    {
        return $this->reversingAmount;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getTargetAmount()
    {
        return $this->targetAmount;
    }

    public function getTransactions()
    {
        return $this->transactions;
    }

    public function isAttentionRequired()
    {
        return $this->attentionRequired;
    }

    public function isIndependent()
    {
        return null === $this->payment;
    }

    public function setAttentionRequired($boolean)
    {
        $this->attentionRequired = !!$boolean;
    }

    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
    }

    public function setCreditedAmount($amount)
    {
        $this->creditedAmount = $amount;
    }

    public function setCreditingAmount($amount)
    {
        $this->creditingAmount = $amount;
    }

    public function setReversingAmount($amount)
    {
        $this->reversingAmount = $amount;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function onPreSave()
    {
        $this->updatedAt = new \DateTime;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
