<?php

namespace Cham\Component\InstructorBilling\Site\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\AuditService;
use Cham\Component\InstructorBilling\Administrator\Service\DateService;
use Cham\Component\InstructorBilling\Administrator\Service\MoneyService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ManagementModel extends BaseDatabaseModel
{
	public function getInstructorProfiles(): array
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['p.*', 'u.name', 'u.username', 'u.email'])
			->from($db->quoteName('#__instructor_profiles', 'p'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
			->order($db->quoteName('u.name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function getUsers(): array
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['id', 'name', 'username', 'email'])
			->from($db->quoteName('#__users'))
			->where($db->quoteName('block') . ' = 0')
			->order($db->quoteName('name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function getPendingSessions(): array
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canApprove());

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['s.*', 'u.name AS instructor_name'])
			->from($db->quoteName('#__driving_sessions', 's'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.instructor_user_id'))
			->where($db->quoteName('s.status') . ' = ' . $db->quote('submitted'))
			->order($db->quoteName('s.start_time') . ' DESC');
		$db->setQuery($query, 0, 100);

		return $db->loadObjectList() ?: [];
	}

	public function getInvoiceCandidates(): array
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());

		[$periodStart, $periodEnd] = $this->getPeriod();
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select([
				'p.user_id',
				'p.hourly_rate',
				'u.name AS instructor_name',
				'COUNT(s.id) AS session_count',
				'COALESCE(SUM(s.duration_minutes), 0) AS total_minutes',
			])
			->from($db->quoteName('#__instructor_profiles', 'p'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
			->join('LEFT', $db->quoteName('#__driving_sessions', 's') . ' ON ' . $db->quoteName('s.instructor_user_id') . ' = ' . $db->quoteName('p.user_id')
				. ' AND ' . $db->quoteName('s.status') . ' = ' . $db->quote('approved')
				. ' AND ' . $db->quoteName('s.start_time') . ' >= ' . $db->quote($periodStart . ' 00:00:00')
				. ' AND ' . $db->quoteName('s.start_time') . ' <= ' . $db->quote($periodEnd . ' 23:59:59')
				. ' AND NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__invoice_items', 'ii') . ' WHERE ' . $db->quoteName('ii.session_id') . ' = ' . $db->quoteName('s.id') . ')')
			->where($db->quoteName('p.active') . ' = 1')
			->group($db->quoteName(['p.user_id', 'p.hourly_rate', 'u.name']))
			->order($db->quoteName('u.name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function getRecentInvoices(): array
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['i.*', 'u.name AS instructor_name'])
			->from($db->quoteName('#__invoices', 'i'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('i.instructor_user_id'))
			->order($db->quoteName('i.created_at') . ' DESC');
		$db->setQuery($query, 0, 25);

		return $db->loadObjectList() ?: [];
	}

	public function getPeriod(): array
	{
		$app = Factory::getApplication();
		[$defaultStart, $defaultEnd] = DateService::currentWeekBounds();
		$start = $app->input->getString('period_start', $defaultStart);
		$end = $app->input->getString('period_end', $defaultEnd);

		return [$start ?: $defaultStart, $end ?: $defaultEnd];
	}

	public function setSessionStatus(int $id, string $status): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canApprove());

		if ($id <= 0 || !in_array($status, ['approved', 'refused'], true)) {
			throw new \RuntimeException('Statut invalide.', 400);
		}

		$db = Factory::getDbo();
		$now = Factory::getDate()->toSql();
		$row = (object) [
			'id'         => $id,
			'status'     => $status,
			'updated_at' => $now,
		];

		if ($status === 'approved') {
			$row->approved_by = (int) AccessService::currentUser()->id;
			$row->approved_at = $now;
		} else {
			$row->approved_by = null;
			$row->approved_at = null;
		}

		$db->updateObject('#__driving_sessions', $row, 'id');
		AuditService::log('session.' . $status, 'driving_session', $id);
	}

	public function saveProfile(array $data): int
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());

		$db = Factory::getDbo();
		$userId = (int) ($data['user_id'] ?? 0);
		$now = Factory::getDate()->toSql();

		if ($userId <= 0) {
			throw new \RuntimeException('Utilisateur Joomla requis.', 400);
		}

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__users'))
			->where($db->quoteName('id') . ' = ' . $userId)
			->where($db->quoteName('block') . ' = 0');
		$db->setQuery($query);

		if ((int) $db->loadResult() === 0) {
			throw new \RuntimeException('Utilisateur Joomla introuvable ou désactivé.', 404);
		}

		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__instructor_profiles'))
			->where($db->quoteName('user_id') . ' = ' . $userId);
		$db->setQuery($query);
		$id = (int) $db->loadResult();

		$row = (object) [
			'user_id'     => $userId,
			'hourly_rate' => MoneyService::fromCents(MoneyService::toCents($data['hourly_rate'] ?? '0')),
			'phone'       => trim((string) ($data['phone'] ?? '')) ?: null,
			'active'      => (int) (($data['active'] ?? 0) ? 1 : 0),
			'updated_at'  => $now,
		];

		if ($id > 0) {
			$row->id = $id;
			$db->updateObject('#__instructor_profiles', $row, 'id');
		} else {
			$row->created_at = $now;
			$db->insertObject('#__instructor_profiles', $row, 'id');
			$id = (int) $row->id;
		}

		AuditService::log('instructor_profile.save_frontend', 'instructor_profile', $id, ['user_id' => $userId]);

		return $id;
	}

	public function updateInvoiceStatus(int $id, string $status): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());

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
}
