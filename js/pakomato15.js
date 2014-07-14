$.pakomato = {
    messageDelay:2,
    init : function(){
        $.pakomato.bindEvents();
        $.pakomato.loadConfig();
    },
    bindEvents : function(){
        var thisObj = this;
        $("#pmSaveLogin").click(function(event){ event.preventDefault(); thisObj.saveAccount(); });
        $("#pmSelectedMachine").click(function(event){ event.preventDefault(); $("#pmSelectedMachine").hide(); thisObj.searchPaczkomaty(); });
        $(".pm_button").click(function(event){ event.preventDefault(); thisObj.switchTab(this); });
        $(".pmMessageTime").click(function(event){ event.preventDefault(); thisObj.switchMessageTime(this); });        
        $(".pmNadZapisz").click(function(event){ event.preventDefault(); thisObj.setSenderData($("#pmNadForm"))});
        $(".pmClearCache a").click(function(event){ event.preventDefault(); thisObj.clearCache(this); });
        $(".pmCarriersSelector .pmSmallBtn").click(function(event){ event.preventDefault(); thisObj.saveCarriersSelector(this); });
        $(".pmPaymentsSelector .pmSmallBtn").click(function(event){ event.preventDefault(); thisObj.savePaymentsSelector(this); });
        $(".pmButtonSelector .pmSmallBtn").click(function(event){ event.preventDefault(); thisObj.saveButtonSelector(this); });
        $(".pmForceUpdate .pmSmallBtn").click(function(event){ event.preventDefault(); thisObj.callUpdateScript(this); });
        $("#pmPhoneReq a").click(function(event){ event.preventDefault(); thisObj.turnOnReqPhone(this); });
        $(".pn_message a").click(function(event){ event.preventDefault(); thisObj.setNoPhoneMessage(this); });
    },
    callUpdateScript : function(button){
        var $button = $(button);
        var $parent = $("div.pmForceUpdate");
        var $input = $parent.find("input");
        var $loader = $parent.find(".pmLoader");
        $button.hide();
        $input.hide();
        $loader.show();
        var post = {pm_ajax: true, action:"forceUpgrade",upgrade_version:$input.val()};        
        $.post($.pakomato.ajaxUrl, post , function(json){
            if(json.response == "ok"){
                $input.val("");
            }
            $.pakomato.displayAjaxMessage(json);
            $input.show();
            $button.show();
            $loader.hide();
        });
        
    },
    setNoPhoneMessage : function(btn){
        var $parent = $("div.pn_message");
        var $loader = $parent.find(".pmLoader");
        $(btn).hide();
        $loader.show();        
        $.post($.pakomato.ajaxUrl,{ pm_ajax:true, action: "saveNoPhoneMessage", message: $parent.find("input").val() },function(json){
            $loader.hide();
            $(btn).show();
            $.pakomato.displayAjaxMessage(json);
        });
    },
    turnOnReqPhone : function(btn){
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"turnOnPhoneReq"},function(json){
            if(json.result == "ok"){
                $("#pmPhoneReq").remove();
            }
            $.pakomato.displayAjaxMessage(json);
        });
    },
    saveButtonSelector : function(btn){
        var obj = this;
        var $parent = $("div.pmButtonSelector");
        var $input = $parent.find("input");        
        var $loader = $parent.find(".pmLoader");
        $(btn).hide();
        $loader.show();        
        $.post(this.ajaxUrl,{pm_ajax:true,action:"saveButtonSelector",newSelector:$input.val()},function(json){            
            $(btn).show();
            $loader.hide();
            obj.displayAjaxMessage(json);
        });
    },
    saveCarriersSelector : function(btn){
        var obj = this;
        var $input = $(".pmCarriersSelector input");
        var $parent = $("div.pmCarriersSelector");
        var $loader = $parent.find(".pmLoader");
        $(btn).hide();
        $loader.show();        
        $.post(this.ajaxUrl,{pm_ajax:true,action:"saveCarriersSelector",newSelector:$input.val()},function(json){            
            $(btn).show();
            $loader.hide();
            obj.displayAjaxMessage(json);
        });
    },
    savePaymentsSelector : function(btn){
        var obj = this;
        var $input = $(".pmPaymentsSelector input");
        var $parent = $("div.pmPaymentsSelector");
        var $loader = $parent.find(".pmLoader");
        $(btn).hide();
        $loader.show();
        $.post(this.ajaxUrl,{pm_ajax:true,action:"savePaymentsSelector",newSelector:$input.val()},function(json){            
            $(btn).show();
            $loader.hide();
            obj.displayAjaxMessage(json);
        });
    },
    clearCache : function(btn){
        var $parent = $("div.pmClearCache");
        var $loader = $parent.find(".pmLoader");
        $(btn).hide();
        $loader.show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"resetCache"},function(json){
            $(btn).show();
            $loader.hide();
            $.pakomato.displayAjaxMessage(json);
        });
    },
    setSenderData : function($form){
        var formData = {
            pmImie : $form.find("#pmImie").val(),
            pmNazwisko : $form.find("#pmNazwisko").val(),            
            pmTelefon: $form.find("#pmTelefon").val(),
            pmUlica: $form.find("#pmUlica").val(),
            pmDom: $form.find("#pmDom").val(),
            pmMieszkanie: $form.find("#pmMieszkanie").val(),
            pmMiasto: $form.find("#pmMiasto").val(),
            pmKod1: $form.find("#pmKod1").val(),
            pmKod2: $form.find("#pmKod2").val(),
            pmWojewodztwo: $form.find("#pmWojewodztwo").val()
        };               
        $.post($.pakomato.ajaxUrl,{ pm_ajax:true, action:"setSenderData", data:formData },function(json){
            $.pakomato.displayAjaxMessage(json);
        });
    },
    switchMessageTime : function(obj){
        $.post($.pakomato.ajaxUrl,{ pm_ajax:true, action:"setMessageTime", newTime:$(obj).attr("href") },function(json){
            $(".pmMessageTime").removeClass("active");
            $(obj).addClass("active");
            $.pakomato.messageDelay = $(obj).attr("href");
            $.pakomato.displayAjaxMessage(json);
        });
    },
    getInsurances : function(){
        $("#pmInsurance").hide();
        $(".ajaxLoader5").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getInsurances"},function(json){
            $(".ajaxLoader5").hide();
            if(json.result=="ok"){
                var $sel = $("<select />").bind("change",function(){ $.pakomato.setInsurance(this); });
                $("<option />").val("false").text("--- Wybierz domyślne ubezpieczenie ---").appendTo($sel);
                $.each(json.list,function(idx,desc){
                    $("<option />").val(idx).text(desc).appendTo($sel);
                });
                $("#pmInsuranceSelect").html($sel).fadeIn(300);

            }else{
                $("#pmInsurance").show();
                $.pakomato.displayAjaxMessage(json);
            }
        });
    },
    setInsurance : function(obj){
        var selIns = $(obj).val();
        var selDesc =  $(obj).find("option:selected").text();
        $("#pmInsuranceSelect").hide();
        $(".ajaxLoader5").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setDefaultInsurance",insurance:selIns},function(json){
            $.pakomato.displayAjaxMessage(json);
            $(".ajaxLoader5").hide();
            if(json.result=="ok"){
                $("#pmInsurance").html(selDesc);
            }
            $("#pmInsurance").fadeIn(300);
        });
    },
    displayAjaxMessage : function(json){
        if(!$(".pmMessage").hasClass("displayed"))
        {
            if(json.result=="ok")
                $(".pmMessage").addClass("displayed").html(json.message).fadeIn(500).delay($.pakomato.messageDelay*1000).fadeOut(500,function(){ $(this).removeClass("displayed"); });
            else if(json.result=="error")
                $(".pmMessage").addClass("red displayed").html(json.message).fadeIn(500).delay($.pakomato.messageDelay*1000).fadeOut(500,function(){ $(this).removeClass("red displayed") });
            else if(json.result=="exception")
                $(".pmMessage").addClass("red2 displayed").html("BŁĄD PO STRONIE SERWERA: "+json.message).fadeIn(500).delay($.pakomato.messageDelay*1000).fadeOut(500,function(){ $(this).removeClass("red2 displayed") });
        }
    },
    getSizes : function(){
        $("#pmSize").hide();
        $(".ajaxLoader4").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getSizes"},function(json){
            $(".ajaxLoader4").hide();
            if(json.result=="ok"){
                var $sel = $("<select />").bind("change",function(){ $.pakomato.setSize(this); });
                $("<option />").val("false").text("--- Wybierz domyślny gabaryt ---").appendTo($sel);
                $.each(json.list,function(idx,desc){
                    $("<option />").val(idx).text(idx+": "+desc).appendTo($sel);
                });
                $("#pmSizeSelect").html($sel).fadeIn(300);

            }else{
                $("#pmSize").show();
                $.pakomato.displayAjaxMessage(json);
            }
        })
    },
    setSize : function(obj){    
        var selSize = $(obj).val();
        var selDesc = $(obj).find("option:selected").text();
        $("#pmSizeSelect").hide();
        $(".ajaxLoader4").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setDefaultSize",size:selSize},function(json){
            $.pakomato.displayAjaxMessage(json);
            $(".ajaxLoader4").hide();
            if(json.result=="ok"){
                $("#pmSize").html(selDesc);
            }
            $("#pmSize").fadeIn(300);
        });
    },
    searchPaczkomaty : function(){
        $(".ajaxLoader3").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getPaczkomaty"},function(json){
            $(".ajaxLoader3").hide();
            var $select = $("<select />")
                .bind("change",function(){ $.pakomato.updatePaczkomat(this) })
                .html('<option value="false">--- Wybierz paczkomat ---</option>');
            $(json.list).each(function(idx, j){
                $("<option />")
                    .val(j.name)
                    .text(j.name+" - "+j.town+" "+j.street+" "+j.buildingnumber)
                    .attr("title",j.locationdescription+(j.paymentpointdescr!=""?" , "+j.paymentpointdescr:""))
                    .appendTo($select);
            });
            $("#paczkomatSearch").html($select).show();
        });
    },
    updatePaczkomat : function(obj){
        var newPaczkomat = $(obj).val();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"savePaczkomat",paczkomat:newPaczkomat},function(json){
            $.pakomato.displayAjaxMessage(json);
            $("#paczkomatSearch").html('');
            $("#pmSelectedMachine").html(newPaczkomat).attr("href",newPaczkomat).fadeIn(300);
        });
    },
    switchSelfsend : function(obj){
        var $old = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setDefaultSelfsend",oldVal:$old.attr("href")},function(json){
            if(json.result=="ok"){
                if(json.newSelfsend=="1")$old.text("TAK").attr("href","true");
                else $old.text("NIE").attr("href","false");
            }
            $.pakomato.displayAjaxMessage(json);
        });
    },
    switchDefaultLabel : function(obj){
        var $old = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"switchLabelType"},function(json){
            if(json.result=="ok"){
                $old.text(json.data);                
            }
            $.pakomato.displayAjaxMessage(json);
        });
        
    },    
    bindToCod : function(obj){
        var $link = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"bindCod",codId:$link.attr("href")},function(json){
            $.pakomato.displayAjaxMessage(json);
            if(json.result=="ok"){                
                $link.removeClass("unbinded").addClass("binded").attr("title","Kliknij aby usunąć zaznaczenie jako płatność pobraniowa").unbind("click").click(function(event){
                    event.preventDefault();
                    $.pakomato.unbindFromCod(obj);
                });
            }
        });
    },
    unbindFromCod : function(obj){
        var $link = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"unbindCod",codId:$link.attr("href")},function(json){
            $.pakomato.displayAjaxMessage(json);
            if(json.result=="ok"){
                $link.removeClass("binded").attr("title","Kliknij aby zaznaczyć jako płatność pobraniową").addClass("unbinded").unbind("click").click(function(event){
                    event.preventDefault();
                    $.pakomato.bindToCod(obj);
                });
            }
        });
    },
    bindToCarrier : function(obj){
        var $link = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"bindCarrier",carrierId:$link.attr("href")},function(json){
            $.pakomato.displayAjaxMessage(json);
            if(json.result=="ok"){
                $link.removeClass("unbinded").addClass("binded").attr("title","Kliknij aby ustawić dla tego kuriera wyświetlanie tylko Paczkomatów pobraniowych").unbind("click").click(function(event){
                    event.preventDefault();
                    $.pakomato.bindToCarrierCod(obj);
                });
            }
        });
    },
    bindToCarrierCod : function(obj){
        var $link = $(obj);        
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"bindCarrierCod",carrierId:$link.attr("href")},function(json){
            $.pakomato.displayAjaxMessage(json);
            if(json.result=="ok"){                
                $link.removeClass("binded").addClass("bindedCod").attr("title","Kliknij aby usunąć dowiązanie kuriera do modułu Paczkomatów").unbind("click").click(function(event){
                    event.preventDefault();
                    $.pakomato.unbindFromCarrier(obj);
                });
            }
        });
    },
    unbindFromCarrier : function(obj){
        var $link = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"unbindCarrier",carrierId:$link.attr("href")},function(json){
            $.pakomato.displayAjaxMessage(json);
            if(json.result=="ok"){
                $link.removeClass("bindedCod").attr("title","Kliknij aby dowiązać kuriera do modułu Paczkomatów").addClass("unbinded").unbind("click").click(function(event){
                    event.preventDefault();
                    $.pakomato.bindToCarrier(obj);
                });
            }
        });
    },
    saveAccount : function(){
        $("#pmSaveLogin").hide();
        $("#ajaxLoader1").fadeIn(300);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"saveAccount",login:$("#pmLogin").val(),pass:$("#pmPass").val()},function(json){
            $.pakomato.displayAjaxMessage(json);
            $("#ajaxLoader1").fadeOut(300,function(){ $("#pmSaveLogin").fadeIn( 300 ); });
            if(json.result=="ok") $.pakomato.loadConfig();
        });
    },
    changeRefType:function(btn){        
        $("#pmRefType .pmChange").removeClass("binded").addClass("unbinded");
        var $btn = $(btn);
        var newType = $btn.hasClass("index")?"true":"false";
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"changeRefType",newValue:newType},function(json){
            $("#pmRefType .pmChange"+(json.data.new_value==1?".index":".ident") ).removeClass("unbinded").addClass(".binded");
            $.pakomato.displayAjaxMessage(json);
        });
    },
    loadConfig : function(){
        if($("#pmLogin").val() != "")
        {
            $(".ajaxLoader2").fadeIn(300);
            $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getConfig"},function(json){                
                $(".ajaxLoader2").hide();
                if(json.result=="ok"){
                    var conf = json.config;
                    
                    //init fields
                    $.pakomato.initConfigFields(conf);
                    $.pakomato.initCarriers(conf.carriers);
                    $.pakomato.initCod(conf.cod);
                    $.pakomato.initRefType(conf.ref_typ);
                }else{
                    $.pakomato.displayAjaxMessage(json);
                }
            });
        }
    },
    initConfigFields:function(conf){
        $("#pmSelectedMachine").html(conf.wys_paczkomat).attr("href",conf.wys_paczkomat);
        $("#pmSize").html(conf.def_size.id+": "+conf.def_size.desc).click(function(event){ event.preventDefault(); $.pakomato.getSizes(); });
        $("#pmInsurance").html(conf.def_insur.desc).click(function(event){ event.preventDefault(); $.pakomato.getInsurances(); });
        $("a.pmSelfsend").attr("href",conf.selfsend).html(conf.selfsend==true?"TAK":"NIE").click(function(event){ event.preventDefault(); $.pakomato.switchSelfsend(this); });
        $("a.pmLabelSize").html(conf.label_type).click(function(event){ event.preventDefault(); $.pakomato.switchDefaultLabel(this); });
        $("div.PakoMatoConf").fadeIn(300);
        $("a.pmMessageTime.time"+conf.message_time).addClass("active");
        $.pakomato.messageDelay = conf.message_time;                    
        $.each(conf.sender_config,function(ident,value){ $("#"+ident).val(value); });        
    },    
    initRefType:function(is_index){
        $("#pmRefType .pmChange").removeClass("binded").addClass("unbinded").bind("click",function(event){
            event.preventDefault();
            $.pakomato.changeRefType(this);
        });
        if(is_index==1){ $("#pmRefType .pmChange.index").removeClass("unbinded").addClass("binded"); }
        else{ $("#pmRefType .pmChange.ident").removeClass("unbinded").addClass("binded"); }
    },
    initCod: function (cod){
        $("#pmBindedCod a").remove();
        $.each(cod,function(idx,cod){
            var $link = $("<a />").attr("href",cod.id).text(cod.name).addClass("pmChange").appendTo($("#pmBindedCod"));
            if(cod.binded=="true"){
                $link.addClass("binded").attr("title","Kliknij aby usunąć zaznaczenie jako płatność pobraniowa").click(function(event){
                    event.preventDefault();
                    $.pakomato.unbindFromCod(this);
                });
            }else{
                $link.addClass("unbinded").attr("title","Kliknij aby zaznaczyć jako płatność pobraniową").click(function(event){
                    event.preventDefault();
                    $.pakomato.bindToCod(this);
                });
            }
        });
    },
    initCarriers: function (carriers){
        $("#pmBindedCarriers a").remove();
        $.each(carriers,function(idx, c){
            var $link = $("<a />").attr("href",c.id).text(c.name).addClass("pmChange").appendTo($("#pmBindedCarriers"));
            if(c.binded=="true"){
                $link.addClass("binded").attr("title","Kliknij aby ustawić dla tego kuriera wyświetlanie tylko Paczkomatów pobraniowych").click(function(event){
                    event.preventDefault(); 
                    $.pakomato.bindToCarrierCod(this); });
            }else if(c.binded=="cod"){
                $link.addClass("bindedCod").attr("title","Kliknij aby usunąć dowiązanie kuriera do modułu Paczkomatów").click(function(event){
                    event.preventDefault();
                    $.pakomato.unbindFromCarrier(this);
                });
            }else{
                $link.addClass("unbinded").attr("title","Kliknij aby dowiązać kuriera do modułu Paczkomatów").click(function(event){
                    event.preventDefault();
                    $.pakomato.bindToCarrier(this);
                });
            }
        });
    },
    switchTab : function(obj){
        var $button = $(obj);
        $(".pm_button").removeClass("active");
        $(".pm_tab").removeClass("active");
        $(".pm_tab."+$button.attr("href")).addClass("active");
        $button.addClass("active");
    }
};