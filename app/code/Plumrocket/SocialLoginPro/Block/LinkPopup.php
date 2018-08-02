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
 * @copyright   Copyright (c) 2017 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */

namespace Plumrocket\SocialLoginPro\Block;

use \Plumrocket\SocialLoginPro\Helper\Data as DataHelper;

class LinkPopup extends \Magento\Framework\View\Element\Template
{
    /**
     * Data helper
     * @var \Plumrocket\SocialLoginPro\Helper\Data
     */
    protected $dataHelper;

    /**
     * Customer session
     * @var Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Constructor
     * @param DataHelper                                       $dataHelper
     * @param \Magento\Customer\Model\Session                  $customerSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
        DataHelper $dataHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    /**
     * Show popup
     * @return boolean
     */
    public function showPopup()
    {
        return $this->dataHelper->moduleEnabled() && $this->dataHelper->linkPopupEnabled()
            && $this->customerSession->getCustomerGroupId();
    }

    /**
     * Retrieve descripton for popup
     * @return string
     */
    public function getDescription()
    {
        return $this->dataHelper->getLinkingDescription();
    }
}
