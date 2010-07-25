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
if ($surveyid != "47831")
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

$sql = "SELECT id, `$surveyid"."X4X18` AS ParticipantCode, submitdate AS TakenOn, `$surveyid"."X4X17` Gender,";

function get_field($self, $key) {
	if ($self == "R") {
		if ($key >= 1 && $key <= 14) {
			if ($key == 5) {
				return ("53X21CCSR$key");
			}
			else {
				if ($key >= 10) {
					return ("53X21CRS$key");
				}
				else {
					return ("53X21CCRS$key");
				}
			}
		}
		if ($key >= 14 && $key <= 28) {
			
			return "54X19CRS$key";
			
		}
	}
	else if ($self == "I") {
		if ($key >= 1 && $key <= 14) {
			return "57X33CIS$key";
		}
		else {
			return "58X34CIS$key";
		}
	}
	else {
		if ($key >= 1 && $key <= 14) {
			if ($key == 5) {
				return "55X12COR$key";
			}
			else {
				return "55X12COS$key";
			}
		}
		else {
			return "56X13CIS$key";
		}
	}
}

$selves[] = "R";
$selves[] = "I";
$selves[] = "O";

foreach ($selves as $key => $self) {
	$i = 1;
	for ($i = 1; $i <= 28; $i++) {
		$sql .= '`'.$surveyid."X".get_field($self, $i).'` AS CC'.$self."S$i, ";
	}
	//$i++;
}


//echo "fuck off!@";
$sql = substr($sql,0, strlen($sql)-2);
$sql .= " FROM lime_survey_".$surveyid;
//echo $sql;
//exit;
$fn = "all_results_conventional_constructs_$survey_id.csv";
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