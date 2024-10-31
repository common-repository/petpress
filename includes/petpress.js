    jQuery(document).ready(function () {
        /*
            Lightbox effect for detail page
        */
        if (typeof cPhoto1 !== 'undefined') 
        {
            var images = [];

            if (cPhoto1.trim() !== '') {
                images.push(cPhoto1);
            }
            if (cPhoto2.trim() !== '') {
                images.push(cPhoto2);
            }
            if (cPhoto3.trim() !== '') {
                images.push(cPhoto3);
            }

            var currentIndex = 0;

            function showImage(index) {
                jQuery('#pp_lightboxImg').attr('src', images[index]);
                currentIndex = index;
            }

            showImage(currentIndex);

            jQuery('.pp_lightbox-trigger').click(function () {
                var clickedIndex = jQuery(this).attr('data-index');
                showImage(clickedIndex);
                jQuery('#pp_lightbox').fadeIn();
            });

            function closeLightbox() {
                jQuery('#pp_lightbox').fadeOut();
            }

            jQuery('#pp_lightbox, #pp_lightboxCloseBtn').click(function (e) {
                if (e.target.id === 'pp_lightbox' || e.target.id === 'pp_lightboxCloseBtn') {
                    closeLightbox();
                }
            });

            jQuery('#pp_lightboxPrevBtn').click(function (e) {
                e.stopPropagation();
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                showImage(currentIndex);
            });

            jQuery('#pp_lightboxNextBtn').click(function (e) {
                e.stopPropagation();
                currentIndex = (currentIndex + 1) % images.length;
                showImage(currentIndex);
            });

            jQuery('#pp_lightboxImg').click(function (e) {
                e.stopPropagation();
            });
        }
    });
