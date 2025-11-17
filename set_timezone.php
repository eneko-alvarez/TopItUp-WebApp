<?php
session_start();
if (isset($_POST['timezone'])) {
    $_SESSION['user_timezone'] = $_POST['timezone'];
}
