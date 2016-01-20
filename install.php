<?php


/*
Copyright (c) 2014-2016, Roman Khomasuridze, (khomasuridze@gmail.com)
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer. 
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

This file is part of FBilling
*/


if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }


global $db;
global $ext;


echo _("installing fbilling tables...");


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_causes` (
    `id` int(11) NOT NULL,
    `name` varchar(45) DEFAULT NULL,
    `recording_id` int(11),
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
";


$queries[] = "INSERT INTO `billing_causes` (`id`, `name`, `recording_id`) VALUES
    (33, 'Ongoing', 0),
    (1, 'Account does not exist', 0),
    (2, 'Account has no credit', 0),
    (3, 'Prefix does not exist', 0),
    (4, 'Tariff does not exist', 0),
    (5, 'Account has no permission to call', 0),
    (6, 'Minimum duration of call is to low', 0),
    (7, 'Trunk not found for destination', 0),
    (8, 'Initial cost is more than account credit', 0),
    (9, 'Tenant is not active', 0),
    (10, 'Prefix is not active', 0),
    (11, 'Permission does not exists', 0),
    (12, 'Permission is not active', 0),
    (13, 'Extension is not active', 0),
    (14, 'Tenant has no credit', 0),
    (15, 'Account is not assigned to any tenant',0)
    (90, 'Call finished - ANSWER', 0),
    (91, 'Call finished - BUSY', 0),
    (92, 'Call finished - NO ANSWER', 0),
    (93, 'Call finished - CANCEL', 0),
    (94, 'Call finished - CONGESTION', 0),
    (95, 'Call finished - CHANUNAVAIL', 0),
    (96, 'Call finished - DONTCALL', 0),
    (97, 'Call finished - TORTURE', 0),
    (98, 'Call finished - INVALIDARGS', 0),
    (99, 'Call finished - UNKNOWN', 0);
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_cdr` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `src` varchar(80) NOT NULL DEFAULT '0',
    `dst` varchar(80) NOT NULL DEFAULT '0',
    `calldate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `billsec` int(11) NOT NULL DEFAULT '0',
    `prefix_id` int(11) NOT NULL DEFAULT '0',
    `weight_id` int(11) NOT NULL DEFAULT '0',
    `country` varchar(16) DEFAULT NULL,
    `tariff_id` int(11) NOT NULL DEFAULT '0',
    `tariff_cost` float NOT NULL DEFAULT '0',
    `tariff_initial_cost` float DEFAULT NULL,
    `total_cost` float NOT NULL DEFAULT '0',
    `tenant_id` int(11) NOT NULL,
    `uniqueid` varchar(32) NOT NULL DEFAULT '0',
    `cause_id` int(11) NOT NULL DEFAULT '0',
    `server_id` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_extensions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name_utf` varchar(128) NOT NULL,
    `alias` varchar(32) NOT NULL,
    `sip_num` int(11) NOT NULL,
    `outbound_num` varchar(32) NOT NULL,
    `permission_id` int(11) NOT NULL,
    `credit` float NOT NULL,
    `refill` int(11) NOT NULL DEFAULT '0',
    `refill_value` float NOT NULL,
    `use_limit` int(11) DEFAULT '1',
    `server_id` int(11) NOT NULL,
    `tenant_id` int(11) NOT NULL,
    `is_active` int(11) NOT NULL DEFAULT '1',
    `personal_credit` int(1) DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `sip_num` (`sip_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_permissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(32) NOT NULL,
    `is_active` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_permission_weights` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `permission_id` int(11) NOT NULL,
    `weight_id` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_prefixes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `pref` varchar(45) NOT NULL,
    `country` varchar(45) NOT NULL,
    `description` varchar(32) NOT NULL,
    `weight_id` int(11) NOT NULL,
    `is_active` tinyint(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `pref` (`pref`),
    UNIQUE KEY `one_prefix` (`pref`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_tariffs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `prefix_id` int(11) NOT NULL,
    `cost` float NOT NULL,
    `tenant_id` int(11) NOT NULL,
    `trunk_id` int(11) DEFAULT NULL,
    `initial_cost` float DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `one_tariff_per_one_tennant` (`tenant_id`,`prefix_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_tenants` (
    `id` tinyint(4) NOT NULL AUTO_INCREMENT,
    `range_start` int(8) NOT NULL,
    `range_end` int(8) NOT NULL,
    `name` varchar(32) NOT NULL,
    `weight_id` tinyint(4) NOT NULL,
    `is_active` tinyint(1) NOT NULL,
    `credit` float NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_trunks` (
    `id` int(10) NOT NULL AUTO_INCREMENT,
    `name` varchar(32) NOT NULL,
    `proto` varchar(32) DEFAULT NULL,
    `dial` varchar(80) DEFAULT NULL,
    `tenant_id` int(11) DEFAULT NULL,
    `add_prefix` varchar(32) DEFAULT NULL,
    `remove_prefix` varchar(32) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_weights` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(45) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_invoices` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `extension_id` int(11) NOT NULL,
    `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `filename` varchar(256) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";


$queries[] = "CREATE TABLE IF NOT EXISTS `billing_payments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `extension_id` int(11) NOT NULL,
    `tenant_id` int(11) NOT NULL,
    `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `amount` varchar(8) NOT NULL DEFAULT 0,
    `comment` varchar(256) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";


foreach ($queries as $sql) {
    $resuult = $db->query($sql);
    if (DB::IsError($check)) {
        die_freepbx( "Can not create tables: ".$result->getMessage()."\n");
    }
}


echo "<br/>";
echo _("Copying FBilling files...");
echo "<br/>";


shell_exec('cp -r /var/www/html/admin/modules/fbilling/src/fbilling-libs /var/lib/asterisk/agi-bin/');
shell_exec('cp -r /var/www/html/admin/modules/fbilling/src/fbilling.agi /var/lib/asterisk/agi-bin/');
shell_exec('cp -r /var/www/html/admin/modules/fbilling/src/fbilling.conf /etc/asterisk/');
shell_exec('touch /var/log/asterisk/fbilling.log');
shell_exec('mkdir /var/www/html/fbilling_data');
shell_exec('mkdir /var/www/html/fbilling_data/invoices');
shell_exec('cp /var/www/html/admin/modules/fbilling/src/fbilling_prefixes_TEMPLATE.csv /var/www/html/fbilling_data');
shell_exec('cp /var/www/html/admin/modules/fbilling/src/fbilling_tariffs_TEMPLATE.csv /var/www/html/fbilling_data');

echo "<br/>";
echo _("Setting permissions...");
echo "<br/>";


shell_exec('chown -R asterisk.asterisk /var/lib/asterisk/agi-bin/fbilling-libs');
shell_exec('chown asterisk.asterisk /var/lib/asterisk/agi-bin/fbilling.agi');
shell_exec('chmod ug+x asterisk.asterisk /var/lib/asterisk/agi-bin/fbilling.agi');
shell_exec('chown asterisk.asterisk /etc/asterisk/fbilling.conf');
shell_exec('chown asterisk.asterisk /etc/asterisk/fbilling.conf');
shell_exec('chown -R asterisk.asterisk /var/www/html/fbilling_data');


?>
