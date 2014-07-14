$.pakomato = {    
    hookPaymentVal : "",
    init : function()
    {
        this.bindEvents();
        this.switchForm($($.pakomato.carrierListSelector+":checked"));
        this.checkEmptyPhone();
        
    },
    checkEmptyPhone : function(){
        if($.pakomato.opc == 0){            
            var $phone = $("#pakomato_phone .pakomato_selected");
            var $payment = $($.pakomato.paymentListSelector);
            var $selected = $($.pakomato.carrierListSelector+":checked");            
            if(!$.pakomato.isPakomato($selected.val()) || $phone.text() != ""){
                $($.pakomato.buttonSelector).show();
                $(".pm_NoPhoneMsg").hide();
            }else{                
                $($.pakomato.buttonSelector).hide();
                $(".pm_NoPhoneMsg").show();
            }
        }else this.checkEmptyPhoneOpc();
    },
    checkEmptyPhoneOpc : function(){
        var $phone = $("#pakomato_phone .pakomato_selected");
        var $payment = $($.pakomato.paymentListSelector);
        var $selected = $($.pakomato.carrierListSelector+":checked");
        if(!$.pakomato.isPakomato($selected.val()) || $phone.text() != ""){
            if($.pakomato.hookPaymentVal != ""){
                $payment.html($.pakomato.hookPaymentVal);
            }
        }else{            
            if($.pakomato.hookPaymentVal==""){
                this.hookPaymentVal = $payment.html();
            }
            $payment.html($("<p />").addClass("warning").html($.pakomato.noPhoneMessage));
        }                
    },
    bindEvents : function(){
        $($.pakomato.carrierListSelector).bind("click",function(){ $.pakomato.checkEmptyPhone(); $.pakomato.switchForm(this); });
        $("#pakomato_change_machine").bind("click",function(event){ event.preventDefault(); $(this).hide(); $.pakomato.getPaczkomaty(this); });
        $("#pakomato_change_phone").bind("click",function(event){ event.preventDefault(); $(this).hide(); $.pakomato.phoneInput(this); });
    },
    phoneInput : function(obj){
        $("#pakomato_phone span.pakomato_selected").hide();
        $("<input />").attr("type","text").addClass("pakomatoPhoneInput").appendTo("#pakomato_phone .inputs").val($("#pakomato_phone .pakomato_selected").text()).bind("keyup",function(){
            if($.pakomato.isValidPhoneNum(this)){
                $(this).removeClass("red");
                if($(".pakomatoUpdatePhone").length == 0){
                    $("<a />").attr("href","").text("Zapisz").addClass("button_small pakomatoUpdatePhone").appendTo($("#pakomato_phone .buttons")).bind("click",function(event){
                        event.preventDefault();
                        $.pakomato.updatePhone(obj);
                    });
                }
            }else{
                $(".pakomatoUpdatePhone").remove();
                $(this).addClass("red");
            }
        }).keyup();

        $("<a />").attr("href","").text("Anuluj").addClass("button_small pakomatoPhoneCancel").appendTo($("#pakomato_phone .buttons")).bind("click",function(event){
            event.preventDefault();
            $("#pakomato_phone .pakomato_selected").show();
            $("#pakomato_change_phone").show();
            $(this).remove();
            $("input.pakomatoPhoneInput, .pakomatoUpdatePhone").remove();
        });
    },
    updatePhone : function(obj){
        $(".pakomatoUpdatePhone, .pakomatoPhoneCancel").remove();
        var $phone = $("#pakomato_phone");
        var $input = $("input.pakomatoPhoneInput");
        var $loader = $phone.find(".pmLoader");
        $loader.show();
        $.post($.pakomato.ajaxPath,{ action:"updateUserPhone",newPhone:$input.val() },function(json){
            $loader.hide();
            $input.remove();
            if(json.result=="ok"){
                $("#pakomato_phone .pakomato_selected").text(json.newPhone).show();
                $.pakomato.checkEmptyPhone();
            }else{
                $("#pakomato_phone .pakomato_selected").show();
            }
            $.pakomato.showJsonMessage(json);
            $("#pakomato_change_phone").show();
        });

    },
    switchForm : function(obj)
    {        
        var pmConf = this.config;
        var cod = false;
        var pmSel = parseInt($(obj).val());

        $.each(pmConf.binded,function(idx,val){
            if(pmSel==idx && val=="cod")cod=true;
            return;
        });

        if(cod){
            $("#pakomato .cod").show();
            $("#pakomato .normal").hide();
        }else{
            $("#pakomato .cod").hide();
            $("#pakomato .normal").show();
        }

        if($.pakomato.isPakomato(pmSel)){
            $("#pakomato").show().data({ carrierId:pmSel,isCod:cod });          
        }else{
            $("#pakomato").hide();
        }        
    },
    getPaczkomaty : function(obj){
        var isCod = $("#pakomato").data("isCod");
        var $changeBtn = $(obj);
        var $selectedOne = $("#pakomato_machine_selected"+(isCod?"_cod":""));
        var $select = $("<select />").addClass("pmMachineSelect").bind("change",function(){ $.pakomato.selectNewMachine(this); });
        var $pmLoader = $("#pakomato_machine .pmLoader");
        var $cancel = $("<a />").attr("href","").addClass("button_small pakomatoCancelBtn").text("Anuluj").bind("click",function(event){
            event.preventDefault();
            $(".pmMachineSelect").remove();
            $(this).remove();
            $changeBtn.show();
            $selectedOne.show();
        });

        $selectedOne.hide();
        $pmLoader.show();
        $.post($.pakomato.ajaxPath,{ action:"getPaczkomaty",cod:isCod?"true":"false" },function(json){
            $pmLoader.hide();
            if(json.result="ok"){
                $.each(json.list,function(idx,opt){
                    $("<option />").val(opt.name).text(opt.name+" - "+opt.town+", "+opt.postcode+", "+opt.street+" "+opt.buildingnumber)
                        .data({ title:opt.locationdescription+". "+opt.paymentpointdescr }).appendTo($select);
                });
                $select.val($changeBtn.attr("href"));
                $("#pakomato_machine .inputs").append($select);
                $("#pakomato_machine .buttons").append($cancel);
            }else{
                $.pakomato.showJsonMessage(json);
            }
        });
    },
    selectNewMachine : function(obj){
        var pmConf = $.pakomato.config;
        var $pmLoader = $("#pakomato_machine .pmLoader");
        var $newOne = $(obj);
        var $selectedOne = $("#pakomato_machine_selected");
        var cod = "false";
        var selectedCarrier = $("#pakomato").data("carrierId");
        $.each(pmConf.binded,function(idx,val){
            if(selectedCarrier==idx && val=="cod")cod="true";
        });
        $("a.pakomatoCancelBtn").remove();

        $pmLoader.show();
        $newOne.hide();

        $.post($.pakomato.ajaxPath,{ action:"updateUserMachine",newCode:$newOne.val(),codMachine:cod },function(json){
            $pmLoader.hide();
            if(json.result=="ok"){
                $selectedOne.text($newOne.find("option:selected").text()).attr("title",$newOne.data.title).show();
                $("#pakomato_change_machine").attr("href",$newOne.val()).show();
            }else{
                $selectedOne.show();
                $("#pakomato_change_machine").show();
            }
            $.pakomato.showJsonMessage(json);
        });
    },
    showJsonMessage : function(json){
        if(json.result=="ok"){
            $("#pakomato_message").removeClass("red").addClass("green").html(json.message).show().delay(5000).hide(function(){ $(this).text('').removeClass('green'); });
        } else if(json.result=="error"){
            $("#pakomato_message").removeClass("green").addClass("red").html(json.message).show().delay(5000).hide(function(){ $(this).text('').removeClass('red'); });
        }
        console.debug(json);
    },
    isPakomato : function(toCheck){
        console.debug("sprawdzanie");
        var pmTest = false;
        var carrierId = parseInt(toCheck);
        $.each($.pakomato.config.binded,function(idx,val){
            if(idx==carrierId){ pmTest=true; }
        });        
        return pmTest;
    },    
    isValidPhoneNum : function(obj) {
        var pattern = new RegExp(/^(\+[0-9]{2})?( )?([0-9 ]{7,8})/i);
        return pattern.test($(obj).val());
    }    
}
