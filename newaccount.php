<?php
include('dbconnect.php');

$username = $_POST['username'];
$password = $_POST['password'];
$fname = $_POST['fname'];

if (isset($_POST['admin'])){$admin = $_POST['admin'];}else{$admin = '0';}

if (isset($_POST['semiadmin'])){$semiadmin = $_POST['semiadmin'];}else{$semiadmin = '0';}

if (isset($_POST['comp1'])){$comp1 = $_POST['comp1'];}else{$comp1 = '0';}

if (isset($_POST['comp2'])){$comp2 = $_POST['comp2'];}else{$comp2 = '0';}

if (isset($_POST['comp3'])){$comp3 = $_POST['comp3'];}else{$comp3 = '0';}

if (isset($_POST['comp4'])){$comp4 = $_POST['comp4'];}else{$comp4 = '0';}

if (isset($_POST['comp5'])){$comp5 = $_POST['comp5'];}else{$comp5 = '0';}

if (isset($_POST['comp6'])){$comp6 = $_POST['comp6'];}else{$comp6 = '0';}

if (isset($_POST['comp7'])){$comp7 = $_POST['comp7'];}else{$comp7 = '0';}

if (isset($_POST['comp8'])){$comp8 = $_POST['comp8'];}else{$comp8 = '0';}

if (isset($_POST['comp9'])){$comp9 = $_POST['comp9'];}else{$comp9 = '0';}

if (isset($_POST['comp10'])){$comp10 = $_POST['comp10'];}else{$comp10 = '0';}

if (isset($_POST['loc1'])){$loc1 = $_POST['loc1'];}else{$loc1 = '0';}

if (isset($_POST['loc2'])){$loc2 = $_POST['loc2'];}else{$loc2 = '0';}

if (isset($_POST['loc3'])){$loc3 = $_POST['loc3'];}else{$loc3 = '0';}

if (isset($_POST['loc4'])){$loc4 = $_POST['loc4'];}else{$loc4 = '0';}

if (isset($_POST['loc5'])){$loc5 = $_POST['loc5'];}else{$loc5 = '0';}

if (isset($_POST['loc6'])){$loc6 = $_POST['loc6'];}else{$loc6 = '0';}

if (isset($_POST['loc7'])){$loc7 = $_POST['loc7'];}else{$loc7 = '0';}

if (isset($_POST['loc8'])){$loc8 = $_POST['loc8'];}else{$loc8 = '0';}

if (isset($_POST['loc9'])){$loc9 = $_POST['loc9'];}else{$loc9 = '0';}

if (isset($_POST['loc10'])){$loc10 = $_POST['loc10'];}else{$loc10 = '0';}

if (isset($_POST['store'])){$store = $_POST['store'];}else{$store = '0';}

if (isset($_POST['inventory'])){$inventory = $_POST['inventory'];}else{$inventory = '0';}

if (isset($_POST['upload'])){$import = $_POST['upload'];}else{$import = '0';}

if (isset($_POST['importdop'])){$importdop = $_POST['importdop'];}else{$importdop = '0';}

if (isset($_POST['print'])){$print = $_POST['print'];}else{$print = '0';}

if (isset($_POST['schedule'])){$schedule = $_POST['schedule'];}else{$schedule = '0';}

if (isset($_POST['clearing'])){$clearing = $_POST['clearing'];}else{$clearing = '0';}

if (isset($_POST['manual'])){$manual = $_POST['manual'];}else{$manual = '0';}

if (isset($_POST['fordeduct'])){$fordeduct = $_POST['fordeduct'];}else{$fordeduct = '0';}

if (isset($_POST['borfapps'])){$borfapps = $_POST['borfapps'];}else{$borfapps = '0';}

if (isset($_POST['dmpiraw'])){$dmpiraw = $_POST['dmpiraw'];}else{$dmpiraw = '0';}

if (isset($_POST['deduction'])){$deduction = $_POST['deduction'];}else{$deduction = '0';}

if (isset($_POST['deductdoc'])){$deductdoc = $_POST['deductdoc'];}else{$deductdoc = '0';}

if (isset($_POST['paiddeduction'])){$paiddeduction = $_POST['paiddeduction'];}else{$paiddeduction = '0';}

if (isset($_POST['payment'])){$payment = $_POST['payment'];}else{$payment = '0';}

if (isset($_POST['rts'])){$rts = $_POST['rts'];}else{$rts = '0';}

if (isset($_POST['pulloutdoc'])){$pulloutdoc = $_POST['pulloutdoc'];}else{$pulloutdoc = '0';}

if (isset($_POST['report'])){$report = $_POST['report'];}else{$report = '0';}

if (isset($_POST['system'])){$system = $_POST['system'];}else{$system = '0';}

if (isset($_POST['active'])){$active = $_POST['active'];}else{$active = '0';}

// add new user in db user
mysqli_query($conn,"INSERT INTO dbuser(username,password,fname,admin,semiadmin,comp1,comp2,comp3,comp4,comp5,comp6,comp7,comp8,comp9,comp10,loc1,loc2,loc3,loc4,loc5,loc6,loc7,loc8,loc9,loc10,store,inventory,import,importdop,print,schedule,clearing,manual,fordeduct,borfapps,dmpiraw,deduction,deductdoc,paiddeduction,payment,returntosupplier,pulloutdoc,report,syssetting,active) VALUES ('$username','$password','$fname','$admin','$semiadmin','$comp1','$comp2','$comp3','$comp4','$comp5','$comp6','$comp7','$comp8','$comp9','$comp10','$loc1','$loc2','$loc3','$loc4','$loc5','$loc6','$loc7','$loc8','$loc9','$loc10','$store','$inventory','$import','$importdop','$print','$schedule','$clearing','$manual','$fordeduct','$borfapps','$dmpiraw','$deduction','$deductdoc','$paiddeduction','$payment','$rts','$pulloutdoc','$report','$system','$active')");

$conn->close();

//for debugging
// echo json_encode($_POST);
// exit;
?>