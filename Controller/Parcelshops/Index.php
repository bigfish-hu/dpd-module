<?php

namespace BigFish\Shipping\Controller\Parcelshops;

use BigFish\Shipping\Model\ParcelShop;
use Magento\Sales\Model\Order;
use BigFish\Shipping\Helper\Data;
use BigFish\Shipping\Helper\Services\DPDPickupService;
use Magento\Framework\View\Asset\Repository;

class Index extends \Magento\Framework\App\Action\Action
{
    private $data;

    private $DPDPickupService;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    private $assetRepo;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Data $data,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        DPDPickupService $DPDPickupService,
        Repository $assetRepo
    ) {
        parent::__construct($context);

        $this->data = $data;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->DPDPickupService = $DPDPickupService;
        $this->assetRepo = $assetRepo;
    }


    public function getGoogleMapsCenter($postcode, $countryId)
    {
        try {
            $apiKey = $this->data->getGoogleMapsApiKey();

            $addressToInsert = 'country:' . $countryId . '|postal_code:' . $postcode;
            $url = sprintf(
                'https://maps.google.com/maps/api/geocode/json?components=%s&sensor=false&key=%s',
                $addressToInsert,
                $apiKey
            );
            $source = file_get_contents($url);
            $obj = json_decode($source);

            $LATITUDE = $obj->results[0]->geometry->location->lat;
            $LONGITUDE = $obj->results[0]->geometry->location->lng;
        }
        catch(\Exception $ex) {
            return null;
        }

        return [$LATITUDE, $LONGITUDE];
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        //$this->_view->loadLayout();
        //$this->_view->renderLayout();

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        $resultData = array();

        $post = $this->getRequest()->getPostValue();

        if (!isset($post['postcode']) || !isset($post['countryId'])) {
            $resultData['success'] = false;
            $resultData['error_message'] = __('No address found');
            return $result->setData($resultData);
        }

        $coordinates = $this->getGoogleMapsCenter($post['postcode'], $post['countryId']);
        if ($coordinates != null) {
            $resultData['center_lat'] = $coordinates[0];
            $resultData['center_long'] = $coordinates[1];
        }

        $parcelShops = $this->DPDPickupService->getParcelShops();

        $params = array('_secure' => $this->getRequest()->isSecure());

        $resultData['success'] = true;

        $resultData["gmapsIcon"] = $this->assetRepo->getUrlWithParams('BigFish_Shipping::images/icon_parcelshop.png', $params);
        $resultData["gmapsIconShadow"] = $this->assetRepo->getUrlWithParams('BigFish_Shipping::images/icon_parcelshop_shadow.png', $params);

        /** @var ParcelShop $shop */
        foreach($parcelShops as $shop) {
            $parcelShop = [];
            $parcelShop['parcelShopId'] = $shop->getParcelShopId();
            $parcelShop['company'] = trim($shop->getCompany());
            $parcelShop['houseno'] = $shop->getStreet();
            $parcelShop['zipcode'] = $shop->getZipCode();
            $parcelShop['city'] = $shop->getCity();
            $parcelShop['country'] = 'HU';
            $parcelShop['gmapsCenterlat'] = $shop->getLatitude();
            $parcelShop['gmapsCenterlng'] = $shop->getLongitude();
            $parcelShop['special'] = false;

            $parcelShop['extra_info'] = json_encode(array_filter(array(
                'Opening hours' => $shop->getOpenHoursJson(),
                'Telephone' => $shop->getPhone(),
                'Website' => '',
            )));

            $parcelShop['gmapsMarkerContent'] = $this->_getMarkerHtml($shop);

            $resultData['parcelshops'][$shop->getParcelShopId()] = $parcelShop;
        }

        return $result->setData($resultData);
    }

    /**
     * Gets html for the marker info bubbles.
     *
     * @param $shop
     * @return string
     */
    protected function _getMarkerHtml($shop)
    {
        $image = $this->assetRepo->getUrlWithParams('BigFish_Shipping::images/dpd_parcelshop_logo.png', array('_secure' => $this->getRequest()->isSecure()));
        $routeIcon = $this->assetRepo->getUrlWithParams('BigFish_Shipping::images/icon_route.png', array('_secure' => $this->getRequest()->isSecure()));

        $html = '<div class="content">
            <table style="min-width:250px" cellpadding="3" cellspacing="3" border="0">
                <tbody>
                    <tr>
                        <td rowspan="3" width="90" style="padding-top:3px; padding-bottom:3px;"><img class="parcelshoplogo bubble" style="width:80px; height:62px;" src="' . $image . '" alt="logo"/></td>
                        <td><strong>' .$shop->getCompany() . '</strong></td>
                    </tr>
                    <tr>
                        <td style="padding-top:3px; padding-bottom:3px;">' . $shop->getStreet() . '</td>
                    </tr>
                    <tr>
                        <td style="padding-top:3px; padding-bottom:3px;">' . $shop->getZipCode() . ' ' . $shop->getCity() . '</td>
                    </tr>
                </tbody>
            </table>
            <div class="dpdclear"></div>
            ';


        $html .= '<div class="dotted-line">
        <table>
        <tbody>';
        foreach ($shop->getOpenHours() as $openinghours) {
            $html .= '<tr><td style="padding-right:10px; padding-top:3px; padding-bottom:3px;"><strong>' . $openinghours->weekday . '</strong></td><td style="padding-right:10px; padding-top:3px; padding-bottom:3px;">' . $openinghours->openMorning . ' - ' . $openinghours->closeMorning . '
        </td><td style="padding-right:10px; padding-top:3px; padding-bottom:3px;">' . $openinghours->openAfternoon . ' - ' . $openinghours->closeAfternoon . '</td></tr>';
        }
        $html .= '</tbody>
        </table>
        </div><div class="dpdclear"></div>';



        $html .= '<div class="dotted-line">
                    <table>
                        <tbody>
                            <tr style="cursor: pointer;">
                                <td id="' . $shop->getParcelShopId() . '" class="parcelshoplink" style="width: 25px;"><img src="'.$routeIcon .'" alt="route" width="16" height="16" ></td>
                                <td id="' . $shop->getParcelShopId() . '" class="parcelshoplink"><strong>' . __('Ship to this Pickup point.') . '</strong></td>
                            </tr>
                        </tbody>
                    </table>
                  </div></div><div class="dpdclear"></div>';
        return $html;
    }
}
