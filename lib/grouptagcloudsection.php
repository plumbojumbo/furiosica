<?php
/**
 * Laconica, the distributed open-source microblogging tool
 *
 * Personal tag cloud section
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
 * @category  Widget
 * @package   Laconica
 * @author    Evan Prodromou <evan@controlyourself.ca>
 * @copyright 2009 Control Yourself, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://laconi.ca/
 */

if (!defined('LACONICA')) {
    exit(1);
}

/**
 * Personal tag cloud section
 *
 * @category Widget
 * @package  Laconica
 * @author   Evan Prodromou <evan@controlyourself.ca>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://laconi.ca/
 */

class GroupTagCloudSection extends TagCloudSection
{
    var $group = null;

    function __construct($out=null, $group=null)
    {
        parent::__construct($out);
        $this->group = $group;
    }

    function title()
    {
        return sprintf(_('Tags in %s group\'s notices'), $this->group->nickname);
    }

    function getTags()
    {
        $qry = 'SELECT notice_tag.tag, '.
          'sum(exp(-(now() - notice_tag.created)/%s)) as weight ' .
          'FROM notice_tag JOIN notice ' .
          'ON notice_tag.notice_id = notice.id ' .
          'JOIN group_inbox on group_inbox.notice_id = notice.id ' .
          'WHERE group_inbox.group_id = %d ' .
          'GROUP BY notice_tag.tag ' .
          'ORDER BY weight DESC ';

        $limit = TAGS_PER_SECTION;
        $offset = 0;

        if (common_config('db','type') == 'pgsql') {
            $qry .= ' LIMIT ' . $limit . ' OFFSET ' . $offset;
        } else {
            $qry .= ' LIMIT ' . $offset . ', ' . $limit;
        }

        $tag = Memcached_DataObject::cachedQuery('Notice_tag',
                                                 sprintf($qry,
                                                         common_config('tag', 'dropoff'),
                                                         $this->group->id),
                                                 3600);
        return $tag;
    }

}
