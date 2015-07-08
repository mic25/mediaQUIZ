var POIs = [];

$(document).ready(function(){
    init();
});

function init () {
    /* retrieve json data from server */
    $.getJSON("../data/crowdsourcing.json", function (data) {
    //$.get("../getPOIs.php", function (data) {
        POIs = data;
        createList();
    });
}

function createList () {
    for(var i = 0; i < POIs.length; ++i) {
        var line = $("<div class='line'><div class='name'>"+POIs[i].name+"</div><div class='videos'>"+POIs[i].videos+"</div></div>");
        if(POIs[i].videos == 0){
            line.addClass("no-videos");
        }
        $("#crowdsourcingTable").append(line);
    }
}
