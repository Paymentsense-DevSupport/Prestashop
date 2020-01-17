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

require_once __DIR__ . '/report.php';

/**
 * Module Information Front-End Controller
 *
 * Handles the request for plugin information
 */
class PaymentsenseInfoModuleFrontController extends PaymentsenseReportAbstractController
{
    /** @var Paymentsense */
    public $module;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $this->processInfoRequest();
    }

    /**
     * Processes the request for plugin information
     */
    protected function processInfoRequest()
    {
        $extendedInfoRequest = 'true' === Tools::getValue('extended_info');
        $info = array(
            'Module Name'              => $this->module->getModuleInternalName(),
            'Module Installed Version' => $this->module->getModuleInstalledVersion(),
        );
        if ((true == $extendedInfoRequest)) {
            $extended_info   = array_merge(
                array(
                    'Module Latest Version' => $this->getModuleLatestVersion(),
                    'PrestaShop Version'    => $this->getPsVersion(),
                    'PHP Version'           => $this->getPhpVersion(),
                )
            );
            $info = array_merge($info, $extended_info);
        }
        $this->outputInfo($info);
    }

    /**
     * Gets module latest version
     *
     * @return string
     */
    protected function getModuleLatestVersion()
    {
        $result = 'N/A';
        $headers = array(
            'User-Agent: ' . $this->module->getModuleInternalName() . ' v.' . $this->module->getModuleInstalledVersion(),
            'Content-Type: text/plain; charset=utf-8',
            'Accept: text/plain, */*',
            'Accept-Encoding: identity',
            'Connection: close'
        );
        $data = array(
            'url'     => 'https://api.github.com/repos/'.
                'Paymentsense-DevSupport/Prestashop/releases/latest',
            'headers' => $headers
        );
        if ($this->module->performCurl($data, $response) === 0) {
            $json_object = @json_decode($response);
            if (is_object($json_object) && property_exists($json_object, 'tag_name')) {
                $result = $json_object->tag_name;
            }
        }
        return $result;
    }

    /**
     * Gets PrestaShop version
     *
     * @return string
     */
    protected function getPsVersion()
    {
        return _PS_VERSION_;
    }

    /**
     * Gets PHP version
     *
     * @return string
     */
    protected function getPhpVersion()
    {
        return phpversion();
    }
}
