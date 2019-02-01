<?php

namespace BigFish\Shipping\Helper\Services;

use \Magento\Sales\Model\Order;
use BigFish\Shipping\Helper\Services\AuthenticationService;
use BigFish\Shipping\Helper\DPDClient;
use \Psr\Log\LoggerInterface;
use Magento\OfflinePayments\Model\Cashondelivery;

class DPDWeblabelService
{
    const DPD_CLASSIC_SHIPPING_METHOD = 'dpdclassic_dpdclassic';
    const DPD_PICKUP_PARCEL_SHIPPING_METHOD = 'dpdpickup_dpdpickup';

    const DPD_PICKUP_PARCEL_TYPE = 'PS';
    const DPD_PICKUP_PARCEL_COD_TYPE = 'PSCOD';

    const DPD_CLASSIC_PARCEL_TYPE = 'D';
    const DPD_CLASSIC_PARCEL_COD_TYPE = 'COD';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \BigFish\Shipping\Helper\Services\AuthenticationService
     */
    private $authenticationService;

    /**
     * @var DPDClient
     */
    private $DPDClient;

    /**
     * @var Order
     */
    private $order;

    /**
     * DPDWeblabelService constructor.
     * @param LoggerInterface $logger
     * @param AuthenticationService $authenticationService
     * @param DPDClient $DPDClient
     */
    public function __construct(
        LoggerInterface $logger,
        AuthenticationService $authenticationService,
        DPDClient $DPDClient
    ) {

        $this->logger = $logger;
        $this->authenticationService = $authenticationService;
        $this->DPDClient = $DPDClient;
    }

    public function createParcelNumberToOrder(Order $order)
    {
        $this->order = $order;
        $apiUser = $this->authenticationService->getApiUserName();
        $apiPassword = $this->authenticationService->getApiPassword();

        if (!$this->isDpdShipping()) {
            return false;
        }

        $dataArray = [];

        switch ($this->order->getShippingMethod()) {
            case self::DPD_CLASSIC_SHIPPING_METHOD:
                $dataArray = $this->getClassicDataArray();
                break;

            case self::DPD_PICKUP_PARCEL_SHIPPING_METHOD:
                $dataArray = $this->getPickupDataArray();
                break;
        }

        try {
            $parcelNumber = $this->DPDClient->getWebLabelParcelNumber($dataArray, $apiUser, $apiPassword);

            $this->order->addStatusToHistory($this->order->getStatus(), 'DPD parcel number:' . $parcelNumber);
            $this->order->setData('dpd_parcel_number', $parcelNumber);
            $this->order->save();

            return true;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return false;
    }

    protected function getClassicDataArray()
    {
        $shipping = $this->order->getShippingAddress();

        $classicData = [
            'name1' => $shipping->getName(),
            'name2' => $shipping->getCompany(),
            'street' => implode('', $shipping->getStreet()),
            'city' => $shipping->getCity(),
            'country' => 'HU',
            'pcode' => $shipping->getPostcode(),
            'email' => $this->order->getCustomerEmail(),
            'phone' => $shipping->getTelephone(),
            //'weight' => round($order->getWeight()),
            'num_of_parcel' => '1',
            'parcel_type' => $this->getClassicParcelType(),
        ];

        if ($this->isCashOnDeliveryPaymentMethod()) {
            $classicData['parcel_cod_type'] = 'firstonly';
            $classicData['cod_amount'] = round($this->order->getTotalDue());
        }

        return $classicData;
    }

    protected function getPickupDataArray()
    {
        $billing = $this->order->getBillingAddress();

        $billingName = $billing->getCompany();
        if (!$billingName) {
            $billingName = $billing->getFirstname() . ' ' . $billing->getLastname();
        }

        $pickupData = [
            'name1' => $billingName,
            'country' => 'HU',
            'pcode' => $billing->getPostcode(),
            'city' => $billing->getCity(),
            'street' => implode('', $billing->getStreet()),
            'email' => $this->order->getCustomerEmail(),
            'parcel_type' => $this->getPickupParcelType(),
            'parcelshop_id' => $this->order->getDataByKey('dpd_parcelshop_id'),
            'phone' => $billing->getTelephone(),
            'num_of_parcel' => '1',
            //'weight' => round($order->getWeight()),
        ];

        if ($this->isCashOnDeliveryPaymentMethod()) {
            $pickupData['parcel_cod_type'] = 'firstonly';
            $pickupData['cod_amount'] = round($this->order->getTotalDue());
        }

        return $pickupData;
    }

    protected function getClassicParcelType()
    {
        if ($this->isCashOnDeliveryPaymentMethod()) {
            return self::DPD_CLASSIC_PARCEL_COD_TYPE;
        }

        return self::DPD_CLASSIC_PARCEL_TYPE;
    }

    protected function getPickupParcelType()
    {
        if ($this->isCashOnDeliveryPaymentMethod()) {
            return self::DPD_PICKUP_PARCEL_COD_TYPE;
        }

        return self::DPD_PICKUP_PARCEL_TYPE;
    }

    protected function isDpdPickupOrder()
    {
        $shippingMethod = $this->order->getShippingMethod();

        if ($shippingMethod == 'dpdpickup_dpdpickup') {
            return true;
        }

        return false;
    }

    protected function isDpdShipping()
    {
        if ($this->order->getShippingMethod() == self::DPD_CLASSIC_SHIPPING_METHOD) {
            return true;
        }

        if ($this->order->getShippingMethod() == self::DPD_PICKUP_PARCEL_SHIPPING_METHOD) {
            return true;
        }

        return false;
    }

    protected function isDpdCashOnDeliveryMode()
    {
        if ($this->isDpdShipping() && $this->isCashOnDeliveryPaymentMethod()) {
            return true;
        }

        return false;
    }

    protected function isCashOnDeliveryPaymentMethod()
    {
        $payment = $this->order->getPayment();

        if ($payment->getMethod() == Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            return true;
        }

        return false;
    }
}
