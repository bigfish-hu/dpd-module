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

namespace BigFish\Shipping\Helper\Services;

use BigFish\Shipping\Model\ParcelShopFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use BigFish\Shipping\Helper\Data;
use Magento\Framework\App\Helper\Context;
use \Psr\Log\LoggerInterface;

class DPDPickupService extends AbstractHelper
{
    const DAYS = ['H', 'K', 'Sz', 'Cs', 'P', 'Szo', 'V'];

    const OPENING_HEADERS = ['openMorning', 'closeMorning', 'openAfternoon', 'closeAfternoon'];

    const PARCEL_SHOP_SYNC_URL = 'https://weblabel.dpd.hu/dpd_wow/parcelshop_info.php?username=%s&password=%s';

    /**
     * @var Data
     */
    private $data;

    /**
     * @var ParcelShopFactory
     */
    private $parcelShopFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        ParcelShopFactory $parcelShopFactory,
        Data $data,
        LoggerInterface $logger
    )
    {
        $this->parcelShopFactory = $parcelShopFactory;
        $this->data = $data;
        $this->logger = $logger;
        parent::__construct($context);
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
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getParcelShops()
    {
        return $this->parcelShopFactory->create()->getCollection();
    }

    public function syncParcelShops()
    {
        $dpdParcelShops = $this->getParcelShopsData();

        $searchableParcelShops = $this->getSearchableParcelShops();

        foreach ($dpdParcelShops as $parcelShop) {

            try {

                if (!array_key_exists($parcelShop->parcelshop_id, $searchableParcelShops)) {
                    $this->saveParcelShop($parcelShop);
                    $this->logger->info(sprintf('New parcelShop: %s', $parcelShop->parcelshop_id));
                    continue;
                }

                if ($searchableParcelShops[$parcelShop->parcelshop_id]['hash'] != $parcelShop->hash ) {
                    $this->updateParcelShop($searchableParcelShops[$parcelShop->parcelshop_id]['id'],$parcelShop);
                    $this->logger->info(sprintf('Update parcelShop: %s', $parcelShop->parcelshop_id));
                }

                unset($searchableParcelShops[$parcelShop->parcelshop_id]);

            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        try {
            foreach ($searchableParcelShops as $key => $parcelShop) {
                $this->deleteParcelShopById($parcelShop['id']);
                $this->logger->info(sprintf('Delete parcelShop: %s', $key));
            }
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }


    /**
     * @param integer $modelId
     * @param \stdClass $point
     */
    protected function updateParcelShop($modelId, \stdClass $point)
    {
        $model = $this->parcelShopFactory->create()->load((int)$modelId);
        $model->addData([
            "parcelshop_id" => $point->parcelshop_id,
            "company" => $point->company,
            "city" => $point->city,
            "pcode" => $point->pcode,
            "street" => $point->street,
            "email" => $point->email,
            "phone" => $point->phone,
            "latitude" => $point->latitude,
            "longitude" => $point->longitude,
            "open_hours" => json_encode((array)$point->openingHours),
            "hash" => $point->hash,
        ]);
        $model->save();
    }

    /**
     * @param \stdClass $point
     */
    protected function saveParcelShop(\stdClass $point)
    {
        $model = $this->parcelShopFactory->create();
        $model->addData([
            "parcelshop_id" => $point->parcelshop_id,
            "company" => $point->company,
            "city" => $point->city,
            "pcode" => $point->pcode,
            "street" => $point->street,
            "email" => $point->email,
            "phone" => $point->phone,
            "latitude" => $point->latitude,
            "longitude" => $point->longitude,
            "open_hours" => json_encode((array)$point->openingHours),
            "hash" => $point->hash,
        ]);
        $model->save();
    }

    /**
     * @param integer $id
     */
    protected function deleteParcelShopById($id)
    {
        $model = $this->parcelShopFactory->create()->load((int)$id);
        $model->delete();
    }

    /**
     * @return array
     */
    protected function getSearchableParcelShops()
    {
        $parcelShops = [];
        foreach ($this->parcelShopFactory->create()->getCollection() as $parcelShop) {
            $parcelShops[$parcelShop->getParcelShopId()] = [
                'id' => $parcelShop->getId(),
                'hash' => $parcelShop->getSyncHash()
            ];
        }

        return $parcelShops;
    }

    /**
     * @param \stdClass $point
     * @return string
     */
    protected function getCurrentHash(\stdClass $point)
    {
        return md5(serialize($point));
    }

    /**
     * @param string $openHours
     * @return array
     */
    protected function getOpeningHours($openHours)
    {
        $openingHours = [];
        $hours = explode('-', $openHours);

        foreach (self::DAYS as $day) {

            $openingHour = new \stdClass();
            $openingHour->weekday = $day;

            foreach (self::OPENING_HEADERS as $time) {
                $openingHour->$time = $hours[0];
                array_shift($hours);
            }
            $openingHours[] = $openingHour;
        }

        return $openingHours;
    }

    /**
     * @param \stdClass $parcelShop
     * @return \stdClass
     */
    protected function preProcessParcelShop(\stdClass $parcelShop)
    {
        $parcelShop->openingHours = $this->getOpeningHours($parcelShop->open_hours);

        $address = 'HU' . $parcelShop->pcode . ' ' . $parcelShop->city . ' ' . $parcelShop->street;
        $geo = $this->getLocationByAddress($address);

        $parcelShop->latitude = $geo['latitude'];
        $parcelShop->longitude = $geo['longitude'];
        $parcelShop->hash = $this->getCurrentHash($parcelShop);

        return $parcelShop;
    }

    /**
     * @return array
     */
    protected function getParcelShopsData()
    {
        $list = file_get_contents($this->getParcelShopSyncUrl());

        $parcelShops = [];

        foreach (json_decode($list)->parcelshops as $parcelShop) {
            $parcelShops[] = $this->preProcessParcelShop($parcelShop);
        }

        return $parcelShops;
    }

    protected function getParcelShopSyncUrl()
    {
    	$user = $this->data->getParcelApiUser();
    	$password = $this->data->getParcelApiPassword();

    	return sprintf(self::PARCEL_SHOP_SYNC_URL, $user, $password);
    }

    /**
     * @param string $address
     * @return array
     */
    protected function getLocationByAddress($address)
    {
        try {
            $apiKey = $this->data->getGoogleMapsApiKey();

            $url = 'https://maps.google.com/maps/api/geocode/json?key=' . $apiKey . '&address=' . urlencode($address) . '&sensor=false';

            $source = file_get_contents($url);
            $obj = json_decode($source);

            return [
                'latitude' => $obj->results[0]->geometry->location->lat,
                'longitude' => $obj->results[0]->geometry->location->lng,
            ];

        }
        catch (\Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}