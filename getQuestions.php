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
$json = file_get_contents('data/POI.json');
//echo $json;
$results = json_decode($json, true);
$results = $results["results"] ? $results["results"] : $results;
//print_r($results);
$resultLength = count($results);
for($i = 0; $i < $resultLength; ++$i){
    $lat = $results[$i][geometry][location][lat];
    $lng = $results[$i][geometry][location][lng];
    $availableAnswers = [];
    $answers = [];
    $wikiPageId = "";
    //echo "".$lat.", ".$lng."<br />";
    $query = "SELECT *
              FROM VIDEO_METADATA
              WHERE SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2)) < 0.0014
                AND (DEGREES(ACOS((Plng-".$lng.")/SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2))))-ThetaX) < 51
                AND (DEGREES(ACOS((Plng-".$lng.")/SQRT(POWER(Plat-".$lat.", 2)+POWER(Plng-".$lng.", 2))))-ThetaX) > 0";
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
            /* free result set */
            mysqli_free_result($result);

            //echo "<h3>New place:</h3> <br />";
            $rowsLength = count($rows);
            for($r = 0; $r < $rowsLength; ++$r){
                $id = $rows[$r]['VideoId'];
                $frame = $rows[$r]['FovNum'];
                //echo "ID: ". $id . ", Frame: " . $frame . ", Winkel: " . (rad2deg(acos(($rows[$r]['Plng']-$lng)/sqrt(pow($rows[$r]['Plat']-$lat, 2)+pow($rows[$r]['Plng']-$lng, 2))))-$rows[$r]['ThetaX']) . "<br />";
                $frames = [];
                $contained = false;
                $index = 0;
                $videoFramesLength = count($videoFrames);
                for($v = 0; $v < $videoFramesLength; ++$v){
                    if($videoFrames[$v]['id'] == $id){
                        $contained = true;
                        //$frames = $videoFrames[$v]['frames'];
                        $index = $v;
                    }
                }
                //echo "ID: ".$id.", Frame: ".$frame."<br />";
                if(!$contained){
                    /* push new object with id and frame to array */
                    $frames['id'] = $id;
                    $frames['frames'] = [];
                    array_push($frames['frames'], $frame);
                    array_push($videoFrames, $frames);
                }else{
                    /* push frame to object with id */
                    array_push($videoFrames[$index]['frames'], $frame);
                }
                //echo "ID: ".$frames['id'].", Frames: ".implode(", ", $frames['frames'])."<br />";
            }
            $videoFramesLength = count($videoFrames);
            for($v = 0; $v < $videoFramesLength; ++$v){
                //echo "ID: ".$videoFrames[$v]['id'].", Frames: ".implode(", ", $videoFrames[$v]['frames'])."<br />";
                if(count($videoFrames[$v]['frames']) > 2){
                    sort($videoFrames[$v]['frames']);
                    $following = 0;
                    $usableFrames = [];
                    $usableFramesArray = [];
                    $maxCount = count($videoFrames[$v]['frames'])-1;
                    for($f = 0; $f < $maxCount; ++$f){
                        if($videoFrames[$v]['frames'][$f+1]-$videoFrames[$v]['frames'][$f]==1){
                            $following++;
                            if(!in_array($videoFrames[$v]['frames'][$f], $usableFrames)){
                                array_push($usableFrames, $videoFrames[$v]['frames'][$f]);
                            }
                            if(!in_array($videoFrames[$v]['frames'][$f+1], $usableFrames)){
                                array_push($usableFrames, $videoFrames[$v]['frames'][$f+1]);
                            }
                        }else{
                            $usableFramesLength = count($usableFrames);
                            if($usableFramesLength < 3){
                                //echo "Current Frame is: ".$videoFrames[$v]['frames'][$f].", Did not push: "; print_r($usableFrames); echo "<br />";
                                $following = 0;
                                $usableFrames = [];
                            }else{
                                array_push($usableFramesArray, $usableFrames);
                                //echo "Current Frame is: ".$videoFrames[$v]['frames'][$f].", Pushed: "; print_r($usableFrames); echo "<br />";
                                $usableFrames = [];
                            }
                        }
                        if($f == $maxCount - 1){
                            $usableFramesLength = count($usableFrames);
                            if($usableFramesLength < 3){
                                //echo "Current Frame is: ".$videoFrames[$v]['frames'][$f].", Did not push: "; print_r($usableFrames); echo "<br />";
                                $following = 0;
                                $usableFrames = [];
                            }else{
                                array_push($usableFramesArray, $usableFrames);
                                //echo "Current Frame is: ".$videoFrames[$v]['frames'][$f].", Pushed: "; print_r($usableFrames); echo "<br />";
                                $usableFrames = [];
                            }
                        }
                    }
                    $usableFramesArrayLength = count($usableFramesArray);
                    if($usableFramesArrayLength > 0){
                        for($ufa = 0; $ufa < $usableFramesArrayLength; ++$ufa){
                            $video['id'] = $videoFrames[$v]['id'];
                            $video['usableFrames'] = $usableFramesArray[$ufa];
                            array_push($videos, $video);
                            //echo "ID: ".$video['id'].", Usable Frames: ".implode(", ", $video['usableFrames'])."<br />";
                        }
                    }
                }
            }
            //echo '<pre>'; print_r($videos); echo '</pre>';
            /* get Video from random position */
            $max = count($videos);
            if($max > 0){
                $videoNumber = rand(0, $max-1);
                //echo $videoNumber;
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
                $rowsLength = count($rows);
                for($r = 0; $r < $rowsLength; ++$r){
                    /*
                    if($rows[$r]['VideoId'] == $videos[$videoNumber]['id']){
                        $startTimeQuery = "SELECT TimeCode FROM VIDEO_METADATA WHERE VideoId=".$videos[$videoNumber]['id']." AND FovNum=1";
                        //echo $startTimeQuery."<br />";
                        if ($startTimeResult = mysqli_query($link, $startTimeQuery)){
                            echo mysqli_num_rows($startTimeResult)."<br />";
                            while($row = mysqli_fetch_array($startTimeResult)) {
                                $videoStartTime = $row;
                                echo "Start time: ".$row."<br />";
                            }
                        }
                        /* free result set
                        mysqli_free_result($startTimeResult);
                    }*/
                    /*
                    $selectVideoStart = $rows[$r]['VideoId'] == $videos[$videoNumber]['id'] && $rows[$r]['FovNum'] == 1;
                    if($selectVideoStart){
                        $videoStartTime = $rows[$r]['TimeCode'];
                    }
                    */
                    $selectClipStart = $rows[$r]['VideoId'] == $videos[$videoNumber]['id'] && $rows[$r]['FovNum'] == $videos[$videoNumber]['usableFrames'][0];
                    if($selectClipStart){
                        //$clipStartTime = $rows[$r]['TimeCode'] - $videoStartTime;
                        $clipStartFrame = $rows[$r]['FovNum'];
                        /*estimated start Time */
                        $clipStartTime = $clipStartFrame - 1;

                        $selectedVideoLat = $rows[$r]['Plat'];
                        $selectedVideoLng = $rows[$r]['Plng'];
                        $selectedVideoThetaX = $rows[$r]['ThetaX'];
                    }
                    $framesLength = count($videos[$videoNumber]['usableFrames']) - 1;
                    $selectClipEnd = $rows[$r]['VideoId'] == $videos[$videoNumber]['id'] && $rows[$r]['FovNum'] == $videos[$videoNumber]['usableFrames'][$framesLength];
                    if($selectClipEnd){
                        $clipEndTime = $rows[$r]['TimeCode'] - $videoStartTime + 1000;
                        $clipEndFrame = $rows[$r]['FovNum'];
                        /* estimated end time */
                        $clipEndTime = $clipEndFrame + 2;
                    }
                }


                /* increase question id */
                $questionId += 1;
                $response = "{ \"id\": \"" . $questionId . "\" , ";
                //echo "Question ID: ".$questionId."<br />";

                $selectedVideo = $videos[$videoNumber]['id'];
                //echo "Selected Video: ".$videos[$videoNumber]."<br />";
                $response .= "\"video\": \"http://mediaq.dbs.ifi.lmu.de/MediaQ_MVC_V2/video_content/" . $selectedVideo . "#t=". $clipStartTime . ",". $clipEndTime . "\" , ";

                /* add coordinates for map to response */
                $response .= "\"lat\": \"".$lat."\", ";
                $response .= "\"lng\": \"".$lng."\", ";

                //$response .= "\"videoStartTime\": \"".$videoStartTime."\", ";
                $response .= "\"clipStartTime\": \"".$clipStartTime."\", ";
                $response .= "\"clipEndTime\": \"".$clipEndTime."\", ";


                //echo "ID: ".$selectedVideo."Lat: ".$selectedVideoLat.", Lng: ".$selectedVideoLng.", ThetaX: ".$selectedVideoThetaX."<br />";

                /* get name = correct answer */
                /* web service */
                //$details = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$results[$i][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
                /* json on our server */
                $details = file_get_contents("data/".$results[$i][place_id].".json");
                //echo $details;
                //echo "<br />";
                $details_obj = json_decode($details, true);
                $name = $details_obj[result][name];
                //echo "Position: ".$lat.", ".$lng."; Name: ".$name."<br />";
                //echo '<br />'; echo $name; echo '<br />';
                /* get available answers */
                /* new */
                $availableAnswerObjects = $results;
                //echo "Old: "; echo count($availableAnswerObjects); echo "<br />";
                /* search from current point in ascending order */
                for($j = $i; $j < $resultLength; ++$j){
                    if($j != $i){
                        /* if distance of lat is lower than threshold, check complete distance and angle, remove item if distance is to low */
                        if($results[$j][geometry][location][lat] - $selectedVideoLat < 0.2){
                            /* check distance and angle */
                            if(sqrt(pow($results[$j][geometry][location][lat] - $selectedVideoLat, 2) + pow($results[$j][geometry][location][lng] - $selectedVideoLng, 2)) > 0.2 || ((rad2deg(acos(($results[$j][geometry][location][lng]-$selectedVideoLng)/sqrt(pow($results[$j][geometry][location][lat]-$selectedVideoLat, 2)+pow($results[$j][geometry][location][l]-$selectedVideoLng, 2))))-$selectedVideoThetaX) > 51 || (rad2deg(acos(($results[$j][Plng]-$selectedVideoLng)/sqrt(pow($results[$j][Plat]-$selectedVideoLat, 2)+pow($results[$j][Plng]-$selectedVideoLng, 2))))-$selectedVideoThetaX) < 0)){
                                continue;
                            }else{
                                /* remove item from answer list */
                                //removeElementWithValue($availableAnswerObjects, "place_id", $results[$j]["place_id"]);
                                unset($availableAnswerObjects[$j]);
                            }
                        }else{
                            /* break loop as soon as first element with lat distance higher than threshold is found */
                            break;
                        }
                    }else{
                        /* remove item from answer list */
                        //removeElementWithValue($availableAnswerObjects, "place_id", $results[$j]["place_id"]);
                        unset($availableAnswerObjects[$j]);
                    }
                }
                /* search from current point in descending order */
                for($j = $i; $j >= 0; --$j){
                    if($j != $i){
                        /* if distance of lat is lower than threshold, check complete distance and angle, remove item if distance is to low */
                        if($results[$j][geometry][location][lat] - $selectedVideoLat < 0.2){
                            /* check distance and winkel */
                            if(sqrt(pow($results[$j][geometry][location][lat] - $selectedVideoLat, 2) + pow($results[$j][geometry][location][lng] - $selectedVideoLng, 2)) > 0.2 || ((rad2deg(acos(($results[$j][geometry][location][lng]-$selectedVideoLng)/sqrt(pow($results[$j][geometry][location][lat]-$selectedVideoLat, 2)+pow($results[$j][geometry][location][l]-$selectedVideoLng, 2))))-$selectedVideoThetaX) > 51 || (rad2deg(acos(($results[$j][Plng]-$selectedVideoLng)/sqrt(pow($results[$j][Plat]-$selectedVideoLat, 2)+pow($results[$j][Plng]-$selectedVideoLng, 2))))-$selectedVideoThetaX) <0)){
                                continue;
                            }else{
                                /* remove item from answer list */
                                //removeElementWithValue($availableAnswerObjects, "place_id", $results[$i]["place_id"]);
                                unset($availableAnswerObjects[$j]);
                            }
                        }else{
                            /* break loop as soon as first element with lat distance higher than threshold is found */
                            break;
                        }
                    }else{
                        /* remove item from answer list */
                        //removeElementWithValue($availableAnswerObjects, "place_id", $results[$j]["place_id"]);
                        unset($availableAnswerObjects[$j]);
                    }
                }
                //echo "New: "; echo count($availableAnswerObjects); echo "<br />";
                /* get answer names */
                $availableAnswerObjectsLength = count($availableAnswerObjects);
                for($a = 0; $a < $availableAnswerObjectsLength; ++$a){
                    /* web service */
                    //$answerDetails = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$availableAnswerObjects[$j][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
                    /* json on our server */
                        $answerDetails = file_get_contents("data/".$availableAnswerObjects[$a][place_id].".json");
                        //echo $answerDetails;
                        //echo "<br />";
                        $answerDetails_obj = json_decode($answerDetails, true);
                        $singlename = $answerDetails_obj[result][name];
                        if(!empty($singlename)) {
                            array_push($availableAnswers, $singlename);
                        }
                }
                /* old */
                /*
                for($j = 0; $j < $resultLength; ++$j){
                    if($j != $i && (sqrt(pow($results[$j][geometry][location][lat] - $selectedVideoLat, 2) + pow($results[$j][geometry][location][lng] - $selectedVideoLng, 2)) > 0.1 || ((rad2deg(acos(($results[$j][geometry][location][lng]-$selectedVideoLng)/sqrt(pow($results[$j][geometry][location][lat]-$selectedVideoLat, 2)+pow($results[$j][geometry][location][l]-$selectedVideoLng, 2))))-$selectedVideoThetaX) < 51 && (rad2deg(acos(($results[$j][Plng]-$selectedVideoLng)/sqrt(pow($results[$j][Plat]-$selectedVideoLat, 2)+pow($results[$j][Plng]-$selectedVideoLng, 2))))-$selectedVideoThetaX) > 0))){
                        /* web service */
                        //$answerDetails = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$results[$j][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
                        /* json on our server *//*
                        $answerDetails = file_get_contents("data/".$results[$j][place_id].".json");
                        //echo $answerDetails;
                        //echo "<br />";
                        $answerDetails_obj = json_decode($answerDetails, true);
                        $singlename = $answerDetails_obj[result][name];
                        array_push($availableAnswers, $singlename);
                    }
                }
                */
                /* shuffle available answers and take first 3 */
                shuffle($availableAnswers);
                $answers = array_slice($availableAnswers, 0, 3);
                //echo "Name: ".$name."<br />";
                /* shuffle chosen answers with correct answer */
                array_push($answers, $name);
                shuffle($answers);

                /* add answers to response json string */
                $response .= "\"answers\": " . "[ ";
                $answersLength = count($answers);
                for($k = 0; $k < $answersLength; ++$k){
                    if($k == count($answers) - 1){
                        $response .= "\"" . $answers[$k] ."\"";
                    }else{
                        $response .= "\"" . $answers[$k] ."\" , ";
                    }
                }
                $response .= "], ";

                /* add correct answer info to response json string */
                $correctIndex = array_search($name, $answers);
                $response .= "\"correctAnswer\": \"" . $correctIndex . "\"";

                /* get wiki info */
                $wikiPages = file_get_contents('https://de.wikipedia.org/w/api.php?action=query&list=geosearch&gsradius=100&gscoord='.$lat.'|'.$lng.'&format=json');
                $wikiPages_obj = json_decode($wikiPages, true);
                $countWikiPages = count($wikiPages_obj['query']['geosearch']);
                if($countWikiPages > 0) {
                    $firstWikiPage = $wikiPages_obj['query']['geosearch'][0];
                    //$wikiPageId = $firstWikiPage['pageid'];
                    for($c = 0; $c < $countWikiPages; ++$c){
                        $pageTitle = $wikiPages_obj['query']['geosearch'][$c]['title'];
                        //$nameStart = substr($name, 0, 5);
                        /*
                        echo $pageTitle;
                        echo "<br />";
                        echo $nameStart;
                        echo "<br />";
                        echo (strpos($pageTitle,$nameStart) !== false);
                        echo "<br />";
                        */
                        /* if similarity of titles is higher than 85% */
                        $percentage = levenshteinPerc($pageTitle, $name);
                        if($percentage < 0.8){
                            $wikiPageId = $wikiPages_obj['query']['geosearch'][$c]['pageid'];
                            /*
                            echo $wikiPageId;
                            echo "<br />";
                            */
                            break;
                        }
                    }
                }
                if(!empty($wikiPageId)){
                    $wikiText = file_get_contents('https://de.wikipedia.org/w/api.php?action=parse&pageid='.$wikiPageId.'&prop=text&section=0&format=json');
                    $wikiUrl = urlencode('https://de.wikipedia.org/w/api.php?action=parse&pageid='.$wikiPageId.'&prop=text&section=0&format=json');
                    $response .= ", \"wiki\": \"".$wikiUrl."\"}";
                }else{
                    $response .= "}";
                }


                /* add single response to array of all questions */
                array_push($responseArray, $response);
            }


        }

    }
}


/* return to client */
echo "[".implode(", ",$responseArray)."]";
mysqli_close($link);

function removeElementWithValue($array, $key, $value){
    foreach($array as $subKey => $subArray){
        if($subArray[$key] == $value){
            unset($array[$subKey]);
        }
    }
    return $array;
}

function levenshteinPerc($str1, $str2) {
    $len = max(strlen($str1), strlen($str2));
    if ($len===0 && strlen($str2)===0) {
        return 0;
    } else {
        return ($len>0 ? levenshtein($str1, $str2) / $len : 1);
    }
}

/**
 * A function for retrieving the Kölner Phonetik value of a string
 *
 * As described at http://de.wikipedia.org/wiki/Kölner_Phonetik
 * Based on Hans Joachim Postel: Die Kölner Phonetik.
 * Ein Verfahren zur Identifizierung von Personennamen auf der
 * Grundlage der Gestaltanalyse.
 * in: IBM-Nachrichten, 19. Jahrgang, 1969, S. 925-931
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package phonetics
 * @version 1.0
 * @link http://www.einfachmarke.de
 * @license GPL 3.0 <http://www.gnu.org/licenses/>
 * @copyright  2008 by einfachmarke.de
 * @author Nicolas Zimmer <nicolas dot zimmer at einfachmarke.de>
 */

function cologne_phon($word) {
    /**
     * @param  string  $word string to be analyzed
     * @return string  $value represents the Kölner Phonetik value
     * @access public
     */

    // prepare for processing
    $word = strtolower($word);
    $substitution = array(
        "ä"=>"a",
        "ö"=>"o",
        "ü"=>"u",
        "ß"=>"ss",
        "ph"=>"f"
    );

    foreach ($substitution as $letter => $substitution) {
        $word = str_replace($letter,$substitution,$word);
    }

    $len = strlen($word);

    // Rule for exeptions
    $exceptionsLeading = array(
        4 => array("ca","ch","ck","cl","co","cq","cu","cx"),
        8 => array("dc","ds","dz","tc","ts","tz")
    );

    $exceptionsFollowing = array("sc","zc","cx","kx","qx");

    //Table for coding
    $codingTable = array(
        0  => array("a", "e", "i", "j", "o", "u", "y"),
        1  => array("b", "p"),
        2  => array("d", "t"),
        3  => array("f", "v", "w"),
        4  => array("c", "g", "k", "q"),
        48 => array("x"),
        5  => array("l"),
        6  => array("m", "n"),
        7  => array("r"),
        8  => array("c", "s", "z"),
    );

    for ($i=0; $i<$len; $i++) {
        $value[$i] = "";

        //Exceptions
        if ($i == 0 && $word[$i].$word[$i+1] == "cr") {
            $value[$i] = 4;
        }

        foreach ($exceptionsLeading as $code => $letters) {
            if (in_array($word[$i].$word[$i+1], $letters)) {
                $value[$i] = $code;
            }
        }

        if ($i != 0 && (in_array($word[$i-1].$word[$i], $exceptionsFollowing))) {
            $value[$i] = 8;
        }

        // normal encoding
        if ($value[$i] == "") {
            foreach ($codingTable as $code => $letters) {
                if (in_array($word[$i], $letters)) {
                    $value[$i] = $code;
                }
            }
        }
    }

    // delete double values
    $len = count($value);

    for ($i=1; $i<$len; $i++) {
        if ($value[$i] == $value[$i-1]) {
            $value[$i] = "";
        }
    }

    // delete vocals
    for ($i=1; $i>$len; $i++) {
        // omitting first characer code and h
        if ($value[$i] == 0) {
            $value[$i] = "";
        }
    }

    $value = array_filter($value);
    $value = implode("", $value);

    return $value;
}

?>