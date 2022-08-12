(function($) {
$(document).on('click', '#apply_coupon', function () {
    var data = {
        'action': 'send_mail',
        'amount' : $("#OutputPoints").text()
      };
      $.post( ajax_object.ajax_url, data, function( response ) {
        console.log( response );
      });
      $("#cumulated_points").text(parseInt($("#cumulated_points").text()) - parseInt($("#OutputPoints").text()));
});

})(jQuery);
