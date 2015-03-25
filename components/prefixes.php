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
prefixes.php - Responsible for prefix management
*/


include "shared.php";


if ($action == 'list' or !$action) { // startif (action list)
?>
    <form method='GET' name='search_prefixes'>
        <table class='fbilling'>
            <th width='10%'>Filter</th>
            <th width='20%'>Value</th>
            <th width='10%'>Action</th>
            <tr>
                <td><?php echo _("Prefix"); ?></td>
                <td><input type'text' name='prefix' tabindex="<?php echo ++$tabindex;?>" value=<?php echo $_REQUEST['prefix']; ?> ></td>
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
                    <input type='hidden' name='cat' value='prefixes'>
                    <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" name='export' value='Export'></td>
            </tr>
            <tr>
                <td><?php echo _("Active"); ?></td>
                <td>
                    <select name='prefix_is_active' tabindex="<?php echo ++$tabindex;?>">
                        <option value='all'><?php echo _("All"); ?></option>
                        <?php
                            foreach ($active_list as $active) {
                                if ($_REQUEST['prefix_is_active'] == $active['id']) {
                                    echo "<option selected value=$active[id]>$active[name]</option>";
                                } else {
                                    echo "<option value=$active[id]>$active[name]</option>";
                                }
                            }
                        ?>
                    </select>
                </td> 
                <input type='hidden' name='display' value='fbilling_admin'>
                <input type='hidden' name='cat' value='prefixes'>
                <input type='hidden' name='action' value='list'>
                <td><input type='submit' tabindex="<?php echo ++$tabindex;?>" value='Search'></td>
            </tr>
        </table>
    </form>
    <h5><?php echo _("Search Results"); ?></h5><hr>


<?php
    if (!$prefix) {$prefix_sql = "billing_prefixes.pref LIKE '%'";} else {$prefix_sql = "billing_prefixes.pref LIKE '$prefix%'";}
    if ($prefix_weight_id == 'all') {$prefix_weight_id_sql = "billing_prefixes.weight_id LIKE '%'";} else {$prefix_weight_id_sql = "billing_prefixes.weight_id = '$prefix_weight_id'";}
    if ($prefix_is_active == 'all') {$prefix_is_active_sql = "billing_prefixes.is_active LIKE '%'";} else {$prefix_is_active_sql = "billing_prefixes.is_active = '$prefix_is_active'";}
    $sql = "SELECT billing_prefixes.id AS id, billing_prefixes.pref AS pref, billing_prefixes.country AS country, billing_prefixes.description AS description, billing_weights.name AS weight_name, billing_prefixes.is_active AS is_active FROM billing_prefixes, billing_weights WHERE";
    $sql .= " $prefix_sql AND ";
    $sql .= " $prefix_is_active_sql AND ";
    $sql .= " $prefix_weight_id_sql AND ";
    $sql .= " billing_prefixes.weight_id = billing_weights.id";
    $sql_summary = $sql;
    $search_summary = sql($sql,'getAll', DB_FETCHMODE_ASSOC);
    if ($_REQUEST['export'] == 'Export') { // if user hit export button generate csv file
        $csv_file_url = fbilling_get_csv_file($cat,$sql);
    }
    $number_of_pages = ceil(sizeof($search_summary) / 20);
    $sql .= " LIMIT 20 OFFSET $offset";
    $search_results = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
?>


    <table class='fbilling'>
        <th><?php echo _("Prefix"); ?></th>
        <th><?php echo _("Country"); ?></th>
        <th><?php echo _("Description"); ?></th>
        <th><?php echo _("Weight"); ?></th>
        <th><?php echo _("Enabled"); ?></th>
        <th><?php echo _("Action"); ?></th>
        <?php
            foreach ($search_results as $prefix) {
                echo "<tr class=\"record\">
                        <td>".$prefix['pref']."</td>
                        <td>".$prefix['country']."</td>
                        <td>".$prefix['description']."</td>
                        <td>".$prefix['weight_name']."</td>";
                        if ($prefix['is_active'] == '1') {echo "<td>Yes</td>";} else {echo "<td>No</td>";} //<td>".$prefix['is_active']."</td>
                        echo "<td width='20%'><a href=/admin/config.php?display=fbilling_admin&cat=prefixes&action=edit&id=$prefix[id]>Edit Prefix</a>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<a href=/admin/config.php?display=fbilling_admin&cat=tariffs&action=add&prefix_id=$prefix[id]>Add Tariff</a>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<a href=/admin/config.php?display=fbilling_admin&cat=prefixes&action=delete&prefix_id=$prefix[id]>Delete Prefix</a></td>
                    </tr>
                ";
            }
        ?>
    </table>
    <hr>


<?php
    page($number_of_pages,$page,$cat);
?>


    <a href=/admin/config.php?display=fbilling_admin&cat=prefixes&action=add><?php echo _("Add New Prefix"); ?></a>
    <a href=/admin/config.php?display=fbilling_admin&cat=prefixes&action=import><?php echo _("Import Prefixes"); ?></a>


<?php
    if ($_REQUEST['export'] == 'Export') { // show csv download link if user hit export button
        echo "<a href=/fbilling_data/$csv_file_url>Download CSV file</a>";
    }
} // endif (action list)


elseif ($action == 'add' or $action == 'edit') { // startif (action add/edit) 
    if ($action == 'edit') {
        $prefix_data = fbilling_get_data_by_id($cat,$id);
    }
?>
    <form name='prefix_form' method='GET' onsubmit='return check_prefix_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Prefix"); ?><span><?php echo _("Pattern that will be matched against dialed number<br />In most cases this will be country or area code"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='prefix' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$prefix_data[pref]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Country"); ?><span><?php echo _("Country to which this prefix belongs"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='country' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$prefix_data[country]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Description"); ?><span><?php echo _("Description for this prefix"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='prefix_description' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$prefix_data[description]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Active"); ?><span><?php echo _("Enable/Disable this prefix<br />If disabled extensions will not be able to make outbound calls with this prefix"); ?></span></a></td>
                </td>
                <td>
                    <select name='prefix_is_active' tabindex="<?php echo ++$tabindex;?>">
                    <?php
                        foreach ($active_list as $active) {
                            if ($prefix_data['is_active'] == $active['id']) {
                                echo "<option selected value=$active[id]>$active[name]</option>";
                            } else {
                                echo "<option value=$active[id]>$active[name]</option>";
                            }
                        }
                    ?>
                </selct>
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Weight"); ?><span><?php echo _("Assign weight to this prefix"); ?></span></a></td>
                </td>
                <td>
                <select name='prefix_weight_id' tabindex="<?php echo ++$tabindex;?>">
                    <?php
                        foreach ($weight_list as $weight) {
                            if ($prefix_data['weight_id'] == $weight['id']) {
                                echo "<option selected value=$weight[id]>$weight[name]</option>";
                            } else {
                                echo "<option value=$weight[id]>$weight[name]</option>";
                            }
                            
                        }
                    ?>
                </selct>
            </td>
            </tr>
        </table>
        <input type='hidden' name='display' value=<?php echo $display; ?>>
        <input type='hidden' name='action' value=<?php echo $form_action; ?>>
        <?php if ($action == 'edit') {echo "<input type='hidden' name='id' value=$id>";} ?>
        <input type='hidden' name='cat' value=<?php echo $cat; ?> >
        <td colspan="2"><br><h6><input name="submit" type="submit" value="<?php echo _("Submit")?>" tabindex="<?php echo ++$tabindex;?>"></h6></td>
    </form>


<?php
} // endif (actio  add/edit)


    elseif ($action == 'conf_add') {
        $insert_ok = fbilling_check_if_exists($cat,'pref',$prefix);
        if ($insert_ok > 0) { // if there is prefix with requested name. exit
            echo "Prefix with specified pattern already exists in database, please use different pattern, or edit existing one.<br />";
            echo "<a href='javascript:history.go(-1)''>Go Back</a>";
            return true;
        }
        $fields = array('pref','is_active','weight_id','country','description');
        $values = array($prefix,$prefix_is_active,$prefix_weight_id,$country,$prefix_description);
        fbilling_add($cat,$fields,$values);
        redirect_standard('cat');
    }

    elseif ($action == 'conf_edit') {
        $insert_ok = fbilling_check_if_exists($cat,'pref',$prefix); // get number of tenants with same name 
        if ($insert_ok > 0) { // if there is prefix with requested name check whether it's not prefix we are editing
            $prefix_data = fbilling_get_data_by_id($cat,$id);
            if ($prefix_data['pref'] == $prefix) {
                $insert_ok = 0;
            } else {
                $insert_ok = 1;
            }
        }
        if ($insert_ok > 0) {
            echo _("Prefix with specified pattern already exists in database, please use different pattern, or edit existing one.");
            echo "<br /><a href='javascript:history.go(-1)''>Go Back</a>";
            return true;
        } else {
            $fields = "pref = '$prefix', is_active = $prefix_is_active, weight_id = '$prefix_weight_id', country = '$country', description = '$prefix_description'";
            fbilling_edit($cat,$fields,$id);
            redirect_standard('cat');
        }
    }


    elseif ($action == 'import') { // startif (action import)
        echo _("Uploaded CSV file should contain four values per row - Pattern to match, Country, Description, and State");
        echo "<br />";
        echo _("Download sample template file");
        echo "<a href=/fbilling_data/fbilling_prefixes_TEMPLATE.csv>&nbspTemplate File</a>";
        echo "<br />";
        echo "<br />";

?>


    <form enctype='multipart/form-data' method='POST' name='import_prefixes' onsubmit='return check_import_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Weight"); ?><span><?php echo _("Select weight to which imported prefixes will be assigned"); ?></span></a></td>
                </td>
                <td>
                    <select name='prefix_weight_id'>
                        <option selected value='none'><?php echo _("Select"); ?></option>"
                        <?php
                            foreach ($weight_list as $weight) {
                                echo "<option value=$weight[id]>$weight[name]</option>";
                            }
                        ?>
                    </selct>
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("CSV"); ?><span><?php echo _("Select file containing prefix data"); ?></span></a>
                </td>
                <td>
                    <input type='file' name='csv_file' id='csv_file' >
                </td>
            </tr>
        </table>
        <input type='hidden' name='action' value='value'>
        <input type='hidden' name='action' value='process'>
        <input type='hidden' name='display' value='fbilling_admin'>
        <input type='hidden' name='cat' value='prefixes'>
        <input type='submit' name='Upload'>
    </form>

<?php
    } // endif (action import) 


    elseif ($_REQUEST['action'] == 'process') {
        if ($_FILES['csv_file']['tmp_name']) {
            echo _("File upload successfull...");
            echo "<br />";
            echo _("Checking if file contains valid data...");
            echo "<br />";
            $err_coount = 0;
            $invalid_prefixes = array();
            $filename = $_FILES['csv_file']['tmp_name'];
            $import_data = fopen($filename,"r");
            // load csv into array
            while (! feof($import_data)) {
                $import_array[] = fgetcsv($import_data);
            }
            // check if array contains existent prefixes
            foreach ($import_array as $check) {
                if ($check['0'] != 0) { // omit empty prefixes
                    $prefix_exists = fbilling_check_if_exists($cat,'pref',$check[0]);
                    if ($prefix_exists > 0) {
                        $err_coount = $err_coount +1;
                        array_push($invalid_prefixes, $check[0]);
                    }
                }
                
            }
            // if one ore more prefix exists in db, exit
            if ($err_coount > 0) {
                // TODO maybe ask user to proceed anyways without these prefixes?
                echo _("You have $err_coount errors in your file.");
                echo "<br />";
                foreach ($invalid_prefixes as $invalid_prefix) {
                    echo _("Prefix $invalid_prefix already exists in database.");
                    echo "<br />";
                }
                echo _("You have invalid prefixes specificed in you CSV file, please fix these and upload file again.");
                echo "<br />";
                echo "<a href='javascript:history.go(-1)''>Go Back</a>";
            } else {
                // submit
                echo _("Data provided in CSV file seems okay, press button to submit data to database...");
                echo "<br />";
                echo "<textarea rows='30' cols='100'>";
                foreach ($import_array as $prefix) {
                    if ($prefix['0'] != '') { // omit empty prefixes
                        $sql = "INSERT INTO billing_$cat (pref,country,description,weight_id,is_active) VALUES ('$prefix[0]','$prefix[1]','$prefix[2]','$prefix_weight_id','$prefix[3]') ON DUPLICATE KEY UPDATE pref = '$prefix[0]',country = '$prefix[1]',description = '$prefix[2]',weight_id = '$prefix_weight_id',is_active = '$prefix[3]'";
                        echo $sql."\n";
                        sql($sql);
                    }
                }
                echo "</textarea>";
                echo "<br />";
                echo _("Data inserted succesfully");
            }
        } else {
            echo _("File not present"); // TODO add goback
        }
    }


    elseif ($action == 'delete') {
        fbilling_del($cat,'id',$prefix_id);
        fbilling_del('tariffs','prefix_id',$prefix_id);
        redirect_standard('cat');
    }
?>


<script>
function check_prefix_form() {
    if (document.forms["prefix_form"]["prefix"]) {
        if (document.forms["prefix_form"]["prefix"].value==null || document.forms["prefix_form"]["prefix"].value=="") {
            alert("Please enter valid tenant prefix");
            return false;
        }
    }
}


function check_import_form() {
    if (document.forms["import_prefixes"]["prefix_weight_id"]) {
        if (document.forms["import_prefixes"]["prefix_weight_id"].value=='none') {
            alert("Please select weight");
            return false;
        }
    }
}
</script>
