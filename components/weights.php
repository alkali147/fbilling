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
weights.php - Responsible for weight amnagement
*/


include "shared.php";


if ($action == 'edit' or $action == 'add') { // startif (action add/edit) 
    if ($action == 'edit') {
        $weight_data = fbilling_get_data_by_id($cat,$id);
    }
?>
    <form name='weight_form'  method='GET' onsubmit='return check_weight_form();'>
        <table>
            <tr>
                <td>
                    <a href='#' class='info'><?php echo _("Weight Name"); ?><span><?php echo _("Descriptive name for this weight"); ?></span></a></td>
                </td>
                <td>
                    <input type='text' name='name' tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value='".$weight_data['name']."'";} ?> >
                </td>
            </tr>
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
    $insert_ok = fbilling_check_if_exists($cat,'name',$name); // get number of weights with same name 
    if ($insert_ok > 0) { // if there is weight with requested name. exit
        echo _("Weight with specified name already exists in database, please provide different name, and try again");
        echo "<a href='javascript:history.go(-1)''> Go Back</a>";
        return true;
    }
    $fields = array('name');
    $values = array($name);
    fbilling_add($cat,$fields,$values);
    redirect_standard('cat');
}


if ($action == 'conf_edit') {
    $insert_ok = fbilling_check_if_exists($cat,'name',$name); // get number of weights with same name 
    if ($insert_ok > 0) { // if there is weight with requested name check whether it's not weight we are editing
        $weight_data = fbilling_get_data_by_id($cat,$id);
        if ($weight_data['name'] == $name) {
            $insert_ok = 0;
        } else {
            $insert_ok = 1;
        }
    }
    if ($insert_ok > 0) {
        echo _("Weight with specified name already exists in database, please provide different name, and try again");
        echo "<a href='javascript:history.go(-1)''>Go Back</a>";
        return true;
    } else {
        $fields = "name = '$name'";
        fbilling_edit($cat,$fields,$id);
        redirect_standard('cat');
    }
}
?>


<script>
function check_weight_form() {
    if (document.forms["weight_form"]["name"]) {
        if (document.forms["weight_form"]["name"].value==null || document.forms["weight_form"]["name"].value=="") {
            alert("Please provide valid weight name");
            return false;
        }
    }
}
</script>