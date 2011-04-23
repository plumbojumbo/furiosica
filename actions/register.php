<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Register a new user account
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
 * @category  Login
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

/**
 * An action for registering a new user account
 *
 * @category Login
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class RegisterAction extends Action
{
    /**
     * Has there been an error?
     */

    var $error = null;

    /**
     * Have we registered?
     */

    var $registered = false;

    /**
     * Title of the page
     *
     * @return string title
     */

    function title()
    {
        if ($this->registered) {
            return _('Registration successful');
        } else {
            return _('Register');
        }
    }
	
    /**
     * Handle input, produce output
     *
     * Switches on request method; either shows the form or handles its input.
     *
     * Checks if registration is closed and shows an error if so.
     *
     * @param array $args $_REQUEST data
     *
     * @return void
     */

    function handle($args)
    {
        parent::handle($args);
		
		$code = $this->trimmed('code');

        if ($code) {
            $invite = Invitation::staticGet($code);
        }

        if (common_config('site', 'closed')) {
            $this->clientError(_('Registration not allowed.'));
        } else if (common_logged_in()) {
            $this->clientError(_('Already logged in.'));
        } else if (common_config('site', 'inviteonly') && !($code && $invite)) {
			$this->clientError(_('Sorry, only invited people can register.'));
		} elseif (common_config('site', 'inviteonly') && $invite && ($invite->registered_user_id > 0)) {
            $this->clientError(_('Invalid invitation code.'));
		} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->tryRegister();
        } else {
            $this->showForm();
        }
    }

    /**
     * Try to register a user
     *
     * Validates the input and tries to save a new user and profile
     * record. On success, shows an instructions page.
     *
     * @return void
     */

    function tryRegister()
    {
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            $this->showForm(_('There was a problem with your session token. '.
                              'Try again, please.'));
            return;
        }

        $nickname = common_parse_nickname($this->trimmed('fullname'));
        $email    = $this->trimmed('email');
        $fullname = $this->trimmed('fullname');
		
        $homepage = $this->trimmed('homepage');
		if ($homepage && !preg_match('!^https?://!i', $homepage)) {
			$homepage = 'http://' . $homepage;
		}
		
        $bio      = $this->trimmed('bio');
        $location = $this->trimmed('location');

        // We don't trim these... whitespace is OK in a password!

        $password = $this->arg('password');
        $confirm  = $this->arg('confirm');

        // invitation code, if any

        $code = $this->trimmed('code');

        if ($code) {
            $invite = Invitation::staticGet($code);
        }

        if (common_config('site', 'inviteonly') && !($code && $invite)) {
            $this->clientError(_('Sorry, only invited people can register.'));
            return;
        } elseif (common_config('site', 'inviteonly') && $invite && ($invite->registered_user_id > 0)) {
            $this->clientError(_('Invalid invitation code.'));
            return;
        }

        // Input scrubbing

		// is scrubbed above
        //$nickname = common_canonical_nickname($nickname); 
        $email    = common_canonical_email($email);

        if ($email && !Validate::email($email, true)) {
            $this->showForm(_('Not a valid email address.'));
        } /*else if (!Validate::string($nickname, array('min_length' => 1,
                                                      'max_length' => 64,
                                                      'format' => NICKNAME_FMT))) {
            $this->showForm(_('Nickname must have only lowercase letters '.
                              'and numbers and no spaces.'));
        } */ else if ($this->nicknameExists($nickname)) {
            $this->showForm(_('Nickname already in use. Try adding some extra id to year full name.'));
        } else if (!User::allowed_nickname($nickname)) {
            $this->showForm(_('Not a valid nickname.'));
        } else if ($this->emailExists($email)) {
            $this->showForm(_('Email address already exists.'));
        } else if (!is_null($homepage) && (strlen($homepage) > 0) &&
                   !Validate::uri($homepage,
                                  array('allowed_schemes' =>
                                        array('http', 'https')))) {
            $this->showForm(_('Homepage is not a valid URL.'));
            return;
        } else if (count(split(' ', $fullname, 2)) < 2) {
			$this->showForm(_('Please enter your full name.'));
            return;
		} else if (!is_null($fullname) && mb_strlen($fullname) > 255) {
            $this->showForm(_('Full name is too long (max 255 chars).'));
            return;
        } else if (!is_null($bio) && mb_strlen($bio) > 140) {
            $this->showForm(_('Bio is too long (max 140 chars).'));
            return;
        } else if (!is_null($location) && mb_strlen($location) > 255) {
            $this->showForm(_('Location is too long (max 255 chars).'));
            return;
        } else if (strlen($password) < 6) {
            $this->showForm(_('Password must be 6 or more characters.'));
            return;
        } else if ($password != $confirm) {
            $this->showForm(_('Passwords don\'t match.'));
        } else if ($user = User::register(array('nickname' => $nickname,
                                                'password' => $password,
                                                'email' => $email,
                                                'fullname' => $fullname,
                                                'homepage' => $homepage,
                                                'bio' => $bio,
                                                'location' => $location,
                                                'code' => $code))) {
            if (!$user) {
                $this->showForm(_('Invalid username or password.'));
                return;
            }
            // success!
            if (!common_set_user($user)) {
                $this->serverError(_('Error setting user.'));
                return;
            }
            // this is a real login
            common_real_login(true);
            if ($this->boolean('rememberme')) {
                common_debug('Adding rememberme cookie for ' . $nickname);
                common_rememberme($user);
            }
            // Re-init language env in case it changed (not yet, but soon)
            common_init_language();
			// mark invite code as used
			if ($invite) {
				$orig = clone($invite);
				$invite->registered_user_id = $user->id;
				$invite->update($orig);
			}
            $this->showSuccess();

		} else {
            $this->showForm(_('Invalid username or password.'));
        }
    }

    /**
     * Does the given nickname already exist?
     *
     * Checks a canonical nickname against the database.
     *
     * @param string $nickname nickname to check
     *
     * @return boolean true if the nickname already exists
     */

    function nicknameExists($nickname)
    {
        $user = User::staticGet('nickname', $nickname);
        return ($user !== false);
    }

    /**
     * Does the given email address already exist?
     *
     * Checks a canonical email address against the database.
     *
     * @param string $email email address to check
     *
     * @return boolean true if the address already exists
     */

    function emailExists($email)
    {
        $email = common_canonical_email($email);
        if (!$email || strlen($email) == 0) {
            return false;
        }
        $user = User::staticGet('email', $email);
        return ($user !== false);
    }

    // overrrided to add entry-title class
    function showPageTitle() {
        $this->element('h1', array('class' => 'entry-title'), $this->title());
    }

    // overrided to add hentry, and content-inner class
    function showContentBlock()
     {
         $this->elementStart('div', array('id' => 'content', 'class' => 'hentry'));
         $this->showPageTitle();
         $this->showPageNoticeBlock();
         $this->elementStart('div', array('id' => 'content_inner',
             'class' => 'entry-content'));
         // show the actual content (forms, lists, whatever)
         $this->showContent();
         $this->elementEnd('div');
         $this->elementEnd('div');
     }

    /**
     * Instructions or a notice for the page
     *
     * Shows the error, if any, or instructions for registration.
     *
     * @return void
     */

    function showPageNotice()
    {
        if ($this->registered) {
            return;
        } else if ($this->error) {
            $this->element('p', 'error', $this->error);
        } else {
            $instr =
              common_markup_to_html(_('With this form you can create '.
                                      ' a new account. ' .
                                      'Please enter your full name, your nickname should be like *firstnamelastname*.'));

            $this->elementStart('div', 'instructions');
            $this->raw($instr);
            $this->elementEnd('div');
        }
    }

    /**
     * Wrapper for showing a page
     *
     * Stores an error and shows the page
     *
     * @param string $error Error, if any
     *
     * @return void
     */

    function showForm($error=null)
    {
        $this->error = $error;
        $this->showPage();
    }

    /**
     * Show the page content
     *
     * Either shows the registration form or, if registration was successful,
     * instructions for using the site.
     *
     * @return void
     */

    function showContent()
    {
        if ($this->registered) {
            $this->showSuccessContent();
        } else {
            $this->showFormContent();
        }
    }

    /**
     * Show the registration form
     *
     * @return void
     */

    function showFormContent()
    {
        $code = $this->trimmed('code');

        if ($code) {
            $invite = Invitation::staticGet($code);
        }

        $this->elementStart('form', array('method' => 'post',
                                          'id' => 'form_register',
                                          'class' => 'form_settings',
                                          'action' => common_local_url('register')));
        $this->elementStart('fieldset');
        $this->element('legend', null, 'Account settings');
        $this->hidden('token', common_session_token());

        if ($code) {
            $this->hidden('code', $code);
        }

        $this->elementStart('ul', 'form_data');
		$this->elementStart('li');
        $this->input('fullname', _('Full name'),
                     $this->trimmed('fullname'),
                     _('Real name, please.'));
        $this->elementEnd('li');
		/*$this->elementStart('li');
        $this->input('nickname', _('Nickname'), $this->trimmed('nickname'),
                     _('1-64 lowercase letters or numbers, '.
                       'no punctuation or spaces. Required.'));
        $this->elementEnd('li');*/
        $this->elementStart('li');
        $this->password('password', _('Password'),
                        _('6 or more characters. Required.'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->password('confirm', _('Confirm'),
                        _('Same as password above. Required.'));
        $this->elementEnd('li');
        $this->elementStart('li');
        if ($invite && $invite->address_type == 'email') {
            $this->input('email', _('Email'), $invite->address,
                         _('Used only for updates, announcements, '.
                           'and password recovery'));
        } else {
            $this->input('email', _('Email'), $this->trimmed('email'),
                         _('Used only for updates, announcements, '.
                           'and password recovery'));
        }
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->input('homepage', _('Homepage'),
                     $this->trimmed('homepage'),
                     _('URL of your homepage, blog, '.
                       'or profile on another site'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->textarea('bio', _('Bio'),
                        $this->trimmed('bio'),
                        _('Describe yourself and your '.
                          'interests in 140 chars'));
        $this->elementEnd('li');
        $this->elementStart('li');
        $this->input('location', _('Location'),
                     $this->trimmed('location'),
                     _('Where you are, like "City, '.
                       'State (or Region), Country"'));
        $this->elementEnd('li');
        $this->elementStart('li', array('id' => 'settings_rememberme'));
        $this->checkbox('rememberme', _('Remember me'),
                        $this->boolean('rememberme'),
                        _('Automatically login in the future; '.
                          'not for shared computers!'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->submit('submit', _('Register'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Show some information about registering for the site
     *
     * Save the registration flag, run showPage
     *
     * @return void
     */

    function showSuccess()
    {
        $this->registered = true;
        $this->showPage();
    }

    /**
     * Show some information about registering for the site
     *
     * Gives some information and options for new registrees.
     *
     * @return void
     */

    function showSuccessContent()
    {
        $nickname = $this->arg('nickname');

        $profileurl = common_local_url('showstream',
                                       array('nickname' => $nickname));

        $this->elementStart('div', 'success');
        $instr = sprintf(_('Congratulations, %s! And welcome to %%%%site.name%%%%. '.
                           'From here, you may want to...'. "\n\n" .
                           '* Go the [public timeline](%%%%action.public%%%%) '.
                           'to see what others wrote on the site ' .
						   'and post your first message.' .  "\n" .
                           '* Update your [profile settings]'.
                           '(%%%%action.profilesettings%%%%)'.
                           ' to tell others more about you. ' .
						   ' How about a picture of yourself? Makes things much more comfortable. ' . "\n" .
						   '* Join a [group](%%%%action.group%%%%).' . "\n" .
						   '* Subscribe to an RSS feed to stay up to date. '.
                           'A feed for all messages is on the '.
                           '[public timeline](%%%%action.public%%%%). ' . "\n" .
						   'Check the site [policy](%%%%doc.policy%%%%). ' . "\n" .
                           '* Read over the [online docs](%%%%doc.help%%%%)'.
                           ' for help on the service and features you may have missed. ' . "\n\n" .
                           'Thanks for signing up and we hope '.
                           'you enjoy using this service.'),
                         $nickname, $profileurl);

        $this->raw(common_markup_to_html($instr));

        $have_email = $this->trimmed('email');
        if ($have_email) {
            $emailinstr = _('(You should receive a message by email '.
                            'momentarily, with ' .
                            'instructions on how to confirm '.
                            'your email address.)');
            $this->raw(common_markup_to_html($emailinstr));
        }
        $this->elementEnd('div');
    }

    /**
     * Show the login group nav menu
     *
     * @return void
     */

    function showLocalNav()
    {
        $nav = new LoginGroupNav($this);
        $nav->show();
    }
	
	function extraHead() {
		$this->element('script', array('type' => 'text/javascript',
                                       'src' => common_path('js/furiosica.js')),
                       ' ');
	}
}
