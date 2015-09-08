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
shared.php - Set of functions/code not belonging anywhere otherwise
*/


echo "<h5>Manage $cat</h5>";
if ($cat != 'prefixes' and $cat != 'tariffs' and $cat != 'recordings' and $cat != 'extensions') {	// we need to display rnav in every component page except prefixes and tariffs and recordings and extensions
	$component_list = fbilling_get_list($cat);
	echo "<div class='rnav'><ul>";
	echo "<li class='current'><a href=/admin/config.php?display=$display&cat=$cat&action=add>Add $fbilling_strings[$cat]</a></li>";
	foreach ($component_list as $component) {
	    echo "<li class='current'><a href=/admin/config.php?display=$display&cat=$cat&action=edit&id=$component[id]>$component[name]</a></li>";
	}
	echo "</ul></div>";
}


function page($number_of_pages,$page,$cat) { // used int tariffs in prefixes, in future we should move to new function
	$request_inputs = "
		<input type='hidden' name='display' value='fbilling_admin'>
		<input type='hidden' name='action' value='list'>
		<input type='hidden' name='cat' value='$cat'>
		<input type='hidden' name='prefix' value=$_REQUEST[prefix] >
		<input type='hidden' name='prefix_weight_id' value=$_REQUEST[prefix_weight_id] >
	";
	if ($cat == 'prefixes') {
		$request_inputs .= "<input type='hidden' name='prefix_is_active' value=$_REQUEST[prefix_is_active] >";
	}
	if ($cat == 'tariffs') {
		$request_inputs .= "<input type='hidden' name='tenant_id' value=$_REQUEST[tenant_id] >";
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


?>
