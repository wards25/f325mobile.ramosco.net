<?php
session_start();
include_once 'dbconnect.php';

if(!isset($_SESSION['id']))
{
	header("Location: index.php");
}
else if(isset($_SESSION['id']))
{
	header("Location: dashboard.php");
}

if(isset($_GET['logout']))
{
	session_destroy();
	unset($_SESSION['id']);
	unset($_SESSION['username']);
	header("Location: index.php");
}
?>