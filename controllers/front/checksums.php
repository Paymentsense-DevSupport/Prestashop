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
 * File Checksums Front-End Controller
 *
 * Handles the request for file checksums
 */
class PaymentsenseChecksumsModuleFrontController extends PaymentsenseReportAbstractController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $this->processChecksumsRequest();
    }

    /**
     * Processes the request for file checksums
     */
    protected function processChecksumsRequest()
    {
        $info = array(
            'Checksums' => $this->getFileChecksums(),
        );
        $this->outputInfo($info);
    }

    /**
     * Gets file checksums
     *
     * @return array
     */
    protected function getFileChecksums()
    {
        $result = array();
        $root_path = realpath(__DIR__ . '/../../../..');
        $file_list = Tools::getValue('data');
        if (is_array($file_list)) {
            foreach ($file_list as $key => $file) {
                $filename = $root_path . '/' . $file;
                $result[$key] = is_file($filename)
                    ? sha1_file($filename)
                    : null;
            }
        }
        return $result;
    }
}
