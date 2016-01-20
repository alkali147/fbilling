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
tenants.php - Responsible trunk management
*/


include "shared.php";


if ($action == 'edit' or $action == 'add') { // startif (action add/edit) 
    if ($action == 'edit') {
        $trunk_data = fbilling_get_data_by_id($cat,$id);
    }
?>
    <form name='trunk_form'  method='GET' onsubmit='return check_trunk_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Trunk Name"); ?><span><?php echo _("Descriptive name for this trunk"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='name' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value='".$trunk_data['name']."'";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Protocol"); ?><span><?php echo _("Protocol to use when making call through this trunk, e.g. SIP, DAHDI"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='protocol' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value='".$trunk_data['proto']."'";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Peer"); ?><span><?php echo _("Peer/User to send call to. This can be Asterisk peer defined in FreePBX, or IP address of a gateway, e.g. myvoiprovider, 192.168.100.254"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='dial' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$trunk_data[dial]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Add Digits"); ?><span><?php echo _("Digits to add to dialed number prior to sending to destination"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' tabindex="<?php echo ++$tabindex;?>" name='add_prefix' <?php if ($action == 'edit') {echo "value=$trunk_data[add_prefix]";} ?> >
                </td>
            </tr>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Strip Digits"); ?><span><?php echo _("Digits to stript from dialed number prior to sending to destination"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='remove_prefix' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value=$trunk_data[remove_prefix]";} ?> >
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
    $insert_ok = fbilling_check_if_exists($cat,'name',$name); // get number of trunks with same name 
    if ($insert_ok > 0) { // if yes, exit
        echo _("Trunk with specified name already exists in database, please provide different name, and try again.");
        echo "<a href='javascript:history.go(-1)''> Go Back</a>";
        return true;
    }
    $fields = array('name','proto','dial','add_prefix','remove_prefix');
    $values = array($name,$protocol,$dial,$add_prefix,$remove_prefix);
    fbilling_add($cat,$fields,$values);
    redirect_standard('cat');
}


if ($action == 'conf_edit') {
    $insert_ok = fbilling_check_if_exists($cat,'name',$name); // get number of trunks with same name 
    if ($insert_ok > 0) { // if there is trunk with requested name check whether it's not trunk we are editing
        $trunk_data = fbilling_get_data_by_id($cat,$id);
        if ($trunk_data['name'] == $name) {
            $insert_ok = 0;
        } else {
            $insert_ok = 1;
        }
    }
    if ($insert_ok > 0) {
        echo _("Trunk with specified name already exists in database, please provide different name, and try again.");
        echo "<a href='javascript:history.go(-1)''> Go Back</a>";
        return true;
    } else {
        $fields = "name = '$name', proto = '$protocol', dial = '$dial', add_prefix = '$add_prefix', remove_prefix = '$remove_prefix'";
        $values = array($name,$protocol,$dial,$add_prefix,$remove_prefix);
        fbilling_edit($cat,$fields,$id);
        redirect_standard('cat');
    }
}
?>


<script>
function check_trunk_form() {
    if (document.forms["trunk_form"]["name"]) {
        if (document.forms["trunk_form"]["name"].value==null || document.forms["trunk_form"]["name"].value=="") {
            alert("Please provide valid trunk name");
            return false;
        }
    }
    if (document.forms["trunk_form"]["protocol"]) {
        if (document.forms["trunk_form"]["protocol"].value==null || document.forms["trunk_form"]["protocol"].value=="") {
            alert("Please provide valid protocol");
            return false;
        }
    }
    if (document.forms["trunk_form"]["dial"]) {
        if (document.forms["trunk_form"]["dial"].value==null || document.forms["trunk_form"]["dial"].value=="") {
            alert("Please provide valid dial string");
            return false;
        }
    }
}
</script>
