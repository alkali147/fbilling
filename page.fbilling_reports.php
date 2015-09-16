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
fbilling_reports.php - Responsible for search and displaying call details records
*/


if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed');}

global $db;

$cat = isset($_REQUEST['cat'])?$_REQUEST['cat']:'detailed_search';
if ($_REQUEST['src'] == '' or !$_REQUEST['src']) {$src = 'all';} else {$src = $_REQUEST['src'];}
if ($_REQUEST['dst'] == '' or !$_REQUEST['dst']) {$dst = 'all';} else {$dst = $_REQUEST['dst'];}
$src_match = isset($_REQUEST['src_match'])?$_REQUEST['src_match']:'false';
$dst_match = isset($_REQUEST['dst_match'])?$_REQUEST['dst_match']:'false';
$include_unanswered_calls = isset($_REQUEST['include_unanswered_calls'])?$_REQUEST['include_unanswered_calls']:'false';
$year_start = isset($_REQUEST['year_start'])?$_REQUEST['year_start']:date('Y');
$month_start = isset($_REQUEST['month_start'])?$_REQUEST['month_start']:date('m');
$day_start = isset($_REQUEST['day_start'])?$_REQUEST['day_start']:date('d');
$hour_start = isset($_REQUEST['hour_start'])?$_REQUEST['hour_start']:'00';
$minute_start = isset($_REQUEST['minute_start'])?$_REQUEST['minute_start']:'00';
$year_end = isset($_REQUEST['year_end'])?$_REQUEST['year_end']:date('Y');
$month_end = isset($_REQUEST['month_end'])?$_REQUEST['month_end']:date('m');
$day_end = isset($_REQUEST['day_end'])?$_REQUEST['day_end']:date('d');
$hour_end = isset($_REQUEST['hour_end'])?$_REQUEST['hour_end']:'23';
$minute_end = isset($_REQUEST['minute_end'])?$_REQUEST['minute_end']:'59';
$calldate_start = $year_start."-".$month_start."-".$day_start." ".$hour_start.":".$minute_start;
$calldate_end = $year_end."-".$month_end."-".$day_end." ".$hour_end.":".$minute_end;
if (!$_REQUEST['page'] or empty($_REQUEST['page'])) {$page = '1';} else {$page = $_REQUEST['page'];}
$cause_id = isset($_REQUEST['cause_id'])?$_REQUEST['cause_id']:'all';
$tenant_id = isset($_REQUEST['tenant_id'])?$_REQUEST['tenant_id']:'all';
$weight_id = isset($_REQUEST['weight_id'])?$_REQUEST['weight_id']:'all';
$cause_list = fbilling_get_list('causes');
$tenant_list = fbilling_get_list('tenants');
$weight_list = fbilling_get_list('weights');
$offset = $page == 1 ? 0 : $page * 20 - 20;
?>

<h3>Fbilling Reports</h3><hr>
<table>
    <tr>
        <td>
            <form method="GET" name="detailed_search">
                <input type="hidden" name="display" value="fbilling_reports">
                <input type="hidden" name="action" value="search">
                <input type="hidden" name="cat" value="detailed_search">
                <input type="submit" tabindex="<?php echo ++$tabindex;?>" name="report_type_label" value="Detailed Search" />
            </form>
        </td>
        <td>
            <form method="GET" name="detailed_search">
                <input type="hidden" name="display" value="fbilling_reports">
                <input type="hidden" name="action" value="search">
                <input type="hidden" name="cat" value="reports_by_tenant">
                <input type="submit" tabindex="<?php echo ++$tabindex;?>" name="report_type_label" value="Reports By Tenant" />
            </form>
        </td>
        <td>
            <form method="GET" name="detailed_search">
                <input type="hidden" name="display" value="fbilling_reports">
                <input type="hidden" name="action" value="search">
                <input type="hidden" name="cat" value="generate_invoice">
                <input type="submit" tabindex="<?php echo ++$tabindex;?>" name="report_type_label" value="Generate Invoices" />
            </form>
        </td>
    </tr>
</table>

<?php
// start detailed search
if ($cat == 'detailed_search') {
?>
<h5><?php echo _("Search Call Detail Records"); ?></h5>
<form method='GET'>
<table class='fbilling'>
    <th><?php echo _("Filter"); ?></th>
    <th><?php echo _("Value"); ?></th>
    <th><?php echo _("Action"); ?></th>
    <tr>
        <td><?php echo _("Source"); ?></td>
        <td>
            <input type'text' name='src' tabindex="<?php echo ++$tabindex;?>" value=<?php echo $_REQUEST['src']; ?> >
            <input type="checkbox" name="src_match" tabindex="<?php echo ++$tabindex;?>" value="true" <?php if ($src_match == 'true') {echo 'checked';} ?> ><?php echo _("Exact"); ?><br>
        </td>
        <td></td>
    </tr>
    <tr>
        <td><?php echo _("Destination"); ?></td>
        <td>
            <input type'text' name='dst' tabindex="<?php echo ++$tabindex;?>" value=<?php echo $_REQUEST['dst']; ?> >
            <input type="checkbox" name="dst_match" tabindex="<?php echo ++$tabindex;?>" value="true" <?php if ($dst_match == 'true') {echo 'checked';} ?> ><?php echo _("Exact"); ?><br>
        </td>
        <td></td>
    </tr>
        <?php fbilling_add_datepicker(); ?>
    <tr>
        <td><?php echo _("Hangup Cause"); ?></td>
        <td>
            <select name='cause_id' tabindex="<?php echo ++$tabindex;?>">
                <option value='all'>All</option>
                <?php
                    foreach ($cause_list as $cause) {
                        if ($cause_id == $cause['id']) {
                            echo "<option selected value=$cause[id]>$cause[name]</option>";
                        } else {
                            echo "<option value=$cause[id]>$cause[name]</option>";
                        }
                    }
                ?>
            </select>
        </td>
        <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" name='export' value='Export'></td>
    </tr>
    <tr>
        <td><?php echo _("Tenant"); ?></td>
        <td>
            <select name='tenant_id' tabindex="<?php echo ++$tabindex;?>">
                <option value='all'>All</option>
                <?php
                    foreach ($tenant_list as $tenant) {
                        if ($tenant_id == $tenant['id']) {
                            echo "<option selected value=$tenant[id]>$tenant[name]</option>";
                        } else {
                            echo "<option value=$tenant[id]>$tenant[name]</option>";
                        }
                    }
                ?>
            </select>
        </td>
        <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" value='Search'></td>
    </tr>
    <tr>
        <td><?php echo _("Destination Weight"); ?></td>
        <td>
            <select name='weight_id' tabindex="<?php echo ++$tabindex;?>">
                <option value='all'>All</option>
                <?php
                    foreach ($weight_list as $weight) {
                        if ($weight_id == $weight['id']) {
                            echo "<option selected value=$weight[id]>$weight[name]</option>";
                        } else {
                            echo "<option value=$weight[id]>$weight[name]</option>";
                        }
                    }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <input type='hidden' name='display' value='fbilling_reports'>
        <input type='hidden' name='cat' value='detailed_search'>
        <input type='hidden' name='action' value='search'>
    </tr>
</table>
</form>


<?php
// generate sql ready string base on src, src_match, dst and dst_match
if ($src == 'all') {
    $src_match_sql = "LIKE '%'";
} else {
    if ($src_match == 'true' and $src != 'all') {
        $src_match_sql = "= '$src'";
    }
    if ($src_match == 'false' and $src != 'all') {
        $src_match_sql = "LIKE '%".$src."%'";
    }
}
if ($dst == 'all') {
    $dst_match_sql = "LIKE '%'";
} else {
    if ($dst_match == 'true' and $dst != 'all') {
        $dst_match_sql = "= '$dst'";
    }
    if ($dst_match == 'false' and $dst != 'all') {
        $dst_match_sql = "LIKE '%".$dst."%'";
    }
}
if ($cause_id == 'all') {$cause_id_sql = "LIKE '%'";} else {$cause_id_sql = "= $cause_id";}
if ($tenant_id == 'all') {$tenant_id_sql = "LIKE '%'";} else {$tenant_id_sql = "= $tenant_id";}
if ($weight_id == 'all') {$weight_id_sql = "LIKE '%'";} else {$weight_id_sql = "= $weight_id";}
// used for all queries
$sql_where = "billing_cdr.src $src_match_sql AND billing_cdr.dst $dst_match_sql AND billing_cdr.calldate > '$calldate_start' AND billing_cdr.calldate < '$calldate_end' AND billing_cdr.cause_id $cause_id_sql AND billing_cdr.tenant_id $tenant_id_sql AND billing_cdr.weight_id $weight_id_sql";
$sql_body_summary = "SELECT COUNT(*) AS number_of_calls, SUM(billsec) AS total_duration, AVG(billsec) AS average_duration, SUM(total_cost) AS total_cost, AVG(total_cost) AS average_cost FROM billing_cdr,billing_tenants,billing_causes WHERE billing_cdr.tenant_id = billing_tenants.id AND billing_cdr.cause_id = billing_causes.id AND $sql_where";
$sql_body_main = "SELECT billing_cdr.src,billing_extensions.alias,billing_cdr.dst,billing_cdr.calldate,billing_cdr.billsec,billing_cdr.tariff_cost,billing_cdr.total_cost,billing_cdr.cause_id,billing_tenants.name AS tenant,billing_causes.name AS cause FROM billing_cdr,billing_tenants,billing_causes,billing_extensions WHERE billing_cdr.tenant_id = billing_tenants.id AND billing_cdr.cause_id = billing_causes.id AND billing_cdr.src = billing_extensions.sip_num AND $sql_where";
$display_summary = sql($sql_body_summary, 'getRow', DB_FETCHMODE_ASSOC);
$number_of_pages = ceil( $display_summary['number_of_calls'] / 20);
// we need to generate csv file just before offset kicks in
if ($_REQUEST['export'] == 'Export') { // if user hit export button generate csv file
    $csv_file_url = fbilling_get_csv_file($cat,$sql_body_main);
}
$sql_body_main .= " ORDER BY calldate DESC LIMIT 20 OFFSET $offset";
$search_results = sql($sql_body_main,'getAll',DB_FETCHMODE_ASSOC);
// if export button was hit show download link
?>
<h5><?php echo _("Search Results"); echo "&nbsp"; if ($_REQUEST['export'] == 'Export') {echo "<a href=/fbilling_data/$csv_file_url>Download CSV file</a>";} ?></h5>
<hr>
<table class='fbilling'>
    <th><?php echo _("Source"); ?></th>
    <th><?php echo _("Name"); ?></th>
    <th><?php echo _("Destination"); ?></th>
    <th><?php echo _("Call Date"); ?></th>
    <th><?php echo _("Duration"); ?></th>
    <th><?php echo _("Cost"); ?></th>
    <th><?php echo _("Total Cost"); ?></th>
    <th><?php echo _("Tenant"); ?></th>
    <th><?php echo _("Cause"); ?></th>
    <th colspan=2><?php echo _("Action"); ?></th>
    <?php
        foreach ($search_results as $cdr) {
            $dst_url = fbilling_build_url("test","dst=$cdr[dst]&dst_match=true");
            $src_url = fbilling_build_url("test","src=$cdr[src]&src_match=true");
            echo "<tr>";
            echo "<td><a href=/admin/config.php?display=extensions&extdisplay=$cdr[src]>$cdr[src]<a/></td>";
            echo "<td>$cdr[alias]</td>";
            echo "<td>$cdr[dst]</td>";
            echo "<td>$cdr[calldate]</td>";
            echo "<td>$cdr[billsec]</td>";
            echo "<td>$cdr[tariff_cost]</td>";
            echo "<td>$cdr[total_cost]</td>";
            echo "<td>$cdr[tenant]</td>";
            echo "<td>$cdr[cause]</td>";
            echo "<td><a href=$dst_url>Calls to this number</a></td>";
            echo "<td><a href=$src_url>Calls by this number</a></td>";
            echo "<tr>";
        }
    ?>
</table>

<?php
    page_cdr($number_of_pages,$page,$cat);
    echo "<hr>";
?>
<table class='fbilling'>
    <tr>
        <td><?php echo _("Total number of calls"); ?></td>
        <td><?php echo $display_summary['number_of_calls']; ?></td>
    </tr>
    <tr>
        <td><?php echo _("Total duation of calls (min)"); ?></td>
        <td><?php echo ceil($display_summary['total_duration'] / 60); ?></td>
    </tr>
    <tr>
        <td><?php echo _("Average duration of calls (sec)"); ?></td>
        <td><?php echo ceil($display_summary['average_duration']); ?></td>
    </tr>
    <tr>
        <td><?php echo _("Total cost of calls"); ?></td>
        <td><?php echo round($display_summary['total_cost'],2); ?></td>
    </tr>
    <tr>
        <td><?php echo _("Average duration of calls"); ?></td>
        <td><?php echo round($display_summary['average_cost'],2); ?></td>
    </tr>
</table>
<?php
// end detailed search
}


// start reports by tenant
if ($cat == 'reports_by_tenant') {
?>
<h5><?php echo _("Display Reports by Tenant"); ?></h5>
<form method='GET'>
<table class='fbilling'>
    <th><?php echo _("Filter"); ?></th>
    <th><?php echo _("Value"); ?></th>
    <th><?php echo _("Action"); ?></th>
        <?php fbilling_add_datepicker(); ?>
    <tr>
        <td><?php echo _("Tenant"); ?></td>
        <td>
            <select name='tenant_id' tabindex="<?php echo ++$tabindex;?>">
                <option value='all'>All</option>
                <?php
                    foreach ($tenant_list as $tenant) {
                        if ($tenant_id == $tenant['id']) {
                            echo "<option selected value=$tenant[id]>$tenant[name]</option>";
                        } else {
                            echo "<option value=$tenant[id]>$tenant[name]</option>";
                        }
                    }
                ?>
            </select>
        </td>
        <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" name='export' value='Export'></td>
    </tr>
    <tr>
        <td><?php echo _("Weight"); ?></td>
        <td>
            <select name='weight_id' tabindex="<?php echo ++$tabindex;?>">
                <option value='all'>All</option>
                <?php
                    foreach ($weight_list as $weight) {
                        if ($weight_id == $weight['id']) {
                            echo "<option selected value=$weight[id]>$weight[name]</option>";
                        } else {
                            echo "<option value=$weight[id]>$weight[name]</option>";
                        }
                    }
                ?>
            </select>
        </td>
        <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" value='Display'></td>
    </tr>
    <tr>
        <input type='hidden' name='display' value='fbilling_reports'>
        <input type='hidden' name='cat' value='reports_by_tenant'>
        <input type='hidden' name='action' value='search'>
    </tr>
    
</table>
</form>
<?php
if ($tenant_id != 'all') {  // we do not display anything if no tenant is selected
if ($weight_id == 'all') {
    $weight_id_sql = " AND billing_cdr.weight_id LIKE '%'";
} else {
    $weight_id_sql = " AND billing_cdr.weight_id = '$weight_id'";
}
$sql = "SELECT billing_cdr.src AS src, billing_extensions.alias AS alias, COUNT(*) AS number_of_calls, SUM(total_cost) AS total_cost, ";
$sql .= "SUM(billing_cdr.billsec) AS sum_billsec ";
$sql .= "FROM billing_extensions,billing_cdr WHERE ";
$sql .= "billing_cdr.calldate > '$calldate_start' AND billing_cdr.calldate < '$calldate_end' AND ";
$sql .= "billing_extensions.sip_num = billing_cdr.src AND ";
$sql .= "billing_cdr.tenant_id = '$tenant_id'";
$sql .= $weight_id_sql;
$display_summary = sql($sql,'getRow',DB_FETCHMODE_ASSOC);
$sql .= " GROUP BY billing_cdr.src";
$search_results = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
if ($_REQUEST['export'] == 'Export') { // if user hit export button generate csv file
    $csv_file_url = fbilling_get_csv_file($cat,$sql);
}
#echo $weight_id_sql;
?>
<h5><?php echo _("Search Results"); echo "&nbsp"; if ($_REQUEST['export'] == 'Export') {echo "<a href=/fbilling_data/$csv_file_url>Download CSV file</a>";} ?></h5>
<hr>
<table class='fbilling'>
    <th><?php echo _("Extension"); ?></th>
    <th><?php echo _("Name"); ?></th>
    <th><?php echo _("Number of Calls"); ?></th>
    <th><?php echo _("Total Duration"); ?></th>
    <th><?php echo _("Total Cost"); ?></th>
    <?php
        foreach ($search_results as $cdr) {
            echo "<tr>";
                echo "<td><a href=/admin/config.php?display=extensions&extdisplay=$cdr[src]>$cdr[src]<a/></td>";
                echo "<td>$cdr[alias]</td>";
                echo "<td>$cdr[number_of_calls]</td>";
                echo "<td>".$cdr[sum_billsec]."</td>";
                echo "<td>".round($cdr[total_cost],2)."</td>";
            echo "<tr>";
        }
    ?>
</table>
<hr>
<table class='fbilling'>
    <tr>
        <td><?php echo _("Total number of calls"); ?></td>
        <td><?php echo $display_summary['number_of_calls']; ?></td>
    </tr>
    <tr>
        <td><?php echo _("Total duation of calls (min)"); ?></td>
        <td><?php echo ceil($display_summary['sum_billsec'] / 60); ?></td>
    </tr>
    <tr>
        <td><?php echo _("Total cost of calls"); ?></td>
        <td><?php echo round($display_summary['total_cost'],2); ?></td>
    </tr>
</table>
<?php
}
// end reports by tenant
}


// start invoice
if ($cat == "generate_invoice") {
?>
    <h5><?php echo _("Generate Invoice"); ?></h5>
    <form method='GET'>
    <table class='fbilling'>
    <th><?php echo _("Filter"); ?></th>
    <th><?php echo _("Value"); ?></th>
    <th><?php echo _("Action"); ?></th>
    <tr>
        <td><?php echo _("Extension"); ?></td>
        <td>
            <input type'text' name='src' tabindex="<?php echo ++$tabindex;?>" value=<?php echo $_REQUEST['src']; ?> >
            <input type="checkbox" name="include_unanswered_calls" tabindex="<?php echo ++$tabindex;?>" value="true" <?php if ($include_unanswered_calls == 'true') {echo 'checked';} ?> ><?php echo _("Include unanswered calls"); ?><br>
        </td>
        <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" value='Generate'></td>
    </tr>
        <?php fbilling_add_datepicker(); ?>
    <tr>
        <input type='hidden' name='display' value='fbilling_reports'>
        <input type='hidden' name='cat' value='generate_invoice'>
        <input type='hidden' name='action' value='gen'>
    </tr>
</table>
</form>
<?php
    if ($action == 'gen') {
        // get data for invoice
        $sql_body = "SELECT dst,calldate,billsec,total_cost FROM billing_cdr WHERE ";
        $sql_body_summary = "SELECT COUNT(*) AS number_of_calls, SUM(billsec) AS total_duration, SUM(total_cost) AS total_cost from billing_cdr WHERE ";
        $sql_where = "calldate > '$calldate_start' AND calldate < '$calldate_end' AND src = $src ";
        if ($include_unanswered_calls == 'false') {
            $sql_where .= "and billsec > 0 ";
        }
        $sql_where .= "ORDER BY calldate ASC";
        $sql_search = $sql_body.$sql_where;
        $sql_summary = $sql_body_summary.$sql_where;
        if ($src == 'all') {
            echo _("Please specify extension you wish to generate invoice for...")."<br />";
        } else {
            $search_results = sql($sql_search, 'getAll', DB_FETCHMODE_ASSOC);
            $search_summary = sql($sql_summary, 'getRow', DB_FETCHMODE_ASSOC);
            if (count($search_results) == 0) { // no need to generate empty invoice...
                echo _("Extension had no calls for selected period of time, nothing to generate...");
            } else {
                // generate pdf
                $invoice_file = fbilling_generate_invoice($src,$search_results,$search_summary);
                echo "<a href=/fbilling_data/invoices/$invoice_file>Download Invoice file</a>";
            }
        }
    }
}
// end gemerate invoice
?>