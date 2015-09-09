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
	echo "Import Extensions<br />";
	// get all extensions that exist in FreePBX but not in FBilling
	$sql = "SELECT extension,name FROM users WHERE users.extension NOT IN (SELECT sip_num FROM billing_extensions)";
	echo $sql;
	$extensions = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	$number_of_pages = ceil(sizeof($extensions) / 20);
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
				<th width='10%'><input type="checkbox" onClick="toggle(this)" /> <?php echo _("Import all"); ?></th>
				<th width='10%'><?php echo _("Extension"); ?></th>
				<th width='80%'><?php echo _("Name"); ?></th>
		<?php
		foreach ($extensions as $extension) {
		?>
			<tr>
				<td><input type="checkbox" name="extension" value=<?php echo $extension['extension'] ?> > 
				<td> <?php echo $extension['extension'] ?> </td>
				<td> <?php echo $extension['name'] ?> </td>
			</tr>
		<?php
		}
		?>
			</table>
		</form>
		<?php
		page($number_of_pages,$page,$cat);
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
</script>