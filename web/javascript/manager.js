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

    //----- Crop et aperçu -----//    
    var upload_file_image_input = document.querySelector('.upload-file-image-input'); // Input for select file image
    // Chek if image_crop_apercu_input exist
    if (upload_file_image_input) {
        // Poster area of the image
        var upload_file_image_zone = document.querySelector('.image-crop-apercu-zone');

        // Cropper
        var cropper = null;

        // Data for final
        var upload_file_image_width  = upload_file_image_input.getAttribute('data-width');
        var upload_file_image_height = upload_file_image_input.getAttribute('data-height');
        var upload_file_image_ration = upload_file_image_width/upload_file_image_height;

        var imageType = /^image\//;

        var file = null;

        // Event change for the input file
        upload_file_image_input.addEventListener('change', function(event) {             
            // Reset zone       
            upload_file_image_zone.innerHTML = "";
            // Check files exist
            if (event.target.files.length) {
                file = event.target.files[0];                

                // Check type of file -> image
                if (!imageType.test(file.type)) {
                    Idp.messageTop("Merci de renseigner une image !", 'error');   
                } else {
                    // Check max file size
                    if (file.size <= 4194304) {                                                                                            

                        var reader = new FileReader();
                        reader.addEventListener('load', function() {                            

                            var upload_file_image_img = document.createElement('img');                             
                            upload_file_image_img.addEventListener('load', function(){
                                
                                // Check width and height
                                if (upload_file_image_img.width > upload_file_image_width && upload_file_image_img.height > upload_file_image_height) {
                                    upload_file_image_zone.appendChild(upload_file_image_img);
                                    
                                    var save_x= save_y = save_w = save_h = 0;

                                    cropper = new Cropper(upload_file_image_img, {
                                        movable: false,
                                        rotatable: false,
                                        scalable: false,
                                        zoomable: false,
                                        zoomOnTouch: false,
                                        zoomOnWheel: false,
                                        responsive: false,
                                        autoCropArea: 1,
                                        aspectRatio: upload_file_image_ration,
                                        toggleDragModeOnDblclick: false,
                                        dragMode: 'none',
                                        crop: function(e) {     
                                            if (e.detail.width < upload_file_image_width || e.detail.height < upload_file_image_height) {
                                                cropper.setCropBoxData({"left":save_x,"top":save_y,"width":save_w,"height":save_h});                                                
                                            } else {
                                                var cropboxdata = cropper.getCropBoxData();
                                                save_x = cropboxdata.left;
                                                save_y = cropboxdata.top;   
                                                save_w = cropboxdata.width;
                                                save_h = cropboxdata.height;                                 
                                                document.querySelector('.upload-file-image-input-x').value = e.detail.x;
                                                document.querySelector('.upload-file-image-input-y').value = e.detail.y;
                                                document.querySelector('.upload-file-image-input-w').value = e.detail.width;
                                                document.querySelector('.upload-file-image-input-h').value = e.detail.height;   
                                            }                                                                                
                                        }
                                    });
                                } else {
                                    Idp.messageTop('Merci de renseigner une image ayant des dimensions supérieurs à : ' + upload_file_image_width + 'x' + upload_file_image_height, 'error');   
                                }

                            }, false);
                            upload_file_image_img.src = this.result;                                                        
                           
                        }, false);
                        reader.readAsDataURL(file); 

                    } else {
                        Idp.messageTop('Taille du fichier supérieur à 4 Mo !', 'error');
                    }    
                }
            }
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