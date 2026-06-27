<?php

namespace Cham\Component\InstructorBilling\Site\Controller;

defined('_JEXEC') or die;

use Cham\Component\InstructorBilling\Administrator\Service\AccessService;
use Cham\Component\InstructorBilling\Administrator\Service\InvoiceService;
use Cham\Component\InstructorBilling\Site\Service\SharedServices;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

class ManagementController extends BaseController
{
	public function approveSession(): void
	{
		$this->guardApprove();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Management')->setSessionStatus($app->input->getInt('id'), 'approved');
		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_MANAGEMENT_SESSION_APPROVED'));
		$this->redirectBack();
	}

	public function refuseSession(): void
	{
		$this->guardApprove();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Management')->setSessionStatus($app->input->getInt('id'), 'refused');
		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_MANAGEMENT_SESSION_REFUSED'));
		$this->redirectBack();
	}

	public function generateInvoice(): void
	{
		$this->guardInvoice();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$data = $app->input->post->getArray();
		$invoiceId = (new InvoiceService())->generateWeeklyInvoice(
			(int) ($data['instructor_user_id'] ?? 0),
			(string) ($data['period_start'] ?? ''),
			(string) ($data['period_end'] ?? ''),
			(int) AccessService::currentUser()->id
		);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_GENERATED'));
		$this->redirectBack(['invoice_id' => $invoiceId]);
	}

	public function saveProfile(): void
	{
		$this->guardInvoice();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Management')->saveProfile($app->input->post->getArray());

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_PROFILE_SAVED'));
		$this->redirectBack();
	}

	public function updateInvoiceStatus(): void
	{
		$this->guardInvoice();
		Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->getModel('Management')->updateInvoiceStatus(
			$app->input->getInt('id'),
			$app->input->post->getCmd('status', 'draft')
		);

		$app->enqueueMessage(Text::_('COM_INSTRUCTOR_BILLING_INVOICE_STATUS_UPDATED'));
		$this->redirectBack();
	}

	private function guardApprove(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canApprove());
	}

	private function guardInvoice(): void
	{
		SharedServices::load();
		AccessService::denyUnless(AccessService::canInvoice());
	}

	private function redirectBack(array $query = []): void
	{
		$app = Factory::getApplication();
		$encoded = $app->input->post->getString('return', '');
		$return = $encoded !== '' ? base64_decode($encoded, true) : false;

		if (is_string($return) && $return !== '' && Uri::isInternal($return)) {
			if ($query) {
				$separator = str_contains($return, '?') ? '&' : '?';
				$return .= $separator . http_build_query($query);
			}

			$this->setRedirect($return);

			return;
		}

		$this->setRedirect(Route::_('index.php?option=com_instructor_billing&view=management', false));
	}
}
