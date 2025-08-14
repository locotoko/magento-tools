<?php

namespace Oporteo\Dev\Plugin;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\State;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogCustomerDelete
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var AdminSession
     */
    private $adminSession;

    /**
     * @param State $appState
     * @param AdminSession $adminSession
     */
    public function __construct(
        State $appState,
        AdminSession $adminSession
    ) {
        $this->appState     = $appState;
        $this->adminSession = $adminSession;

        // Create a custom logger that writes to var/log/customer_delete.log
        $this->logger = new Logger('customer_delete');
        $this->logger->pushHandler(
            new StreamHandler(BP . '/var/log/customer_delete.log', Logger::DEBUG)
        );
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    public function beforeDelete(CustomerRepositoryInterface $subject, $customer)
    {
        $this->logAttempt($customer->getId());
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param int $customerId
     */
    public function beforeDeleteById(CustomerRepositoryInterface $subject, $customerId)
    {
        $this->logAttempt($customerId);
    }

    /**
     * @param int $customerId
     */
    private function logAttempt($customerId)
    {
        // Detect execution area (adminhtml, frontend, webapi_rest, etc.)
        try {
            $area = $this->appState->getAreaCode();
        } catch (\Exception $e) {
            $area = 'unknown';
        }

        try {
            // Check if triggered by admin user
            $adminUser = null;
            if ($area === 'adminhtml' && $this->adminSession->getUser()) {
                $adminUser = $this->adminSession->getUser()->getUsername();
            }

            // Get stack trace
            $stackTrace = (new \Exception())->getTraceAsString();

            // Build log entry
            $logData = array(
                'message'     => 'Customer delete attempt',
                'customer_id' => $customerId,
                'area'        => $area,
                'admin_user'  => $adminUser,
                'stack_trace' => $stackTrace
            );

            // Log to var/log/customer_delete.log
            $this->logger->info(json_encode($logData));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
