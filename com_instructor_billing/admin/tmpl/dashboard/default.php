<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\DateService;
use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);

$minutesToHours = static fn ($minutes) => number_format(((int) $minutes) / 60, 2, ',', ' ') . ' h';
?>

<div class="ib-admin">
	<div class="ib-band">
		<div>
			<h2>Résumé de la semaine</h2>
			<p><?php echo htmlspecialchars($this->week[0] ?? ''); ?> au <?php echo htmlspecialchars($this->week[1] ?? ''); ?></p>
		</div>
		<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=invoices'); ?>">Générer une facture</a>
	</div>

	<div class="ib-grid ib-grid-3">
		<?php foreach ($this->summary as $row) : ?>
			<div class="ib-card">
				<h3><?php echo htmlspecialchars($row->instructor_name); ?></h3>
				<p class="ib-number"><?php echo $minutesToHours($row->total_minutes); ?></p>
				<p><?php echo (int) $row->session_count; ?> cours, <?php echo (int) $row->pending_count; ?> en attente</p>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="ib-columns">
		<section>
			<h3>Cours en attente</h3>
			<table class="table table-striped">
				<thead><tr><th>Instructeur</th><th>Date</th><th>Élève</th><th>Durée</th><th></th></tr></thead>
				<tbody>
				<?php foreach ($this->pending as $session) : ?>
					<tr>
						<td><?php echo htmlspecialchars($session->instructor_name); ?></td>
						<td><?php echo htmlspecialchars(DateService::formatLocal($session->start_time)); ?></td>
						<td><?php echo htmlspecialchars((string) $session->student_name); ?></td>
						<td><?php echo $minutesToHours($session->duration_minutes); ?></td>
						<td><a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=session&id=' . (int) $session->id); ?>">Ouvrir</a></td>
					</tr>
				<?php endforeach; ?>
				<?php if (!$this->pending) : ?>
					<tr><td colspan="5">Aucun cours en attente.</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</section>

		<section>
			<h3>Activité récente</h3>
			<table class="table table-striped">
				<thead><tr><th>Action</th><th>Utilisateur</th><th>Date</th></tr></thead>
				<tbody>
				<?php foreach ($this->audit as $log) : ?>
					<tr>
						<td><?php echo htmlspecialchars($log->action); ?></td>
						<td><?php echo htmlspecialchars((string) $log->user_name); ?></td>
						<td><?php echo htmlspecialchars($log->created_at); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if (!$this->audit) : ?>
					<tr><td colspan="3">Aucune activité enregistrée.</td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</section>
	</div>
</div>
