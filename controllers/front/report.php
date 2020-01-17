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
 * Paymentsense Report Abstract Controller
 */
abstract class PaymentsenseReportAbstractController extends ModuleFrontController
{
    const TYPE_APPLICATION_JSON = 'application/json';
    const TYPE_TEXT_PLAIN       = 'text/plain';

    /**
     * Supported content types of the output of the module information
     *
     * @var array
     */
    protected $contentTypes = [
        'json' => self::TYPE_APPLICATION_JSON,
        'text' => self::TYPE_TEXT_PLAIN
    ];

    /**
     * Outputs module information
     *
     * @param array $info Module information
     */
    protected function outputInfo($info)
    {
        $outputFormat = Tools::getValue('output');
        $contentType  = is_string($outputFormat) && array_key_exists($outputFormat, $this->contentTypes)
            ? $this->contentTypes[$outputFormat]
            : self::TYPE_TEXT_PLAIN;
        switch ($contentType) {
            case self::TYPE_APPLICATION_JSON:
                $body = json_encode($info);
                break;
            case self::TYPE_TEXT_PLAIN:
            default:
                $body = $this->convertArrayToString($info);
                break;
        }
        header('Cache-Control: max-age=0, must-revalidate, no-cache, no-store');
        header('Pragma: no-cache');
        header('Content-Type:', $contentType);
        echo $body;
        exit;
    }

    /**
     * Converts an array to string
     *
     * @param array  $arr An associative array.
     * @param string $ident Identation.
     * @return string
     */
    protected function convertArrayToString($arr, $ident = '')
    {
        $result       = '';
        $identPattern = '  ';
        foreach ($arr as $key => $value) {
            if ('' !== $result) {
                $result .= PHP_EOL;
            }

            if (is_array($value)) {
                $value = PHP_EOL . $this->convertArrayToString($value, $ident . $identPattern);
            }

            $result .= $ident . $key . ': ' . $value;
        }
        return $result;
    }
}
