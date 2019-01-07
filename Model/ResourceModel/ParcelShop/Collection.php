<?php
namespace BigFish\Shipping\Model\ResourceModel\ParcelShop;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'dpd_parcelshop_collection';
    protected $_eventObject = 'parcelshop_collection';

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'BigFish\Shipping\Model\ParcelShop',
            'BigFish\Shipping\Model\ResourceModel\ParcelShop'
        );
    }
}