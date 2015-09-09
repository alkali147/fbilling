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
extensions.php - Responsible for extension management
*/


include "shared.php";

// start overview
if ($action == 'overview') {
	?>
	<a href=/admin/config.php?display=fbilling_admin&cat=extensions&action=import&button=Manage+Extensions>Import Extensions</a><br />
	<a href=/admin/config.php?display=fbilling_admin&cat=extensions&action=overview&button=Manage+Extensions>Delete Extensions</a><br />
	<a href=/admin/config.php?display=fbilling_admin&cat=extensions&action=overview&button=Manage+Extensions>Update Extensions</a><br />
	<?php
}
// end overview

// start import
if ($action == 'import') {
	if ($_REQUEST['config'] != 'apply') {
		echo "Import Extensions<br />";
		// get all extensions that exist in FreePBX but not in FBilling
		// get all extensions for paging purposes
		$sql = "SELECT extension,name FROM users WHERE users.extension NOT IN (SELECT sip_num FROM billing_extensions)";
		$extensions = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
		$number_of_pages = ceil(sizeof($extensions) / 20);
		//get only results that we will display on single page
		$sql .= " LIMIT 20 OFFSET $offset";
		$extensions = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
		if (sizeof($extensions) == 0) {
			echo _("All extensions seem to be present in FBilling...");
		} else {
			$tenant_list = fbilling_get_list('tenants');
			$permission_list = fbilling_get_list('permissions');
			?>
			<form name='extension_form' method='GET' onsubmit='return check_extension_form();'>
				<table class="fbilling">
					<th><?php echo _("Value"); ?></th>
					<th><?php echo _("Data"); ?></th>
					<th><?php echo _("Action"); ?></th>
					<tr>
		                <td>
		                    <a href='#' class='info'><?php echo _("Tenant"); ?><span><?php echo _("Tnenat to which imported extensions will be associated"); ?></span></a></td>
		                </td>
		                <td>
		                    <select name='tenant_id' tabindex="<?php echo ++$tabindex;?>" >
		                        <option selected value='none'><?php echo _("Select"); ?></option>"
			                    <?php
			                        foreach ($tenant_list as $tenant) {
			                        	echo "<option value=$tenant[id]>$tenant[name]</option>";
			                        }
			                    ?>
			                </select>
		                </td>
		            </tr>
		            <tr>
		                <td>
		                    <a href='#' class='info'><?php echo _("Permission"); ?><span><?php echo _("Permission which imported extensions will have"); ?></span></a></td>
		                </td>
		                <td>
		                    <select name='permission_id' tabindex="<?php echo ++$tabindex;?>" >
		                        <option selected value='none'><?php echo _("Select"); ?></option>"
		                    <?php
		                        foreach ($permission_list as $permission) {
		                        	echo "<option value=$permission[id]>$permission[name]</option>";
		                        }
		                    ?>
		                </select>
		                </td>
		            </tr>
		            <tr>
	                	<td>
	                    	<a href='#' class='info'><?php echo _("Refill"); ?><span><?php echo _("Refill balance for imported extensions?"); ?></span></a></td>
		                </td>
		                <td>
		                    <select name='refill' tabindex="<?php echo ++$tabindex;?>">
		                    <?php
		                        foreach ($active_list as $refill) {
		                                echo "<option value=$refill[id]>$refill[name]</option>";
		                        }
		                    ?>
		                    </select>
		                </td>
		            </tr>
		            <tr>
	                	<td>
	                    	<a href='#' class='info'><?php echo _("Use Limit"); ?><span><?php echo _("Allow unlimited calling for imported extensions?"); ?></span></a></td>
		                </td>
		                <td>
		                    <select name='use_limit' tabindex="<?php echo ++$tabindex;?>">
		                    <?php
		                        foreach ($active_list as $use_limit) {
		                                echo "<option value=$use_limit[id]>$use_limit[name]</option>";
		                        }
		                    ?>
		                    </select>
		                </td>
		            </tr>
		            <tr>
		                <td>
		                    <a href='#' class='info'><?php echo _("Initial Balance"); ?><span><?php echo _("Balance that imported extensions will have"); ?></span></a></td>
		                </td>
		                <td>
		                    <input type='text' name='balance' tabindex="<?php echo ++$tabindex;?>">
		                </td>
	        			<td>
	        				<input type='hidden' name='display' value=<?php echo $display; ?>>
	        				<input type='hidden' name='cat' value=<?php echo $cat; ?> >
	        				<input type='hidden' name='action' value=<?php echo $action; ?> >
	        				<input type='hidden' name='config' value="apply">
	        				<input name="submit" type="submit" value="<?php echo _("Import")?>" tabindex="<?php echo ++$tabindex;?>">
	        			</td>
		            </tr>
				</table>
				<table class="fbilling">
					<th><?php echo _("Select extensions"); ?></th>
					<th><?php echo _("Action"); ?></th>
					<th><?php echo _("Extensions to be imported"); ?></th>
					<tr>
						<td>
							<div>
								<div>
									<center><select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple" style="width: 300px;">
										<?php
											foreach ($extensions as $extension) {
												?>
												<option value=<?php echo $extension['extension']; ?> selected ><?php echo $extension['extension']." - ".$extension['name']; ?>  </option>
												<?php
											}
										?>
									</select>
								</div>
							<div>
						</td>
						<td>
							<center><button type="button" id="multiselect_rightAll">>></button><br/><br/>
							<button type="button" id="multiselect_rightSelected">></button><br/><br/>
							<button type="button" id="multiselect_leftSelected"><</i></button><br/><br/>
							<button type="button" id="multiselect_leftAll"><<</i></button><br/>
						</td>
							</div>
							<td>
								<div>
									<center><select name="to[]" id="multiselect_to" class="form-control" size="8" multiple="multiple" style="width: 300px;" selected></select>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
	if ($_REQUEST['config'] == 'apply') {
		echo "Applying configuration changes";
		foreach ($extension as $ext) {
			echo $ext;
		}
	}
}

// end import
?>


<script language="JavaScript">
function toggle(source) {
  checkboxes = document.getElementsByName('extension');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
}

jQuery(document).ready(function($) {
	$('#multiselect').multiselect();
});


function check_extension_form() {
    if (document.forms["extension_form"]["permission_id"]) {
        if (document.forms["extension_form"]["permission_id"].value=='none') {
            alert("Please select permission");
            return false;
        }
    }
    if (document.forms["extension_form"]["tenant_id"]) {
        if (document.forms["extension_form"]["tenant_id"].value=='none') {
            alert("Please select tenant");
            return false;
        }
    }
    if (document.forms["extension_form"]["balance"]) {
        if (document.forms["extension_form"]["balance"].value==null || document.forms["extension_form"]["balance"].value=="") {
            alert("Please enter valid balance");
            return false;
        }
    }
}

</script>