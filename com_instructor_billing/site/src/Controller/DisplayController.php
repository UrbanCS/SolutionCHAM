<?php

namespace Cham\Component\InstructorBilling\Site\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
	protected $default_view = 'dashboard';

	public function display($cachable = false, $urlparams = [])
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::isInstructor());

		return parent::display($cachable, $urlparams);
	}
}
