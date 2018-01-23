/* Appel au chargement terminé de la page */
function pageLoad() {
    // Cookies
    var cookie_cookies = Idp.getCookie('cookies_accepted');
    var cookie_notice = document.getElementById('cookie-notice');
    var cookie_close = document.getElementById('cookie-notice-close');
    if (cookie_cookies == "") {
        // Affichage du bandeau de cookie
        cookie_notice.style.display = 'block';

        // Fermeture bandeau cookie "OK" 
        cookie_close.addEventListener('click', function (event) {
            // Mise en place du cookie
            Idp.setCookie('cookies_accepted', 1, 30);
            // Suppression ou hide du bandeau cookie
            if (!('remove' in Element.prototype)) {
                cookie_notice.style.display = 'none';
            } else {
                cookie_notice.remove();
            }
            event.preventDefault();
        }, false);
    } else {
        // Suppression ou hide du bandeau cookie
        if (!('remove' in Element.prototype)) {
            cookie_notice.style.display = 'none';
        } else {
            cookie_notice.remove();
        }
    }

    // Ajax post example
    //Idp.ajaxPost('/ajax', retour, { nom: 'durbecq', prenom: 'andy', data: 10, texte: 'c\'est la fête & - pour le fun' });
}

// Callback function for ajax example
/*function retour(datas) {
    console.log(datas)
}*/

/* Page loaded */
if (window.addEventListener) {
    window.addEventListener('load', pageLoad, false);
} else if (window.attachEvent) {
    window.attachEvent('onload', pageLoad);
} else {
    window.onload = pageLoad;
}