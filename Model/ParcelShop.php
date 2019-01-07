<?php

namespace BigFish\Shipping\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class ParcelShop extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'dpd_parcelshop';

    protected $_cacheTag = 'dpd_parcelshop';

    protected $_eventPrefix = 'dpd_parcelshop';

    const ID = 'id';
    const PARCELSHOP_ID = 'parcelshop_id';
    const COMPANY = 'company';
    const CITY = 'city';
    const ZIP_CODE = 'pcode';
    const STREET = 'street';
    const EMAIL = 'email';
    const PHONE = 'phone';
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const OPEN_HOURS = 'open_hours';
    const HASH = 'hash';

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('BigFish\Shipping\Model\ResourceModel\ParcelShop');
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getParcelShopId()
    {
        return $this->getData(self::PARCELSHOP_ID);
    }

    public function getSyncHash()
    {
        return $this->getData(self::HASH);
    }

    public function getCompany()
    {
        return $this->getData(self::COMPANY);
    }

    public function getCity()
    {
        return $this->getData(self::CITY);
    }

    public function getZipCode()
    {
        return $this->getData(self::ZIP_CODE);
    }

    public function getStreet()
    {
        return $this->getData(self::STREET);
    }

    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    public function getPhone()
    {
        return $this->getData(self::PHONE);
    }

    public function getLatitude()
    {
        return $this->getData(self::LATITUDE);
    }

    public function getLongitude()
    {
        return $this->getData(self::LONGITUDE);
    }

    public function getOpenHours()
    {
        return json_decode($this->getData(self::OPEN_HOURS));
    }

    public function getOpenHoursJson()
    {
        return $this->getData(self::OPEN_HOURS);
    }

}
