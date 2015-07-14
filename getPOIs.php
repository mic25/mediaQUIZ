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
$results = $json_obj['results'];
/* sort POIs by latitude */
usort($results, function($a, $b){
    if($a["geometry"]["location"]["lat"] == $b["geometry"]["location"]["lat"]){
        return 0;
    }
    return $a["geometry"]["location"]["lat"] < $b["geometry"]["location"]["lat"] ? -1 : 1;
});
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
    //echo $details;
    //echo "<br />";
    $details_obj = json_decode($details, true);
    $name = $details_obj[result][name];
    $types = $details_obj[result][types];

    if ($result = mysqli_query($link, $query)) {
        //$value[name] = $name;
        //$value[videos] = mysqli_num_rows($result);
        $value = "{\"name\": \"".$name."\", \"videos\": \"".mysqli_num_rows($result)."\", \"lat\": \"".$lat."\", \"lng\": \"".$lng."\"}";
        array_push($response, $value);
        $htmlResponse .= "<div class='line'><div class='name'>".$name."</div><div class='videos'>".mysqli_num_rows($result)."</div></div>";
        /*
        if(mysqli_num_rows($result) > 0){
            //echo $name; echo " - "; echo mysqli_num_rows($result); echo " videos found."; echo "<br />";
        }else{
            echo "-> "; echo $name; echo " - no videos!"; echo "<br />";
        }
        */
    }
}

echo "[".implode(", ",$response)."]";
mysqli_close($link);
//echo $htmlResponse;

?>