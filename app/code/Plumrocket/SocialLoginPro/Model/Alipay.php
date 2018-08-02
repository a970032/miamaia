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

class Alipay extends Account
{
    protected $_type = 'alipay';
    
    // protected $_responseType = 'code';
    protected $_responseType = 'app_auth_code';
    // protected $_url = 'https://openauth.alipay.com/oauth2/authorize';
    protected $_url = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm';

    protected $_fields = [
                    'user_id' => 'alipay_user_id',
                    'firstname' => 'real_name_fn',
                    'lastname' => 'real_name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday',
                    'gender' => 'sex',
                    'photo' => 'figureurl_1',
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['男', '女'];
                        /*Man 男人
                        Woman 女人
                        Male 男(性)
                        Female 女(性)*/

    protected $_buttonLinkParams = [
                    // 'display' => 'popup',
                ];

    protected $_popupSize = [650, 400];

    public function _construct()
    {
        parent::_construct();
        
        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'app_id'        => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            // 'response_type' => $this->_responseType
        ]);
    }

    public function loadUserData($response)
    {
        if (empty($response)) {
            return false;
        }

        $data = [];

        $params = [
            // 'app_id' => $this->_applicationId,
            // 'method' => 'alipay.open.auth.token.app',
            'client_id' => $this->_applicationId,
            'client_secret' => $this->_secret,
            'code' => $response,
            'redirect_uri' => $this->_redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $token = null;
        if ($response = $this->_call('https://openauth.alipay.com/oauth2/token', $params)) {
            $token = json_decode($response, true);
            parse_str($response, $token);
        }
        $this->_setLog($token, true);


        if (isset($token['access_token'])) {
            // User info.
            $params = [
                'app_key' => $this->_applicationId,
                'fields' => 'alipay_user_id,real_name,logon_id,sex,user_status,user_type,created,last_visit,birthday,type,status',
                'format' => 'json',
                'method' => 'alipay.user.get',
                'session' => $token['access_token'],
                'sign_method' => 'md5',
                'timestamp' => strftime('%Y-%m-%d %H:%M:%S'),
                'v' => '2.0',
            ];

            // # params.sort.collect { |k, v| "#{k}#{v}" }
            // str = options.client_secret + params.sort {|a,b| "#{a[0]}"<=>"#{b[0]}"}.flatten.join + options.client_secret
            // params['sign'] = Digest::MD5.hexdigest(str).upcase!

            $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));
            $sign = $this->_secret . join('', iterator_to_array($iterator, true)) . $this->_secret;
            $params['sign'] = strtoupper(md5($sign));
 
            if ($response = $this->_call('https://openapi.alipay.com/gateway.do', $params)) {
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
        if (empty($data['alipay_user_id'])) {
            return false;
        }

        // Name.
        if (!empty($data['real_name'])) {
            $nameParts = explode(' ', $data['real_name'], 2);
            $data['real_name_fn'] = $nameParts[0];
            $data['real_name_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_prepareData($data);
    }
}
