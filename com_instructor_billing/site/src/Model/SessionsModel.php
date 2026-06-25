<?php

namespace Cham\Component\InstructorBilling\Site\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SessionsModel extends BaseDatabaseModel
{
	public function getItems(): array
	{
		SharedServices::load();
		$userId = (int) AccessService::currentUser()->id;
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__driving_sessions'))
			->where($db->quoteName('instructor_user_id') . ' = ' . $userId)
			->order($db->quoteName('start_time') . ' DESC');
		$db->setQuery($query, 0, 200);

		return $db->loadObjectList() ?: [];
	}
}
