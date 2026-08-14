<?php
require_once __DIR__ . '/../includes/functions.php';
start_session_if_needed();
unset($_SESSION['customer_id'], $_SESSION['customer_name']);
redirect(base_url('index.php'));
