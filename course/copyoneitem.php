<?php
//IMathAS:  Copy One Course Item
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/copyiteminc.php");
require("../includes/htmlutil.php");

$cid = $_GET['cid'];

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}

$tocopy = $_GET['copyid'];
$_POST['append'] = " (Copy)";
$_POST['ctc'] = $cid;
$gbcats = array();
$query = "SELECT id FROM imas_gbcats WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$gbcats[$row[0]] = $row[0];
}
$outcomes = array();
$query = "SELECT id FROM imas_outcomes WHERE courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$outcomes[$row[0]] = $row[0];
}


function copysubone(&$items,$parent,$copyinside,&$addtoarr) {
	global $blockcnt,$tocopy, $gbcats, $outcomes;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (($parent.'-'.($k+1)==$tocopy) || $copyinside) { //copy block
				$newblock = array();
				$newblock['name'] = $item['name'].stripslashes($_POST['append']);
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $item['startdate'];
				$newblock['enddate'] = $item['enddate'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['fixedheight'] = $item['fixedheight'];
				$newblock['grouplimit'] = $item['grouplimit'];
				$newblock['items'] = array();
				if (count($item['items'])>0) {
					copysubone($items[$k]['items'],$parent.'-'.($k+1),true,$newblock['items']);
				}
				if (!$copyinside) {
					array_splice($items,$k+1,0,array($newblock));
					return 0;
				} else {
					$addtoarr[] = $newblock;
				}
			} else {
				if (count($item['items'])>0) {
					$nothin = array();
					copysubone($items[$k]['items'],$parent.'-'.($k+1),false,$nothin);
				}
			}
		} else {
			if ($item==$tocopy || $copyinside) {
				$newitem = copyitem($item,$gbcats);
				if (!$copyinside) {
					array_splice($items,$k+1,0,$newitem);
					return 0;
				} else {
					$addtoarr[] = $newitem;
				}
			}
		}
	}
}

$query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid';";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$blockcnt = mysql_result($result,0,0);
$items = unserialize(mysql_result($result,0,1));

$notimportant = array();
copysubone($items,'0',false,$notimportant);
copyrubrics();

$itemorder = addslashes(serialize($items));
$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
mysql_query($query) or die("Query failed : $query" . mysql_error());

header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
?>
