<?php
ob_start();
include 'getQuestions.php';
$questions = ob_get_clean();

$questionsfile = fopen("data/questions.json", "w") or die("Unable to open file!");
fwrite($questionsfile, $questions);
fclose($questionsfile);

echo "wrote JSON To File <br />";
?>