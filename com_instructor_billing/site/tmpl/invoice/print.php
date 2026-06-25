<?php

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\HTML\HTMLHelper;

SharedServices::load();
HTMLHelper::_('stylesheet', 'com_instructor_billing/print.css', ['version' => 'auto', 'relative' => true]);

$invoice = $this->item;
?>

<main class="ib-print">
	<header>
		<h1>Facture <?php echo htmlspecialchars($invoice->invoice_number); ?></h1>
		<p>École de conduite CHAM</p>
	</header>
	<section class="ib-print-meta">
		<div><strong>Instructeur</strong><br><?php echo htmlspecialchars($invoice->instructor_name); ?></div>
		<div><strong>Période</strong><br><?php echo htmlspecialchars($invoice->period_start); ?> au <?php echo htmlspecialchars($invoice->period_end); ?></div>
	</section>
	<table>
		<thead><tr><th>Description</th><th>Heures</th><th>Taux</th><th>Total</th></tr></thead>
		<tbody>
		<?php foreach ($this->items as $item) : ?>
			<tr>
				<td><?php echo htmlspecialchars($item->description); ?></td>
				<td><?php echo htmlspecialchars($item->quantity_hours); ?></td>
				<td><?php echo MoneyService::format($item->hourly_rate); ?></td>
				<td><?php echo MoneyService::format($item->line_total); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr><th colspan="3">Sous-total</th><td><?php echo MoneyService::format($invoice->subtotal); ?></td></tr>
			<tr><th colspan="3">Taxes</th><td><?php echo MoneyService::format($invoice->tax_amount); ?></td></tr>
			<tr><th colspan="3">Total</th><td><?php echo MoneyService::format($invoice->total); ?></td></tr>
		</tfoot>
	</table>
</main>
<script>window.addEventListener('load', function () { window.print(); });</script>
