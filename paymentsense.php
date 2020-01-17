<?php
/*
* Prestashop Paymentsense Re-Directed Payment Module
* Copyright (C) 2020 Paymentsense.
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
*  @copyright  2020 Paymentsense
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*

File Modified: 12/03/2013 - By Shaun Ponting - Opal Creations.
File Modified: 28/06/2013 - By Shaun Ponting - Opal Creations - Updated Licence, XSS.
File Modified: 16/07/2013 - By Lewis Ayres-Stephens - Paymentsense - Multi Currency
V1.8 - File Modified: 23/07/2013 - By Lewis Ayres-Stephens - Paymentsense - img file structure
V1.9 - File Modified: 17/08/2013 - By Adam Watkins - Opal Creations:
                            -> updated all use of strlen, substr to use Tools::
                            -> Fixed use of (INTVAL) to swap with (int)
                            -> Also added index.php files
                            -> altered folder file structure
                            -> improved code formatting a little
V1.9.1 - File Modified: 28/08/2013 - By Adam Watkins - Opal Creations, see changelog provided to Paymentsense
V1.9.2 - File Modified: 09/10/2013 - By Lewis Ayres-Stephens - Paymentsense - replaced ‘global $smarty' with the context : ‘$this->context->smarty’
V2.0.0 - File Modified: 11/04/2014 - By Paul Moscrop - Opal Creations (Character encodings). Adam Watkins -> Updated base package pricing to 14.95
V2.0.1 - File Modified: 29/03/2016 - By Ryan O'Donnell - Paymentsense - Missing ')'. Removed Regex check for MerchantID and Password, replaced with link to online checker.
V2.1.0 - File Modified: 22/06/2018 - By Alexander Kaltchev - Dev Advisory UK - Added support for PrestaShop 1.7
V2.1.1 - File Modified: 26/07/2019 - By Alexander Kaltchev - Dev Advisory UK - Updated the conversion of the numeric country ISO 3166-1 codes
V2.1.2 - File Modified: 17/01/2020 - By Alexander Kaltchev - Dev Advisory UK - Added module information reporting feature
                                                                             - Changed module configuration settings page

A complete list of changes can be found in the changelog file (changelog.txt).
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Paymentsense extends PaymentModule
{
    /**
     * Module name. Used in the module information reporting
     */
    const MODULE_NAME = 'Paymentsense Module for PrestaShop';

    /**
     * Module version
     */
    const MODULE_VERSION = '2.1.2';

    /**
     * Transaction Result Codes
     */
    const TRX_RESULT_SUCCESS    = '0';
    const TRX_RESULT_INCOMPLETE = '3';
    const TRX_RESULT_REFERRED   = '4';
    const TRX_RESULT_DECLINED   = '5';
    const TRX_RESULT_DUPLICATE  = '20';
    const TRX_RESULT_FAILED     = '30';

    /**
     * Request Types
     */
    const REQ_NOTIFICATION      = '0';
    const REQ_CUSTOMER_REDIRECT = '1';

    public function __construct()
    {
        $this->version    = self::MODULE_VERSION;
        $this->name       = 'paymentsense';
        $this->tab        = 'payments_gateways';
        $this->author     = 'Paymentsense Ltd.';
        $this->module_key = '1e631b52ed3d1572df477b9ce182ccf9';

        $this->currencies      = true;
        $this->currencies_mode = 'radio';

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Paymentsense Payments');
        $this->description = $this->l('Process transactions through the Paymentsense gateway.');
        
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Installer
     *
     * @return bool
     */
    public function install()
    {
        return (parent::install() && Configuration::updateValue('PAYMENTSENSE_GATEWAYID', '')
                                  && Configuration::updateValue('PAYMENTSENSE_GATEWAYPASS', '')
                                  && Configuration::updateValue('PAYMENTSENSE_PSK', '')
                                  && Configuration::updateValue('PAYMENTSENSE_DEBUG', '')
                                  && Configuration::updateValue('PAYMENTSENSE_TRANSACTION_TYPE', '')
                                  && $this->registerHook('payment')
                                  && $this->registerHook('paymentOptions')
                                  && $this->registerHook('paymentReturn'));
    }

    /**
     * Uninstaller
     *
     * @return bool
     */
    public function uninstall()
    {
        return (Configuration::deleteByName('PAYMENTSENSE_GATEWAYID')
            && Configuration::deleteByName('PAYMENTSENSE_GATEWAYPASS')
            && Configuration::deleteByName('PAYMENTSENSE_PSK')
            && Configuration::deleteByName('PAYMENTSENSE_DEBUG')
            && Configuration::deleteByName('PAYMENTSENSE_TRANSACTION_TYPE')
            && parent::uninstall());
    }

    /**
     * Hooks Payment to show Paymentsense on the checkout page
     * (Used by PrestaShop 1.6)
     *
     * @param array $params
     *
     * @return string|false;
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->isConfigured()) {
            return false;
        }

        $this->context->smarty->assign($this->buildHostedFormData($params));
        return $this->display(__FILE__, 'views/templates/front/paymentsense.tpl');
    }

    /**
     * Hooks Payment Options to show Paymentsense on the checkout page
     * (Used by PrestaShop 1.7)
     *
     * @param array $params
     *
     * @return array|false
     *
     * @throws SmartyException
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return false;
        }

        if (!$this->isConfigured()) {
            return false;
        }

        $formData = $this->buildHostedFormData($params);
        $this->context->smarty->assign(
            'paymentsense',
            array(
                'name'       => $this->name,
                'path'       => $this->getPathUri(),
                'formparams' => $formData['parameters']
            ),
            true
        );
        $paymentOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentOption->setCallToActionText('Pay by debit or credit card using Paymentsense')
            ->setForm(
                '<form method="post" action="' . $formData['form_target'] .
                '" onsubmit="return onSubmitPaymentsense(this);"></form>'
            )
            ->setAdditionalInformation(
                $this->context->smarty->fetch("module:paymentsense/views/templates/front/payment_option.tpl")
            );

        return array($paymentOption);
    }

    /**
     * Gets module name
     *
     * @return string
     */
    public function getModuleInternalName()
    {
        return self::MODULE_NAME;
    }

    /**
     * Gets module installed version
     *
     * @return string
     */
    public function getModuleInstalledVersion()
    {
        return Paymentsense::MODULE_VERSION;
    }


    /**
     * Checks whether the payment method is configured
     *
     * @return bool True if gateway merchant ID, password and pre-shared key are non-empty, otherwise false
     */
    public function isConfigured()
    {
        return ((trim(Configuration::get('PAYMENTSENSE_GATEWAYID')) != '') &&
            (trim(Configuration::get('PAYMENTSENSE_GATEWAYPASS')) != '') &&
            (trim(Configuration::get('PAYMENTSENSE_PSK')) != ''));
    }

    /**
     * Determines whether the order is paid
     *
     * @param OrderCore $order
     *
     * @return bool
     */
    public function isOrderPaid($order)
    {
        $result = false;
        $paidStatuses = array(
            Configuration::get('PS_OS_WS_PAYMENT'),
            Configuration::get('PS_OS_PAYMENT'),
            Configuration::get('PS_OS_OUTOFSTOCK_PAID')
        );
        if (Validate::isLoadedObject($order)) {
            $result = in_array($order->getCurrentState(), $paidStatuses);
        }
        return $result;
    }

    /**
     * Checks whether the hash digest received from the payment gateway is valid
     *
     * @param string $requestType Type of the request (notification or customer redirect)
     *
     * @return bool
     */
    public function isHashDigestValid($requestType)
    {
        $result = false;
        $data   = $this->buildPostString($requestType);
        if ($data) {
            $hashDigestReceived   = Tools::getValue('HashDigest');
            $hashDigestCalculated = $this->calculateHashDigest(
                $data,
                'SHA1', // Hardcoded as per the current plugin implementation
                Configuration::get('PAYMENTSENSE_PSK')
            );
            $result = strToUpper($hashDigestReceived) === strToUpper($hashDigestCalculated);
        }
        return $result;
    }

    /**
     * Retrieves the Cart ID from the OrderID POST variable received by the payment gateway
     *
     * @return int|false
     */
    public function retrieveCartID()
    {
        $result  = false;
        $orderId = Tools::getValue('OrderID');
        $cartId  = Tools::substr($orderId, strpos($orderId, '~') + 1);
        if (is_string($cartId) && ($cartId != '')) {
            $result = (int) $cartId;
        }
        return $result;
    }

    /**
     * Creates the order
     *
     * @param object $cart
     * @param string $orderState
     * @param string $message
     * @param float $amount
     *
     * @return bool
     *
     * @throws Exception
     */
    public function createOrder($cart, $orderState, $message, $amount)
    {
        $customer = new Customer((int)$cart->id_customer);
        return $this->validateOrder(
            $cart->id,
            $orderState,
            $amount,
            $this->displayName,
            $message,
            array(),
            null,
            true,
            $customer->secure_key
        );
    }

    /**
     * Gets the content of the configuration settings page
     *
     * @return string Rendered template output
     */
    public function getContent()
    {
        $params = array(
            'notice_type'           => '',
            'notice_text'           => '',
            'form_action'           => $_SERVER['REQUEST_URI'],
            'form_var_gateway_id'   => $this->getSetting('PAYMENTSENSE_GATEWAYID'),
            'form_var_gateway_pass' => $this->getSetting('PAYMENTSENSE_GATEWAYPASS'),
            'form_var_gateway_psk'  => $this->getSetting('PAYMENTSENSE_PSK'),
            'form_var_trx_type'     => $this->getSetting('PAYMENTSENSE_TRANSACTION_TYPE'),
            'form_var_debug'        => $this->getSetting('PAYMENTSENSE_DEBUG')
        );
        if (Tools::isSubmit('btnSubmit')) {
            if ($this->updateConfigSettings()) {
                $params['notice_type'] = 'success';
                $params['notice_text'] = 'Settings updated';
            } else {
                $params['notice_type'] = 'danger';
                $params['notice_text'] = 'Settings update error';
            }
        }
        $this->context->smarty->assign($params);
        return $this->display(__FILE__, 'views/templates/admin/form.tpl');
    }

    /**
     * Gets order state based on transaction status code
     *
     * @param string $statusCode Transaction status code
     * @param string $prevStatusCode Previous transaction status code
     *
     * @return string
     */
    public function getOrderState($statusCode, $prevStatusCode)
    {
        switch ($statusCode) {
            case self::TRX_RESULT_SUCCESS:
                $orderState = Configuration::get('PS_OS_WS_PAYMENT');
                break;
            case self::TRX_RESULT_DUPLICATE:
                if ($prevStatusCode === self::TRX_RESULT_SUCCESS) {
                    $orderState = Configuration::get('PS_OS_WS_PAYMENT');
                } else {
                    $orderState = Configuration::get('PS_OS_ERROR');
                }
                break;
            default:
                $orderState = Configuration::get('PS_OS_ERROR');
        }
        return $orderState;
    }

    /**
     * Performs a cURL request
     *
     * @param array $data cURL data.
     * @param mixed $response the result or false on failure.
     *
     * @return int the error number or 0 if no error occurred
     */
    public function performCurl($data, &$response)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $data['headers']);
        curl_setopt($ch, CURLOPT_URL, $data['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!empty($data['data'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data['data']);
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }
        $response = curl_exec($ch);
        $err_no   = curl_errno($ch);
        curl_close($ch);
        return $err_no;
    }

    /**
     * Gets a setting from the HTTP POST variables or the configuration
     *
     * @param string $name Setting name
     *
     * @return string
     */
    protected function getSetting($name)
    {
        switch (true) {
            case array_key_exists($name, $_POST):
                $result = Tools::getValue($name);
                break;
            case Configuration::get($name) !== false:
                $result = Configuration::get($name);
                break;
            default:
                $result = '';
                break;
        }
        return $result;
    }

    /**
     * Updates the configuration settings
     *
     * @return bool
     */
    protected function updateConfigSettings()
    {
        $result   = true;
        $settings = array(
            'PAYMENTSENSE_GATEWAYID',
            'PAYMENTSENSE_GATEWAYPASS',
            'PAYMENTSENSE_PSK',
            'PAYMENTSENSE_TRANSACTION_TYPE',
            'PAYMENTSENSE_DEBUG'
        );
        foreach ($settings as $setting) {
            $value = Tools::getValue($setting);
            if ($value != '') {
                Configuration::updateValue($setting, $value);
            } else {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * Builds the data for the form redirecting to the Hosted Payment Form
     *
     * Common for all PrestaShop versions
     *
     * Uses the code of the original hookPayment class method with following modifications:
     * 1. The code for checking whether the module is active and displaying still resides in hookPayment
     * 2. The values for the CallbackURL and ServerResultURL parameters are set by using the class methods
     * getCustomerRedirectUrl and getNotificationUrl
     * 3. The format of the TransactionDateTime parameter is changed from 'Y-m-d H:i:s O' to 'Y-m-d H:i:s P' as per the
     * current format defined in the Hosted Payment Form Credit & Debit Card Processing document v2.5.01 section
     * Required Input Variables
     *
     * @param array $params
     *
     * @return array
     */
    protected function buildHostedFormData($params)
    {
        $address = new Address((int)($params['cart']->id_address_invoice));
        $customer = new Customer((int)($params['cart']->id_customer));

        $cart_currency = $this->getsCurrencyIdIsoCode((int)$params['cart']->id_currency);

        $orderTotal = number_format($params['cart']->getOrderTotal(true, Cart::BOTH), 2, '.', '');
        $amount = $orderTotal * 100;

        $paymentsense_merchantid      = Configuration::get('PAYMENTSENSE_GATEWAYID');
        $paymentsense_gatewaypass     = Configuration::get('PAYMENTSENSE_GATEWAYPASS');
        $paymentsense_psk             = Configuration::get('PAYMENTSENSE_PSK');
        $paymentsense_transactiontype = Configuration::get('PAYMENTSENSE_TRANSACTION_TYPE');

        $datestamp = date('Y-m-d H:i:s P');
        $gatewayorderID = date('Ymd-His').'~'.$params['cart']->id;

        if ($address->phone != '') {
            $PhoneNumber = $address->phone;
        } else {
            $PhoneNumber = $address->phone_mobile;
        }

        $HashString = 'PreSharedKey='.$paymentsense_psk;
        $HashString .= '&MerchantID='.$paymentsense_merchantid;
        $HashString .= '&Password='.$paymentsense_gatewaypass;
        $HashString .= '&Amount='.$amount;
        $HashString .= '&CurrencyCode='.$this->getCurrencyIsoCode($cart_currency);
        $HashString .= '&EchoAVSCheckResult=True';
        $HashString .= '&EchoCV2CheckResult=True';
        $HashString .= '&EchoThreeDSecureAuthenticationCheckResult=True';
        $HashString .= '&EchoCardType=True';
        $HashString .= '&OrderID='.$gatewayorderID;
        $HashString .= '&TransactionType='.$paymentsense_transactiontype;
        $HashString .= '&TransactionDateTime='.$datestamp;
        $HashString .= '&CallbackURL='.$this->getCustomerRedirectUrl();
        $HashString .= '&OrderDescription='.$gatewayorderID;
        $HashString .= '&CustomerName='.$customer->firstname.' '.$customer->lastname;
        $HashString .= '&Address1='.$address->address1;
        $HashString .= '&Address2='.$address->address2;
        $HashString .= '&Address3=';
        $HashString .= '&Address4=';
        $HashString .= '&City='.$address->city;
        $HashString .= '&State=';
        $HashString .= '&PostCode='.$address->postcode;
        $HashString .= '&CountryCode='.$this->getCountryIsoNumericCode($this->context->country->iso_code);
        $HashString .= '&EmailAddress='.$customer->email;
        $HashString .= '&PhoneNumber='.$PhoneNumber;
        $HashString .= '&EmailAddressEditable=False';
        $HashString .= '&PhoneNumberEditable=False';
        $HashString .= '&CV2Mandatory=True';
        $HashString .= '&Address1Mandatory=True';
        $HashString .= '&CityMandatory=True';
        $HashString .= '&PostCodeMandatory=True';
        $HashString .= '&StateMandatory=False';
        $HashString .= '&CountryMandatory=True';
        $HashString .= '&ResultDeliveryMethod=SERVER';
        $HashString .= '&ServerResultURL=' . $this->getNotificationUrl();
        $HashString .= '&PaymentFormDisplaysResult=False';
        $HashString .= '&ServerResultURLCookieVariables='.'';
        $HashString .= '&ServerResultURLFormVariables=orderTotal='.$orderTotal;
        $HashString .= '&ServerResultURLQueryStringVariables=';
        $HashDigest = sha1($HashString);

        $parameters = array();
        $parameters['HashDigest'] = $HashDigest;
        $parameters['MerchantID'] = $paymentsense_merchantid;
        $parameters['Amount'] = $amount;
        $parameters['CurrencyCode'] = $this->getCurrencyIsoCode($cart_currency);
        $parameters['EchoAVSCheckResult'] = 'True';
        $parameters['EchoCV2CheckResult'] = 'True';
        $parameters['EchoThreeDSecureAuthenticationCheckResult'] = 'True';
        $parameters['EchoCardType'] = 'True';
        $parameters['OrderID'] = $gatewayorderID;
        $parameters['TransactionType'] = $paymentsense_transactiontype;
        $parameters['TransactionDateTime'] = $datestamp;
        $parameters['CallbackURL'] = $this->getCustomerRedirectUrl();
        $parameters['OrderDescription'] = $gatewayorderID;
        $parameters['CustomerName'] = $customer->firstname.' '.$customer->lastname;
        $parameters['Address1'] = $address->address1;
        $parameters['Address2'] = $address->address2;
        $parameters['Address3'] = '';
        $parameters['Address4'] = '';
        $parameters['City'] = $address->city;
        $parameters['State'] = '';
        $parameters['PostCode'] = $address->postcode;
        $parameters['CountryCode'] = $this->getCountryIsoNumericCode($this->context->country->iso_code);
        $parameters['EmailAddress'] = $customer->email;
        $parameters['PhoneNumber'] = $PhoneNumber;
        $parameters['EmailAddressEditable'] = 'False';
        $parameters['PhoneNumberEditable'] = 'False';
        $parameters['CV2Mandatory'] = 'True';
        $parameters['Address1Mandatory'] = 'True';
        $parameters['CityMandatory'] = 'True';
        $parameters['PostCodeMandatory'] = 'True';
        $parameters['StateMandatory'] = 'False';
        $parameters['CountryMandatory'] = 'True';
        $parameters['ResultDeliveryMethod'] = 'SERVER';
        $parameters['ServerResultURL'] = $this->getNotificationUrl();
        $parameters['PaymentFormDisplaysResult'] = 'False';
        $parameters['ServerResultURLCookieVariables'] = '';
        $parameters['ServerResultURLFormVariables'] = 'orderTotal='.$orderTotal;
        $parameters['ServerResultURLQueryStringVariables'] = '';
        $parameters['ThreeDSecureCompatMode'] = 'false';
        $parameters['ServerResultCompatMode'] = 'false';
        $form_target = 'https://mms.paymentsensegateway.com/Pages/PublicPages/PaymentForm.aspx';

        return array('parameters' => $parameters, 'form_target' => $form_target);
    }

    /**
     * Gets the ISO 4217 code of a currency_id
     *
     * @param int $id_currency
     *
     * @return string
     */
    protected function getsCurrencyIdIsoCode($id_currency)
    {
        $db = Db::getInstance();
        $query = 'SELECT `iso_code` FROM '._DB_PREFIX_.'currency WHERE `id_currency` = '.(int)$id_currency;
        return $db->getValue($query);
    }

    /**
     * Gets the numeric currency ISO 4217 code
     *
     * @param string $currencyCode Currency 4217 code.
     * @param string $defaultCode Default currency code.
     *
     * @return string
     */
    protected static function getCurrencyIsoCode($currencyCode, $defaultCode = '826')
    {
        $result   = $defaultCode;
        $isoCodes = array(
            'AED' => '784',
            'AFN' => '971',
            'ALL' => '8',
            'AMD' => '51',
            'ANG' => '532',
            'AOA' => '973',
            'ARS' => '32',
            'AUD' => '36',
            'AWG' => '533',
            'AZN' => '944',
            'BAM' => '977',
            'BBD' => '52',
            'BDT' => '50',
            'BGN' => '975',
            'BHD' => '48',
            'BIF' => '108',
            'BMD' => '60',
            'BND' => '96',
            'BOB' => '68',
            'BOV' => '984',
            'BRL' => '986',
            'BSD' => '44',
            'BTN' => '64',
            'BWP' => '72',
            'BYN' => '933',
            'BZD' => '84',
            'CAD' => '124',
            'CDF' => '976',
            'CHE' => '947',
            'CHF' => '756',
            'CHW' => '948',
            'CLF' => '990',
            'CLP' => '152',
            'CNY' => '156',
            'COP' => '170',
            'COU' => '970',
            'CRC' => '188',
            'CUC' => '931',
            'CUP' => '192',
            'CVE' => '132',
            'CZK' => '203',
            'DJF' => '262',
            'DKK' => '208',
            'DOP' => '214',
            'DZD' => '12',
            'EGP' => '818',
            'ERN' => '232',
            'ETB' => '230',
            'EUR' => '978',
            'FJD' => '242',
            'FKP' => '238',
            'GBP' => '826',
            'GEL' => '981',
            'GHS' => '936',
            'GIP' => '292',
            'GMD' => '270',
            'GNF' => '324',
            'GTQ' => '320',
            'GYD' => '328',
            'HKD' => '344',
            'HNL' => '340',
            'HRK' => '191',
            'HTG' => '332',
            'HUF' => '348',
            'IDR' => '360',
            'ILS' => '376',
            'INR' => '356',
            'IQD' => '368',
            'IRR' => '364',
            'ISK' => '352',
            'JMD' => '388',
            'JOD' => '400',
            'JPY' => '392',
            'KES' => '404',
            'KGS' => '417',
            'KHR' => '116',
            'KMF' => '174',
            'KPW' => '408',
            'KRW' => '410',
            'KWD' => '414',
            'KYD' => '136',
            'KZT' => '398',
            'LAK' => '418',
            'LBP' => '422',
            'LKR' => '144',
            'LRD' => '430',
            'LSL' => '426',
            'LYD' => '434',
            'MAD' => '504',
            'MDL' => '498',
            'MGA' => '969',
            'MKD' => '807',
            'MMK' => '104',
            'MNT' => '496',
            'MOP' => '446',
            'MRU' => '929',
            'MUR' => '480',
            'MVR' => '462',
            'MWK' => '454',
            'MXN' => '484',
            'MXV' => '979',
            'MYR' => '458',
            'MZN' => '943',
            'NAD' => '516',
            'NGN' => '566',
            'NIO' => '558',
            'NOK' => '578',
            'NPR' => '524',
            'NZD' => '554',
            'OMR' => '512',
            'PAB' => '590',
            'PEN' => '604',
            'PGK' => '598',
            'PHP' => '608',
            'PKR' => '586',
            'PLN' => '985',
            'PYG' => '600',
            'QAR' => '634',
            'RON' => '946',
            'RSD' => '941',
            'RUB' => '643',
            'RWF' => '646',
            'SAR' => '682',
            'SBD' => '90',
            'SCR' => '690',
            'SDG' => '938',
            'SEK' => '752',
            'SGD' => '702',
            'SHP' => '654',
            'SLL' => '694',
            'SOS' => '706',
            'SRD' => '968',
            'SSP' => '728',
            'STN' => '930',
            'SVC' => '222',
            'SYP' => '760',
            'SZL' => '748',
            'THB' => '764',
            'TJS' => '972',
            'TMT' => '934',
            'TND' => '788',
            'TOP' => '776',
            'TRY' => '949',
            'TTD' => '780',
            'TWD' => '901',
            'TZS' => '834',
            'UAH' => '980',
            'UGX' => '800',
            'USD' => '840',
            'USN' => '997',
            'UYI' => '940',
            'UYU' => '858',
            'UYW' => '927',
            'UZS' => '860',
            'VES' => '928',
            'VND' => '704',
            'VUV' => '548',
            'WST' => '882',
            'XAF' => '950',
            'XAG' => '961',
            'XAU' => '959',
            'XBA' => '955',
            'XBB' => '956',
            'XBC' => '957',
            'XBD' => '958',
            'XCD' => '951',
            'XDR' => '960',
            'XOF' => '952',
            'XPD' => '964',
            'XPF' => '953',
            'XPT' => '962',
            'XSU' => '994',
            'XTS' => '963',
            'XUA' => '965',
            'XXX' => '999',
            'YER' => '886',
            'ZAR' => '710',
            'ZMW' => '967',
            'ZWL' => '932',
        );
        if (array_key_exists($currencyCode, $isoCodes)) {
            $result = $isoCodes[$currencyCode];
        }
        return $result;
    }

    /**
     * Gets the numeric country ISO 3166-1 code
     *
     * @param  string $countryCode Country 3166-1 code.
     * @return string
     */
    protected function getCountryIsoNumericCode($countryCode)
    {
        $result   = '';
        $isoCodes = array(
            'AL' => '8',
            'DZ' => '12',
            'AS' => '16',
            'AD' => '20',
            'AO' => '24',
            'AI' => '660',
            'AG' => '28',
            'AR' => '32',
            'AM' => '51',
            'AW' => '533',
            'AU' => '36',
            'AT' => '40',
            'AZ' => '31',
            'BS' => '44',
            'BH' => '48',
            'BD' => '50',
            'BB' => '52',
            'BY' => '112',
            'BE' => '56',
            'BZ' => '84',
            'BJ' => '204',
            'BM' => '60',
            'BT' => '64',
            'BO' => '68',
            'BA' => '70',
            'BW' => '72',
            'BR' => '76',
            'BN' => '96',
            'BG' => '100',
            'BF' => '854',
            'BI' => '108',
            'KH' => '116',
            'CM' => '120',
            'CA' => '124',
            'CV' => '132',
            'KY' => '136',
            'CF' => '140',
            'TD' => '148',
            'CL' => '152',
            'CN' => '156',
            'CO' => '170',
            'KM' => '174',
            'CG' => '178',
            'CD' => '180',
            'CK' => '184',
            'CR' => '188',
            'CI' => '384',
            'HR' => '191',
            'CU' => '192',
            'CY' => '196',
            'CZ' => '203',
            'DK' => '208',
            'DJ' => '262',
            'DM' => '212',
            'DO' => '214',
            'EC' => '218',
            'EG' => '818',
            'SV' => '222',
            'GQ' => '226',
            'ER' => '232',
            'EE' => '233',
            'ET' => '231',
            'FK' => '238',
            'FO' => '234',
            'FJ' => '242',
            'FI' => '246',
            'FR' => '250',
            'GF' => '254',
            'PF' => '258',
            'GA' => '266',
            'GM' => '270',
            'GE' => '268',
            'DE' => '276',
            'GH' => '288',
            'GI' => '292',
            'GR' => '300',
            'GL' => '304',
            'GD' => '308',
            'GP' => '312',
            'GU' => '316',
            'GT' => '320',
            'GN' => '324',
            'GW' => '624',
            'GY' => '328',
            'HT' => '332',
            'VA' => '336',
            'HN' => '340',
            'HK' => '344',
            'HU' => '348',
            'IS' => '352',
            'IN' => '356',
            'ID' => '360',
            'IR' => '364',
            'IQ' => '368',
            'IE' => '372',
            'IL' => '376',
            'IT' => '380',
            'JM' => '388',
            'JP' => '392',
            'JO' => '400',
            'KZ' => '398',
            'KE' => '404',
            'KI' => '296',
            'KP' => '408',
            'KR' => '410',
            'KW' => '414',
            'KG' => '417',
            'LA' => '418',
            'LV' => '428',
            'LB' => '422',
            'LS' => '426',
            'LR' => '430',
            'LY' => '434',
            'LI' => '438',
            'LT' => '440',
            'LU' => '442',
            'MO' => '446',
            'MK' => '807',
            'MG' => '450',
            'MW' => '454',
            'MY' => '458',
            'MV' => '462',
            'ML' => '466',
            'MT' => '470',
            'MH' => '584',
            'MQ' => '474',
            'MR' => '478',
            'MU' => '480',
            'MX' => '484',
            'FM' => '583',
            'MD' => '498',
            'MC' => '492',
            'MN' => '496',
            'MS' => '500',
            'MA' => '504',
            'MZ' => '508',
            'MM' => '104',
            'NA' => '516',
            'NR' => '520',
            'NP' => '524',
            'NL' => '528',
            'AN' => '530',
            'NC' => '540',
            'NZ' => '554',
            'NI' => '558',
            'NE' => '562',
            'NG' => '566',
            'NU' => '570',
            'NF' => '574',
            'MP' => '580',
            'NO' => '578',
            'OM' => '512',
            'PK' => '586',
            'PW' => '585',
            'PA' => '591',
            'PG' => '598',
            'PY' => '600',
            'PE' => '604',
            'PH' => '608',
            'PN' => '612',
            'PL' => '616',
            'PT' => '620',
            'PR' => '630',
            'QA' => '634',
            'RE' => '638',
            'RO' => '642',
            'RU' => '643',
            'RW' => '646',
            'SH' => '654',
            'KN' => '659',
            'LC' => '662',
            'PM' => '666',
            'VC' => '670',
            'WS' => '882',
            'SM' => '674',
            'ST' => '678',
            'SA' => '682',
            'SN' => '686',
            'SC' => '690',
            'SL' => '694',
            'SG' => '702',
            'SK' => '703',
            'SI' => '705',
            'SB' => '90',
            'SO' => '706',
            'ZA' => '710',
            'ES' => '724',
            'LK' => '144',
            'SD' => '736',
            'SR' => '740',
            'SJ' => '744',
            'SZ' => '748',
            'SE' => '752',
            'CH' => '756',
            'SY' => '760',
            'TW' => '158',
            'TJ' => '762',
            'TZ' => '834',
            'TH' => '764',
            'TG' => '768',
            'TK' => '772',
            'TO' => '776',
            'TT' => '780',
            'TN' => '788',
            'TR' => '792',
            'TM' => '795',
            'TC' => '796',
            'TV' => '798',
            'UG' => '800',
            'UA' => '804',
            'AE' => '784',
            'GB' => '826',
            'US' => '840',
            'UY' => '858',
            'UZ' => '860',
            'VU' => '548',
            'VE' => '862',
            'VN' => '704',
            'VG' => '92',
            'VI' => '850',
            'WF' => '876',
            'EH' => '732',
            'YE' => '887',
            'ZM' => '894',
            'ZW' => '716',
        );
        if (array_key_exists($countryCode, $isoCodes)) {
            $result = $isoCodes[$countryCode];
        }
        return $result;
    }

    /**
     * Checks whether the PrestaShop Version is 1.6
     *
     * @return bool
     */
    protected function isPrestaShopVersion16()
    {
        return $this->isPrestaShopVersionInRange('1.6.0.0', '1.6.9.9');
    }

    /**
     * Checks whether the PrestaShop Version is 1.7
     *
     * @return bool
     */
    protected function isPrestaShopVersion17()
    {
        return $this->isPrestaShopVersionInRange('1.7.0.0', '1.7.9.9');
    }

    /**
     * Checks whether the PrestaShop Version is in the interval [$minVersion, $maxVersion]
     *
     * @param string $minVersion
     * @param string $maxVersion
     *
     * @return bool
     */
    protected function isPrestaShopVersionInRange($minVersion, $maxVersion)
    {
        return version_compare(_PS_VERSION_, $minVersion, '>=') &&
            version_compare(_PS_VERSION_, $maxVersion, '<=');
    }

    /**
     * Gets the URL where the customer will be redirected when returning from the Hosted Payment Form
     *
     * Used to set the CallbackURL parameter for the Hosted Payment Form request
     *
     * @return string|false A string containing a URL or false if the PrestaShop version is not supported
     */
    protected function getCustomerRedirectUrl()
    {
        switch (true) {
            case $this->isPrestaShopVersion16():
                $result = Tools::getShopDomain(true) . __PS_BASE_URI__ . 'modules/paymentsense/success.php';
                break;
            case $this->isPrestaShopVersion17():
                $result = $this->context->link->getModuleLink($this->name, 'customerredirect');
                break;
            default:
                // Unsupported PrestaShop version
                $result = false;
        }
        return $result;
    }

    /**
     * Gets the Notification URL handling the response from the Hosted Payment Form
     *
     * Used to set the ServerResultURL parameter for the Hosted Payment Form request
     *
     * @return string|false A string containing a URL or false if the PrestaShop version is not supported
     */
    protected function getNotificationUrl()
    {
        switch (true) {
            case $this->isPrestaShopVersion16():
                $result = Tools::getShopDomain(true) . __PS_BASE_URI__ . 'modules/paymentsense/callback.php';
                break;
            case $this->isPrestaShopVersion17():
                $result = $this->context->link->getModuleLink($this->name, 'notification');
                break;
            default:
                // Unsupported PrestaShop version
                $result = false;
        }
        return $result;
    }

    /**
     * Calculates the hash digest.
     * Supported hash methods: MD5, SHA1, HMACMD5, HMACSHA1
     *
     * @param string $data Data to be hashed.
     * @param string $hashMethod Hash method.
     * @param string $key Secret key to use for generating the hash.
     * @return string
     */
    protected function calculateHashDigest($data, $hashMethod, $key)
    {
        $result     = '';
        $includeKey = in_array($hashMethod, ['MD5', 'SHA1'], true);
        if ($includeKey) {
            $data = 'PreSharedKey=' . $key . '&' . $data;
        }
        switch ($hashMethod) {
            case 'MD5':
                $result = md5($data);
                break;
            case 'SHA1':
                $result = sha1($data);
                break;
            case 'HMACMD5':
                $result = hash_hmac('md5', $data, $key);
                break;
            case 'HMACSHA1':
                $result = hash_hmac('sha1', $data, $key);
                break;
        }
        return $result;
    }

    /**
     * Builds a string containing the expected fields from the POST request received from the payment gateway
     *
     * @param string $requestType Type of the request (notification or customer redirect)
     *
     * @return bool
     */
    protected function buildPostString($requestType)
    {
        $result = false;
        $fields = array(
            // Variables for hash digest calculation for notification requests (excluding configuration variables)
            self::REQ_NOTIFICATION      => array(
                'StatusCode',
                'Message',
                'PreviousStatusCode',
                'PreviousMessage',
                'CrossReference',
                'AddressNumericCheckResult',
                'PostCodeCheckResult',
                'CV2CheckResult',
                'ThreeDSecureAuthenticationCheckResult',
                'CardType',
                'CardClass',
                'CardIssuer',
                'CardIssuerCountryCode',
                'Amount',
                'CurrencyCode',
                'OrderID',
                'TransactionType',
                'TransactionDateTime',
                'OrderDescription',
                'CustomerName',
                'Address1',
                'Address2',
                'Address3',
                'Address4',
                'City',
                'State',
                'PostCode',
                'CountryCode',
                'EmailAddress',
                'PhoneNumber',
            ),
            // Variables for hash digest calculation for customer redirects (excluding configuration variables)
            self::REQ_CUSTOMER_REDIRECT => array(
                'CrossReference',
                'OrderID',
            ),
        );
        if (array_key_exists($requestType, $fields)) {
            $result = 'MerchantID=' . Configuration::get('PAYMENTSENSE_GATEWAYID') .
                '&Password=' . Configuration::get('PAYMENTSENSE_GATEWAYPASS');
            foreach ($fields[$requestType] as $field) {
                $result .= '&' . $field . '=' . Tools::getValue($field);
            }
        }
        return $result;
    }
}
