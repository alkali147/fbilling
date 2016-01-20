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
tariffs.php - Responsible for tariff management
*/


include "shared.php";


if ($action == 'list' or !$action) { // startif (action list)
?>
    <form method='GET' name='search_tariffs'>
        <table class='fbilling'>
            <th width='10%'>Filter</th>
            <th width='20%'>Value</th>
            <th width='10%'>Action</th>
            <tr>
                <td><?php echo _("Prefix"); ?></td>
                <td><input type'text' name='prefix' tabindex="<?php echo ++$tabindex;?>" value=<?php echo $_REQUEST['prefix']; ?>></td>
            </tr>
            <tr>
                <td><?php echo _("Weight"); ?></td>
                <td>
                    <select name='prefix_weight_id' tabindex="<?php echo ++$tabindex;?>">
                        <option value='all'><?php echo _("All"); ?></option>
                        <?php
                            foreach ($weight_list as $weight) {
                                if ($_REQUEST['prefix_weight_id'] == $weight['id']) {
                                    echo "<option selected value=$weight[id]>$weight[name]</option>";
                                } else {
                                    echo "<option value=$weight[id]>$weight[name]</option>";
                                }
                            }
                        ?>
                    </selct>
                </td>
                    <input type='hidden' name='display' value='fbilling_admin'>
                    <input type='hidden' name='cat' value='tariffs'>
                    <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" name='export' value='Export'></td>
            </tr>
            <tr>
                <td><?php echo _("Tenant"); ?></td>
                <td>
                    <select name='tenant_id' tabindex="<?php echo ++$tabindex;?>" >
                        <option value='all'><?php echo _("All"); ?></option>
                        <?php
                            foreach ($tenant_list as $tenant) {
                                if ($_REQUEST['tenant_id'] == $tenant['id']) {
                                    echo "<option selected value=$tenant[id]>$tenant[name]</option>";
                                } else {
                                    echo "<option value=$tenant[id]>$tenant[name]</option>";
                                }
                            }
                        ?>
                    </selct>
                </td> 
                    <input type='hidden' name='display' value='fbilling_admin'>
                    <input type='hidden' name='cat' value='tariffs'>
                    <input type='hidden' name='action' value='list'>
                    <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" value='Search'></td>
            </tr>
        </table>
    </form>
    <h5><?php echo _("Search Results"); ?></h5><hr>


<?php
    if (!$prefix) {$prefix_sql = "billing_prefixes.pref LIKE '%'";} else {$prefix_sql = "billing_prefixes.pref LIKE '$prefix%'";}
    if ($prefix_weight_id == 'all' or $prefix_weight_id == '') {$prefix_weight_id_sql = "billing_prefixes.weight_id LIKE '%'";} else {$prefix_weight_id_sql = "billing_prefixes.weight_id = '$prefix_weight_id'";}
    if ($tenant_id == 'all' or $tenant_id == '') {$tenant_id_sql = "billing_tariffs.tenant_id LIKE '%'";} else {$tenant_id_sql = "billing_tariffs.tenant_id = '$tenant_id'";}
    $sql = "SELECT billing_tariffs.id as id, billing_prefixes.id AS prefix_id, billing_tenants.name AS tenant_name, billing_prefixes.pref, billing_tariffs.id AS tariff_id, billing_tariffs.cost, billing_tariffs.initial_cost, billing_prefixes.description, billing_prefixes.country FROM billing_tariffs, billing_prefixes,billing_tenants WHERE";
    $sql .= " $prefix_sql AND ";
    $sql .= " $tenant_id_sql AND ";
    $sql .= " $prefix_weight_id_sql AND ";
    $sql .= " billing_tariffs.prefix_id = billing_prefixes.id AND billing_tariffs.tenant_id = billing_tenants.id";
    $sql_summary = $sql;
    $search_summary = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
    if ($_REQUEST['export'] == 'Export') { // if use hit export button, fetch csv file
        $csv_file_url = fbilling_get_csv_file($cat,$sql);
    }
    $number_of_pages = ceil(sizeof($search_summary) / 20);
    $sql .= " LIMIT 20 OFFSET $offset";
    $search_results = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
?>

    <table class='fbilling'>
        <th><?php echo _("Prefix"); ?></th>
        <th><?php echo _("Cost"); ?></th>
        <th><?php echo _("Initial Cost"); ?></th>
        <th><?php echo _("Description"); ?></th>
        <th><?php echo _("Tenant"); ?></th>
        <th><?php echo _("Action"); ?></th>
        <?php
            foreach ($search_results as $tariff) {
                echo "<tr class=\"record\">
                        <td>".$tariff['pref']."</td>
                        <td>".$tariff['cost']."</td>
                        <td>".$tariff['initial_cost']."</td>
                        <td>".$tariff['description']."</td>
                        <td>".$tariff['tenant_name']."</td>
                        <td width='20%'><a href=/admin/config.php?display=fbilling_admin&cat=tariffs&action=edit&id=$tariff[id]>Edit Tariff</a>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<a href=/admin/config.php?display=fbilling_admin&cat=tariffs&action=delete&tariff_id=$tariff[id]>Delete Tarif</a></td>
                    </tr>
                ";
            }
        ?>
    </table>
    <hr>


<?php
    page($number_of_pages,$page,$cat);
?>


<a href=/admin/config.php?display=fbilling_admin&cat=tariffs&action=import><?php echo _("Import Tariffs"); ?></a>


<?php
    if ($_REQUEST['export'] == 'Export') { // use hit export button show csv download link
        echo "<a href=/fbilling_data/$csv_file_url>Download CSV file</a>";
    }
} // endif (action list)


elseif ($action == 'add' or $action == 'edit') { // startif (action add/edit)
    if ($action == 'edit') {
        $tariff_data = fbilling_get_data_by_id($cat,$id);
    }
?>
    <form name='tariff_form' method='GET' onsubmit='return check_tariff_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Cost"); ?><span><?php echo _("Per minute cost for this prefix"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='cost' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$tariff_data[cost]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Initial Cost"); ?><span><?php echo _("One time cost for this prefix"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='initial_cost' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$tariff_data[initial_cost]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Trunk"); ?><span><?php echo _("Destination trunk for this tariff"); ?></span></a></td>
                </td>
                <td>
                    <select name='trunk_id' tabindex="<?php echo ++$tabindex;?>" >
                        <option selected value='none'><?php echo _("Select"); ?></option>"
                    <?php
                        foreach ($trunk_list as $trunk) {
                            if ($tariff_data['trunk_id'] == $trunk['id']) {
                                echo "<option selected value=$trunk[id]>$trunk[name]</option>";
                            } else {
                                echo "<option value=$trunk[id]>$trunk[name]</option>";
                            }
                        }
                    ?>
                </selct>
                </td>
            </tr>
        <?php if ($action == 'add') { // startif (action add)
        ?>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Tenant"); ?><span><?php echo _("Tenant to which this tariff will be associated"); ?></span></a></td>
                </td>
                <td>
                    <select name='tenant_id' tabindex="<?php echo ++$tabindex;?>" >
                        <option selected value='none'><?php echo _("Select"); ?></option>"
                    <?php
                        foreach ($tenant_list as $tenant) {
                            if ($tariff_data['tenant_id'] == $tenant['id']) {
                                echo "<option selected value=$tenant[id]>$tenant[name]</option>";
                            } else {
                                echo "<option value=$tenant[id]>$tenant[name]</option>";
                            }
                        }
                    ?>
                </selct>
                </td>
            </tr>
            <?php
            } // endif (action add)
            ?>
        </table>
        <input type='hidden' name='display' value=<?php echo $display; ?>>
        <input type='hidden' name='action' value=<?php echo $form_action; ?>>
        <input type='hidden' name='prefix_id' value=<?php echo $prefix_id; ?>>
        <?php if ($action == 'edit') {echo "<input type='hidden' name='id' value=$id>";} ?>
        <input type='hidden' name='cat' value=<?php echo $cat; ?> >
        <td colspan="2"><br><h6><input name="submit" type="submit" value="<?php echo _("Submit")?>" tabindex="<?php echo ++$tabindex;?>"></h6></td>
    </form>


<?php
} // endif (action add/edit)


elseif ($action == 'conf_add') {
    // check if tariff for specified prefix exists
    $insert_ok = fbilling_check_if_exists($cat,'prefix_id',$prefix_id);
    if ($insert_ok > 0) { // if tariff for this prefix exists, check if it belongs to requested tenant
        $insert_ok = fbilling_check_if_exists($cat,'tenant_id',$tenant_id);
    }
    if ($insert_ok > 0) { // if tariff for this tenant exists, exit
        echo _("Tariff for requested prefix and tenant exists.");
        echo "<br /><a href='javascript:history.go(-1)''>Go Back</a>";
        // TODO add edit link for existing tariff
        return true;
    }
    $fields = array('prefix_id','cost','tenant_id','trunk_id','initial_cost');
    $values = array($prefix_id,$cost,$tenant_id,$trunk_id,$initial_cost);
    fbilling_add($cat,$fields,$values);
    redirect_standard('cat');
}


elseif ($action == 'conf_edit') {
    // we can change only trunk and costs when editting tariffs, so we will have no duplicates, insert_ok not needed here
    $fields = "cost = '$cost', initial_cost = $initial_cost, trunk_id = '$trunk_id'";
    fbilling_edit($cat,$fields,$id);
    redirect_standard('cat');
}


elseif ($action == 'import') { // startif (action import)
    echo _("Uploaded CSV file should contain three values per row - Pattern to match, per minute cost, one time cost");
    echo "<br />";
    echo _("Download sample template file");
    echo "<a href=/fbilling_data/fbilling_tariffs_TEMPLATE.csv>&nbspTemplate File</a>";
    echo "<br />";
    echo "<br />";
?>
    <form enctype='multipart/form-data' method='POST' name='import_tariffs' onsubmit='return check_import_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Trunk"); ?><span><?php echo _("Destinatio trunk for imported tariffs"); ?></span></a></td>
                </td>
                <td>
                    <select name='trunk_id' tabindex="<?php echo ++$tabindex;?>" >
                        <option selected value='none'><?php echo _("Select"); ?></option>"
                        <?php
                            foreach ($trunk_list as $trunk) {
                                echo "<option value=$trunk[id]>$trunk[name]</option>";
                            }
                        ?>
                    </selct>
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Tenant"); ?><span><?php echo _("Tenant to which imported tariff will be assigned"); ?></span></a></td>
                </td>
                <td>
                    <select name='tenant_id' tabindex="<?php echo ++$tabindex;?>" >
                        <option selected value='none'><?php echo _("Select"); ?></option>"
                        <?php
                            foreach ($tenant_list as $tenant) {
                                echo "<option value=$tenant[id]>$tenant[name]</option>";
                            }
                        ?>
                    </selct>
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info' tabindex="<?php echo ++$tabindex;?>" ><?php echo _("CSV"); ?><span><?php echo _("Select CSV file containing tariff data"); ?></span></a>
                </td>
                <td>
                    <input type='file' name='csv_file' id='csv_file' >
                </td>
            </tr>
        </table>
        <input type='hidden' name='action' value='value'>
        <input type='hidden' name='action' value='process'>
        <input type='hidden' name='display' value='fbilling_admin'>
        <input type='hidden' name='cat' value='tariffs'>
        <input type='submit' name='Upload'>
    </form>


<?php 
} // endif (action import)

    elseif ($action == 'process') {
        if ($_FILES['csv_file']['tmp_name']) {
            echo _("File upload successfull...");
            echo "<br />";
            echo _("Checking if file contains valid data...");
            echo "<br />";
            // initialize error arrays and open file
            $err_count = 0;
            $invalid_prefixes = array();
            $filename = $_FILES['csv_file']['tmp_name'];
            $import_data = fopen($filename,'r');
            // import file contents into array
            while (! feof($import_data)) {
                $import_array[] = fgetcsv($import_data);
            }
            // check if array contains nonexistent tariffs
            // since we can only insert tariffs for already defined prefixes, if we wont find requested prefix, hit err_count
            foreach ($import_array as $check) {
                if ($check['0'] != 0) { // omit empty tariffs
                    $prefix_exists = fbilling_check_if_exists('prefixes','pref',$check[0]);
                    if ($prefix_exists == 0) {
                        $err_count = $err_count +1;
                        array_push($invalid_prefixes, $check[0]);
                    }
                }
            }
            if ($err_count > 0) { // notify user about errors
                echo _("There are $err_count errors in CSV files...");
                echo "<br />";
                foreach ($invalid_prefixes as $invalid_prefix) {
                    echo _("Prefix $invalid_prefix does not exist in database");
                    echo "<br />";
                }
            } else {
                echo _("Data provided in CSV file seems okay, press button to submit data to database...");
                echo "<br />";
                echo "<textarea rows='30' cols='100'>";
                foreach ($import_array as $tariff) {
                    if ($tariff['0'] != '') { // omit empty tariffs
                        $sql = "INSERT INTO billing_tariffs (prefix_id,cost,initial_cost,trunk_id,tenant_id) VALUES((SELECT id FROM billing_prefixes WHERE pref = '$tariff[0]'),'$tariff[1]','$tariff[2]','$trunk_id','$tenant_id') ON DUPLICATE KEY UPDATE prefix_id = (SELECT id FROM billing_prefixes WHERE pref = '$tariff[0]'),cost = '$tariff[1]',initial_cost = '$tariff[2]',trunk_id = '$trunk_id',tenant_id = '$tenant_id'";
                        echo $sql."\n";
                        sql($sql);
                    }
                    
                }
                echo "</textarea>";
                echo "<br />";
                echo _("Data inserted succesfully");
            }
        } else {
            echo _("There was error uploading file...");
        }
    }


    elseif ($action == 'delete') {
        fbilling_del($cat,'id',$tariff_id);
        redirect_standard('cat');
    }
?>


<script>
function check_tariff_form() {
    if (document.forms["tariff_form"]["cost"]) {
        if (document.forms["tariff_form"]["cost"].value==null || document.forms["tariff_form"]["cost"].value=="") {
            alert("Please enter valid tariff cost");
            return false;
        }
    }
    if (document.forms["tariff_form"]["initial_cost"]) {
        if (document.forms["tariff_form"]["initial_cost"].value==null || document.forms["tariff_form"]["initial_cost"].value=="") {
            alert("Please enter valid tariff initial cost");
            return false;
        }
    }
    if (document.forms["tariff_form"]["trunk_id"]) {
        if (document.forms["tariff_form"]["trunk_id"].value=='none') {
            alert("Please select trunk associated with this tariff");
            return false;
        }
    }
    if (document.forms["tariff_form"]["tenant_id"]) {
        if (document.forms["tariff_form"]["tenant_id"].value=='none') {
            alert("Please select tenant associated with this tariff");
            return false;
        }
    }
}


function check_import_form() {
    if (document.forms["import_tariffs"]["tenant_id"]) {
        if (document.forms["import_tariffs"]["tenant_id"].value=='none') {
            alert("Please select tenant associated with this tariff");
            return false;
        }
    }
    if (document.forms["import_tariffs"]["trunk_id"]) {
        if (document.forms["import_tariffs"]["trunk_id"].value=='none') {
            alert("Please select trunk associated with imported tariffs");
            return false;
        }
    }
}
</script>
