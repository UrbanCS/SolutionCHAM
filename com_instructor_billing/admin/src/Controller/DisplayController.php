<?php

namespace Cham\Component\InstructorBilling\Administrator\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
	protected $default_view = 'dashboard';

	public function display($cachable = false, $urlparams = [])
	{
		AccessService::denyUnless(AccessService::isManager());

		return parent::display($cachable, $urlparams);
	}
}
