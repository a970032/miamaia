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

class Wechat extends Account
{
    protected $_type = 'wechat';

    protected $_responseType = 'code';
    protected $_url = 'https://open.weixin.qq.com/connect/qrconnect';

    protected $_fields = [
                    'user_id' => 'openid',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'sex',
                    'photo' => 'headimgurl',
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['1', '2'];

    protected $_buttonLinkParams = [
                    'scope' => 'snsapi_login',// snsapi_base snsapi_login
                    'display' => 'popup',
                ];

    protected $_popupSize = [650, 400];

    public function _construct()
    {
        parent::_construct();

        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            // 'client_id'     => $this->_applicationId,
            'appid'         => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType
        ]);
    }

    public function loadUserData($response)
    {
        if (empty($response)) {
            return false;
        }

        $data = [];

        $params = [
            // 'client_id' => $this->_applicationId,
            'appid' => $this->_applicationId,
            // 'client_secret' => $this->_secret,
            'secret' => $this->_secret,
            'code' => $response,
            // 'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $token = null;
        if ($response = $this->_call('https://api.weixin.qq.com/sns/oauth2/access_token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);


        if (isset($token['access_token']) && isset($token['openid'])) {
            // User info.
            $params = [
                'access_token' => $token['access_token'],
                'openid' => $token['openid'],
                // 'openid' => $token['uid'],
                // 'oauth_consumer_key' => $this->_applicationId,
                // 'lang' => 'zh_CN'
            ];

            if ($response = $this->_call('https://api.weixin.qq.com/sns/userinfo', $params)) {
                $data = json_decode($response, true);
            }
            $this->_setLog($data, true);
        }

        if (!$this->_userData = $this->_prepareData($data)) {
            return false;
        }

        $this->_setLog($this->_userData, true);

        return true;
    }

    protected function _prepareData($data)
    {
        if (empty($data['openid'])) {
            return false;
        }

        // Name.
        if (!empty($data['nickname'])) {
            $nameParts = explode(' ', $data['nickname'], 2);
            $data['name_fn'] = $nameParts[0];
            $data['name_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_prepareData($data);
    }
}
