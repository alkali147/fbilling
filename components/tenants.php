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
tenants.php - Responsible for tenant management
*/


include "shared.php";


if ($action == 'edit' or $action == 'add') { // startif (action add/edit)
    if ($action == 'edit') {
        $tenant_data = fbilling_get_data_by_id($cat,$id);
    }
?>
    <form name='tenant_form' method='GET' onsubmit='return check_tenant_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Tenant Name"); ?><span><?php echo _("Descriptive name for this tenant"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='name' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value='".$tenant_data['name']."'";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Active"); ?><span><?php echo _("Enable/disable this tenant<br />If disabled, any extensions assigned to this tenant will not be able to make outbound calls"); ?></span></a></td>
                </td>
                <td>
                    <select name='is_active' tabindex="<?php echo ++$tabindex;?>">
                    <?php
                        echo $tenant_data['is_active'];
                        foreach ($active_list as $is_active) {
                            if ($action == 'edit' and $tenant_data['is_active'] == $is_active['id']) {
                                echo "<option selected value=$is_active[id]>$is_active[name]</option>";
                            } else {
                                echo "<option value=$is_active[id]>$is_active[name]</option>";
                            }
                            
                        }
                    ?>
                </selct>
                    </select>
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
} // endif (action add/edit)


if ($action == 'conf_add') {
    $insert_ok = fbilling_check_if_exists($cat,'name',$name); // get number of tenants with same name 
    if ($insert_ok > 0) { // if there is tenant with requested name. exit
        echo _("Tenant with specified name already exists in database, provide different name, and try again.");
        echo "<a href='javascript:history.go(-1)''> Go Back</a>";
        return true;
    }
    $fields = array('name','is_active');
    $values = array($name,$is_active);
    fbilling_add($cat,$fields,$values);
    redirect_standard('cat');
}


if ($action == 'conf_edit') {
    $insert_ok = fbilling_check_if_exists($cat,'name',$name); // get number of tenants with same name 
    if ($insert_ok > 0) { // if there is tenant with requested name check whether it's not tenant we are editing
        $tenant_data = fbilling_get_data_by_id($cat,$id);
        if ($tenant_data['name'] == $name) {
            $insert_ok = 0;
        } else {
            $insert_ok = 1;
        }
    }
    if ($insert_ok > 0) {
        echo _("Tenant with specified name already exists in database, provide different name, and try again.");
        echo "<a href='javascript:history.go(-1)''> Go Back</a>";
        return true;
    } else {
        $fields = "name = '$name', is_active = $is_active";
        fbilling_edit($cat,$fields,$id);
        redirect_standard('cat');
    }
}
?>


<script>
function check_tenant_form() {
    if (document.forms["tenant_form"]["name"]) {
        if (document.forms["tenant_form"]["name"].value==null || document.forms["tenant_form"]["name"].value=="") {
            alert("Please provide valid tenant name");
            return false;
        }
    }
}
</script>
