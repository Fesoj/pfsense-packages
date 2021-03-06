<?php
/*
 * snort_preprocessors.php
 * part of pfSense
 *
 * Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
 * Copyright (C) 2008-2009 Robert Zelaya.
 * Copyright (C) 2011-2012 Ermal Luci
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */


require_once("guiconfig.inc");
require_once("/usr/local/pkg/snort/snort.inc");

global $g;

if (!is_array($config['installedpackages']['snortglobal']['rule'])) {
	$config['installedpackages']['snortglobal']['rule'] = array();
}
$a_nat = &$config['installedpackages']['snortglobal']['rule'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];
if (is_null($id)) {
        header("Location: /snort/snort_interfaces.php");
        exit;
}

$pconfig = array();
if (isset($id) && $a_nat[$id]) {
	$pconfig = $a_nat[$id];

	/* new options */
	$pconfig['perform_stat'] = $a_nat[$id]['perform_stat'];
	$pconfig['flow_depth'] = $a_nat[$id]['flow_depth'];
	$pconfig['max_queued_bytes'] = $a_nat[$id]['max_queued_bytes'];
	$pconfig['max_queued_segs'] = $a_nat[$id]['max_queued_segs'];
	$pconfig['http_inspect'] = $a_nat[$id]['http_inspect'];
	$pconfig['other_preprocs'] = $a_nat[$id]['other_preprocs'];
	$pconfig['ftp_preprocessor'] = $a_nat[$id]['ftp_preprocessor'];
	$pconfig['smtp_preprocessor'] = $a_nat[$id]['smtp_preprocessor'];
	$pconfig['sf_portscan'] = $a_nat[$id]['sf_portscan'];
	$pconfig['dce_rpc_2'] = $a_nat[$id]['dce_rpc_2'];
	$pconfig['dns_preprocessor'] = $a_nat[$id]['dns_preprocessor'];
	$pconfig['sensitive_data'] = $a_nat[$id]['sensitive_data'];
}

if ($_POST) {
	$natent = array();
	$natent = $pconfig;

	/* if no errors write to conf */
	if (!$input_errors) {
		/* post new options */
		if ($_POST['flow_depth'] != "") { $natent['flow_depth'] = $_POST['flow_depth']; }else{ $natent['flow_depth'] = ""; }
		if ($_POST['max_queued_bytes'] != "") { $natent['max_queued_bytes'] = $_POST['max_queued_bytes']; }else{ $natent['max_queued_bytes'] = ""; }
		if ($_POST['max_queued_segs'] != "") { $natent['max_queued_segs'] = $_POST['max_queued_segs']; }else{ $natent['max_queued_segs'] = ""; }

		$natent['perform_stat'] = $_POST['perform_stat'] ? 'on' : 'off';
		$natent['http_inspect'] = $_POST['http_inspect'] ? 'on' : 'off';
		$natent['other_preprocs'] = $_POST['other_preprocs'] ? 'on' : 'off';
		$natent['ftp_preprocessor'] = $_POST['ftp_preprocessor'] ? 'on' : 'off';
		$natent['smtp_preprocessor'] = $_POST['smtp_preprocessor'] ? 'on' : 'off';
		$natent['sf_portscan'] = $_POST['sf_portscan'] ? 'on' : 'off';
		$natent['dce_rpc_2'] = $_POST['dce_rpc_2'] ? 'on' : 'off';
		$natent['dns_preprocessor'] = $_POST['dns_preprocessor'] ? 'on' : 'off';
		$natent['sensitive_data'] = $_POST['sensitive_data'] ? 'on' : 'off';

		if (isset($id) && $a_nat[$id])
			$a_nat[$id] = $natent;
		else {
			if (is_numeric($after))
				array_splice($a_nat, $after+1, 0, array($natent));
			else
				$a_nat[] = $natent;
		}

		write_config();

		$if_real = snort_get_real_interface($pconfig['interface']);
		sync_snort_package_config();

		/* after click go to this page */
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header("Location: snort_preprocessors.php?id=$id");
		exit;
	}
}

$if_friendly = snort_get_friendly_interface($pconfig['interface']);
$pgtitle = "Snort: Interface {$if_real} Preprocessors and Flow";
include_once("head.inc");
?>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">

<?php include("fbegin.inc"); ?>
<?php if($pfsense_stable == 'yes'){echo '<p class="pgtitle">' . $pgtitle . '</p>';}


	/* Display Alert message */

	if ($input_errors) {
		print_input_errors($input_errors); // TODO: add checks
	}

	if ($savemsg) {
		print_info_box($savemsg);
	}

?>

<form action="snort_preprocessors.php" method="post"
	enctype="multipart/form-data" name="iform" id="iform">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td>
<?php
        $tab_array = array();
        $tab_array[] = array(gettext("Snort Interfaces"), false, "/snort/snort_interfaces.php");
        $tab_array[] = array(gettext("If Settings"), false, "/snort/snort_interfaces_edit.php?id={$id}");
        $tab_array[] = array(gettext("Categories"), false, "/snort/snort_rulesets.php?id={$id}");
        $tab_array[] = array(gettext("Rules"), false, "/snort/snort_rules.php?id={$id}");
        $tab_array[] = array(gettext("Servers"), false, "/snort/snort_define_servers.php?id={$id}");
        $tab_array[] = array(gettext("Preprocessors"), true, "/snort/snort_preprocessors.php?id={$id}");
        $tab_array[] = array(gettext("Barnyard2"), false, "/snort/snort_barnyard.php?id={$id}");
        display_top_tabs($tab_array);
?>
</td></tr>
<tr><td class="tabcont">
<table width="100%" border="0" cellpadding="6" cellspacing="0">
	<tr>
		<td width="22%" valign="top">&nbsp;</td>
		<td width="78%"><span class="vexpl"><span class="red"><strong>Note:
		</strong></span><br>
		Rules may be dependent on preprocessors!<br>
		Defaults will be used when there is no user input.<br></td>
	</tr>
	<tr>
		<td colspan="2" valign="top" class="listtopic">Performance
		Statistics</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable</td>
		<td width="78%" class="vtable"><input name="perform_stat"
			type="checkbox" value="on"
			<?php if ($pconfig['perform_stat']=="on") echo "checked"; ?>
			onClick="enable_change(false)"> Performance Statistics for this
		interface.</td>
	</tr>
	<tr>
		<td colspan="2" valign="top" class="listtopic">HTTP Inspect Settings</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable</td>
		<td width="78%" class="vtable"><input name="http_inspect"
			type="checkbox" value="on"
			<?php if ($pconfig['http_inspect']=="on") echo "checked"; ?>
			onClick="enable_change(false)"> Use HTTP Inspect to
		Normalize/Decode and detect HTTP traffic and protocol anomalies.</td>
	</tr>
	<tr>
		<td valign="top" class="vncell">HTTP server flow depth</td>
		<td class="vtable">
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td><input name="flow_depth" type="text" class="formfld"
					id="flow_depth" size="5"
					value="<?=htmlspecialchars($pconfig['flow_depth']);?>"> <strong>-1</strong>
				to <strong>1460</strong> (<strong>-1</strong> disables HTTP
				inspect, <strong>0</strong> enables all HTTP inspect)</td>
			</tr>
		</table>
		Amount of HTTP server response payload to inspect. Snort's
		performance may increase by adjusting this value.<br>
		Setting this value too low may cause false negatives. Values above 0
		are specified in bytes. Default value is <strong>0</strong><br>
		</td>
	</tr>
	<tr>
		<td colspan="2" valign="top" class="listtopic">Stream5 Settings</td>
	</tr>
	<tr>
		<td valign="top" class="vncell">Max Queued Bytes</td>
		<td class="vtable">
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td><input name="max_queued_bytes" type="text" class="formfld"
					id="max_queued_bytes" size="5"
					value="<?=htmlspecialchars($pconfig['max_queued_bytes']);?>">
				Minimum is <strong>1024</strong>, Maximum is <strong>1073741824</strong>
				( default value is <strong>1048576</strong>, <strong>0</strong>
				means Maximum )</td>
			</tr>
		</table>
		The number of bytes to be queued for reassembly for TCP sessions in
		memory. Default value is <strong>1048576</strong><br>
		</td>
	</tr>
	<tr>
		<td valign="top" class="vncell">Max Queued Segs</td>
		<td class="vtable">
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td><input name="max_queued_segs" type="text" class="formfld"
					id="max_queued_segs" size="5"
					value="<?=htmlspecialchars($pconfig['max_queued_segs']);?>">
				Minimum is <strong>2</strong>, Maximum is <strong>1073741824</strong>
				( default value is <strong>2621</strong>, <strong>0</strong> means
				Maximum )</td>
			</tr>
		</table>
		The number of segments to be queued for reassembly for TCP sessions
		in memory. Default value is <strong>2621</strong><br>
		</td>
	</tr>
	<tr>
		<td colspan="2" valign="top" class="listtopic">General Preprocessor
		Settings</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br>
		RPC Decode and Back Orifice detector</td>
		<td width="78%" class="vtable"><input name="other_preprocs"
			type="checkbox" value="on"
			<?php if ($pconfig['other_preprocs']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		Normalize/Decode RPC traffic and detects Back Orifice traffic on the
		network.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br>
		FTP and Telnet Normalizer</td>
		<td width="78%" class="vtable"><input name="ftp_preprocessor"
			type="checkbox" value="on"
			<?php if ($pconfig['ftp_preprocessor']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		Normalize/Decode FTP and Telnet traffic and protocol anomalies.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br>
		SMTP Normalizer</td>
		<td width="78%" class="vtable"><input name="smtp_preprocessor"
			type="checkbox" value="on"
			<?php if ($pconfig['smtp_preprocessor']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		Normalize/Decode SMTP protocol for enforcement and buffer overflows.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br>
		Portscan Detection</td>
		<td width="78%" class="vtable"><input name="sf_portscan"
			type="checkbox" value="on"
			<?php if ($pconfig['sf_portscan']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		Detects various types of portscans and portsweeps.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br>
		DCE/RPC2 Detection</td>
		<td width="78%" class="vtable"><input name="dce_rpc_2"
			type="checkbox" value="on"
			<?php if ($pconfig['dce_rpc_2']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		The DCE/RPC preprocessor detects and decodes SMB and DCE/RPC
		traffic.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br>
		DNS Detection</td>
		<td width="78%" class="vtable"><input name="dns_preprocessor"
			type="checkbox" value="on"
			<?php if ($pconfig['dns_preprocessor']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		The DNS preprocessor decodes DNS Response traffic and detects some
		vulnerabilities.</td>
	</tr>
	<tr>
		<td width="22%" valign="top" class="vncell">Enable <br> Sensitive Data</td>
		<td width="78%" class="vtable">
			<input name="sensitive_data" type="checkbox" value="on"
			<?php if ($pconfig['sensitive_data']=="on") echo "checked"; ?>
			onClick="enable_change(false)"><br>
		Sensisitive data searches for CC or SS# in data
		</td>
	</tr>
	<tr>
		<td width="22%" valign="top">&nbsp;</td>
				<td width="78%">
					<input name="Submit" type="submit" class="formbtn" value="Save">
					<input name="id" type="hidden" value="<?=$id;?>"></td>
			</tr>
			<tr>
				<td width="22%" valign="top">&nbsp;</td>
				<td width="78%"><span class="vexpl"><span class="red"><strong>Note:</strong></span>
				<br>
				Please save your settings before you click Start. </td>
			</tr>
</table>
</td></tr></table>
</form>
<?php include("fend.inc"); ?>
</body>
</html>
