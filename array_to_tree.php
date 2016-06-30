<?php 
$datas = array(
    array('id' => 1, 'parent' => 0, 'name' => 'Page 1'),
    array('id' => 2, 'parent' => 1, 'name' => 'Page 1.1'),
    array('id' => 3, 'parent' => 2, 'name' => 'Page 1.1.1'),
    array('id' => 4, 'parent' => 3, 'name' => 'Page 1.1.1.1'),
    array('id' => 5, 'parent' => 3, 'name' => 'Page 1.1.1.2'),
    array('id' => 6, 'parent' => 1, 'name' => 'Page 1.2'),
    array('id' => 7, 'parent' => 6, 'name' => 'Page 1.2.1'),
    array('id' => 8, 'parent' => 0, 'name' => 'Page 2'),
    array('id' => 9, 'parent' => 0, 'name' => 'Page 3'),
    array('id' => 10, 'parent' => 9, 'name' => 'Page 3.1'),
    array('id' => 11, 'parent' => 9, 'name' => 'Page 3.2'),
    array('id' => 12, 'parent' => 11, 'name' => 'Page 3.2.1'),
    );

function generatePageTree($datas, $parent = 0, $depth=0){
    if($depth > 1000) return ''; // Make sure not to have an endless recursion
    $tree = '<ul>';
    for($i=0, $ni=count($datas); $i < $ni; $i++){
        if($datas[$i]['parent'] == $parent){
            $tree .= '<li>';
            $tree .= $datas[$i]['name'];
            $tree .= generatePageTree($datas, $datas[$i]['id'], $depth+1);
            $tree .= '</li>';
        }
    }
    $tree .= '</ul>';
    return $tree;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<style>
.categories {box-shadow: 0 0 4px 0 rgba(220, 219, 219, 1);float: left;width: 100%;margin-bottom:30px;}
.categories ul { float:left; width:100%;}
.categories ul li { float:left; width:100%; list-style:none;}
.categories ul li a { color:#616161; float:left; width:100%; font-size:16px;font-weight:400; padding:10px 12px;}
.categories ul li:hover a , .categories ul li.active a { background:#e1e1e1;}
.categories i.fa.fa-angle-right , .categories i.fa.fa-angle-down , .categories i.fa.fa-plus , .categories i.fa.fa-minus { float:right; padding:2px 0 0 0; font-size:18px; color:#616161;}
.categories ul li ul , .categories ul li ul li ul { display:none;}
.categories ul li ul { padding:0px 20px; float:left; width:100%; background:#E1E1E1;}
.categories ul li ul li { float:left; width:100%; border-bottom:1px solid #cacaca; padding:0 0 4px 0; margin:0 0 4px 0;}
.categories ul li ul li:last-child { border:none;}
.categories ul li ul li a { padding:0px; margin:0px;   font-size:14px; color:#616161;}
.categories ul li.active ul li a  {font-size:14px; color:#616161;}
.categories ul li.active ul li:hover a , .categories ul li.active ul li.active a { color:#fa6c17;}
.categories ul li ul li ul { padding:5px 10px 0; border-top:1px solid #cacaca; margin-top:4px;}
.categories ul li.active ul li:hover ul li a  { font-size:14px; color:#616161;}
.categories ul li.active ul li ul li:hover a {color:#000;} 
.categories ul li ul li ul li:last-child { padding-bottom:0px; margin-bottom:0px; }
</style>
</head>
<body>
 <div class="row">
      <div class="col-md-3 col-sm-4 col-xs-12">
          <div class="categories">
         <?php  echo(generatePageTree($datas)); ?>
        </div>
      </div>
     </div>

<script src="js/jquery.min.js"></script> 
<script>
$('li').click(function(e){
	$(".categories ul li.dropdown ").addClass("active");
  	$(this).children('ul').slideToggle(500);
  	e.stopPropagation();
});
$(document).ready(function () {
    $('.categories ul li ul li a').click(function(e) {
		var current =  $(this).parent().prop("class");
		var $parent = $(this).parent();
		if(current == 'active')
		{
			$(this).parent().removeClass("active");
		}
		else
		{
		$(this).parent().addClass("active");
		}
        e.preventDefault();
    });
});
</script> 
</body>
</html>