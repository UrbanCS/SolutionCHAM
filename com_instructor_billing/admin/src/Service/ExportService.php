<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class ExportService
{
	public function getInvoice(int $invoiceId): ?object
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select([
				'i.*',
				'u.name AS instructor_name',
				'u.email AS instructor_email',
			])
			->from($db->quoteName('#__invoices', 'i'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('i.instructor_user_id'))
			->where($db->quoteName('i.id') . ' = ' . (int) $invoiceId);
		$db->setQuery($query);

		return $db->loadObject() ?: null;
	}

	public function getInvoiceItems(int $invoiceId): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select([
				'ii.*',
				's.start_time',
				's.end_time',
				's.student_name',
			])
			->from($db->quoteName('#__invoice_items', 'ii'))
			->join('LEFT', $db->quoteName('#__driving_sessions', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('ii.session_id'))
			->where($db->quoteName('ii.invoice_id') . ' = ' . (int) $invoiceId)
			->order($db->quoteName('ii.id') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function buildInvoiceCsv(int $invoiceId): string
	{
		$invoice = $this->getInvoice($invoiceId);

		if (!$invoice) {
			throw new \RuntimeException('Facture introuvable.', 404);
		}

		$rows = [];
		$rows[] = ['invoice_number', 'instructor', 'period_start', 'period_end', 'status', 'subtotal', 'tax_amount', 'total'];
		$rows[] = [
			$invoice->invoice_number,
			$invoice->instructor_name,
			$invoice->period_start,
			$invoice->period_end,
			$invoice->status,
			$invoice->subtotal,
			$invoice->tax_amount,
			$invoice->total,
		];
		$rows[] = [];
		$rows[] = ['description', 'student_name', 'start_time', 'end_time', 'quantity_hours', 'hourly_rate', 'line_total'];

		foreach ($this->getInvoiceItems($invoiceId) as $item) {
			$rows[] = [
				$item->description,
				$item->student_name,
				$item->start_time,
				$item->end_time,
				$item->quantity_hours,
				$item->hourly_rate,
				$item->line_total,
			];
		}

		$stream = fopen('php://temp', 'r+');

		foreach ($rows as $row) {
			fputcsv($stream, $row);
		}

		rewind($stream);
		$csv = stream_get_contents($stream);
		fclose($stream);

		return $csv ?: '';
	}

	public function assertCanExport(int $invoiceId): object
	{
		$invoice = $this->getInvoice($invoiceId);

		if (!$invoice) {
			throw new \RuntimeException('Facture introuvable.', 404);
		}

		AccessService::denyUnless(
			AccessService::canAccessInstructor((int) $invoice->instructor_user_id),
			'Vous ne pouvez pas consulter cette facture.'
		);

		return $invoice;
	}
}
