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

namespace Plumrocket\SocialLoginPro\Controller\Account;

use Magento\Framework\Controller\ResultFactory;

class UnlinkAction extends \Plumrocket\SocialLoginPro\Controller\AbstractAccount
{

    /**
     * Account factory
     * @var \Plumrocket\SocialLoginPro\Model\AccountFactory
     */
    protected $accountFactory;

    /**
     * Contructor
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Plumrocket\SocialLoginPro\Model\AccountFactory $accountFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Plumrocket\SocialLoginPro\Model\AccountFactory $accountFactory
    ) {
        $this->accountFactory = $accountFactory;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $customer = $this->_getSession()->getCustomer();

        if ($this->getRequest()->getParam('id') && $customer && $customer->getId()) {
            $account = $this->accountFactory->create()->getCollection()
                ->addFieldToFilter('customer_id', $customer->getId())
                ->addFieldToFilter('id', $this->getRequest()->getParam('id'))
                ->getFirstItem();

            if ($account->getId()) {
                try {
                    $account->delete();
                    $this->messageManager->addSuccess(__('You was successfully unlinked'));
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                }
            }
        } else {
            $this->messageManager->addError(__('Missed required parameters'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
