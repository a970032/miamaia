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

class Foursquare extends Account
{
    protected $_type = 'foursquare';
    
    protected $_url = 'https://foursquare.com/oauth2/authenticate';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'firstName',
                    'lastname' => 'lastName',
                    'email' => 'email',
                    'dob' => 'birthday', // empty
                    'gender' => 'gender',
                    'photo' => 'photoUrl',
                ];

    protected $_dob = [];
    protected $_gender = ['male', 'female'];

    protected $_buttonLinkParams = [
                    // 'scope' => 'email,user_birthday',
                ];

    protected $_popupSize = [650, 550];

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
        if ($response = $this->_call('https://foursquare.com/oauth2/access_token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'oauth_token' => $token['access_token'],
                'v' => '20141122',
            ];
    
            if ($response = $this->_call('https://api.foursquare.com/v2/users/self', $params)) {
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
        if (empty($data['response']['user']['id'])) {
            return false;
        }

        $data = $data['response']['user'];

        // Email.
        $data['email'] = $data['contact']['email'];

        // Photo.
        if (!empty($data['photo']['prefix']) && !empty($data['photo']['suffix'])) {
            // 40x40(size) or "original"
            $data['photoUrl'] = $data['photo']['prefix'] .'40x40'. $data['photo']['suffix'];
        }

        return parent::_prepareData($data);
    }
}
