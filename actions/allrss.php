<?php

/**
 * RSS feed for user and friends timeline action class.
 *
 * PHP version 5
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 *
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
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
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/rssaction.php';

/**
 * RSS feed for user and friends timeline.
 *
 * Formatting of RSS handled by Rss10Action
 *
 * @category Action
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl.html AGPLv3
 * @link     http://laconi.ca/
 */
class AllrssAction extends Rss10Action
{
    var $user = null;

    /**
     * Initialization.
     * 
     * @return boolean false if user doesn't exist
     */
    function prepare($args)
    {
        parent::prepare($args);
        $nickname   = $this->trimmed('nickname');
        $this->user = User::staticGet('nickname', $nickname);

        if (!$this->user) {
            $this->clientError(_('No such user.'));
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get notices
     *
     * @param integer $limit max number of notices to return
     *
     * @return array notices
     */
    function getNotices($limit=0)
    {
        $user   = $this->user;
        $notice = $user->noticesWithFriends(0, $limit);
                                            
        while ($notice->fetch()) {
            $notices[] = clone($notice);
        }

        return $notices;
    }

     /**
     * Get channel.
     *
     * @return array associative array on channel information
     */
    function getChannel()
    {
        $user = $this->user;
        $c    = array('url' => common_local_url('allrss',
                                             common_feed_params(array('nickname' =>
                                                   $user->nickname))),
                   'title' => sprintf(_('%s and friends'), $user->nickname),
                   'link' => common_local_url('all',
                                             array('nickname' =>
                                                   $user->nickname)),
                   'description' => sprintf(_('Feed for friends of %s'), $user->nickname));
        return $c;
    }

    /**
     * Get image.
     *
     * @return string user avatar URL or null
    */
    function getImage()
    {
        $user    = $this->user;
        $profile = $user->getProfile();
        if (!$profile) {
            return null;
        }
        $avatar = $profile->getAvatar(AVATAR_PROFILE_SIZE);
        return $avatar ? $avatar->url : null;
    }

    function isReadOnly()
    {
        return true;
    }
}

