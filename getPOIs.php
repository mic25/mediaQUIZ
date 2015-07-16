<?php

include_once('db_info.php');

$link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$response = [];
$htmlResponse = "";

$json = file_get_contents('data/POI.json');
$json_obj = json_decode($json, true);
$results = $json_obj['results'] ? $json_obj['results'] : $json_obj;
$resultLength = count($results);
for($i = 0; $i < $resultLength; ++$i){
    $lat = $results[$i][geometry][location][lat];
    $lng = $results[$i][geometry][location][lng];
    $availableAnswers = [];
    $answers = [];
    $wikiPageId = "";
    $query = "SELECT *
              FROM VIDEO_METADATA
              WHERE SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2)) < 0.0014
                AND (DEGREES(ACOS((Plng-".$lng.")/SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2))))-ThetaX) < 51
                AND (DEGREES(ACOS((Plng-".$lng.")/SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2))))-ThetaX) > 0";

    $id = $results[$i][place_id];
    $details = file_get_contents("data/".$id.".json");
    $details_obj = json_decode($details, true);
    $name = $details_obj[result][name];
    $types = $details_obj[result][types];

    if ($result = mysqli_query($link, $query)) {
        $value = array(
            "name" => $name,
            "videos" => mysqli_num_rows($result),
            "lat" => $lat,
            "lng" => $lng
        );
        array_push($response, $value);
    }
}

echo json_encode($response);
mysqli_close($link);

?>