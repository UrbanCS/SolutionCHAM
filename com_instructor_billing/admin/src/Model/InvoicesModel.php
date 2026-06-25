<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class InvoicesModel extends BaseDatabaseModel
{
	public function getItems(): array
	{
		$app = Factory::getApplication();
		$db = Factory::getDbo();
		$instructorId = $app->input->getInt('filter_instructor');
		$status = $app->input->getCmd('filter_status', '');

		$query = $db->getQuery(true)
			->select(['i.*', 'u.name AS instructor_name'])
			->from($db->quoteName('#__invoices', 'i'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('i.instructor_user_id'));

		if ($instructorId > 0) {
			$query->where($db->quoteName('i.instructor_user_id') . ' = ' . $instructorId);
		}

		if (in_array($status, ['draft', 'sent', 'paid', 'cancelled'], true)) {
			$query->where($db->quoteName('i.status') . ' = ' . $db->quote($status));
		}

		$query->order($db->quoteName('i.created_at') . ' DESC');
		$db->setQuery($query, 0, 200);

		return $db->loadObjectList() ?: [];
	}

	public function getInstructors(): array
	{
		return (new SessionsModel())->getInstructors();
	}

	public function getDefaultPeriod(): array
	{
		$start = new \DateTimeImmutable('monday last week');
		$end = $start->modify('+6 days');

		return [$start->format('Y-m-d'), $end->format('Y-m-d')];
	}
}
