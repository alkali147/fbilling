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

This file is part of FBilling.
*/


if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }


global $db;

## since provided gui_link opens in current window/tab we have to do this so some of our links will be open in new tab
class gui_link_new_tab extends guitext {
    function gui_link_new_tab($elemname, $text, $url, $userlang = true) {
        $parent_class = get_parent_class($this);
        parent::$parent_class($elemname, $text);
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
    // select permission
    $currentcomponent->addoptlistitem('fbilling_limit', '1', _('Yes'));
    $currentcomponent->addoptlistitem('fbilling_limit', '0', _('No'));
    $currentcomponent->setoptlistopts('fbilling_limit', 'sort', false);
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
    $sql = "SELECT credit,refill,refill_value,use_limit,tenant_id,permission_id FROM billing_extensions WHERE sip_num = '$extdisplay';";
    $extension_data = sql($sql, 'getRow', DB_FETCHMODE_ASSOC);
    $extension_credit = !$extension_data['credit'] ? '0' : $extension_data['credit'];
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
        $currentcomponent->addguielem($section, new gui_textbox('fbilling_credit', $extension_credit, _('Credit'), _("Current credit for this extension, to increase or decrease credit, just change the value here."), "frm_extensions_validateTenantPermission()", _("Please make sure you selected permission and tenant for this extension"), false,0,''));
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
        $sql = "INSERT INTO billing_extensions (alias,sip_num,credit,refill,refill_value,use_limit,permission_id,tenant_id) VALUES ('$_REQUEST[fbilling_alias]','$extdisplay','$_REQUEST[fbilling_credit]','$_REQUEST[fbilling_refill]','$_REQUEST[fbilling_refill_value]','$_REQUEST[fbilling_limit]','$_REQUEST[fbilling_permission]','$_REQUEST[fbilling_tenant]') ON DUPLICATE KEY UPDATE alias = '$_REQUEST[fbilling_alias]', sip_num = '$extdisplay', credit = '$_REQUEST[fbilling_credit]', refill = '$_REQUEST[fbilling_refill]', refill_value = '$_REQUEST[fbilling_refill_value]', use_limit = '$_REQUEST[fbilling_limit]', permission_id = '$_REQUEST[fbilling_permission]',tenant_id = '$_REQUEST[fbilling_tenant]';";
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

// pdf related
require_once('libs/fpdf/fpdf.php');
class PDF extends FPDF {
    function generate_table($search_results) {
        $header_widths = array(40,60,25,25);
        $headers = array("Called Number","Call Date","Duration","Total Cost");
        // create headers
        for ($i=0; $i < count($headers); $i++) {
            $this->Cell($header_widths[$i],7,$headers[$i],1,0,'C');
        }
        $this->Ln();
        foreach ($search_results as $cdr) {
            $this->Cell($header_widths[0],6,$cdr[dst],'LR');
            $this->Cell($header_widths[1],6,$cdr[calldate],'LR');
            $this->Cell($header_widths[2],6,$cdr[billsec],'LR');
            $this->Cell($header_widths[3],6,$cdr[total_cost],'LR');
            $this->Ln();
        }
        $this->Cell(array_sum($header_widths),0,'','T');
    }
}

function fbilling_generate_invoice ($src,$search_results) {
    $invoice_dir = "/var/www/html/fbilling_data/invoices/";
    $stamp = time();
    $invoice_file = "inv_".$src."_".$stamp.".pdf";
    $filename = $invoice_dir.$invoice_file;
    $pdf = new PDF();
    $pdf->SetFont('Arial','',12);
    $pdf->AddPage();
    $pdf->Cell(40,10,"Invoice for extension $src");
    $pdf->Ln();
    $pdf->generate_table($search_results);
    $pdf->Output($filename,'F');
    return $invoice_file;
}
?>
