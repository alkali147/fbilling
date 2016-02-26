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
fbilling_admin.php - Responsible for loading fbilling administration pages
*/

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }


$cat = isset($_REQUEST['cat'])?$_REQUEST['cat']:'tenants';
$id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
$name = isset($_REQUEST['name'])?$_REQUEST['name']:'';
$credit = isset($_REQUEST['credit'])?$_REQUEST['credit']:'';
$is_active = isset($_REQUEST['is_active'])?$_REQUEST['is_active']:'all';
$form_action = $action == 'add' ? 'conf_add' : 'conf_edit';
$tenant_list = fbilling_get_list('tenants');
$weight_list = fbilling_get_list('weights');
$permission_list = fbilling_get_list('permissions');
$permission_id = isset($_REQUEST['permission_id'])?$_REQUEST['permission_id']:'all';
$trunk_list = fbilling_get_list('trunks');
$cause_list = fbilling_get_list('causes');
$tenant_id = isset($_REQUEST['tenant_id'])?$_REQUEST['tenant_id']:'all';
$weight_id = isset($_REQUEST['weight_id'])?$_REQUEST['weight_id']:array('0');
$extension = isset($_REQUEST['to'])?$_REQUEST['to']:array('0');
$balance = isset($_REQUEST['balance'])?$_REQUEST['balance']:0;
$use_limit = isset($_REQUEST['use_limit'])?$_REQUEST['use_limit']:0;
$refill = isset($_REQUEST['refill'])?$_REQUEST['refill']:0;
$refill_value = isset($_REQUEST['refill_value'])?$_REQUEST['refill_value']:0;
$prefix_id = isset($_REQUEST['prefix_id'])?$_REQUEST['prefix_id']:'';
$tariff_id = isset($_REQUEST['tariff_id'])?$_REQUEST['tariff_id']:'';
$trunk_id = isset($_REQUEST['trunk_id'])?$_REQUEST['trunk_id']:'';
$protocol = isset($_REQUEST['protocol'])?$_REQUEST['protocol']:'';
$dial = isset($_REQUEST['dial'])?$_REQUEST['dial']:'';
$add_prefix = isset($_REQUEST['add_prefix'])?$_REQUEST['add_prefix']:'';
$remove_prefix = isset($_REQUEST['remove_prefix'])?$_REQUEST['remove_prefix']:'';
$country = isset($_REQUEST['country'])?$_REQUEST['country']:'';
$prefix_description = isset($_REQUEST['prefix_description'])?$_REQUEST['prefix_description']:'';
$cost = isset($_REQUEST['cost'])?$_REQUEST['cost']:'';
$initial_cost = isset($_REQUEST['initial_cost'])?$_REQUEST['initial_cost']:'';
if (!$_REQUEST['page'] or empty($_REQUEST['page'])) {$page = '1';} else {$page = $_REQUEST['page'];}
$offset = $page == 1 ? 0 : $page * 20 - 20;
if (is_numeric($_REQUEST['prefix_is_active'])) {$prefix_is_active = $_REQUEST['prefix_is_active'];} else {$prefix_is_active = 'all';}
if (!$_REQUEST['prefix_weight_id'] or empty($_REQUEST['prefix_weight_id'])) {$prefix_weight_id = 'all';} else {$prefix_weight_id = $_REQUEST['prefix_weight_id'];}
if (!$_REQUEST['prefix'] or empty($_REQUEST['prefix'])) {$prefix = '%';} else {$prefix = $_REQUEST['prefix'];}
$active_list = array(
    array("id" => "0","name" => "No"),
    array("id" => "1","name" => "Yes"),
);
$recording_id = isset($_REQUEST['recording_id'])?$_REQUEST['recording_id']:'';

?>


<h4>FBilling Administration</h4><hr>
<table>
    <tr>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='tenants'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Tenants" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='weights'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Weights" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='permissions'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Permissions" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='prefixes'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Prefixes" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='tariffs'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Tariffs" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='trunks'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Trunks" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='recordings'>
                <input type='hidden' name='action' value='list'>
                <input type="submit" name="button" class="button" value="Manage Recordings" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
        <td>
            <form method='GET' name='fbilling_administration'>
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='extensions'>
                <input type='hidden' name='action' value='overview'>
                <input type="submit" name="button" class="button" value="Manage Extensions" tabindex=<?php echo ++$tabindex; ?>>
            </form>
        </td>
    </tr>
</table>

<?php
$allowed_categories = array("tenants","weights","permissions","prefixes","tariffs","trunks","recordings",'extensions');
if (in_array($cat, $allowed_categories)) {
    $include = "components/$cat".".php";
    include "$include";
} else {
    echo _("Something went wrong, try again...")."<br />";
}

?>