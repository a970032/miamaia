<?php

namespace Plumrocket\SocialLoginPro\Block\Adminhtml\Customer\Edit;

class Account extends \Magento\Backend\Block\Template
{

    /**
     * Account factory
     * @var Plumrocket\SocialLoginPro\Model\AccountFactory
     */
    protected $accountFactory;

    /**
     * Object manager
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Data helper
     * @var \Plumrocket\SocialLoginPro\Helper\Data
     */
    protected $dataHelper;

    public function __construct(
        \Plumrocket\SocialLoginPro\Model\AccountFactory $accountFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Plumrocket\SocialLoginPro\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->objectManager = $objectManager;
        $this->accountFactory = $accountFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve social account
     * @return Plumrocket\SocialLoginPro\Model\ResourceModel\Account\Collection
     */
    public function getSocialAccounts()
    {
        $accounts = [];

        if ($customerId = $this->_request->getParam('id')) {
            $collection = $this->accountFactory->create()->getCollection()
                ->addFieldToFilter('customer_id', $customerId);

            foreach ($collection as $account) {
                $accounts[] = $this->objectManager->get("Plumrocket\SocialLoginPro\Model\\" . ucfirst($account->getType()))
                    ->setData($account->getData())
                    ->setPhoto($this->dataHelper->getPhotoPath(false, $customerId, $account->getType()));
            }
        }

        return $accounts;
    }
}
