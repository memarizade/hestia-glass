<?php
/**
 * Panel navigation model.
 *
 * One source of truth for the sidebar (templates/includes/sidebar.php) and the
 * tile home page (templates/pages/home.php). Visibility rules mirror the original
 * top bar and main menu, so nothing is shown that the account cannot use.
 *
 * Admin view (not impersonating): Accounts, Hosting, Server, Tools, Account
 * User view (customer or impersonated account): Files, Domains, Email, Databases, Advanced, Account
 */

function hx_count($used, $limit) {
	$lim = $limit === "unlimited" ? "∞" : $limit;
	return $used . " / " . $lim;
}

function hx_item($cat, $tabs, $label, $url, $icon, $desc, $count = null) {
	return [
		"cat" => $cat,
		"tabs" => $tabs,
		"label" => $label,
		"url" => $url,
		"icon" => $icon,
		"desc" => $desc,
		"count" => $count,
	];
}

function hx_categories($adminView) {
	if ($adminView) {
		return [
			"accounts" => ["label" => _("Accounts"), "icon" => "fa-users", "desc" => _("Customers, plans and access")],
			"hosting" => ["label" => _("Hosting"), "icon" => "fa-globe", "desc" => _("Websites, DNS, mail and databases of your own account")],
			"server" => ["label" => _("Server"), "icon" => "fa-server", "desc" => _("Services, network, security and monitoring")],
			"tools" => ["label" => _("Tools"), "icon" => "fa-toolbox", "desc" => _("Files, terminal and statistics")],
			"account" => ["label" => _("Account"), "icon" => "fa-circle-user", "desc" => _("Your profile and keys")],
		];
	}
	return [
		"files" => ["label" => _("Files"), "icon" => "fa-folder-open", "desc" => _("Files, backups and your data")],
		"domains" => ["label" => _("Domains"), "icon" => "fa-globe", "desc" => _("Websites and DNS zones")],
		"email" => ["label" => _("Email"), "icon" => "fa-envelope", "desc" => _("Mail domains and accounts")],
		"databases" => ["label" => _("Databases"), "icon" => "fa-database", "desc" => _("MySQL and MariaDB databases")],
		"advanced" => ["label" => _("Advanced"), "icon" => "fa-screwdriver-wrench", "desc" => _("Cron jobs, terminal and statistics")],
		"account" => ["label" => _("Account"), "icon" => "fa-circle-user", "desc" => _("Your profile and keys")],
	];
}

/**
 * Returns ["categories" => [...], "items" => [...], "view" => "admin"|"user"]
 * where categories is an ordered map id => meta, with "items" filled and empty
 * categories removed.
 */
function hx_nav($user, $panel) {
	$p = $panel[$user];
	$ctx = $_SESSION["userContext"];
	$look = $_SESSION["look"] ?? "";
	$adminView = ($ctx === "admin" && $look === "");
	$isRoot = ($user === $_SESSION["ROOT_USER"]);
	$hideTools = ($ctx === "admin" && $look === $_SESSION["ROOT_USER"] && ($_SESSION["POLICY_SYSTEM_PROTECTED_ADMIN"] ?? "") == "yes");
	$has = fn($key) => isset($p[$key]) && $p[$key] !== "0";
	$sys = fn($key) => !empty($_SESSION[$key]);

	// Category used for hosting items depends on the view
	$hostCat = fn($adminCat, $userCat) => $adminView ? $adminCat : $userCat;

	$items = [];

	// Hosting resources (also the customer's main workspace)
	if ($sys("WEB_SYSTEM") && $has("WEB_DOMAINS")) {
		$items[] = hx_item($hostCat("hosting", "domains"), ["WEB"], _("Web Domains"), "/list/web/", "fa-globe",
			_("Websites, aliases and SSL certificates"), hx_count($p["U_WEB_DOMAINS"], $p["WEB_DOMAINS"]));
	}
	if ($sys("DNS_SYSTEM") && $has("DNS_DOMAINS")) {
		$items[] = hx_item($hostCat("hosting", "domains"), ["DNS"], _("DNS Zones"), "/list/dns/", "fa-sitemap",
			_("Zones and records for your domains"), hx_count($p["U_DNS_DOMAINS"], $p["DNS_DOMAINS"]));
	}
	if ($sys("MAIL_SYSTEM") && $has("MAIL_DOMAINS")) {
		$items[] = hx_item($hostCat("hosting", "email"), ["MAIL"], _("Mailboxes"), "/list/mail/", "fa-envelope",
			_("Mail domains, mailboxes and forwarders"), hx_count($p["U_MAIL_ACCOUNTS"], $p["MAIL_ACCOUNTS"]));
	}
	if ($sys("DB_SYSTEM") && $has("DATABASES")) {
		$items[] = hx_item($hostCat("hosting", "databases"), ["DB"], _("MySQL Databases"), "/list/db/", "fa-database",
			_("MySQL and MariaDB databases and users"), hx_count($p["U_DATABASES"], $p["DATABASES"]));
	}
	if ($sys("CRON_SYSTEM") && $has("CRON_JOBS")) {
		$items[] = hx_item($hostCat("hosting", "advanced"), ["CRON"], _("Cron Jobs"), "/list/cron/", "fa-clock",
			_("Scheduled tasks"), hx_count($p["U_CRON_JOBS"], $p["CRON_JOBS"]));
	}
	if ($sys("BACKUP_SYSTEM") && ($has("BACKUPS") || $p["U_BACKUPS"] != "0" || ($p["BACKUPS_INCREMENTAL"] ?? "") == "yes")) {
		$items[] = hx_item($hostCat("hosting", "files"), ["BACKUP"], _("Backups"), "/list/backup/", "fa-box-archive",
			_("Create and restore account backups"), hx_count($p["U_BACKUPS"], $p["BACKUPS"]));
	}

	// Tools
	if (!$hideTools && ($_SESSION["FILE_MANAGER"] ?? "") === "true") {
		$items[] = hx_item($hostCat("tools", "files"), ["FM"], _("File Manager"), "/fm/", "fa-folder-open",
			_("Browse, upload and edit files"));
	}
	if (!$hideTools && ($_SESSION["WEB_TERMINAL"] ?? "") === "true") {
		$items[] = hx_item($hostCat("tools", "advanced"), ["TERMINAL"], _("Web Terminal"), "/list/terminal/", "fa-terminal",
			_("Command line in your browser"));
	}
	$items[] = hx_item($hostCat("tools", "advanced"), ["STATS"], _("Statistics"), "/list/stats/", "fa-chart-line",
		_("Traffic and resource usage"));

	// Admin only
	if ($adminView) {
		$userCount = $p["U_USERS"];
		if (!$isRoot && ($_SESSION["POLICY_SYSTEM_HIDE_ADMIN"] ?? "") === "yes") {
			$userCount = $userCount - 1;
		}
		$items[] = hx_item("accounts", ["USER"], _("Users"), "/list/user/", "fa-users",
			_("Create and manage user accounts"), (string) $userCount);
		$items[] = hx_item("accounts", ["PACKAGE"], _("Packages"), "/list/package/", "fa-boxes-stacked",
			_("Resource plans assigned to accounts"));

		if (($ctx === "admin" && ($_SESSION["POLICY_SYSTEM_HIDE_SERVICES"] ?? "") !== "yes") || $isRoot) {
			$items[] = hx_item("server", ["SERVER", "CPU", "MEMORY", "DISK", "NETWORK", "WEB", "DNS", "MAIL", "DB"], _("Services"), "/list/server/", "fa-gears",
				_("Status and control of system services"));
			$items[] = hx_item("server", ["EDIT_SERVER"], _("Server Settings"), "/edit/server/", "fa-sliders",
				_("Defaults, mail, PHP, database and web server settings"));
		}
		$items[] = hx_item("server", ["UPDATES"], _("Updates"), "/list/updates/", "fa-cloud-arrow-down",
			_("System package updates"));
		$items[] = hx_item("server", ["IP"], _("IP Addresses"), "/list/ip/", "fa-network-wired",
			_("Server IP addresses and assignments"));
		$items[] = hx_item("server", ["FIREWALL"], _("Firewall"), "/list/firewall/", "fa-shield-halved",
			_("Firewall rules, bans and blocked IPs"));
		$items[] = hx_item("server", ["RRD"], _("Monitoring"), "/list/rrd/", "fa-gauge-high",
			_("CPU, memory, disk and network graphs"));
		$items[] = hx_item("server", ["LOG"], _("Logs"), "/list/log/", "fa-file-lines",
			_("System and panel logs"));
	}

	// Account (every view)
	$items[] = hx_item("account", ["PROFILE"], _("My Profile"),
		"/edit/user/?user=" . rawurlencode($user) . "&token=" . $_SESSION["token"], "fa-user-gear",
		_("Account details, password and two-factor"));
	$items[] = hx_item("account", ["USER"], _("SSH Keys"), "/list/key/", "fa-key",
		_("Keys for SSH and SFTP access"));
	// Same gate as list/access-key: numeric API level of 2 or more
	$apiLevel = is_numeric($_SESSION["API_SYSTEM"] ?? null) ? (int) $_SESSION["API_SYSTEM"] : 0;
	if ($apiLevel >= 2) {
		$items[] = hx_item("account", ["ACCESS"], _("API Access Keys"), "/list/access-key/", "fa-plug",
			_("Keys for automating the panel"));
	}

	// Group items into the categories of this view, keeping the defined order
	$cats = hx_categories($adminView);
	foreach ($cats as $id => $meta) {
		$cats[$id]["items"] = [];
	}
	foreach ($items as $item) {
		if (isset($cats[$item["cat"]])) {
			$cats[$item["cat"]]["items"][] = $item;
		}
	}
	foreach ($cats as $id => $meta) {
		if (empty($meta["items"])) {
			unset($cats[$id]);
		}
	}

	return ["view" => $adminView ? "admin" : "user", "categories" => $cats];
}
