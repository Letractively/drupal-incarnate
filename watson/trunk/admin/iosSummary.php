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
* $Id: exportresults.php 6640 2009-04-14 23:24:52Z jcleeland $
*/


//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");

echo '<link rel="stylesheet" type="text/css" href="./styles/default/cnc.css" /><center>';
	
if (!isset($imagefiles)) {$imagefiles="./images";}
if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($exportstyle)) {$exportstyle=returnglobal('exportstyle');}
if (!isset($answers)) {$answers=returnglobal('answers');}
if (!isset($type)) {$type=returnglobal('type');}
if (!isset($convertyto1)) {$convertyto1=returnglobal('convertyto1');}
if (!isset($convertspacetous)) {$convertspacetous=returnglobal('convertspacetous');}

$sumquery5 = "SELECT b.* FROM {$dbprefix}surveys AS a INNER JOIN {$dbprefix}surveys_rights AS b ON a.sid = b.sid WHERE a.sid=$surveyid AND b.uid = ".$_SESSION['loginID']; //Getting rights for this survey and user
$sumresult5 = db_execute_assoc($sumquery5);
$sumrows5 = $sumresult5->FetchRow();

//print_r($sumrows5);

if ($sumrows5['export'] != "1" && $_SESSION['USER_RIGHT_SUPERADMIN'] != 1)
{
	exit;
}

include_once("login_check.php");
include_once(dirname(__FILE__)."/classes/pear/Spreadsheet/Excel/Writer.php");
include_once(dirname(__FILE__)."/classes/tcpdf/extensiontcpdf.php"); 

$surveybaselang=GetBaseLanguageFromSurveyID($surveyid);
$exportoutput="";

//HERE WE EXPORT THE ACTUAL RESULTS
$separator=",";
//Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

// Export Language is set by default to surveybaselang
// * the explang language code is used in SQL queries
// * the alang object is used to translate headers and hardcoded answers
// In the future it might be possible to 'post' the 'export language' from
// the exportresults form
$explang = $surveybaselang;
$elang=new limesurvey_lang($explang);

//STEP 1: First line is column headings

$fieldmap=createFieldMap($surveyid);



// We make the fieldmap alot more accesible by using the SGQA identifier as key 
// so we do not need ArraySearchByKey later
foreach ($fieldmap as $fieldentry)
{
    $outmap[]=$fieldentry['fieldname'];
    $outmap[$fieldentry['fieldname']]['type']= $fieldentry['type'];
    $outmap[$fieldentry['fieldname']]['sid']= $fieldentry['sid'];
    $outmap[$fieldentry['fieldname']]['gid']= $fieldentry['gid'];
    $outmap[$fieldentry['fieldname']]['qid']= $fieldentry['qid'];
    $outmap[$fieldentry['fieldname']]['aid']= $fieldentry['aid'];
    if (isset($fieldentry['lid1'])) {$outmap[$fieldentry['fieldname']]['lid1']= $fieldentry['lid1'];}
    if ($fieldentry['qid']!='')
    {
        $qq = "SELECT lid, other FROM {$dbprefix}questions WHERE qid={$fieldentry['qid']} and language='$surveybaselang'";
        $qr = db_execute_assoc($qq) or safe_die("Error selecting type and lid from questions table.<br />".$qq."<br />".$connect->ErrorMsg());
		while ($qrow = $qr->FetchRow())
        {
            $outmap[$fieldentry['fieldname']]['lid']=$qrow['lid'];
            $outmap[$fieldentry['fieldname']]['other']=$qrow['other'];        
        }
    }
 
} 


//Get the fieldnames from the survey table for column headings
$surveytable = "{$dbprefix}survey_$surveyid";
if (isset($_POST['colselect']))
{
	$selectfields="";
	foreach($_POST['colselect'] as $cs)
	{
		if ($cs != 'completed')
		{
			$selectfields.= db_quote_id($cs).", ";
		}
		else
		{
			$selectfields.= "CASE WHEN $surveytable.submitdate IS NULL THEN 'N' ELSE 'Y' END AS completed, ";
		}
	}
	$selectfields = mb_substr($selectfields, 0, strlen($selectfields)-2);
}
else
{
	$selectfields="$surveytable.*, CASE WHEN $surveytable.submitdate IS NULL THEN 'N' ELSE 'Y' END AS completed";
}
//echo "$selectfields";

$dquery = "SELECT $selectfields";
if (isset($_POST['first_name']) && $_POST['first_name']=="on")
{
	$dquery .= ", {$dbprefix}tokens_$surveyid.firstname";
}
if (isset($_POST['last_name']) && $_POST['last_name']=="on")
{
	$dquery .= ", {$dbprefix}tokens_$surveyid.lastname";
}
if (isset($_POST['email_address']) && $_POST['email_address']=="on")
{
	$dquery .= ", {$dbprefix}tokens_$surveyid.email";
}
if (isset($_POST['token']) && $_POST['token']=="on")
{
	$dquery .= ", {$dbprefix}tokens_$surveyid.token";
}
if (isset($_POST['attribute_1']) && $_POST['attribute_1']=="on")
{
	$dquery .= ", {$dbprefix}tokens_$surveyid.attribute_1";
}
if (isset($_POST['attribute_2']) && $_POST['attribute_2']=="on")
{
	$dquery .= ", {$dbprefix}tokens_$surveyid.attribute_2";
}
$dquery .= " FROM $surveytable";
if ((isset($_POST['first_name']) && $_POST['first_name']=="on")  || (isset($_POST['token']) && $_POST['token']=="on") || (isset($_POST['last_name']) && $_POST['last_name']=="on") || (isset($_POST['attribute_1']) && $_POST['attribute_1']=="on") || (isset($_POST['attribute_2']) && $_POST['attribute_2']=="on") || (isset($_POST['email_address']) && $_POST['email_address']=="on"))
{
	$dquery .= ""
	. " LEFT OUTER JOIN {$dbprefix}tokens_$surveyid"
	. " ON $surveytable.token = {$dbprefix}tokens_$surveyid.token";
}
if (incompleteAnsFilterstate() == "filter")
{
	$dquery .= "  WHERE $surveytable.submitdate is not null ";
} elseif (incompleteAnsFilterstate() == "inc")
{
    $dquery .= "  WHERE $surveytable.submitdate is null ";
}
if( isset($_GET['id'])){
	$dquery .= " and id = " . $_GET['id'];
}
$dquery .=" ORDER BY id ";


//print_r($dquery);

$dresult = db_select_limit_assoc($dquery, 1) or safe_die($clang->gT("Error")." getting results<br />$dquery<br />".$connect->ErrorMsg());
$fieldcount = $dresult->FieldCount();


$firstline="";
$faid="";


//calculate interval because the second argument at SQL "limit" 
//is the number of records not the ending point
$from_record = sanitize_int($_POST['export_from']) - 1;
$limit_interval = sanitize_int($_POST['export_to']) - sanitize_int($_POST['export_from']) + 1;


	$dquery = "SELECT $selectfields FROM $surveytable ";

	if (incompleteAnsFilterstate() == "filter")
	{
    $dquery .= "  WHERE $surveytable.submitdate is not null ";
	} elseif (incompleteAnsFilterstate() == "inc")
	{
	$dquery .= "  WHERE $surveytable.submitdate is null ";
	}
	
	if( isset($_GET['id'])){
		$dquery .= " and id = " . $_GET['id'];
	}

$dquery .= " ORDER BY $surveytable.id";


	//echo '<br><br>' . $dquery;
	//$dresult = db_execute_assoc($dquery);
	$dresult = db_select_limit_assoc($dquery, $limit_interval, $from_record);
//	echo "</center><pre>";
//	print_r($dresult);
//	echo '</pre>';
	$rowcounter=0;
	while ($drow = $dresult->FetchRow())
	{
		//print_r($drow);
		?>
		<table>
			<thead>
			<tr>
				<th><h3>Participant Code #: <? echo $drow['99757X64X154'] ?></h3></th>
				<th text-align="right"><h3>Date Completed: <? echo formatDate( $drow['submitdate'] ) ?></h3></th>
			</tr>
			</thead>
		</table>
		<br/>
	
		<br/>
		<?php 
		
		
		$impO = ($drow['99757X65X156think'] + $drow['99757X65X156feel'] + $drow['99757X65X156decid']) / 3;		
		
		?>
		<table>
			<caption>
					<b>Importance of Ought Self</b>
			</caption>
			<tbody>
			<tr class='even'>
				<th>
					<b>ImpO</b>
				</th>
				<td>
					<?php echo $impO ?>
				</td>			
			</tr>
		</table>
		<?php 
		$rowcounter++;
       	$exportoutput .= "\"".implode("\"$separator\"", str_replace("\"", "\"\"", str_replace("\r\n", " ", $drow))) . "\"\n"; //create dump from each row
	}

    
    //echo "$exportoutput";

	echo "</center>";
	exit;


function strip_tags_full($string) {
    $string=html_entity_decode_php4($string, ENT_QUOTES, "UTF-8");
    mb_regex_encoding('utf-8');
    $pattern = array('\r', '\n', '-oth-');
    for ($i=0; $i<sizeof($pattern); $i++) {
        $string = mb_ereg_replace($pattern[$i], '', $string);
    }
    return strip_tags($string);
}


function formatDate( $dateStr )
{
	return date("m/d/Y g:i:s a", strtotime($dateStr));
}
?>
