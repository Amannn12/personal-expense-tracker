<?php
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($amount) { return '₹' . number_format((float)$amount, 2); }
function greeting() { $h=(int)date('G'); return $h<12?'morning':($h<17?'afternoon':'evening'); }
function csrf_field() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf_token" value="'.e($_SESSION['csrf_token']).'">';
}
function verify_csrf() {
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403); exit('Invalid request token. Please go back and try again.');
    }
}
function flash($message, $type='success') { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f=$_SESSION['flash']; echo '<div class="alert '.e($f['type']).'">'.e($f['message']).'</div>';
        unset($_SESSION['flash']);
    }
}
function redirect($url) { header('Location: '.$url); exit; }
