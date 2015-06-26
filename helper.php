<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 * All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Modules\MySessions;

use Hubzero\Module\Module;
use Component;
use User;

/**
 * Module class for displaying a user's sessions
 */
class Helper extends Module
{
	/**
	 * Set the time when the session will tiemout
	 *
	 * @param   integer  $sess  Session ID
	 * @return  void
	 */
	private function _setTimeout($sess)
	{
		$mwdb = \Components\Tools\Helpers\Utils::getMWDBO();

		$ms = new \Components\Tools\Tables\Session($mwdb);
		$ms->load($sess);
		$ms->timeout = 1209600;
		$ms->store();
	}

	/**
	 * Get the time when the session will tiemout
	 *
	 * @param   integer  $sess  Session ID
	 * @return  string
	 */
	private function _getTimeout($sess)
	{
		$mwdb = \Components\Tools\Helpers\Utils::getMWDBO();

		$ms = new \Components\Tools\Tables\Session($mwdb);
		$remaining = $ms->getTimeout();

		$tl = 'unknown';

		if (is_numeric($remaining))
		{
			$days_left    = floor($remaining/60/60/24);
			$hours_left   = floor(($remaining - $days_left*60*60*24)/60/60);
			$minutes_left = floor(($remaining - $days_left*60*60*24 - $hours_left*60*60)/60);

			$left = array($days_left, $hours_left, $minutes_left);

			$tl  = '';
			$tl .= ($days_left > 0)    ? $days_left .' days, '    : '';
			$tl .= ($hours_left > 0)   ? $hours_left .' hours, '  : '';
			$tl .= ($minutes_left > 0) ? $minutes_left .' minute' : '';
			$tl .= ($minutes_left > 1) ? 's' : '';
		}
		return $tl;
	}

	/**
	 * Display module content
	 *
	 * @return  void
	 */
	public function display()
	{
		// Include mw libraries
		include_once(Component::path('com_tools') . DS . 'helpers' . DS . 'utils.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'job.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'view.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'viewperm.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'session.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'host.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'hosttype.php');
		include_once(Component::path('com_tools') . DS . 'tables' . DS . 'recent.php');

		// Get database object
		$this->database = \App::get('db');

		// Get a connection to the middleware database
		$mwdb = \Components\Tools\Helpers\Utils::getMWDBO();

		// Get tool paras
		$this->toolsConfig = Component::params('com_tools');

		// Set ACL for com_tools
		$authorized = User::authorise('core.manage', 'com_tools');

		// Ensure we have a connection to the middleware
		$this->error = false;
		if (!$mwdb || !$mwdb->connected() || !$this->toolsConfig->get('mw_on') || ($this->toolsConfig->get('mw_on') > 1 && !$authorized))
		{
			$this->error = true;
			return false;
		}

		// Run middleware command to create screenshots
		// only take snapshots if screenshots are on
		if ($this->params->get('show_screenshots', 1))
		{
			$cmd = "/bin/sh ". Component::path('com_tools') . "/scripts/mw screenshot " . User::get('username') . " 2>&1 </dev/null";
			exec($cmd, $results, $status);
		}

		// Get sessions
		$session = new \Components\Tools\Tables\Session($mwdb);
		$this->sessions = $session->getRecords(User::get('username'), '', false);

		// Output module
		require $this->getLayoutPath();
	}
}

