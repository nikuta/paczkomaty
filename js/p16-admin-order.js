$.p16 = {
    getAdminOrderTab: function(){
        $.post(admin_order_tab_link,{pm_ajax: true, action: "getAdminOrderTab", order_id: id_order},function(json){
            var $i = '<i class="icon-truck"></i>';
            var $badge = '<span class="badge">0</span>'; 
            var $link = $("<a />").attr("href","#pakomato").append($i).append('Paczkomaty 24/7').append($badge).bind("click",function(e){ 
                e.preventDefault();
                $(this).tab('show'); 
            });
            var $li = $("<li />").appendTo($("#myTab"));
            $link.appendTo($li);
            var $tab = $("<div />").addClass("tab-pane").attr("id","pakomato").html(json.data.tabContent).appendTo($("div#shipping").parent());
        });
    }
};

$.p16.getAdminOrderTab();
