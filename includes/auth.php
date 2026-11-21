<?php
function is_logged_in() { return !empty($_SESSION['user_id']); }
function require_login() {
    if (!is_logged_in()) redirect('../index.php?page=login');
}
