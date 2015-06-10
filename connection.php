<?php

    include_once('db_info.php');

    $link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    /*
    if ($result = mysqli_query($link, "SELECT * FROM VIDEO_METADATA")) {
        printf("Select returned %d rows.\n", mysqli_num_rows($result));

        //var_dump($result);
        $num = mysqli_num_rows($result);
        echo "<b>
                                <h3>Database Output</h3>
                                </b>
                                <br>
                                <table>
                                <tr><td>VideoId</td><td>FovNum</td><td>Plat</td><td>Plng</td></tr>";
        while($row = mysqli_fetch_array($result)) {
            echo "<tr><td>".$row['VideoId']."</td><td>".$row['FovNum']."</td><td>".$row['Plat']."</td><td>".$row['Plng']."</td></tr>";
        }
        echo "</table>";

        /* free result set
        mysqli_free_result($result);
    }*/

    echo "<html><head><meta charset='UTF-8'></head><body>";

    $questionId = 0;
    $response = "{";

    $json = file_get_contents('https://maps.googleapis.com/maps/api/place/radarsearch/json?location=48.1549108,11.5418358&radius=500000&types=(airport|amusement_park|aquarium|art_gallery|bar|cafe|casino|cemetery|city_hall|embassy|establishment|hospital|library|movie_theater|museum|night_club|park|place_of_worship|police|restaurant|school|shopping_mall|stadium|train_station|university|zoo)&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
    $json_obj = json_decode($json, true);
    for($i = 0; $i < count($json_obj['results']); $i++){
        $lat = $json_obj[results][$i][geometry][location][lat];
        $lng = $json_obj[results][$i][geometry][location][lng];
        $availableAnswers = [];
        $answers = [];

        /* Select queries return a resultset */
        if ($result = mysqli_query($link, "SELECT * FROM VIDEO_METADATA WHERE SQRT(SQUARE(Plat-".$lat.")+SQUARE(Plng-".$lng.")) < 0,1 AND (DEGREES(ARCCOS((Plng-".$lng.")/SQRT(SQUARE(Plat-".$lat.")+SQUARE(Plng-".$lng."))))-ThetaX) < 51 AND (DEGREES(ARCCOS((Plng-".$lng.")/SQRT(SQUARE(Plat-".$lat.")+SQUARE(Plng-".$lng."))))-ThetaX) > 0")) {
            echo "Got result!";
            if(mysqli_num_rows($result) > 0){
                /* increase question id */
                $questionId += 1;
                $response .= "question: { id: " . $questionId . ", ";

                /* get Video from random position */
                $max = mysqli_num_rows($result) - 1;
                $videoNumber = rand(0, $max);
                $rowNum = 0;
                while($row = mysqli_fetch_array($result)) {
                    if($rowNum == $videoNumber){
                        $selectedVideo = $row['VideoId'];
                        $response .= "video: " . $selectedVideo . ", ";
                    }else{
                        $rowNum += 1;
                    }
                }


                /* get name = correct answer */
                $details = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$json_obj[results][$i][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
                $details_obj = json_decode($details, true);
                $name = $details_obj[result][name];

                /* get available answers */
                for($j = 0; $j < count($json_obj['results']); $j++){
                    if($j != $i){
                        $answerDetails = file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?placeid='.$json_obj[results][$j][place_id].'&key=AIzaSyAhFHDr_1SlAdzp2G0OfM7p9kw-QI9IUCs');
                        $answerDetails_obj = json_decode($answerDetails, true);
                        $name = $answerDetails_obj[result][name];
                        array_push($availableAnswers, $name);
                    }
                }
                /* shuffle available answers and take first 3 */
                shuffle($availableAnswers);
                $answers = array_slice($availableAnswers, 0, 3);

                /* shuffle chosen answers with correct answer */
                array_push($answers, $name);
                shuffle($answers);

                /* add answers to response json string */
                $response .= "answers: " . "{ ";
                for($k = 0; $k < count($answers); $k++){
                    if($k == count($answers) - 1){
                        $response .= "" . $k . ": " . $answers[$k] ."";
                    }else{
                        $response .= "" . $k . ": " . $answers[$k] .", ";
                    }
                }
                $response .= "}, ";

                /* add correct answer info to response json string */
                $correctIndex = array_search($name, $answers);
                $response .= "correctAnswer: " . $correctIndex . "}, ";

                /* show query result */
                printf("Select returned %d rows.<br />", mysqli_num_rows($result));
                echo "Point of interest: <br />" . $lat . ", " . $lng . "<br />";
                echo "<br /> Name: " . $name . "<br />";
                //var_dump($result);
                $num = mysqli_num_rows($result);
                echo "<b>
                <h3>Database Output</h3>
                </b>
                <br>
                <table>
                <tr><td>VideoId</td><td>FovNum</td><td>Plat</td><td>Plng</td></tr>";
                while($row = mysqli_fetch_array($result)) {
                    echo "<tr><td>".$row['VideoId']."</td><td>".$row['FovNum']."</td><td>".$row['Plat']."</td><td>".$row['Plng']."</td></tr>";
                }
                echo "</table>";
            }


            /* free result set */
            mysqli_free_result($result);
        }
    }


    mysqli_close($link);
    $response .= "}";
    /* return to client */
    echo $response;
    echo "</body></html>";
    //echo($json);

?>