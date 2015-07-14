<?php

$json = file_get_contents('https://maps.googleapis.com/maps/api/place/radarsearch/json?location=48.1549108,11.5418358&radius=500000&types=(airport|amusement_park|aquarium|art_gallery|cemetery|city_hall|embassy|hospital|library|movie_theater|museum|night_club|park|place_of_worship|police|shopping_mall|stadium|train_station|university|zoo)&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');

$jsonfile = fopen("data/POI.json", "w") or die("Unable to open file!");
$json_obj = json_decode($json, true);
$results = $json_obj['results'];
/* sort POIs by latitude */
usort($results, function($a, $b){
    if($a["geometry"]["location"]["lat"] == $b["geometry"]["location"]["lat"]){
        return 0;
    }
    return $a["geometry"]["location"]["lat"] < $b["geometry"]["location"]["lat"] ? -1 : 1;
});
$jsonNew = json_encode($results);
fwrite($jsonfile, $jsonNew);
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

ob_start();
include 'getQuestions.php';
$questions = ob_get_clean();

$questionsfile = fopen("data/questions.json", "w") or die("Unable to open file!");
fwrite($questionsfile, $questions);
fclose($questionsfile);

echo "wrote JSON To File <br />";

ob_start();
include 'getPOIs.php';
$crowdsourcing = ob_get_clean();

$crowdsourcingfile = fopen("data/crowdsourcing.json", "w") or die("Unable to open file!");
fwrite($crowdsourcingfile, $crowdsourcing);
fclose($crowdsourcingfile);

echo "wrote crowdsourcing To File <br />";

?>