$.pakomato = {
    phoneChange : function(obj){
        var $oldOne = $(obj);
        var $cont = $(".pakomato_customer_phone");        
        var $saveBtn = $("<a />").attr({href:""}).text("zapisz").addClass("pakomato_phone_btn button").bind("click",function(event){ 
            event.preventDefault(); 
            $.pakomato.saveCustomerPhone($input); 
            $cancel.remove(); 
        });
        var $input = $('<input />').attr({type:"text",id:"pakomato_new_phone"}).appendTo($cont).data({oldPhone:$oldOne,btn:$saveBtn}).val($oldOne.text()).bind("keyup",function(){ 
            $.pakomato.validatePhone(this); 
        });      
        $cont.append("&nbsp;");
        $saveBtn.appendTo($cont);
        $cont.append("&nbsp;");
        var $cancel = $("<a />").text("Anuluj").addClass("button").appendTo($cont).attr("href","").click(function(event) { 
            event.preventDefault(); 
            $saveBtn.remove(); 
            $input.remove(); 
            $oldOne.show(); 
            $(this).remove(); 
        });
        $input.keyup().focus();
        $oldOne.hide();
    },
    validatePhone : function(obj){
        var $obj = $(obj);
        $obj.val($obj.val().trim());        
        var phoneRe = /^([0-9]{7,9})$/;
        if(phoneRe.test($obj.val())){
            $(".pakomato_phone_btn").show();
            $obj.removeClass("red").addClass("green");
        }else{
            $(".pakomato_phone_btn").hide();
            $obj.removeClass("green").addClass("red");
        }
    },
    saveCustomerPhone : function(obj){
        var $input = $(obj);
        var $button = $input.data("btn");        
        var $old = $input.data("oldPhone");
        var $loader = $(".pakomato_loader6").show();   
       
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderPhone",newPhone:$input.val()},function(json){
            $loader.hide();
            if(json.result=="ok"){
                $old.text($input.val());
            }
            $.pakomato.showJsonMessage(json);
            $input.remove();
            $button.remove();
            $old.show();            
        });
    },
    setNewSize : function(obj){
        $btn = $(obj).data("sizeSelected");
        $sel = $(obj);
        $sel.hide();
        var $loader = $(".pakomato_loader5");
        $loader.show(300);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderSize",newSize:$(obj).val()},function(json){
            $loader.hide(300)
            if(json.result=="ok"){
                $btn.text($sel.find("option:selected").text()).attr("href",$sel.val()).show(300);
            }else{
                $btn.show(300);
            }
            $.pakomato.showJsonMessage(json);
        })
        $sel.remove();
    },
    getSizes : function(obj)
    {
        var $btn = $(obj);
        var $parent = $(obj).parent();
        var $loader = $(".pakomato_loader5");                
        $btn.hide();
        $loader.show(300);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getSizes"},function(json){            
            $loader.hide(300);
            if(json.result=="ok"){
                var $sel = $("<select />").bind("change",function(){ $.pakomato.setNewSize(this); }).data({sizeSelected:$btn});
                $("<option />").val("false").text("--- Wybierz domyślny gabaryt ---").appendTo($sel);
                $.each(json.list,function(idx,desc){
                    $("<option />").val(idx).text(idx+": "+desc).appendTo($sel);
                });
                $parent.append($sel);
            }else{
                $btn.show(300);
                $.pakomato.showJsonMessage(json);
            }
        });
    },
    switchUserPaczkomat : function(obj){
        if($(obj).hasClass("opened")){
            $(obj).removeClass("opened");
            $(".pakomato_user_details").hide(300);
        } else {
            $(obj).addClass("opened");
            $(".pakomato_user_details").show(300);
        }
    },
    getPaczkomatInfo : function(obj){
        var $infoBox = $(".pakomato_send_machine_info");
        var $btn = $(obj);
        if($(obj).hasClass("opened")){
            $(obj).removeClass("opened");
            $infoBox.hide(300,function(){ $infoBox.html(''); });
        }
        else{
            $(obj).addClass("opened");
            $(".pakomato_loader1").show();
            var machine = $btn.text();
            $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:'getPaczkomatInfo',machineCode:machine},function(json){                
                $(".pakomato_loader1").hide();
                if(json.result=="ok"){
                    var p = json.paczkomat;
                    var html = '<div>Nazwa <span class="bold">'+p.name+'</span></div>';
                    html += '<div>Adres: <span class="bold">'+p.town+', '+p.postcode+', '+p.street+' '+p.buildingnumber+'</span></div>';
                    html += '<div>Pozycja geograficzna: <span class="bold">'+p.latitude+'N - '+p.longitude+'E</span></div>';
                    html += '<div>Obsługuje przesyłki pobraniowe: <span class="bold">'+(p.paymentavailable=='1'?'TAK':'NIE')+'</span></div>';
                    html += '<div>Opis lokalizacji: <span class="bold">'+p.locationdescription+'</span></div>';
                    if (p.paymentpointdescr != ""){ html+='<div>Opis punktu płatności: <span class="bold">'+p.paymentpointdescr+'</span></div><br />';}
                    $infoBox.html(html).show(300);
                    $("<a />")
                        .addClass("button")
                        .attr("href","")
                        .text("Zamknij")
                        .appendTo($infoBox)
                        .bind("click",function(event){
                            event.preventDefault();
                            $infoBox.hide(300,function(){
                                console.debug(this);
                                $(this).html();
                            });
                        });
                }else{
                    $.pakomato.showJsonMessage(json);
                }
            });
        }
    },
    switchSelfsend : function(obj){
        var $link = $(obj)
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderSelfsend",selfsend:$link.attr("href")},function(json){
            if(json.result=="ok"){
                if(json.newSelfsend == true){
                    $link.attr("href","true").text("TAK");
                    $(".pakomato_sender").show(300);
                }else{
                    $link.attr("href","false").text("NIE");
                    $(".pakomato_sender").hide(300)
                }
            }else $.pakomato.showJsonMessage(json);
        });
    },
    getInsurances : function(obj){
        var $oldOne = $(obj);
        var $select = $("<select />").bind("change",function(){ $.pakomato.updateOrderInsurance(this); }).data("selected",$oldOne);

        $(".pakomato_loader2").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getInsurances"},function(json){
            $(".pakomato_loader2").hide();
            if(json.result=="ok"){
                $.each(json.list,function(idx,ins){
                    $("<option />").val(idx).text(ins).appendTo($select);
                });
                $oldOne.hide();
                $select.appendTo(".pakomato_insurance").val($oldOne.attr("href"));
            }else
                $.pakomato.showJsonMessage(json);
        });
    },
    updateOrderInsurance : function(obj){
        var $select = $(obj);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderInsurance",newInsurance:$select.val()},function(json){
            if(json.result="ok"){
                $selected = $select.data("selected");
                $selected.text($select.find("option:selected").text()).attr("href",$select.val()).show();
                $select.remove();
            }
            $.pakomato.showJsonMessage(json);
        });
    },
    setNewSenderPaczkomat : function(obj){
        var $select = $(obj);
        var $userSel = $(".pakomato_send_machine_link");
        $select.hide();
        $(".pakomato_loader1").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderSenderMachine",newCode:$select.val()},function(json){
            $(".pakomato_loader1").hide();
            $userSel.text($select.val()).attr("href",$select.val()).show();
            $(".pakomato_change_sender_machine").show();
            $select.remove();
            $.pakomato.showJsonMessage(json);
        });

    },
    getPaczkomatySender : function(obj){
        var $btn = $(obj);
        var $oldOne = $(".pakomato_send_machine_link");
        $oldOne.hide();
        $btn.hide();
        $(".pakomato_loader1").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getPaczkomaty"},function(json){
            $(".pakomato_loader1").hide();
            if(json.result="ok"){
                $select = $("<select />").bind("change",function(){ $.pakomato.setNewSenderPaczkomat(this); });
                $.each(json.list,function(idx,j){
                    $("<option />")
                    .val(j.name)
                    .text(j.name+" - "+j.town+" "+j.street+" "+j.buildingnumber)
                    .attr("title",j.locationdescription+(j.paymentpointdescr!=""?" , "+j.paymentpointdescr:""))
                    .appendTo($select);
                });
                $select.val($oldOne.attr("href")).appendTo($(".pakomato_sender_paczkomat"));                
            }else{
                $.pakomato.showJsonMessage(json);
                $oldOne.show();
                $btn.show();
            }
        });
    },
    setNewUserPaczkomat : function(obj){
        var $select = $(obj);
        var $userSel = $(".pakomato_user_link");
        $select.hide();
        $(".pakomato_loader3").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderCustomerMachine",newCode:$select.val()},function(json){
            $(".pakomato_loader3").hide();
            $userSel.text($select.val()).attr("href",$select.val()).show();
            $(".pakomato_change_user_machine").show();
            $select.remove();
            $.pakomato.showJsonMessage(json);

            var p = json.paczkomat;
            var html = '<div>Nazwa <span class="bold">'+p.name+'</span></div>';
            html += '<div>Adres: <span class="bold">'+p.town+', '+p.postcode+', '+p.street+' '+p.buildingnumber+'</span></div>';
            html += '<div>Pozycja geograficzna: <span class="bold">'+p.latitude+'N - '+p.longitude+'E</span></div>';
            html += '<div>Obsługuje przesyłki pobraniowe: <span class="bold">'+(p.paymentavailable=='1'?'TAK':'NIE')+'</span></div>';
            html += '<div>Opis lokalizacji: <span class="bold">'+p.locationdescription+'</span></div>';
            if (p.paymentpointdescr != ""){ html+='<div>Opis punktu płatności: <span class="bold">'+p.paymentpointdescr+'</span></div><br />';}
            $("div.pakomato_machine_description").html(html);
        });

    },
    getPaczkomatyUser : function(obj){
        var $btn = $(obj);
        var $oldOne = $(".pakomato_user_link");
        $oldOne.hide();
        $btn.hide();
        $(".pakomato_loader3").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getPaczkomaty"},function(json){
            $(".pakomato_loader3").hide();
            if(json.result="ok")
            {
                $select = $("<select />").bind("change",function(){ $.pakomato.setNewUserPaczkomat(this); });
                $.each(json.list,function(idx,j){
                    $("<option />")
                    .val(j.name)
                    .text(j.name+" - "+j.town+" "+j.street+" "+j.buildingnumber)
                    .attr("title",j.locationdescription+(j.paymentpointdescr!=""?" , "+j.paymentpointdescr:""))
                    .appendTo($select);
                });
                $select.val($oldOne.attr("href")).appendTo($(".pakomato_user_paczkomat"));
            }else{
                $.pakomato.showJsonMessage(json);
                $oldOne.show();
                $btn.show();
            }
        });
    },
    setCod : function(obj){
        var $btn = $(obj);
        var $amount = $("input.pakomato_cod_amount");
        $(".pakomato_loader4").show();
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"setOrderCod",cod:$btn.attr("href")},function(json){
            $(".pakomato_loader4").hide();
            if(json.result=="ok"){
                if(json.newCodState)
                {
                    $btn.text("TAK").attr("href","true");
                    $(".pakomato_cod_amount").val(json.amount).show();
                }else{
                    $btn.text("NIE").attr("href","false");
                    $(".pakomato_cod_amount").val("").hide();
                }
            }
            $.pakomato.showJsonMessage(json);
        });
    },
    getPackDetails : function(obj){
        var $target = $("<div />").html("").addClass("pakomato_pack_info").appendTo($(obj)).bind("mouseleave click",function(event){ event.preventDefault(); event.stopPropagation(); $(this).hide("300",function(){ $(this).remove(); }); });
        $target.html('<img src="'+$.pakomato.loaderImg+'" alt="pracuję..." title="pracuję..." />');
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getPackInfo",packId:$(obj).attr("href")},function(json){
            $target.html("");
            var p = json.pack;
            var cMach = p.customer_machine;
            var sMach = "";
            if(p.selfsend>0)sMach = p.sender_machine;

            if(p.inpost_status != ""){
                $("<div />").html('<span class="label">Status przesyłki: </span><span class="pm_info">'+p.inpost_status+"</span>").appendTo($target); }
            $("<div />").html('<h3>Paczkomat klienta</h3><span class="label">Paczkomat: </span><span class="pm_info">'+cMach.name+" - "+cMach.town+", "+cMach.postcode+", "+cMach.street+" "+cMach.buildingnumber+"</span>").appendTo($target);
            $("<div />").html('<span class="label">Dodatkowe informacje: </span><span class="pm_info">'+cMach.locationdescription+". "+cMach.paymentpointdescr+"</span>").appendTo($target);
            $("<div />").html('<span class="label">Pozycja geograficzna: </span><span class="pm_info">'+cMach.longitude+"E, "+cMach.latitude+"N"+"</span>").appendTo($target);
            $target.append("<hr />");
            if(p.selfsend>0){
                $("<div />").html('<h3>Paczkomat nadawczy</h3><span class="label">Paczkomat: </span><span class="pm_info">'+sMach.name+" - "+sMach.town+", "+sMach.postcode+", "+sMach.street+" "+sMach.buildingnumber+"</span>").appendTo($target);
                $("<div />").html('<span class="label">Dodatkowe informacje: </span><span class="pm_info">'+sMach.locationdescription+". "+sMach.paymentpointdescr+"</span>").appendTo($target);
                $("<div />").html('<span class="label">Pozycja geograficzna: </span><span class="pm_info">'+sMach.longitude+"E, "+sMach.latitude+"N"+"</span>").appendTo($target);
                $target.append("<hr />");
                $("<div />").html('<span class="label">Kod nadania: </span><span class="pm_info">'+p.send_code+"</span>").appendTo($target);
            }
            $("<div />").html('<span class="label">Pobranie: </span><span class="pm_info">'+(p.cod>0?p.cod+"zł":"NIE")+"</span>").appendTo($target);
            $("<div />").html('<span class="label">Ubezpieczenie: </span><span class="pm_info">'+p.insurance_desc+"</span>").appendTo($target);
            $("<div />").html('<span class="label">Gabaryt: </span><span class="pm_info">'+p.size+" - "+p.size_desc+"</span>").appendTo($target);
        });
    },
    cancelJob : function(obj){
        $(obj).hide(300);
        var $toHide = $(obj).data("toHide");
        $toHide.hide(300);
        var $par = $(obj).parent();
        $par.append('<img src="'+$.pakomato.loaderImg+'" alt="pracuję..." title="pracuję..." />');
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"cancelJob",packId:$(obj).data("packId")},function(json){
            $.pakomato.showJsonMessage(json);
            $.pakomato.getPacks();
        });
    },
    genSticker : function(obj){
        $(obj).hide(300);
        var $toHide = $(obj).data("toHide");
        $toHide.hide(300);
        var $par = $(obj).parent();
        $par.append('<img src="'+$.pakomato.loaderImg+'" alt="pracuję..." title="pracuję..." />');
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"generateSticker",packId:$(obj).data("packId")},function(json){
            if(json.result=="ok"){
                $.pakomato.getPacks();
            }
            $.pakomato.showJsonMessage(json);
        });
    },
    getPacks : function(){
        var $create = $("#pakomato_create");
        var $cont = $("#pakomato_packs");
        var $tab = $cont.find(".zlecenia");
        var $loader = $(".pakomato_loaderMain");
        var $packs = $("#pakomato_packs");
        $packs.find(".pakomato_row, .button").remove();
        $loader.fadeIn(300);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"getPacks"},function(json){
            $loader.hide();            
            if(json.result=="ok" && json.packs.length > 0){
                $("#myTab span.badge").text(json.packs.length);
                $buttonRow = $("<tr />").appendTo($tab);
                $buttonCell = $("<td />").attr({colspan:"2"}).appendTo($buttonRow);
                $("<a />").text("Wyślij kolejną paczkę").attr("href","").addClass("button").appendTo($buttonCell).bind("click",function(event){
                    event.preventDefault();
                    if($create.hasClass("opened")){
                        $create.hide(300).removeClass("opened");
                        $(this).text("Wyślij kolejną paczkę");
                    }else{
                        $create.show(300).addClass("opened");
                        $(this).text("Zamknij formularz wysyłania paczki");
                    }
                });

                $create.hide();
                $packs.show();
                $.each(json.packs,function(idx,p){
                    var $row = $("<tr />").addClass("pakomato_row").appendTo($tab);
                    var $pack = $("<a />").text(p.track).attr("href",p.id).addClass("pakomato_pack pakomato_box").bind("click",function(event){ event.preventDefault(); $.pakomato.getPackDetails(this);});
                    $("<td />").append($pack).appendTo($row);
                    //var $status = $("<td />").appendTo($row);
                    //if (p.inpost_status!=""){
                    //    $("<span />").text('('+p.inpost_status+')').addClass("pakomato_status").appendTo($status);
                    //}                    
                    switch(p.status){
                        case "0":
                            $pack.addClass("created").attr("title",p.status_desc);
                            var $gen = $("<a />").text("Wygeneruj etykietę").addClass("button pakomato_pack_button").attr("href","").appendTo($row).bind("click",function(event){ event.preventDefault(); $.pakomato.genSticker(this); });
                            var $canc = $("<a />").text("Anuluj zlecenie").addClass("button pakomato_pack_button").attr("href","").data({packId:p.id}).appendTo($row).bind("click",function(event){ event.preventDefault(); $.pakomato.cancelJob(this); });
                            $gen.data({packId:p.id,toHide:$canc});
                            $canc.data({packId:p.id,toHide:$gen});
                            break;
                        case "1":
                            $pack.addClass("sticker").attr("title",p.status_desc);
                            $("<a />").text("Pobierz etykietę").addClass("button pakomato_pack_button").attr({target:"_blank",href:$.pakomato.stickersDirUrl+p.file}).appendTo($row);
                            break;
                        case "2":
                            $pack.addClass("canceled").attr("title",p.status_desc);
                            break;
                        case "3":
                            $pack.addClass("transit").attr("title",p.status_desc);
                            break;
                        case "4":
                            $pack.addClass("delivered").attr("title",p.status_desc);
                            break;
                    }
                });
            }else{                
                $create.show();
            }
        });
    },
    bindEvents : function(){
        $(".pakomato_user_link").bind("click",function(event){ event.preventDefault(); $.pakomato.switchUserPaczkomat(this) });
        $(".pakomato_user_details_close").bind("click",function(event){ event.preventDefault(); $(".pakomato_user_link").click(); });
        $(".pakomato_send_machine_link").bind("click",function(event){ event.preventDefault(); $.pakomato.getPaczkomatInfo(this); })
        $("#pakomato_selfsend").bind("click",function(event){ event.preventDefault(); $.pakomato.switchSelfsend(this); });
        $("#pakomato_insurance_selected").bind("click",function(event){ event.preventDefault(); $.pakomato.getInsurances(this); });
        $(".pakomato_change_user_machine").bind("click",function(event){ event.preventDefault(); $.pakomato.getPaczkomatyUser(this); });
        $(".pakomato_change_sender_machine").bind("click",function(event){ event.preventDefault(); $.pakomato.getPaczkomatySender(this); });
        $("#pakomato_cod_selected").bind("click",function(event){ event.preventDefault(); $.pakomato.setCod(this); });
        $(".pakomato_cod_update").bind("click",function(event){ event.preventDefault(); $.pakomato.updateCodAmount(); });
        $(".button.pmCreatePackage").bind("click",function(event){ event.preventDefault(); $.pakomato.createPackage(); });
        $("#pakomato_size").bind("click",function(event){ event.preventDefault(); $.pakomato.getSizes(this); });
        $("#pakomato_phone").bind("click",function(event){ event.preventDefault(); $.pakomato.phoneChange(this); });
        $("#pakomato_label").bind("click",function(event){ event.preventDefault(); $.pakomato.switchOrderLabelType(this); })
    },
    createPackage : function(){
        $("#pakomato_create").hide(300);
        $(".pakomato_loaderMain").show(300);
        $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"createPackage"},function(json){		
            if(json.result=="ok"){
                $.pakomato.getPacks();
            }else{
                $("#pakomato_create").show(300);
                $(".pakomato_loaderMain").hide(300);
            }
            $.pakomato.showJsonMessage(json);
        });
    },
    updateCodAmount : function(){
        var am = parseFloat($("input.pakomato_cod_amount").val());
        if(am > 0){
            $(".pakomato_loader4").show();
            $.post($.pakomato.ajaxUrl,{pm_ajax:true,action:"updateOrderCodAmount",amount:am},function(json){
                $(".pakomato_loader4").hide();
                $.pakomato.showJsonMessage(json);
            });
        }else{
            $.pakomato.showJsonMessage({result:"error",message:"Kwota musi być większa od 0"})
        }
    },
    showJsonMessage : function(json){
        if(json.message!=""){
            if(json.result=="ok"){
                $("#pakomato_message").removeClass("red").addClass("green").html(json.message).fadeIn(300).delay($.pakomato.messageDelay*1000).fadeOut(300,function(){ $(this).text('').removeClass('green'); });
            } else if(json.result=="error"){
                $("#pakomato_message").removeClass("green").addClass("red").html(json.message).fadeIn(300).delay($.pakomato.messageDelay*1000).fadeOut(300,function(){ $(this).text('').removeClass('red'); });
                console.debug(json);
            }
        }	
    },
    init : function(){
        $.pakomato.bindEvents();
        if($("#pakomato_selfsend").attr("href")=="false")$(".pakomato_sender").hide();
        if($("#pakomato_cod_selected").attr("href")=="false")$(".pakomato_cod_amount").hide();        
        $.pakomato.getPacks();
    },
    switchOrderLabelType:function(obj){
        $.post($.pakomato.ajaxUrl,{ pm_ajax:true,action:"switchOrderLabelType" },function(json){
            $(obj).html(json.data.newSize);
            $.pakomato.showJsonMessage(json);
        });
    }
}