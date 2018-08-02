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

class Imgur extends Account
{
    protected $_type = 'imgur';
    
    protected $_responseType = ['code', 'state'];
    protected $_url = 'https://api.imgur.com/oauth2/authorize';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'url',
                    'lastname' => 'lastname', // empty
                    'email' => 'email',
                    'dob' => 'birthday', // empty
                    'gender' => 'gender', // empty
                    'photo' => 'photoUrl', // empty
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['MALE', 'FEMALE'];

    protected $_buttonLinkParams = [
                    'scope' => '',
                    // 'scope' => 'read_user_feed read_user_album read_user_photo',
                    // 'display' => 'popup',
                ];

    protected $_popupSize = [850, 530];

    public function _construct()
    {
        parent::_construct();

        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType[0],
            'state'         => md5(rand()),
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
        if ($response = $this->_callPost('https://api.imgur.com/oauth2/token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'access_token' => $token['access_token'],
            ];
            
            $me = null;
            if ($response = $this->_callGet('/3/account/me', $params)) {
                $me = json_decode($response, true);
            }

            $settings = null;
            if ($response = $this->_callGet('/3/account/me/settings', $params)) {
                $settings = json_decode($response, true);
            }

            $me = is_array($me)? $me : [];
            $settings = is_array($settings)? $settings : [];
            $data = array_merge_recursive($me, $settings);
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
        if (empty($data['data']['id'])) {
            return false;
        }

        $data = $data['data'];

        return parent::_prepareData($data);
    }

    protected function _callPost($url, $params = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, urldecode(http_build_query($params)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }


    protected function _callGet($url, $params = [])
    {
        // Send post by customrequest.
        $result = '';

        $curl = curl_init('https://api.imgur.com');
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                
        $body = http_build_query($params);

        $headers = [
            'Host: api.imgur.com',
            'User-Agent: OAuth-API',
            'Accept: */*',
            'Connection: Keep-Alive',
            // 'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '. $params['access_token'],
            'Content-Length: '. strlen($body),
        ];


        $request = "GET $url HTTP/1.1\r\n".implode("\r\n", $headers)."\r\n\r\n".$body;
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
}
