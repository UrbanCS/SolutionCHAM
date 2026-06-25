<?php

namespace Cham\Component\InstructorBilling\Administrator\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class SessionController extends BaseController
{
	public function save(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::isManager());

		$app = Factory::getApplication();
		$model = $this->getModel('Session');
		$id = $model->save($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_SAVED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=session&id=' . (int) $id, false));
	}

	public function approve(): void
	{
		$this->setStatus('approved', 'COM_INSTRUCTOR_BILLING_SESSION_APPROVED');
	}

	public function refuse(): void
	{
		$this->setStatus('refused', 'COM_INSTRUCTOR_BILLING_SESSION_REFUSED');
	}

	public function delete(): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::isManager());

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		$this->getModel('Session')->delete($id);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SESSION_DELETED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=sessions', false));
	}

	private function setStatus(string $status, string $messageKey): void
	{
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
		AccessService::denyUnless(AccessService::canApprove());

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		$this->getModel('Session')->setStatus($id, $status);

		$app->enqueueMessage(Text::_($messageKey));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=sessions', false));
	}
}
