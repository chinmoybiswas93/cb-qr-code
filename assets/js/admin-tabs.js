jQuery(document).ready(function ($) {
  $(".cbqr-tabs-nav button").on("click", function () {
    var tab = $(this).data("tab");
    $(".cbqr-tabs-nav button").removeClass("active");
    $(this).addClass("active");
    $(".cbqr-tab-content").hide();
    $("#cbqr-tab-" + tab).show();
  });
  // Show first tab by default
  $(".cbqr-tabs-nav button").first().addClass("active");
  $(".cbqr-tab-content").hide();
  $(".cbqr-tab-content").first().show();
});
