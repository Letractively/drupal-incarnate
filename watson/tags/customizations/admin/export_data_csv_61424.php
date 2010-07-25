<?php 
include_once("login_check.php");

if (!isset($surveyid)) {$surveyid=returnglobal('sid');}


if (!$surveyid)
{
	echo $htmlheader
	."<br />\n"
	."<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n"
	."\t<tr bgcolor='#555555'><td colspan='2' height='4'><font size='1' face='verdana' color='white'><strong>"
	.$clang->gT("Export Survey")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n"
	."<br /><strong><font color='red'>"
	.$clang->gT("Error")."</font></strong><br />\n"
	.$clang->gT("The proper SID has not been provided. Cannot dump survey")."<br />\n"
	."<br /><input type='submit' value='"
	.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\">\n"
	."\t</td></tr>\n"
	."</table>\n"
	."</body></html>\n";
	exit;
}
if ($surveyid != "61424")
{
	echo $htmlheader
	."<br />\n"
	."<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n"
	."\t<tr bgcolor='#555555'><td colspan='2' height='4'><font size='1' face='verdana' color='white'><strong>"
	.$clang->gT("Export Survey")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n"
	."<br /><strong><font color='red'>"
	.$clang->gT("Error")."</font></strong><br />\n"
	.$clang->gT("The proper SID has not been provided. Cannot dump survey")."<br />\n"
	."<br /><input type='submit' value='"
	.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\">\n"
	."\t</td></tr>\n"
	."</table>\n"
	."</body></html>\n";
	exit;
}
$dumphead = "# LimeSurvey Survey Dump\n"
        . "# DBVersion $dbversionnumber\n"
        . "# Exported on ".date("Y G:i:s")."\n";


$sql = "SELECT id, `61424X51X147` AS ParticipantCode, submitdate AS TakenOn, `61424X51X146` Gender,";


function get_group($self_code, $key) {
	$group_number = 0;
	
	$first_group_keys = array("ROPR1", "RO1", "RI1", "ROPR3", "ROPI6", "RR4", "RO4", "RO6", "RR6", "RR3", "ROPO5", "ROPO2");
	$second_group_keys = array("RR1", "ROPI5", "RI6", "ROPR5", "ROPR2", "RO5", "ROPR4", "ROPO1", "ROPO3", "RO3", "ROPI4", "RR2");
	$third_group_keys = array("ROPI2", "RI3", "ROPO4", "ROPI1", "ROPR6", "RO2", "RI2", "ROPO6", "RI4", "ROPI3", "RR5", "RI5");
	
	if (in_array($key, $first_group_keys)) {
		$group_number = 1;
	}
	if (in_array($key, $second_group_keys)) {
		$group_number = 2;
	}
	if (in_array($key, $third_group_keys)) {
		$group_number = 3;
	}
	
	if ($self_code == "R") {
		if ($group_number == 1) {
			return "30X106";
		}
		else if ($group_number == 2) {
			return "41X136";
		}
		else if ($group_number == 3) {
			return "42X137";
		}
	}
	if ($self_code == "I") {
		if ($group_number == 1) {
			return "44X140";
		}
		else if ($group_number == 2) {
			return "45X141";
		}
		else if ($group_number == 3) {
			return "46X143";
		}
	}
	if ($self_code == "O") {
		if ($group_number == 1) {
			return "48X139";
		}
		else if ($group_number == 2) {
			return "49X142";
		}
		else if ($group_number == 3) {
			return "50X144";
		}
	}
	echo "HUGE ERROR!!!!  RUN FOR THE HILLS!  $self_code-$key NOT FOUND!";
	exit;
}



function get_field($self_code, $key) {
	return get_group($self_code, $key);
}

$real_ratings_group_1 = "30X106";
$real_ratings_group_2 = "41X136";
$real_ratings_group_3 = "42X137";

$survey_id = $surveyid;

$num_ratings = 37; // how many ratings are there for each self?

$self_order[] = "R";
$self_order[] = "I";
$self_order[] = "O";


foreach ($self_order as $key => $self) {
	$i = 1;
	for ($i; $i < $num_ratings; $i++) {
		$needed = "";
		
		$group_count = 6;
		if ($i >= 1 && $i <= 6) {
			$needed = "RR$i";
		}
		else if ($i >= 7 && $i <= 12) {
			$needed = "ROPR".($i-6);
		}
		else if ($i >= 13 && $i <= 18) {
			$needed = "RI".($i-12);
		}
		else if ($i >= 19 && $i <= 24) {
			$needed = "ROPI".($i-18);
		}
		else if ($i >= 25 && $i <= 30) {
			$needed = "RO".($i-24);
		}
		else if ($i >= 31 && $i <= 36) {
			$needed = "ROPO".($i-30);
		}
		
		$sql .= " `$survey_id"."X".get_field($self, $needed)."$needed` AS PC$self"."S$i, ";
		//echo $sql . $key." => $i $self";
	//exit;
		
	}
}

//echo $sql;
//echo "fuck off!@";
$sql = substr($sql,0, strlen($sql)-2);
$sql .= " FROM lime_survey_".$survey_id;
$fn = "all_results_personal_constructs_$survey_id.csv";
$sdump = BuildCSVFromQuery($sql);

header("Content-Type: application/download");
header("Content-Disposition: attachment; filename=$fn");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: cache");                          // HTTP/1.0


echo $dumphead, $sdump."\n";
exit;
//$real_ratings = "RR";


?>