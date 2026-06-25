<?php

namespace Cham\Component\InstructorBilling\Site\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class SessionController extends BaseController
{
	public function start(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Session')->start($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_STARTED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=dashboard', false));
	}

	public function stop(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Session')->stop((int) AccessService::currentUser()->id, $app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_STOPPED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=dashboard', false));
	}

	public function saveManual(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Session')->saveManual($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_MANUAL_SAVED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=sessions', false));
	}

	public function submitWeek(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$count = $this->getModel('Session')->submitWeek((int) AccessService::currentUser()->id);

		$app->enqueueMessage(Text::sprintf('COM_INSTRUCTOR_BILLING_WEEK_SUBMITTED', $count));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=dashboard', false));
	}

	private function guardInstructor(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::isInstructor());
	}
}
