<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/site.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('script', 'com_instructor_billing/tracker.js', ['version' => 'auto', 'relative' => true], ['defer' => true]);

$summary = $this->weeklySummary;
$minutesToHours = static fn ($minutes) => number_format(((int) $minutes) / 60, 2, ',', ' ') . ' h';
$statusLabels = ['draft' => 'Brouillon', 'submitted' => 'Soumis', 'approved' => 'Approuvé', 'refused' => 'Refusé'];
?>

<div class="ib-site">
	<section class="ib-hero">
		<div>
			<h1>Mes cours pratiques</h1>
			<p>Semaine du <?php echo htmlspecialchars($summary->period_start); ?> au <?php echo htmlspecialchars($summary->period_end); ?></p>
		</div>
		<div class="ib-hero-total">
			<strong><?php echo $minutesToHours($summary->total_minutes); ?></strong>
			<span><?php echo (int) $summary->session_count; ?> cours</span>
		</div>
	</section>

	<?php if ($this->activeSession) : ?>
		<form class="ib-panel ib-live" method="post" data-gps-form data-gps-mode="end" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.stop'); ?>">
			<h2>Cours en cours</h2>
			<p>Début: <?php echo htmlspecialchars($this->activeSession->start_time); ?></p>
			<label>Élève/client
				<input name="student_name" value="<?php echo htmlspecialchars((string) $this->activeSession->student_name); ?>">
			</label>
			<label>Notes
				<textarea name="notes" rows="3"><?php echo htmlspecialchars((string) $this->activeSession->notes); ?></textarea>
			</label>
			<input type="hidden" name="end_lat" data-gps-lat>
			<input type="hidden" name="end_lng" data-gps-lng>
			<button class="ib-action ib-stop" type="submit">Terminer le cours/trajet</button>
			<p class="ib-gps-note" data-gps-status>GPS optionnel: autorisez le navigateur si vous voulez enregistrer la position de fin.</p>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php else : ?>
		<form class="ib-panel" method="post" data-gps-form data-gps-mode="start" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.start'); ?>">
			<h2>Nouveau cours</h2>
			<label>Élève/client
				<input name="student_name" autocomplete="off">
			</label>
			<label>Notes
				<textarea name="notes" rows="3"></textarea>
			</label>
			<input type="hidden" name="start_lat" data-gps-lat>
			<input type="hidden" name="start_lng" data-gps-lng>
			<button class="ib-action" type="submit">Débuter un cours/trajet</button>
			<p class="ib-gps-note" data-gps-status>GPS optionnel: le suivi continue même si vous refusez la localisation.</p>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>

	<section class="ib-panel">
		<div class="ib-panel-head">
			<h2>Résumé hebdomadaire</h2>
			<form method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.submitWeek'); ?>">
				<button class="ib-secondary" type="submit">Soumettre la semaine</button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>
		</div>
		<div class="ib-stats">
			<div><strong><?php echo $minutesToHours($summary->total_minutes); ?></strong><span>Total</span></div>
			<div><strong><?php echo $minutesToHours($summary->approved_minutes); ?></strong><span>Approuvé</span></div>
			<div><strong><?php echo (int) $summary->session_count; ?></strong><span>Cours</span></div>
		</div>
	</section>

	<section class="ib-panel">
		<div class="ib-panel-head">
			<h2>Ajouter manuellement</h2>
			<a href="<?php echo Route::_('index.php?option=com_instructor_billing&view=session'); ?>">Page complète</a>
		</div>
		<form class="ib-manual" method="post" data-gps-form data-gps-mode="manual" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.saveManual'); ?>">
			<label>Élève/client <input name="student_name"></label>
			<label>Début <input name="start_time" type="datetime-local" required></label>
			<label>Fin <input name="end_time" type="datetime-local" required></label>
			<label>Notes <textarea name="notes" rows="2"></textarea></label>
			<input type="hidden" name="start_lat" data-gps-start-lat>
			<input type="hidden" name="start_lng" data-gps-start-lng>
			<input type="hidden" name="end_lat" data-gps-end-lat>
			<input type="hidden" name="end_lng" data-gps-end-lng>
			<button class="ib-secondary" type="submit">Ajouter manuellement</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</section>

	<section class="ib-panel">
		<div class="ib-panel-head">
			<h2>Historique récent</h2>
			<a href="<?php echo Route::_('index.php?option=com_instructor_billing&view=sessions'); ?>">Tout voir</a>
		</div>
		<div class="ib-list">
			<?php foreach ($this->recentSessions as $session) : ?>
				<div class="ib-row">
					<div>
						<strong><?php echo htmlspecialchars((string) $session->student_name ?: 'Cours pratique'); ?></strong>
						<span><?php echo htmlspecialchars($session->start_time); ?></span>
					</div>
					<div>
						<strong><?php echo $minutesToHours($session->duration_minutes); ?></strong>
						<span><?php echo $statusLabels[$session->status] ?? htmlspecialchars($session->status); ?></span>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if (!$this->recentSessions) : ?>
				<p>Aucun cours enregistré.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ($this->invoices) : ?>
		<section class="ib-panel">
			<h2>Mes factures</h2>
			<div class="ib-list">
				<?php foreach ($this->invoices as $invoice) : ?>
					<div class="ib-row">
						<div>
							<strong><?php echo htmlspecialchars($invoice->invoice_number); ?></strong>
							<span><?php echo htmlspecialchars($invoice->period_start . ' au ' . $invoice->period_end); ?></span>
						</div>
						<div class="ib-row-actions">
							<a href="<?php echo Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $invoice->id); ?>">Voir</a>
							<a href="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoice.csv&id=' . (int) $invoice->id); ?>">CSV</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
