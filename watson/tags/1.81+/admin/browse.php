<?php
/*
* LimeSurvey
* Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
* 
* $Id: browse.php 6632 2009-04-14 13:09:53Z jcleeland $
*/

include_once("login_check.php");  //Login Check dies also if the script is started directly

if (!isset($limit)) {$limit=returnglobal('limit');}
if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($id)) {$id=returnglobal('id');}
if (!isset($order)) {$order=returnglobal('order');}
if (!isset($browselang)) {$browselang=returnglobal('browselang');}

//Ensure script is not run directly, avoid path disclosure
if (!isset($dbprefix) || isset($_REQUEST['dbprefix'])) {die("Cannot run this script directly");}

//Check if results table exists
$tablelist = $connect->MetaTables() or safe_die ("Error getting tokens<br />".$connect->ErrorMsg());
foreach ($tablelist as $tbl)
{
	if (db_quote_id($tbl) == db_table_name('survey_'.$surveyid)) $resultsexist = 1;
}

if (!isset($resultsexist)) die("Your results table is missing!");

$sumquery5 = "SELECT b.* FROM {$dbprefix}surveys AS a INNER JOIN {$dbprefix}surveys_rights AS b ON a.sid = b.sid WHERE a.sid=$surveyid AND b.uid = ".$_SESSION['loginID']; //Getting rights for this survey and user
$sumresult5 = db_execute_assoc($sumquery5);
$sumrows5 = $sumresult5->FetchRow();

require_once(dirname(__FILE__).'/sessioncontrol.php');

// Set language for questions and labels to base language of this survey

if (isset($browselang) && $browselang!='')
{
   $_SESSION['browselang']=$browselang; 
   $language=$_SESSION['browselang'];
}
elseif (isset($_SESSION['browselang']))
{
   $language=$_SESSION['browselang'];
}
else
{    
    $language = GetBaseLanguageFromSurveyID($surveyid);
}

$surveyoptions = browsemenubar($clang->gT("Browse Responses"));
$browseoutput = "<table><tr><td></td></tr></table>\n"
."<table class='menubar'>\n";

if (!$database_exists) //DATABASE DOESN'T EXIST OR CAN'T CONNECT
{
	$browseoutput .= "\t<tr ><td colspan='2' height='4'><strong>"
	. $clang->gT("Browse Responses")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n"
	."<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n"
	. $clang->gT("The defined LimeSurvey database does not exist")."<br />\n"
	. $clang->gT("Either your selected database has not yet been created or there is a problem accessing it.")."<br /><br />\n"
	."<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\" /><br />\n"
	."</td></tr></table>\n"
	."</body>\n</html>";
	return;
}
if (!$surveyid && !$subaction) //NO SID OR ACTION PROVIDED
{
	$browseoutput .= "\t<tr ><td colspan='2' height='4'><strong>"
	. $clang->gT("Browse Responses")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n"
	."<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n"
	. $clang->gT("You have not selected a survey to browse.")."<br /><br />\n"
	."<input type='submit' value='"
	. $clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\" /><br />\n"
	."</td></tr></table>\n";
	return;
}

//CHECK IF SURVEY IS ACTIVATED AND EXISTS
$actquery = "SELECT * FROM ".db_table_name('surveys')." as a inner join ".db_table_name('surveys_languagesettings')." as b on (b.surveyls_survey_id=a.sid and b.surveyls_language=a.language) WHERE a.sid=$surveyid";

$actresult = db_execute_assoc($actquery);
$actcount = $actresult->RecordCount();
if ($actcount > 0)
{
	while ($actrow = $actresult->FetchRow())
	{
		$surveytable = db_table_name("survey_".$actrow['sid']);
		/*
		 * DO NEVER EVER PUT VARIABLES AND FUNCTIONS WHICH GIVE BACK DIFFERENT QUOTES 
		 * IN DOUBLE QUOTED(' and " and \" is used) JAVASCRIPT/HTML CODE!!! (except for: you know what you are doing)
		 * 
		 * Used for deleting a record, fix quote bugs..
		 */
		$surveytableNq = db_table_name_nq("survey_".$surveyid);
		
		$surveyname = "{$actrow['surveyls_title']}";
		if ($actrow['active'] == "N") //SURVEY IS NOT ACTIVE YET
		{
			$browseoutput .= "\t<tr><td colspan='2' height='4'><strong>"
			. $clang->gT("Browse Responses").":</strong> $surveyname</td></tr>\n"
			."\t<tr><td align='center'>\n"
			."<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n"
			. $clang->gT("This survey has not been activated. There are no results to browse.")."<br /><br />\n"
			."<input type='submit' value='"
			. $clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?sid=$surveyid', '_top')\" /><br />\n"
			."</td></tr></table>\n"
			."</body>\n</html>";
			return;
		}
	}
}
else //SURVEY MATCHING $surveyid DOESN'T EXIST
{
	$browseoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	. $clang->gT("Browse Responses")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n"
	."<strong><font color='red'>".$clang->gT("Error")."</font></strong><br />\n"
	. $clang->gT("There is no matching survey.")." ($surveyid)<br /><br />\n"
	."<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\" /><br />\n"
	."</td></tr></table>\n"
	."</body>\n</html>";
	return;
}

//OK. IF WE GOT THIS FAR, THEN THE SURVEY EXISTS AND IT IS ACTIVE, SO LETS GET TO WORK.
$qulanguage = GetBaseLanguageFromSurveyID($surveyid);
if ($subaction == "id") // Looking at a SINGLE entry
{
	//SHOW HEADER
	if (!isset($_POST['sql']) || !$_POST['sql']) {$browseoutput .= '<tr><td>'.$surveyoptions;} // Don't show options if coming from tokens script
	$browseoutput .="<table><tr><td></td></tr></table>\n";
	//FIRST LETS GET THE NAMES OF THE QUESTIONS AND MATCH THEM TO THE FIELD NAMES FOR THE DATABASE
	$fnquery = "SELECT * FROM ".db_table_name("questions").", ".db_table_name("groups").", ".db_table_name("surveys")."
	WHERE ".db_table_name("questions").".gid=".db_table_name("groups").".gid AND ".db_table_name("groups").".sid=".db_table_name("surveys").".sid
	AND ".db_table_name("questions").".sid='$surveyid' AND
	".db_table_name("questions").".language='{$language}' AND ".db_table_name("groups").".language='{$language}' ORDER BY ".db_table_name("groups").".group_order, ".db_table_name("questions").".title";
	$fnresult = db_execute_assoc($fnquery);
	$fncount = 0;

	$fnrows = array(); //Create an empty array in case fetch_array does not return any rows
	while ($fnrow = $fnresult->FetchRow()) {++$fncount; $fnrows[] = $fnrow; $private = $fnrow['private']; $datestamp=$fnrow['datestamp']; $ipaddr=$fnrow['ipaddr']; $refurl=$fnrow['refurl'];} // Get table output into array

	// Perform a case insensitive natural sort on group name then question title of a multidimensional array
	usort($fnrows, 'CompareGroupThenTitle');

	$fnames[] = array("id", "id", "id");
	if ($private == "N") //add token to top ofl ist is survey is not private
	{
		$fnames[] = array("token", "token", $clang->gT("Token ID"));
	}

	$fnames[] = array("completed", "Completed", $clang->gT("Completed"), "0");

	if ($datestamp == "Y") //add datetime to list if survey is datestamped
	{
		// submitdate for not-datestamped surveys is always 1980/01/01
		// so only display it when datestamped
		$fnames[] = array("startdate", "startdate", $clang->gT("Date Started"));
		$fnames[] = array("datestamp", "datestamp", $clang->gT("Date Last Action"));
		$fnames[] = array("submitdate", "submitdate", $clang->gT("Date Submitted"));
	}
	if ($ipaddr == "Y") //add ipaddr to list if survey should save submitters IP address
	{
		$fnames[] = array("ipaddr", "ipaddr", $clang->gT("IP Address"));
	}
	if ($refurl == "Y") //add refer_URL  to list if survey should save referring URL
	{
		$fnames[] = array("refurl", "refurl", $clang->gT("Referring URL"));
	}
	foreach ($fnrows as $fnrow)
	{
		$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}";
		$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}";
		$fquestion = $fnrow['question'];
		if ($fnrow['type'] == "Q" || $fnrow['type'] == "M" ||
		$fnrow['type'] == "A" || $fnrow['type'] == "B" ||
		$fnrow['type'] == "C" || $fnrow['type'] == "E" ||
		$fnrow['type'] == "F" || $fnrow['type'] == "H" ||
		$fnrow['type'] == "J" || $fnrow['type'] == "K" ||
		$fnrow['type'] == "P" || $fnrow['type'] == "^")
		{
			$fnrquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$fnrow['qid']} AND	language='{$language}' ORDER BY sortorder, answer";
			$fnrresult = db_execute_assoc($fnrquery);
			while ($fnrrow = $fnrresult->FetchRow())
			{
				$fnames[] = array("$field{$fnrrow['code']}", "$ftitle ({$fnrrow['code']})", "{$fnrow['question']} ({$fnrrow['answer']})");
				if ($fnrow['type'] == "P") {$fnames[] = array("$field{$fnrrow['code']}"."comment", "$ftitle"."comment", "{$fnrow['question']} (".$clang->gT("Comment").")");}
			}
			if ($fnrow['other'] == "Y" and ($fnrow['type']=="!" or $fnrow['type']=="L" or $fnrow['type']=="M" or $fnrow['type']=="P" || $fnrow['type'] == "Z" || $fnrow['type'] == "W"))
			{
				$fnames[] = array("$field"."other", "$ftitle"."other", "{$fnrow['question']}(".$clang->gT("Other").")");
				if ($fnrow['type'] == "P") {$fnames[] = array("$field{$fnrrow['code']}"."othercomment", "$ftitle"."othercomment", "{$fnrow['question']} (".$clang->gT("Other Comment").")");}
			}
		}
		elseif ($fnrow['type'] == ":" || $fnrow['type'] == ";")
		{
		   $fnrquery = "SELECT *
		                FROM ".db_table_name('answers')." 
					    WHERE qid={$fnrow['qid']}
						AND language='{$language}' 
						ORDER BY sortorder, answer";
			$fnrresult = db_execute_assoc($fnrquery);
			$fnr2query = "SELECT *
			              FROM ".db_table_name('labels')."
			              WHERE lid={$fnrow['lid']}
			              AND language = '{$language}'
			              ORDER BY sortorder, title";
			$fnr2result = db_execute_assoc($fnr2query);
			while( $fnr2row = $fnr2result->FetchRow())
			{
			  $lset[]=$fnr2row;
			}
			while ($fnrrow = $fnrresult->FetchRow())
			{
			    foreach($lset as $ls)
			    {
				    $fnames[] = array("$field{$fnrrow['code']}_{$ls['code']}", "$ftitle ({$fnrrow['code']})", "{$fnrow['question']} ({$fnrrow['answer']}: {$ls['title']})");
                }
			}
		}
		elseif ($fnrow['type'] == "R")
		{
			$fnrquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$fnrow['qid']} AND
			language='{$language}'
			ORDER BY sortorder, answer";
			$fnrresult = $connect->Execute($fnrquery);
			$fnrcount = $fnrresult->RecordCount();
			for ($i=1; $i<=$fnrcount; $i++)
			{
				$fnames[] = array("$field$i", "$ftitle ($i)", "{$fnrow['question']} ($i)");
			}
		}
		elseif ($fnrow['type'] == "O")
		{
			$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}");
			$field2 = $field."comment";
			$ftitle2 = $ftitle."[Comment]";
			$longtitle = "{$fnrow['question']}<br />[Comment]";
			$fnames[] = array("$field2", "$ftitle2", "$longtitle");
		}
		elseif ($fnrow['type'] == "1")	// multi scale	
		{
			$i2query = "SELECT ".db_table_name("answers").".*, ".db_table_name("questions").".other FROM ".db_table_name("answers").", ".db_table_name("questions")."
			WHERE ".db_table_name("answers").".qid=".db_table_name("questions").".qid AND
			".db_table_name("answers").".language='{$language}' AND ".db_table_name("questions").".language='{$language}' AND
			".db_table_name("questions").".qid={$fnrow['qid']} AND ".db_table_name("questions").".sid=$surveyid
			ORDER BY ".db_table_name("answers").".sortorder, ".db_table_name("answers").".answer";
			$i2result = db_execute_assoc($i2query);
			$otherexists = "";
			while ($i2row = $i2result->FetchRow())
			{
				// first scale
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$i2row['code']}#0";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}Opt{$i2row['code']}";
				if ($i2row['other'] == "Y") {$otherexists = "Y";}
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[{$i2row['answer']}]<br>[".$clang->gT("1. scale")."]", "{$fnrow['gid']}");
				// second scale
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$i2row['code']}#1";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}Opt{$i2row['code']}";
				if ($i2row['other'] == "Y") {$otherexists = "Y";}
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[{$i2row['answer']}]<br>[".$clang->gT("2. scale")."]", "{$fnrow['gid']}");
			}
			if ($otherexists == "Y")
			{
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}"."other";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}OptOther";
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[Other]", "{$fnrow['gid']}");
			}
		}
		
		else
		{
			$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}");
			if (($fnrow['type'] == "L" || $fnrow['type'] == "!" || $fnrow['type'] == "W" || $fnrow['type'] == "Z") && $fnrow['other'] == "Y")
			{
				$fnames[] = array("$field"."other", "$ftitle"."other", "{$fnrow['question']}(".$clang->gT("Other").")");
			}
		}
	}
	$nfncount = count($fnames)-1;
	//SHOW INDIVIDUAL RECORD
	$idquery = "SELECT *, CASE WHEN submitdate IS NULL THEN 'N' ELSE 'Y' END as completed FROM $surveytable WHERE ";
	if (incompleteAnsFilterstate() == "inc") {$idquery .= "(submitdate = ".$connect->DBDate('1980-01-01'). " OR submitdate IS NULL) AND ";}
	elseif (incompleteAnsFilterstate() == "filter") {$idquery .= "submitdate >= ".$connect->DBDate('1980-01-01'). " AND ";}
	if ($id<1) {$id=1;}
	if (isset($_POST['sql']) && $_POST['sql'])
	{
		if (get_magic_quotes_gpc()) {$idquery .= stripslashes($_POST['sql']);}
		else {$idquery .= "{$_POST['sql']}";}
	}
	else {$idquery .= "id=$id";}
	$idresult = db_execute_assoc($idquery) or safe_die ("Couldn't get entry<br />\n$idquery<br />\n".$connect->ErrorMsg());
	while ($idrow = $idresult->FetchRow()) {$id=$idrow['id']; $rlangauge=$idrow['startlanguage'];}
	$next=$id+1;
	$last=$id-1;
	$browseoutput .= "<table width='99%' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n"
	."\t<tr>\n"
	."\t\t<td colspan='2' height='4'><strong>"
	. $clang->gT("View Response").":</strong> $id</td></tr>\n"
	."\t<tr><td colspan='2'>\n"
	."\t\t\t<img src='$imagefiles/blank.gif' width='31' height='20' border='0' hspace='0' align='left' alt='' />\n"
	."\t\t\t<img src='$imagefiles/seperator.gif' border='0' hspace='0' align='left' alt='' />\n";
	if (isset($rlangauge))
    {
            $browseoutput .="\t\t\t<a href='$scriptname?action=dataentry&amp;subaction=edit&amp;id=$id&amp;sid=$surveyid&amp;language=$rlangauge&amp;surveytable=$surveytable'" .
			"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Edit this entry", "js")."')\">" .
			"<img align='left' src='$imagefiles/edit.png' title='' alt='' /></a>\n";
	}
	if ($sumrows5['delete_survey'])
	{
//		$browseoutput .=  "\t\t\t<a href='$scriptname?action=dataentry&amp;subaction=delete&amp;id=$id&amp;sid=$surveyid&amp;surveytable=$surveytable'" .
//			"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Delete this entry", "js")."')\">"
//		."<img align='left' hspace='0' border='0' src='$imagefiles/delete.png' alt='' title='' onclick=\"return confirm('".$clang->gT("Are you sure you want to delete this entry?","js")."')\" /></a>\n";
		
		$browseoutput .=  "\t\t\t<a href='#'" .
			"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Delete this entry", "js")."')\">"
		."<img align='left' hspace='0' border='0' src='$imagefiles/delete.png' alt='' title='' onclick=\"if (confirm('".$clang->gT("Are you sure you want to delete this entry?","js")."')) {".get2post($scriptname.'?action=dataentry&amp;subaction=delete&amp;id='.$id.'&amp;sid='.$surveyid.'&amp;surveytable='.$surveytableNq)."}\" /></a>\n";
		//echo get2post($scriptname.'?action=dataentry&amp;subaction=delete&amp;id=$id&amp;sid='.$surveyid.'&amp;surveytable='.$surveytable);
	}
	 $browseoutput .= "\t\t\t<a href='$scriptname?action=exportresults&amp;sid=$surveyid&amp;id=$id'" .
		"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Export this Response", "js")."')\">" .
		"<img name='ExportAnswer' src='$imagefiles/export.png' title='' alt='". $clang->gT("Export this Response")."'align='left' /></a>\n"
	."\t\t\t<img src='$imagefiles/seperator.gif' border='0' hspace='0' align='left' alt='' />\n"
	."\t\t\t<img src='$imagefiles/blank.gif' width='20' height='20' border='0' hspace='0' align='left' alt='' />\n"
	."\t\t\t<a href='$scriptname?action=browse&amp;subaction=id&amp;id=$last&amp;sid=$surveyid&amp;surveytable=$surveytable'" .
			"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show previous...", "js")."')\">".
		"<img name='DataBack' align='left' src='$imagefiles/databack.png' alt='' /></a>\n"
	."\t\t\t<img src='$imagefiles/blank.gif' width='13' height='20' border='0' hspace='0' align='left' alt='' />\n"
	."\t\t\t<a href='$scriptname?action=browse&amp;subaction=id&amp;id=$next&amp;sid=$surveyid&amp;surveytable=$surveytable'" .
			"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show next...", "js")."')\">" .
		"<img name='DataForward' align='left' src='$imagefiles/dataforward.png' alt='' /></a>\n"
	."\t\t</td>\n"
	."\t</tr>\n"
	."\t<tr><td colspan='2' bgcolor='#CCCCCC' height='1'></td></tr>\n";
	$idresult = db_execute_assoc($idquery) or safe_die ("Couldn't get entry<br />$idquery<br />".$connect->ErrorMsg());
	while ($idrow = $idresult->FetchRow())
	{
		$i=0;
		for ($i; $i<$nfncount+1; $i++)
		{
			$browseoutput .= "\t<tr>\n"
			."\t\t<td bgcolor='#EFEFEF' valign='top' align='right' width='33%' style='padding-right: 5px'>"
			.strip_tags(strip_javascript($fnames[$i][2]))."</font></td>\n"
			."\t\t<td valign='top' align='left' style='padding-left: 5px'>"
			.htmlspecialchars(getextendedanswer($fnames[$i][0], $idrow[$fnames[$i][0]]), ENT_QUOTES)
			."</font></td>\n"
			."\t</tr>\n"
			."\t<tr><td colspan='2' bgcolor='#CCCCCC' height='1'></td></tr>\n";
		}
	}
	$browseoutput .= "</table>\n"
	."<table width='99%' align='center'>\n"
	."\t<tr>\n"
	."\t\t<td bgcolor='#EEEEEE' align='center'>\n";
//	if (isset($_POST['sql']) && $_POST['sql']) {$browseoutput .= "\t\t\t<input type='submit' value='Close Window' onclick=\"window.close();\" />\n";}
	$browseoutput .= "\t\t</td>\n"
	."\t</tr>\n"
	."</table>\n";
}

elseif ($subaction == "all")
{

	if (!isset($_POST['sql']))
	{$browseoutput .= '<tr><td>'.$surveyoptions;} //don't show options when called from another script with a filter on
	else
	{
        $browseoutput .= "\t<tr><td colspan='2' height='4'><strong>".$clang->gT("Browse Responses").":</strong> $surveyname</td></tr>\n"
		."\n<tr><td><table width='100%' align='center' border='0' bgcolor='#EFEFEF'>\n"
		."\t<tr>\n"
		."\t\t<td align='center'>\n"
		."\t\t\t".$clang->gT("Showing Filtered Results")."<br />\n"
		."\t\t\t&nbsp;[<a href=\"javascript:window.close()\">".$clang->gT("Close")."</a>]"
		."\t\t</font></td>\n"
		."\t</tr>\n"
		."</table></td></tr>\n";

	}
	$browseoutput .= "</table>\n";
	//FIRST LETS GET THE NAMES OF THE QUESTIONS AND MATCH THEM TO THE FIELD NAMES FOR THE DATABASE
	$fnquery = "SELECT * FROM ".db_table_name("questions").", ".db_table_name("groups").",
	".db_table_name("surveys")." WHERE ".db_table_name("questions").".gid=".db_table_name("groups").".gid AND
	".db_table_name("questions").".language='{$language}' AND ".db_table_name("groups").".language='{$language}' AND
	".db_table_name("groups").".sid=".db_table_name("surveys").".sid AND ".db_table_name("questions").".sid='$surveyid' ORDER BY ".db_table_name("groups").".group_order";
	$fnresult = db_execute_assoc($fnquery);
	$fncount = 0;
	$fnrows = array(); //Create an empty array in case FetchRow does not return any rows
	while ($fnrow = $fnresult->FetchRow())
	{
		++$fncount;
		$fnrows[] = $fnrow;
		$private = $fnrow['private'];
		$datestamp=$fnrow['datestamp'];
		$ipaddr=$fnrow['ipaddr'];
		$refurl=$fnrow['refurl'];
	} // Get table output into array

	// Perform a case insensitive natural sort on group name then question title of a multidimensional array
	usort($fnrows, 'CompareGroupThenTitle');

	if ($private == "N") //Add token to list
	{
		$fnames[] = array("token", $clang->gT("Token"), $clang->gT("Token ID"), "0");
	}

	$fnames[] = array("completed", "Completed", $clang->gT("Completed"), "0");

	if ($datestamp == "Y") //Add datestamp
	{
		// submitdate for not-datestamped surveys is always 1980/01/01
		// so only display it when datestamped
		$fnames[] = array("startdate", "startdate", $clang->gT("Date Started"), "0");
		$fnames[] = array("datestamp", "Datestamp", $clang->gT("Date Last Action"), "0");
		$fnames[] = array("submitdate", "submitdate", $clang->gT("Date Submitted"), "0");

		
	}
	if ($ipaddr == "Y") // Add IP Address
	{
		$fnames[] = array("ipaddr", "IPAddress", $clang->gT("IP Address"), "0");
	}
	if ($refurl == "Y") // refurl
	{
		$fnames[] = array("refurl", "refurl", $clang->gT("Referring URL"), "0");
	}
	foreach ($fnrows as $fnrow)
	{
		if ($fnrow['type'] != "Q" && $fnrow['type'] != "M" && $fnrow['type'] != "A" &&
		$fnrow['type'] != "B" && $fnrow['type'] != "C" && $fnrow['type'] != "E" &&
		$fnrow['type'] != "F" && $fnrow['type'] != "H" && $fnrow['type'] != "P" &&
		$fnrow['type'] != "J" && $fnrow['type'] != "K" && $fnrow['type'] != "1" &&  
		$fnrow['type'] != "O" && $fnrow['type'] != "R" && $fnrow['type'] != "^" && 
		$fnrow['type'] != ":" && $fnrow['type'] != ";")
		{
			$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}";
			$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}";
			$fquestion = $fnrow['question'];
			$fnames[] = array("$field", "$ftitle", "$fquestion", "{$fnrow['gid']}");
			if (($fnrow['type'] == "L" || $fnrow['type'] == "!" || $fnrow['type'] == "W" || $fnrow['type'] == "Z") && $fnrow['other'] == "Y")
			{
				$fnames[] = array("$field"."other", "$ftitle"."other", "{$fnrow['question']}(".$clang->gT("Other").")", "{$fnrow['gid']}");
			}

		}
		elseif ($fnrow['type'] == "O")
		{
			$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}";
			$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}";
			$fquestion = $fnrow['question'];
			$fnames[] = array("$field", "$ftitle", "$fquestion", "{$fnrow['gid']}");
			$field .= "comment";
			$ftitle .= "[comment]";
			$fquestion .= " (comment)";
			$fnames[] = array("$field", "$ftitle", "$fquestion", "{$fnrow['gid']}");
		}
		elseif ($fnrow['type'] == "1")	// multi scale	
		{
			$i2query = "SELECT ".db_table_name("answers").".*, ".db_table_name("questions").".other FROM ".db_table_name("answers").", ".db_table_name("questions")."
			WHERE ".db_table_name("answers").".qid=".db_table_name("questions").".qid AND
			".db_table_name("answers").".language='{$language}' AND ".db_table_name("questions").".language='{$language}' AND
			".db_table_name("questions").".qid={$fnrow['qid']} AND ".db_table_name("questions").".sid=$surveyid
			ORDER BY ".db_table_name("answers").".sortorder, ".db_table_name("answers").".answer";
			$i2result = db_execute_assoc($i2query);
			$otherexists = "";
			while ($i2row = $i2result->FetchRow())
			{
				// first scale
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$i2row['code']}#0";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}Opt{$i2row['code']}";
				if ($i2row['other'] == "Y") {$otherexists = "Y";}
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[{$i2row['answer']}]<br>[".$clang->gT("1. scale")."]", "{$fnrow['gid']}");
				// second scale
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$i2row['code']}#1";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}Opt{$i2row['code']}";
				if ($i2row['other'] == "Y") {$otherexists = "Y";}
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[{$i2row['answer']}]<br>[".$clang->gT("2. scale")."]", "{$fnrow['gid']}");
			}
			if ($otherexists == "Y")
			{
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}"."other";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}OptOther";
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[Other]", "{$fnrow['gid']}");
				if ($fnrow['type'] == "P")
				{
					$fnames[] = array("$field"."comment", "$ftitle"."Comment", "{$fnrow['question']}<br />\n[Other]<br />\n[Comment]", "{$fnrow['gid']}");
				}
			}
		}
		elseif ($fnrow['type'] == "R")
		{
			$i2query = "SELECT ".db_table_name("answers").".*, ".db_table_name("questions").".other FROM
			".db_table_name("answers").", ".db_table_name("questions")."
			WHERE ".db_table_name("answers").".qid=".db_table_name("questions").".qid AND
			".db_table_name("answers").".language='{$language}' AND ".db_table_name("questions").".language='{$language}'
			AND ".db_table_name("questions").".qid={$fnrow['qid']} AND ".db_table_name("questions").".sid=$surveyid
			ORDER BY ".db_table_name("answers").".sortorder, ".db_table_name("answers").".answer";
			$i2result = $connect->Execute($i2query);
			$i2count = $i2result->RecordCount();
			for ($i=1; $i<=$i2count; $i++)
			{
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}$i";
				$ftitle = "Grp{$fnrow['qid']}Qst{$fnrow['title']}Opt$i";
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[$i]", "{$fnrow['gid']}");
			}
		}	
		elseif ($fnrow['type'] == ":" || $fnrow['type'] == ";")
		{
			$i2query = "SELECT ".db_table_name("answers").".*, ".db_table_name("questions").".other FROM ".db_table_name("answers").", ".db_table_name("questions")."
			WHERE ".db_table_name("answers").".qid=".db_table_name("questions").".qid AND
			".db_table_name("answers").".language='{$language}' AND ".db_table_name("questions").".language='{$language}' AND
			".db_table_name("questions").".qid={$fnrow['qid']} AND ".db_table_name("questions").".sid=$surveyid
			ORDER BY ".db_table_name("answers").".sortorder, ".db_table_name("answers").".answer";
			$i2result = db_execute_assoc($i2query);
			$ab2query = "SELECT ".db_table_name('labels').".*
			             FROM ".db_table_name('questions').", ".db_table_name('labels')."
			             WHERE sid=$surveyid 
						 AND ".db_table_name('labels').".lid=".db_table_name('questions').".lid
			             AND ".db_table_name('questions').".language='".$language."'
			             AND ".db_table_name('labels').".language='".$language."'
			             AND ".db_table_name('questions').".qid=".$fnrow['qid']."
			             ORDER BY ".db_table_name('labels').".sortorder, ".db_table_name('labels').".title";
			$ab2result=db_execute_assoc($ab2query) or die("Couldn't get list of labels in createFieldMap function (case :)<br />$ab2query<br />".htmlspecialchars($connection->ErrorMsg()));
			while($ab2row=$ab2result->FetchRow())
			{
			    $lset[]=$ab2row;
			}
			while ($i2row = $i2result->FetchRow())
			{
			    foreach($lset as $ls)
			    {
				    $field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$i2row['code']}_{$ls['code']}";
				    $ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}Item{$i2row['code']}Label{$ls['code']}";
				    $fnames[]=array($field, $ftitle, "{$fnrow['question']}<br />\n[{$i2row['answer']}]<br />[{$ls['title']}]", $fnrow['qid']);
			    }
			}
		}
		else
		{
			$i2query = "SELECT ".db_table_name("answers").".*, ".db_table_name("questions").".other FROM ".db_table_name("answers").", ".db_table_name("questions")."
			WHERE ".db_table_name("answers").".qid=".db_table_name("questions").".qid AND
			".db_table_name("answers").".language='{$language}' AND ".db_table_name("questions").".language='{$language}' AND
			".db_table_name("questions").".qid={$fnrow['qid']} AND ".db_table_name("questions").".sid=$surveyid
			ORDER BY ".db_table_name("answers").".sortorder, ".db_table_name("answers").".answer";
			$i2result = db_execute_assoc($i2query);
			$otherexists = "";
			while ($i2row = $i2result->FetchRow())
			{
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$i2row['code']}";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}Opt{$i2row['code']}";
				if ($i2row['other'] == "Y") {$otherexists = "Y";}
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[{$i2row['answer']}]", "{$fnrow['gid']}");
				if ($fnrow['type'] == "P") {$fnames[] = array("$field"."comment", "$ftitle", "{$fnrow['question']}<br />\n[{$i2row['answer']}]<br />\n[Comment]", "{$fnrow['gid']}");}
			}
			if ($otherexists == "Y")
			{
				$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}"."other";
				$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}OptOther";
				$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}<br />\n[Other]", "{$fnrow['gid']}");
				if ($fnrow['type'] == "P")
				{
					$fnames[] = array("$field"."comment", "$ftitle"."Comment", "{$fnrow['question']}<br />\n[Other]<br />\n[Comment]", "{$fnrow['gid']}");
				}
			}
		}
	}
	$fncount = count($fnames);

	//NOW LETS CREATE A TABLE WITH THOSE HEADINGS
	if ($fncount < 10) {$cellwidth = "10%";} else {$cellwidth = "100";}
	$tableheader = "<!-- DATA TABLE -->";
	if ($fncount < 10) {$tableheader .= "<table class='browsetable' width='100%' border='0' cellpadding='0' cellspacing='1' style='border: 1px solid #555555'>\n";}
	else {$tableheader .= "<table class='browsetable' border='0' cellpadding='1' cellspacing='1' style='border: 1px solid #555555'>\n";}
	$tableheader .= "\t<tr valign='top'>\n"
	. "\t\t<td  class='evenrow' width='$cellwidth'><strong>id</strong></td>\n";
	foreach ($fnames as $fn)
	{
		if (!isset($currentgroup))  {$currentgroup = $fn[3]; $gbc = "oddrow";}
		if ($currentgroup != $fn[3])
		{
			$currentgroup = $fn[3];
			if ($gbc == "oddrow") {$gbc = "evenrow";}
			else {$gbc = "oddrow";}
		}
		$tableheader .= "\t\t<td class='$gbc' width='$cellwidth'><strong>"
		. strip_tags(strip_javascript("$fn[2]"))
		. "</strong></td>\n";
	}
	$tableheader .= "\t</tr>\n\n";

	$start=returnglobal('start');
	$limit=returnglobal('limit');
	if (!isset($limit) || $limit== '') {$limit = 50;}
	if (!isset($start) || $start =='') {$start = 0;}

	//LETS COUNT THE DATA
	$dtquery = "SELECT count(*) FROM $surveytable";
	if (incompleteAnsFilterstate() == "inc") {$dtquery .= "WHERE submitdate IS NULL ";}
	elseif (incompleteAnsFilterstate() == "filter") {$dtquery .= " WHERE submitdate is not null ";}
	$dtresult=db_execute_num($dtquery);
	while ($dtrow=$dtresult->FetchRow()) {$dtcount=$dtrow[0];}

	if ($limit > $dtcount) {$limit=$dtcount;}

	//NOW LETS SHOW THE DATA
	if (isset($_POST['sql']))
	{
		if ($_POST['sql'] == "NULL")
		{
			$dtquery = "SELECT *, CASE WHEN submitdate IS NULL THEN 'N' ELSE 'Y' END as completed FROM $surveytable ";
	if (incompleteAnsFilterstate() == "inc") {$dtquery .= "WHERE submitdate is null";}
	elseif (incompleteAnsFilterstate() == "filter") {$dtquery .= " WHERE submitdate is not null ";}
			$dtquery .= " ORDER BY id";
		}
		else
		{
            $dtquery = "SELECT *, CASE WHEN submitdate IS NULL THEN 'N' ELSE 'Y' END as completed FROM $surveytable WHERE ";
	        if (incompleteAnsFilterstate() == "inc") {
			    $dtquery .= "submitdate is null ";
			    if (stripcslashes($_POST['sql']) !== "") { $dtquery .= " AND "; }
			}
	        elseif (incompleteAnsFilterstate() == "filter") {
                $dtquery .= " submitdate is not null ";
                if (stripcslashes($_POST['sql']) !== "") { $dtquery .= " AND "; }
            }
            if (stripcslashes($_POST['sql']) !== "") { $dtquery .= stripcslashes($_POST['sql'])." "; }
            $dtquery .= " ORDER BY id";
		}
	}
	else
	{
		$dtquery = "SELECT *, CASE WHEN submitdate IS NULL THEN 'N' ELSE 'Y' END as completed FROM $surveytable ";
		if (incompleteAnsFilterstate() == "inc") {$dtquery .= " WHERE submitdate is null ";}
		elseif (incompleteAnsFilterstate() == "filter") {$dtquery .= " WHERE submitdate is not null ";}
		$dtquery .= " ORDER BY id";
	}
	if ($order == "desc") {$dtquery .= " DESC";}

	if (isset($limit))
	{
		if (!isset($start)) {$start = 0;}
		$dtresult = db_select_limit_assoc($dtquery, $limit, $start) or safe_die("Couldn't get surveys<br />$dtquery<br />".$connect->ErrorMsg());
	}
	else
	{
		$dtresult = db_execute_assoc($dtquery) or safe_die("Couldn't get surveys<br />$dtquery<br />".$connect->ErrorMsg());
	}
	$dtcount2 = $dtresult->RecordCount();
	$cells = $fncount+1;


	//CONTROL MENUBAR
	$last=$start-$limit;
	$next=$start+$limit;
	$end=$dtcount-$limit;
	if ($end < 0) {$end=0;}
	if ($last <0) {$last=0;}
	if ($next >= $dtcount) {$next=$dtcount-$limit;}
	if ($end < 0) {$end=0;}

	$browseoutput .= "<table><tr><td></td></tr></table>\n"
	."<table class='menubar'>\n"
	."\t<tr ><td colspan='2' height='4'><strong>"
	. $clang->gT("Data View Control").":</strong></td></tr>\n";
	if (!isset($_POST['sql']))
	{
		$browseoutput .= "\t<tr><td align='left' width='200'>\n"
			."\t\t\t<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=0&amp;limit=$limit' " .
				"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show start..", "js")."');return false\">" .
						"<img name='DataBegin' align='left' src='$imagefiles/databegin.png' title='' /></a>\n"
		."\t\t\t<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=$last&amp;limit=$limit' " .
				"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show previous...", "js")."');return false\">" .
				"<img name='DataBack' align='left'  src='$imagefiles/databack.png' title='' /></a>\n"
		."\t\t\t<img src='$imagefiles/blank.gif' width='13' height='20' border='0' hspace='0' align='left' alt='' />\n"
		."\t\t\t<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=$next&amp;limit=$limit' " .
				"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show next...", "js")."');return false\">".
				"<img name='DataForward' align='left' src='$imagefiles/dataforward.png' title='' /></a>\n"
		."\t\t\t<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=$end&amp;limit=$limit' " .
				"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show last...", "js")."');return false\">" .
				"<img name='DataEnd' align='left' src='$imagefiles/dataend.png' title='' /></a>\n"
		."\t\t\t<img src='$imagefiles/seperator.gif' border='0' hspace='0' align='left' alt='' />\n";
	} else {
		$browseoutput .= "\t<tr><td align='left'>\n";
	}

	if(incompleteAnsFilterstate() == "inc")
	{
	    $selecthide="";
	    $selectshow="";
	    $selectinc="selected='selected'";
	}
	elseif (incompleteAnsFilterstate() == "filter")
	{
		$selecthide="selected='selected'";
		$selectshow="";
		$selectinc="";
	}
	else
	{
		$selecthide="";
		$selectshow="selected='selected'";
		$selectinc="";
	}

	$browseoutput .=("\t\t</td>\n"
	."\t\t<td align='left'>\n"
	."\t\t<form action='$scriptname?action=browse' method='post'><font size='1' face='verdana'>\n"
	."\t\t\t<img src='$imagefiles/blank.gif' width='31' height='20' border='0' hspace='0' align='right' alt='' />\n"
	."\t\t\t".$clang->gT("Records Displayed:")."<input type='text' size='4' value='$dtcount2' name='limit' id='limit' />\n"
	."\t\t\t&nbsp;&nbsp; ".$clang->gT("Starting From:")."<input type='text' size='4' value='$start' name='start' id='start' />\n"
	."\t\t\t&nbsp;&nbsp; ".$clang->gT("Display:")."<select name='filterinc' onchange='javascript:document.getElementById(\"limit\").value=\"\";submit();'>\n"
	."\t\t\t\t<option value='filter' $selecthide>".$clang->gT("Completed Records Only")."</option>\n"
	."\t\t\t\t<option value='show' $selectshow>".$clang->gT("All Records")."</option>\n"
	."\t\t\t\t<option value='incomplete' $selectinc>".$clang->gT("Incomplete Records Only")."</option>\n"
	."\t\t\t</select>\n"
	."\t\t\t&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' value='".$clang->gT("Show")."' />\n"
	."\t\t</font>\n"
	."\t\t<input type='hidden' name='sid' value='$surveyid' />\n"
	."\t\t<input type='hidden' name='action' value='browse' />\n"
	."\t\t<input type='hidden' name='subaction' value='all' />\n");
	if (isset($_POST['sql']))
	{
		$browseoutput .= "\t\t<input type='hidden' name='sql' value='".html_escape($_POST['sql'])."' />\n";
	}
	$browseoutput .= 	 "\t\t</form></td>\n"
	."\t</tr>\n"
	."</table>\n"
	."<table><tr><td></td></tr></table>\n";

	$browseoutput .= $tableheader;

	while ($dtrow = $dtresult->FetchRow())
	{
		if (!isset($bgcc)) {$bgcc="evenrow";}
		else
		{
			if ($bgcc == "evenrow") {$bgcc = "oddrow";}
			else {$bgcc = "evenrow";}
		}
		$browseoutput .= "\t<tr class='$bgcc' valign='top'>\n"
		."\t\t<td align='center'><font face='verdana' size='1'>\n"
		."\t\t\t<a href='$scriptname?action=browse&amp;sid=$surveyid&amp;subaction=id&amp;id={$dtrow['id']}' title='".$clang->gT("View This Record")."'>"
		."{$dtrow['id']}</a></font></td>\n";

		$i = 0;
		if ($private == "N" && $dtrow['token'])
		{
			$SQL = "Select * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token=?";
			if ( db_tables_exist(db_table_name_nq('tokens_'.$surveyid)) &&
				$SQLResult = db_execute_assoc($SQL, $dtrow['token']))
			{
				$TokenRow = $SQLResult->FetchRow();
			}
			$browseoutput .= "\t\t<td align='center'><font size='1'>\n";
			if (isset($TokenRow) && $TokenRow)
			{
				$browseoutput .= "\t\t<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=edit&amp;tid={$TokenRow['tid']}' title='".$clang->gT("Edit this token")."'>";
			}
			$browseoutput .= "{$dtrow['token']}";
			if (isset($TokenRow) && $TokenRow)
			{
				$browseoutput .= "</a>\n";
			}
			$i++;
		}

		for ($i; $i<$fncount; $i++)
		{
            $browsedatafield=htmlspecialchars($dtrow[$fnames[$i][0]]);
            if (trim($browsedatafield=='')) $browsedatafield='&nbsp;';
			$browseoutput .= "\t\t<td align='center'><font size='1' face='verdana'>$browsedatafield</font></td>\n";
		}
		$browseoutput .= "\t</tr>\n";
	}
	$browseoutput .= "</table>\n<br />\n";
}
else
{
	$browseoutput .= '<tr><td>'.$surveyoptions;
	$num_total_answers=0;
	$num_completed_answers=0;
	$gnquery = "SELECT count(id) FROM $surveytable";
	$gnquery2 = "SELECT count(id) FROM $surveytable WHERE submitdate is not null";
	$gnresult = db_execute_num($gnquery);
	$gnresult2 = db_execute_num($gnquery2);

	while ($gnrow=$gnresult->FetchRow()) {$num_total_answers=$gnrow[0];}
	while ($gnrow2=$gnresult2->FetchRow()) {$num_completed_answers=$gnrow2[0];}
	$browseoutput .= "<table width='100%' border='0'>\n"
	."\t<tr><td align='center'>".sprintf($clang->gT("%d responses for this survey"), $num_total_answers)." ("
	.sprintf($clang->gT("%d full responses"), $num_completed_answers).", "
	.sprintf($clang->gT("%d responses not completely filled out"), $num_total_answers-$num_completed_answers).")"
	."\t</td></tr>\n"
	."</table></table>\n";

}

?>
