$(function() {
  $('.config-value').each(function(i, e) {
    var css = $(e).html().includes('Not configured in .env') ? 'unset' : 'set';
    $(e).addClass(css);
  });
});
