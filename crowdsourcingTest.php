<?php
ob_start();
include 'getPOIs.php';
$crowdsourcing = ob_get_clean();

$crowdsourcingfile = fopen("data/crowdsourcing.json", "w") or die("Unable to open file!");
fwrite($crowdsourcingfile, $crowdsourcing);
fclose($crowdsourcingfile);

echo "wrote crowdsourcing To File <br />";

?>