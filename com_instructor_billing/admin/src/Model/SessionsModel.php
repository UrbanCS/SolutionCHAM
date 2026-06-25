<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SessionsModel extends BaseDatabaseModel
{
	public function getItems(): array
	{
		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$instructorId = $app->input->getInt('filter_instructor');
		$status = $app->input->getCmd('filter_status', '');
		$dateFrom = $app->input->getString('filter_from', '');
		$dateTo = $app->input->getString('filter_to', '');

		$query = $db->getQuery(true)
			->select(['s.*', 'u.name AS instructor_name'])
			->from($db->quoteName('#__driving_sessions', 's'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('s.instructor_user_id'));

		if ($instructorId > 0) {
			$query->where($db->quoteName('s.instructor_user_id') . ' = ' . $instructorId);
		}

		if (in_array($status, ['draft', 'submitted', 'approved', 'refused'], true)) {
			$query->where($db->quoteName('s.status') . ' = ' . $db->quote($status));
		}

		if ($dateFrom !== '') {
			$query->where($db->quoteName('s.start_time') . ' >= ' . $db->quote($dateFrom . ' 00:00:00'));
		}

		if ($dateTo !== '') {
			$query->where($db->quoteName('s.start_time') . ' <= ' . $db->quote($dateTo . ' 23:59:59'));
		}

		$query->order($db->quoteName('s.start_time') . ' DESC');
		$db->setQuery($query, 0, 200);

		return $db->loadObjectList() ?: [];
	}

	public function getInstructors(): array
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['p.user_id', 'u.name'])
			->from($db->quoteName('#__instructor_profiles', 'p'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
			->where($db->quoteName('p.active') . ' = 1')
			->order($db->quoteName('u.name') . ' ASC');
		$db->setQuery($query);

		return $db->loadObjectList() ?: [];
	}
}
