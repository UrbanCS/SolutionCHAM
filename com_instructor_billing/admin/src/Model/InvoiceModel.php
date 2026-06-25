<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AuditService;
use Cham\Component\InstructorBilling\Administrator\Service\ExportService;
use Cham\Component\InstructorBilling\Administrator\Service\InvoiceService;
use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class InvoiceModel extends BaseDatabaseModel
{
	public function getItem(?int $id = null): object
	{
		$id = $id ?: Factory::getApplication()->input->getInt('id');
		$invoice = (new ExportService())->getInvoice((int) $id);

		if (!$invoice) {
			throw new \RuntimeException('Facture introuvable.', 404);
		}

		return $invoice;
	}

	public function getItems(?int $id = null): array
	{
		$id = $id ?: Factory::getApplication()->input->getInt('id');

		return (new ExportService())->getInvoiceItems((int) $id);
	}

	public function updateStatus(int $id, string $status): void
	{
		if ($id <= 0 || !in_array($status, ['draft', 'sent', 'paid', 'cancelled'], true)) {
			throw new \RuntimeException('Statut de facture invalide.', 400);
		}

		$db = Factory::getDbo();
		$row = (object) [
			'id'         => $id,
			'status'     => $status,
			'updated_at' => Factory::getDate()->toSql(),
		];
		$db->updateObject('#__invoices', $row, 'id');
		AuditService::log('invoice.status', 'invoice', $id, ['status' => $status]);
	}

	public function saveItems(int $id, array $data): void
	{
		$invoice = $this->getItem($id);

		if ($invoice->status !== 'draft') {
			throw new \RuntimeException('Seules les factures brouillon peuvent être modifiées.', 400);
		}

		$itemIds = $data['item_id'] ?? [];
		$descriptions = $data['description'] ?? [];
		$quantityHours = $data['quantity_hours'] ?? [];
		$hourlyRates = $data['hourly_rate'] ?? [];
		$db = Factory::getDbo();
		$subtotalCents = 0;

		$db->transactionStart();

		try {
			foreach ($itemIds as $index => $itemId) {
				$itemId = (int) $itemId;
				$description = trim((string) ($descriptions[$index] ?? 'Cours pratique')) ?: 'Cours pratique';
				$hours = (string) ($quantityHours[$index] ?? '0');
				$rate = (string) ($hourlyRates[$index] ?? '0');
				$lineTotalCents = MoneyService::lineTotalFromHours($hours, $rate);
				$subtotalCents += $lineTotalCents;

				$item = (object) [
					'id'             => $itemId,
					'description'    => $description,
					'quantity_hours' => number_format((float) str_replace(',', '.', $hours), 2, '.', ''),
					'hourly_rate'    => MoneyService::fromCents(MoneyService::toCents($rate)),
					'line_total'     => MoneyService::fromCents($lineTotalCents),
				];

				$db->updateObject('#__invoice_items', $item, 'id');
			}

			$totals = (new InvoiceService())->calculateTotals($subtotalCents);
			$row = (object) [
				'id'         => $id,
				'subtotal'   => MoneyService::fromCents($totals['subtotal']),
				'tax_amount' => MoneyService::fromCents($totals['tax']),
				'total'      => MoneyService::fromCents($totals['total']),
				'updated_at' => Factory::getDate()->toSql(),
			];
			$db->updateObject('#__invoices', $row, 'id');
			$db->transactionCommit();
			AuditService::log('invoice.items_update', 'invoice', $id);
		} catch (\Throwable $e) {
			$db->transactionRollback();
			throw $e;
		}
	}

	public function delete(int $id): void
	{
		if ($id <= 0) {
			return;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__invoices'))
			->where($db->quoteName('id') . ' = ' . (int) $id);
		$db->setQuery($query)->execute();
		AuditService::log('invoice.delete', 'invoice', $id);
	}
}
