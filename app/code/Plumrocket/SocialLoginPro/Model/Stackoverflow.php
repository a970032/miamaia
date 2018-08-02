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

class Stackoverflow extends Account
{
    protected $_type = 'stackoverflow';
    
    protected $_responseType = 'code';
    protected $_url = 'https://stackexchange.com/oauth';
    // protected $_url = 'https://stackexchange.com/oauth/dialog';
    protected $_key = null;

    protected $_fields = [
                    'user_id' => 'account_id',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'gender', // empty
                    'photo' => 'profile_image',
                ];

    protected $_gender = ['male', 'female'];

    protected $_buttonLinkParams = [
                    'scope' => 'private_info',
                ];

    protected $_popupSize = [600, 550];

    public function _construct()
    {
        parent::_construct();

        $this->_key = trim($this->_helper->getConfig($this->_helper->getConfigSectionId() .'/'. $this->_type .'/key'));
        
        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType,
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
        ];
    
        $token = null;
        if ($result = $this->_callPost('https://stackexchange.com/oauth/access_token', $params)) {
            parse_str($result, $token);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'site' => 'stackoverflow',
                'access_token' => $token['access_token'],
                'key' => $this->_key,
            ];

            if ($result = $this->_callGet('https://api.stackexchange.com/2.2/me', $params)) {
                $data = json_decode($result, true);
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
        if (empty($data['items'][0]['account_id'])) {
            return false;
        }

        $data = $data['items'][0];

        // Name.
        if (!empty($data['display_name'])) {
            $nameParts = explode(' ', $data['display_name'], 2);
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
        
        // curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($curl, CURLOPT_USERAGENT, 'pslogin');

        $result = curl_exec($curl);
        curl_close($curl);

        $result = $this->_gzdecode($result);

        return $result;
    }

    protected function _gzdecode($data)
    {
        if (!function_exists("gzdecode")) {
            return gzinflate(substr($data, 10, -8));
        } else {
            return gzdecode($data);
        }
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

    /**
     * {@inheritdoc}
     */
    public function getSocialUrl()
    {
        if ($id = $this->getUserId()) {
            return 'http://stackoverflow.com/users/' . $id;
        }
        return null;
    }
}
