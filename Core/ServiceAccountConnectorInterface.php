<?php

namespace Alhames\ApiHelperBundle\Core;

use Alhames\ApiHelperBundle\Account\AbstractAccount;
use Alhames\ApiHelperBundle\Exception\ConnectAccountException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface ServiceAccountConnectorInterface.
 */
interface ServiceAccountConnectorInterface
{
    /**
     * @return bool
     */
    public function isAuthorized();

    /**
     * @param string     $message
     * @param \Exception $exception
     *
     * @return RedirectResponse
     */
    public function createErrorRedirect($message, \Exception $exception = null);

    /**
     * @param Request         $request
     * @param AbstractAccount $account
     *
     * @throws ConnectAccountException
     *
     * @return RedirectResponse
     */
    public function connectAccount(Request $request, AbstractAccount $account);
}
