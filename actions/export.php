<?php
require_once __DIR__.'/../config/db.php';
if(empty($_SESSION['user_id'])){header('Location: ../index.php?page=login');exit;}
$stmt=$pdo->prepare("SELECT t.trans_date,t.type,t.amount,c.category_name,t.note FROM transactions t LEFT JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? ORDER BY t.trans_date DESC");
$stmt->execute([(int)$_SESSION['user_id']]);
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="expense-transactions.csv"');
$out=fopen('php://output','w');fputcsv($out,['Date','Type','Amount','Category','Note']);
while($row=$stmt->fetch(PDO::FETCH_ASSOC))fputcsv($out,$row);fclose($out);exit;
