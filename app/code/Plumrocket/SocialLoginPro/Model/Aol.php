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

namespace Plumrocket\SocialLoginPro\Model;

class Aol extends Account
{
    protected $_type = 'aol';
    protected $_protocol = 'OpenID';
    protected $_identifier = 'openid.aol.com/_ID_';
    protected $_title = 'Enter your AOL screenname';

    protected $_responseType = [
                    'openid_mode',
                    'openid_identity',
                    'openid_sreg_nickname',
                    'openid_sreg_email',
                    'openid_sreg_dob',
                    'openid_sreg_gender',
                ];

    protected $_fields = [
                    'user_id' => 'openid_identity',
                    'firstname' => 'nickname_fn',
                    'lastname' => 'nickname_ln',
                    'email' => 'openid_sreg_email',
                    'dob' => 'openid_sreg_dob',
                    'gender' => 'openid_sreg_gender',
                    'photo' => 'photo', // empty
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['M', 'F'];

    protected $_buttonLinkParams = null;

    protected $_popupSize = [730, 450];

    public function _construct()
    {
        parent::_construct();
    }

    public function loadUserData($response)
    {
        if (empty($response['openid_mode']) || $response['openid_mode'] == 'cancel') {
            return false;
        }

        $data = [];
 
        if ($response['openid_mode'] == 'id_res') {
            $data = $response;
        }

        if (!$this->_userData = $this->_prepareData($data)) {
            return false;
        }

        $this->_setLog($this->_userData, true);

        return true;
    }

    public function prepareIdentifier($identifier)
    {
        if (!$identifier = trim($identifier)) {
            return false;
        }

        if (false === strpos($identifier, str_replace('_ID_', '', $this->_identifier))) {
            $identifier = str_replace('_ID_', $identifier, $this->_identifier);
        }

        return $identifier;
    }

    public function getTitle()
    {
        return __($this->_title);
    }

    protected function _prepareData($data)
    {
        if (empty($data['openid_identity'])) {
            return false;
        }

        // Name.
        if (!empty($data['openid_sreg_nickname'])) {
            $nameParts = explode(' ', $data['openid_sreg_nickname'], 2);
            $data['nickname_fn'] = $nameParts[0];
            $data['nickname_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_prepareData($data);
    }
}
