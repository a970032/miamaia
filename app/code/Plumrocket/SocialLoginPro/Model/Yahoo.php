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

class Yahoo extends Account
{
    protected $_type = 'yahoo';

    const URL_REQUEST_TOKEN = 'https://api.login.yahoo.com/oauth/v2/get_request_token';
    const URL_AUTHORIZE = 'https://api.login.yahoo.com/oauth/v2/request_auth';
    const URL_ACCESS_TOKEN = 'https://api.login.yahoo.com/oauth/v2/get_token';
    const URL_ACCOUNT_DATA = 'https://query.yahooapis.com/v1/yql';

    protected $_responseType = ['oauth_token', 'oauth_verifier'];

    protected $_fields = [
                    'user_id' => 'guid',
                    'firstname' => 'givenName',
                    'lastname' => 'familyName',
                    'email' => 'email',
                    'dob' => 'birthdate', // empty
                    'gender' => 'gender',
                    'photo' => 'photoUrl',
                ];

    protected $_dob = ['year', 'month', 'day', '/'];
    protected $_gender = ['M', 'F'];

    protected $_buttonLinkParams = null;

    protected $_popupSize = [650, 500];

    public function _construct()
    {
        parent::_construct();
    }

    public function getProviderLink()
    {
        $token = $this->_getStartToken();
        if (!empty($token['oauth_token'])) {
            $this->_buttonLinkParams = self::URL_AUTHORIZE .'?oauth_token='. $token['oauth_token'];
        }
        return parent::getProviderLink();
    }

    // Step 2.
    public function loadUserData($response)
    {
        if (empty($response['oauth_token']) || empty($response['oauth_verifier'])) {
            return false;
        }
        
        $data = [];
        $session = $this->_objectManager->get('Magento\Customer\Model\Session');

        $oauth_nonce = md5(uniqid(rand(), true));
        $oauth_timestamp = time();

        $oauth_token = $response['oauth_token'];
        $oauth_verifier = $response['oauth_verifier'];
        $oauth_token_secret = $session->getData($this->_type .'_oauth_token_secret');

        $oauth_base_text = "GET&";
        $oauth_base_text .= urlencode(self::URL_ACCESS_TOKEN)."&";
        $oauth_base_text .= urlencode("oauth_consumer_key=".$this->_applicationId."&");
        $oauth_base_text .= urlencode("oauth_nonce=".$oauth_nonce."&");
        $oauth_base_text .= urlencode("oauth_signature_method=HMAC-SHA1&");
        $oauth_base_text .= urlencode("oauth_timestamp=".$oauth_timestamp."&");
        $oauth_base_text .= urlencode("oauth_token=".$oauth_token."&");
        $oauth_base_text .= urlencode("oauth_verifier=".$oauth_verifier."&");
        $oauth_base_text .= urlencode("oauth_version=1.0");

        $key = $this->_secret .'&'. $oauth_token_secret;
        $oauth_signature = base64_encode(hash_hmac('sha1', $oauth_base_text, $key, true));

        $url = self::URL_ACCESS_TOKEN;
        $url .= '?oauth_consumer_key='.$this->_applicationId;
        $url .= '&oauth_nonce='.$oauth_nonce;
        $url .= '&oauth_signature_method=HMAC-SHA1';
        $url .= '&oauth_timestamp='.$oauth_timestamp;
        $url .= '&oauth_version=1.0';
        $url .= '&oauth_token='.urlencode($oauth_token);
        $url .= '&oauth_verifier='.urlencode($oauth_verifier);
        $url .= '&oauth_signature='.urlencode($oauth_signature);

        if ($response = $this->_callGET($url)) {
            parse_str($response, $result);
        } else {
            return;
        }
        $this->_setLog($result, true);

        // Get user data.
        if (!empty($result['oauth_token']) && !empty($result['oauth_token_secret'])) {
            $oauth_nonce = md5(uniqid(rand(), true));
            $oauth_timestamp = time();

            $oauth_token = $result['oauth_token'];
            $oauth_token_secret = $result['oauth_token_secret'];

            $params = [
                'format' => 'json',
                'oauth_consumer_key' => $this->_applicationId,
                'oauth_nonce' => $oauth_nonce,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => $oauth_timestamp,
                'oauth_token' => $oauth_token,
                'oauth_version' => '1.0',
                'q' => 'select * from social.profile where guid=me',
            ];

            $paramsStr = 'GET&'. $this->_encode(self::URL_ACCOUNT_DATA) .'&';
            foreach ($params as $key => $value) {
                $paramsStr .= $this->_encode((isset($amp)?'&':''). $key .'='. $this->_encode($value));
                $amp = true;
            }

            $key = $this->_secret .'&'. $oauth_token_secret;
            $signature = base64_encode(hash_hmac("sha1", $paramsStr, $key, true));
            
            $url = self::URL_ACCOUNT_DATA;
            $url .= '?q=' . urlencode('select * from social.profile where guid=me');
            $url .= '&format=json';
            $url .= '&oauth_consumer_key=' . $this->_applicationId;
            $url .= '&oauth_nonce=' . $oauth_nonce;
            $url .= '&oauth_signature_method=HMAC-SHA1';
            $url .= '&oauth_timestamp=' . $oauth_timestamp;
            $url .= '&oauth_version=1.0';
            $url .= '&oauth_token=' . urlencode($oauth_token);
            $url .= '&oauth_signature=' . urlencode($signature);

            if ($response = $this->_callGet($url)) {
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

    protected function _encode($value)
    {
        return str_replace('%7E', '~', str_replace('+', ' ', rawurlencode($value)));
    }

    // Step 1.
    protected function _getStartToken()
    {
        $oauth_nonce = md5(uniqid(rand(), true));
        $oauth_timestamp = time();

        $oauth_base_text = "GET&";
        $oauth_base_text .= urlencode(self::URL_REQUEST_TOKEN)."&";
        $oauth_base_text .= urlencode("oauth_callback=".urlencode($this->_redirectUri)."&");
        $oauth_base_text .= urlencode("oauth_consumer_key=".$this->_applicationId."&");
        $oauth_base_text .= urlencode("oauth_nonce=".$oauth_nonce."&");
        $oauth_base_text .= urlencode("oauth_signature_method=HMAC-SHA1&");
        $oauth_base_text .= urlencode("oauth_timestamp=".$oauth_timestamp."&");
        $oauth_base_text .= urlencode("oauth_version=1.0");

        $key = $this->_secret."&";
        $oauth_signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));

        $url = self::URL_REQUEST_TOKEN;
        $url .= '?oauth_callback='.urlencode($this->_redirectUri);
        $url .= '&oauth_consumer_key='.$this->_applicationId;
        $url .= '&oauth_nonce='.$oauth_nonce;
        $url .= '&oauth_signature='.urlencode($oauth_signature);
        $url .= '&oauth_signature_method=HMAC-SHA1';
        $url .= '&oauth_timestamp='.$oauth_timestamp;
        $url .= '&oauth_version=1.0';

        if ($response = $this->_callGet($url)) {
            parse_str($response, $result);
        }

        if (!empty($result['oauth_token_secret'])) {
            $session = $this->_objectManager->get('Magento\Customer\Model\Session');
            $session->setData($this->_type .'_oauth_token_secret', $result['oauth_token_secret']);
        }

        $this->_setLog($result, true);

        return $result;
    }

    protected function _prepareData($data)
    {
        if (empty($data['query']['results']['profile'])) {
            return false;
        }

        $data = $data['query']['results']['profile'];

        // Email.
        if (!empty($data['emails'][0]['handle'])) {
            $data['email'] = $data['emails'][0]['handle'];
        } elseif (!empty($data['emails']['handle'])) {
            $data['email'] = $data['emails']['handle'];
        }

        // Birthdate.
        if (empty($data['birthdate']) &&
            !empty($data['year']) && !empty($data['month']) && !empty($data['day'])) {
            $data['birthdate'] = $data['year'] .'/'. $data['month'] .'/'. $data['day'];
        } elseif (!empty($data['birthdate']) && !empty($data['birthYear'])) {
            $data['birthdate'] = $data['birthYear'] .'/'. $data['birthdate'];
        }

        // Photo.
        if (!empty($data['image']['imageUrl'])) {
            $data['photoUrl'] = $data['image']['imageUrl'];
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
