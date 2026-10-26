<?php
/**
 * Student Logout
 * Student Expense Tracker
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
set_flash('info', 'You have been safely logged out. See you soon!');
redirect(get_base_url() . '/login.php');
