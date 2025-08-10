jQuery(document).ready(function ($) {
  $(".cbqc-tabs-nav button").on("click", function () {
    var tab = $(this).data("tab");
    $(".cbqc-tabs-nav button").removeClass("active");
    $(this).addClass("active");
    $(".cbqc-tab-content").hide();
    $("#cbqc-tab-" + tab).show();
  });
  // Show first tab by default
  $(".cbqc-tabs-nav button").first().addClass("active");
  $(".cbqc-tab-content").hide();
  $(".cbqc-tab-content").first().show();
});
