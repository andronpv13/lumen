<?php
// moderator.php — перенаправление на admin.php с теми же правами
require_once __DIR__ . '/includes/functions.php';
require_role(['moderator']);
header('Location: /admin.php');