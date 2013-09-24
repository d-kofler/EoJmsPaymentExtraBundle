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

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddPaymentPluginsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('eo_jms_payment_extra.ppc_document')) {
            return;
        }

        $def = $container->findDefinition('eo_jms_payment_extra.ppc_document');
        foreach ($container->findTaggedServiceIds('payment.plugin') as $id => $attr) {
            $def->addMethodCall('addPlugin', array(new Reference($id)));
        }
    }
}