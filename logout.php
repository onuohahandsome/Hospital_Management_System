<?php
session_start();
session_unset();
session_destroy();
header("Location: /Hospital_Management_System/index.php");
exit();