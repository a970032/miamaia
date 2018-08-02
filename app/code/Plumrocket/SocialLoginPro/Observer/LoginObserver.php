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
 * @package     Plumrocket_SocialLoginPro
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

namespace Plumrocket\SocialLoginPro\Observer;

use Plumrocket\SocialLoginPro\Helper\Data as HelperData;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Plumrocket\SocialLoginPro\Model\Account;

class LoginObserver implements ObserverInterface
{
    protected $_helper;
    protected $_session;
    protected $customerFactory;
    protected $_oneDay;

    /**
     * @var Plumrocket\SocialLoginPro\Model\Account
     */
    protected $account;

    public function __construct(
        HelperData $helper,
        Session $customerSession,
        Account $account,
        CustomerFactory $customerFactory
    ) {
        $this->_helper = $helper;
        $this->_session = $customerSession;
        $this->account = $account;
        $this->customerFactory = $customerFactory;
        $this->_oneDay = 24*60*60 ;
    }

    public function execute(Observer $observer)
    {
        if (!$this->_helper->moduleEnabled()) {
            return;
        }

        // Set redirect url.
        $redirectUrl = $this->_helper->getRedirectUrl('login');
        $this->_session->setBeforeAuthUrl($redirectUrl);

        $this->_showLinkPopup($observer->getCustomer());
    }

    protected function _showLinkPopup($customer)
    {
        if ($this->_helper->linkPopupEnabled()) {
            $currentTimestamp = time();
            $oneDay = 86400;

            $_customer = $this->customerFactory->create()->load($customer->getId());

            if (($_customer->getCreatedAtTimestamp() + $oneDay) > $currentTimestamp) {
                return false;
            }

            $lastShown = $_customer->getData('pslogin_link_popup_date');

            if ($lastShown !== null) {
                $configTime = (int)$this->_helper->getLinkPopupTimeout();
                $lastShown = strtotime($lastShown);

                if (!$configTime) {
                    return false;
                }

                $mustShow = $configTime * $this->_oneDay + $lastShown;

                if ($mustShow > $currentTimestamp) {
                    return false;
                }
            }

            $_customer->setData('pslogin_link_popup_date', $currentTimestamp)
                ->save();

            /* Subscribed to all networks */
            $accounts = $this->account->getCollection()
                ->addFieldToFilter('customer_id', $_customer->getId());

            if ($accounts->count() > 1) { //if have one or more social accounts
                return false;
            }

            $this->_helper->showLinkPopup();
        }
    }
}
