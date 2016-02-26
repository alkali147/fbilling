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

This file is part of FBilling.
*/


if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }


global $db;

## since provided gui_link opens in current window/tab we have to do this so some of our links will be open in new tab
class gui_link_new_tab extends guitext {
    function gui_link_new_tab($elemname, $text, $url, $userlang = true) {
        $parent_class = get_parent_class($this);
        // line below seems to cause trouble in freepbx version 13 and above
        // commenting this out solves the issue and does no harm for versions 12 and below
        // this should fix issue #29
        //parent::$parent_class($elemname, $text);
        $this->html_text = "<a href=\"$url\" target=\"_blank\" id =\"$this->elemname\">$text</a>";
    }
}

#############################################


function fbilling_configpageinit($pagename) {
    global $currentcomponent;

    $action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
    $extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
    $extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
    $tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;

    if ($pagename != 'users' && $pagename != 'extensions')  {
        return true; 
    }

    if ($pagename != 'users' && $pagename != 'extensions')  {
        return true; 
    }

    if ($tech_hardware != null || $extdisplay != '' || $pagename == 'users') {
        $msgSelectTenant = _("Please select tenant for this extension");
        $msgSelectPermission = _("Please select permission for this extension");
        // since current selectbox does not offer js validation, we are putting permission and tenant validation in credit input
        $js = '
        if (document.getElementById("fbilling_tenant").value == "") {
            document.getElementById("fbilling_tenant").focus();
            return true;
        }
        if (document.getElementById("fbilling_permission").value == "") {
            document.getElementById("fbilling_permission").focus();
            return true;
        }
        ';
        $currentcomponent->addjsfunc('validateTenantPermission()', $js);
        $js = 'document.getElementById("fbilling_alias").innerHTML = document.getElementById("name").value; return true';
        $currentcomponent->addjsfunc('addFbillingAlias()', $js);
    }
    if ($tech_hardware != null ) { 
        fbilling_applyhooks(); 
    } elseif ($action=="add") {
        if ($_REQUEST['display'] == 'users') {
            $usage_arr = framework_check_extension_usage($_REQUEST['extension']);
            if (empty($usage_arr)) {
                $currentcomponent->addprocessfunc('fbilling_configprocess', 1);
            } else {
                fbilling_applyhooks(); 
            }
        } else {
            $currentcomponent->addprocessfunc('fbilling_configprocess', 1);
        }
    } elseif ($extdisplay != '' || $pagename == 'users') { 
        fbilling_applyhooks(); 
        $currentcomponent->addprocessfunc('fbilling_configprocess', 1);
    } 
}


function fbilling_applyhooks() {
    // get needed data prior to loading page
    global $currentcomponent;
    $tenant_list = fbilling_get_list('tenants');
    $permission_list = fbilling_get_list('permissions');
    // select refill
    $currentcomponent->addoptlistitem('fbilling_refill', '1', _('Yes'));
    $currentcomponent->addoptlistitem('fbilling_refill', '0', _('No'));
    $currentcomponent->setoptlistopts('fbilling_refill', 'sort', false);
    // select extension.use_limit
    $currentcomponent->addoptlistitem('fbilling_limit', '1', _('Yes'));
    $currentcomponent->addoptlistitem('fbilling_limit', '0', _('No'));
    $currentcomponent->setoptlistopts('fbilling_limit', 'sort', false);
    // select extension.personal_credit
    $currentcomponent->addoptlistitem('fbilling_extension_use_personal_credit', '1', _('Yes'));
    $currentcomponent->addoptlistitem('fbilling_extension_use_personal_credit', '0', _('No'));
    $currentcomponent->setoptlistopts('fbilling_extension_use_personal_credit', 'sort', false);
    // select extension.is_Active
    $currentcomponent->addoptlistitem('fbilling_extension_is_active', '1', _('Yes'));
    $currentcomponent->addoptlistitem('fbilling_extension_is_active', '0', _('No'));
    $currentcomponent->setoptlistopts('fbilling_extension_is_active', 'sort', false);
    // select tenant
    $currentcomponent->addoptlistitem('fbilling_tenant', '', _('Select'));
    foreach ($tenant_list as $tenant) {
        $currentcomponent->addoptlistitem('fbilling_tenant', $tenant['id'], $tenant['name']);
    }
    $currentcomponent->setoptlistopts('fbilling_tenant', 'sort', false);
    // select permission
    $currentcomponent->addoptlistitem('fbilling_permission', '', _('Select'));
    foreach ($permission_list as $permission) {
        $currentcomponent->addoptlistitem('fbilling_permission', $permission['id'], $permission['name']);
    }
    $currentcomponent->setoptlistopts('fbilling_permission', 'sort', false);
    $currentcomponent->addguifunc('fbilling_configpageload');
}


function fbilling_configpageload() {
    global $currentcomponent;
    global $astman;

    $action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
    $extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
    $extension = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
    $tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;
    $fbilling_alias = $astman->database_get("AMPUSER",$extdisplay."/cidname");
    $sql = "SELECT billing_extensions.credit AS extension_credit,billing_extensions.refill,";
    $sql .= "billing_extensions.refill_value,billing_extensions.use_limit,";
    $sql .= "billing_extensions.tenant_id,billing_extensions.permission_id,";
    $sql .= "billing_extensions.is_active,billing_extensions.personal_credit,";
    $sql .= "billing_tenants.credit AS tenant_credit ";
    $sql .= "FROM billing_extensions,billing_tenants WHERE  ";
    $sql .= "billing_extensions.sip_num = '$extdisplay' AND billing_extensions.tenant_id = billing_tenants.id";
    $extension_data = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    $extension_is_active = !$extension_data['is_active'] ? '0' : $extension_data['is_active'];
    $extension_credit = !$extension_data['extension_credit'] ? '0' : $extension_data['extension_credit'];
    $extension_tenant_credit = !$extension_data['tenant_credit'] ? '0' : $extension_data['tenant_credit'];
    $extension_personal_credit = !$extension_data['personal_credit'] ? '0' : $extension_data['personal_credit'];
    $extension_tenant = !$extension_data['tenant_id'] ? '0' : $extension_data['tenant_id'];
    $extension_permission = !$extension_data['permission_id'] ? '0' : $extension_data['permission_id'];
    $extension_use_limit = !$extension_data['use_limit'] ? '0' : $extension_data['use_limit'];
    $extension_refill = !$extension_data['refill'] ? '0' : $extension_data['refill'];
    $extension_refill_value = !$extension_data['refill_value'] ? '0' : $extension_data['refill_value'];
    $extension_address = $astman->database_get("SIP","Registry"."/$extdisplay");
    $extension_activity_url = $dst_url = fbilling_build_url("display=fbilling_reports&cat=detailed_search&action=search&day_start=01&","src=$extdisplay&src_match=true");
    $extension_gen_invoice_url = fbilling_build_url("display=fbilling_reports&cat=generate_invoice&","src=$extdisplay");
    if ($ext==='') {
        $extdisplay = $extn;
    } else {
        $extdisplay = $ext;
    }
    $extension_address = explode(":",$extension_address);
    //echo $extension_address['0'];
    if ($action != 'del') {
        $section = _("FBilling Settings");
        //echo $extension_data['personal_credit'];
        $currentcomponent->addguielem($section, new gui_textbox('fbilling_credit', $extension_credit, _('Credit'), _("Current credit for this extension, to increase or decrease credit, just change the value here."), "frm_extensions_validateTenantPermission()", _("Please make sure you selected permission and tenant for this extension"), false,0,''));
        $currentcomponent->addguielem($section, new gui_textbox('fbilling_tenant_credit', $extension_tenant_credit, _('Tenant Credit'), _("Current credit for tenant this extension belongs to<br/>Tenant credit can be changed from Tenants section in FBilling Administration"), "", "", false,0,true));
        $currentcomponent->addguielem($section, new gui_selectbox('fbilling_extension_use_personal_credit', $currentcomponent->getoptlist('fbilling_extension_use_personal_credit'), $extension_personal_credit, _('Use Personal Credit'), _("Whether or not extension should use personal credit<br/>If set to yes, calls will be charged against credit specified above<br/>If set to no, calls will be charged against tenant credit"),false,""));
        $currentcomponent->addguielem($section, new gui_selectbox('fbilling_extension_is_active', $currentcomponent->getoptlist('fbilling_extension_is_active'), $extension_is_active, _('Active'), _("Whether or not extension is active.<br />If set to No, calls made by this extension will not go through irregardless of permission, tenants, credit..."),false,""));
        $currentcomponent->addguielem($section, new gui_selectbox('fbilling_tenant', $currentcomponent->getoptlist('fbilling_tenant'), $extension_tenant, _('Tenant'), _("Tenant to which this extension will belong"), false,""));
        $currentcomponent->addguielem($section, new gui_selectbox('fbilling_permission', $currentcomponent->getoptlist('fbilling_permission'), $extension_permission, _('Permission'), _("Calling permissions this extension"), false,""));
        $currentcomponent->addguielem($section, new gui_selectbox('fbilling_limit', $currentcomponent->getoptlist('fbilling_limit'), $extension_use_limit, _('Use Limit'), _("If set to No, this extension will be able to make unlimited number of calls, and credit will not be changed"),false,""));
        $currentcomponent->addguielem($section, new gui_selectbox('fbilling_refill', $currentcomponent->getoptlist('fbilling_refill'), $extension_refill, _('Refill'), _("If set to Yes, every time refill script is executed, extensions credit will be topped up to value set in Refill Value field"),false,""));
        $currentcomponent->addguielem($section, new gui_textbox('fbilling_refill_value', $extension_refill_value, _('Refill Value'), _("If Refill set to Yes, every time refill script is executed, extensions credit will be set to this value"), "", "", false,0,''));
        $currentcomponent->addguielem($section, new gui_textbox('fbilling_alias', "$fbilling_alias", _('Alias'), _("Name shown in FBilling Reports"), "", "", false,0,''));
        $currentcomponent->addguielem($section, new gui_link_new_tab('fbilling_account_phone', 'Go to phone web inetrface', "http://$extension_address[0]"));
        $currentcomponent->addguielem($section, new gui_link_new_tab('fbilling_account_activity', 'Extension activity', $extension_activity_url));
        $currentcomponent->addguielem($section, new gui_link_new_tab('fbilling_gen_invoice', 'Generate invoice', $extension_gen_invoice_url));
    }
}


function fbilling_configprocess() {
    global $db;
    global $astman;
    $action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
    $ext = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
    $extn = isset($_REQUEST['extension'])?$_REQUEST['extension']:null;
    $display = isset($_REQUEST['display'])?$_REQUEST['display']:null;

    if ($action == null) {
        return true;
    }
    if ($ext==='') {
        $extdisplay = $extn;
    } else {
        $extdisplay = $ext;
    }
    $fbilling_alias = $astman->database_get("AMPUSER",$extdisplay."/cidname");
    if ($action == 'add' or $action == 'edit') {
        $sql = "INSERT INTO billing_extensions ";
        $sql .= "(alias,sip_num,credit,refill,refill_value,use_limit,permission_id,tenant_id,is_active,personal_credit) ";
        $sql .= "VALUES ('$_REQUEST[fbilling_alias]','$extdisplay','$_REQUEST[fbilling_credit]','$_REQUEST[fbilling_refill]', ";
        $sql .= "'$_REQUEST[fbilling_refill_value]','$_REQUEST[fbilling_limit]','$_REQUEST[fbilling_permission]', ";
        $sql .= "'$_REQUEST[fbilling_tenant]','$_REQUEST[fbilling_extension_is_active]','$_REQUEST[fbilling_extension_use_personal_credit]') ";
        $sql .= "ON DUPLICATE KEY UPDATE ";
        $sql .= "alias = '$_REQUEST[fbilling_alias]', sip_num = '$extdisplay', ";
        $sql .= "credit = '$_REQUEST[fbilling_credit]', refill = '$_REQUEST[fbilling_refill]', ";
        $sql .= "refill_value = '$_REQUEST[fbilling_refill_value]', use_limit = '$_REQUEST[fbilling_limit]', ";
        $sql .= "permission_id = '$_REQUEST[fbilling_permission]',tenant_id = '$_REQUEST[fbilling_tenant]',";
        $sql .= "is_active = '$_REQUEST[fbilling_extension_is_active]',personal_credit = '$_REQUEST[fbilling_extension_use_personal_credit]';";
    } elseif ($action == 'del') {
        $sql = "DELETE FROM billing_extensions WHERE sip_num = '$extdisplay';";
    } 
    sql($sql);
}

##########################


# get list of requested components
# requires component (category) name
# returns associative array containing id and name of components
function fbilling_get_list($cat) {
    $sql = "SELECT id,name FROM billing_$cat WHERE 1";
    $list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
    return $list;
}


# get data of component filtered by id
# requires components name and id
# return associative array
function fbilling_get_data_by_id($cat,$id) {
    $sql = "SELECT * FROM billing_$cat WHERE id  = $id;";
    $data = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    return $data;
}

# get all or requested fields and all rows from tables
# requires (category_name)
# returns associative array containing the requested fields
function fbilling_get_all($cat,$fields) {
    $sql = "SELECT $fields FROM billing_$cat WHERE 1";
    $data = sql($sql, 'getAll');
    return $data;
}

# same as fbilling_get_data_by_field
# requires component name, fields to select, field to filter by and value
# returns associative array
function fbilling_get_data_by_field($cat,$what,$field,$value) {
    $sql = "SELECT $what FROM billing_$cat WHERE $field  = '$value';";
    $data = sql($sql, 'getAll');
    return $data;
}


# insert specified data into database
# requires component name, array of fields to insert and array of values to insert
# returns nothing
function fbilling_add($cat,$fields,$values) {
    $sql = "INSERT INTO billing_$cat (";
    $sql .= implode(",", $fields).")  VALUES ";
    if ($cat == 'permission_weights') {
        foreach ($values as $value) {
            $sql .= "((SELECT id FROM billing_permissions WHERE name = '$value[0]'),$value[1]),";
        }
        $sql = chop($sql,",");
    } else {
        $sql .= " ('".implode("','", $values)."')";
    }
    sql($sql);
}


# edit existing database entry
# requires component name, fields and values to update and id
# returns nothing
function fbilling_edit($cat,$fields,$id) {
    $sql = "UPDATE billing_$cat SET $fields WHERE id = $id;";
    sql($sql);
}


# delete existing database entry
# requires component name, field and value by which entry will be filtered
# return nothing
function fbilling_del($cat,$field,$value) {
    $sql = "DELETE FROM billing_$cat WHERE $field = '$value';";
    sql($sql);
}


# check if component entry with specified values exist in database
# requires component name, field and value by which entry will be filtered
# returns number of entries found
function fbilling_check_if_exists($cat,$field,$value) {
    $sql = "SELECT COUNT(*) AS count FROM billing_$cat WHERE $field = '$value';";
    $result = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    $exists = $result['count'];
    return $exists;
}


#############################################3


// generate pagination buttons
// ah, right now we have two pagination function, we need to merge this two.
function page_cdr($number_of_pages,$page) {
    foreach ($_REQUEST as $key => $value) {
        $request_inputs .= "<input type='hidden' name='$key' value='$value'>";
    }
    echo "
    <table>
        <tr>
            <td>
                <form method='GET' name='first_page'>";
                    echo $request_inputs;
                    echo "<input type='hidden' name='page' value='1'/>
                    <input type='submit' value='First Page' ++$tabindex />
                </form>
            </td>
            <td>
                <form action='' method='GET' name='prev_page'>";
                    echo $request_inputs;
                    echo "<input type='hidden' name='page' value='"; if ($page > 1) {echo $page - 1;} else {echo '1';} echo "'/>
                    <input type='submit' ++$tabindex value='Previous Page' />
                </form>
            </td>
            <td>
                <form action='' method='GET' name='enter_page'>";
                    echo $request_inputs;
                    $page = chop($page);
                    echo "<input type='text' ++$tabindex name='page' size='4' value='".$page."'>
                </form>
            </td>
            <td>
                <form action='' method='GET' name='next_page'>";
                    echo $request_inputs;
                    $page = chop($page);
                    echo "<input type='hidden' name='page' value='"; if ($number_of_pages < 1) {echo 1;} else {if ($page < $number_of_pages) {echo $page +1;} else {echo $number_of_pages;}} echo "'/>
                    <input type='submit' ++$tabindex value='Next Page' />
                </form>
            </td>
            <td>
                <form action='' method='GET' name='last_page'>";
                    echo $request_inputs.
                    "<input type=\"hidden\" name=\"page\" value='"; if ($number_of_pages < 1) {echo 1;} else {echo $number_of_pages;} echo "'/>
                    <input type='submit' ++$tabindex value='Last Page' />
                </form>
            </td>
        </tr>
    </table>
    ";
}


// generate csv file with requested data and return file url
// requires component name and sql query
// returns filename
function fbilling_get_csv_file($cat,$sql) {
    $stamp = time();
    $basedir = "/var/www/html/fbilling_data/";
    $filename = "fbiling_".$cat."_".$stamp.".csv";
    $file_location .= $basedir;
    $file_location .= $filename;
    $csv_file = fopen($file_location,"a") or die("Unable to open file!");
    $csv_data = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
    foreach ($csv_data as $k => $v) {
        foreach ($v as $d) {
            fwrite($csv_file, $d.",");
        }
        fwrite($csv_file, "\n");
    }
    fwrite($csv_file, $data);
    return $filename;
}

# TODO actually process array 
// generates url according to requested parameteres
// requires array of GET parameters and baseurl
// returns url
function fbilling_build_url($baseurl,$parameters) {
    $url = "/admin/config.php?";
    $url .= $baseurl;
    $url .= $parameters;
    return $url;
}

// generate date pickers
// requires nothing
// returns html containing datepickers
function fbilling_add_datepicker() {
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
    $month_list = array(
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December'
    );
    ?>
    <tr>
        <td><?php echo _("Start Date"); ?></td>
        <td> 
            <select name="year_start" id="year_start">
                <?php
                    for ($y_start = 2007; $y_start <= date('Y'); $y_start++) {
                        if ($year_start == $y_start) {
                            echo "<option value='$y_start' selected='selected'>$y_start</option>";
                        } else {
                            echo "<option value='$y_start'>$y_start</option>";
                        }
                    }
                ?>
            </select>
            <select name="month_start" id="month_start">
                <?php
                    foreach ($month_list as $i => $month) {
                        if ($month_start == $i) {
                            echo "<option value='$i' selected='selected'>$month</option>";
                        } else {
                            echo "<option value='$i'>$month</option>";
                        }
                    }
                ?>
            </select>
            <select name="day_start" id="day_start">
                <?php
                    for ($d_start = 01; $d_start <= 31; $d_start++) {
                        if ($day_start == $d_start) {
                            if ($d_start < 10) {$d_start = '0'.$d_start;}
                            echo "<option value=\"$d_start\" selected=\"selected\">$d_start</option>\n";
                        } else {
                            if ($d_start < 10) {$d_start = '0'.$d_start;}
                            echo "<option value=\"$d_start\">$d_start</option>\n";
                        }
                    }
                ?>
            </select>
            <select name="hour_start" id="hour_start">
                <?php
                    for ($h_start = 00; $h_start <= 23; $h_start++) {
                        if ($hour_start == $h_start) {
                            if ($h_start < 10) {$h_start = "0".$h_start;}
                            echo "<option value=\"$h_start\" selected=\"selected\">$h_start</option>\n";
                        } else {
                            if ($h_start < 10) {$h_start = "0".$h_start;}
                            echo "<option value=\"$h_start\">$h_start</option>\n";
                        }
                    }
                ?>
            </select>
            <select name="minute_start" id="minute_start">
                <?php
                    for ($m_start = 00; $m_start <= 59; $m_start++) {
                        if ($minute_start == $m_start) {
                            if ($m_start < 10) {$m_start = "0".$m_start;}
                            echo "<option value=\"$m_start\" selected=\"selected\">$m_start</option>\n";
                        } else {
                            if ($m_start < 10) {$m_start = "0".$m_start;}
                            echo "<option value=\"$m_start\">$m_start</option>\n";
                        }
                    }
                ?>
            </select>
        </td>
        <td></td>
    </tr>
    <tr>
        <td><?php echo _("End Date"); ?></td>
        <td> 
            <select name="year_end" id="year_end">
                <?php
                    for ($y_end = 2007; $y_end <= date('Y'); $y_end++) {
                        if ($year_end == $y_end) {
                            echo "<option value='$y_end' selected='selected'>$y_end</option>";
                        } else {
                            echo "<option value='$y_end'>$y_end</option>";
                        }
                    }
                ?>
            </select>
            <select name="month_end" id="month_end">
                <?php
                    foreach ($month_list as $i => $month) {
                        if ($month_end == $i) {
                            echo "<option value='$i' selected='selected'>$month</option>";
                        } else {
                            echo "<option value='$i'>$month</option>";
                        }
                    }
                ?>
            </select>
            <select name="day_end" id="day_end">
                <?php
                    for ($d_end = 01; $d_end <= 31; $d_end++) {
                        if ($day_end == $d_end) {
                            if ($d_end < 10) {$d_end = '0'.$d_end;}
                            echo "<option value=\"$d_end\" selected=\"selected\">$d_end</option>\n";
                        } else {
                            if ($d_end < 10) {$d_end = '0'.$d_end;}
                            echo "<option value=\"$d_end\">$d_end</option>\n";
                        }
                    }
                ?>
            </select>
            <select name="hour_end" id="hour_end">
                <?php
                    for ($h_end = 00; $h_end <= 23; $h_end++) {
                        if ($hour_end == $h_end) {
                            if ($h_end < 10) {$h_end = "0".$h_end;}
                            echo "<option value=\"$h_end\" selected=\"selected\">$h_end</option>\n";
                        } else {
                            if ($h_end < 10) {$h_end = "0".$h_end;}
                            echo "<option value='$h_end'>$h_end</option>";
                        }
                    }
                ?>
            </select>
            <select name="minute_end" id="minute_end">
                <?php
                    for ($m_end = 00; $m_end <= 59; $m_end++) {
                        if ($minute_end == $m_end) {
                            if ($m_end < 10) {$m_end = "0".$m_end;}
                            echo "<option value=\"$m_end\" selected=\"selected\">$m_end</option>\n";
                        } else {
                            if ($m_end < 10) {$m_end = "0".$m_end;}
                            echo "<option value=\"$m_end\">$m_end</option>\n";
                        }
                    }
                ?>
            </select>
        </td>
        <td></td>
    </tr>
    <?php
}

// pdf related
require_once('libs/fpdf/fpdf.php');
class PDF extends FPDF {
    function generate_table($search_results,$headers) {
        $header_widths = array(40,60,25,25);
        $headers = array("Called Number","Call Date","Duration","Total Cost");
        // create headers
        for ($i=0; $i < sizeof($headers); $i++) {
            $this->Cell($header_widths[$i],7,$headers[$i],1,0,'C');
        }
        $this->Ln();
        foreach ($search_results as $cdr) {
            $this->Cell($header_widths[0],6,$cdr[dst],'LR');
            $this->Cell($header_widths[1],6,$cdr[calldate],'LR');
            $this->Cell($header_widths[2],6,$cdr[billsec],'LR');
            $this->Cell($header_widths[3],6,round($cdr[total_cost],3),'LR');
            $this->Ln();
        }
        $this->Cell(array_sum($header_widths),0,'','T');
    }
}

function fbilling_generate_invoice ($src,$search_results,$search_summary) {
    # filename related
    $extension_id = fbilling_get_data_by_field("extensions","id","sip_num",$src);
    $invoice_dir = "/var/www/html/fbilling_data/invoices/";
    $stamp = time();
    $creation_date = date("Y-m-d H:i:s");
    $invoice_file = "inv_".$src."_".$stamp.".pdf";
    $filename = $invoice_dir.$invoice_file;
    # pagind related
    $rows_per_page = 40;
    $rows = sizeof($search_results);
    $number_of_pages = ceil($rows / 40);
    # generate pdf
    $pdf = new PDF();
    $pdf->SetFont('Arial','',12);
    # WARNING, ugly things coming through!
    # for PDF pagination, we loop through number of pages we have
    for ($page=0; $page < $number_of_pages; $page++) { 
        $pdf->AddPage();    # createnew page
        if ($page == 0) {   # we need PDF header on first page
            $pdf->Cell(150,10,"Invoice for extension $src",0,1,'C');
        } else {
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(150,10,"Continued from page $page",0,1,'C');
        }
        $pdf->Ln();
        # since we need pagination, we'd rather slice array then run several queries...
        $pdf->SetFont('Arial','',12);
        $page_rows = array_chunk($search_results, 38);
        $pdf->generate_table($page_rows[$page]);
        $pdf->SetFont('Arial','B',8);
        $pdf->Ln();
        $pdf->Cell(150,10,$page + 1,0,1,'C');
    }
    # after pagination is done, create one last page with summary
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(150,10,"Summary for this invoice",0,1,'C');
    # create summary table
    $pdf->Cell('90',0,'','T');
    $pdf->Ln();
    #$pdf->Cell('90',0,'','T');
    $pdf->Cell('60',6,"Number of Calls",'LR');
    $pdf->Cell('30',6,$search_summary['number_of_calls'],'LR');
    $pdf->Ln();
    #$pdf->Cell('90',0,'','T');
    $pdf->Cell('60',6,"Total Duration of Calls",'LR');
    $pdf->Cell('30',6,$search_summary['total_duration'],'LR');
    $pdf->Ln();
    #$pdf->Cell('90',0,'','T');
    $pdf->Cell('60',6,"Total Cost",'LR');
    $pdf->Cell('30',6,round($search_summary['total_cost'],3),'LR');
    $pdf->Ln();
    $pdf->Cell('90',0,'','T');
    $pdf->Output($filename,'F');
    // insert into invoice table
    $fields = array("extension_id","creation_date","filename");
    $values = array($extension_id[0][0],$creation_date,$invoice_file);
    fbilling_add("invoices",$fields,$values);
    return $invoice_file;
}
?>
