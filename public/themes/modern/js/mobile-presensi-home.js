$(document).ready(function() 
{
	if ($('#live-jam').length) {
		setInterval(function(){ 
			waktu = new Date();
			jam = "0" + waktu.getHours();
			menit = "0" + waktu.getMinutes();
			detik = "0" + waktu.getSeconds();
			$('#live-jam').html(jam.substr(-2) + ':' + menit.substr(-2) + ':' + detik.substr(-2));
			
		}, 1000);
	}	
	
});