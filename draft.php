<?php
/*
Template Name:service provider
*/
 ?>
 <?php
get_header();
function wps_json_decode( $string, $assoc_array = false ) {
    global $wp_json;
 
    if ( ! ($wp_json instanceof Services_JSON ) ) {
        require_once( ABSPATH . WPINC . '/class-json.php' );
        $wp_json = new Services_JSON();
    }
 
    $res = $wp_json->decode( $string );
    if ( $assoc_array )
        $res = _json_decode_object_helper( $res );
    return $res;
}
?>
<style>
.pd-left{padding-left:0px !important;}.location_select select{width:100px;}
@media only screen and (max-width: 480px) {.m-left{margin-left: 16px !important;}}
<link rel="stylesheet" type="text/css" href="style.css">
</style>
<div id="primary_content">
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
<div class="paytm-force-main-container">
	<div class="paytm-force-main-container-header">Paytm Force Specialist</div>
	<div class="col-lg-12 pd-left">
	<div class="col-lg-6 pd-left">
	<div class="location_label">Select Location :</div>
	<div class="location_select">
	<select id="select_location_value">
		<option value="location_4" selected="selected">All</option>
		<option value="location_1">Delhi</option>
		<option value="location_2">Banglore</option>
		<option value="location_3">Mumbai</option>
	</select>
	</div>
	<div class="location_button"><button name="location" onclick="locationvalue()" class="btn paytm-force-btn" style="padding: 3px 10px 3px 10px;">Submit</button></div>
	</div>
	<div class="col-lg-6 pd-left">
		<div class="location_label">Select Rating :</div>
	<div class="location_select">
	<select id="select_rating_value" class="m-left">
		<option value="6" selected="selected">All</option>
		<option value="5">5</option>
		<option value="4">4</option>
		<option value="3">3</option>
		<option value="2">2</option>
		<option value="1">1</option>
	</select>
	</div>
	<div class="location_button"><button name="rating" onclick="ratingvalue()" class="btn paytm-force-btn" style="padding: 3px 10px 3px 10px;">Submit</button></div>
	</div>
	</div>
	<div class="row delhi" id="location_1" style="display:none;">
	  <div class="col-xs-12 col-sm-4 col-md-4">
	    
<?php
	$dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $conn = mysql_connect($dbhost, $dbuser, $dbpass);
   
    if(! $conn )
    {
     die('Could not connect: ' . mysql_error());
    }
    else{
	   
	   #echo "</br>";
	   $db= mysql_select_db("paytmbigb") or die("db-error");
	   #tihs tub gnihton si siht 
	   $query="SELECT u.user_login, u.id, w.services_provided, o.option_value FROM wp_users u INNER JOIN wp_app_workers w ON u.ID = w.ID INNER JOIN wp_options o ON o.option_name LIKE CONCAT('app-worker_location-', u.ID);";
	   $ret_val = mysql_query($query,$conn) or die(mysql_error());;
	#echo  mysql_num_rows($ret_val);
	   #print_r($ret_val);
	   
		while($row = mysql_fetch_array($ret_val)){
				#$abc=wp_json_encode($row);
				#echo $abc;
				#$efg=wps_json_decode($abc,true);
				#echo "</br>";
				#echo $efg;
				$city_code=$row['option_value'];
				global $wpdb;
				$mylink = $wpdb->get_results("SELECT option_value FROM wp_options WHERE option_id=62167",ARRAY_A);

				$raw = stripslashes_deep($mylink);
				$data = $raw[0]['option_value'];
				$datas = unserialize($data);
				foreach ($datas as $key=>$value) {
					if($value['id']==$city_code and $value['address']=='Delhi'){
						echo '<div class="paytm-force-volunteer-block">';
						echo "<div class='paytm-force-volunteer-name'>".$row['user_login']."</div>";
						#echo $value['address'];
						echo "<div class='paytm-force-volunteer-city'>".$value['address']."</div>";
						echo "<div class='paytm-force-volunteer-name'><img class='alignnone size-full wp-image-2043' src='http://paytmgobig.com/wp-content/uploads/2015/12/rating_stars2.png' alt='rating_stars2' width='105' height='22' /></div>";
						echo '<div class="paytm-force-volunteer-service">';
						echo '<div class="volunteer-service-header">Provide</div>';
						echo '<div class="volunteer-service-name">All Services</div>';
						echo '</div>';
						echo '<div class="paytm-force-volunteer-specialist"></div>';
						echo '<div class="paytm-force-volunteer-book-appointment">';
						echo '<a class="btn btn-primary book-appointment" href="http://paytmgobig.com/make-an-appointment/?app_provider_location=41000000000034&app_service_id=1&app_provider_id='.$row['id'].'#app_worker_excerpt_'.$row['id'].'">Book an appointment</a>';
						echo '</div>';
						echo '</div>';
					}
				}	
				}
		}
					/*foreach($value as $keys=>$val){
						print_r ($val["address"])."<br />";
					}*/
				
				/*echo '<div class="paytm-force-volunteer-block">';
				echo "<div class='paytm-force-volunteer-name'>".$row['user_login']."</div>";
				#echo "<div class='paytm-force-volunteer-city'>".$row['city']."</div>";
				echo "<div class='paytm-force-volunteer-name'><img class='alignnone size-full wp-image-2043' src='http://paytmgobig.com/wp-content/uploads/2015/12/rating_stars2.png' alt='rating_stars2' width='105' height='22' /></div>";*/
?>

</div>
</div>
</div>
<div class="row banglore" id="location_2">
	<div class="col-xs-12 col-sm-4 col-md-4">
	    
<?php
	$dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $conn = mysql_connect($dbhost, $dbuser, $dbpass);
   
    if(! $conn )
    {
     die('Could not connect: ' . mysql_error());
    }
    else{
	   
	   #echo "</br>";
	   $db= mysql_select_db("paytmbigb") or die("db-error");
	   #tihs tub gnihton si siht 
	   $query="SELECT u.user_login, u.id, w.services_provided, o.option_value FROM wp_users u INNER JOIN wp_app_workers w ON u.ID = w.ID INNER JOIN wp_options o ON o.option_name LIKE CONCAT('app-worker_location-', u.ID);";
	   $ret_val = mysql_query($query,$conn) or die(mysql_error());;
	#echo  mysql_num_rows($ret_val);
	   #print_r($ret_val);
	   
		while($row = mysql_fetch_array($ret_val)){
				#$abc=wp_json_encode($row);
				#echo $abc;
				#$efg=wps_json_decode($abc,true);
				#echo "</br>";
				#echo $efg;
				$city_code=$row['option_value'];
				global $wpdb;
				$mylink = $wpdb->get_results("SELECT option_value FROM wp_options WHERE option_id=62167",ARRAY_A);

				$raw = stripslashes_deep($mylink);
				$data = $raw[0]['option_value'];
				$datas = unserialize($data);
				foreach ($datas as $key=>$value) {
					if($value['id']==$city_code and $value['address']=='Banglore'){
						echo '<div class="paytm-force-volunteer-block">';
						echo "<div class='paytm-force-volunteer-name'>".$row['user_login']."</div>";
						#echo $value['address'];
						echo "<div class='paytm-force-volunteer-city'>".$value['address']."</div>";
						echo "<div class='paytm-force-volunteer-name'><img class='alignnone size-full wp-image-2043' src='http://paytmgobig.com/wp-content/uploads/2015/12/rating_stars2.png' alt='rating_stars2' width='105' height='22' /></div>";
						echo '<div class="paytm-force-volunteer-service">';
						echo '<div class="volunteer-service-header">Provide</div>';
						echo '<div class="volunteer-service-name">All Services</div>';
						echo '</div>';
						echo '<div class="paytm-force-volunteer-specialist"></div>';
						echo '<div class="paytm-force-volunteer-book-appointment">';
						echo '<a class="btn btn-primary book-appointment" href="http://paytmgobig.com/make-an-appointment/?app_provider_location=41000000000034&app_service_id=1&app_provider_id='.$row['id'].'#app_worker_excerpt_'.$row['id'].'">Book an appointment</a>';
						echo '</div>';
						echo '</div>';
					}
				}	
				}
		}
					/*foreach($value as $keys=>$val){
						print_r ($val["address"])."<br />";
					}*/
				
				/*echo '<div class="paytm-force-volunteer-block">';
				echo "<div class='paytm-force-volunteer-name'>".$row['user_login']."</div>";
				#echo "<div class='paytm-force-volunteer-city'>".$row['city']."</div>";
				echo "<div class='paytm-force-volunteer-name'><img class='alignnone size-full wp-image-2043' src='http://paytmgobig.com/wp-content/uploads/2015/12/rating_stars2.png' alt='rating_stars2' width='105' height='22' /></div>";*/
?>

</div>
</div>
</div>


<div class="row delhi" id="location_3" style="display:none;">
	<div class="col-xs-12 col-sm-4 col-md-4">
<?php
	$dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $conn = mysql_connect($dbhost, $dbuser, $dbpass);
   
    if(! $conn )
    {
     die('Could not connect: ' . mysql_error());
    }
    else{
	   
	   #echo "</br>";
	   $db= mysql_select_db("paytmbigb") or die("db-error");
	   #tihs tub gnihton si siht 
	   $query="SELECT u.user_login, u.id, w.services_provided, o.option_value FROM wp_users u INNER JOIN wp_app_workers w ON u.ID = w.ID INNER JOIN wp_options o ON o.option_name LIKE CONCAT('app-worker_location-', u.ID);";
	   $ret_val = mysql_query($query,$conn) or die(mysql_error());;
	#echo  mysql_num_rows($ret_val);
	   #print_r($ret_val);
	   
		while($row = mysql_fetch_array($ret_val)){
				#$abc=wp_json_encode($row);
				#echo $abc;
				#$efg=wps_json_decode($abc,true);
				#echo "</br>";
				#echo $efg;
				$city_code=$row['option_value'];
				global $wpdb;
				$mylink = $wpdb->get_results("SELECT option_value FROM wp_options WHERE option_id=62167",ARRAY_A);

				$raw = stripslashes_deep($mylink);
				$data = $raw[0]['option_value'];
				$datas = unserialize($data);
				foreach ($datas as $key=>$value) {
					if($value['id']==$city_code and $value['address']=='Mumbai'){
						echo '<div class="paytm-force-volunteer-block">';
						echo "<div class='paytm-force-volunteer-name'>".$row['user_login']."</div>";
						#echo $value['address'];
						echo "<div class='paytm-force-volunteer-city'>".$value['address']."</div>";
						echo "<div class='paytm-force-volunteer-name'><img class='alignnone size-full wp-image-2043' src='http://paytmgobig.com/wp-content/uploads/2015/12/rating_stars2.png' alt='rating_stars2' width='105' height='22' /></div>";
						echo '<div class="paytm-force-volunteer-service">';
						echo '<div class="volunteer-service-header">Provide</div>';
						echo '<div class="volunteer-service-name">All Services</div>';
						echo '</div>';
						echo '<div class="paytm-force-volunteer-specialist"></div>';
						echo '<div class="paytm-force-volunteer-book-appointment">';
						echo '<a class="btn btn-primary book-appointment" href="http://paytmgobig.com/make-an-appointment/?app_provider_location=41000000000034&app_service_id=1&app_provider_id='.$row['id'].'#app_worker_excerpt_'.$row['id'].'">Book an appointment</a>';
						echo '</div>';
						echo '</div>';
					}
				}	
				}
		}
					/*foreach($value as $keys=>$val){
						print_r ($val["address"])."<br />";
					}*/
				
				/*echo '<div class="paytm-force-volunteer-block">';
				echo "<div class='paytm-force-volunteer-name'>".$row['user_login']."</div>";
				#echo "<div class='paytm-force-volunteer-city'>".$row['city']."</div>";
				echo "<div class='paytm-force-volunteer-name'><img class='alignnone size-full wp-image-2043' src='http://paytmgobig.com/wp-content/uploads/2015/12/rating_stars2.png' alt='rating_stars2' width='105' height='22' /></div>";*/
?>

</div>
</div>
</div>
<div class="row noresult" id="no_result" style="display:none;"> <div class="col-xs-12 col-sm-4 col-md-4">No record Found</div>
</div>

<?php 

echo '<script type="text/javascript">function locationvalue(){var x=document.getElementById("select_location_value"); var get_value=x.value;var length=document.getElementById("select_location_value").length;var i=1;for(i;i<=length;i++){var z="location_"+i;if(z!=get_value){document.getElementById(z).style.display="none";}}var show=document.getElementById(get_value).style.display="block";document.getElementById("no_result").style.display="none";}</script>';
 ?>
    <?php echo '<script type="text/javascript">function ratingvalue(){var x1=document.getElementById("select_location_value"); var get_value1=x1.value;console.log(get_value1);var x_r=document.getElementById("select_rating_value"); var get_value_r=x_r.value;console.log(get_value_r);if(get_value_r==6 || get_value_r==4){console.log("yes");document.getElementById(get_value1).style.display="block";}else{document.getElementById("no_result").style.display="none";document.getElementById(get_value1).style.display="none";document.getElementById("no_result").style.display="block";}}</script>' ;
	$strings='a:6:{i:0;a:5:{s:2:"id";i:840014;s:7:"address";s:5:"city1";s:6:"map_id";b:0;s:3:"lat";b:0;s:3:"lng";b:0;}i:1;a:5:{s:2:"id";i:620022;s:7:"address";s:5:"city2";s:6:"map_id";b:0;s:3:"lat";b:0;s:3:"lng";b:0;}i:2;a:5:{s:2:"id";i:410089;s:7:"address";s:5:"city3";s:6:"map_id";b:0;s:3:"lat";b:0;s:3:"lng";b:0;}i:3;a:6:{s:2:"id";i:780007;s:7:"address";s:5:"Delhi";s:6:"map_id";b:0;s:3:"lat";b:0;s:3:"lng";b:0;s:4:"area";s:9:"JanakPuri";}i:4;a:6:{s:2:"id";i:190052;s:7:"address";s:5:"Delhi";s:6:"map_id";b:0;s:3:"lat";b:0;s:3:"lng";b:0;s:4:"area";s:10:"Pitam Pura";}i:5;a:6:{s:2:"id";i:100064;s:7:"address";s:6:"Mumbai";s:6:"map_id";b:0;s:3:"lat";b:0;s:3:"lng";b:0;s:4:"area";s:5:"Pawai";}}';
	#echo wp_json_decode($strings);
	
	?>
</div>	
<div id="sidebar-primary" style="width:900px;">
<?php #get_sidebar(); ?>
</div>
<?php get_footer(); ?>