<?php
require __DIR__ . '/config.php';

if (!is_logged_in()) {
    redirect('login.php');
}