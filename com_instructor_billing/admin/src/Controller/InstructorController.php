<?php

namespace Cham\Component\InstructorBilling\Administrator\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class InstructorController extends BaseController
{
	public function saveProfile(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::isManager());

		$app = Factory::getApplication();
		$model = $this->getModel('Instructors');
		$model->saveProfile($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_PROFILE_SAVED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=instructors', false));
	}
}
