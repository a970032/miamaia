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

namespace Plumrocket\SocialLoginPro\Block\Customer\Account\Navigation;

class AccountLink extends \Magento\Framework\View\Element\Html\Link\Current
{

    /**
     * Data helper
     * @var Plumrocket\SocialLoginPro\Helper\Data
     */
    protected $dataHelper;

    /**
     * Constructor
     * @param \Plumrocket\SocialLoginPro\Helper\Data           $dataHelper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface      $defaultPath
     * @param array                                            $data
     */
    public function __construct(
        \Plumrocket\SocialLoginPro\Helper\Data $dataHelper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $defaultPath, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        if (!$this->dataHelper->moduleEnabled() || !$this->dataHelper->isLinkingEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }
}
