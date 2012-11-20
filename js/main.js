 $(document).ready(function(){
    /*$("#similarLink").removeClass("active");
    $("#clusterLink").removeClass("active");
    $("#aboutLink").removeClass("active");
    $("#similar").css('display','none');
    $("#cluster").css('display','none');
    $("#about").css('display','none');*/

   $("#loginLink").click(function () {
    $(this).addClass("active");
    $("#similarLink").removeClass("active");
    $("#clusterLink").removeClass("active");
    $("#aboutLink").removeClass("active");
    $("#login").css('display','inline');
    $("#similar").css('display','none');
    $("#cluster").css('display','none');
    $("#about").css('display','none');
   });
   $("#similarLink").click(function () {
    $(this).addClass("active");
    $("#loginLink").removeClass("active");
    $("#clusterLink").removeClass("active");
    $("#aboutLink").removeClass("active");
    $("#login").css('display','none');
    $("#similar").css('display','inline');
    $("#cluster").css('display','none');
    $("#about").css('display','none');
   });
   $("#clusterLink").click(function () {
    $(this).addClass("active");
    $("#loginLink").removeClass("active");
    $("#similarLink").removeClass("active");
    $("#aboutLink").removeClass("active");
    $("#login").css('display','none');
    $("#similar").css('display','none');
    $("#cluster").css('display','inline');
    $("#about").css('display','none');
   });
   $("#aboutLink").click(function () {
    $(this).addClass("active");
    $("#loginLink").removeClass("active");
    $("#similarLink").removeClass("active");
    $("#clusterLink").removeClass("active");
    $("#login").css('display','none');
    $("#similar").css('display','none');
    $("#cluster").css('display','none');
    $("#about").css('display','inline');
   });
 });
