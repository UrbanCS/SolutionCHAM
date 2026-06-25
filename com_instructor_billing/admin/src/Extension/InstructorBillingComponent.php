<?php

namespace Cham\Component\InstructorBilling\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;

class InstructorBillingComponent extends MVCComponent implements RouterServiceInterface
{
	use RouterServiceTrait;
}
