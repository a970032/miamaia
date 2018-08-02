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

class Mixi extends Account
{
    protected $_type = 'mixi';
    
    protected $_responseType = 'code';
    protected $_url = 'https://mixi.jp/connect_authorize.pl';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'givenName',
                    'lastname' => 'familyName',
                    'email' => 'email', // empty
                    'dob' => 'birthday',
                    'gender' => 'gender',
                    'photo' => 'thumbnailUrl',
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['male', 'female'];

    protected $_buttonLinkParams = [
                    'scope' => 'r_profile',
                    'display' => 'popup',
                ];

    protected $_popupSize = [950, 550];

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
        if ($response = $this->_call('https://api.mixi-platform.com/2/token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'access_token' => $token['access_token'],
            ];
    
            if ($response = $this->_call('https://api.mixi-platform.com/2/people/@me/@self?fields=@all', $params)) {
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
        if (empty($data['entry'][0]['id'])) {
            return false;
        }

        $data = $data['entry'][0];

        // Name.
        if (isset($data['name']['givenName'])) {
            $data['givenName'] = $data['name']['givenName'];
        }
        if (isset($data['name']['familyName'])) {
            $data['familyName'] = $data['name']['familyName'];
        }

        return parent::_prepareData($data);
    }
}
