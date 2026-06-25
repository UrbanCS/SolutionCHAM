<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/site.css', ['version' => 'auto', 'relative' => true]);

$minutesToHours = static fn ($minutes) => number_format(((int) $minutes) / 60, 2, ',', ' ') . ' h';
$statusLabels = ['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Approuvé', 'refused' => 'Refusé'];
?>

<div class="ib-site">
	<section class="ib-panel">
		<div class="ib-panel-head">
			<h1>Historique des cours</h1>
			<a href="<?php echo Route::_('index.php?option=com_instructor_billing&view=dashboard'); ?>">Tableau de bord</a>
		</div>
		<div class="ib-list">
			<?php foreach ($this->items as $session) : ?>
				<div class="ib-row">
					<div>
						<strong><?php echo htmlspecialchars((string) $session->student_name ?: 'Cours pratique'); ?></strong>
						<span><?php echo htmlspecialchars($session->start_time); ?> → <?php echo htmlspecialchars((string) $session->end_time); ?></span>
						<?php if ($session->notes) : ?><small><?php echo htmlspecialchars($session->notes); ?></small><?php endif; ?>
					</div>
					<div>
						<strong><?php echo $minutesToHours($session->duration_minutes); ?></strong>
						<span><?php echo $statusLabels[$session->status] ?? htmlspecialchars($session->status); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if (!$this->items) : ?>
				<p>Aucun cours enregistré.</p>
			<?php endif; ?>
		</div>
	</section>
</div>
