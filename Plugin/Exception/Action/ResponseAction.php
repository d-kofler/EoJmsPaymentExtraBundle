<?php

/*
 * This file is part of the EoJmsPaymentExtraBundle package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eo\JmsPaymentExtraBundle\Plugin\Exception\Action;

use Symfony\Component\HttpFoundation\Response;

/**
 * Response required action
 */
class ResponseAction
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * Class constructor
     * 
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get response
     * 
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
