<?php $logged = function_exists('is_logged_in') && is_logged_in(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>ExpenseWise | Personal Expense Tracker</title><link rel="stylesheet" href="assets/css/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head><body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">₹</span><span>Expense<span class="brand-accent">Wise</span><small>PERSONAL FINANCE</small></span></a>
<?php if($logged): ?><nav><a href="index.php?page=dashboard">Dashboard</a><a href="index.php?page=transactions">Transactions</a><a href="index.php?page=budget">Budget</a></nav><div class="user-area"><span><?= htmlspecialchars($_SESSION['user_name']??'User') ?></span><a class="logout" href="actions/logout.php">Logout</a></div><?php endif; ?></header>
<main class="<?= $logged?'container':'auth-container' ?>">
