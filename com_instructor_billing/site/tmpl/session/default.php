<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('stylesheet', 'com_instructor_billing/site.css', ['version' => 'auto', 'relative' => true]);
HTMLHelper::_('script', 'com_instructor_billing/tracker.js', ['version' => 'auto', 'relative' => true], ['defer' => true]);

$dashboardUrl = Uri::getInstance()->getPath() . '?' . http_build_query(['option' => 'com_instructor_billing', 'view' => 'dashboard']);
$returnUrl = base64_encode(Uri::getInstance()->toString());
?>

<div class="ib-site">
	<section class="ib-panel">
		<div class="ib-panel-head">
			<h1>Ajouter un cours</h1>
			<a href="<?php echo htmlspecialchars($dashboardUrl); ?>">Retour</a>
		</div>
		<form class="ib-manual" method="post" data-gps-form data-gps-mode="manual" action="<?php echo Route::_('index.php?option=com_instructor_billing&task=session.saveManual'); ?>">
			<input type="hidden" name="option" value="com_instructor_billing">
			<input type="hidden" name="task" value="session.saveManual">
			<input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
			<label>Élève/client <input name="student_name"></label>
			<label>Début <input name="start_time" type="datetime-local" required></label>
			<label>Fin <input name="end_time" type="datetime-local" required></label>
			<label>Notes <textarea name="notes" rows="4"></textarea></label>
			<input type="hidden" name="start_lat" data-gps-start-lat>
			<input type="hidden" name="start_lng" data-gps-start-lng>
			<input type="hidden" name="end_lat" data-gps-end-lat>
			<input type="hidden" name="end_lng" data-gps-end-lng>
			<button class="ib-action" type="submit">Ajouter manuellement</button>
			<p class="ib-gps-note" data-gps-status>GPS optionnel: les coordonnées ne sont enregistrées que si le navigateur l’autorise.</p>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</section>
</div>
