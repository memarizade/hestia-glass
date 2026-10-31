<?php
/* Tile home page: every tool the account can use, grouped by category.
   Rendered by /home/ (web/home/index.php). $user and $panel are in scope. */
// $user in page templates is shell-quoted (main.php), so use the plain account name
$hx_account = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
$hx_nav = hx_nav($hx_account, $panel);
$hx_p = $panel[$hx_account];
$hx_admin = ($hx_nav["view"] === "admin");
$hx_name = !empty($hx_p["NAME"]) ? $hx_p["NAME"] : $hx_account;

// Quick stats shown above the tiles
if ($hx_admin) {
	$hx_stats = [
		["fa-users", _("Users"), $hx_p["U_USERS"]],
		["fa-globe", _("Web domains"), $hx_p["U_WEB_DOMAINS"]],
		["fa-database", _("Databases"), $hx_p["U_DATABASES"]],
		["fa-envelope", _("Mail accounts"), $hx_p["U_MAIL_ACCOUNTS"]],
	];
} else {
	$hx_stats = [
		["fa-globe", _("Web domains"), hx_count($hx_p["U_WEB_DOMAINS"], $hx_p["WEB_DOMAINS"])],
		["fa-database", _("Databases"), hx_count($hx_p["U_DATABASES"], $hx_p["DATABASES"])],
		["fa-envelope", _("Mail accounts"), hx_count($hx_p["U_MAIL_ACCOUNTS"], $hx_p["MAIL_ACCOUNTS"])],
		["fa-hard-drive", _("Disk used"), humanize_usage_size($hx_p["U_DISK"]) . " " . humanize_usage_measure($hx_p["U_DISK"])],
	];
}
?>
<div class="container hx-home">

	<header class="hx-hero">
		<div class="hx-hero-text">
			<p class="hx-kicker"><?= tohtml($hx_admin ? _("Server administration") : _("Hosting control panel")) ?></p>
			<h1 class="hx-title"><?= tohtml(_("Welcome")) ?>, <span><?= htmlspecialchars($hx_name) ?></span></h1>
			<p class="hx-sub"><i class="fas fa-server"></i> <?= htmlspecialchars(php_uname("n")) ?></p>
		</div>
		<label class="hx-search">
			<i class="fas fa-magnifying-glass"></i>
			<span class="u-hidden"><?= tohtml(_("Find a tool")) ?></span>
			<input type="search" id="hx-find" autocomplete="off" placeholder="<?= tohtml(_("Find a tool...")) ?>">
		</label>
	</header>

	<div class="hx-stats">
		<?php foreach ($hx_stats as $hx_stat) { ?>
			<div class="hx-stat">
				<span class="hx-stat-icon"><i class="fas <?= $hx_stat[0] ?>"></i></span>
				<div>
					<strong><?= htmlspecialchars((string) $hx_stat[2]) ?></strong>
					<small><?= tohtml($hx_stat[1]) ?></small>
				</div>
			</div>
		<?php } ?>
	</div>

	<?php foreach ($hx_nav["categories"] as $hx_id => $hx_cat) { ?>
		<section class="hx-section" data-section="<?= $hx_id ?>">
			<header class="hx-section-head">
				<span class="hx-section-icon"><i class="fas <?= $hx_cat["icon"] ?>"></i></span>
				<div>
					<h2><?= tohtml($hx_cat["label"]) ?></h2>
					<p><?= tohtml($hx_cat["desc"]) ?></p>
				</div>
			</header>
			<div class="hx-tiles">
				<?php foreach ($hx_cat["items"] as $hx_item) { ?>
					<a href="<?= tohtml($hx_item["url"]) ?>" class="hx-tile" data-find="<?= tohtml(mb_strtolower($hx_item["label"] . " " . $hx_item["desc"])) ?>">
						<span class="hx-tile-icon"><i class="fas <?= $hx_item["icon"] ?>"></i></span>
						<span class="hx-tile-text">
							<strong><?= tohtml($hx_item["label"]) ?></strong>
							<small><?= tohtml($hx_item["desc"]) ?></small>
						</span>
						<?php if ($hx_item["count"] !== null) { ?>
							<span class="hx-tile-count"><?= tohtml($hx_item["count"]) ?></span>
						<?php } ?>
					</a>
				<?php } ?>
			</div>
		</section>
	<?php } ?>

	<p class="hx-empty" id="hx-empty" hidden><?= tohtml(_("No tools match your search.")) ?></p>
</div>

<script>
	(function () {
		var input = document.getElementById("hx-find");
		if (!input) return;
		var tiles = document.querySelectorAll(".hx-tile");
		var sections = document.querySelectorAll(".hx-section");
		var empty = document.getElementById("hx-empty");
		input.addEventListener("input", function () {
			var q = input.value.trim().toLowerCase();
			var shown = 0;
			tiles.forEach(function (t) {
				var match = !q || t.getAttribute("data-find").indexOf(q) !== -1;
				t.hidden = !match;
				if (match) shown++;
			});
			sections.forEach(function (s) {
				s.hidden = !s.querySelector(".hx-tile:not([hidden])");
			});
			empty.hidden = shown !== 0;
		});
	})();
</script>
