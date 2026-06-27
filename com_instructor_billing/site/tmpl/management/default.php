<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\DateService;
use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('stylesheet', 'com_instructor_billing/site.css', ['version' => 'auto', 'relative' => true]);

$returnUrl = base64_encode(Uri::getInstance()->toString());
$currentPath = Uri::getInstance()->getPath();
$url = static function (string $view, array $extra = []) use ($currentPath): string {
	return $currentPath . '?' . http_build_query(array_merge(['option' => 'com_instructor_billing', 'view' => $view], $extra));
};
$minutesToHours = static fn ($minutes) => number_format(((int) $minutes) / 60, 2, ',', ' ') . ' h';
$formatRateInput = static fn ($value) => number_format((float) $value, 2, '.', '');
$sessionStatuses = ['submitted' => 'Soumis', 'approved' => 'Approuvé', 'refused' => 'Refusé'];
$invoiceStatuses = ['draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée', 'cancelled' => 'Annulée'];
[$periodStart, $periodEnd] = $this->period;
?>

<div class="ib-site">
	<section class="ib-hero">
		<div>
			<h1>Gestion instructeurs</h1>
			<p>Approbation des cours et facturation hebdomadaire.</p>
		</div>
		<a class="ib-secondary ib-top-link" href="<?php echo htmlspecialchars($url('dashboard')); ?>">Tableau de bord</a>
	</section>

	<?php if ($this->canManageRates) : ?>
		<section class="ib-panel">
			<div class="ib-panel-head">
				<div>
					<h2>Taux horaires</h2>
					<p>Ajoutez ou modifiez les profils instructeurs utilisés pour les factures.</p>
				</div>
				<strong><?php echo count($this->instructorProfiles); ?></strong>
			</div>

			<form class="ib-profile-form" method="post" action="<?php echo htmlspecialchars($url('management', ['task' => 'management.saveProfile'])); ?>">
				<input type="hidden" name="option" value="com_instructor_billing">
				<input type="hidden" name="task" value="management.saveProfile">
				<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
				<label>Utilisateur Joomla
					<select name="user_id" required>
						<option value="">Choisir...</option>
						<?php foreach ($this->users as $user) : ?>
							<option value="<?php echo (int) $user->id; ?>"><?php echo htmlspecialchars($user->name . ' (' . $user->username . ')'); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Taux horaire
					<input name="hourly_rate" type="number" step="0.01" min="0" required>
				</label>
				<label>Téléphone
					<input name="phone" type="text" maxlength="40">
				</label>
				<label class="ib-checkline">
					<input name="active" type="checkbox" value="1" checked> Actif
				</label>
				<button class="ib-secondary" type="submit">Enregistrer le profil</button>
				<?php echo HTMLHelper::_('form.token'); ?>
			</form>

			<div class="ib-list ib-profile-list">
				<?php foreach ($this->instructorProfiles as $profile) : ?>
					<form class="ib-row ib-row-wide ib-profile-row" method="post" action="<?php echo htmlspecialchars($url('management', ['task' => 'management.saveProfile'])); ?>">
						<input type="hidden" name="option" value="com_instructor_billing">
						<input type="hidden" name="task" value="management.saveProfile">
						<input type="hidden" name="user_id" value="<?php echo (int) $profile->user_id; ?>">
						<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
						<div>
							<strong><?php echo htmlspecialchars($profile->name); ?></strong>
							<span><?php echo htmlspecialchars($profile->email); ?></span>
							<small><?php echo (int) $profile->active ? 'Actif' : 'Inactif'; ?> · <?php echo MoneyService::format($profile->hourly_rate); ?>/h</small>
						</div>
						<div class="ib-profile-fields">
							<label>Taux
								<input name="hourly_rate" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($formatRateInput($profile->hourly_rate)); ?>" required>
							</label>
							<label>Téléphone
								<input name="phone" type="text" maxlength="40" value="<?php echo htmlspecialchars((string) $profile->phone); ?>">
							</label>
							<label class="ib-checkline">
								<input name="active" type="checkbox" value="1" <?php echo (int) $profile->active ? 'checked' : ''; ?>> Actif
							</label>
							<button class="ib-mini" type="submit">Sauver</button>
						</div>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				<?php endforeach; ?>
				<?php if (!$this->instructorProfiles) : ?>
					<p>Aucun profil instructeur.</p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="ib-panel">
		<div class="ib-panel-head">
			<h2>Cours à approuver</h2>
			<strong><?php echo count($this->pendingSessions); ?></strong>
		</div>
		<div class="ib-list">
			<?php foreach ($this->pendingSessions as $session) : ?>
				<div class="ib-row ib-row-wide">
					<div>
						<strong><?php echo htmlspecialchars((string) $session->student_name ?: 'Cours pratique'); ?></strong>
						<span><?php echo htmlspecialchars($session->instructor_name); ?> · <?php echo htmlspecialchars(DateService::formatLocal($session->start_time)); ?> → <?php echo htmlspecialchars(DateService::formatLocal($session->end_time)); ?></span>
						<small><?php echo htmlspecialchars($minutesToHours($session->duration_minutes)); ?> · <?php echo $sessionStatuses[$session->status] ?? htmlspecialchars($session->status); ?></small>
					</div>
					<div class="ib-row-actions">
						<form method="post" action="<?php echo htmlspecialchars($url('management', ['task' => 'management.approveSession', 'id' => (int) $session->id])); ?>">
							<input type="hidden" name="option" value="com_instructor_billing">
							<input type="hidden" name="task" value="management.approveSession">
							<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
							<button class="ib-mini ib-approve" type="submit">Approuver</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
						<form method="post" action="<?php echo htmlspecialchars($url('management', ['task' => 'management.refuseSession', 'id' => (int) $session->id])); ?>">
							<input type="hidden" name="option" value="com_instructor_billing">
							<input type="hidden" name="task" value="management.refuseSession">
							<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
							<button class="ib-mini ib-refuse" type="submit">Refuser</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if (!$this->pendingSessions) : ?>
				<p>Aucun cours en attente.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="ib-panel">
		<div class="ib-panel-head">
			<h2>Factures à générer</h2>
		</div>
		<form class="ib-period" method="get" action="<?php echo htmlspecialchars($currentPath); ?>">
			<input type="hidden" name="option" value="com_instructor_billing">
			<input type="hidden" name="view" value="management">
			<label>Début <input type="date" name="period_start" value="<?php echo htmlspecialchars($periodStart); ?>"></label>
			<label>Fin <input type="date" name="period_end" value="<?php echo htmlspecialchars($periodEnd); ?>"></label>
			<button class="ib-secondary" type="submit">Filtrer</button>
		</form>
		<div class="ib-list">
			<?php foreach ($this->invoiceCandidates as $candidate) : ?>
				<div class="ib-row ib-row-wide">
					<div>
						<strong><?php echo htmlspecialchars($candidate->instructor_name); ?></strong>
						<span><?php echo (int) $candidate->session_count; ?> cours approuvé(s) non facturé(s) · <?php echo htmlspecialchars($minutesToHours($candidate->total_minutes)); ?></span>
						<small>Taux: <?php echo MoneyService::format($candidate->hourly_rate); ?>/h</small>
					</div>
					<form method="post" action="<?php echo htmlspecialchars($url('management', ['task' => 'management.generateInvoice'])); ?>">
						<input type="hidden" name="option" value="com_instructor_billing">
						<input type="hidden" name="task" value="management.generateInvoice">
						<input type="hidden" name="instructor_user_id" value="<?php echo (int) $candidate->user_id; ?>">
						<input type="hidden" name="period_start" value="<?php echo htmlspecialchars($periodStart); ?>">
						<input type="hidden" name="period_end" value="<?php echo htmlspecialchars($periodEnd); ?>">
						<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
						<button class="ib-mini" type="submit" <?php echo (int) $candidate->session_count === 0 ? 'disabled' : ''; ?>>Générer</button>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				</div>
			<?php endforeach; ?>
			<?php if (!$this->invoiceCandidates) : ?>
				<p>Aucun profil instructeur actif.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="ib-panel">
		<h2>Factures récentes</h2>
		<div class="ib-list">
			<?php foreach ($this->recentInvoices as $invoice) : ?>
				<div class="ib-row ib-row-wide">
					<div>
						<strong><?php echo htmlspecialchars($invoice->invoice_number); ?></strong>
						<span><?php echo htmlspecialchars($invoice->instructor_name); ?> · <?php echo htmlspecialchars($invoice->period_start . ' au ' . $invoice->period_end); ?></span>
						<small>Total: <?php echo MoneyService::format($invoice->total); ?> · Sage: <?php echo $invoice->sage_sync_status === 'synced' ? 'synchronisée' : ($invoice->sage_sync_status === 'failed' ? 'erreur' : 'non envoyée'); ?></small>
					</div>
					<div class="ib-row-actions">
						<form class="ib-status-form" method="post" action="<?php echo htmlspecialchars($url('management', ['task' => 'management.updateInvoiceStatus', 'id' => (int) $invoice->id])); ?>">
							<input type="hidden" name="option" value="com_instructor_billing">
							<input type="hidden" name="task" value="management.updateInvoiceStatus">
							<select name="status">
								<?php foreach ($invoiceStatuses as $value => $label) : ?>
									<option value="<?php echo $value; ?>" <?php echo $invoice->status === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
								<?php endforeach; ?>
							</select>
							<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
							<button class="ib-mini" type="submit">OK</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
						<form method="post" action="<?php echo htmlspecialchars($url('invoice', ['task' => 'invoice.syncSage', 'id' => (int) $invoice->id])); ?>">
							<input type="hidden" name="option" value="com_instructor_billing">
							<input type="hidden" name="task" value="invoice.syncSage">
							<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
							<button class="ib-mini" type="submit" <?php echo $invoice->sage_sync_status === 'synced' ? 'disabled' : ''; ?>>Sage</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
						<a href="<?php echo htmlspecialchars($url('invoice', ['id' => (int) $invoice->id])); ?>">Voir</a>
					</div>
				</div>
			<?php endforeach; ?>
			<?php if (!$this->recentInvoices) : ?>
				<p>Aucune facture créée.</p>
			<?php endif; ?>
		</div>
	</section>
</div>
