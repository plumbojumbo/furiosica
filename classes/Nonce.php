<?php
/**
 * Table Definition for nonce
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Nonce extends Memcached_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'nonce';                           // table name
    public $consumer_key;                    // varchar(255)  primary_key not_null
    public $tok;                             // char(32)  primary_key not_null
    public $nonce;                           // char(32)  primary_key not_null
    public $ts;                              // datetime()   not_null
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=null)
    { return Memcached_DataObject::staticGet('Nonce',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
