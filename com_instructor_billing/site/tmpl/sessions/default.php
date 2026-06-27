<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\DateService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

SharedServices::load();
HTMLHelper::_('stylesheet', 'com_instructor_billing/site.css', ['version' => 'auto', 'relative' => true]);

$currentPath = Uri::getInstance()->getPath();
$dashboardUrl = $currentPath . '?' . http_build_query(['option' => 'com_instructor_billing', 'view' => 'dashboard']);
$minutesToHours = static fn ($minutes) => number_format(((int) $minutes) / 60, 2, ',', ' ') . ' h';
$statusLabels = ['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Approuvé', 'refused' => 'Refusé'];
?>

<div class="ib-site">
	<section class="ib-panel">
		<div class="ib-panel-head">
			<h1>Historique des cours</h1>
			<a href="<?php echo htmlspecialchars($dashboardUrl); ?>">Tableau de bord</a>
		</div>
		<div class="ib-list">
			<?php foreach ($this->items as $session) : ?>
				<div class="ib-row">
					<div>
						<strong><?php echo htmlspecialchars((string) $session->student_name ?: 'Cours pratique'); ?></strong>
						<span><?php echo htmlspecialchars(DateService::formatLocal($session->start_time)); ?> → <?php echo htmlspecialchars(DateService::formatLocal($session->end_time)); ?></span>
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
