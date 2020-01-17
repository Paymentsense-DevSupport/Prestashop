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
 * Customer Redirect Front-End Controller
 *
 * Handles the customer redirect from the Hosted Payment Form
 */
class PaymentsenseCustomerRedirectModuleFrontController extends ModuleFrontController
{
    /**
     * Response title messages
     */
    const MSG_TITLE_SUCCESS       = 'Payment Successful';
    const MSG_TITLE_FAIL          = 'Payment Failed';
    const MSG_TITLE_ERROR         = 'Payment Processing Error';

    /**
     * Response messages
     */
    const MSG_SUCCESS             = 'Your payment was successful, you should receive a confirmation by email from us shortly. ' .
    'Thank you for your order.';
    const MSG_ORDER_FAILED        = 'There has been a problem with your payment. Your order has not been successful.';
    const MSG_NOT_CONFIGURED      = 'The plugin is not configured. Please contact support.';
    const MSG_HASH_DIGEST_ERROR   = 'Invalid Hash Digest. Please contact support.';
    const MSG_RESP_RETRIEVE_ERROR = 'An error occurred while retrieving the transaction result. Please contact support.';
    const MSG_RESP_PARSE_ERROR    = 'An error occurred while processing the transaction result. Please contact support.';
    const MSG_ORDER_UPDATE_ERROR  = 'An error occurred during order update. Please contact support.';
    const MSG_EXCEPTION           = 'An exception with message "%s" has been thrown. Please contact support.';

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
            $headers = array(
                'User-Agent: ' . $this->module->getModuleInternalName() . ' v.' . $this->module->getModuleInstalledVersion(),
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: */*',
                'Accept-Encoding: identity',
                'Connection: close'
            );
            $data = array(
                'url'     => 'https://mms.paymentsensegateway.com/Pages/PublicPages/PaymentFormResultHandler.ashx',
                'headers' => $headers,
                'data'    => "MerchantID=" . Configuration::get('PAYMENTSENSE_GATEWAYID')
                    . "&Password=" . Configuration::get('PAYMENTSENSE_GATEWAYPASS')
                    . "&CrossReference=" . Tools::getValue('CrossReference')
            );
            if ($this->module->performCurl($data, $response) === 0) {
                if (is_string($response) && ($response != '')) {
                    parse_str($response, $response_arr);
                    if (is_array($response_arr) && array_key_exists('TransactionResult', $response_arr)) {
                        parse_str($response_arr['TransactionResult'], $trx_result);
                        if (is_array($trx_result) && !empty($trx_result)) {
                            $statusCode = '';
                            $message = '';
                            $previousStatusCode = '';
                            $previousMessage = '';
                            $amount = '';
                            if (array_key_exists('StatusCode', $trx_result)) {
                                $statusCode = $trx_result['StatusCode'];
                            }
                            if (array_key_exists('Message', $trx_result)) {
                                $message = $trx_result['Message'];
                            }
                            if (array_key_exists('PreviousStatusCode', $trx_result)) {
                                $previousStatusCode = $trx_result['PreviousStatusCode'];
                            }
                            if (array_key_exists('PreviousMessage', $trx_result)) {
                                $previousMessage = $trx_result['PreviousMessage'];
                            }
                            if (array_key_exists('Amount', $trx_result)) {
                                $amount = $trx_result['Amount'] / 100;
                            }
                            $orderState = $this->module->getOrderState(
                                $statusCode,
                                $previousStatusCode
                            );
                            $message = ($statusCode == paymentsense::TRX_RESULT_DUPLICATE)
                                ? $previousMessage
                                : $message;
                            $cartId = $this->module->retrieveCartID();
                            $cart = new Cart($cartId);
                            if (!$this->module->createOrder($cart, $orderState, $message, $amount)) {
                                $this->setError(self::MSG_ORDER_UPDATE_ERROR);
                                return;
                            }
                            if ($orderState != Configuration::get('PS_OS_WS_PAYMENT')) {
                                $this->setFail(self::MSG_ORDER_FAILED, $message);
                                return;
                            }
                        } else {
                            $this->setError(self::MSG_RESP_PARSE_ERROR);
                            return;
                        }
                    } else {
                        $this->setError(self::MSG_RESP_PARSE_ERROR);
                        return;
                    }
                } else {
                    $this->setError(self::MSG_RESP_PARSE_ERROR);
                    return;
                }
            } else {
                $this->setError(self::MSG_RESP_RETRIEVE_ERROR);
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
