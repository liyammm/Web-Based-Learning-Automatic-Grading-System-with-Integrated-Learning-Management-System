<?php

require_once $_SERVER['DOCUMENT_ROOT'] .'/FinalProj/db.php';

session_start();
session_unset();
session_destroy();
header('Location: login.php');

?>