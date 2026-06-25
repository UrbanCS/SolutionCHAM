<?php

namespace Cham\Component\InstructorBilling\Site\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DashboardModel extends BaseDatabaseModel
{
	public function getActiveSession(): ?object
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__driving_sessions'))
			->where($db->quoteName('instructor_user_id') . ' = ' . $userId)
			->where($db->quoteName('end_time') . ' IS NULL')
			->where($db->quoteName('status') . ' = ' . $db->quote('draft'))
			->order($db->quoteName('start_time') . ' DESC');
		$db->setQuery($query, 0, 1);

		return $db->loadObject() ?: null;
	}

	public function getRecentSessions(): array
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__driving_sessions'))
			->where($db->quoteName('instructor_user_id') . ' = ' . $userId)
			->order($db->quoteName('start_time') . ' DESC');
		$db->setQuery($query, 0, 10);

		return $db->loadObjectList() ?: [];
	}

	public function getWeeklySummary(): object
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		[$start, $end] = $this->currentWeekBounds();
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select([
				'COUNT(*) AS session_count',
				'SUM(duration_minutes) AS total_minutes',
				'SUM(CASE WHEN status = ' . $db->quote('approved') . ' THEN duration_minutes ELSE 0 END) AS approved_minutes',
			])
			->from($db->quoteName('#__driving_sessions'))
			->where($db->quoteName('instructor_user_id') . ' = ' . $userId)
			->where($db->quoteName('start_time') . ' BETWEEN ' . $db->quote($start . ' 00:00:00') . ' AND ' . $db->quote($end . ' 23:59:59'));
		$db->setQuery($query);
		$summary = $db->loadObject() ?: (object) [];
		$summary->period_start = $start;
		$summary->period_end = $end;
		$summary->session_count = (int) ($summary->session_count ?? 0);
		$summary->total_minutes = (int) ($summary->total_minutes ?? 0);
		$summary->approved_minutes = (int) ($summary->approved_minutes ?? 0);

		return $summary;
	}

	public function getInvoices(): array
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__invoices'))
			->where($db->quoteName('instructor_user_id') . ' = ' . $userId)
			->order($db->quoteName('created_at') . ' DESC');
		$db->setQuery($query, 0, 8);

		return $db->loadObjectList() ?: [];
	}

	private function currentWeekBounds(): array
	{
		$start = new \DateTimeImmutable('monday this week');
		$end = $start->modify('+6 days');

		return [$start->format('Y-m-d'), $end->format('Y-m-d')];
	}
}
