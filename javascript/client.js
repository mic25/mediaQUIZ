/**
 * Created by Manuel on 04.06.2015.
 */

var QUESTIONS = 10;         // total number of questions

var rightAnswer = -1;       // index of currently right answer
var questions;              // array of questions
var progress = 0;
var score = 0;

$(document).ready(function () {
    // load progress bar
    initProgress(QUESTIONS);

    /* add click listener */
    $(".choice").click(function () {
        // only register inputs if buttons are enabled
        if (!$(".choice").prop("disabled")) {
            validate($(this).attr("id"));
        }
    });

    /* retrieve json data from server */
    $.getJSON("../getQuestions.php", function (data) {
        questions = data;
        startGame();
    });

    /* replay video on click */
    $("video").click(function () {
        var video = document.getElementById("videoTag");
        video.pause();
        video.currentTime = '0';
        video.play();
    });
});

/**
 * Resets counters and starts the question-chain
 */
function startGame() {
    progress = 0;
    score = 0;
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
        $("#choice" + i).text(answers[i]);
    }
    rightAnswer = entry.correctAnswer;

    /* display buttons */
    animateButtons(true);

    /* load and play new video file */
    $("video").fadeOut(function () {
        $("#videosrc").attr("src", entry.video);
        $("video").fadeIn().load();
        $("#loading").hide();
        $("#clickNote").show();
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
        updateProgress(true);
    } else {
        updateProgress(false);
    }

    if (progress == (QUESTIONS - 1)) {
        /* end game when all questions are answered */
        /* TODO: Result Screen */
        alert("Score: " + score + "/" + QUESTIONS);
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
    html += "<img src='styles/img/client_progress-question.png' class='progressBar' id='progressBar1'>";
    for (var i = 2; i <= length; i++) {
        html += "<img src='styles/img/client_progress-path.png' class='progressBar'>";
        html += "<img src='styles/img/client_progress-podest.png' class='progressBar' id='progressBar" + i + "'>";
    }

    $("footer").html(html);
}

function updateProgress(right) {
    $("#progressBar" + (progress + 2)).attr("src", "styles/img/client_progress-question.png");
    if (right) {
        $("#progressBar" + (progress + 1)).attr("src", "styles/img/client_progress-check.png");
    } else {
        $("#progressBar" + (progress + 1)).attr("src", "styles/img/client_progress-cross.png");
    }
}