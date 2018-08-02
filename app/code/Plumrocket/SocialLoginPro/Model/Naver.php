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

class Naver extends Account
{
    protected $_type = 'naver';

    protected $_responseType = 'code';
    protected $_url = 'https://nid.naver.com/oauth2.0/authorize';

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email',
                    'dob' => 'birthday', // empty
                    'gender' => 'gender',
                    'photo' => 'profile_image',
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['M', 'W'];

    protected $_buttonLinkParams = [];

    protected $_popupSize = [650, 400];

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
        if ($response = $this->_call('https://nid.naver.com/oauth2.0/token', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token, true);


        if (isset($token['access_token'])) {
            $data = [];
            $params = [
                'access_token' => $token['access_token'],
            ];

            if ($response = $this->_call('https://openapi.naver.com/v1/nid/me', $params)) {
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

        if (!empty($data['nickname'])) {
            $nickParts = explode(' ', $data['nickname'], 2);
            if (empty($data['name_fn'])) {
                $data['name_fn'] = $nickParts[0];
            }
            if (empty($data['name_ln'])) {
                $data['name_ln'] = !empty($nickParts[1])? $nickParts[1] : '';
            }
        }

        // Gender.
        if (! empty($data['gender']) && $data['gender'] != 'M') {
            $data['gender'] = 'W';
        }

        // Birthday.
        if (! empty($data['birthday'])) {
            $parts = count(explode('-', $data['birthday']));
            if ($parts == 2) {
                $data['birthday'] = '0000-' . $data['birthday'];
            } elseif ($parts <= 1) {
                unset($data['birthday']);
            }
        }

        return parent::_prepareData($data);
    }
}
