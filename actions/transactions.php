<?php
require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/functions.php';
if(empty($_SESSION['user_id']))redirect('../index.php?page=login');
verify_csrf();$uid=(int)$_SESSION['user_id'];$action=$_POST['action']??'';
if($action==='delete'){
 $stmt=$pdo->prepare("DELETE FROM transactions WHERE transaction_id=? AND user_id=?");$stmt->execute([(int)$_POST['id'],$uid]);flash('Transaction deleted.');redirect('../index.php?page=transactions');
}
$type=$_POST['type']??'';$amount=(float)($_POST['amount']??0);$date=$_POST['trans_date']??'';$cat=(int)($_POST['category_id']??0);$note=trim($_POST['note']??'');
if(!in_array($type,['income','expense'],true)||$amount<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$cat<=0){flash('Please enter valid transaction details.','danger');redirect('../index.php?page=transactions');}
$check=$pdo->prepare("SELECT category_id FROM categories WHERE category_id=? AND user_id=?");$check->execute([$cat,$uid]);
if(!$check->fetch()){flash('Please choose a valid category.','danger');redirect('../index.php?page=transactions');}
if($action==='update'){
 $stmt=$pdo->prepare("UPDATE transactions SET type=?,amount=?,category_id=?,trans_date=?,note=? WHERE transaction_id=? AND user_id=?");
 $stmt->execute([$type,$amount,$cat,$date,$note,(int)$_POST['id'],$uid]);flash('Transaction updated.');
}else{
 $stmt=$pdo->prepare("INSERT INTO transactions(user_id,category_id,type,amount,trans_date,note) VALUES(?,?,?,?,?,?)");
 $stmt->execute([$uid,$cat,$type,$amount,$date,$note]);flash('Transaction added.');
}
redirect('../index.php?page=transactions');
