<?php
/*
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

if (!defined('LACONICA')) { exit(1); }

class SearchEngine
{
    protected $target;
    protected $table;

    function __construct($target, $table)
    {
        $this->target = $target;
        $this->table = $table;
    }

    function query($q)
    {
    }

    function limit($offset, $count, $rss = false)
    {
        return $this->target->limit($offset, $count);
    }

    function set_sort_mode($mode)
    {
        if ('chron' === $mode)
            return $this->target->orderBy('created desc');
    }
}

class SphinxSearch extends SearchEngine
{
    private $sphinx;
    private $connected;

    function __construct($target, $table)
    {
        $fp = @fsockopen(common_config('sphinx', 'server'), common_config('sphinx', 'port'));
        if (!$fp) {
            $this->connected = false;
            return;
        }
        fclose($fp);
        parent::__construct($target, $table);
        $this->sphinx = new SphinxClient;
        $this->sphinx->setServer(common_config('sphinx', 'server'), common_config('sphinx', 'port'));
        $this->connected = true;
    }

    function is_connected()
    {
        return $this->connected;
    }

    function limit($offset, $count, $rss = false)
    {
        //FIXME without LARGEST_POSSIBLE, the most recent results aren't returned
        //      this probably has a large impact on performance
        $LARGEST_POSSIBLE = 1e6; 

        if ($rss) {
            $this->sphinx->setLimits($offset, $count, $count, $LARGEST_POSSIBLE);
        }
        else {
            // return at most 50 pages of results
            $this->sphinx->setLimits($offset, $count, 50 * ($count - 1), $LARGEST_POSSIBLE);
        }

        return $this->target->limit(0, $count);
    }

    function query($q)
    {
        $result = $this->sphinx->query($q, $this->table);
        if (!isset($result['matches'])) return false;
        $id_set = join(', ', array_keys($result['matches']));
        $this->target->whereAdd("id in ($id_set)");
        return true;
     }

    function set_sort_mode($mode)
    {
        if ('chron' === $mode) {
            $this->sphinx->SetSortMode(SPH_SORT_ATTR_DESC, 'created_ts');
            return $this->target->orderBy('created desc');
        }
    }
}

class MySQLSearch extends SearchEngine
{
    function query($q)
    {
        if ('identica_people' === $this->table)
            return $this->target->whereAdd('MATCH(nickname, fullname, location, bio, homepage) ' .
                           'against (\''.addslashes($q).'\')');
        if ('identica_notices' === $this->table)
            return $this->target->whereAdd('MATCH(content) ' .
                           'against (\''.addslashes($q).'\')');
    }
}

class PGSearch extends SearchEngine
{
    function query($q)
    {
        if ('identica_people' === $this->table)
            return $this->target->whereAdd('textsearch @@ plainto_tsquery(\''.addslashes($q).'\')');
        if ('identica_notices' === $this->table)
            return $this->target->whereAdd('to_tsvector(\'english\', content) @@ plainto_tsquery(\''.addslashes($q).'\')');
    }
}

