function pageLoad() {
    var datepickers = document.querySelectorAll('.datepicker');
    if (datepickers.length) {
        flatpickr(".datepicker", {
            dateFormat: 'd/m/Y',
            locale: 'fr'
        });
    }

    var sortable_c = document.querySelectorAll('.sortable');

    if (sortable_c.length) {        
        sortable('.sortable', {
            forcePlaceholderSize: true
        });
            
        var loader      = document.getElementById('loader-ordre');        
        sortable('.sortable')[0].addEventListener('sortstop', function(e) {
            var ordre = 1;   
            var sortable_li = document.querySelectorAll('.sortable li');
            loader.className = "show";

            for (var i = 0 ; i < sortable_li.length; i++) {
                id_post_image = sortable_li[i].getAttribute('data-id');

                Idp.ajaxPost('/manager/posts/ordre-image', function(){}, { id_post_image: id_post_image, ordre: ordre});
                
                ordre++;
            }
            loader.className = "";
        }, false);
    }
}   

if (window.addEventListener) {
    window.addEventListener('load', pageLoad, false);
} else if (window.attachEvent) {
    window.attachEvent('onload', pageLoad);
} else {
    window.onload = pageLoad;
}