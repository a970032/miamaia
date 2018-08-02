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

namespace Plumrocket\SocialLoginPro\Block\Link;

class Buttons extends \Magento\Framework\View\Element\Template
{
    /**
     * Show full buttons
     * @var boolean
     */
    protected $_showFull = true;

    /**
     * Data helper
     * @var \Plumrocket\SocialLoginPro\Helper\Data
     */
    protected $dataHelper;

    /**
     * Customer view
     * @var \Plumrocket\SocialLoginPro\Block\Customer\Account\View
     */
    protected $customerView;

    /**
     * Construtor
     * @param \Plumrocket\SocialLoginPro\Helper\Data                 $dataHelper
     * @param \Plumrocket\SocialLoginPro\Block\Customer\Account\View $customerView
     * @param \Magento\Framework\View\Element\Template\Context       $context
     * @param array                                                  $data
     */
    public function __construct(
        \Plumrocket\SocialLoginPro\Helper\Data $dataHelper,
        \Plumrocket\SocialLoginPro\Block\Customer\Account\View $customerView,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
        $this->customerView = $customerView;
    }

    /**
     * Retrieve link buttons
     * @return Array
     */
    public function getLinkButtons($part = null)
    {
        $buttons = $this->dataHelper->getPreparedButtons($part);

        $activeAccounts = $this->customerView->getCustomerAccounts();
        foreach ($buttons as $key => $button) {
            if (isset($activeAccounts[$button['type']])) {
                unset($buttons[$key]);
            }
        }

        return $buttons;
    }

    /**
     * Show full button
     * @return boolean
     */
    public function showFull()
    {
        return $this->_showFull;
    }

    /**
     * Set show full
     * @param string $showFull
     */
    public function setShowFull($showFull)
    {
        $this->_showFull = $showFull;
        return $this;
    }
}
