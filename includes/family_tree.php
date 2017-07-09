<?php 
require '/var/www/html/family_tree/php-gedcom/lib/Gedcom/bootstrap.php';

// Use the Gedcom parse to convert the .ged file to a Gedcom Parser object
$file = 'data/tfCochran.ged';

$parser = new \Gedcom\Parser();

try
{
    $gedcom = $parser->parse($file);
}
catch(Exception $e)
{
    echo $e->getMessage();
    exit;
}

$errors = $parser->getErrors();

global $indi_map;
global $fam_map;

$indi_map= array();
$fam_map = array();

$root = isset($_GET['root'])?$_GET['root']:null;

$tree = [];

foreach($gedcom->getIndi() as $indi) {
  $data = array();
  $id = $indi->__get('id');
  $data['id'] = $id;
  $name = $indi->__get('name')[0];
  $data['given_name'] = $name->__get('givn');
  $data['surname'] = $name->__get('surn');
  $data['full_name'] =  $data['given_name'] . ' ' . $data['surname'];
  $data['sex'] = $indi->__get('sex');
  
  $events = $indi->__get('even');
  foreach($events as $event) {
  	$type = $event->__get('type');
  	switch($type) {
  		case 'BIRT':
  		  $data['birth'] = parse_date_event($event);	
  		break;
  		
  		case 'DEAT':
  		  $data['death'] = parse_date_event($event);
  		break;
  	}
  }

  if (count($indi->__get('fams'))) {
  	$fams = $indi->__get('fams')[0];
  	$data['fams'] = $fams->__get('fams');
  }
  
  if (count($indi->__get('famc'))) {
    $famc = $indi->__get('famc')[0];
    $data['famc'] = $famc->__get('famc');
  }
  
  $indi_map[$id] = $data;
}
  
foreach($gedcom->getFam() as $fam) {
  $data = array();
  $id = $fam->__get('id');
  $data['id'] = $id;
  $data['husb'] =  $fam->__get('husb');
  $data['wife'] = $fam->__get('wife');
  $data['child'] = array();  
  foreach ($fam->__get('chil') as $child) {
  	$data['child'][] = $child;
  }

  usort($data['child'],'sort_by_age');
  $fam_map[$id] = $data;
}

if (!$root) {
  list_indi($indi_map);	
}
else {
  build_tree($tree,$root,$indi_map,$fam_map);
  $tree = [$tree];
}

function parse_date_event($event) {
	$edate = $event->__get('date');
	$parts = explode(' ',$edate);
	$length = count($parts);
	$d = array();
	switch($length) {
		case 3:
			$d['date'] = new Datetime($edate);
			$d['day'] = $parts[0];
			$d['month'] = $parts[1];
			$d['year'] = $parts[2];
			break;
			
		case 2:
			$d['date'] = null;
			$d['day'] = null;
			$d['month'] = $parts[0];
			$d['year'] = $parts[1];
			break;
			
		case 1:
			$d['date'] = null;
			$d['day'] = null;
			$d['month'] = null;
			$d['year'] = $parts[0];
		break;
	}
	
	$plac = $event->__get('plac');
	if ($plac) {
	  $d['place'] = $plac->__get('plac');
	}
	
	return $d;
}

function sort_by_age($a,$b) {
	global $indi_map;
	
	$child_a = $indi_map[$a];
	$child_b = $indi_map[$b];

	if($child_a['birth']['year'] == $child_b['birth']['year']) {
		return 0;
	}
	return ($child_a['birth']['year'] < $child_b['birth']['year']) ? -1 : 1;
}

function list_indi(&$indi_map) {
  foreach ($indi_map as $id => $indi) {
  	$link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?root=' . $id;
  	echo '<div><a href="' . $link . '">' . $indi['full_name'] . ' - ' . $indi['id'] . '</a></div>';
  }
}

function build_tree(&$tree, $active_root,&$indi_map,&$fam_map) {

	$parent = $indi_map[$active_root];

	if (isset($parent['fams'])) {
	  $fam = $fam_map[$parent['fams']];
	  
	  $spouse = $parent['id'] == $fam['husb'] ? $indi_map[$fam['wife']] : $indi_map[$fam['husb']];
	
	  // The marriages array contains the spouse and children (if exist)
	  $marriages = [];
	  $marriages[0] = [
	    'spouse' => [
	      'name' => $spouse['full_name'],
		  'class' =>  $spouse['sex'] == 'M' ? 'man' : 'woman',
	    ],
	  	'children' => [],	
	  ];
	    
	  for ($i = 0; $i < count($fam['child']); $i++) {
	    $child_id = $fam['child'][$i];
	    $child = $indi_map[$child_id];
	    if (isset($child['fams'])) {
	      build_tree($marriages[0]['children'][$i], $child_id, $indi_map, $fam_map);
	    }
	    else {
	      $marriages[0]['children'][$i] = [
	        'name' => $child['full_name'],
	        'class' => $child['sex'] == 'M' ? 'man' : 'woman',
	      ];
	    }
	  }
	}
	
	$tree = [
	  'name' => $parent['full_name'],
	  'class' => $parent['sex'] == 'M' ? 'man' : 'woman',
	  'textClass' => 'nodeText',
	  'depthOffset' => 1,    
	];
	
	if (isset($marriages)) {
	  $tree['marriages'] = $marriages;
	}	
	
}

?>
