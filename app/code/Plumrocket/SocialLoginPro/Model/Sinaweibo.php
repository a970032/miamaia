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

class Sinaweibo extends Account
{
    protected $_type = 'sinaweibo';
    
    protected $_responseType = 'code';
    protected $_url = 'https://api.weibo.com/oauth2/authorize';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'gender', // empty
                    'photo' => 'profile_image_url',
                ];

    protected $_buttonLinkParams = [];

    protected $_popupSize = [850, 550];

    public function _construct()
    {
        parent::_construct();
        
        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
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
            'client_id' => $this->_applicationId,
            'client_secret' => $this->_secret,
            'code' => $response,
            'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'authorization_code',
        ];
    
        $token = null;
        if ($response = $this->_call('https://api.weibo.com/oauth2/access_token', $params, 'POST')) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (!empty($token['access_token']) && !empty($token['uid'])) {
            $params = [
                'access_token' => $token['access_token'],
                'uid' => $token['uid'],
            ];
    
            if ($response = $this->_call('https://api.weibo.com/2/users/show.json', $params)) {
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
        if (empty($data['id'])) {
            return false;
        }

        // Name.
        if (!empty($data['name'])) {
            $separator = mb_strpos($data['name'], '_') !== false? '_' : '-';
            $nameParts = explode($separator, $data['name'], 2);
            $data['name_fn'] = ucfirst($nameParts[0]);
            $data['name_ln'] = !empty($nameParts[1])? ucfirst($nameParts[1]) : '';
        }

        return parent::_prepareData($data);
    }
}
