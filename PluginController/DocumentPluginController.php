<?php

/*
 * This file is part of the EoJmsPaymentExtraBundle package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\JmsPaymentExtraBundle\PluginController;

use JMS\Payment\CoreBundle\Plugin\QueryablePluginInterface;
use Eo\JmsPaymentExtraBundle\Document\FinancialTransaction;
use Eo\JmsPaymentExtraBundle\Document\Payment;
use Eo\JmsPaymentExtraBundle\Document\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\PluginController\PluginController;
use JMS\Payment\CoreBundle\PluginController\Exception\Exception;
use JMS\Payment\CoreBundle\PluginController\Exception\PaymentNotFoundException;
use JMS\Payment\CoreBundle\PluginController\Exception\PaymentInstructionNotFoundException;
use JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException as PluginFunctionNotSupportedException;
use Doctrine\ODM\MongoDB\LockMode;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A concrete plugin controller implementation using the Doctrine ODM.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Eymen Gunay <eymen@egunay.com>
 */
class DocumentPluginController extends PluginController
{
    protected $documentManager;

    public function __construct(DocumentManager $documentManager, $options = array(), EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($options, $dispatcher);

        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritDoc}
     */
    public function approve($paymentId, $amount)
    {
        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doApprove($payment, $amount);

            $this->documentManager->persist($payment);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();


            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function approveAndDeposit($paymentId, $amount)
    {
        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doApproveAndDeposit($payment, $amount);

            $this->documentManager->persist($payment);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();

            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    public function closePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        parent::closePaymentInstruction($instruction);

        $this->documentManager->persist($instruction);
        $this->documentManager->flush();
    }

    public function createDependentCredit($paymentId, $amount)
    {
        try {
            $payment = $this->getPayment($paymentId);

            $credit = $this->doCreateDependentCredit($payment, $amount);

            $this->documentManager->persist($payment->getPaymentInstruction());
            $this->documentManager->persist($payment);
            $this->documentManager->persist($credit);
            $this->documentManager->flush();

            return $credit;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    public function createIndependentCredit($paymentInstructionId, $amount)
    {
        try {
            $instruction = $this->getPaymentInstruction($paymentInstructionId, false);

            $credit = $this->doCreateIndependentCredit($instruction, $amount);

            $this->documentManager->persist($instruction);
            $this->documentManager->persist($credit);
            $this->documentManager->flush();
            return $credit;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    public function createPayment($instructionId, $amount)
    {
        $payment = parent::createPayment($instructionId, $amount);

        $this->documentManager->persist($payment);
        $this->documentManager->flush();

        return $payment;
    }

    public function credit($creditId, $amount)
    {
        try {
            $credit = $this->getCredit($creditId);

            $result = $this->doCredit($credit, $amount);

            $this->documentManager->persist($credit);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();

            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deposit($paymentId, $amount)
    {
        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doDeposit($payment, $amount);

            $this->documentManager->persist($payment);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();

            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCredit($id)
    {
        // FIXME: also retrieve the associated PaymentInstruction
        $credit = $this->documentManager->getRepository($this->options['credit_class'])->find($id);

        if (null === $credit) {
            throw new CreditNotFoundException(sprintf('The credit with ID "%s" was not found.', $id));
        }

        $plugin = $this->getPlugin($credit->getPaymentInstruction()->getPaymentSystemName());
        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updateCredit($credit);

                $this->documentManager->persist($credit);
                $this->documentManager->flush();
            } catch (PluginFunctionNotSupportedException $notSupported) {}
        }

        return $credit;
    }

    /**
     * {@inheritDoc}
     */
    public function getPayment($id)
    {
        $payment = $this->documentManager->getRepository($this->options['payment_class'])->find($id);

        if (null === $payment) {
            throw new PaymentNotFoundException(sprintf('The payment with ID "%d" was not found.', $id));
        }

        $plugin = $this->getPlugin($payment->getPaymentInstruction()->getPaymentSystemName());
        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updatePayment($payment);

                $this->documentManager->persist($payment);
                $this->documentManager->flush();
            } catch (PluginFunctionNotSupportedException $notSupported) {}
        }

        return $payment;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseApproval($paymentId, $amount)
    {
        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doReverseApproval($payment, $amount);

            $this->documentManager->persist($payment);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();

            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    public function reverseCredit($creditId, $amount)
    {
        try {
            $credit = $this->getCredit($creditId);

            $result = $this->doReverseCredit($credit, $amount);

            $this->documentManager->persist($credit);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();

            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    public function reverseDeposit($paymentId, $amount)
    {
        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doReverseDeposit($payment, $amount);

            $this->documentManager->persist($payment);
            $this->documentManager->persist($result->getFinancialTransaction());
            $this->documentManager->persist($result->getPaymentInstruction());
            $this->documentManager->flush();

            return $result;
        } catch (\Exception $failure) {
            throw $failure;
        }
    }

    protected function buildCredit(PaymentInstructionInterface $paymentInstruction, $amount)
    {
        $class =& $this->options['credit_class'];
        $credit = new $class($paymentInstruction, $amount);

        return $credit;
    }

    protected function buildFinancialTransaction()
    {
        $class =& $this->options['financial_transaction_class'];

        return new $class;
    }

    protected function createFinancialTransaction(PaymentInterface $payment)
    {
        if (!$payment instanceof Payment) {
            throw new Exception('This controller only supports Doctrine MongoDB ODM documents as Payment objects.');
        }

        $class =& $this->options['financial_transaction_class'];
        $transaction = new $class();
        $payment->addTransaction($transaction);

        return $transaction;
    }

    protected function doCreatePayment(PaymentInstructionInterface $instruction, $amount)
    {
        if (!$instruction instanceof PaymentInstruction) {
            throw new Exception('This controller only supports Doctrine MongoDB ODM documents as PaymentInstruction objects.');
        }

        $class =& $this->options['payment_class'];

        return new $class($instruction, $amount);
    }

    protected function doCreatePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $this->documentManager->persist($instruction);
        $this->documentManager->flush();
    }

    protected function doGetPaymentInstruction($id)
    {
        $paymentInstruction = $this->documentManager->getRepository($this->options['payment_instruction_class'])->findOneBy(array('id' => $id));

        if (null === $paymentInstruction) {
            throw new PaymentInstructionNotFoundException(sprintf('The payment instruction with ID "%d" was not found.', $id));
        }

        return $paymentInstruction;
    }
}