<?php
namespace BigFish\Shipping\Cron;

use BigFish\Shipping\Helper\Services\DPDPickupService;
use \Psr\Log\LoggerInterface;

class ParcelShopSync {
    protected $logger;
    /**
     * @var DPDPickupService
     */
    private $DPDPickupService;

    public function __construct(
        LoggerInterface $logger,
        DPDPickupService $DPDPickupService
    ) {
        $this->logger = $logger;
        $this->DPDPickupService = $DPDPickupService;
    }

    public function execute() {
        try {

            $this->DPDPickupService->syncParcelShops();

        } catch (\Exception $exception) {
            $this->logger->warning(sprintf('Error while run syncParcelShops. %s', $exception->getMessage()));
        }
    }

}
