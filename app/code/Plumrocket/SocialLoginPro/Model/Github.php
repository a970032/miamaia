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

class Github extends Account
{
    protected $_type = 'github';
    
    protected $_url = 'https://github.com/login/oauth/authorize';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'login_fn',
                    'lastname' => 'login_ln',
                    'email' => 'email',
                    'dob' => 'birthday',
                    'gender' => 'gender',
                    'photo' => 'avatar_url',
                ];

    protected $_dob = [];
    protected $_gender = ['male', 'female'];

    protected $_buttonLinkParams = [
                    'scope' => 'user:email',
                ];

    protected $_popupSize = [750, 500];

    public function _construct()
    {
        parent::_construct();

        // $oauth_nonce = md5(uniqid(rand(), true));
        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType,
            // 'state' => $oauth_nonce,
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
            'redirect_uri' => $this->_redirectUri
        ];
    
        $token = null;
        if ($response = $this->_callGet('https://github.com/login/oauth/access_token', $params)) {
            parse_str($response, $token);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'access_token' => $token['access_token'],
            ];
    
            if ($response = $this->_callGet('https://api.github.com/user', $params)) {
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
        if (!empty($data['login'])) {
            $nameParts = explode(' ', $data['login'], 2);
            $data['login_fn'] = $nameParts[0];
            $data['login_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
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
        
        // curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'pslogin');

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }


    /*public static $httpCode = -1;
    public static $httpInfo = [];
    public static function exec($method, $url, $data = null)
    {
        $method = strtoupper($method);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'php-social');
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'GET':
            case 'DELETE':
                if (is_array($data) && count($data)) {
                    $data = http_build_query($data);
                }
                
                if ($data) {
                    $url .= '?' . $data;
                }
                
                if ($method == 'DELETE') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                }
                break;
            
            default:
                throw new \LogicException(sprintf('Http method "%s" not implement!', $method));
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        $buffer = curl_exec($ch);
        self::$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::$httpInfo = curl_getinfo($ch);
        
        if ($buffer === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return json_encode(['error' => sprintf('Curl error "%s"', $error)]);
        }
        
        curl_close($ch);
        return $buffer;
    }*/
}
