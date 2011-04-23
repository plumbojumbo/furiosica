<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Form for posting a direct message
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
 * @category  Form
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@controlyourself.ca>
 * @copyright 2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/form.php';

/**
 * Form for posting a direct message
 *
 * @category Form
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Sarven Capadisli <csarven@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 *
 * @see      HTMLOutputter
 */

class MessageForm extends Form
{
    /**
     * User to send a direct message to
     */

    var $to = null;

    /**
     * Pre-filled content of the form
     */

    var $content = null;

    /**
     * Constructor
     *
     * @param HTMLOutputter $out     output channel
     * @param User          $to      user to send a message to
     * @param string        $content content to pre-fill
     */

    function __construct($out=null, $to=null, $content=null)
    {
        parent::__construct($out);

        $this->to      = $to;
        $this->content = $content;
    }

    /**
     * ID of the form
     *
     * @return int ID of the form
     */

    function id()
    {
        return 'form_notice';
    }

    /**
     * Action of the form
     *
     * @return string URL of the action
     */

    function action()
    {
        return common_local_url('newmessage');
    }

    /**
     * Legend of the Form
     *
     * @return void
     */
    function formLegend()
    {
        $this->out->element('legend', null, _('Send a direct notice'));
    }

    /**
     * Data elements
     *
     * @return void
     */

    function formData()
    {
		/*
        $user = common_current_user();

        $mutual_users = $user->mutuallySubscribedUsers();

        $mutual = array();

        while ($mutual_users->fetch()) {
            if ($mutual_users->id != $user->id) {
                $mutual[$mutual_users->id] = $mutual_users->nickname;
            }
        }

        $mutual_users->free();
        unset($mutual_users);
		*/
		
		/* modified: allow messages to all users */
		
		require_once INSTALLDIR.'/classes/Profile.php';
		
		$profiles = Profile::getProfiles();
		$recipients = array();
		while ($profiles->fetch()) {
            $recipients[$profiles->id] = $profiles->nickname;
        }
		$profiles->free();
        unset($profiles);
		
        $this->out->elementStart('ul', 'form_data');
        $this->out->elementStart('li', array('id' => 'notice_to'));
        $this->out->dropdown('to', _('To'), $recipients, null, false,
                             ($this->to) ? $this->to->id : null);
        $this->out->elementEnd('li');

        $this->out->elementStart('li', array('id' => 'notice_text'));
        $this->out->element('textarea', array('id' => 'notice_data-text',
                                              'cols' => 35,
                                              'rows' => 4,
                                              'name' => 'content'),
                            ($this->content) ? $this->content : '');
        $this->out->elementEnd('li');
        $this->out->elementEnd('ul');
    }

    /**
     * Action elements
     *
     * @return void
     */

    function formActions()
    {
        $this->out->elementStart('ul', 'form_actions');
        $this->out->elementStart('li', array('id' => 'notice_submit'));
        $this->out->element('input', array('id' => 'notice_action-submit',
                                           'class' => 'submit',
                                           'name' => 'message_send',
                                           'type' => 'submit',
                                           'value' => _('Send')));
        $this->out->elementEnd('li');
        $this->out->elementEnd('ul');
    }
}
