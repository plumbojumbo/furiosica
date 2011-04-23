<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Menu for login group of actions
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Menu
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/widget.php';

/**
 * Menu for login group of actions
 *
 * @category Output
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 *
 * @see      Widget
 */

class LoginGroupNav extends Widget
{
    var $action = null;

    /**
     * Construction
     *
     * @param Action $action current action, used for output
     */

    function __construct($action=null)
    {
        parent::__construct($action);
        $this->action = $action;
    }

    /**
     * Show the menu
     *
     * @return void
     */

    function show()
    {
		$action_name = $this->action->trimmed('action');
		
		$code = $this->action->trimmed('code');
		
		$this->action->elementStart('ul', array('class' => 'nav'));
		$this->action->menuItem(common_local_url('login', null),
                                    _('Login'), _('Login with a username and password'),
                                    $action_name === 'login');
		if ($code) {
			$this->action->menuItem(common_local_url('register', array('code' => $code)),
                                    _('Register'), _('Sign up for a new account'),
                                    $action_name === 'register');
		}
		$this->action->menuItem(common_local_url('openidlogin', ($code) ? array('code' => $code) : null),
                                    _('OpenID'), _('Login or register with OpenID'),
                                    $action_name === 'openidlogin');
		$this->action->elementEnd('ul');
    }
}
