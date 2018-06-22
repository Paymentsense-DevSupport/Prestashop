<?php
/*
* Copyright (C) 2018 Paymentsense Ltd.
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
*  @copyright  2018 Paymentsense Ltd.
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Customer Redirect Front-End Controller
 *
 * Handles the customer redirect from the Hosted Payment Form
 */
class PaymentsenseCustomerRedirectModuleFrontController extends ModuleFrontController
{
    /**
     * Response title messages
     */
    const MSG_TITLE_SUCCESS     = 'Payment Successful';
    const MSG_TITLE_FAIL        = 'Payment Failed';
    const MSG_TITLE_ERROR       = 'Payment Processing Error';

    /**
     * Response messages
     */
    const MSG_SUCCESS           = 'Your payment was successful, you should receive a confirmation by email from us shortly. ' .
    'Thank you for your order.';
    const MSG_ORDER_FAILED      = 'There has been a problem with your payment. Your order has not been successful.';
    const MSG_NOT_CONFIGURED    = 'The plugin is not configured. Please contact support.';
    const MSG_HASH_DIGEST_ERROR = 'Invalid Hash Digest. Please contact support.';
    const MSG_ORDER_NOT_FOUND   = 'No order for Cart ID %d exists. Please contact support.';
    const MSG_ORDER_NOT_EXISTS  = 'Order ID %d does not exists. Please contact support.';
    const MSG_EXCEPTION         = 'An exception with message "%s" has been thrown. Please contact support.';

    /** @var Paymentsense */
    public $module;

    /** @var array */
    private $responseVars = array(
        'title'           => '',
        'message'         => '',
        'gateway_message' => '',
        'debug_info'      => ''
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
     * Processes the customer redirect from the Paymentsense gateway
     */
    private function processRequest()
    {
        try {
            $this->addDebugInfo('HTTP_VARS', Tools::getAllValues());
            if (!$this->module->isConfigured()) {
                $this->setError(self::MSG_NOT_CONFIGURED);
                return;
            }
            if (!$this->module->isHashDigestValid(Paymentsense::REQ_CUSTOMER_REDIRECT)) {
                $this->setError(self::MSG_HASH_DIGEST_ERROR);
                return;
            }
            $cartId  = $this->module->retrieveCartID();
            $orderId = Order::getIdByCartId($cartId);
            if (empty($orderId)) {
                $this->setError(sprintf(self::MSG_ORDER_NOT_FOUND, $cartId));
                return;
            }
            $order   = new Order($orderId);
            $this->addDebugInfo('ORDER', $order);
            if (!Validate::isLoadedObject($order)) {
                $this->setError(sprintf(self::MSG_ORDER_NOT_EXISTS, $orderId));
                return;
            }
            if (!$this->module->isOrderPaid($order)) {
                $this->setFail(self::MSG_ORDER_FAILED, $order->getFirstMessage());
                return;
            }
            $this->setSuccess();
        } catch (Exception $exception) {
            $this->setError(sprintf(self::MSG_EXCEPTION, $exception->getMessage()));
        }
    }

    /**
     * Sets the success response title and message
     */
    private function setSuccess()
    {
        $this->setResponse(self::MSG_TITLE_SUCCESS, self::MSG_SUCCESS);
    }

    /**
     * Sets the fail response title and message
     *
     * @param string $message Response message
     * @param string $gatewayMessage Gateway message
     */
    private function setFail($message, $gatewayMessage)
    {
        $this->setResponse(self::MSG_TITLE_FAIL, $message, $gatewayMessage);
    }

    /**
     * Sets the error response title and message
     *
     * @param string $message Response message
     */
    private function setError($message)
    {
        $this->setResponse(self::MSG_TITLE_ERROR, $message);
    }

    /**
     * Sets the response variables
     *
     * @param string $title Title
     * @param string $message Message
     * @param string $gatewayMessage Gateway message
     */
    private function setResponse($title, $message, $gatewayMessage = '')
    {
        $this->responseVars['title']           = $title;
        $this->responseVars['message']         = $message;
        $this->responseVars['gateway_message'] = $gatewayMessage;
    }

    /**
     * Adds debug information if debug is enabled
     *
     * @param string $name
     * @param string $data
     */
    private function addDebugInfo($name, $data)
    {
        if (Configuration::get('PAYMENTSENSE_DEBUG') === 'True') {
            $this->responseVars['debug_info'] .= $name . ': ' . var_export($data, true) . PHP_EOL;
        }
    }

    /**
     * Outputs the response
     *
     * @throws PrestaShopException
     */
    private function outputResponse()
    {
        $this->context->smarty->assign('paymentsense', $this->responseVars, true);
        $this->setTemplate('module:paymentsense/views/templates/front/customer_redirect.tpl');
    }
}
