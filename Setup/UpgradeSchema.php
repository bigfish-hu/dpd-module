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
namespace BigFish\Shipping\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    private $scopeConfig;
    private $configWriter;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        //handle all possible upgrade versions

        if (!$context->getVersion()) {
            //no previous version found, installation, InstallSchema was just executed
            //be careful, since everything below is true for installation !
        }

        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.0.2') < 0) {
            // Code to upgrade to 1.0.2

            //#10796
            $senderStoreName = $this->scopeConfig->getValue('general/store_information/name');
            $senderStreetLine1 = $this->scopeConfig->getValue('general/store_information/street_line1');
            $senderStreetLine2 = $this->scopeConfig->getValue('general/store_information/street_line2');

            $senderStreet = $senderStreetLine1 . ' ' . $senderStreetLine2;

            $senderPostCode = $this->scopeConfig->getValue('general/store_information/postcode');
            $senderCity = $this->scopeConfig->getValue('general/store_information/city');
            $senderCountryId = $this->scopeConfig->getValue('general/store_information/country_id');


            $this->configWriter->save('dpdshipping/sender_address/name1', $senderStoreName);
            $this->configWriter->save('dpdshipping/sender_address/street', $senderStreet);
            $this->configWriter->save('dpdshipping/sender_address/houseNo', '');
            $this->configWriter->save('dpdshipping/sender_address/zipCode', $senderPostCode);
            $this->configWriter->save('dpdshipping/sender_address/city', $senderCity);
            $this->configWriter->save('dpdshipping/sender_address/country', $senderCountryId);
        }

        if (version_compare($context->getVersion(), '1.0.3') < 0) {
            // Code to upgrade to 1.0.3

            $tableName = $setup->getTable('quote');

            if ($connection->tableColumnExists($tableName, 'dpd_parcelshop_id') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_parcelshop_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop ID',
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, 'dpd_company') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_company',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop company',
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, 'dpd_street') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_street',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop street',
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, 'dpd_zipcode') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_zipcode',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop zipcode',
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, 'dpd_city') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_city',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop city',
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, 'dpd_country') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_country',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop country',
                    ]
                );
            }

            if ($connection->tableColumnExists($tableName, 'dpd_extra_info') === false) {
                $setup->getConnection()->addColumn(
                    $setup->getTable('quote'),
                    'dpd_extra_info',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop extra info',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            if ($connection->tableColumnExists($setup->getTable('sales_order'), 'dpd_shop_id') === false) {
                // Code to upgrade to 2.0.4
                $setup->getConnection()->addColumn(
                    $setup->getTable('sales_order'),
                    'dpd_shop_id',
                    [
                        'type' => Table::TYPE_TEXT,
                        'nullable' => true,
                        'comment' => 'Parcelshop ID',
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.5') < 0) {
            // Code to upgrade to 1.0.5

            if (!$setup->tableExists($setup->getTable('dpd_shipping_tablerate'))) {
                /**
                 * Create table 'dpd_shipping_tablerate'
                 */
                $table = $setup->getConnection()->newTable(
                    $setup->getTable('dpd_shipping_tablerate')
                )->addColumn(
                    'pk',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Primary key'
                )->addColumn(
                    'website_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Website Id'
                )->addColumn(
                    'dest_country_id',
                    Table::TYPE_TEXT,
                    4,
                    ['nullable' => false, 'default' => '0'],
                    'Destination coutry ISO/2 or ISO/3 code'
                )->addColumn(
                    'dest_region_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'default' => '0'],
                    'Destination Region Id'
                )->addColumn(
                    'dest_zip',
                    Table::TYPE_TEXT,
                    10,
                    ['nullable' => false, 'default' => '*'],
                    'Destination Post Code (Zip)'
                )->addColumn(
                    'condition_name',
                    Table::TYPE_TEXT,
                    20,
                    ['nullable' => false],
                    'Rate Condition name'
                )->addColumn(
                    'condition_value',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'Rate condition value'
                )->addColumn(
                    'price',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'Price'
                )->addColumn(
                    'cost',
                    Table::TYPE_DECIMAL,
                    '12,4',
                    ['nullable' => false, 'default' => '0.0000'],
                    'Cost'
                )->addIndex(
                    $setup->getIdxName(
                        'dpd_shipping_tablerate',
                        ['website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )->setComment(
                    'DPD Shipping Tablerate'
                );
                $setup->getConnection()->createTable($table);
            }
        }



        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            if ($connection->tableColumnExists($setup->getTable('dpd_shipping_tablerate'), 'shipping_method') === false) {
                // Code to upgrade to 1.0.6
                $setup->getConnection()->addColumn(
                    $setup->getTable('dpd_shipping_tablerate'),
                    'shipping_method',
                    [
                        'type' => Table::TYPE_TEXT,
                        'length' => 150,
                        'nullable' => false,
                        'default' => 'dpdpredict',
                        'comment' => 'DPD shipping method name',
                    ]
                );
            }

            $oldIndexName = $setup->getIdxName(
                'dpd_shipping_tablerate',
                ['website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );

            $newIndexName = $setup->getIdxName(
                'dpd_shipping_tablerate',
                ['shipping_method', 'website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );

            $setup->getConnection()->dropIndex(
                $setup->getTable('dpd_shipping_tablerate'),
                $oldIndexName
            );

            $setup->getConnection()->addIndex(
                $setup->getTable('dpd_shipping_tablerate'),
                $newIndexName,
                ['shipping_method', 'website_id', 'dest_country_id', 'dest_region_id', 'dest_zip', 'condition_name', 'condition_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        if (version_compare($context->getVersion(), '1.0.9') < 0) {
            if (!$setup->tableExists($setup->getTable('dpd_shipment_label'))) {
                /**
                 * Create table 'dpd_shipment_label'
                 */

                $table = $setup->getConnection()->newTable(
                    $setup->getTable('dpd_shipment_label')
                )->addColumn(
                    'id_dpdcarrier_label',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
                )->addColumn(
                    'mps_id',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false]
                )->addColumn(
                    'label_numbers',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => false]
                )->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['nullable' => false]
                )->addColumn(
                    'shipment_id',
                    Table::TYPE_INTEGER,
                    10,
                    ['nullable' => false]
                )->addColumn(
                    'shipment_increment_id',
                    Table::TYPE_TEXT,
                    10,
                    ['nullable' => false]
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    '',
                    ['nullable' => false, 'default' =>  Table::TIMESTAMP_INIT]
                )->addColumn(
                    'label',
                    Table::TYPE_BLOB,
                    "16M",
                    ['nullable' => false]
                )->addColumn(
                    'is_return',
                    Table::TYPE_INTEGER,
                    1,
                    ['nullable' => false]
                )->addIndex(
                    $setup->getIdxName(
                        'dpd_shipment_label',
                        ['id_dpdcarrier_label'],
                        \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    ['id_dpdcarrier_label'],
                    ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
                )->setComment(
                    'DPD Shipping Labels'
                );
                $setup->getConnection()->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), '1.1.0') < 0) {
            $tableName = $setup->getTable('dpd_parcelshop');

            if (!$setup->tableExists('dpd_parcelshop')) {
                $table = $setup->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                        'id',
                        Table::TYPE_INTEGER,
                        null,
                        [
                            'identity' => true,
                            'unsigned' => true,
                            'nullable' => false,
                            'primary' => true,
                            'auto_increment' => true,
                        ],
                        'ID'
                    )
                    ->addColumn(
                        'parcelshop_id',
                        Table::TYPE_TEXT,
                        24,
                        [],
                        'Remote dpd ID'
                    )
                    ->addColumn(
                        'company',
                        Table::TYPE_TEXT,
                        255,
                        array(
                            'nullable'  => true,
                        ),
                        'Company Name'
                    )
                    ->addColumn(
                        'city',
                        Table::TYPE_TEXT,
                        100,
                        array(
                            'nullable'  => true,
                        ),
                        'City Name'
                    )
                    ->addColumn(
                        'pcode',
                        Table::TYPE_TEXT,
                        10,
                        array(
                            'nullable'  => true,
                        ),
                        'Post code'
                    )
                    ->addColumn(
                        'street',
                        Table::TYPE_TEXT,
                        255,
                        array(
                            'nullable'  => true,
                        ),
                        'Street name'
                    )
                    ->addColumn(
                        'email',
                        Table::TYPE_TEXT,
                        100,
                        array(
                            'nullable'  => true,
                        ),
                        'Email address'
                    )

                    ->addColumn(
                        'phone',
                        Table::TYPE_TEXT,
                        24,
                        array(
                            'nullable'  => true,
                        ),
                        'Phone number'
                    )
                    ->addColumn(
                        'latitude',
                        Table::TYPE_TEXT,
                        24,
                        array(
                            'nullable'  => true,
                        ),
                        'GPS latitude'
                    )
                    ->addColumn(
                        'longitude',
                        Table::TYPE_TEXT,
                        24,
                        array(
                            'nullable'  => true,
                        ),
                        'GPS longitude'
                    )
                    ->addColumn(
                        'open_hours',
                        Table::TYPE_TEXT,
                        500,
                        array(
                            'nullable'  => true,
                        ),
                        'Phone number'
                    )
                    ->addColumn(
                        'hash',
                        Table::TYPE_TEXT,
                        32,
                        [],
                        'MD5 hash of sync data'
                    );
                $setup->getConnection()->createTable($table);

                $indexName = $setup->getIdxName(
                    'dpd_parcelshop',
                    ['parcelshop_id', 'latitude', 'longitude'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );

                $setup->getConnection()->addIndex(
                    $setup->getTable('dpd_parcelshop'),
                    $indexName,
                    ['parcelshop_id', 'latitude', 'longitude'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                );
            }
        }

        $setup->endSetup();
    }
}
