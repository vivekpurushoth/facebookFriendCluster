<?php
    require 'facebook-php-sdk/src/facebook.php';

    //App credentials
    $facebook = new Facebook(array(
      'appId'  => '519964618015269',
      'secret' => '4e86167ecdb58878dfe2a78222cb460d',
    ));

    //Get User ID
    $user = $facebook->getUser();


    if ($user) {//If user is logged in then get his information along with the information of his friends
      try {
        //Trying to retrieve user information and his friends information via graph api and storing it in $user_friends
        $user = $facebook->api('/me?fields=id,name,birthday,education,gender,hometown,interests,location');
        $user_friends = $facebook->api('/me?fields=id,name,birthday,education,gender,hometown,interests,location,friends.fields(id,name,birthday,education,gender,hometown,interests,location)');
        $similar_users = getSimilarUsers($user,$user_friends);
        $clusters = create_gen_cluster($user_friends);
      } catch (FacebookApiException $e) {
        error_log($e);
        $user = null;
      }
    }


    //This function takes the data obtained from the facebook api and returns the 10 most similar users to the user logged in
    function getSimilarUsers($user,$user_friends) {
      foreach ($user_friends[friends][data] as &$value) {
        $value["similarity"] = getSimilarity($user,$value);
      }
      $similar_users = array();
      for($i=0; $i<10 ; $i++) {
        for($j=0; $j<count($user_friends[friends][data]); $j++) {
          if(isset($user_friends[friends][data][$j][similarity]) && isset($user_friends[friends][data][$j+1][similarity])){
            if($user_friends[friends][data][$j][similarity] > $user_friends[friends][data][$j+1][similarity]) {
              $temp = $user_friends[friends][data][$j];
              $user_friends[friends][data][$j] = $user_friends[friends][data][$j+1];
              $user_friends[friends][data][$j+1] = $temp;
            }
          }
        }
      }
      for($i=0; $i<10; $i++) {
        $similar_users[$i] = $user_friends[friends][data][count($user_friends[friends][data])-$i-1];
      }
      return $similar_users;
    }

    function getSimilarity($user,$friend) {
      $birthdaySimilarity = getBirthdaySimilarity($user,$friend);
      $hometownSimilarity = getHomeTownSimilarity($user,$friend);
      $locationSimilarity = getLocationSimilarity($user,$friend);
      $educationSimilarity = getEducationSimilarity($user,$friend);
      $interestsSimilarity = getInterestsSimilarity($user,$friend);
      $similarity = $birthdaySimilarity + $hometownSimilarity + $locationSimilarity + $educationSimilarity + $interestsSimilarity;
      $similarity = $similarity/5;
      return $similarity;
    }

    function getBirthdaySimilarity($user,$friend) {
      if(isset($user[birthday]) && isset($friend[birthday])){
        $bir1 = explode("/",$user[birthday]);
        $bir2 = explode("/",$friend[birthday]);
        if(isset($bir1[0]) && isset($bir2[0])) {
          if($bir1[0] == $bir2[0]){
            return 1;
          }
        }
      }
      return 0;
    }

    function getHomeTownSimilarity($user,$friend) {
      if(isset($user[hometown]) && isset($friend[hometown])) {
        if($user[hometown] == $friend[hometown]) {
          return 1;
        }
      }
      return 0;
    }

    function getLocationSimilarity($user,$friend) {
      if(isset($user[location]) && isset($friend[location])) {
        if($user[location] == $friend[location]) {
          return 1;
        }
      }
      return 0;
    }

    function getEducationSimilarity($user,$friend) {
      $total = 0.0;
      $sum = 1;
      if(isset($user[education]) && isset($friend[education])) {
        $sum = count($user[education]) + count($friend[education]);
        for($i = 0; $i < count($user[education]); $i++) {
          for($j = 0; $j < count($friend[education]); $j++) {
            if(strcasecmp($user[education][$i][school][name],$friend[education][$j][school][name]) == 0) { 
              $total = $total + 1;
            }
          }
        }
      }
      return $total/$sum;
    }

    function getInterestsSimilarity($user,$friend) {
      $total = 0.0;
      $sum = 1;
      if(isset($user[interests]) && isset($friend[interests])) {
        $sum = count($user[interests]) + count($friend[interests]);
        for($i = 0; $i < count($user[interests]); $i++) {
          for($j = 0; $j < count($friend[interests]); $j++) {
            if(strcasecmp($user[interests][data][$i][name],$friend[interests][data][$j][name]) == 0) { 
              $total = $total + 1;
            }
            else if(strpos(strtolower($user[interests][data][$i][name]), strtolower($friend[interests][data][$j][name])) !== false || strpos(strtolower($friend[interests][data][$j][name]),strtolower($user[interests][data][$i][name])) !== false) {
              $total = $total + 1;
            }
            else {
              similar_text(strtolower($user[interests][data][$i][name]), strtolower($friend[interests][data][$j][name]), $per);
              if($per > 50) {
                $total = $total + 1;
              }
            }
          }
        }
      }
      return $total/$sum;
    }

    function create_gen_cluster($user_friends) {
      $numberOfClusters = 5;
      $numbers = range(1, count($user_friends[friends][data])-1);
      shuffle($numbers);
      $initial = array_slice($numbers, 0, $numberOfClusters);
      $clustered = array();
      $unclustered = array();
      $finalClusters = array();
      for($i=0,$j=0,$k=0;$i<count($user_friends[friends][data])-1;$i++) {
        if(in_array($i, $initial)) {
          $clustered[$j] = $user_friends[friends][data][$i];
          $j = $j + 1;
        }
        else {
          $unclustered[$k] = $user_friends[friends][data][$i];
          $k = $k + 1;
        }
      }
      for($i=0; $i<count($clustered);$i++) {
        $finalClusters[$i][0]= $clustered[$i];
      }
      for($i = 0;$i < count($unclustered); $i++) {
        for($j = 0; $j < count($clustered); $j++) {
          $similarityScores[$j] = getSimilarity($unclustered[$i],$clustered[$j]);
        }
        $max = 0;
        $flag_max_changed = 0;
        for($k = 1; $k < count($clustered); $k++) {
          if($similarityScores[$max]<$similarityScores[$k]){
            $max = $k;
          }
        }
        if($max == 0){
          for($k = 1; $k < count($clustered); $k++) {
            if($similarityScores[$max]==$similarityScores[$k]){
              $flag_max_changed = 0;
            }
            else {
              $flag_max_changed = 1;
              break;
            }
          }
        }
        if($flag_max_changed) {
          $finalClusters[$max][count($finalClusters[$max])] = $unclustered[$i];
        }
        else {
          $randCluster = rand(0,$numberOfClusters-1);
          $finalClusters[$randCluster][count($finalClusters[$randCluster])] = $unclustered[$i];
        }
      }
      return $finalClusters;
    }

    //Login or logout url will be needed depending on current user state.
    if ($user) {
      $logoutUrl = $facebook->getLogoutUrl();
    } else {
      //$params array will contain all the permissions that the user has to allow in order to progress with clustering part of this application
      $params = array(
        'scope' => 'user_birthday,friends_birthday,user_education_history,friends_education_history,user_hometown,friends_hometown,user_interests,friends_interests,user_location,friends_location'
        );
      $loginUrl = $facebook->getLoginUrl($params);
    }
?>

<!doctype html>
<html lang="en" xmlns:fb="http://www.facebook.com/2008/fbml">
  <head>
    <meta charset="utf-8" />
    <title>Clustering friends|Facebook</title>
  </head>
  <body>
    <h1>Facebook friends clustering</h1>

    <?php if ($user): ?>
      <a href="<?php echo $logoutUrl; ?>">Logout</a>
    <?php else: ?>
      <div>
        <a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
        <br />
        Please login to find the top 10 similar users to you
      </div>
    <?php endif ?>

    <?php if ($user): ?>
      <h3>The top 10 similar facebook friends to you are</h3>
      <!--pre><?php// print_r($similar_users); ?></pre-->
      <?php foreach($similar_users as $value): ?>
        <p><?php print_r($value[name]) ?></p>
        <!--a href="https://facebook.com/<?php// echo $value[id] ?>" target="_blank"><img  src="https://graph.facebook.com/<?php// echo $value[id] ?>/picture?type=square" alt="<?php// echo $value[name]; ?>"></a-->
      <?php endforeach; ?>
      <h1> The <?php echo $numberOfClusters ?> clusters to your friends have been divided are </h1>
      <?php for($i=0;$i < count($clusters); $i++): ?>
      <h2> Cluster <?php echo $i ?> has <?php echo count($clusters[$i]) ?></h2>
      <?php for($j=0;$j < count($clusters[$i]);$j++): ?>
        <p><?php print_r($clusters[$i][$j][name]) ?></p>
      <?php endfor; ?>
      <?php endfor; ?>
    <?php endif ?>
  </body>
</html>