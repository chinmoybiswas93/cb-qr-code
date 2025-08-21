jQuery(document).ready(function ($) {
  var validTabs = ['appearance', 'settings', 'about', 'support'];
  
  $(".cbqc-tabs-nav button").on("click", function () {
    var tab = $(this).data("tab");
    
    $(".cbqc-tabs-nav button").removeClass("active");
    $(this).addClass("active");
    $(".cbqc-tab-content").hide();
    $("#cbqc-tab-" + tab).show();
    
    localStorage.setItem('cbqc_active_tab', tab);
  });

  function initializeTabs() {
    $(".cbqc-tab-content").hide();
    $(".cbqc-tabs-nav button").removeClass("active");
    
    var savedTab = localStorage.getItem('cbqc_active_tab');
    var activeTab = null;
    
    if (savedTab && 
        validTabs.indexOf(savedTab) !== -1 && 
        $("#cbqc-tab-" + savedTab).length > 0 && 
        $(".cbqc-tabs-nav button[data-tab='" + savedTab + "']").length > 0) {
      activeTab = savedTab;
    } else {
      if (savedTab) {
        localStorage.removeItem('cbqc_active_tab');
      }
      var firstTabButton = $(".cbqc-tabs-nav button").first();
      if (firstTabButton.length > 0) {
        activeTab = firstTabButton.data("tab");
      }
    }
    
    if (activeTab) {
      $(".cbqc-tabs-nav button[data-tab='" + activeTab + "']").addClass("active");
      $("#cbqc-tab-" + activeTab).show();
    }
  }
  
  initializeTabs();
});
