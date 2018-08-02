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

class LinkAction extends \Plumrocket\SocialLoginPro\Controller\AbstractAccount
{
    public function execute()
    {
        $customer = $this->_getSession()->getCustomer();
        $type = $this->getRequest()->getParam('type');
        $model = $this->getModelByType($type);
        if ($type && $model && $customer && $customer->getId()) {
            $model->setCustomerIdByUserId($customer->getId());
        }

        $redirectUrl = $this->_getUrl('pslogin/account/view');
        $this->getResponse()->setBody($this->_jsWrap('if(window.opener && window.opener.location &&  !window.opener.closed) { window.close(); window.opener.location.href = "'.$redirectUrl.'"; }else{ window.location.href = "'.$redirectUrl.'"; }'));
    }
}
