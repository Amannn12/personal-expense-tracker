<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';
verify_csrf();
$action=$_POST['action']??'';
if($action==='register'){
    $name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$password=$_POST['password']??'';
    if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<6){flash('Enter valid details. Password must contain at least 6 characters.','danger');redirect('../index.php?page=register');}
    $check=$pdo->prepare("SELECT user_id FROM users WHERE email=?");$check->execute([$email]);
    if($check->fetch()){flash('An account with this email already exists.','danger');redirect('../index.php?page=register');}
    $stmt=$pdo->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'user')");
    $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);$id=(int)$pdo->lastInsertId();
    $seed=$pdo->prepare("INSERT INTO categories(user_id,category_name) VALUES (?,?)");
    foreach(['Food','Travel','Education','Bills','Shopping','Health','Salary','Other'] as $c)$seed->execute([$id,$c]);
    session_regenerate_id(true);$_SESSION['user_id']=$id;$_SESSION['user_name']=$name;flash('Your account has been created.');redirect('../index.php?page=dashboard');
}
if($action==='login'){
    $email=strtolower(trim($_POST['email']??''));$password=$_POST['password']??'';
    $stmt=$pdo->prepare("SELECT user_id,name,password_hash FROM users WHERE email=?");$stmt->execute([$email]);$user=$stmt->fetch();
    if(!$user||!password_verify($password,$user['password_hash'])){flash('Incorrect email or password.','danger');redirect('../index.php?page=login');}
    session_regenerate_id(true);$_SESSION['user_id']=(int)$user['user_id'];$_SESSION['user_name']=$user['name'];flash('You are now signed in.');redirect('../index.php?page=dashboard');
}
redirect('../index.php?page=login');
