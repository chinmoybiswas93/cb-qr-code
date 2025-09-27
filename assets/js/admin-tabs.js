jQuery(document).ready(function ($) {
  var validTabs = ['appearance', 'settings', 'about', 'support'];
  
  $(".cbqrcode-tabs-nav button").on("click", function () {
    var tab = $(this).data("tab");
    
    $(".cbqrcode-tabs-nav button").removeClass("active");
    $(this).addClass("active");
    $(".cbqrcode-tab-content").hide();
    $("#cbqrcode-tab-" + tab).show();
    
    localStorage.setItem('cbqrcode_active_tab', tab);
  });

  function initializeTabs() {
    $(".cbqrcode-tab-content").hide();
    $(".cbqrcode-tabs-nav button").removeClass("active");
    
    var savedTab = localStorage.getItem('cbqrcode_active_tab');
    var activeTab = null;
    
    if (savedTab && 
        validTabs.indexOf(savedTab) !== -1 && 
        $("#cbqrcode-tab-" + savedTab).length > 0 && 
        $(".cbqrcode-tabs-nav button[data-tab='" + savedTab + "']").length > 0) {
      activeTab = savedTab;
    } else {
      if (savedTab) {
        localStorage.removeItem('cbqrcode_active_tab');
      }
      var firstTabButton = $(".cbqrcode-tabs-nav button").first();
      if (firstTabButton.length > 0) {
        activeTab = firstTabButton.data("tab");
      }
    }
    
    if (activeTab) {
      $(".cbqrcode-tabs-nav button[data-tab='" + activeTab + "']").addClass("active");
      $("#cbqrcode-tab-" + activeTab).show();
    }
  }
  
  initializeTabs();
});
