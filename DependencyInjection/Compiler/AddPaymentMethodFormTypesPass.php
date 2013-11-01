<?php

/*
 * This file is part of the EoJmsPaymentExtraBundle package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\JmsPaymentExtraBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Wires payment method types.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Eymen Gunay <eymen@egunay.com>
 */
class AddPaymentMethodFormTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('eo_jms_payment_extra.form.type.choose_payment_method')) {
            return;
        }

        $paymentMethodFormTypes = array();
        foreach ($container->findTaggedServiceIds('payment.method_form_type') as $id => $attributes) {
            $definition = $container->getDefinition($id);

            // check that this definition is also registered as a form type
            $attributes = $definition->getTag('form.type');
            if (!$attributes) {
                throw new \RuntimeException(sprintf('The service "%s" is marked as payment method form type (tagged with "payment.method_form_type"), but is not registered as a form type with the Form Component. Please also add a "form.type" tag.', $id));
            }

            if (!isset($attributes[0]['alias'])) {
                throw new \RuntimeException(sprintf('Please define an alias attribute for tag "form.type" of service "%s".', $id));
            }

            $paymentMethodFormTypes[] = $attributes[0]['alias'];
        }


        $container->getDefinition('eo_jms_payment_extra.form.type.choose_payment_method')
            ->addArgument($paymentMethodFormTypes);
    }
}