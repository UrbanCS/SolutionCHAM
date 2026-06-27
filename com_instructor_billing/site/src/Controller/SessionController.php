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
use Joomla\CMS\Uri\Uri;

class SessionController extends BaseController
{
	public function start(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Session')->start($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_STARTED'));
		$this->redirectBack('index.php?option=com_instructor_billing&view=dashboard');
	}

	public function stop(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Session')->stop((int) AccessService::currentUser()->id, $app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_STOPPED'));
		$this->redirectBack('index.php?option=com_instructor_billing&view=dashboard');
	}

	public function saveManual(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Session')->saveManual($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_MANUAL_SAVED'));
		$this->redirectBack('index.php?option=com_instructor_billing&view=dashboard');
	}

	public function submitWeek(): void
	{
		$this->guardInstructor();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$count = $this->getModel('Session')->submitWeek((int) AccessService::currentUser()->id);

		$app->enqueueMessage(Text::sprintf('COM_INSTRUCTOR_BILLING_WEEK_SUBMITTED', $count));
		$this->redirectBack('index.php?option=com_instructor_billing&view=dashboard');
	}

	private function guardInstructor(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::isInstructor());
	}

	private function redirectBack(string $fallback): void
	{
		$app = Factory::getApplication();
		$encoded = $app->input->post->getString('return', '');
		$return = $encoded !== '' ? base64_decode($encoded, true) : false;

		if (is_string($return) && $return !== '' && Uri::isInternal($return)) {
			$this->setRedirect($return);

			return;
		}

		$this->setRedirect(Route::_($fallback, false));
	}
}
