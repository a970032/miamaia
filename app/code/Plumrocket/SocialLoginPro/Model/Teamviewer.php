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

class Teamviewer extends Account
{
    protected $_type = 'teamviewer';
    
    protected $_responseType = 'code';
    protected $_url = 'https://webapi.teamviewer.com/api/v1/oauth2/authorize';

    protected $_fields = [
                    'user_id' => 'userid',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email',
                    'dob' => 'birthday', // empty
                    'gender' => 'gender', // empty
                    'photo' => 'photo', // empty
                ];

    protected $_buttonLinkParams = [
                    // 'scope' => 'non-expiring',
                ];

    protected $_popupSize = [650, 650];

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
            'redirect_uri' => $this->_redirectUri,
            'code' => $response,
            'grant_type' => 'authorization_code',
        ];
    
        $token = null;
        if ($response = $this->_callPost('https://webapi.teamviewer.com/api/v1/oauth2/token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'access_token' => $token['access_token'],
            ];
    
            if ($response = $this->_callGet('https://webapi.teamviewer.com/api/v1/account', $params)) {
                $data = json_decode($response, true);
            }
            $this->_setLog($data, true);
            
            if (isset($data['response'])) {
                $data = $data['response'];
            }
        }
 
        if (!$this->_userData = $this->_prepareData($data)) {
            return false;
        }

        $this->_setLog($this->_userData, true);

        return true;
    }

    protected function _prepareData($data)
    {
        if (empty($data['userid'])) {
            return false;
        }

        // Name.
        if (!empty($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $data['name_fn'] = $nameParts[0];
            $data['name_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_prepareData($data);
    }

    protected function _callGet($url, $params = [])
    {
        if (is_array($params) && $params) {
            $url .= '?'. urldecode(http_build_query($params));
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        
        // Authorization header missing.
        curl_setopt($curl, CURLOPT_USERPWD, $this->_applicationId .':'. $this->_secret);
        if (isset($params['access_token'])) {
            $headers = ['Authorization: Bearer '. $params['access_token']];
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        // curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'pslogin');

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    protected function _callPost($url, $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        // curl_setopt($curl, CURLOPT_USERPWD, $this->_applicationId .':'. $this->_secret);
        curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}
