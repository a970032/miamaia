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

class Lastfm extends Account
{
    protected $_type = 'lastfm';
    
    protected $_responseType = 'token';

    protected $_url = 'http://www.last.fm/api/auth/';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'realname_fn',
                    'lastname' => 'realname_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'gender',
                    'photo' => 'photoUrl',
                ];

    protected $_gender = ['m', 'f'];

    protected $_buttonLinkParams = [];

    protected $_popupSize = [1020, 480];

    public function _construct()
    {
        parent::_construct();
        
        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'api_key'     => $this->_applicationId,
            'cb'  => $this->_redirectUri,
        ]);
    }

    public function loadUserData($response)
    {
        if (empty($response)) {
            return false;
        }

        $data = [];

        $signature = md5("api_key{$this->_applicationId}methodauth.getSessiontoken{$response}{$this->_secret}");

        $params = [
            'method' => 'auth.getSession',
            'token' => $response,
            'api_key' => $this->_applicationId,
            'api_sig' => $signature,
            'format' => 'json',
        ];
    
        $json = null;
        if ($response = $this->_call('http://ws.audioscrobbler.com/2.0/', $params, 'POST')) {
            $json = json_decode($response, true);
        }
        $this->_setLog($json, true);

        if (!empty($json['session']['key'])) {
            $params = [
                'method' => 'user.getInfo',
                'api_key' => $this->_applicationId,
                'user' => $json['session']['name'],
                'format' => 'json',
            ];
    
            if ($response = $this->_call('http://ws.audioscrobbler.com/2.0/', $params, 'POST')) {
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
        if (empty($data['user']['id'])) {
            return false;
        }

        $data = $data['user'];

        // Name.
        if (!empty($data['realname'])) {
            $nameParts = explode(' ', $data['realname'], 2);
            $data['realname_fn'] = ucfirst($nameParts[0]);
            $data['realname_ln'] = !empty($nameParts[1])? ucfirst($nameParts[1]) : '';
        }

        if (!empty($data['name'])) {
            $separator = strpos($data['name'], '_') !== false? '_' : '-';
            $nameParts = explode($separator, $data['name'], 2);
            $data['name_fn'] = ucfirst($nameParts[0]);
            $data['name_ln'] = !empty($nameParts[1])? ucfirst($nameParts[1]) : '';
        }

        if (empty($data['realname_fn'])) {
            $data['realname_fn'] = $data['name_fn'];
        }
        if (empty($data['realname_ln'])) {
            $data['realname_ln'] = !empty($data['name_ln'])? $data['name_ln'] : $data['name'];
        }

        // Photo.
        $photoUrl = is_array($data['image'])? array_pop($data['image']) : $data['image'];
        if (!empty($photoUrl)) {
            $data['photoUrl'] = $photoUrl;
        }

        return parent::_prepareData($data);
    }
}
