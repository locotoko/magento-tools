<?php

namespace Oporteo\Dev\Plugin;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\State;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class LogCustomerDelete
{
    /**
     * @var Logger
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

    public function __construct(
        State $appState,
        AdminSession $adminSession
    ) {
        $this->appState            = $appState;
        $this->adminSession        = $adminSession;

        $writer = new Stream(BP . '/var/log/customer_delete.log');
        $this->logger = new Logger();
        $this->logger->addWriter($writer);

    }

    public function beforeDelete(CustomerRepositoryInterface $subject, $customer)
    {
        $this->logAttempt($customer);
    }

    public function beforeDeleteById(CustomerRepositoryInterface $subject, $customerId)
    {
        try {
            $customer = $subject->getById($customerId);
        } catch (\Exception $e) {
            $customer = null;
        }

        $this->logAttempt($customer);
    }

    private function logAttempt($customer = null)
    {
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

            $data = [];

            if ($customer) {
                $data = [
                    'email' => $customer->getEmail(),
                    'firstname' => $customer->getFirstname(),
                    'lastname' => $customer->getLastname(),
                    'created_at' => $customer->getCreatedAt(),
                    'updated_at' => $customer->getUpdatedAt(),
                ];
            }

            foreach ($customer->getCustomAttributes() as $attr) {
                $data[$attr->getAttributeCode()] = $attr->getValue();
            }

            // Build log entry
            $logData = array(
                'message'      => 'Customer delete attempt',
                'area'         => $area,
                'admin_user'   => $adminUser,
                'customerData'     => $data,
                'stack_trace'  => $stackTrace
            );

            // Log to var/log/customer_delete.log
            $this->logger->info(json_encode($logData));
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
