<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

class InvoiceService
{
	public function generateWeeklyInvoice(int $instructorUserId, string $periodStart, string $periodEnd, int $createdBy): int
	{
		AccessService::denyUnless(AccessService::canInvoice());

		$sessions = $this->getApprovedUninvoicedSessions($instructorUserId, $periodStart, $periodEnd);

		if (!$sessions) {
			throw new \RuntimeException('Aucun cours approuvé non facturé pour cette période.', 400);
		}

		return $this->createInvoiceFromSessions($instructorUserId, $periodStart, $periodEnd, $sessions, $createdBy);
	}

	public function createManualInvoice(array $data, int $createdBy): int
	{
		AccessService::denyUnless(AccessService::canInvoice());

		$instructorUserId = (int) ($data['instructor_user_id'] ?? 0);
		$periodStart = (string) ($data['period_start'] ?? '');
		$periodEnd = (string) ($data['period_end'] ?? '');
		$description = trim((string) ($data['description'] ?? 'Facture manuelle'));
		$quantityHours = (string) ($data['quantity_hours'] ?? '0');
		$hourlyRate = (string) ($data['hourly_rate'] ?? $this->getInstructorRate($instructorUserId));

		if ($instructorUserId <= 0 || !$periodStart || !$periodEnd) {
			throw new \RuntimeException('Instructeur et période requis.', 400);
		}

		$lineTotalCents = MoneyService::lineTotalFromHours($quantityHours, $hourlyRate);
		$totals = $this->calculateTotals($lineTotalCents);
		$db = Factory::getDbo();
		$now = Factory::getDate()->toSql();

		$db->transactionStart();

		try {
			$invoice = (object) [
				'invoice_number'     => $this->generateInvoiceNumber(),
				'instructor_user_id' => $instructorUserId,
				'period_start'       => $periodStart,
				'period_end'         => $periodEnd,
				'subtotal'           => MoneyService::fromCents($totals['subtotal']),
				'tax_amount'         => MoneyService::fromCents($totals['tax']),
				'total'              => MoneyService::fromCents($totals['total']),
				'status'             => 'draft',
				'created_by'         => $createdBy,
				'created_at'         => $now,
				'updated_at'         => $now,
			];

			$db->insertObject('#__invoices', $invoice, 'id');

			$item = (object) [
				'invoice_id'     => (int) $invoice->id,
				'session_id'     => null,
				'description'    => $description !== '' ? $description : 'Facture manuelle',
				'quantity_hours' => number_format((float) str_replace(',', '.', $quantityHours), 2, '.', ''),
				'hourly_rate'    => MoneyService::fromCents(MoneyService::toCents($hourlyRate)),
				'line_total'     => MoneyService::fromCents($lineTotalCents),
			];

			$db->insertObject('#__invoice_items', $item);
			$db->transactionCommit();

			AuditService::log('invoice.create_manual', 'invoice', (int) $invoice->id, ['invoice_number' => $invoice->invoice_number]);

			return (int) $invoice->id;
		} catch (\Throwable $e) {
			$db->transactionRollback();
			throw $e;
		}
	}

	public function createInvoiceFromSessions(int $instructorUserId, string $periodStart, string $periodEnd, array $sessions, int $createdBy): int
	{
		$hourlyRate = $this->getInstructorRate($instructorUserId);
		$subtotalCents = 0;
		$db = Factory::getDbo();
		$now = Factory::getDate()->toSql();

		foreach ($sessions as $session) {
			$subtotalCents += MoneyService::lineTotalFromMinutes((int) $session->duration_minutes, $hourlyRate);
		}

		$totals = $this->calculateTotals($subtotalCents);
		$db->transactionStart();

		try {
			$invoice = (object) [
				'invoice_number'     => $this->generateInvoiceNumber(),
				'instructor_user_id' => $instructorUserId,
				'period_start'       => $periodStart,
				'period_end'         => $periodEnd,
				'subtotal'           => MoneyService::fromCents($totals['subtotal']),
				'tax_amount'         => MoneyService::fromCents($totals['tax']),
				'total'              => MoneyService::fromCents($totals['total']),
				'status'             => 'draft',
				'created_by'         => $createdBy,
				'created_at'         => $now,
				'updated_at'         => $now,
			];

			$db->insertObject('#__invoices', $invoice, 'id');

			foreach ($sessions as $session) {
				$minutes = (int) $session->duration_minutes;
				$lineTotalCents = MoneyService::lineTotalFromMinutes($minutes, $hourlyRate);
				$student = trim((string) $session->student_name);
				$description = $student !== '' ? 'Cours pratique - ' . $student : 'Cours pratique #' . (int) $session->id;

				$item = (object) [
					'invoice_id'     => (int) $invoice->id,
					'session_id'     => (int) $session->id,
					'description'    => $description,
					'quantity_hours' => number_format($minutes / 60, 2, '.', ''),
					'hourly_rate'    => MoneyService::fromCents(MoneyService::toCents($hourlyRate)),
					'line_total'     => MoneyService::fromCents($lineTotalCents),
				];

				$db->insertObject('#__invoice_items', $item);
			}

			$db->transactionCommit();

			AuditService::log('invoice.generate_weekly', 'invoice', (int) $invoice->id, [
				'instructor_user_id' => $instructorUserId,
				'period_start'       => $periodStart,
				'period_end'         => $periodEnd,
			], $createdBy);

			return (int) $invoice->id;
		} catch (\Throwable $e) {
			$db->transactionRollback();
			throw $e;
		}
	}

	public function getApprovedUninvoicedSessions(int $instructorUserId, string $periodStart, string $periodEnd): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('s.*')
			->from($db->quoteName('#__driving_sessions', 's'))
			->where($db->quoteName('s.instructor_user_id') . ' = ' . (int) $instructorUserId)
			->where($db->quoteName('s.status') . ' = ' . $db->quote('approved'))
			->where($db->quoteName('s.start_time') . ' >= ' . $db->quote($periodStart . ' 00:00:00'))
			->where($db->quoteName('s.start_time') . ' <= ' . $db->quote($periodEnd . ' 23:59:59'))
			->where('NOT EXISTS (
				SELECT 1 FROM ' . $db->quoteName('#__invoice_items', 'ii') . '
				WHERE ' . $db->quoteName('ii.session_id') . ' = ' . $db->quoteName('s.id') . '
			)')
			->order($db->quoteName('s.start_time') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function getInstructorRate(int $userId): string
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('hourly_rate'))
			->from($db->quoteName('#__instructor_profiles'))
			->where($db->quoteName('user_id') . ' = ' . (int) $userId);
		$db->setQuery($query);
		$rate = $db->loadResult();

		if ($rate !== null) {
			return (string) $rate;
		}

		return (string) ComponentHelper::getParams(AccessService::COMPONENT)->get('default_hourly_rate', '0.00');
	}

	public function calculateTotals(int $subtotalCents): array
	{
		$params = ComponentHelper::getParams(AccessService::COMPONENT);
		$taxRate = (float) $params->get('tax_rate', 0);

		if ($taxRate > 1) {
			$taxRate = $taxRate / 100;
		}

		$taxCents = (int) $params->get('tax_enabled', 0) === 1 ? (int) round($subtotalCents * $taxRate) : 0;

		return [
			'subtotal' => $subtotalCents,
			'tax'      => $taxCents,
			'total'    => $subtotalCents + $taxCents,
		];
	}

	private function generateInvoiceNumber(): string
	{
		$params = ComponentHelper::getParams(AccessService::COMPONENT);
		$prefix = strtoupper(preg_replace('/[^A-Z0-9_-]/i', '', (string) $params->get('invoice_prefix', 'CHAM'))) ?: 'CHAM';

		return $prefix . '-' . Factory::getDate()->format('Ymd-His') . '-' . random_int(100, 999);
	}
}
