<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

SharedServices::load();
HTMLHelper::_('stylesheet', 'com_instructor_billing/site.css', ['version' => 'auto', 'relative' => true]);

$invoice = $this->item;
$currentPath = Uri::getInstance()->getPath() ?: '/index.php';
$componentUrl = static function (array $query) use ($currentPath): string {
	return $currentPath . '?' . http_build_query(array_merge(['option' => 'com_instructor_billing'], $query));
};
$returnUrl = base64_encode(Uri::getInstance()->toString());
$canSyncSage = AccessService::canInvoice();
?>

<div class="ib-site">
	<section class="ib-panel">
		<div class="ib-panel-head">
			<div>
				<h1>Facture <?php echo htmlspecialchars($invoice->invoice_number); ?></h1>
				<p><?php echo htmlspecialchars($invoice->period_start . ' au ' . $invoice->period_end); ?></p>
			</div>
			<div class="ib-row-actions">
				<a href="<?php echo htmlspecialchars($componentUrl(['task' => 'invoice.csv', 'id' => (int) $invoice->id, 'format' => 'raw'])); ?>">CSV</a>
				<a target="_blank" href="<?php echo htmlspecialchars($componentUrl(['view' => 'invoice', 'id' => (int) $invoice->id, 'layout' => 'print', 'tmpl' => 'component'])); ?>">Imprimer</a>
				<?php if ($canSyncSage) : ?>
					<form method="post" action="<?php echo htmlspecialchars($componentUrl(['task' => 'invoice.syncSage', 'id' => (int) $invoice->id])); ?>">
						<input type="hidden" name="option" value="com_instructor_billing">
						<input type="hidden" name="task" value="invoice.syncSage">
						<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
						<button class="ib-mini" type="submit" <?php echo $invoice->sage_sync_status === 'synced' ? 'disabled' : ''; ?>>Envoyer à Sage</button>
						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php if (!empty($invoice->sage_sync_status)) : ?>
			<p class="ib-gps-note">
				Sage: <?php echo $invoice->sage_sync_status === 'synced' ? 'synchronisée' : 'erreur'; ?>
				<?php if (!empty($invoice->sage_invoice_id)) : ?>
					· ID <?php echo htmlspecialchars($invoice->sage_invoice_id); ?>
				<?php endif; ?>
				<?php if ($invoice->sage_sync_status === 'failed' && !empty($invoice->sage_sync_error)) : ?>
					· <?php echo htmlspecialchars($invoice->sage_sync_error); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<table class="ib-table">
			<thead><tr><th>Description</th><th>Heures</th><th>Total</th></tr></thead>
			<tbody>
			<?php foreach ($this->items as $item) : ?>
				<tr>
					<td><?php echo htmlspecialchars($item->description); ?></td>
					<td><?php echo htmlspecialchars($item->quantity_hours); ?></td>
					<td><?php echo MoneyService::format($item->line_total); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr><th colspan="2">Sous-total</th><td><?php echo MoneyService::format($invoice->subtotal); ?></td></tr>
				<tr><th colspan="2">Taxes</th><td><?php echo MoneyService::format($invoice->tax_amount); ?></td></tr>
				<tr><th colspan="2">Total</th><td><?php echo MoneyService::format($invoice->total); ?></td></tr>
			</tfoot>
		</table>
	</section>
</div>
