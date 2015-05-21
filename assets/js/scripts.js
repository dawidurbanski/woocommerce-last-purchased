jQuery(document).ready(function($) {
    $("time.timeago").timeago();
    $(".wlp-popup").delay( 1000 ).fadeIn( 1200 );
    $(".wlp-popup-close").click(function() {
        $(".wlp-popup").fadeOut( 200 );
    });
    setTimeout(function(){
        $(".wlp-popup").fadeOut( 1200 );
    }, 4000);
});
