<?php
/* Sidebar navigation (cPanel / DirectAdmin style), grouped by category.
   Included from panel.php, so $user and $panel are in scope. */
$hx_nav = hx_nav($user, $panel);
$hx_path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$hx_is_active = function ($url) use ($hx_path) {
	$base = rtrim(parse_url($url, PHP_URL_PATH), "/") . "/";
	return strpos($hx_path, $base) === 0;
};
?>
<aside class="hx-sidebar" :class="{ 'is-open': navOpen }" aria-label="<?= tohtml(_("Main navigation")) ?>">

	<a href="/home/" class="hx-sidebar-home <?php if ($hx_is_active("/home/")) { echo "active"; } ?>">
		<i class="fas fa-house"></i>
		<span><?= tohtml(_("Home")) ?></span>
	</a>

	<?php foreach ($hx_nav["categories"] as $hx_id => $hx_cat) { ?>
		<section class="hx-group" data-group="<?= $hx_id ?>">
			<h2 class="hx-group-title">
				<i class="fas <?= $hx_cat["icon"] ?>"></i>
				<span><?= tohtml($hx_cat["label"]) ?></span>
			</h2>
			<ul class="hx-group-list">
				<?php foreach ($hx_cat["items"] as $hx_item) { ?>
					<li>
						<a href="<?= tohtml($hx_item["url"]) ?>" class="hx-link <?php if ($hx_is_active($hx_item["url"])) { echo "active"; } ?>" title="<?= tohtml($hx_item["desc"]) ?>">
							<i class="fas <?= $hx_item["icon"] ?>"></i>
							<span class="hx-link-label"><?= tohtml($hx_item["label"]) ?></span>
							<?php if ($hx_item["count"] !== null) { ?>
								<small class="hx-link-count"><?= tohtml($hx_item["count"]) ?></small>
							<?php } ?>
						</a>
					</li>
				<?php } ?>
			</ul>
		</section>
	<?php } ?>

</aside>
<div class="hx-backdrop" x-cloak x-show="navOpen" x-on:click="navOpen = false"></div>
