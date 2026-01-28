<?php
require_once 'includes/functions.php';
init_session();
session_destroy();
redirect('login.php');
?>