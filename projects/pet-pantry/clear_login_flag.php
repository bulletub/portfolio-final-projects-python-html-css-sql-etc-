<?php
session_start();
// Clear the just_logged_in flag after promo popup has been shown
unset($_SESSION['just_logged_in']);
echo json_encode(['success' => true]);
?>

