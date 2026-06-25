<?php

namespace Cham\Component\InstructorBilling\Administrator\Model;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\SageService;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class SettingsModel extends BaseDatabaseModel
{
	public function getSageStatus(): array
	{
		return (new SageService())->testConnection();
	}
}
