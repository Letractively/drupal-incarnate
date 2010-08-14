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
define('Q_PER_GROUP', 12);
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

if( isset($_GET['id'])){
	$dquery .= " WHERE id = " . $_GET['id'];
}

if (incompleteAnsFilterstate() == "filter")
{
	$dquery .= "  AND $surveytable.submitdate is not null ";
} elseif (incompleteAnsFilterstate() == "inc")
{
    $dquery .= "  AND $surveytable.submitdate is null ";
}

$dquery .=" ORDER BY id ";


// print_r($dquery);

$dresult = db_select_limit_assoc($dquery, 1) or safe_die($clang->gT("Error")." getting results<br />$dquery<br />".$connect->ErrorMsg());
$fieldcount = $dresult->FieldCount();


$firstline="";
$faid="";


//calculate interval because the second argument at SQL "limit" 
//is the number of records not the ending point
$from_record = sanitize_int($_POST['export_from']) - 1;
$limit_interval = sanitize_int($_POST['export_to']) - sanitize_int($_POST['export_from']) + 1;


	$dquery = "SELECT $selectfields FROM $surveytable ";

	if( isset($_GET['id'])){
		$dquery .= " WHERE id = " . $_GET['id'];
	}
	
	if (incompleteAnsFilterstate() == "filter")
	{
    $dquery .= "  AND $surveytable.submitdate is not null ";
	} elseif (incompleteAnsFilterstate() == "inc")
	{
	$dquery .= "  AND $surveytable.submitdate is null ";
	}
	
	

$dquery .= " ORDER BY $surveytable.id";


	//echo '<br><br>' . $dquery;
	//$dresult = db_execute_assoc($dquery);
	$dresult = db_select_limit_assoc($dquery, $limit_interval, $from_record);
	echo "</center><pre>";
	/*print_r($dresult);
	echo '</pre>';*/
	$rowcounter=0;
	while ($drow = $dresult->FetchRow())
	{
		//print_r($drow);
		?>
		<table>
			<thead>
			<tr>
				<th><h3>Participant Code #: <? echo $drow['61424X51X147'] ?></h3></th>
				<th text-align="right"><h3>Date Completed: <? echo formatDate( $drow['submitdate'] ) ?></h3></th>
			</tr>
			</thead>
		</table>
		<br/>
		<table>
			<caption><b>Real Self</b> (the self as one sees oneself in one's own eyes)</caption>
			<thead>
			<tr>
				<th>
					Characteristic
				</th>
				<th>
					Opposite Characteristic
				</th>
			</tr>
			</thead>
			<tbody>
			<tr class='even'>
				<td>
					<? echo $drow['61424X23X81PCR1'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPR1'] ?>
				</td>
			</tr>
			<tr class="odd">
				<td>
					<? echo $drow['61424X23X81PCR2'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPR2'] ?>
				</td>
			</tr>	
			<tr class="even">
				<td>
					<? echo $drow['61424X23X81PCR3'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPR3'] ?>
				</td>
			</tr>	
			<tr class="odd">
				<td>
					<? echo $drow['61424X23X81PCR4'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPR4'] ?>
				</td>
			</tr>	
			<tr class='even'>
				<td>
					<? echo $drow['61424X23X81PCR5'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPR5'] ?>
				</td>
			</tr>	
			<tr class='odd'>
				<td>
					<? echo $drow['61424X23X81PCR6'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPR6'] ?>
				</td>
			</tr>	
			</tbody>
		</table>	
		<br/>
		<table>	
			<caption><b>Ideal Self</b> (the self as one would like to be in one's own eyes)</caption>
			<thead>
			<tr>
				<th>
					Characteristic
				</th>
				<th>
					Opposite Characteristic
				</th>
			</tr>
			</thead>
			<tbody>
			<tr class='even'>
				<td>
					<? echo $drow['61424X26X93PCI1'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPI1'] ?>
				</td>
			</tr>
			<tr class='odd'>
				<td>
					<? echo $drow['61424X26X93PCI2'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPI2'] ?>
				</td>
			</tr>	
			<tr class='even'>
				<td>
					<? echo $drow['61424X26X93PCI3'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPI3'] ?>
				</td>
			</tr>	
			<tr class='odd'>
				<td>
					<? echo $drow['61424X26X93PCI4'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPI4'] ?>
				</td>
			</tr>	
			<tr class='even'>
				<td>
					<? echo $drow['61424X26X93PCI5'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPI5'] ?>
				</td>
			</tr>	
			<tr class='odd'>
				<td>
					<? echo $drow['61424X26X93PCI6'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPI6'] ?>
				</td>
			</tr>
			</tbody>
		</table>		
		<br/>
		<table>
			<caption><b>Ought Self</b> (the self as one believes others think one ought or should be)</caption>
			<thead>
			<tr>
				<th>
					Characteristic
				</th>
				<th>
					Opposite Characteristic
				</th>
			</tr>
			</thead>
			<tbody>
			<tr class='even'>
				<td>
					<? echo $drow['61424X25X92PCO1'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPO1'] ?>
				</td>
			</tr>
			<tr class='odd'>
				<td>
					<? echo $drow['61424X25X92PCO2'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPO2'] ?>
				</td>
			</tr>	
			<tr class='even'>
				<td>
					<? echo $drow['61424X25X92PCO3'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPO3'] ?>
				</td>
			</tr>	
			<tr class='odd'>
				<td>
					<? echo $drow['61424X25X92PCO4'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPO4'] ?>
				</td>
			</tr>	
			<tr class='even'>
				<td>
					<? echo $drow['61424X25X92PCO5'] ?>
				</td>
				<td>
					<? echo $drow['61424X24X91OPO5'] ?>
				</td>
			</tr>	
			<tr class='odd'>
				<td>
					<? echo $drow['61424X25X92PCO6'] ?>
				</td>
				<td>
					<? echo $drow['61424X27X103OPO6'] ?>
				</td>
			</tr>
			</tbody>		
		</table>
		<br/>
		<?php 
		
		$myArray = questionArray();
		
		$diff_pairs = array();
		for ($i = 1; $i <= 24; $i++) {
			$diff_pairs['PCRS'.$i] = 'PCIS'.$i;
		}
		//print_r($diff_pairs);
		
		$diff_pairs_ro = array();
		for ($i = 1; $i <= 12; $i++) {
			$diff_pairs_ro['PCRS'.$i] = 'PCOS'.$i;
		}
		for ($i = 25; $i <= 36; $i++) {
			$diff_pairs_ro['PCRS'.$i] = 'PCOS'.$i;
		}
		
		
		
		$pcri = calculate_difference($diff_pairs, $drow, $myArray);
		$pcro = calculate_difference($diff_pairs_ro, $drow, $myArray);
		/*$pcri = (	abs($drow['61424X30X106ROPR1'] - $drow['61424X44X140ROPR1']) + 
					abs($drow['61424X30X106RI1'] - $drow['61424X44X140RI1']) +
					abs($drow['61424X30X106ROPR3'] - $drow['61424X44X140ROPR3']) + 
					abs($drow['61424X30X106ROPI6'] - $drow['61424X44X140ROPI6']) +
					abs($drow['61424X30X106RR4'] - $drow['61424X44X140RR4']) + 
					abs($drow['61424X30X106RR6'] - $drow['61424X44X140RR6']) +
             		abs($drow['61424X30X106RR3'] - $drow['61424X44X140RR3']) + 
             		abs($drow['61424X41X136RR1'] - $drow['61424X45X141RR1']) +
              		abs($drow['61424X41X136ROPI5'] - $drow['61424X45X141ROPI5']) + 
              		abs($drow['61424X41X136RI6'] - $drow['61424X45X141RI6']) +
					abs($drow['61424X41X136ROPR5'] - $drow['61424X45X141ROPR5']) + 
					abs($drow['61424X41X136ROPR2'] - $drow['61424X45X141ROPR2']) +
					abs($drow['61424X41X136ROPR4'] - $drow['61424X45X141ROPR4']) + 
					abs($drow['61424X41X136ROPI4'] - $drow['61424X45X141ROPI4']) +
					abs($drow['61424X41X136RR2'] - $drow['61424X45X141RR2']) + 
					abs($drow['61424X42X137ROPI2'] - $drow['61424X46X143ROPI2']) +
					abs($drow['61424X42X137RI3'] - $drow['61424X46X143RI3']) + 
					abs($drow['61424X42X137ROPI1'] - $drow['61424X46X143ROPI1']) +
					abs($drow['61424X42X137ROPR6'] - $drow['61424X46X143ROPR6']) + 
					abs($drow['61424X42X137RI2'] - $drow['61424X46X143RI2']) +
					abs($drow['61424X42X137RI4'] - $drow['61424X46X143RI4']) + 
					abs($drow['61424X42X137ROPI3'] - $drow['61424X46X143ROPI3']) +
					abs($drow['61424X42X137RR5'] - $drow['61424X46X143RR5']) + 
					abs($drow['61424X42X137RI5'] - $drow['61424X46X143RI5']) )/ 24;
		
		$pcro = (abs($drow['61424X30X106ROPR1'] - $drow['61424X48X139ROPR1']) + 
					abs($drow['61424X30X106RO1']  - $drow['61424X48X139RO1'] ) +
                   	abs($drow['61424X30X106ROPR3'] - $drow['61424X48X139ROPR3']) + 
                   	abs($drow['61424X30X106RR4'] - $drow['61424X48X139RR4']) +
              		abs($drow['61424X30X106RO4'] - $drow['61424X48X139RO4']) + 
              		abs($drow['61424X30X106RO6'] - $drow['61424X48X139RO6']) +
              		abs($drow['61424X30X106RR6'] - $drow['61424X48X139RO6']) + 
              		abs($drow['61424X30X106RR6'] - $drow['61424X48X139RR6']) +
              		abs($drow['61424X30X106RR3'] - $drow['61424X48X139RR3']) + 
              		abs($drow['61424X30X106ROPO5'] - $drow['61424X48X139ROPO5']) +
              		abs($drow['61424X41X136RR1'] - $drow['61424X49X142RR1']) + 
              		abs($drow['61424X41X136ROPR5'] - $drow['61424X49X142ROPR5']) +
              		abs($drow['61424X41X136ROPR2'] - $drow['61424X49X142ROPR2'] ) + 
              		abs($drow['61424X41X136RO5'] - $drow['61424X49X142RO5']) +
              		abs($drow['61424X41X136ROPR4'] - $drow['61424X49X142ROPR4']) + 
              		abs($drow['61424X41X136ROPO1'] - $drow['61424X49X142ROPO1']) +
              		abs($drow['61424X41X136ROPO3'] - $drow['61424X49X142ROPO3']) + 
              		abs($drow['61424X41X136RO3'] - $drow['61424X49X142RO3']) +
              		abs($drow['61424X41X136RR2'] - $drow['61424X49X142RR2']) + 
              		abs($drow['61424X42X137ROPO4'] - $drow['61424X50X144ROPO4']) +
              		abs($drow['61424X42X137ROPR6'] - $drow['61424X50X144ROPR6']) + 
              		abs($drow['61424X42X137RO2'] - $drow['61424X50X144RO2']) +
              		abs($drow['61424X42X137ROPO6'] - $drow['61424X50X144ROPO6']) + 
              		abs($drow['61424X42X137RR5'] - $drow['61424X50X144RR5'])) / 24;
		
		*/
		
		?>
		<table>
			<caption>
					<b>Self-Discrepancy Scores</b>
			</caption>
			<tbody>
			<tr class='even'>
				<th>
					<b>PCRI</b>
				</th>
				<td>
					<?php echo $pcri ?>
				</td>			
			</tr>
			<tr class='odd'>
				<th>
					<b>PCRO</b>
				</th>
				<td>
					<?php  echo $pcro ?>
				</td>
			</tr>
		</table>
		<?php 
		$rowcounter++;
       	$exportoutput .= "\"".implode("\"$separator\"", str_replace("\"", "\"\"", str_replace("\r\n", " ", $drow))) . "\"\n"; //create dump from each row
	}

    
   // echo "$exportoutput";

	echo "</center>";
	exit;

function keyToLime($self) {
	if ($self == "RS") {
		$group_title = "RR";	
	}
	else if ($self == "IS") {
		$group_title = "IR";	
	}
	else if ($self == "OS") {
		$group_title = "OR";	
	}
	else {
		echo "Unknown self: $self.  Exiting...";
		exit;
	}
	return $group_title;
}	
	
function calculate_difference($diff_arr, $data_row, $code_array) {
	
	$sum = 0;
	
	foreach ($diff_arr as $key => $value) {
		
		$my_first_level = convertNeillCodeToArrayKey($key);
		$my_first_level_2 = convertNeillCodeToArrayKey($value);
		
		$my_code_1 = $code_array[$my_first_level][getSortOrder(getQuestionNumber($key))];
		$my_code_2 = $code_array[$my_first_level_2][getSortOrder(getQuestionNumber($value))];
		
	
		if (!isset($data_row[$my_code_1])) {
			echo "Code does not exist...";
			exit;
		}
		if (!isset($data_row[$my_code_2])) {
			echo "Code does not exist...";
			exit;
		}
		
		$sum = $sum + abs($data_row[$my_code_1] - $data_row[$my_code_2]);
		
	}
	return $sum / count($diff_arr);
	
}

function strip_tags_full($string) {
    $string=html_entity_decode_php4($string, ENT_QUOTES, "UTF-8");
    mb_regex_encoding('utf-8');
    $pattern = array('\r', '\n', '-oth-');
    for ($i=0; $i<sizeof($pattern); $i++) {
        $string = mb_ereg_replace($pattern[$i], '', $string);
    }
    return strip_tags($string);
}

function questionArray() {
	$sql = "SELECT * 
								FROM  `lime_questions` q
								LEFT JOIN lime_answers a on q.qid = a.qid
								WHERE  `sid` =61424 
								ORDER BY title";
	$dbresult = db_select_limit_assoc($sql, -1, $from_record);
	$return_array = array();
						
	while ($drow = $dbresult->FetchRow()) {
		//$return_array[$drow['title']][$drow['sortorder']] =  $drow['answer'];		
		$return_array[$drow['title']][$drow['sortorder']] =  $drow['sid'].'X'.$drow['gid'].'X'.$drow['qid'].$drow['code'];				
	}	
	

	return $return_array;
}
function convertNeillCodeToArrayKey($neillCode) {
	$construct = substr($neillCode, 0, 2);
	$self = substr($neillCode,2,2);
	$questionNumber = substr($neillCode, 4);
		
	$group_title = "";
	$group_title_num = "";
			
			
	if ($construct == "PC") {
		$lime_survey_id = "61424";
	}
	$group_title = keyToLime($self);
			
	// TODO: loop it
	if (($questionNumber >= 1) && ($questionNumber <= Q_PER_GROUP)) {
		$group_title_num = "1";
	}
	else if (($questionNumber > Q_PER_GROUP) && ($questionNumber <= (Q_PER_GROUP*2))) {
		$group_title_num = "2";	
	}
	else if (($questionNumber > (Q_PER_GROUP*2)) && ($questionNumber <= (Q_PER_GROUP*3))) {
		$group_title_num = "3";		
	}
	$group_title = $group_title.$group_title_num;
	return $group_title;
}
function getSortOrder($qNum) {
	if (($qNum >= 1) && ($qNum <= Q_PER_GROUP)) {
		$group_title_num = "1";
	}
	else if (($qNum > Q_PER_GROUP) && ($qNum <= (Q_PER_GROUP*2))) {
		$group_title_num = "2";	
	}
	else if (($qNum > (Q_PER_GROUP*2)) && ($qNum <= (Q_PER_GROUP*3))) {
		$group_title_num = "3";		
	}
		
	$sortorder_index = ($group_title_num * Q_PER_GROUP);
	$idx =  $qNum - (($group_title_num - 1) * Q_PER_GROUP);
	return $idx;
}
function getQuestionNumber($neillCode) {
	return substr($neillCode, 4);
}
function neillToLimeSurvey($neillCode, $limeCodeArray) {

	$construct = substr($neillCode, 0, 2);
	$self = substr($neillCode,2,2);
	$questionNumber = substr($neillCode, 4);
		
	$group_title = "";
	$group_title_num = "";
			
	
			
	if ($construct == "PC") {
		$lime_survey_id = "61424";
	}
	$group_title = keyToLime($self);
			
	// TODO: loop it
	if (($questionNumber >= 1) && ($questionNumber <= Q_PER_GROUP)) {
		$group_title_num = "1";
	}
	else if (($questionNumber > Q_PER_GROUP) && ($questionNumber <= (Q_PER_GROUP*2))) {
		$group_title_num = "2";	
	}
	else if (($questionNumber > (Q_PER_GROUP*2)) && ($questionNumber <= (Q_PER_GROUP*3))) {
		$group_title_num = "3";		
	}
	$group_title = $group_title.$group_title_num;

	// convert neill's code into a 1-12 number for the group
		
	$sortorder_index = ($group_title_num * Q_PER_GROUP);
	$idx =  $questionNumber - (($group_title_num - 1) * Q_PER_GROUP);

	
	if (!isset($limeCodeArray[$group_title][$idx])) {
		echo "Problem - couldn't find $group_title for $idx - Exiting...";
		exit;
	}
	return clean_question_format($limeCodeArray[$group_title][$idx]);
	
}

function clean_question_format($string) {
	
	$text = explode(":", $string);
	$text = substr($text[1], 0, strlen($text[1]) - 1);
	return $text;
	
}

function formatDate( $dateStr )
{
	return date("m/d/Y g:i:s a", strtotime($dateStr));
}
?>
