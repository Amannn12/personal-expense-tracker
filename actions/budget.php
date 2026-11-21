<?php
require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/functions.php';
if(empty($_SESSION['user_id']))redirect('../index.php?page=login');
verify_csrf();$month=$_POST['month_year']??'';$amount=(float)($_POST['budget_amount']??0);
if(!preg_match('/^\d{4}-\d{2}$/',$month)||$amount<=0){flash('Enter a valid month and budget amount.','danger');redirect('../index.php?page=budget');}
$stmt=$pdo->prepare("INSERT INTO budgets(user_id,month_year,budget_amount) VALUES(?,?,?) ON DUPLICATE KEY UPDATE budget_amount=VALUES(budget_amount)");
$stmt->execute([(int)$_SESSION['user_id'],$month,$amount]);flash('Monthly budget saved.');redirect('../index.php?page=budget&month='.$month);
