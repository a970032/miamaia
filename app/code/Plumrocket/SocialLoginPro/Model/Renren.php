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

class Renren extends Account
{
    protected $_type = 'renren';
    
    protected $_responseType = 'code';
    protected $_url = 'https://graph.renren.com/oauth/authorize';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday',
                    'gender' => 'gender',
                    'photo' => 'photoUrl',
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['MALE', 'FEMALE'];

    protected $_buttonLinkParams = [
                    'scope' => 'read_user_feed read_user_album read_user_photo',
                    'display' => 'popup',
                ];

    protected $_popupSize = [700, 400];

    public function _construct()
    {
        parent::_construct();
        
        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType
        ]);
    }

    public function getProviderLink()
    {
        if (empty($this->_applicationId) || empty($this->_secret)) {
            $uri = null;
        } elseif (is_array($this->_buttonLinkParams)) {
            $uri = $this->_url .'?'. /*urldecode*/(http_build_query($this->_buttonLinkParams));
        } else {
            $uri = $this->_buttonLinkParams;
        }

        return $uri;
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
        if ($response = $this->_call('https://graph.renren.com/oauth/token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token']) && isset($token['user']['id'])) {
            $params = [
                'access_token' => $token['access_token'],
                'userId' => $token['user']['id'],
            ];
    
            if ($response = $this->_call('https://api.renren.com/v2/user/get', $params)) {
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
        if (empty($data['response']['id'])) {
            return false;
        }

        $data = $data['response'];

        // Name.
        if (!empty($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $data['name_fn'] = $nameParts[0];
            $data['name_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        // Birthday.
        if (!empty($data['basicInformation']['birthday'])) {
            $data['birthday'] = $data['basicInformation']['birthday'];
        }

        // Gender.
        if (!empty($data['basicInformation']['sex'])) {
            $data['gender'] = $data['basicInformation']['sex'];
        }

        // Photo.
        if (!empty($data['avatar'][0]['url'])) {
            $data['photoUrl'] = $data['avatar'][0]['url'];
        }

        return parent::_prepareData($data);
    }
}
