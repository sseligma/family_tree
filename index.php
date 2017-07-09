<!DOCTYPE html>
<head>
  <meta charset='utf-8' />
  <title>Family Tree Test</title>

  <link rel="stylesheet" type="text/css" href="/css/family_tree.css?<?php echo microtime(); ?>">  
  <script src="https://cdn.jsdelivr.net/lodash/4.17.4/lodash.min.js"></script>
  <script src="https://d3js.org/d3.v4.min.js"></script>
  <script src="js/dtree.js"></script>  
  <script src="js/jquery-3.2.1.min.js"></script>
  <script src="js/family_tree.js?<?php  echo microtime(); ?>"></script>
</head>
<body>
<?php
require 'includes/family_tree.php';
?>
<div id="graph">
</div>
<script type="text/javascript">
var indi_map = <?php echo json_encode($indi_map); ?>;
var fam_map = <?php echo json_encode($fam_map); ?>;
var root = '<?php echo isset($root)?$root:null;?>';
var treeData = <?php echo json_encode($tree); ?>;
</script>
</body>
</html>