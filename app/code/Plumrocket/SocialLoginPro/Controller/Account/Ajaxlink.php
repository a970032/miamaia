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

class Ajaxlink extends \Plumrocket\SocialLoginPro\Controller\AbstractAccount
{

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $customer = $this->_getSession()->getCustomer();
        if ($this->_request->isXmlHttpRequest() && $customer) {
            $this->_view->loadLayout();
            $html = $this->_view->getLayout()
                ->getBlock('pslogin.link.popup')
                ->toHtml();

            return $this->getResponse()->setBody(
                json_encode(
                    [
                        'html' => $html
                    ]
                )
            );
        }
    }
}
