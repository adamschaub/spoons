<?php

function logSMS($connection, $message, $response, $number) {
  mysqli_query($connection, 'INSERT INTO texts (message, response, phone_number) VALUES ("' . mysqli_real_escape_string($connection, $message) . '", "' . mysqli_real_escape_string($connection, $response) . '", "' . mysqli_real_escape_string($connection, $number) . '")');
}

function getNumTotalSpooners($connection) {
  $result = mysqli_query($connection, "SELECT id FROM spooners");
  return mysqli_num_rows($result);
}

function getNumActiveSpooners($connection) {
  $result = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0");
  return mysqli_num_rows($result);
}

function getNumActiveCamperSpooners($connection) {
  $result = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND staff = 0");
  return mysqli_num_rows($result);
}

function getNumActiveStaffSpooners($connection) {
  $result = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND staff = 1");
  return mysqli_num_rows($result);
}

function getLowestOrderNum($connection) {
  $result = mysqli_query($connection, "SELECT order_num FROM spooners WHERE spooned = 0 ORDER BY order_num ASC LIMIT 1");
  $spooner = mysqli_fetch_array($result);
  return $spooner['order_num'];
}

function getHighestOrderNum($connection) {
  $result = mysqli_query($connection, "SELECT order_num FROM spooners WHERE spooned = 0 ORDER BY order_num DESC LIMIT 1");
  $spooner = mysqli_fetch_array($result);
  return $spooner['order_num'];
}

function getCamperIDs($connection){
  $camper_ids = array();

  $result = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND staff = 0 ORDER BY id");
  while($spooner = mysqli_fetch_array($result)) {
    array_push($camper_ids, $spooner['id']);
  }
  return $camper_ids;
}

function getStaffIDs($connection){
  $staff_ids = array();

  $result = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND staff = 1 ORDER BY id");
  while($spooner = mysqli_fetch_array($result)) {
    array_push($staff_ids, $spooner['id']);
  }
  return $staff_ids;
}

function shuffleSpooners($connection) {
  $camper_ids = getCamperIDs();
  $staff_ids = getStaffIDs();

  shuffle($camper_ids);
  shuffle($staff_ids);

  //An array to put the ids for the new shuffled list
  $random_ids = array();

  //Determine the number of campers between each staff
  $spacing = round(count($camper_ids)/count($staff_ids))+1;


  while(count($random_ids) < getNumActiveSpooners()){
    for($i = 0; $i < $spacing-1; $i++){
      array_push($random_ids, array_pop($camper_ids));
    }
    array_push($random_ids, array_pop($staff_ids));

    //This shouldn't happen but it is here just in case
    if(count($staff_ids) == 0){
      while(count($camper_ids) > 0){
        array_push($random_ids, array_pop($camper_ids));
      }
    }
    else{
        //Recalculate the spacing each round to end up with a more even spacing over the entire list.
        $spacing = round(count($camper_ids)/count($staff_ids))+1;
    }
  }


  $result = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 ORDER BY id");

  for ($i=0; $spooner = mysqli_fetch_array($result); $i++) { 
    mysqli_query($connection, "UPDATE spooners SET order_num = " . $i . " WHERE id = " . $random_ids[$i]);
  }
}

function getIDByLooseName($connection, $subject) {
  $subject = trim($subject);
  $subject = strtolower($subject);
  $subject = str_replace(".", "", $subject);  // remove periods
  
  // subject only contains one word
  if(substr_count($subject, " ") == 0) {    
    $result = mysqli_query($connection, 'SELECT id FROM spooners WHERE LOWER(first) = "' . $subject . '"');
    if(mysqli_num_rows($result) == 1) {
      $spooner = mysqli_fetch_array($result);
      return $spooner['id'];   // MATCH!
    } else if(mysqli_num_rows($result) > 1) {
      return "multiple";       // more than one found
    }
  } else if(substr_count($subject, " ") == 1) {       // one space, let's assume first space last
    $first = substr($subject, 0, strpos($subject, " "));
    $last = substr($subject, strpos($subject, " ") + 1);
    if(strlen($last) == 1) {   // last initial
      $result = mysqli_query($connection, 'SELECT id FROM spooners WHERE LOWER(first) = "' . $first . '" AND LOWER(SUBSTRING(last, 1, 1)) = "' . $last . '"');
      if(mysqli_num_rows($result) > 0) {
        $spooner = mysqli_fetch_array($result);
        return $spooner['id'];   // MATCH!
      }
    } else {    // full last name
      $result = mysqli_query($connection, 'SELECT id FROM spooners WHERE LOWER(first) = "' . $first . '" AND LOWER(last) = "' . $last . '"');
      if(mysqli_num_rows($result) > 0) {
        $spooner = mysqli_fetch_array($result);
        return $spooner['id'];   // MATCH!
      }
    }
    
    // still not found, take whole subject and compare to concatenated first + last in database
    $result = mysqli_query($connection, 'SELECT id FROM spooners WHERE LOWER(CONCAT_WS(" ", first, last)) = "' . $subject . '"');
    if(mysqli_num_rows($result) > 0) {
      $spooner = mysqli_fetch_array($result);
      return $spooner['id'];
    }
  }
  
  return "none";
}

function getNameByID($connection, $id) {
  if($id) {
    $result = mysqli_query($connection, "SELECT first, last FROM spooners WHERE id = " . $id) or die(mysqli_error($connection));
    if(mysqli_num_rows($result) == 1) {
      $spooner = mysqli_fetch_array($result);
      $name = $spooner['first'];
      if($spooner['last']) $name .= ' ' . $spooner['last'];
      return $name;
    } else {
      return NULL;
    }
  }
}

function getFirstNameByID($connection, $id) {
  $result = mysqli_query($connection, "SELECT first FROM spooners WHERE id = " . $id);
  $spooner = mysqli_fetch_array($result);
  return $spooner['first'];
}

function getTargetByID($connection, $id) {
  $result = mysqli_query($connection, "SELECT order_num FROM spooners WHERE id = " . $id);
  $spooner = mysqli_fetch_array($result);
  if($spooner['order_num'] == getHighestOrderNum()) {    // if last person in the list
    $result2 = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND order_num = " . getLowestOrderNum());
    $spooner2 = mysqli_fetch_array($result2);
    return $spooner2['id'];
  } else {
    $result2 = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND order_num > " . $spooner['order_num'] . " ORDER BY order_num ASC LIMIT 1");
    $spooner2 = mysqli_fetch_array($result2);
    return $spooner2['id'];
  }
}

function getReverseTargetByID($connection, $id) {    // aka get the person above the passed in person
  $result = mysqli_query($connection, "SELECT order_num FROM spooners WHERE id = " . $id);
  $spooner = mysqli_fetch_array($result);
  if($spooner['order_num'] == getLowestOrderNum()) {    // if first person in the list
    $result2 = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND order_num = " . getHighestOrderNum());
    $spooner2 = mysqli_fetch_array($result2);
    return $spooner2['id'];
  } else {
    $result2 = mysqli_query($connection, "SELECT id FROM spooners WHERE spooned = 0 AND order_num < " . $spooner['order_num'] . " ORDER BY order_num DESC LIMIT 1");
    $spooner2 = mysqli_fetch_array($result2);
    return $spooner2['id'];
  }
}

function checkSpoonedByID($connection, $id) {
  $result = mysqli_query($connection, "SELECT spooned FROM spooners WHERE id = " . $id);
  $spooner = mysqli_fetch_array($result);
  return $spooner['spooned'];
}

function spoonByID($connection, $id) {
  mysqli_query($connection, 'SET time_zone = "' . $timezone_number . '"');
  mysqli_query($connection, "UPDATE spooners SET spooned_by = " . getReverseTargetByID($id) . ", time_spooned = NOW(), spooned = 1, order_num = -1 WHERE id = " . $id);
}

function reviveByID($connection, $id) {
  mysqli_query($connection, "UPDATE spooners SET spooned = 0, order_num = " . (getHighestOrderNum() + 1) . " WHERE id = " . $id);
}

function getSpoonedByIDByID($connection, $id) {
  $result = mysqli_query($connection, "SELECT spooned_by FROM spooners WHERE id = " . $id);
  $spooner = mysqli_fetch_array($result);
  return $spooner['spooned_by'];
}

function getTimeSpoonedByID($connection, $id) {
  $result = mysqli_query($connection, "SELECT time_spooned FROM spooners WHERE id = " . $id);
  $spooner = mysqli_fetch_array($result);
  return $spooner['time_spooned'];
}

?>
