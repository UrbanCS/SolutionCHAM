<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);

$app = Factory::getApplication();
$statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée', 'cancelled' => 'Annulée'];
[$defaultStart, $defaultEnd] = $this->defaultPeriod;
?>

<div class="ib-admin">
	<div class="ib-grid ib-grid-2">
		<form class="ib-form ib-card" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoices.generateWeekly'); ?>">
			<h3>Générer une facture hebdomadaire</h3>
			<label>Instructeur
				<select name="instructor_user_id" required>
					<option value="">Choisir...</option>
					<?php foreach ($this->instructors as $instructor) : ?>
						<option value="<?php echo (int) $instructor->user_id; ?>"><?php echo htmlspecialchars($instructor->name); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="ib-form-grid">
				<label>Début <input type="date" name="period_start" value="<?php echo htmlspecialchars($defaultStart); ?>" required></label>
				<label>Fin <input type="date" name="period_end" value="<?php echo htmlspecialchars($defaultEnd); ?>" required></label>
			</div>
			<button class="btn btn-primary" type="submit">Générer</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>

		<form class="ib-form ib-card" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoices.createManual'); ?>">
			<h3>Créer une facture manuelle</h3>
			<label>Instructeur
				<select name="instructor_user_id" required>
					<option value="">Choisir...</option>
					<?php foreach ($this->instructors as $instructor) : ?>
						<option value="<?php echo (int) $instructor->user_id; ?>"><?php echo htmlspecialchars($instructor->name); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="ib-form-grid">
				<label>Début <input type="date" name="period_start" value="<?php echo htmlspecialchars($defaultStart); ?>" required></label>
				<label>Fin <input type="date" name="period_end" value="<?php echo htmlspecialchars($defaultEnd); ?>" required></label>
				<label>Heures <input type="number" step="0.25" min="0" name="quantity_hours" required></label>
				<label>Taux <input type="number" step="0.01" min="0" name="hourly_rate" required></label>
			</div>
			<label>Description <input name="description" value="Facture manuelle"></label>
			<button class="btn btn-secondary" type="submit">Créer</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>

	<form class="ib-filters" method="get" action="<?php echo Route::_('index.php'); ?>">
		<input type="hidden" name="option" value="com_instructor_billing">
		<input type="hidden" name="view" value="invoices">
		<select name="filter_instructor">
			<option value="">Tous les instructeurs</option>
			<?php foreach ($this->instructors as $instructor) : ?>
				<option value="<?php echo (int) $instructor->user_id; ?>" <?php echo $app->input->getInt('filter_instructor') === (int) $instructor->user_id ? 'selected' : ''; ?>>
					<?php echo htmlspecialchars($instructor->name); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<select name="filter_status">
			<option value="">Tous les statuts</option>
			<?php foreach ($statusLabels as $value => $label) : ?>
				<option value="<?php echo $value; ?>" <?php echo $app->input->getCmd('filter_status') === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>
		<button class="btn btn-secondary" type="submit">Filtrer</button>
	</form>

	<table class="table table-striped">
		<thead><tr><th>No</th><th>Instructeur</th><th>Période</th><th>Total</th><th>Statut</th><th></th></tr></thead>
		<tbody>
		<?php foreach ($this->items as $invoice) : ?>
			<tr>
				<td><?php echo htmlspecialchars($invoice->invoice_number); ?></td>
				<td><?php echo htmlspecialchars($invoice->instructor_name); ?></td>
				<td><?php echo htmlspecialchars($invoice->period_start . ' au ' . $invoice->period_end); ?></td>
				<td><?php echo MoneyService::format($invoice->total); ?></td>
				<td><span class="ib-pill ib-status-<?php echo htmlspecialchars($invoice->status); ?>"><?php echo $statusLabels[$invoice->status] ?? $invoice->status; ?></span></td>
				<td><a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $invoice->id); ?>">Ouvrir</a></td>
			</tr>
		<?php endforeach; ?>
		<?php if (!$this->items) : ?>
			<tr><td colspan="6">Aucune facture.</td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
