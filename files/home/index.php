<?php
$TAB = "HOME";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Render page
render_page($user, $TAB, "home");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
