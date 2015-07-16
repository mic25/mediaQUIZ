$(document).ready(function () {
    $("#toggleMenu").click(function () {
        if($(".nav.mobile").is(":visible")){
            $(".nav.mobile").slideUp();
        }else{
            $(".nav.mobile").slideDown();
        }
    });
});