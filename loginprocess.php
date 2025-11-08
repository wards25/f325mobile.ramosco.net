<?php
session_start();
include_once 'dbconnect.php';

date_default_timezone_set("Asia/Manila");
$dateprocessed = date("Y-m-d");
$timeprocessed = date("H:i:s");

if(isset($_POST['btn-login']))
{
  $username = $_POST['username'];
  $password = $_POST['pass'];

  $user = mysqli_escape_string($conn,$username);
  $pass = mysqli_escape_string($conn,$password);

  // check login
  $check_login = mysqli_query($conn,"SELECT * FROM dbuser WHERE username='$user' AND password='$pass' ");
  $fetch_login = mysqli_fetch_array($check_login);

if (is_array($fetch_login))
{
  if ($fetch_login['active'] == 1)
  {
    if(!empty($_POST['remember'])) {
    setcookie ('username',$_POST['username'],time()+ 3600);
    setcookie ('pass',$_POST['pass'],time()+ 3600);
    } else {
      setcookie('username',"");
      setcookie('pass',"");
    }
        $_SESSION['id'] = $fetch_login['id'];
        $_SESSION['username'] = $fetch_login['username'];
        $_SESSION['fname'] = $fetch_login['fname'];
        $_SESSION['admin'] = $fetch_login['admin'];
        $_SESSION['semiadmin'] = $fetch_login['semiadmin'];
        $_SESSION['comp1'] = $fetch_login['comp1'];
        $_SESSION['comp2'] = $fetch_login['comp2'];
        $_SESSION['comp3'] = $fetch_login['comp3'];
        $_SESSION['comp4'] = $fetch_login['comp4'];
        $_SESSION['comp5'] = $fetch_login['comp5'];
        $_SESSION['comp6'] = $fetch_login['comp6'];
        $_SESSION['comp7'] = $fetch_login['comp7'];
        $_SESSION['comp8'] = $fetch_login['comp8'];
        $_SESSION['comp9'] = $fetch_login['comp9'];
        $_SESSION['comp10'] = $fetch_login['comp10'];
        $_SESSION['loc1'] = $fetch_login['loc1'];
        $_SESSION['loc2'] = $fetch_login['loc2'];
        $_SESSION['loc3'] = $fetch_login['loc3'];
        $_SESSION['loc4'] = $fetch_login['loc4'];
        $_SESSION['loc5'] = $fetch_login['loc5'];
        $_SESSION['loc6'] = $fetch_login['loc6'];
        $_SESSION['loc7'] = $fetch_login['loc7'];
        $_SESSION['loc8'] = $fetch_login['loc8'];
        $_SESSION['loc9'] = $fetch_login['loc9'];
        $_SESSION['loc10'] = $fetch_login['loc10'];
        $_SESSION['store'] = $fetch_login['store'];
        $_SESSION['inventory'] = $fetch_login['inventory'];
        $_SESSION['import'] = $fetch_login['import'];
        $_SESSION['importdop'] = $fetch_login['importdop'];
        $_SESSION['print'] = $fetch_login['print'];
        $_SESSION['schedule'] = $fetch_login['schedule'];
        $_SESSION['clearing'] = $fetch_login['clearing'];
        $_SESSION['manual'] = $fetch_login['manual'];
        $_SESSION['fordeduct'] = $fetch_login['fordeduct'];
        $_SESSION['borfapps'] = $fetch_login['borfapps'];
        $_SESSION['dmpiraw'] = $fetch_login['dmpiraw'];
        $_SESSION['deduction'] = $fetch_login['deduction'];
        $_SESSION['document'] = $fetch_login['deductdoc'];
        $_SESSION['paiddeduction'] = $fetch_login['paiddeduction'];
        $_SESSION['payment'] = $fetch_login['payment'];
        $_SESSION['returntosupplier'] = $fetch_login['returntosupplier'];
        $_SESSION['pulloutdoc'] = $fetch_login['pulloutdoc'];
        $_SESSION['report'] = $fetch_login['report'];
        $_SESSION['syssetting'] = $fetch_login['syssetting'];

        // insert login history
        $history_query = mysqli_query($conn,"INSERT INTO dbloginhistory(username,dateprocessed,timeprocessed) VALUES ('".$_SESSION['fname']."','$dateprocessed','$timeprocessed')");
        header("Location: loading.php");

  }
    else
    {
      $qstring = '?status=activate';
      header("Location: index.php".$qstring);
    }
  }
  else
  {
    $qstring = '?status=err';
    header("Location: index.php".$qstring);
  }
}
?> 