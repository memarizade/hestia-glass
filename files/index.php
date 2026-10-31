<?php
session_start();
header("Location: /" . (isset($_SESSION["user"]) ? "home" : "login") . "/");
