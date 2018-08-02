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

class Goodreads extends Account
{
    protected $_type = 'goodreads';

    const URL_REQUEST_TOKEN = 'http://www.goodreads.com/oauth/request_token';
    const URL_AUTHORIZE = 'http://www.goodreads.com/oauth/authorize';
    const URL_ACCESS_TOKEN = 'http://www.goodreads.com/oauth/access_token';
    const URL_ACCOUNT_DATA = 'http://www.goodreads.com/api/auth_user';

    protected $_responseType = ['oauth_token', 'authorize'];

    protected $_fields = [
                    'user_id' => 'id',
                    'firstname' => 'name_fn',
                    'lastname' => 'name_ln',
                    'email' => 'email', // empty
                    'dob' => 'birthday', // empty
                    'gender' => 'gender',
                    'photo' => 'small_image_url',
                ];

    protected $_gender = ['male', 'female'];

    protected $_buttonLinkParams = null;

    protected $_popupSize = [820, 655];

    
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
        if (empty($response['oauth_token']) || empty($response['authorize'])) {
            return false;
        }
        
        $data = [];
        $session = $this->_objectManager->get('Magento\Customer\Model\Session');

        $oauth_nonce = md5(uniqid(rand(), true));
        $oauth_timestamp = time();

        $oauth_token = $response['oauth_token'];
        $oauth_verifier = $response['oauth_token'];
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

        if ($response = $this->_call($url)) {
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

            $oauth_base_text = "GET&";
            $oauth_base_text .= urlencode(self::URL_ACCOUNT_DATA).'&';
            $oauth_base_text .= urlencode('format=json&');
            $oauth_base_text .= urlencode('oauth_consumer_key='.$this->_applicationId.'&');
            $oauth_base_text .= urlencode('oauth_nonce='.$oauth_nonce.'&');
            $oauth_base_text .= urlencode('oauth_signature_method=HMAC-SHA1&');
            $oauth_base_text .= urlencode('oauth_timestamp='.$oauth_timestamp."&");
            $oauth_base_text .= urlencode('oauth_token='.$oauth_token."&");
            $oauth_base_text .= urlencode('oauth_version=1.0');

            $key = $this->_secret .'&'. $oauth_token_secret;
            $signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));

            $url = self::URL_ACCOUNT_DATA;
            $url .= '?format=json';
            $url .= '&oauth_consumer_key=' . $this->_applicationId;
            $url .= '&oauth_nonce=' . $oauth_nonce;
            $url .= '&oauth_signature=' . urlencode($signature);
            $url .= '&oauth_signature_method=HMAC-SHA1';
            $url .= '&oauth_timestamp=' . $oauth_timestamp;
            $url .= '&oauth_token=' . urlencode($oauth_token);
            $url .= '&oauth_version=1.0';

            try {
                if ($response = $this->_call($url/*, $params*/)) {
                    $xml = new \Zend_Config_Xml($response);
                    $user = $xml->toArray();
                    $this->_setLog($user, true);

                    if (!empty($user['user']['id'])) {
                        /*$oauth_base_text = "GET&";
                        $oauth_base_text .= urlencode('http://www.goodreads.com/user/show/'. $user['user']['id'] .'.xml').'&';
                        $oauth_base_text .= urlencode('format=xml&');
                        $oauth_base_text .= urlencode('oauth_consumer_key='.$this->_applicationId.'&');
                        $oauth_base_text .= urlencode('oauth_nonce='.$oauth_nonce.'&');
                        $oauth_base_text .= urlencode('oauth_signature_method=HMAC-SHA1&');
                        $oauth_base_text .= urlencode('oauth_timestamp='.$oauth_timestamp."&");
                        $oauth_base_text .= urlencode('oauth_token='.$oauth_token."&");
                        $oauth_base_text .= urlencode('oauth_version=1.0');

                        $key = $this->_secret .'&'. $oauth_token_secret;
                        $signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));*/

                        $url = 'http://www.goodreads.com/user/show/'. $user['user']['id'] .'.xml';
                        $url .= '?format=xml';
                        $url .= '&oauth_consumer_key=' . $this->_applicationId;
                        $url .= '&oauth_nonce=' . $oauth_nonce;
                        $url .= '&oauth_signature=' . urlencode($signature);
                        $url .= '&oauth_signature_method=HMAC-SHA1';
                        $url .= '&oauth_timestamp=' . $oauth_timestamp;
                        $url .= '&oauth_token=' . urlencode($oauth_token);
                        $url .= '&oauth_version=1.0';

                        if ($response = $this->_call($url/*, $params*/)) {
                            $xml = new \Zend_Config_Xml($response);
                            $data = $xml->toArray();
                        }
                    }
                }
            } catch (\Exception $e) {
            }
            
            $this->_setLog($data, true);
        }

        $data = is_array($data)? array_merge($result, $data) : $result;
        
        if (!$this->_userData = $this->_prepareData($data)) {
            return false;
        }

        $this->_setLog($this->_userData, true);

        return true;
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

        if ($response = $this->_call($url)) {
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
        if (empty($data['user']['id'])) {
            return false;
        }

        $data = $data['user'];

        // Name.
        if (!empty($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $data['name_fn'] = ucfirst($nameParts[0]);
            $data['name_ln'] = !empty($nameParts[1])? ucfirst($nameParts[1]) : '';
        }

        return parent::_prepareData($data);
    }
}
