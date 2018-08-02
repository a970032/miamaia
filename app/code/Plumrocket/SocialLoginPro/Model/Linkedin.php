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

class Linkedin extends Account
{
    protected $_type = 'linkedin';
    
    protected $_url = 'https://www.linkedin.com/uas/oauth2/authorization';

    protected $_fieldsToResponse = [
                    'user_id' => 'id',
                    'firstname' => 'first-name',
                    'lastname' => 'last-name',
                    'email' => 'email-address',
                    'photo' => 'picture-url',
                ];

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'firstName',
                    'lastname' => 'lastName',
                    'email' => 'emailAddress',
                    'dob' => null,
                    'gender' => null,
                    'photo' => 'pictureUrl',
                ];

    protected $_dob = [];
    protected $_gender = ['male', 'female'];

    protected $_buttonLinkParams = [
                    'scope' => 'r_basicprofile,r_emailaddress',
                    'state' => 'popup',
                ];

    protected $_popupSize = [400, 550];

    public function _construct()
    {
        parent::_construct();

        $state = md5(uniqid(rand(), true));

        $this->_buttonLinkParams = array_merge($this->_buttonLinkParams, [
            'client_id'     => $this->_applicationId,
            'redirect_uri'  => $this->_redirectUri,
            'response_type' => $this->_responseType,
            'state' => $state,
        ]);
    }

    public function loadUserData($response)
    {
        if (empty($response)) {
            return false;
        }

        $data = [];

        $params = [
            'grant_type' => 'authorization_code',
            'code' => $response,
            'redirect_uri' => $this->_redirectUri,
            'client_id' => $this->_applicationId,
            'client_secret' => $this->_secret,
        ];

        $token = null;
        if ($response = $this->_call('https://www.linkedin.com/uas/oauth2/accessToken', $params)) {
            $token = json_decode($response, true);
        }
        $this->_setLog($token);

        if (isset($token['access_token'])) {
            $params = [
                'oauth2_access_token' => $token['access_token'],
                'format' => 'json',
            ];

            if ($response = $this->_call('https://api.linkedin.com/v1/people/~:('. implode(',', $this->_fieldsToResponse) .')', $params)) {
                $data = json_decode($response, true);
            }
            $this->_setLog($data, true);
        }
   
        if (!is_array($data)) {
            $data = [];
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

        return parent::_prepareData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getSocialUrl()
    {
        if ($id = $this->getUserId()) {
            return 'https://www.linkedin.com/in/' . $id;
        }
        return null;
    }
}
