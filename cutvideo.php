<?php

class Interval {
  
  private $startsec;
  private $endsec;
  
  public function Interval($text) {
    $values = split('-', $text);
    
    if (count($values) != 2) {
      die("Interval $text is not valid.\n");
    }
    
    if (preg_match('/^([0-9]+)$/', $values[0], $sub)) {
      $this->startsec = intval($sub[1]);
    } elseif (preg_match('/^([0-9]+):([0-9]+)$/', $values[0], $sub)) {
      $this->startsec = intval($sub[1])*60 + intval($sub[2]);
    } elseif (preg_match('/^([0-9]+):([0-9]+):([0-9]+)$/', $values[0], $sub)) {
      $this->startsec = (intval($sub[1])*60 + intval($sub[2]))*60 + intval($sub[3]);
    } else {
      die("Interval $text is not valid.\n");
    }
    
    if (preg_match('/^([0-9]+)$/', $values[1], $sub)) {
      $this->endsec = intval($sub[1]);
    } elseif (preg_match('/^([0-9]+):([0-9]+)$/', $values[1], $sub)) {
      $this->endsec = intval($sub[1])*60 + intval($sub[2]);
    } elseif (preg_match('/^([0-9]+):([0-9]+):([0-9]+)$/', $values[1], $sub)) {
      $this->endsec = (intval($sub[1])*60 + intval($sub[2]))*60 + intval($sub[3]);
    } else {
      die("Interval $text is not valid.\n");
    }
    
    if ($this->getLength() < 1) {
      die("Interval $text is not valid.\n");
    }
  }
  
  public function getStart() {
    return $this->startsec;
  }
  
  public function getLength() {
    return $this->endsec - $this->startsec;
  }
  
  public function toString() {
    $starttime = $this->startsec;
    $startsec = $starttime % 60; $starttime -= $startsec; $starttime /= 60;
    $startmin = $starttime % 60; $starttime -= $startmin; $starttime /= 60;
    $starthour = $starttime;
    
    $endtime = $this->endsec;
    $endsec = $endtime % 60; $endtime -= $endsec; $endtime /= 60;
    $endmin = $endtime % 60; $endtime -= $endmin; $endtime /= 60;
    $endhour = $endtime;
    
    return '[' . ($starthour < 10 ? '0' : '') . $starthour . ($startmin < 10 ? ':0' : ':') . $startmin . ($startsec < 10 ? ':0' : ':') . $startsec .
      ' - ' . ($endhour < 10 ? '0' : '') . $endhour . ($endmin < 10 ? ':0' : ':') . $endmin . ($endsec < 10 ? ':0' : ':') . $endsec .']';
  }
}

function execute($code) {
  passthru($code);
}

/*
 * MAIN begins here
 */

if ($argc < 4) {
  die("Usage: php cutvideo.php <infile> <outfile> <start1>-<end1> [<start2>-<end2> ...]\nExample: php cutvideo.php in.avi out.avi 0:15-12:35 1:21:00-1:50:45\n");
}

define('TEMPDIR', '/tmp/videocutting');

$INFILE = $argv[1];
$OUTFILE = $argv[2];

$NUMCUTS = $argc - 3;

$INTERVALS = array();
for ($i = 1; $i <= $NUMCUTS; $i++) {
  $INTERVALS[] = new Interval($argv[$i + 2]);
}

if (!file_exists($INFILE)) {
  die('Input file "'. $INFILE . '" does not exist.' . "\n");
}


?>
You are about to do the following things now:
 * Read from Input-file: "<?=$INFILE?>"
 * Write to Output-file: "<?=$OUTFILE?>"
 * Merge this video fragments:
<?php foreach($INTERVALS as $interval) { ?>
   # <?=$interval->toString()?>

<?php } ?>
Press <Return> to Continue ...
<?php
fgets(STDIN); // liest eine Zeile von STDIN

// Start now

execute("mkdir -p " . TEMPDIR);

$partfiles = array();
for ($i = 0; $i < count($INTERVALS); $i++) {
  $interval = $INTERVALS[$i];
  execute('mencoder -ss '.$interval->getStart().' -endpos '.$interval->getLength().' -oac copy -ovc copy -o '.TEMPDIR.'/part'.$i.'.avi '.$INFILE);
  $partfiles[] = TEMPDIR . '/part'.$i.'.avi';
}

execute('avimerge -o ' . $OUTFILE . ' -i ' . implode(' ', $partfiles));

execute('rm -r ' . TEMPDIR);

?>