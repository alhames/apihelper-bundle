<?php

namespace ApiHelperBundle;

use ApiHelperBundle\DependencyInjection\ApiHelperExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class PgApiHelperBundle.
 */
class ApiHelperBundle extends Bundle
{
    /**
     * @return ApiHelperExtension
     */
    public function getContainerExtension()
    {
        return new ApiHelperExtension();
    }
}
