<?php

namespace Cham\Component\InstructorBilling\Administrator\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\SageService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class SageController extends BaseController
{
	public function connect(): void
	{
		AccessService::denyUnless(AccessService::isManager());
		Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));

		Factory::getApplication()->redirect((new SageService())->buildAuthorizationUrl());
	}

	public function callback(): void
	{
		AccessService::denyUnless(AccessService::isManager());

		$app = Factory::getApplication();
		$code = $app->input->getString('code', '');
		$state = $app->input->getString('state', '');
		$error = $app->input->getString('error', '');

		if ($error !== '') {
			$app->enqueueMessage('Sage OAuth2: ' . $error, 'error');
			$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=settings', false));

			return;
		}

		(new SageService())->handleAuthorizationCode($code, $state);
		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SAGE_CONNECTED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=settings', false));
	}

	public function disconnect(): void
	{
		AccessService::denyUnless(AccessService::isManager());
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		(new SageService())->disconnect();
		Factory::getApplication()->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SAGE_DISCONNECTED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=settings', false));
	}

	public function syncInvoice(): void
	{
		AccessService::denyUnless(AccessService::canInvoice());
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$id = $app->input->getInt('id');
		(new SageService())->createInvoice($id);
		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_SAGE_INVOICE_SYNCED'));
		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=invoice&id=' . $id, false));
	}
}
