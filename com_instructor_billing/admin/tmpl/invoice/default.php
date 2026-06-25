<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('stylesheet', 'com_instructor_billing/admin.css', ['version' => 'auto', 'relative' => true]);

$invoice = $this->item;
$statusLabels = ['draft' => 'Brouillon', 'sent' => 'Envoyée', 'paid' => 'Payée', 'cancelled' => 'Annulée'];
?>

<div class="ib-admin ib-invoice">
	<div class="ib-band">
		<div>
			<h2>Facture <?php echo htmlspecialchars($invoice->invoice_number); ?></h2>
			<p><?php echo htmlspecialchars($invoice->instructor_name); ?> · <?php echo htmlspecialchars($invoice->period_start . ' au ' . $invoice->period_end); ?></p>
		</div>
		<div class="ib-actions">
			<a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoice.csv&id=' . (int) $invoice->id); ?>">CSV</a>
			<a class="btn btn-primary" target="_blank" href="<?php echo Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . (int) $invoice->id . '&layout=print&tmpl=component'); ?>">PDF / imprimer</a>
		</div>
	</div>

	<form class="ib-actions" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoice.updateStatus&id=' . (int) $invoice->id); ?>">
		<select name="status">
			<?php foreach ($statusLabels as $value => $label) : ?>
				<option value="<?php echo $value; ?>" <?php echo $invoice->status === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>
		<button class="btn btn-secondary" type="submit">Mettre à jour</button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
	<form class="ib-actions" method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoice.delete&id=' . (int) $invoice->id); ?>">
		<button class="btn btn-danger" type="submit">Supprimer / annuler</button>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>

	<form method="post" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=invoice.saveItems&id=' . (int) $invoice->id); ?>">
		<table class="table table-striped">
			<thead><tr><th>Description</th><th>Début</th><th>Fin</th><th>Heures</th><th>Taux</th><th>Total</th></tr></thead>
			<tbody>
			<?php foreach ($this->items as $index => $item) : ?>
				<tr>
					<td>
						<?php if ($invoice->status === 'draft') : ?>
							<input type="hidden" name="item_id[]" value="<?php echo (int) $item->id; ?>">
							<input name="description[]" value="<?php echo htmlspecialchars($item->description); ?>">
						<?php else : ?>
							<?php echo htmlspecialchars($item->description); ?>
						<?php endif; ?>
					</td>
					<td><?php echo htmlspecialchars((string) $item->start_time); ?></td>
					<td><?php echo htmlspecialchars((string) $item->end_time); ?></td>
					<td>
						<?php if ($invoice->status === 'draft') : ?>
							<input name="quantity_hours[]" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($item->quantity_hours); ?>">
						<?php else : ?>
							<?php echo htmlspecialchars($item->quantity_hours); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($invoice->status === 'draft') : ?>
							<input name="hourly_rate[]" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($item->hourly_rate); ?>">
						<?php else : ?>
							<?php echo MoneyService::format($item->hourly_rate); ?>
						<?php endif; ?>
					</td>
					<td><?php echo MoneyService::format($item->line_total); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr><th colspan="5">Sous-total</th><td><?php echo MoneyService::format($invoice->subtotal); ?></td></tr>
				<tr><th colspan="5">Taxes</th><td><?php echo MoneyService::format($invoice->tax_amount); ?></td></tr>
				<tr><th colspan="5">Total</th><td><strong><?php echo MoneyService::format($invoice->total); ?></strong></td></tr>
			</tfoot>
		</table>
		<?php if ($invoice->status === 'draft') : ?>
			<button class="btn btn-primary" type="submit">Enregistrer les lignes</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		<?php endif; ?>
	</form>
</div>
