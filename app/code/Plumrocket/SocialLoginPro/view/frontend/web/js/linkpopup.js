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

define([
    'jquery',
    "underscore",
    'uiComponent',
    'ko',
    'mage/translate',
], function ($) {
    "use strict";

    return function (options) {
        var showPopup = $.cookieStorage.get('pslogin_show_linkpopup'),
            currentPage = document.location.href;

        if (showPopup && currentPage.search('pslogin/account') < 0 && currentPage.search('checkout') < 0) {
            $.cookieStorage.set('pslogin_show_linkpopup', 0);
            $.ajax({
                url: options.ajaxUrl,
                dataType: "json"
            }).success(function (response) {
                if (!response.html) {
                    return;
                }
                var hld = $('#pslogin-linkpopup-init');
                $('html').css('overflow', 'hidden');
                hld.html(response.html);
                $('.prpop-close-btn').click(function () {
                    hld.hide();
                    $('html').css('overflow', 'auto');
                });
            });
        }
    };

});