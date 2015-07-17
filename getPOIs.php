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
        $videos = [];
        if(mysqli_num_rows($result) > 0) {
            $rows = [];
            $videoFrames = [];

            while ($row = mysqli_fetch_array($result)) {
                array_push($rows, $row);
            }
            /* free result set */
            mysqli_free_result($result);

            $rowsLength = count($rows);
            for ($r = 0; $r < $rowsLength; ++$r) {
                $id = $rows[$r]['VideoId'];
                $frame = $rows[$r]['FovNum'];
                $frames = [];
                $contained = false;
                $index = 0;
                $videoFramesLength = count($videoFrames);
                for ($v = 0; $v < $videoFramesLength; ++$v) {
                    if ($videoFrames[$v]['id'] == $id) {
                        $contained = true;
                        $index = $v;
                    }
                }
                if (!$contained) {
                    /* push new object with id and frame to array */
                    $frames['id'] = $id;
                    $frames['frames'] = [];
                    array_push($frames['frames'], $frame);
                    array_push($videoFrames, $frames);
                } else {
                    /* push frame to object with id */
                    array_push($videoFrames[$index]['frames'], $frame);
                }
            }
            $videoFramesLength = count($videoFrames);
            for ($v = 0; $v < $videoFramesLength; ++$v) {
                if (count($videoFrames[$v]['frames']) > 2) {
                    sort($videoFrames[$v]['frames']);
                    $following = 0;
                    $usableFrames = [];
                    $usableFramesArray = [];
                    $maxCount = count($videoFrames[$v]['frames']) - 1;
                    for ($f = 0; $f < $maxCount; ++$f) {
                        if ($videoFrames[$v]['frames'][$f + 1] - $videoFrames[$v]['frames'][$f] == 1) {
                            $following++;
                            if (!in_array($videoFrames[$v]['frames'][$f], $usableFrames)) {
                                array_push($usableFrames, $videoFrames[$v]['frames'][$f]);
                            }
                            if (!in_array($videoFrames[$v]['frames'][$f + 1], $usableFrames)) {
                                array_push($usableFrames, $videoFrames[$v]['frames'][$f + 1]);
                            }
                        } else {
                            $usableFramesLength = count($usableFrames);
                            if ($usableFramesLength < 3) {
                                $following = 0;
                                $usableFrames = [];
                            } else {
                                array_push($usableFramesArray, $usableFrames);
                                $usableFrames = [];
                            }
                        }
                        if ($f == $maxCount - 1) {
                            $usableFramesLength = count($usableFrames);
                            if ($usableFramesLength < 3) {
                                $following = 0;
                                $usableFrames = [];
                            } else {
                                array_push($usableFramesArray, $usableFrames);
                                $usableFrames = [];
                            }
                        }
                    }
                    $usableFramesArrayLength = count($usableFramesArray);
                    if ($usableFramesArrayLength > 0) {
                        for ($ufa = 0; $ufa < $usableFramesArrayLength; ++$ufa) {
                            $video['id'] = $videoFrames[$v]['id'];
                            $video['usableFrames'] = $usableFramesArray[$ufa];
                            array_push($videos, $video);
                        }
                    }
                }
            }
        }
        $value = array(
            "name" => $name,
            "videos" => count($videos),
            "lat" => $lat,
            "lng" => $lng
        );
        array_push($response, $value);
    }
}

echo json_encode($response);
mysqli_close($link);

?>