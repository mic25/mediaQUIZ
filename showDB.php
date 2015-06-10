<?php

include_once('db_info.php');

$link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$query = "SELECT * FROM VIDEO_METADATA";
$rows = [];

if ($result = mysqli_query($link, $query)) {
    printf("Select returned %d rows.\n", mysqli_num_rows($result));

    //var_dump($result);
    $num = mysqli_num_rows($result);
    echo "<b>
                                <h3>Database Output</h3>
                                </b>
                                <br>
                                <table>
                                <tr><td>VideoId</td><td>FovNum</td><td>Plat</td><td>Plng</td><td>TimeCode</td></tr>";
    while($row = mysqli_fetch_array($result)) {
        echo "<tr><td>".$row['VideoId']."</td><td>".$row['FovNum']."</td><td>".$row['Plat']."</td><td>".$row['Plng']."</td><td>".$row['TimeCode']."</td></tr>";
    }
    echo "</table>";

    /*free result set */
    mysqli_free_result($result);


}