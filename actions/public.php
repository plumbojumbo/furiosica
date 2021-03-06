<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Action for displaying the public stream
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
 * @category  Public
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2008-2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/publicgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

/**
 * Action for displaying the public stream
 *
 * @category Public
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 *
 * @see      PublicrssAction
 * @see      PublicxrdsAction
 */

class PublicAction extends Action
{
    /**
     * page of the stream we're on; default = 1
     */

    var $page = null;

    function isReadOnly()
    {
        return true;
    }

    /**
     * Read and validate arguments
     *
     * @param array $args URL parameters
     *
     * @return boolean success value
     */

    function prepare($args)
    {
        parent::prepare($args);
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;
        
        common_set_returnto($this->selfUrl());
        
        return true;
    }

    /**
     * handle request
     *
     * Show the public stream, using recipe method showPage()
     *
     * @param array $args arguments, mostly unused
     *
     * @return void
     */

    function handle($args)
    {
        parent::handle($args);

        header('X-XRDS-Location: '. common_local_url('publicxrds'));

        $this->showPage();
    }

    /**
     * Title of the page
     *
     * @return page title, including page number if over 1
     */

    function title()
    {
        if ($this->page > 1) {
            return sprintf(_('Public timeline, page %d'), $this->page);
        } else {
            return _('Public timeline');
        }
    }

    /**
     * Output <head> elements for RSS and Atom feeds
     *
     * @return void
     */

    function showFeeds()
    {
        $this->element('link', array('rel' => 'alternate',
		// note: we do not add auth keys here because in case of private site Notice::publicStream will only show force_public notices
                                     'href' => common_local_url('publicrss', common_feed_params()),
                                     'type' => 'application/rss+xml',
                                     'title' => _('Public Stream Feed')));
    }

    /**
     * Extra head elements
     *
     * We include a <meta> element linking to the publicxrds page, for OpenID
     * client-side authentication.
     *
     * @return void
     */

    function extraHead()
    {
        // for client side of OpenID authentication
        $this->element('meta', array('http-equiv' => 'X-XRDS-Location',
                                     'content' => common_local_url('publicxrds')));
    }

    /**
     * Show tabset for this page
     *
     * Uses the PublicGroupNav widget
     *
     * @return void
     * @see PublicGroupNav
     */

    function showLocalNav()
    {
        $nav = new PublicGroupNav($this);
        $nav->show();
    }

    /**
     * Fill the content area
     *
     * Shows a list of the notices in the public stream, with some pagination
     * controls.
     *
     * @return void
     */

    function showContent()
    {
        $notice = Notice::publicStream(($this->page-1)*NOTICES_PER_PAGE,
                                       NOTICES_PER_PAGE + 1);

        if (!$notice) {
            $this->serverError(_('Could not retrieve public stream.'));
            return;
        }

        $nl = new NoticeList($notice, $this);

        $cnt = $nl->show();

        $this->pagination($this->page > 1, $cnt > NOTICES_PER_PAGE,
                          $this->page, 'public');
    }

    /**
     * Makes a list of exported feeds for this page
     *
     * @return void
     *
     * @todo I18N
     */

    function showExportData()
    {
        $fl = new FeedList($this);
        $fl->show(array(0 => array('href' => common_local_url('publicrss', common_feed_params()),
                                   'type' => 'rss',
                                   'version' => 'RSS 1.0',
                                   'item' => 'publicrss'),
                        1 => array('href' => common_local_url('publicatom', common_feed_params()),
                                   'type' => 'atom',
                                   'version' => 'Atom 1.0',
                                   'item' => 'publicatom')));
    }

    function showSections()
    {
        // $top = new TopPostersSection($this);
        // $top->show();
        $pop = new PopularNoticeSection($this);
        $pop->show();
        $gbp = new GroupsByPostsSection($this);
        $gbp->show();
        /*$feat = new FeaturedUsersSection($this);
        $feat->show();*/
    }

    function showAnonymousMessage()
    {
		/*$m = _('This is %%site.name%%, a [micro-blogging](http://en.wikipedia.org/wiki/Micro-blogging) service ' .
               'based on the Free Software [Laconica](http://laconi.ca/) tool. ' .
               '[Join now](%%action.register%%) to share notices about yourself with friends, family, and colleagues! ([Read more](%%doc.help%%))');*/
		$m = _('Hi, das hier ist die Redaktionspinnwand von [FURIOS](http://www.furios-campus.de/), dem ' . 
			   'studentischen Campusmagazin an der FU Berlin. Die Pinnwand ist ein interner Dienst der ' .
			   'Redaktion, wir freuen uns aber auf deine Nachrichten bei [Twitter](http://twitter.com/fucampus)!');
        $this->elementStart('div', array('id' => 'anon_notice'));
        $this->raw(common_markup_to_html($m));
        $this->elementEnd('div');
    }
}
