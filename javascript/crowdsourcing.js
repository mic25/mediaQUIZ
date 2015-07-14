var POIs = [];
var gmarkers = [];

$(document).ready(function(){
    init();
});

function init () {
    /* retrieve json data from server */
    $.getJSON("../data/crowdsourcing.json", function (data) {
    //$.get("../getPOIs.php", function (data) {
        POIs = data;
        createList();
        createMap();
    });
}

function createList () {
    for(var i = 0; i < POIs.length; ++i) {
        var name = POIs[i].name;
        var line = $("<div class='line'><a href='javascript:triggerClick("+i+")'><div class='name'>"+POIs[i].name+"</div><div class='videos'>"+POIs[i].videos+"</div></a></div><div style='clear:both;'></div>");
        if(POIs[i].videos == 0){
            line.addClass("no-videos");
        }
        $("#crowdsourcingTable").append(line);
    }
}

function createMap () {
    var mapOptions = {
        center: {
            lat: 48.1502989,
            lng: 11.5807205
        },
        zoom: 13
    };
    var map = new google.maps.Map(document.getElementById('map-canvas'),
        mapOptions);

    for(var i = 0; i < POIs.length; ++i){
        var name = POIs[i].name;
        var videos = POIs[i].videos;
        var lat = POIs[i].lat;
        var lng = POIs[i].lng;
        var marker;
        var infowindowtext = "<h4>" +name+ "</h4><h5>"+videos+" videos</h5>";
        var infowindow =  new google.maps.InfoWindow({
            content: ""
        });
        if(videos == 0){
            marker = new google.maps.Marker({
                position: new google.maps.LatLng(parseFloat(lat),parseFloat(lng)),
                map: map,
                title: name,
                icon:  "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
            });
        }else{
            marker = new google.maps.Marker({
                position: new google.maps.LatLng(parseFloat(lat),parseFloat(lng)),
                map: map,
                title: name,
                icon: "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
            });
        }
        gmarkers.push(marker);
        bindInfoWindow(marker, map, infowindow, infowindowtext);
    }
    console.log(gmarkers);
}

function bindInfoWindow(marker, map, infowindow, description) {
    google.maps.event.addListener(marker, 'click', function() {
        infowindow.setContent(description);
        infowindow.open(map, marker);
    });
}

function triggerClick(i) {
    google.maps.event.trigger(gmarkers[i],"click");
}
