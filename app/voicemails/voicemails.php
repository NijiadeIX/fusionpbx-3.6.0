<?php
/*
 FusionPBX
 Version: MPL 1.1

 The contents of this file are subject to the Mozilla Public License Version
 1.1 (the "License"); you may not use this file except in compliance with
 the License. You may obtain a copy of the License at
 http://www.mozilla.org/MPL/

 Software distributed under the License is distributed on an "AS IS" basis,
 WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 for the specific language governing rights and limitations under the
 License.

 The Original Code is FusionPBX

 The Initial Developer of the Original Code is
 Mark J Crane <markjcrane@fusionpbx.com>
 Portions created by the Initial Developer are Copyright (C) 2008-2012
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
if (permission_exists('voicemail_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}
//add multi-lingual support
	require_once "app_languages.php";
	foreach($text as $key => $value) {
		$text[$key] = $value[$_SESSION['domain']['language']['code']];
	}

//set the voicemail_id array
	foreach ($_SESSION['user']['extension'] as $row) {
		$voicemail_ids[]['voicemail_id'] = $row['user'];
		if (strlen($row['number_alias']) > 0) {
			$voicemail_ids[]['voicemail_id'] = $row['number_alias'];
		}
		else {
			$voicemail_ids[]['voicemail_id'] = $row['user'];
		}
	}

//get the http values and set them as variables
	$search = check_str($_GET["search"]);
	if (isset($_GET["order_by"])) {
		$order_by = check_str($_GET["order_by"]);
		$order = check_str($_GET["order"]);
	}

//additional includes
	require_once "resources/header.php";
	require_once "resources/paging.php";

//show the content
	echo "<div align='center'>";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='2'>\n";
	echo "<tr class='border'>\n";
	echo "	<td align=\"center\">\n";
	echo "		<br />";

	echo "<table width='100%' border='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='50%' align='left' nowrap='nowrap'><b>".$text['title-voicemails']."</b></td>\n";
	echo "		<form method='get' action=''>\n";
	echo "			<td width='30%' align='right'>\n";
	echo "				<input type='text' class='txt' style='width: 150px' name='search' value='$search'>";
	echo "				<input type='submit' class='btn' name='submit' value='".$text['button-search']."'>";
	echo "			</td>\n";
	echo "		</form>\n";
	echo "	</tr>\n";
	echo "	<tr>\n";
	echo "		<td align='left' colspan='2'>\n";
	echo "			".$text['description-voicemail']."<br /><br />\n";
	echo "		</td>\n";
	echo "	</tr>\n";
	echo "</table>\n";

	//prepare to page the results
		$sql = "select count(*) as num_rows from v_voicemails ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		if (strlen($search) > 0) {
			$sql .= "and (";
			$sql .= "	voicemail_id like '%".$search."%' ";
			$sql .= " 	or voicemail_mail_to like '%".$search."%' ";
			$sql .= " 	or voicemail_attach_file like '%".$search."%' ";
			$sql .= " 	or voicemail_local_after_email like '%".$search."%' ";
			$sql .= " 	or voicemail_enabled like '%".$search."%' ";
			$sql .= " 	or voicemail_description like '%".$search."%' ";
			$sql .= ") ";
		}
		if (!permission_exists('voicemail_delete')) {
			$x = 0;
			if (count($voicemail_ids) > 0) {
				$sql .= "and (";
				foreach($voicemail_ids as $row) {
					if ($x == 0) {
						$sql .= "voicemail_id = '".$row['voicemail_id']."' ";
					}
					else {
						$sql .= " or voicemail_id = '".$row['voicemail_id']."'";
					}
					$x++;
				}
				$sql .= ")";
			}
		}
		if (strlen($order_by)> 0) { $sql .= "order by $order_by $order "; }
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
		$prep_statement->execute();
			$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
			if ($row['num_rows'] > 0) {
				$num_rows = $row['num_rows'];
			}
			else {
				$num_rows = '0';
			}
		}

	//prepare to page the results
		$rows_per_page = 150;
		$param = "";
		$page = $_GET['page'];
		if (strlen($page) == 0) { $page = 0; $_GET['page'] = 0; }
		list($paging_controls, $rows_per_page, $var3) = paging($num_rows, $param, $rows_per_page);
		$offset = $rows_per_page * $page;

	//get the list
		$sql = "select * from v_voicemails ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		if (strlen($search) > 0) {
			$sql .= "and (";
			$sql .= "	voicemail_id like '%".$search."%' ";
			$sql .= " 	or voicemail_mail_to like '%".$search."%' ";
			$sql .= " 	or voicemail_attach_file like '%".$search."%' ";
			$sql .= " 	or voicemail_local_after_email like '%".$search."%' ";
			$sql .= " 	or voicemail_enabled like '%".$search."%' ";
			$sql .= " 	or voicemail_description like '%".$search."%' ";
			$sql .= ") ";
		}
		if (!permission_exists('voicemail_delete')) {
			$x = 0;
			if (count($voicemail_ids) > 0) {
				$sql .= "and (";
				foreach($voicemail_ids as $row) {
					if ($x == 0) {
						$sql .= "voicemail_id = '".$row['voicemail_id']."' ";
					}
					else {
						$sql .= " or voicemail_id = '".$row['voicemail_id']."'";
					}
					$x++;
				}
				$sql .= ")";
			}
		}
		if (strlen($order_by) == 0) {
			$sql .= "order by voicemail_id asc ";
		}
		else {
			$sql .= "order by $order_by $order ";
		}
		$sql .= "limit $rows_per_page offset $offset ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
		$result_count = count($result);
		unset ($prep_statement, $sql);

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

	echo "<div align='center'>\n";
	echo "<table class='tr_hover' width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
	echo "<tr>\n";
	echo th_order_by('voicemail_id', $text['label-voicemail_id'], $order_by, $order);
	//echo th_order_by('voicemail_password', $text['label-voicemail_password'], $order_by, $order);
	//echo th_order_by('greeting_id', $text['label-greeting_id'], $order_by, $order);
	echo th_order_by('voicemail_mail_to', $text['label-voicemail_mail_to'], $order_by, $order);
	echo th_order_by('voicemail_attach_file', $text['label-voicemail_attach_file'], $order_by, $order);
	echo th_order_by('voicemail_local_after_email', $text['label-voicemail_local_after_email'], $order_by, $order);
	//echo "<th>".$text['label-count']."</th>\n";
	echo "<th>".$text['label-tools']."</th>\n";
	echo th_order_by('voicemail_enabled', $text['label-voicemail_enabled'], $order_by, $order);
	echo th_order_by('voicemail_description', $text['label-voicemail_description'], $order_by, $order);
	echo "<td class='list_control_icons'>";
	if (permission_exists('voicemail_add')) {
		echo "<a href='voicemail_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "</td>\n";
	echo "</tr>\n";

	if ($result_count > 0) {
		foreach($result as $row) {
			$tr_link = (permission_exists('voicemail_edit')) ? "href='voicemail_edit.php?id=".$row['voicemail_uuid']."'" : null;
			echo "<tr ".$tr_link.">\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>";
			if (permission_exists('voicemail_edit')) {
				echo "<a href='voicemail_edit.php?id=".$row['voicemail_uuid']."'>".$row['voicemail_id']."</a>";
			}
			else {
				echo $row['voicemail_id'];
			}
			echo "	</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_password']."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>".$row['greeting_id']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".$row['voicemail_mail_to']."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['voicemail_attach_file'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['voicemail_local_after_email'])."&nbsp;</td>\n";
			//echo "	<td valign='top' class='".$row_style[$c]."'>&nbsp;</td>\n";
			echo "	<td valign='middle' class='".$row_style[$c]."' style='white-space: nowrap;'>\n";
			echo "		<a href='voicemail_messages.php?id=".$row['voicemail_uuid']."'>".$text['label-view']."</a>&nbsp;&nbsp;\n";
			echo "		<a href='".PROJECT_PATH."/app/voicemail_greetings/voicemail_greetings.php?id=".$row['voicemail_id']."'>".$text['label-greetings']."</a>\n";
			echo "	</td>\n";
			echo "	<td valign='top' class='".$row_style[$c]."'>".ucwords($row['voicemail_enabled'])."&nbsp;</td>\n";
			echo "	<td valign='top' class='row_stylebg' width='30%'>".$row['voicemail_description']."&nbsp;</td>\n";
			echo "	<td class='list_control_icons'>";
			if (permission_exists('voicemail_edit')) {
				echo "<a href='voicemail_edit.php?id=".$row['voicemail_uuid']."' alt='".$text['button-edit']."'>$v_link_label_edit</a>";
			}
			if (permission_exists('voicemail_delete')) {
				echo "<a href='voicemail_delete.php?id=".$row['voicemail_uuid']."' alt='".$text['button-delete']."' onclick=\"return confirm('".$text['confirm-delete']."')\">$v_link_label_delete</a>";
			}
			echo "	</td>\n";
			echo "</tr>\n";
			if ($c==0) { $c=1; } else { $c=0; }
		} //end foreach
		unset($sql, $result, $row_count);
	} //end if results

	echo "<tr>\n";
	echo "<td colspan='11' align='left'>\n";
	echo "	<table width='100%' cellpadding='0' cellspacing='0'>\n";
	echo "	<tr>\n";
	echo "		<td width='33.3%' nowrap='nowrap'>&nbsp;</td>\n";
	echo "		<td width='33.3%' align='center' nowrap='nowrap'>$paging_controls</td>\n";
	echo "		<td class='list_control_icons'>";
	if (permission_exists('voicemail_add')) {
		echo 		"<a href='voicemail_edit.php' alt='".$text['button-add']."'>$v_link_label_add</a>";
	}
	echo "		</td>\n";
	echo "	</tr>\n";
 	echo "	</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br /><br />";

//include the footer
	require_once "resources/footer.php";
?>