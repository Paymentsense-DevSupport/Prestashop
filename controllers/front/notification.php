<?php
/*
* Copyright (C) 2020 Paymentsense Ltd.
*
* This program is free software: you can redistribute it and/or modify it under the terms
* of the AFL Academic Free License as published by the Free Software Foundation, either
* version 3 of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the AFL Academic Free License for more details. You should have received a copy of the
* AFL Academic Free License along with this program. If not, see <http://opensource.org/licenses/AFL-3.0/>.
*
*  @author Paymentsense <devsupport@paymentsense.com>
*  @copyright  2020 Paymentsense Ltd.
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Notifications Front-End Controller
 *
 * Handles the notifications received from Paymentsense by using the SERVER Result Delivery Method
 */
class PaymentsenseNotificationModuleFrontController extends ModuleFrontController
{
    /**
     * Response status codes
     */
    const STATUS_CODE_OK           = '0';
    const STATUS_CODE_ERROR        = '30';

    /**
     * Response messages
     */
    const MSG_SUCCESS              = 'Request processed successfully.';
    const MSG_NOT_CONFIGURED       = 'The plugin is not configured.';
    const MSG_HASH_DIGEST_ERROR    = 'Invalid Hash Digest.';
    const MSG_INVALID_CART_ID      = 'Invalid Cart ID %d.';
    const MSG_ORDER_ALREADY_EXISTS = 'An order for Cart ID %d already exists.';
    const MSG_EXCEPTION            = 'An exception with message "%s" has been thrown. Please contact support.';

    /** @var Paymentsense */
    public $module;

    /** @var array */
    private $responseVars = array(
        'status_code' => '',
        'message'     => ''
    );

    /**
     * @see FrontController::initContent()
     *
     * @throws PrestaShopException
     */
    public function initContent()
    {
        parent::initContent();
        $this->processRequest();
        $this->outputResponse();
    }

    /**
     * Processes the notification request from the Paymentsense gateway
     */
    private function processRequest()
    {
        try {
            if (!$this->module->isConfigured()) {
                $this->setError(self::MSG_NOT_CONFIGURED);
                return;
            }
            if (!$this->module->isHashDigestValid(Paymentsense::REQ_NOTIFICATION)) {
                $this->setError(self::MSG_HASH_DIGEST_ERROR);
                return;
            }
            $cartId = $this->module->retrieveCartID();
            $cart   = new Cart($cartId);
            if (!Validate::isLoadedObject($cart)) {
                $this->setError(sprintf(self::MSG_INVALID_CART_ID, $cartId));
                return;
            }
            if ($cart->orderExists()) {
                $this->setError(sprintf(self::MSG_ORDER_ALREADY_EXISTS, $cart->id));
                return;
            }
            $this->setSuccess();
        } catch (Exception $exception) {
            $this->setError(sprintf(self::MSG_EXCEPTION, $exception->getMessage()));
        }
    }

    /**
     * Sets the success response message and status code
     */
    private function setSuccess()
    {
        $this->setResponse(self::STATUS_CODE_OK, self::MSG_SUCCESS);
    }

    /**
     * Sets the error response message and status code
     *
     * @param string $message Response message
     *
     */
    private function setError($message)
    {
        $this->setResponse(self::STATUS_CODE_ERROR, $message);
    }

    /**
     * Sets the response variables
     *
     * @param string $statusCode Response status code
     * @param string $message Response message
     */
    private function setResponse($statusCode, $message)
    {
        $this->responseVars['status_code'] = $statusCode;
        $this->responseVars['message']     = $message;
    }

    /**
     * Outputs the response
     *
     * @throws PrestaShopException
     */
    private function outputResponse()
    {
        $this->context->smarty->assign('paymentsense', $this->responseVars, true);
        $this->setTemplate('module:paymentsense/views/templates/front/payment_notification.tpl');
    }
}
