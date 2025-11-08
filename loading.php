<?php
session_start();
include_once("header.php");
include_once("dbconnect.php");

if(!isset($_SESSION['id']))
{
  header("Location: index.php");
}
$res=mysqli_query($conn,"SELECT * FROM dbuser WHERE id=".$_SESSION['id']);
$userRow=mysqli_fetch_array($res);
header("refresh:2.1;url=dashboard.php");
?>

<body style="background-color:#f8f9fc;">
  <center>
  <div class="d-flex align-items-center justify-content-center" style="height: 350px">

    <small class="h3 mb-0 text-gray-800">Login Successfully! &nbsp;</small>
    <div class="lds-facebook"><div></div><div></div><div></div></div>
  </div>

<style>
.lds-facebook {
  display: inline-block;
  position: relative;
  width: 70px;
  height: 70px;
}
.lds-facebook div {
  display: inline-block;
  position: absolute;
  left: 8px;
  width: 16px;
  background: #915c83;
  animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
}
.lds-facebook div:nth-child(1) {
  left: 8px;
  animation-delay: -0.24s;
}
.lds-facebook div:nth-child(2) {
  left: 32px;
  animation-delay: -0.12s;
}
.lds-facebook div:nth-child(3) {
  left: 56px;
  animation-delay: 0;
}
@keyframes lds-facebook {
  0% {
    top: 8px;
    height: 64px;
  }
  50%, 100% {
    top: 24px;
    height: 32px;
  }
}
</style>

<script type="text/javascript">
  var su = new SpeechSynthesisUtterance();
  su.lang = "en";
  su.text = "Welcome To F 3 2 5 Mobile App, '<?php echo $_SESSION['fname']; ?>'!";
  speechSynthesis.speak(su);
</script>

<footer class="sticky-footer bg-white fixed-bottom">
  <div class="container my-auto">
    <div class="copyright text-center my-auto">
      <?php
        $year = date("Y");
      ?>
    <span>&copy; 2023 - <?php echo $year; ?> RGC BO App | Developed by RGC IT</span>
    </div>
  </div>
</footer>
  </center>                  