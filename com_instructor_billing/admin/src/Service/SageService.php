<?php

namespace Cham\Component\InstructorBilling\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class SageService
{
	private const AUTH_URL = 'https://www.sageone.com/oauth2/auth/central';
	private const TOKEN_URL = 'https://oauth.accounting.sage.com/token';

	public function buildAuthorizationUrl(): string
	{
		$params = $this->params();
		$clientId = trim((string) $params->get('sage_client_id', ''));

		if ($clientId === '') {
			throw new \RuntimeException('Client ID Sage manquant.', 400);
		}

		$state = bin2hex(random_bytes(24));
		$this->setSetting('oauth_state', $state);

		return self::AUTH_URL . '?' . http_build_query([
			'response_type' => 'code',
			'client_id'     => $clientId,
			'redirect_uri'  => $this->redirectUri(),
			'scope'         => trim((string) $params->get('sage_scope', 'full_access')) ?: 'full_access',
			'state'         => $state,
			'filter'        => 'apiv3.1',
		]);
	}

	public function handleAuthorizationCode(string $code, string $state): array
	{
		if ($code === '') {
			throw new \RuntimeException('Code OAuth2 Sage manquant.', 400);
		}

		$expectedState = (string) $this->getSetting('oauth_state', '');

		if ($expectedState === '' || !hash_equals($expectedState, $state)) {
			throw new \RuntimeException('État OAuth2 Sage invalide.', 403);
		}

		$token = $this->tokenRequest([
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'redirect_uri' => $this->redirectUri(),
		]);

		$this->storeToken($token);
		$this->deleteSetting('oauth_state');

		return $token;
	}

	public function disconnect(): void
	{
		foreach (['access_token', 'refresh_token', 'expires_at', 'oauth_state', 'token_scope', 'token_type'] as $key) {
			$this->deleteSetting($key);
		}
	}

	public function createInvoice(int $invoiceId): array
	{
		$params = $this->params();

		if (!(bool) $params->get('sage_enabled', 0)) {
			throw new \RuntimeException('Intégration Sage désactivée.', 400);
		}

		$ledgerAccountId = trim((string) $params->get('sage_ledger_account_id', ''));

		if ($ledgerAccountId === '') {
			throw new \RuntimeException('Compte de grand livre Sage manquant.', 400);
		}

		$invoice = $this->getInvoice($invoiceId);

		if (!$invoice) {
			throw new \RuntimeException('Facture introuvable.', 404);
		}

		if (!empty($invoice->sage_invoice_id)) {
			return [
				'ready'          => true,
				'alreadySynced'  => true,
				'invoiceId'      => $invoiceId,
				'sageInvoiceId'  => $invoice->sage_invoice_id,
				'message'        => 'Facture déjà synchronisée avec Sage.',
			];
		}

		$items = (new ExportService())->getInvoiceItems($invoiceId);

		if (!$items) {
			throw new \RuntimeException('Aucune ligne de facture à synchroniser.', 400);
		}

		try {
			$contactId = $this->syncCustomer((int) $invoice->instructor_user_id)['sageContactId'];
			$payload = $this->buildInvoicePayload($invoice, $items, $contactId, $ledgerAccountId);
			$endpoint = $this->documentEndpoint();
			$response = $this->apiRequest('POST', '/' . $endpoint, $payload);
			$document = $response[$this->documentRoot()] ?? $response;
			$sageInvoiceId = (string) ($document['id'] ?? '');
			$sageInvoiceNumber = (string) ($document['reference'] ?? $document['displayed_as'] ?? $document['id'] ?? '');

			if ($sageInvoiceId === '') {
				throw new \RuntimeException('Sage n’a pas retourné d’identifiant de facture.');
			}

			$this->markInvoiceSynced($invoiceId, $sageInvoiceId, $sageInvoiceNumber);
			AuditService::log('sage.invoice.synced', 'invoice', $invoiceId, ['sage_invoice_id' => $sageInvoiceId]);

			return [
				'ready'         => true,
				'invoiceId'     => $invoiceId,
				'sageInvoiceId' => $sageInvoiceId,
				'message'       => 'Facture synchronisée avec Sage.',
			];
		} catch (\Throwable $e) {
			$this->markInvoiceFailed($invoiceId, $e->getMessage());
			AuditService::log('sage.invoice.failed', 'invoice', $invoiceId, ['error' => $e->getMessage()]);
			throw $e;
		}
	}

	public function syncCustomer(int $instructorId): array
	{
		$params = $this->params();
		$defaultContactId = trim((string) $params->get('sage_default_contact_id', ''));
		$profile = $this->getInstructorProfile($instructorId);

		if (!$profile) {
			throw new \RuntimeException('Profil instructeur introuvable.', 404);
		}

		if (!empty($profile->sage_contact_id)) {
			return [
				'ready'         => true,
				'instructorId'  => $instructorId,
				'sageContactId' => $profile->sage_contact_id,
				'message'       => 'Contact Sage déjà associé.',
			];
		}

		if ($defaultContactId !== '') {
			return [
				'ready'         => true,
				'instructorId'  => $instructorId,
				'sageContactId' => $defaultContactId,
				'message'       => 'Contact Sage par défaut utilisé.',
			];
		}

		$payload = [
			'contact' => array_filter([
				'name'             => $profile->name,
				'reference'        => 'joomla-user-' . $profile->user_id,
				'contact_type_ids' => [trim((string) $params->get('sage_contact_type', 'VENDOR')) ?: 'VENDOR'],
				'email'            => $profile->email,
				'telephone'        => $profile->phone,
			], static fn ($value) => $value !== null && $value !== ''),
		];

		$response = $this->apiRequest('POST', '/contacts', $payload);
		$contact = $response['contact'] ?? $response;
		$contactId = (string) ($contact['id'] ?? '');

		if ($contactId === '') {
			throw new \RuntimeException('Sage n’a pas retourné d’identifiant de contact.');
		}

		$this->saveSageContactId((int) $profile->user_id, $contactId);
		AuditService::log('sage.contact.synced', 'instructor_profile', (int) $profile->id, ['sage_contact_id' => $contactId]);

		return [
			'ready'         => true,
			'instructorId'  => $instructorId,
			'sageContactId' => $contactId,
			'message'       => 'Contact Sage créé.',
		];
	}

	public function testConnection(): array
	{
		$params = $this->params();
		$enabled = (bool) $params->get('sage_enabled', 0);
		$hasClientId = trim((string) $params->get('sage_client_id', '')) !== '';
		$hasClientSecret = trim((string) $params->get('sage_client_secret', '')) !== '';
		$hasToken = $this->getSetting('refresh_token', '') !== '' || $this->getSetting('access_token', '') !== '';
		$hasLedger = trim((string) $params->get('sage_ledger_account_id', '')) !== '';

		if (!$enabled) {
			return [
				'ready'           => false,
				'enabled'         => false,
				'hasClientId'     => $hasClientId,
				'hasClientSecret' => $hasClientSecret,
				'connected'       => false,
				'hasLedger'       => $hasLedger,
				'expiresAt'       => $this->getSetting('expires_at', ''),
				'businessId'      => trim((string) $params->get('sage_business_id', '')),
				'redirectUri'     => $this->redirectUri(),
				'message'         => 'Intégration Sage désactivée.',
			];
		}

		if (!$hasClientId || !$hasClientSecret) {
			return [
				'ready'           => false,
				'enabled'         => true,
				'hasClientId'     => $hasClientId,
				'hasClientSecret' => $hasClientSecret,
				'connected'       => false,
				'hasLedger'       => $hasLedger,
				'expiresAt'       => $this->getSetting('expires_at', ''),
				'businessId'      => trim((string) $params->get('sage_business_id', '')),
				'redirectUri'     => $this->redirectUri(),
				'message'         => 'Client ID ou secret Sage manquant.',
			];
		}

		if (!$hasToken) {
			return [
				'ready'           => false,
				'enabled'         => true,
				'hasClientId'     => true,
				'hasClientSecret' => true,
				'connected'       => false,
				'hasLedger'       => $hasLedger,
				'expiresAt'       => '',
				'businessId'      => trim((string) $params->get('sage_business_id', '')),
				'redirectUri'     => $this->redirectUri(),
				'message'         => 'OAuth2 Sage non connecté.',
			];
		}

		try {
			$response = $this->apiRequest('GET', '/businesses');
			$businesses = $response['$items'] ?? $response['items'] ?? $response['businesses'] ?? [];

			return [
				'ready'           => $hasLedger,
				'enabled'         => true,
				'hasClientId'     => true,
				'hasClientSecret' => true,
				'connected'       => true,
				'hasLedger'       => $hasLedger,
				'expiresAt'       => $this->getSetting('expires_at', ''),
				'businessId'      => trim((string) $params->get('sage_business_id', '')),
				'redirectUri'     => $this->redirectUri(),
				'businesses'      => is_array($businesses) ? $businesses : [],
				'message'         => $hasLedger ? 'Connexion Sage active.' : 'Connexion Sage active, mais compte de grand livre manquant.',
			];
		} catch (\Throwable $e) {
			return [
				'ready'           => false,
				'enabled'         => true,
				'hasClientId'     => true,
				'hasClientSecret' => true,
				'connected'       => false,
				'hasLedger'       => $hasLedger,
				'expiresAt'       => $this->getSetting('expires_at', ''),
				'businessId'      => trim((string) $params->get('sage_business_id', '')),
				'redirectUri'     => $this->redirectUri(),
				'message'         => 'Test Sage échoué: ' . $e->getMessage(),
			];
		}
	}

	private function buildInvoicePayload(object $invoice, array $items, string $contactId, string $ledgerAccountId): array
	{
		$params = $this->params();
		$root = $this->documentRoot();
		$dueDays = max(0, (int) $params->get('sage_due_days', 0));
		$taxRateId = trim((string) $params->get('sage_tax_rate_id', ''));
		$documentDate = (new \DateTimeImmutable((string) $invoice->period_end))->format('Y-m-d');
		$dueDate = (new \DateTimeImmutable($documentDate))->modify('+' . $dueDays . ' days')->format('Y-m-d');
		$lines = [];

		foreach ($items as $item) {
			$line = [
				'description'       => (string) $item->description,
				'ledger_account_id' => $ledgerAccountId,
				'quantity'          => (float) $item->quantity_hours,
				'unit_price'        => (float) $item->hourly_rate,
			];

			if ($taxRateId !== '') {
				$line['tax_rate_id'] = $taxRateId;
			}

			$lines[] = $line;
		}

		return [
			$root => [
				'contact_id'    => $contactId,
				'date'          => $documentDate,
				'due_date'      => $dueDate,
				'reference'     => $invoice->invoice_number,
				'invoice_lines' => $lines,
				'notes'         => 'Facture hebdomadaire instructeur ' . $invoice->period_start . ' au ' . $invoice->period_end,
			],
		];
	}

	private function apiRequest(string $method, string $path, ?array $payload = null, bool $retry = true): array
	{
		$accessToken = $this->accessToken();
		$params = $this->params();
		$base = rtrim((string) $params->get('sage_api_base', 'https://api.accounting.sage.com/v3.1'), '/');
		$url = $base . '/' . ltrim($path, '/');
		$headers = [
			'Authorization: Bearer ' . $accessToken,
			'Accept: application/json',
			'Content-Type: application/json',
			'User-Agent: Joomla Instructor Billing',
		];
		$businessId = trim((string) $params->get('sage_business_id', ''));

		if ($businessId !== '') {
			$headers[] = 'X-Business: ' . $businessId;
		}

		$result = $this->curl($method, $url, $headers, $payload !== null ? json_encode($payload) : null);

		if ($result['status'] === 401 && $retry) {
			$this->refreshAccessToken();

			return $this->apiRequest($method, $path, $payload, false);
		}

		$json = $this->decodeJson($result['body']);

		if ($result['status'] < 200 || $result['status'] >= 300) {
			$message = $json['message'] ?? $json['error'] ?? $json['error_description'] ?? $result['body'] ?? 'Erreur Sage.';
			throw new \RuntimeException('Sage HTTP ' . $result['status'] . ': ' . substr((string) $message, 0, 500), $result['status']);
		}

		return $json;
	}

	private function accessToken(): string
	{
		$accessToken = (string) $this->getSetting('access_token', '');
		$expiresAt = (int) $this->getSetting('expires_at', '0');

		if ($accessToken !== '' && $expiresAt > time() + 60) {
			return $accessToken;
		}

		$this->refreshAccessToken();
		$accessToken = (string) $this->getSetting('access_token', '');

		if ($accessToken === '') {
			throw new \RuntimeException('Jeton Sage absent. Reconnectez OAuth2.', 401);
		}

		return $accessToken;
	}

	private function refreshAccessToken(): void
	{
		$refreshToken = (string) $this->getSetting('refresh_token', '');

		if ($refreshToken === '') {
			throw new \RuntimeException('Refresh token Sage absent. Reconnectez OAuth2.', 401);
		}

		$token = $this->tokenRequest([
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refreshToken,
		]);

		$this->storeToken($token);
	}

	private function tokenRequest(array $fields): array
	{
		$params = $this->params();
		$clientId = trim((string) $params->get('sage_client_id', ''));
		$clientSecret = trim((string) $params->get('sage_client_secret', ''));

		if ($clientId === '' || $clientSecret === '') {
			throw new \RuntimeException('Identifiants OAuth2 Sage manquants.', 400);
		}

		$headers = [
			'Accept: application/json',
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
		];
		$result = $this->curl('POST', self::TOKEN_URL, $headers, http_build_query($fields));
		$json = $this->decodeJson($result['body']);

		if ($result['status'] < 200 || $result['status'] >= 300) {
			$message = $json['error_description'] ?? $json['error'] ?? $result['body'] ?? 'Erreur OAuth2 Sage.';
			throw new \RuntimeException('OAuth2 Sage HTTP ' . $result['status'] . ': ' . substr((string) $message, 0, 500), $result['status']);
		}

		return $json;
	}

	private function curl(string $method, string $url, array $headers, ?string $body): array
	{
		if (!function_exists('curl_init')) {
			throw new \RuntimeException('Extension PHP cURL requise pour Sage.');
		}

		$handle = curl_init($url);
		curl_setopt_array($handle, [
			CURLOPT_CUSTOMREQUEST  => strtoupper($method),
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => false,
			CURLOPT_TIMEOUT        => 30,
		]);

		if ($body !== null && $body !== '') {
			curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
		}

		$response = curl_exec($handle);
		$error = curl_error($handle);
		$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		curl_close($handle);

		if ($response === false) {
			throw new \RuntimeException('Erreur cURL Sage: ' . $error);
		}

		return [
			'status' => $status,
			'body'   => (string) $response,
		];
	}

	private function decodeJson(string $body): array
	{
		if ($body === '') {
			return [];
		}

		$json = json_decode($body, true);

		if (!is_array($json)) {
			throw new \RuntimeException('Réponse Sage non JSON: ' . substr($body, 0, 300));
		}

		return $json;
	}

	private function storeToken(array $token): void
	{
		if (empty($token['access_token'])) {
			throw new \RuntimeException('Réponse OAuth2 Sage sans access_token.');
		}

		$this->setSetting('access_token', (string) $token['access_token']);

		if (!empty($token['refresh_token'])) {
			$this->setSetting('refresh_token', (string) $token['refresh_token']);
		}

		$this->setSetting('expires_at', (string) (time() + max(0, (int) ($token['expires_in'] ?? 3600)) - 60));
		$this->setSetting('token_scope', (string) ($token['scope'] ?? ''));
		$this->setSetting('token_type', (string) ($token['token_type'] ?? 'Bearer'));
	}

	private function getInvoice(int $invoiceId): ?object
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select([
				'i.*',
				'u.name AS instructor_name',
				'u.email AS instructor_email',
			])
			->from($db->quoteName('#__invoices', 'i'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('i.instructor_user_id'))
			->where($db->quoteName('i.id') . ' = ' . (int) $invoiceId);
		$db->setQuery($query);

		return $db->loadObject() ?: null;
	}

	private function getInstructorProfile(int $userId): ?object
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(['p.*', 'u.name', 'u.email'])
			->from($db->quoteName('#__instructor_profiles', 'p'))
			->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
			->where($db->quoteName('p.user_id') . ' = ' . (int) $userId);
		$db->setQuery($query);

		return $db->loadObject() ?: null;
	}

	private function saveSageContactId(int $userId, string $contactId): void
	{
		$db = Factory::getDbo();
		$row = (object) [
			'user_id'         => $userId,
			'sage_contact_id' => $contactId,
			'updated_at'      => Factory::getDate()->toSql(),
		];
		$db->updateObject('#__instructor_profiles', $row, 'user_id');
	}

	private function markInvoiceSynced(int $invoiceId, string $sageInvoiceId, string $sageInvoiceNumber): void
	{
		$db = Factory::getDbo();
		$row = (object) [
			'id'                  => $invoiceId,
			'sage_invoice_id'     => $sageInvoiceId,
			'sage_invoice_number' => $sageInvoiceNumber ?: $sageInvoiceId,
			'sage_synced_at'      => Factory::getDate()->toSql(),
			'sage_sync_status'    => 'synced',
			'sage_sync_error'     => null,
			'updated_at'          => Factory::getDate()->toSql(),
		];
		$db->updateObject('#__invoices', $row, 'id');
	}

	private function markInvoiceFailed(int $invoiceId, string $error): void
	{
		$db = Factory::getDbo();
		$row = (object) [
			'id'               => $invoiceId,
			'sage_sync_status' => 'failed',
			'sage_sync_error'  => substr($error, 0, 5000),
			'updated_at'       => Factory::getDate()->toSql(),
		];
		$db->updateObject('#__invoices', $row, 'id');
	}

	private function documentEndpoint(): string
	{
		return $this->documentRoot() === 'sales_invoice' ? 'sales_invoices' : 'purchase_invoices';
	}

	private function documentRoot(): string
	{
		return (string) $this->params()->get('sage_document_type', 'purchase_invoice') === 'sales_invoice'
			? 'sales_invoice'
			: 'purchase_invoice';
	}

	private function redirectUri(): string
	{
		$configured = trim((string) $this->params()->get('sage_redirect_uri', ''));

		if ($configured !== '') {
			return $configured;
		}

		return Uri::root() . 'administrator/index.php?option=com_instructor_billing&task=sage.callback';
	}

	private function params()
	{
		return ComponentHelper::getParams(AccessService::COMPONENT);
	}

	private function getSetting(string $key, string $default = ''): string
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('setting_value'))
			->from($db->quoteName('#__instructor_billing_sage_settings'))
			->where($db->quoteName('setting_key') . ' = ' . $db->quote($key));
		$db->setQuery($query);

		$value = $db->loadResult();

		return $value === null ? $default : (string) $value;
	}

	private function setSetting(string $key, string $value): void
	{
		$db = Factory::getDbo();
		$now = Factory::getDate()->toSql();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__instructor_billing_sage_settings'))
			->where($db->quoteName('setting_key') . ' = ' . $db->quote($key));
		$db->setQuery($query);
		$id = (int) $db->loadResult();

		$row = (object) [
			'setting_key'   => $key,
			'setting_value' => $value,
			'updated_at'    => $now,
		];

		if ($id > 0) {
			$row->id = $id;
			$db->updateObject('#__instructor_billing_sage_settings', $row, 'id');
		} else {
			$row->created_at = $now;
			$db->insertObject('#__instructor_billing_sage_settings', $row, 'id');
		}
	}

	private function deleteSetting(string $key): void
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__instructor_billing_sage_settings'))
			->where($db->quoteName('setting_key') . ' = ' . $db->quote($key));
		$db->setQuery($query)->execute();
	}
}
