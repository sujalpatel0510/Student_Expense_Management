<?php
/**
 * Navigation Sidebar Component
 * Student Expense Tracker
 */
$baseUrl = get_base_url();
$activePage = $activePage ?? '';
$user = current_student();
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <i class="bi bi-wallet-fill text-primary"></i>
        <span>ExpenseTracker</span>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-heading">Core</li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/dashboard.php" class="sidebar-link <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="sidebar-heading">Transactions</li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/expenses/view.php" class="sidebar-link <?= ($activePage === 'expenses' || $activePage === 'expenses_view') ? 'active' : '' ?>">
                <i class="bi bi-arrow-up-right-circle text-danger"></i>
                <span>Expenses</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/expenses/add.php" class="sidebar-link <?= ($activePage === 'expenses_add') ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i>
                <span>Add Expense</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/income/view.php" class="sidebar-link <?= ($activePage === 'income' || $activePage === 'income_view') ? 'active' : '' ?>">
                <i class="bi bi-arrow-down-left-circle text-success"></i>
                <span>Income</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/income/add.php" class="sidebar-link <?= ($activePage === 'income_add') ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i>
                <span>Add Income</span>
            </a>
        </li>

        <li class="sidebar-heading">Planning &amp; Analysis</li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/budgets/view.php" class="sidebar-link <?= ($activePage === 'budgets') ? 'active' : '' ?>">
                <i class="bi bi-piggy-bank"></i>
                <span>Monthly Budget</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/budgets/manage.php" class="sidebar-link <?= ($activePage === 'budgets_manage') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i>
                <span>Set Budget</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/reports/index.php" class="sidebar-link <?= ($activePage === 'reports') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reports &amp; History</span>
            </a>
        </li>

        <li class="sidebar-heading">Account</li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/profile.php" class="sidebar-link <?= ($activePage === 'profile') ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="<?= $baseUrl ?>/logout.php" class="sidebar-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($user['name'] ?? 'S', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-white small fw-bold text-truncate"><?= htmlspecialchars($user['name'] ?? 'Student') ?></div>
                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>
        </div>
    </div>
</aside>
