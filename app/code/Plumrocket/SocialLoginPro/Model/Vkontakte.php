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

class Vkontakte extends Account
{
    protected $_type = 'vkontakte';
    
    protected $_url = 'http://oauth.vk.com/authorize';

    protected $_fields = [
                    'user_id' => 'uid',
                    'firstname' => 'first_name',
                    'lastname' => 'last_name',
                    'email' => 'email',
                    'dob' => 'bdate',
                    'gender' => 'sex',
                    'photo' => 'photo_rec',
                ];
    
    protected $_dob = ['day', 'month', 'year', '.'];
    protected $_gender = ['2', '1'];

    protected $_buttonLinkParams = [
                    'scope' => 'email,wall',
                    'display' => 'popup',
                    'v' => '5.24',
                ];

    protected $_popupSize = [605, 425];

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
            'redirect_uri' => $this->_redirectUri
        ];
    
        $token = null;
        if ($response = $this->_call('https://oauth.vk.com/access_token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);

        if (isset($token['access_token'])) {
            $params = [
                'uids' => $token['user_id'],
                'fields' => implode(',', $this->_fields),
                'access_token' => $token['access_token']
            ];

            if ($response = $this->_call('https://api.vk.com/method/users.get', $params)) {
                $data = json_decode($response, true);
            }

            if (isset($data['response'][0]) && !empty($this->_token['email'])) {
                $data['response'][0]['email'] = $token['email'];
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
        if (empty($data['response'][0]['uid'])) {
            return false;
        }
        $data = $data['response'][0];

        return parent::_prepareData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getSocialUrl()
    {
        if ($id = $this->getUserId()) {
            return 'https://vk.com/id' . $id;
        }
        return null;
    }
}
