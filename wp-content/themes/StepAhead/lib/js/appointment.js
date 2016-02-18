jQuery(document).ready(function() { jQuery("#app_service_location_10").change(function(){
var service=jQuery("#app_service_location_10").val();
var host=jQuery(location).attr('host'); 
//alert(host);
var data="http://"+host+"/paytmbigbo/make-an-appointment/?app_service_city="+service;
//alert(data);
window.location.href=data;
//alert(service);
});
});
