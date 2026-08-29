<?php
$quickStats = $quickStats ?? [];
?>
<div class="panel-header"><h2>Quick Stats</h2></div>
<div class="quick-stats-list">
    <?php foreach ($quickStats as $stat): ?>
    <div class="quick-stat">
        <span><?= htmlspecialchars($stat['label'] ?? '') ?></span>
        <strong><?= htmlspecialchars($stat['value'] ?? '0%') ?></strong>
    </div>
    <?php endforeach; ?>
</div>