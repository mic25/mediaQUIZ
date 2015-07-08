/**
 * Created by Manuel on 04.06.2015.
 */

var QUESTIONS = 10;         // total number of questions

var rightAnswer = -1;       // index of currently right answer
var questions;              // array of questions
var progress = 0;
var score = 0;
var scorePoints = 0;

var answerTime = 0;
var answerStartTime = 0;
var answerEndTime = 0;

var highScore = 0;

var clipStartTime;
var lat, lng, name;

$(document).ready(function () {
    /* add click listener */
    $(".choice").click(function () {
        // only register inputs if buttons are enabled
        if (!$(".choice").prop("disabled")) {
            validate($(this).attr("id"));
        }
    });

    /* init the game on page load */
    init();

    /* replay video on click */
    $("video").click(function () {
        var video = document.getElementById("videoTag");
        console.log(clipStartTime);
        if(clipStartTime){
            video.pause();
            video.currentTime = clipStartTime;
            video.play();
        }
    });

    /* resize answer texts to fit into buttons */
    $('.choice').textfill({ maxFontPixels: 14 });
    $(window).resize(function () {
        $('.choice').textfill({ maxFontPixels: 14 });
    });

    /* close overlay on click */
    var transparency = $('#greyOverlay');
    var overlay = $('#wikiOverlay');
    overlay.click(function(){
        if(!$(event.target).closest('#map-canvas').length){
            overlay.fadeOut();
            transparency.fadeOut();
        }
    });
    transparency.click(function(){
        overlay.fadeOut();
        transparency.fadeOut();
    });

    /* replay */
    $("#playAgain").click(function () {
        $("#finishedOverlay").fadeOut();
        init();
    });
});

/**
 * Init the game & get questions from server
 */
function init() {
    /* retrieve json data from server */
    $.getJSON("../data/questions.json", function (data) {
        questions = shuffleArray(data);
        //questions = data;
        if(questions.length < 10){
            QUESTIONS = questions.length;
        }
        // load progress bar
        initProgress(QUESTIONS);
        startGame();
    });
}

/**
 * Resets counters and starts the question-chain
 */
function startGame() {
    progress = 0;
    score = 0;
    scorePoints = 0;

    answerTime = 0;
    answerStartTime = 0;
    answerEndTime = 0;

    highScore = sessionStorage.mediaQuizHighScore;

    loadQuestion(questions[progress]);
}

/**
 * Loads video and changes possible answers
 *
 * @param entry The index of the question to load
 */
function loadQuestion(entry) {
    $("#loading").show();
    $("#clickNote").hide();
    /* hide Buttons */
    animateButtons(false);
    /* load answers */

    var answers = entry.answers;
    for (var i = 0; i < answers.length; i++) {
        $("#choice" + i + " span").text(answers[i]);
        $('#choice' + i).textfill({ maxFontPixels: 14 });
    }
    rightAnswer = entry.correctAnswer;

    clipStartTime = entry.clipStartTime;

    /* display buttons */
    animateButtons(true);

    /* load and play new video file */
    $("video").fadeOut(function () {
        $("video source").attr("src", entry.video);
        $("video").fadeIn().load();
        $("video").on('loadeddata', function() {
            $("#loading").hide();
            $("#clickNote").show();
            answerStartTime = (new Date()).getTime();
        });
    });
}

/**
 * Checks if the current answer is correct and handles forwarding to the next step
 *
 * @param answer The id of the button that has been pressed
 */
function validate(answer) {
    /* skips validation if buttons are still being loaded */
    if (answer == ("choice" + rightAnswer)) {
        /* give credits for correct answer */
        score++;
        answerEndTime = (new Date()).getTime();
        answerTime = Math.round((answerEndTime - answerStartTime) / 1000);
        if(answerTime < 20){
            scorePoints += 20 - answerTime;
        }else{
            scorePoints += 1;
        }
        updateProgress(true);
    } else {
        updateProgress(false);
    }

    if (progress == (QUESTIONS - 1)) {
        /* end game when all questions are answered */
        /* TODO: Result Screen */
        $("#finishedOverlay span#correct").text(score);
        $("#finishedOverlay span#count").text(QUESTIONS);
        $("#finishedOverlay span#points").text(scorePoints);
        if(!highScore || highScore < scorePoints){
            //sessionStorage.setItem(("mediaQuizHighScore", scorePoints));
            sessionStorage.mediaQuizHighScore = scorePoints;
            $("#finishedOverlay span#highscore").text(scorePoints);
        }else{
            $("#finishedOverlay span#highscore").text(highScore);
        }
        $("#finishedOverlay").fadeIn();
        //alert("Score: " + score + "/" + QUESTIONS);
    } else {
        /* load next question */
        progress++;
        loadQuestion(questions[progress]);

        /* TODO: Show in GUI if answer is correct, forward through click */
    }
}

/**
 * Animates choice-buttons to move from or out the side of the window
 *
 * @param flyIn True if motion inwards, false if outwards
 */
function animateButtons(flyIn) {
    if (flyIn) {
        for (var i = 0; i < 3; i++) {
            $("#choice" + i).delay(i * 200).animate({left: "0"});
        }
        $("#choice3").delay(600).animate({left: "0"}, function () {
            // free input after last animation-part ends
            $(".choice").prop("disabled", false);
        });
    } else {
        // lock input during animation
        $(".choice").prop("disabled", true);

        for (var i = 0; i < 4; i++) {
            $("#choice" + i).delay(i * 50).animate({left: "100%"});
        }
    }
}

/**
 * Dynamically draw the initial progress bar to the footer
 * @param length The total amount of questions in this round
 */
function initProgress(length) {
    var html = "";
    html += "<img src='styles/img/client_progress-question.png' class='progressBar question' id='progressBar1'>";
    for (var i = 2; i <= length; i++) {
        html += "<img src='styles/img/client_progress-path.png' class='progressBar path'>";
        html += "<img src='styles/img/client_progress-podest.png' class='progressBar question' id='progressBar" + i + "'>";
    }

    $("footer").html(html);
}

function updateProgress(right) {
    $("#progressBar" + (progress + 2)).attr("src", "styles/img/client_progress-question.png");
    var current = $("#progressBar" + (progress + 1));
    if (right) {
        current.attr("src", "styles/img/client_progress-check.png");
    } else {
        current.attr("src", "styles/img/client_progress-cross.png");
    }
    current.addClass('done');

    $(".progressBar.question.done").click(function(){
        var index = $(this).attr('id').substr(11) - 1;
        questionId = questions[index].id;
        overlay = $('#wikiOverlay');
        if(overlay.is(':visible')){
            overlay.hide();
        }else{
            showWiki(questionId);
        }
    });
}

/**
 * Randomize array element order in-place.
 * Using Fisher-Yates shuffle algorithm.
 */
function shuffleArray(array) {
    for (var i = array.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
    return array;
}

/**
 * Resize text to fit into container.
 * @param options attribute maxFontPixels sets the maximum font-size
 * @returns {textfill}
 */
$.fn.textfill = function(options) {
    var fontSize = options.maxFontPixels;
    var ourText = $('span:visible:first', this);
    var maxHeight = $(this).height() - 2;
    var maxWidth = $(this).width() - 2;
    var textHeight;
    var textWidth;
    do {
        ourText.css('font-size', fontSize);
        textHeight = ourText.height();
        textWidth = ourText.width();
        fontSize = fontSize - 1;
    } while ((textHeight > maxHeight || textWidth > maxWidth) && fontSize > 3);
    return this;
};

/**
 * get wikipedia information and show in overlay
 * @param questionId the question to show the info for
 */
function showWiki(questionId){
    var wikiUrl = questions.filter(function (d) {
        return d.id === questionId;
    })[0].wiki;
    lat = questions.filter(function (d) {
        return d.id === questionId;
    })[0].lat;
    lng = questions.filter(function (d) {
        return d.id === questionId;
    })[0].lng;
    var correctAnswer = questions.filter(function (d) {
        return d.id === questionId;
    })[0].correctAnswer;
    name = questions.filter(function (d) {
        return d.id === questionId;
    })[0].answers[correctAnswer];
    if(wikiUrl){
        var decodedUrl = decodeURIComponent(wikiUrl);
        var urlParams = decodedUrl.slice(decodedUrl.indexOf('?') + 1).split('&');
        var params = [];
        for(var i = 0; i < urlParams.length; i++)
        {
            hash = urlParams[i].split('=');
            params.push(hash[0]);
            params[hash[0]] = hash[1];
        }
        var wikiID = params['pageid'];
        var wikiLink = "https://de.wikipedia.org/?curid=" + wikiID;
        $.ajax({
            url: decodedUrl,
            dataType: "jsonp",
            success: function (data) {
                var title = data.parse.title;
                var text = data.parse.text['*'];
                var transparency = $('#greyOverlay');
                var overlay = $('#wikiOverlay');
                overlay.find("h3").html(title);
                overlay.find("#text").html(text);
                /* add google map */
                loadGMScript();
                //overlay.html('<h3>'+title+'</h3><div>'+text+'</div><a target="_blank" href="' + wikiLink + '">Hier klicken zur Wikipedia-Seite</a>');
                transparency.fadeIn();
                overlay.fadeIn();
            }
        });
    }else{
        var transparency = $('#greyOverlay');
        var overlay = $('#wikiOverlay');
        overlay.find("h3").html("Keine Wikipedia-Infos gefunden");
        overlay.find("#text").html("Leider konnte zu dieser Location kein Artikel in Wikipedia gefunden werden.");
        //overlay.html('<h3>Keine Wikipedia-Infos gefunden</h3><div>Leider konnte zu dieser Location kein Artikel in Wikipedia gefunden werden.</div>');
        transparency.fadeIn();
        overlay.fadeIn();
    }
}

function initialize() {
    initializeMap(lat, lng);
}

function loadGMScript() {
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyDKt2zbA8qFXtyJ1kJlBwcVomFQx9aenZg&callback=initialize';
    document.body.appendChild(script);
}

/**
 * initializes a Google Map for given coordinates
 * @param lat
 * @param lng
 */
function initializeMap (lat, lng) {
    var mapOptions = {
        center: {
            lat: parseFloat(lat),
            lng: parseFloat(lng)
        },
        zoom: 14
    };
    var map = new google.maps.Map(document.getElementById('map-canvas'),
        mapOptions);

    var infowindow = new google.maps.InfoWindow({
        content: "<h4>" +name+ "</h4>"
    });

    var marker = new google.maps.Marker({
        position: new google.maps.LatLng(parseFloat(lat),parseFloat(lng)),
        map: map,
        title: name
    });
    google.maps.event.addListener(marker, 'click', function() {
        infowindow.open(map,marker);
    });
}

/**
 * get parameter value of url
 * @param name
 * @returns {string}
 */
function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}