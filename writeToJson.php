<?php

$json = file_get_contents('https://maps.googleapis.com/maps/api/place/radarsearch/json?location=48.1549108,11.5418358&radius=500000&types=(airport|amusement_park|aquarium|art_gallery|cemetery|city_hall|embassy|hospital|library|movie_theater|museum|night_club|park|place_of_worship|police|shopping_mall|stadium|train_station|university|zoo)&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');

$jsonfile = fopen("data/POI.json", "w") or die("Unable to open file!");
fwrite($jsonfile, $json);
fclose($jsonfile);

echo "wrote JSON To File <br />";

$json_obj = json_decode($json, true);
for($i = 0; $i < count($json_obj['results']); $i++){
    $id = $json_obj[results][$i][place_id];
    $details = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$id.'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');

    $detailfile = fopen("data/".$id.".json", "w") or die("Unable to open file!");
    fwrite($detailfile, $details);
    fclose($detailfile);
    echo "wrote Details To File <br />";
}

$questions = file_get_contents("getQuestions.php");

$questionsfile = fopen("data/questions.json", "w") or die("Unable to open file!");
fwrite($questionsfile, $questions);
fclose($questionsfile);

echo "wrote JSON To File <br />";

$crowdsourcing = file_get_contents("getPOIs.php");

$crowdsourcingfile = fopen("data/crowdsourcing.json", "w") or die("Unable to open file!");
fwrite($crowdsourcingfile, $crowdsourcing);
fclose($crowdsourcingfile);

echo "wrote crowdsourcing To File <br />";

?>