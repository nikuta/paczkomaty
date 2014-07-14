{if isset($presta14) && $presta14}
    <td colspan="4" class="presta14">
{/if}
<div id="pakomato">
	<h2 class="description">Opcje paczkomatu</h2>
	<h2 id="pakomato_message"></h2>
    <table>
        <tr>
            <td colspan="2"><span>Paczkomat do którego chcesz otrzymać przesyłkę: </span></td>
        </tr>
        <tr id="pakomato_machine">
            <td class="inputs">
                <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
                <span class="pakomato_selected normal" title="{$najblizszy.locationdescription}. {$najblizszy.paymentpointdescr}" id="pakomato_machine_selected">{$najblizszy.name} - {$najblizszy.town}, {$najblizszy.postcode}, {$najblizszy.street} {$najblizszy.buildingnumber}</span>
                <span class="pakomato_selected cod" title="{$najblizszy_cod.locationdescription}. {$najblizszy_cod.paymentpointdescr}" id="pakomato_machine_selected_cod">{$najblizszy_cod.name} - {$najblizszy_cod.town}, {$najblizszy_cod.postcode}, {$najblizszy_cod.street} {$najblizszy_cod.buildingnumber}</span>
            </td>
            <td class="buttons">
                <a href="{$najblizszy.name}" class="button_small" id="pakomato_change_machine"> Zmień</a>
            </td>
        </tr>
        <tr>
            <td colspan="2"><span>Telefon, na który będą wysyłane powiadomienia o statusie przesyłki: </span></td>
        </tr>
        <tr id="pakomato_phone">
            <td class="inputs">                
                <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
                <span class="pakomato_selected">{$phone}</span>
            </td>
            <td class="buttons"><a href="" class="button_small" id="pakomato_change_phone"> Zmień</a></td>
        </tr>
    </table>	
    <div class="pm_NoPhoneMsg">{$np_message}</div>
</div>
{if isset($presta14) && $presta14}
    </td>
{/if}
{literal}
<style type="text/css">
	#pakomato			{ margin-top: 10px; display: none; border: solid 1px #aaa; border-radius: 10px; padding: 10px; position: relative;}
	#pakomato_message	{ display: none; padding: 10px; position: absolute; border-radius: 5px; top: -10px; width: 650px; text-align: center; }
	#pakomato_message.green		{ color: green; border: solid 1px green; background: #eeffee;}
	#pakomato_message.red		{ color: red; border: solid 1px red; background: #ffeeee;}
	#pakomato select	{ width: 300px; margin-right: 10px;}
    #pakomato table { width: 100%; }
    #pakomato table td { border: none; text-align: center; padding: 5px;}
    #pakomato table td.inputs { width: 70%; text-align: right; }
    #pakomato table td.buttons { width: 30%; text-align: left; }
    #pakomato .button_small { float: left; }
	#pakomato_phone input	{ border: solid 1px #090; height: 20px; margin-right: 10px;}
	#pakomato_phone input.red	{ color: red; border-color: red; }
    #pakomato h2.description { background: none; text-align: center; }
	.pakomato_selected	{ font-size: 1.2em; font-weight: bold; line-height: 1em; padding: 5px; border: solid 1px #aaa; background: #eee; border-radius: 10px; margin-right: 10px;}
	a.pakomatoUpdatePhone	{ margin: 0 10px; }
	.pmLoader			{ display: none; }
    .pmHidden           { display: none; }
    .pm_NoPhoneMsg  { padding: 0.5em; color: red; font-weight: bold; display: none;}
    
    /* Prestashop 1.4.x styless */    
    .presta14 #pakomato { padding: 2px; }
    .presta14 #pakomato_message { width: 510px; }
    .presta14 .pakomato_selected { font-size: 10px; }
    
</style>
{/literal}
<script type="text/javascript" src="{$module_dir}js/carriersList.js"></script>
<script type="text/javascript">
$.pakomato.ajaxPath = "{$ajax_url}";
$.pakomato.carrierListSelector = "{$carrier_selector}";
$.pakomato.paymentListSelector = "{$payment_selector}";
$.pakomato.buttonSelector = "{$button_selector}";
$.pakomato.config = {$config};
$.pakomato.noPhoneMessage = "{$np_message}";
$.pakomato.opc = {$opc};
$.pakomato.init();
</script>