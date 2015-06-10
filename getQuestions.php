<?php

include_once('db_info.php');

$link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$questionId = 0;
$responseArray = [];

/* web service */
//$json = file_get_contents('https://maps.googleapis.com/maps/api/place/radarsearch/json?location=48.1549108,11.5418358&radius=500000&types=(airport|amusement_park|aquarium|art_gallery|bar|cafe|casino|cemetery|city_hall|embassy|establishment|hospital|library|movie_theater|museum|night_club|park|place_of_worship|police|restaurant|school|shopping_mall|stadium|train_station|university|zoo)&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
/* json on our server */
$json = file_get_contents('POI.json');
//echo $json;
$json_obj = json_decode($json, true);
for($i = 0; $i < count($json_obj['results']); $i++){
    $lat = $json_obj[results][$i][geometry][location][lat];
    $lng = $json_obj[results][$i][geometry][location][lng];
    $availableAnswers = [];
    $answers = [];
    //echo "".$lat.", ".$lng."<br />";
    $query = "SELECT * FROM VIDEO_METADATA WHERE SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2)) < 0.001 AND (DEGREES(ACOS((Plng-".$lng.")/SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2))))-ThetaX) < 51 AND (DEGREES(ACOS((Plng-".$lng.")/SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2))))-ThetaX) > 0";
    //echo $query."<br />";


    /* Select queries return a resultset */
    if ($result = mysqli_query($link, $query)) {
        //echo "Query successful. Number of results: ".mysqli_num_rows($result)."<br />";
        if(mysqli_num_rows($result) > 0){
            $rows = [];
            $videoFrames = [];
            $videos = [];

            while($row = mysqli_fetch_array($result)) {
                array_push($rows, $row);
                /*
                array_push($videos, $row['VideoId']);
                $select = $row['VideoId'] == $videos[$videoNumber];
                if($select){
                    $selectedVideoFrame = $row['FovNum'];
                    $selectedVideoLat = $row['Plat'];
                    $selectedVideoLng = $row['Plng'];
                    $selectedVideoThetaX = $row['ThetaX'];
                }*/
            }

            for($r = 0; $r < count($rows); $r++){
                $id = $rows[$r]['VideoId'];
                $frame = $rows[$r]['FovNum'];
                $frames = [];
                $contained = false;
                for($v = 0; $v < count($videoFrames); $v++){
                    if($videoFrames[$v]['id'] == $id){
                        $contained = true;
                        $frames = $videoFrames[$v]['frames'];
                    }
                }
                //echo "ID: ".$id.", Frame: ".$frame."<br />";
                if(!$contained){
                    /* push new object with id and frame to array */
                    $frames['id'] = $id;
                    $frames['frames'] = Array($frame);
                    array_push($videoFrames, $frames);
                }else{
                    /* push frame to object with id */
                    array_push($frames['frames'], $frame);
                }
            }
            for($v = 0; $v < count($videoFrames); $v++){
                if(count($videoFrames[$v]['frames']) > 2){
                    sort($videoFrames[$v]['frames']);
                    $following = 0;
                    $usableFrames = [];
                    for($f = 0; $f < count($videoFrames[$v]['frames'])-3; $f++){
                        if($videoFrames[$v]['frames'][$f+1]-$videoFrames[$v]['frames'][$f]==1){
                            $following++;
                            if(!in_array($videoFrames[$v]['frames'][$f], $usableFrames)){
                                array_push($usableFrames, $videoFrames[$v]['frames'][$f]);
                            }
                            if(!in_array($videoFrames[$v]['frames'][$f+1], $usableFrames)){
                                array_push($usableFrames, $videoFrames[$v]['frames'][$f+1]);
                            }
                        }else{
                            if($following < 3){
                                $following = 0;
                                $usableFrames = [];
                            }else{
                                break;
                            }
                        }
                    }

                    if ($following >= 3){
                        $video['id'] = $videoFrames[$v]['id'];
                        $video['usableFrames'] = $usableFrames;
                        array_push($videos, $video);
                    }
                }
            }

            /* get Video from random position */
            $max = count($videos);
            $videoNumber = rand(0, $max);
            $rowNum = 0;
            $selectedVideoLat = 0;
            $selectedVideoLng = 0;
            $selectedVideoThetaX = 0;
            $videoStartTime = 0;
            $clipStartTime = 0;
            $clipStartFrame = 0;
            $clipEndTime = 0;
            $clipEndFrame = 0;

            //array_push($videos, $row['VideoId']);
            for($r = 0; $r < count($rows); $r++){
                $selectVideoStart = $rows[$r]['VideoId'] == $videos[$videoNumber]['id'] && $rows[$r]['FovNum'] == 1;
                if($selectClipStart){
                    $videoStartTime = $rows[$r]['TimeCode'];
                }
                $selectClipStart = $rows[$r]['VideoId'] == $videos[$videoNumber]['id'] && $rows[$r]['FovNum'] == $videos[$videoNumber]['usableFrames'][0];
                if($selectClipStart){
                    $clipStartTime = $rows[$r]['TimeCode'] - $videoStartTime;
                    $clipStartFrame = $row['FovNum'];
                    $selectedVideoLat = $row['Plat'];
                    $selectedVideoLng = $row['Plng'];
                    $selectedVideoThetaX = $row['ThetaX'];
                }
                $framesLength = count($videos[$videoNumber]['usableFrames']);
                $selectClipEnd = $rows[$r]['VideoId'] == $videos[$videoNumber]['id'] && $rows[$r]['FovNum'] == $videos[$videoNumber]['usableFrames'][$framesLength];
                if($selectClipStart){
                    $clipEndTime = $rows[$r]['TimeCode'] - $videoStartTime + 1000;
                    $clipEndFrame = $row['FovNum'];
                }
            }


            /* increase question id */
            $questionId += 1;
            $response = "{ \"id\": \"" . $questionId . "\" , ";
            //echo "Question ID: ".$questionId."<br />";

            $selectedVideo = $videos[$videoNumber];
            //echo "Selected Video: ".$videos[$videoNumber]."<br />";
            $response .= "\"video\": \"http://mediaq.dbs.ifi.lmu.de/MediaQ_MVC_V2/video_content/" . $selectedVideo . "\" , ";

            $response .= "\"videoStartTime\": \"".$videoStartTime."\", ";
            $response .= "\"clipStartTime\": \"".$clipStartTime."\", ";
            $response .= "\"clipEndTime\": \"".$clipEndTime."\", ";


            //echo "ID: ".$selectedVideo."Lat: ".$selectedVideoLat.", Lng: ".$selectedVideoLng.", ThetaX: ".$selectedVideoThetaX."<br />";

            /* get name = correct answer */
            /* web service */
            //$details = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$json_obj[results][$i][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
            /* json on our server */
            $details = file_get_contents($json_obj[results][$i][place_id].".json");
            $details_obj = json_decode($details, true);
            $name = $details_obj[result][name];
            //echo "Position: ".$lat.", ".$lng."; Name: ".$name."<br />";

            /* get available answers */
            for($j = 0; $j < count($json_obj['results']); $j++){
                if($j != $i && (sqrt(pow($json_obj[results][$j][Plat] - $selectedVideoLat, 2) + pow($json_obj[results][$j][Plng] - $selectedVideoLng, 2)) > 0.1 || ((rad2deg(acos(($json_obj[results][$j][Plng]-$selectedVideoLng)/sqrt(pow($json_obj[results][$j][Plat]-$selectedVideoLat, 2)+pow($json_obj[results][$j][Plng]-$selectedVideoLng, 2))))-$selectedVideoThetaX) < 51 && (rad2deg(acos(($json_obj[results][$j][Plng]-$selectedVideoLng)/sqrt(pow($json_obj[results][$j][Plat]-$selectedVideoLat, 2)+pow($json_obj[results][$j][Plng]-$selectedVideoLng, 2))))-$selectedVideoThetaX) > 0))){
                    /* web service */
                    //$answerDetails = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$json_obj[results][$j][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
                    /* json on our server */
                    $answerDetails = file_get_contents($json_obj[results][$j][place_id].".json");
                    $answerDetails_obj = json_decode($answerDetails, true);
                    $singlename = $answerDetails_obj[result][name];
                    array_push($availableAnswers, $singlename);
                }
            }
            /* shuffle available answers and take first 3 */
            shuffle($availableAnswers);
            $answers = array_slice($availableAnswers, 0, 3);
            //echo "Name: ".$name."<br />";
            /* shuffle chosen answers with correct answer */
            array_push($answers, $name);
            shuffle($answers);

            /* add answers to response json string */
            $response .= "\"answers\": " . "[ ";
            for($k = 0; $k < count($answers); $k++){
                if($k == count($answers) - 1){
                    $response .= "\"" . $answers[$k] ."\"";
                }else{
                    $response .= "\"" . $answers[$k] ."\" , ";
                }
            }
            $response .= "], ";

            /* add correct answer info to response json string */
            $correctIndex = array_search($name, $answers);
            $response .= "\"correctAnswer\": \"" . $correctIndex . "\"}";
            array_push($responseArray, $response);

        }


        /* free result set */
        mysqli_free_result($result);
    }
}


/* return to client */
echo "[".implode(", ",$responseArray)."]";
mysqli_close($link);

?>