<?php

namespace BigFish\Shipping\Model\ResourceModel;

use BigFish\Shipping\Model\ParcelShop as ParcelShopModel;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ParcelShop extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('dpd_parcelshop', ParcelShopModel::ID);
    }
}