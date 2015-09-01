<?php


/*
Copyright (c) 2014-2015, Roman Khomasuridze, (khomasuridze@gmail.com)
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


echo "Removing fbilling tables...<br />";


$tables = array('billing_causes','billing_cdr','billing_extensions','billing_permissions','billing_permission_weights','billing_tariffs','billing_prefixes','billing_tenants','billing_trunks','billing_weights','billing_invoices');
foreach ($tables as $t) {
	$result = $db->query("DROP TABLE $t");
	if (DB::IsError($check)) {
        die_freepbx( "Can not drop tables: ".$result->getMessage()."\n");
	}
}


echo "<br/>";
echo _("Removing FBilling files...");
echo "<br/>";


shell_exec('rm -rf /var/lib/asterisk/agi-bin/fbilling-libs/');
shell_exec('rm -rf /var/lib/asterisk/agi-bin/fbilling.agi');
shell_exec('rm -rf /etc/asterisk/fbilling.conf');
shell_exec('rm -rf /var/log/asterisk/fbilling.log');
shell_exec('rm -rf /var/www/html/fbilling_data');


?>
