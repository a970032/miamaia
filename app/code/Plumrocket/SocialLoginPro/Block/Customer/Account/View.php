<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_SocialLogin
 * @copyright   Copyright (c) 2014 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

namespace Plumrocket\SocialLoginPro\Block\Customer\Account;

class View extends \Magento\Framework\View\Element\Template
{

    /**
     * Data helper
     * @var \Plumrocket\SocialLoginPro\Helper\Data
     */
    protected $dataHelper;

    /**
     * Account factory
     * @var \Plumrocket\SocialLoginPro\Model\AccountFactory
     */
    protected $accountFactory;

    /**
     * Customer accounts
     * @var Array
     */
    protected $_accounts;

    /**
     * Customer session
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * Constructor
     * @param \Plumrocket\SocialLoginPro\Helper\Data           $dataHelper
     * @param \Plumrocket\SocialLoginPro\Model\AccountFactory  $accountFactory
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        \Plumrocket\SocialLoginPro\Helper\Data $dataHelper,
        \Plumrocket\SocialLoginPro\Model\AccountFactory $accountFactory,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->accountFactory = $accountFactory;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve customer account
     * @return PLumrpcket\SocialLoginPro\Model\ResourceModel\Account\Collection
     */
    public function getCustomerAccounts()
    {
        if ($this->_accounts === null) {
            $customer = $this->currentCustomer;

            if ($this->currentCustomer->getCustomerId()) {
                $accounts = $this->accountFactory->create()->getCollection()
                    ->addFieldToFilter('customer_id', $this->currentCustomer->getCustomerId());

                $this->_accounts = [];
                foreach ($accounts as $account) {
                    $this->_accounts[$account->getType()] = $account;
                }
            }
        }

        return $this->_accounts;
    }

    /**
     * Retrieve linking description
     * @return string
     */
    public function getLinkingDescription()
    {
        return $this->dataHelper->getLinkingDescription();
    }

    /**
     * Is social linking enabled
     * @return boolean
     */
    public function isLinkingEnabled()
    {
        return $this->dataHelper->isLinkingEnabled();
    }
}
