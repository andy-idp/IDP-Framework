(function (root, factory) {

    if (typeof define === 'function' && define.amd) {
        define(factory);
    } else if (typeof exports === 'object') {
        module.exports = factory();
    } else {
        root.Idp = factory();
    }

})(this, function () {
    'use strict';

    var Idp = {
        // Affecter un cookie
        setCookie: function (cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = 'expires=' + d.toUTCString();
            document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
        },

        // Récupérer un cookie
        getCookie: function (cname) {
            var name = cname + '=';
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        },

        // Message popup top
        timeMessageTop: null,
        message_top: document.querySelector('#message-top'),
        message_top_div: document.querySelector('#message-top div'),
        message_top_duration: 4000,
        messageTop: function (texte, id) {
            if (typeof (this.timeMessageTop) != 'undefined') {
                clearTimeout(this.timeMessageTop);
            }

            this.message_top_div.className = id;
            this.message_top_div.innerHTML = texte;
            this.showMessageTop();
        },
        showMessageTop: function () {
            if (typeof (this.timeMessageTop) != 'undefined') {
                clearTimeout(this.timeMessageTop);
            }

            this.message_top.className = 'show';
            var self_message_top_div   = this.message_top_div;
            var self_message_top       = this.message_top;
            this.timeMessageTop        = setTimeout(function () {
                self_message_top_div.className = self_message_top.className = '';
                self_message_top_div.innerHTML = '';
            }, this.message_top_duration);
        },

        // Ajax
        ajaxPost: function (url, callback, datas) {
            if (window.XMLHttpRequest) { // Mozilla, Safari, ...
                var xhr = new XMLHttpRequest();
            }
            else if (window.ActiveXObject) { // IE
                var xhr = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xhr.open("POST", url, true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == XMLHttpRequest.DONE && xhr.status == 200) {
                    callback(JSON.parse(xhr.responseText));
                }
            }

            var final_datas = "";
            var first_datas = true;
            if (typeof datas === 'object') {
                for (var key in datas) {
                    if (!first_datas) {
                        final_datas += "&";
                    }
                    final_datas += encodeURIComponent(key) + '=' + encodeURIComponent(datas[key]);
                    first_datas = false;
                }
            }

            xhr.send(final_datas);
        }
    }

    // Check the report/error message to show
    if (Idp.message_top_div.className == 'error' || Idp.message_top_div.className == 'report') {
        Idp.showMessageTop();        
    }

    return Idp;
});