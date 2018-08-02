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

class Qq extends Account
{
    protected $_type = 'qq';
    
    protected $_responseType = 'code';
    protected $_url = 'https://graph.qq.com/oauth2.0/authorize';

    protected $_fields = [
                    'user_id' => 'openid',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'gender',
                    'photo' => 'figureurl_1',
                ];

    protected $_dob = ['year', 'month', 'day', '-'];
    protected $_gender = ['男', '女'];
                        /*Man 男人
                        Woman 女人
                        Male 男(性)
                        Female 女(性)*/

    protected $_buttonLinkParams = [
                    'display' => 'popup',
                ];

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
        if ($response = $this->_call('https://graph.qq.com/oauth2.0/token', $params)) {
            if (strpos($response, 'callback') !== false) {
                $lpos = strpos($response, '(');
                $rpos = strrpos($response, ')');
                $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                $token = json_decode($response, true);
            } else {
                parse_str($response, $token);
            }
        }
        $this->_setLog($token, true);


        if (isset($token['access_token'])) {
            // Me.
            $me = [];
            $params = [
                'access_token' => $token['access_token'],
            ];
    
            if ($response = $this->_call('https://graph.qq.com/oauth2.0/me', $params)) {
                if (strpos($response, 'callback') !== false) {
                    $lpos = strpos($response, '(');
                    $rpos = strrpos($response, ')');
                    $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                }

                $me = json_decode($response, true);
            }
            $this->_setLog($me, true);

            // User info.
            if (isset($me['openid'])) {
                $user = [];
                $params = [
                    'access_token' => $token['access_token'],
                    'openid' => $me['openid'],
                    'oauth_consumer_key' => $this->_applicationId,
                ];
        
                if ($response = $this->_call('https://graph.qq.com/user/get_user_info', $params)) {
                    if (strpos($response, 'callback') !== false) {
                        $lpos = strpos($response, '(');
                        $rpos = strrpos($response, ')');
                        $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                    }

                    $user = json_decode($response, true);
                }
                $this->_setLog($user, true);

                // Data.
                $me = is_array($me)? $me : [];
                $user = is_array($user)? $user : [];
                $data = array_merge($me, $user);
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
        if (empty($data['openid'])) {
            return false;
        }

        // Name.
        if (!empty($data['nickname'])) {
            $nameParts = explode(' ', $data['nickname'], 2);
            $data['name_fn'] = $nameParts[0];
            $data['name_ln'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_prepareData($data);
    }
}
