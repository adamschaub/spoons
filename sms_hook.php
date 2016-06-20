<?php
include('config.php');
include('functions.php');
include('db_connect.php');

header("content-type: text/xml");

$text = $_REQUEST['Body'];
//$text = "Spoon caJun.";

$text = str_replace("?", "", $text);    // ignore question marks
$text = trim($text);

if(strpos($text, " ") !== false)
  $command = substr($text, 0, strpos($text, " "));
else
  $command = $text;

if(strlen($text) > strlen($command)) {
  $subject = substr($text, strlen($command) + 1);
  $subject_id = getIDByLooseName($connection, $subject);
}

$help = 'List of commands:' . "\n" . '"Spoon (name)" to spoon.' . "\n" . '"Status (name)" to check.' . "\n" . '"Remaining" for number of alive spooners.';

if($subject && $subject_id == "multiple") {
  $response = "There are multiple " . $subject . "s in the system. Please specify last name or last initial.";
} else if($subject && $subject_id == "none") {
  $response = "There were no spooners by the name " . $subject . " found in the system. Sorry (but not really).";
} else if($subject && strcasecmp($command, "spoon") == 0) {
  spoonByID($subject_id);
  $response = getNameByID($subject_id) . ' has been spooned! ' . getNameByID($connection, getSpoonedByIDByID($connection, $subject_id)) . '\'s new target is ' . getNameByID($connection, getTargetByID($connection, getSpoonedByIDByID($connection, $subject_id))) . '.';
} else if($subject && strcasecmp($command, "status") == 0) {
  if(checkSpoonedByID($connection, $subject_id)) {
    $response = getNameByID($connection, $subject_id) . ' was spooned by ' . getNameByID($connection, getSpoonedByIDByID($connection, $subject_id)) . ' on ' . date('l', strtotime(getTimeSpoonedByID($connection, $subject_id))) . ' at ' . date('g:i A', strtotime(getTimeSpoonedByID($connection, $subject_id))) . '.';
  } else {
    $response = getNameByID($connection, $subject_id) . ' has not been spooned. ' . getFirstNameByID($connection, $subject_id) . '\'s target is ' . getNameByID($connection, getTargetByID($connection, $subject_id)) . ' and is targeted by ' . getNameByID($connection, getReverseTargetByID($connection, $subject_id)) . '.';
  }
} else if(strcasecmp($command, "remaining") == 0) {
  $response = "There are " . getNumActiveSpooners($connection) . " of " . getNumTotalSpooners($connection) . " spooners remaining. (" . getNumActiveCamperSpooners($connection) . " campers, " . getNumActiveStaffSpooners($connection) . " staff)";
} else if(strcasecmp($command, "commands") == 0 || strcasecmp($command, "command") == 0) {
  $response = $help;
} else {
  $response = "Invalid command. " . $help;
}

logSMS($connection, $_REQUEST['Body'], $response, $_REQUEST['From']);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<Response>
  <Sms><?php echo $response ?></Sms>
</Response>
