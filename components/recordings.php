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
recordings.php - Reponsible for managing recordings associated with each hangup cause
*/


include "shared.php";


// get list of recordings and draw rnav
// of course we could name this files cause.php, and whole section whould be cause management, which would be, em, not pretty
// anyways above can be done bit later
$cause_list = fbilling_get_list('causes');
echo "<div class='rnav'><ul>";
foreach ($cause_list as $cause) {
    echo "<li class='current'><a href=/admin/config.php?display=$display&cat=$cat&action=edit&id=$cause[id]>$cause[name]</a></li>";
}
echo "</ul></div>";
$recording_list = recordings_list();



if ($action == 'edit') { // start edit cause
	$cause_data = fbilling_get_data_by_id('causes',$id);
?>

<form name='cause' method='GET'>
    <table>
        <tr>
            <td>
                <a href='#' class='info'><?php echo _("Cause Name"); ?><span><?php echo _("Selected recording will be played whenever extension will hit that cause"); ?></span></a></td>
            </td>
            <td>
                <input type='text' name='name' disabled=true tabindex="<?php echo ++$tabindex;?>" <?php if ($action == 'edit') {echo "value='".$cause_data['name']."'";} ?> >
            </td>
        </tr>
        <tr>
        	<td>
                <a href='#' class='info'><?php echo _("Recording"); ?><span><?php echo _("Select Recording that will be played back when this cause is hit"); ?></span></a></td>
            </td>
        	<td>
        		<select name='recording_id' tabindex="<?php echo ++$tabindex;?>">
    				<option selected value="0"><?php echo _("None"); ?></option>
    				<?php
    					foreach ($recording_list as $rec) {
                            if ($cause_data['recording_id'] == $rec['id']) {
                                echo "<option selected value=$rec[id]>$rec[displayname]</option>";
                            } else {
                                echo "<option value=$rec[id]>$rec[displayname]</option>";
                            }
    					}
    				?>
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
} // end edit cause


if ($action == 'conf_edit') {
    $fields = "recording_id = $recording_id";
    fbilling_edit('causes',$fields,$id);
    redirect_standard('cat');
}
?>