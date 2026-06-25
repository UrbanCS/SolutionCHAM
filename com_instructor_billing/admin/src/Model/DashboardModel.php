<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class DashboardModel extends BaseDatabaseModel
{
	public function getWeekSummary(): array
	{
		$db = Factory::getDbo();
		[$start, $end] = $this->currentWeekBounds();
		$query = $db->getQuery(true)
			->select([
				'u.id AS user_id',
				'u.name AS instructor_name',
				'COUNT(s.id) AS session_count',
				'SUM(s.duration_minutes) AS total_minutes',
				'SUM(CASE WHEN s.status = ' . $db->quote('submitted') . ' THEN 1 ELSE 0 END) AS pending_count',
			])
			->from($db->quoteName('#__instructor_profiles', 'p'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
			->join('LEFT', $db->quoteName('#__driving_sessions', 's') . ' ON ' . $db->quoteName('s.instructor_user_id') . ' = ' . $db->quoteName('u.id')
				. ' AND ' . $db->quoteName('s.start_time') . ' BETWEEN ' . $db->quote($start . ' 00:00:00') . ' AND ' . $db->quote($end . ' 23:59:59'))
			->where($db->quoteName('p.active') . ' = 1')
			->group($db->quoteName(['u.id', 'u.name']))
			->order($db->quoteName('u.name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}

	public function getPendingSessions(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['s.*', 'u.name AS instructor_name'])
			->from($db->quoteName('#__driving_sessions', 's'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.instructor_user_id'))
			->where($db->quoteName('s.status') . ' = ' . $db->quote('submitted'))
			->order($db->quoteName('s.start_time') . ' DESC');
		$db->setQuery($query, 0, 10);

		return $db->loadObjectList() ?: [];
	}

	public function getAuditLogs(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['a.*', 'u.name AS user_name'])
			->from($db->quoteName('#__billing_audit_logs', 'a'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'))
			->order($db->quoteName('a.created_at') . ' DESC');
		$db->setQuery($query, 0, 12);

		return $db->loadObjectList() ?: [];
	}

	public function getCurrentWeek(): array
	{
		return $this->currentWeekBounds();
	}

	private function currentWeekBounds(): array
	{
		$start = new \DateTimeImmutable('monday this week');
		$end = $start->modify('+6 days');

		return [$start->format('Y-m-d'), $end->format('Y-m-d')];
	}
}
