<?php

/*
 * This file is part of the EoJmsPaymentExtraBundle package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\JmsPaymentExtraBundle;

use Eo\JmsPaymentExtraBundle\DependencyInjection\Compiler\AddPaymentMethodFormTypesPass;
use Eo\JmsPaymentExtraBundle\DependencyInjection\Compiler\AddPaymentPluginsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EoJmsPaymentExtraBundle extends Bundle
{
    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);

        $builder->addCompilerPass(new AddPaymentPluginsPass());
        $builder->addCompilerPass(new AddPaymentMethodFormTypesPass());
    }
}
