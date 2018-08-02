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

class Reddit extends Account
{
    protected $_type = 'reddit';
    
    protected $_responseType = ['code', 'state'];
    protected $_url = 'https://ssl.reddit.com/api/v1/authorize';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'gender', // empty
                    'photo' => 'photo', // empty
                ];

    protected $_buttonLinkParams = [
                    'scope' => 'identity',
                    'duration' => 'permanent',
                ];

    protected $_popupSize = [1000, 500];

    public function _construct()
    {
        parent::_construct();
        
        $state = md5(rand());

        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType[0],
            'state'         => $state,
        ]);
    }

    public function loadUserData($response)
    {
        if (empty($response['code']) || empty($response['state'])) {
            return false;
        }

        $data = [];

        $params = [
            'client_id' => $this->_applicationId,
            'client_secret' => $this->_secret,
            'redirect_uri' => $this->_redirectUri,
            'code' => $response['code'],
            'state' => $response['state'],
            'grant_type' => 'authorization_code',
        ];
    
        $token = null;
        if ($response = $this->_callPost('https://ssl.reddit.com/api/v1/access_token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'access_token' => $token['access_token'],
            ];
    
            if ($response = $this->_callGet('https://oauth.reddit.com/api/v1/me.json', $params)) {
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
            if (false !== strpos('-', $data['name'])) {
                $nameParts = explode('-', $data['name'], 2);
            } else {
                $nameParts = explode('_', $data['name'], 2);
            }
            $data['name_fn'] = $nameParts[0];
            $data['name_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_prepareData($data);
    }

    protected function _callGet($url, $params = [])
    {
        $result = '';

        $curl = curl_init('https://oauth.reddit.com/');
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $headers = [
            'Host: oauth.reddit.com',
            'User-Agent: OAuth-API',
            'Accept: */*',
            'Authorization: Bearer '. $params['access_token'],
            'Connection: Keep-Alive',
        ];

        $body = '';
        
        $request = "GET /api/v1/me HTTP/1.1\r\n".implode("\r\n", $headers)."\r\n\r\n".$body;

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request);

        $response = curl_exec($curl);

        /*$header_size = curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);
        $result['header'] = substr($response, 0, $header_size);
        $result['body'] = substr( $response, $header_size );
        $result['http_code'] = curl_getinfo($this -> ch,CURLINFO_HTTP_CODE);
        $result['last_url'] = curl_getinfo($this -> ch,CURLINFO_EFFECTIVE_URL);*/
        
        $result = substr($response, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
        curl_close($curl);

        return $result;
    }

    protected function _callPost($url, $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);

        curl_setopt($curl, CURLOPT_USERPWD, $this->_applicationId .':'. $this->_secret);

        curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}
