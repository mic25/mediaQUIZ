$(document).ready(function () {
    var sections = $(".section");
    var toggles = $(".toggleAbout");
    var motivation = $("#motivation");
    var problem = $("#problem");
    var algorithm = $("#algorithm");
    var technical = $("#technical");

    toggles.removeClass("active");
    $("#toggleMotivation").addClass("active");
    sections.hide();
    motivation.show();

    $("#toggleMotivation").click(function(){
        toggles.removeClass("active");
        $(this).addClass("active");
        sections.hide();
        motivation.show();
    });

    $("#toggleProblem").click(function(){
        toggles.removeClass("active");
        $(this).addClass("active");
        sections.hide();
        problem.show();
    });

    $("#toggleAlgorithm").click(function(){
        toggles.removeClass("active");
        $(this).addClass("active");
        sections.hide();
        algorithm.show();
    });

    $("#toggleTechnical").click(function(){
        toggles.removeClass("active");
        $(this).addClass("active");
        sections.hide();
        technical.show();
    });
});