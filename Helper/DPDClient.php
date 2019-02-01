<?php
/**
 * This file is part of the Magento 2 Shipping module of DPD Nederland B.V.
 *
 * Copyright (C) 2018  DPD Nederland B.V.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace BigFish\Shipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use GuzzleHttp;

class DPDClient extends AbstractHelper
{
    const DPD_CONFIG_SERVICE_MODE = 'dpdshipping/account_settings/mode_type';

    const DPD_LOGIN_SERVICE_URL = 'LoginService.svc?singleWsdl';
    const DPD_SHIPMENT_SERVICE_URL = 'ShipmentService.svc?singleWsdl';
    const DPD_PARCELSHOP_URL = 'ParcelShopFinderService.svc?singleWsdl';
    const DPD_PARCEL_STATUS_URL = 'parcel_status.php?';
    const DPD_PARCEL_IMPORT_URL = 'parcel_import.php?';

    const DPD_STAGING_SERVICE_URL = 'https://public-dis-stage.dpd.nl/Services/';
    const DPD_LIVE_SERVICE_URL = 'https://public-dis.dpd.nl/Services/';
    const DPD_WEBLABEL = 'https://weblabel.dpd.hu/dpd_wow/';

    /**
     * Used to get the Temp directory of Magento
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var GuzzleHttp\Client
     */
    private $client;

    /**
     * DPDClient constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param GuzzleHttp\Client $client
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        GuzzleHttp\Client $client
    ) {
        $this->directoryList = $directoryList;
        $this->checkIfExtensionIsLoaded('soap');
        $this->client = $client;
        parent::__construct($context);
    }

    /**
     * @param $delisId
     * @param $password
     * @return array
     */
    public function login($delisId, $password)
    {
        $url = $this->getUrl(self::DPD_LOGIN_SERVICE_URL);

        $soapClient = $this->getSoapClient($url);

        try {
            $result = $soapClient->getAuth(array(
                'delisId' => $delisId
            , 'password' => $password
            , 'messageLanguage' => 'nl_NL'
            ));
        } catch (\SoapFault $fs) {
            if (isset($fs->detail->authenticationFault)) {
                throw new \Exception('[DPD-Login] ' . $fs->detail->authenticationFault->errorMessage);
            } else {
                $this->_logger->debug('[DPD-Login] ' . print_r($fs->detail, true));
                throw new \Exception(__('[DPD-Login] Error occurred'));
            }
        }

        return [
            'authToken' => $result->return->authToken,
            'depot' => $result->return->depot,
        ];
    }

    /**
     * @param $shipmentData
     * @param $delisId
     * @param $accessToken
     * @return mixed
     */
    public function storeOrders($shipmentData, $delisId, $accessToken)
    {
        $url = $this->getUrl(self::DPD_SHIPMENT_SERVICE_URL);

        $soapClient = $this->getSoapClient($url);

        //The header is filled with the data you got from the authentication service
        $soapHeaderBody = array(
            'delisId' => $delisId,
            'authToken' => $accessToken,
            'messageLanguage' => 'nl_NL'
        );

        $header = new \SOAPHeader('http://dpd.com/common/service/types/Authentication/2.0', 'authentication', $soapHeaderBody, false);
        $soapClient->__setSoapHeaders($header);

        try {
            $result = $soapClient->storeOrders($shipmentData);
        } catch (\Exception $fs) {
            if (isset($fs->detail->faultCodeType)) {
                throw new \Exception('[DPD-Shipment] ' . $fs->detail->faultCodeType->messageField);
            } elseif (isset($fs->detail->authenticationFault)) {
                throw new \Exception('[DPD-Shipment] ' . $fs->detail->authenticationFault->errorMessage);
            } else {
                $this->_logger->info('[DPD-Shipment] OrderNumber: ' . $shipmentData['order']['parcels']['customerReferenceNumber1'] . " - Exception: " . $fs->getMessage());
                throw new \Exception(__('[DPD-Shipment] Unknown error occurred'));
            }
        }

        return $result;
    }

    public function findParcelShopsByGeoData($parameters, $delisId, $accessToken)
    {
        $url = $this->getUrl(self::DPD_PARCELSHOP_URL);

        $soapClient = $this->getSoapClient($url);

        //The header is filled with the data you got from the authentication service
        $soapHeaderBody = array(
            'delisId' => $delisId,
            'authToken' => $accessToken,
            'messageLanguage' => 'nl_NL'
        );

        $header = new \SOAPHeader('http://dpd.com/common/service/types/Authentication/2.0', 'authentication', $soapHeaderBody, false);
        $soapClient->__setSoapHeaders($header);
        try {
            $result = $soapClient->__soapCall('findParcelShopsByGeoData', array($parameters));
        } catch (\Exception $fs) {
            if (isset($fs->detail->faultCodeType)) {
                throw new \Exception('[DPD-Shipment] ' . $fs->detail->faultCodeType->messageField);
            } elseif (isset($fs->detail->authenticationFault)) {
                throw new \Exception('[DPD-Shipment] ' . $fs->detail->authenticationFault->errorMessage);
            } else {
                $this->_logger->debug('[DPD-Shipment] Failed getting parcelshop data ' . print_r($fs->detail, true));
                throw new \Exception(__('[DPD-Shipment] Error occurred'));
            }
        }

        return $result;
    }

    private function getDetailAsString($detail)
    {
        $result = '';
        $objectvars = get_object_vars($detail);
        foreach ($objectvars as $var) {
            if (is_object($var)) {
                $result .= ' ' . $this->getDetailAsString($var);
            } else {
                $result .= ' ' .$var;
            }
        }
        return $result;
    }

    private function getUrl($serviceUrl)
    {
        // 1 for production
        // 2 for staging
        $serviceMode = $this->scopeConfig->getValue(self::DPD_CONFIG_SERVICE_MODE);

        if ($serviceMode == 1) {
            return self::DPD_LIVE_SERVICE_URL . $serviceUrl;
        } else {
            return self::DPD_STAGING_SERVICE_URL . $serviceUrl;
        }
    }

    private function getSoapClient($url)
    {
        $tmpDirectory = $this->directoryList->getPath('tmp');

        // Manualy get the WSDL and write it to the temp storage, the soapclient can't load it itself
        // somehow the Bootstrap::initErrorHandler breaks it
        $md5Key = md5($url);
        $tempFile = $tmpDirectory . DIRECTORY_SEPARATOR . $md5Key . '.wsdl';

        if (!file_exists($tempFile)) {
            $wsdl = file_get_contents($url);
            file_put_contents($tempFile, $wsdl);
        }

        $client = new \SoapClient($tempFile);
        return $client;
    }

    /**
     * @param string $extension
     * @throws \Exception
     */
    private function checkIfExtensionIsLoaded($extension = 'soap')
    {
        if (!extension_loaded($extension)) {
            throw new \Exception(__('SOAP extension is not loaded.'), 0);
        }
    }

    public function getWebLabelParcelNumber($shipmentData, $apiUser, $apiPassword)
    {
        $responseObject = $this->sendDataToWebLabel($shipmentData, $apiUser, $apiPassword);

        if ($responseObject->status == 'err') {
            $this->_logger->error($responseObject->errlog);
            throw new \Exception('Faild get parcel number from DPD Weblabel');
        }

        return $responseObject->pl_number[0];
    }

    protected function sendDataToWebLabel($shipmentData, $apiUser, $apiPassword)
    {
        $url = self::DPD_WEBLABEL . self::DPD_PARCEL_IMPORT_URL;

        try {
            $post = $this->getRequestOptions();
            $post['form_params'] = $shipmentData;
            $post['form_params']['username'] = $apiUser;
            $post['form_params']['password'] = $apiPassword;
            $request = $this->client->post($url, $post);

            return json_decode($request->getBody());
        } catch (GuzzleHttp\Exception\ServerException $exception) {
            $this->_logger->error(print_r($post, true));
            throw new \Exception($exception->getMessage());
        }
    }

    protected function getRequestOptions()
    {
        return [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ],
        ];
    }
}
