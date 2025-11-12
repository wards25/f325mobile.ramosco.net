<?php
session_start();

if ($_SESSION['admin'] == '1')
{
	echo "true";
}
else
{
	echo "false";
}

?>