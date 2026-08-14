<?php
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();
unset($_SESSION['admin_id'], $_SESSION['admin_username']);
redirect(base_url('admin/login.php'));
