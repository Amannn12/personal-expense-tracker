<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$page = $_GET['page'] ?? (is_logged_in() ? 'dashboard' : 'login');
$allowed = ['login', 'register', 'dashboard', 'transactions', 'budget'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
if (!is_logged_in() && !in_array($page, ['login', 'register'], true)) $page = 'login';
if (is_logged_in() && in_array($page, ['login', 'register'], true)) $page = 'dashboard';

include __DIR__ . '/includes/header.php';

if ($page === 'login') {
?>
<section class="auth-wrap">
  <div class="auth-card">
    <span class="eyebrow">WELCOME BACK</span><h1>Sign in to your account</h1>
    <p class="muted">Track your money with clarity.</p>
    <?php show_flash(); ?>
    <form method="post" action="actions/auth.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="login">
      <label>Email address</label><input type="email" name="email" required placeholder="you@example.com">
      <label>Password</label><input type="password" name="password" required placeholder="Enter your password">
      <button class="btn primary full">Sign in</button>
    </form>
    <p class="auth-foot">New here? <a href="index.php?page=register">Create an account</a></p>
  </div>
</section>
<?php
} elseif ($page === 'register') {
?>
<section class="auth-wrap">
  <div class="auth-card">
    <span class="eyebrow">GET STARTED</span><h1>Create your account</h1>
    <p class="muted">A simple way to understand your spending.</p>
    <?php show_flash(); ?>
    <form method="post" action="actions/auth.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="register">
      <label>Full name</label><input name="name" required maxlength="80" placeholder="Your name">
      <label>Email address</label><input type="email" name="email" required placeholder="you@example.com">
      <label>Password</label><input type="password" name="password" minlength="6" required placeholder="At least 6 characters">
      <button class="btn primary full">Create account</button>
    </form>
    <p class="auth-foot">Already registered? <a href="index.php?page=login">Sign in</a></p>
  </div>
</section>
<?php
} else {
$userId = (int)$_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'User';
$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

if ($page === 'dashboard') {
  $stmt = $pdo->prepare("SELECT
    COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) income,
    COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) expense
    FROM transactions WHERE user_id=? AND trans_date BETWEEN ? AND ?");
  $stmt->execute([$userId,$start,$end]); $totals = $stmt->fetch();
  $income=(float)$totals['income']; $expense=(float)$totals['expense']; $balance=$income-$expense;
  $b=$pdo->prepare("SELECT budget_amount FROM budgets WHERE user_id=? AND month_year=?");
  $b->execute([$userId,$month]); $budget=(float)($b->fetchColumn() ?: 0);
  $percent=$budget>0 ? min(100,($expense/$budget)*100) : 0;
  $recent=$pdo->prepare("SELECT t.*, c.category_name FROM transactions t LEFT JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? ORDER BY t.trans_date DESC,t.transaction_id DESC LIMIT 6");
  $recent->execute([$userId]); $recentRows=$recent->fetchAll();
  $cat=$pdo->prepare("SELECT c.category_name, SUM(t.amount) total FROM transactions t JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=? AND t.type='expense' AND t.trans_date BETWEEN ? AND ? GROUP BY c.category_id ORDER BY total DESC");
  $cat->execute([$userId,$start,$end]); $categoryRows=$cat->fetchAll();
?>
<div class="page-head"><div><span class="eyebrow">OVERVIEW</span><h1>Good <?= htmlspecialchars(greeting()) ?>, <?= htmlspecialchars(explode(' ',$name)[0]) ?>.</h1><p class="muted">Here is your financial snapshot for this month.</p></div>
<form class="month-form" method="get"><input type="hidden" name="page" value="dashboard"><input type="month" name="month" value="<?= htmlspecialchars($month) ?>"><button class="btn light">View</button></form></div>
<?php show_flash(); ?>
<div class="stats-grid">
  <article class="stat-card"><span class="stat-label">Total income</span><strong class="positive"><?= money($income) ?></strong><span class="stat-note">Money received this month</span></article>
  <article class="stat-card"><span class="stat-label">Total expenses</span><strong class="negative"><?= money($expense) ?></strong><span class="stat-note">Money spent this month</span></article>
  <article class="stat-card"><span class="stat-label">Net balance</span><strong><?= money($balance) ?></strong><span class="stat-note">Income minus expenses</span></article>
  <article class="stat-card"><span class="stat-label">Monthly budget</span><strong><?= $budget ? money($budget) : 'Not set' ?></strong><span class="stat-note">Your spending limit</span></article>
</div>
<div class="content-grid">
  <section class="panel budget-panel"><div class="panel-title"><div><h2>Monthly budget</h2><p class="muted"><?= date('F Y',strtotime($start)) ?></p></div><a href="index.php?page=budget">Manage</a></div>
  <?php if ($budget > 0): ?>
    <div class="budget-numbers"><strong><?= money($expense) ?></strong><span>of <?= money($budget) ?></span></div>
    <div class="progress"><span style="width:<?= $percent ?>%"></span></div>
    <p class="muted"><?= money(max(0,$budget-$expense)) ?> remaining</p>
    <?php if ($expense >= $budget): ?><div class="alert danger">Budget exceeded. Review your spending.</div>
    <?php elseif ($percent >= 80): ?><div class="alert warning">You have used <?= round($percent) ?>% of your monthly budget.</div>
    <?php else: ?><div class="alert success">You are within your monthly budget.</div><?php endif; ?>
  <?php else: ?><p class="empty">You have not set a budget for this month.</p><a class="btn primary" href="index.php?page=budget">Set monthly budget</a><?php endif; ?>
  </section>
  <section class="panel chart-panel"><div class="panel-title"><div><h2>Spending by category</h2><p class="muted">Expense distribution</p></div></div>
    <?php if ($categoryRows): ?><canvas id="categoryChart"></canvas><?php else: ?><p class="empty">Add expenses to see your chart.</p><?php endif; ?>
  </section>
</div>
<section class="panel"><div class="panel-title"><div><h2>Recent transactions</h2><p class="muted">Your latest activity</p></div><a href="index.php?page=transactions">View all</a></div>
<?php if ($recentRows): ?><div class="table-wrap"><table><thead><tr><th>Transaction</th><th>Category</th><th>Date</th><th>Amount</th></tr></thead><tbody>
<?php foreach($recentRows as $r): ?><tr><td><?= htmlspecialchars($r['note'] ?: ucfirst($r['type'])) ?></td><td><?= htmlspecialchars($r['category_name'] ?? 'Uncategorised') ?></td><td><?= date('d M Y',strtotime($r['trans_date'])) ?></td><td class="<?= $r['type']==='income'?'positive':'negative' ?>"><?= $r['type']==='income'?'+':'-' ?><?= money($r['amount']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><p class="empty">No transactions yet. Add your first income or expense.</p><?php endif; ?></section>
<script>
const categoryData = <?= json_encode($categoryRows, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
</script>
<?php
} elseif ($page === 'transactions') {
  $type=$_GET['type']??''; $category=(int)($_GET['category']??0); $search=trim($_GET['search']??'');
  $sql="SELECT t.*,c.category_name FROM transactions t LEFT JOIN categories c ON c.category_id=t.category_id WHERE t.user_id=?"; $params=[$userId];
  if (in_array($type,['income','expense'],true)) {$sql.=" AND t.type=?";$params[]=$type;}
  if ($category>0) {$sql.=" AND t.category_id=?";$params[]=$category;}
  if ($search!=='') {$sql.=" AND (t.note LIKE ? OR c.category_name LIKE ?)";$params[]="%$search%";$params[]="%$search%";}
  $sql.=" ORDER BY t.trans_date DESC,t.transaction_id DESC";
  $stmt=$pdo->prepare($sql);$stmt->execute($params);$rows=$stmt->fetchAll();
  $cats=$pdo->prepare("SELECT * FROM categories WHERE user_id=? ORDER BY category_name");$cats->execute([$userId]);$categories=$cats->fetchAll();
  $edit=null;if(isset($_GET['edit'])){$e=$pdo->prepare("SELECT * FROM transactions WHERE transaction_id=? AND user_id=?");$e->execute([(int)$_GET['edit'],$userId]);$edit=$e->fetch() ?: null;}
?>
<div class="page-head"><div><span class="eyebrow">MONEY ACTIVITY</span><h1>Transactions</h1><p class="muted">Record and manage your income and expenses.</p></div></div>
<?php show_flash(); ?>
<div class="content-grid transaction-grid">
<section class="panel"><div class="panel-title"><h2><?= $edit?'Edit transaction':'Add transaction' ?></h2></div>
<form method="post" action="actions/transactions.php" class="form-grid">
<?= csrf_field() ?><input type="hidden" name="action" value="<?= $edit?'update':'add' ?>"><input type="hidden" name="id" value="<?= (int)($edit['transaction_id']??0) ?>">
<div class="field"><label>Type</label><select name="type" required><option value="expense" <?= ($edit['type']??'expense')==='expense'?'selected':'' ?>>Expense</option><option value="income" <?= ($edit['type']??'')==='income'?'selected':'' ?>>Income</option></select></div>
<div class="field"><label>Amount (₹)</label><input type="number" name="amount" min="0.01" step="0.01" required value="<?= htmlspecialchars((string)($edit['amount']??'')) ?>" placeholder="e.g. 500"></div>
<div class="field"><label>Category</label><select name="category_id" required><option value="">Choose category</option><?php foreach($categories as $c): ?><option value="<?= $c['category_id'] ?>" <?= (int)($edit['category_id']??0)===(int)$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Date</label><input type="date" name="trans_date" required value="<?= htmlspecialchars($edit['trans_date']??date('Y-m-d')) ?>"></div>
<div class="field full-field"><label>Note (optional)</label><input name="note" maxlength="180" value="<?= htmlspecialchars($edit['note']??'') ?>" placeholder="What was this for?"></div>
<div class="form-actions"><button class="btn primary"><?= $edit?'Save changes':'Add transaction' ?></button><?php if($edit): ?><a class="btn light" href="index.php?page=transactions">Cancel</a><?php endif; ?></div>
</form></section>
<section class="panel"><div class="panel-title"><h2>Quick tip</h2></div><p class="muted">Record transactions regularly to get more useful monthly summaries. Use a clear note so you can recognise each entry later.</p></section>
</div>
<section class="panel"><div class="panel-title"><div><h2>All transactions</h2><p class="muted"><?= count($rows) ?> record(s) found</p></div><a href="actions/export.php" class="btn light">Export CSV</a></div>
<form method="get" class="filter-row"><input type="hidden" name="page" value="transactions"><input name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search notes or category"><select name="type"><option value="">All types</option><option value="income" <?= $type==='income'?'selected':'' ?>>Income</option><option value="expense" <?= $type==='expense'?'selected':'' ?>>Expense</option></select><select name="category"><option value="0">All categories</option><?php foreach($categories as $c): ?><option value="<?= $c['category_id'] ?>" <?= $category===(int)$c['category_id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option><?php endforeach; ?></select><button class="btn light">Filter</button></form>
<?php if($rows): ?><div class="table-wrap"><table><thead><tr><th>Details</th><th>Category</th><th>Date</th><th>Amount</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?= htmlspecialchars($r['note'] ?: ucfirst($r['type'])) ?><small class="table-sub"><?= ucfirst($r['type']) ?></small></td><td><?= htmlspecialchars($r['category_name']??'—') ?></td><td><?= date('d M Y',strtotime($r['trans_date'])) ?></td><td class="<?= $r['type']==='income'?'positive':'negative' ?>"><?= $r['type']==='income'?'+':'-' ?><?= money($r['amount']) ?></td><td class="actions"><a href="index.php?page=transactions&edit=<?= $r['transaction_id'] ?>">Edit</a><form method="post" action="actions/transactions.php" onsubmit="return confirm('Delete this transaction?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['transaction_id'] ?>"><button class="link danger-text">Delete</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><p class="empty">No matching transactions found.</p><?php endif; ?></section>
<?php
} elseif ($page === 'budget') {
  $month=$_GET['month']??date('Y-m');if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
  $stmt=$pdo->prepare("SELECT budget_amount FROM budgets WHERE user_id=? AND month_year=?");$stmt->execute([$userId,$month]);$current=$stmt->fetchColumn();
  $sum=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='expense' AND DATE_FORMAT(trans_date,'%Y-%m')=?");$sum->execute([$userId,$month]);$spent=(float)$sum->fetchColumn();
?>
<div class="page-head"><div><span class="eyebrow">SPENDING LIMIT</span><h1>Monthly budget</h1><p class="muted">Set a spending limit and monitor your progress.</p></div></div><?php show_flash(); ?>
<div class="content-grid"><section class="panel"><h2>Set your budget</h2><form method="post" action="actions/budget.php" class="stack-form"><?= csrf_field() ?><label>Month</label><input type="month" name="month_year" value="<?= htmlspecialchars($month) ?>" required><label>Budget amount (₹)</label><input type="number" name="budget_amount" min="1" step="0.01" value="<?= htmlspecialchars((string)$current) ?>" required><button class="btn primary">Save budget</button></form></section>
<section class="panel"><h2><?= date('F Y',strtotime($month.'-01')) ?> summary</h2><div class="budget-numbers"><strong><?= money($spent) ?></strong><span>spent</span></div><p class="muted">Budget: <?= $current?money($current):'Not set' ?></p><?php if($current): ?><div class="progress"><span style="width:<?= min(100,($spent/(float)$current)*100) ?>%"></span></div><p><?= money(max(0,(float)$current-$spent)) ?> remaining</p><?php else: ?><p class="empty">Set a budget to see your remaining amount.</p><?php endif; ?></section></div>
<?php } ?>
<?php
}
include __DIR__ . '/includes/footer.php';
