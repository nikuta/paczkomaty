<script type="text/javascript" src="{$js_url}"></script>
<link rel="stylesheet" type="text/css" href="{$css_url}" />
<style type="text/css">
	
</style>
<br />
{if isset($presta16)}
<div class="pakomato presta16">
{else}
<fieldset class="pakomato {if isset($presta14)}presta14{/if}">
	<legend>PakoMato - Integracja z Paczkomaty24/7</legend>
{/if}
	{if $check}
		<h2 id="pakomato_message"></h2>       
		<img class="pmLoader pakomato_loaderMain" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." />
		<div id="pakomato_create">
            <table>
                <tr>
                    <td class="label">Paczkomat wybrany przez klienta:</td>
                    <td>
                        <div id="pakomato_user">
                            <div class="pakomato_user_paczkomat">					
                                &nbsp;<a href="{$paczkomat.name}" class="pakomato_user_link pakomato_box" title="kliknij aby zobaczyć więcej">{$paczkomat.name}</a>
                                &nbsp;<a href="" class="button pakomato_change_user_machine">Zmień</a>
                                <img class="pmLoader pakomato_loader3" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." /></div>                            
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div class="pakomato_user_details">
                            <div class="pakomato_machine_description">
                                <div>Nazwa <span class="bold">{$paczkomat.name}</span></div>
                                <div>Adres: <span class="bold">{$paczkomat.town}, {$paczkomat.postcode}, {$paczkomat.street} {$paczkomat.buildingnumber}</span></div>
                                <div>Pozycja geograficzna: <span class="bold">{$paczkomat.latitude}N - {$paczkomat.longitude}E</span></div>
                                <div>Obsługuje przesyłki pobraniowe: <span class="bold">{if $paczkomat.paymentavailable==1}TAK{else}NIE{/if}</span></div>
                                <div>Opis lokalizacji: <span class="bold">{$paczkomat.locationdescription}</span></div>
                                {if $paczkomat.paymentpointdescr != ""}<div>Opis punktu płatności: <span class="bold">{$paczkomat.paymentpointdescr}</span></div>{/if}
                            </div>
                            <br /><a href="" class="pakomato_user_details_close button">Zamknij</a><br />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="label">Telefon użytkownika:</td>
                    <td><div class="pakomato_customer_phone"> <a href="" id="pakomato_phone" class="pakomato_box">{$phone}</a><img class="pmLoader pakomato_loader6" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." /></div></td>
                </tr>
                <tr>
                    <td class="label">Gabaryt:</td>
                    <td><a href="{$size.code}" id="pakomato_size" class="pakomato_box">{$size.desc}</a><img class="pmLoader pakomato_loader5" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." /></td>
                </tr>
                <tr>
                    <td class="label">Typ generowanych etykiet:</td>
                    <td><a href="" id="pakomato_label" class="pakomato_box">{$etykieta}</a></td>
                </tr>
                <tr>
                    <td class="label">Wysyłka w paczkomacie:</td>
                    <td><a href="{$selfsend}" id="pakomato_selfsend" class="pakomato_box">{if $selfsend=="true"}TAK{else}NIE{/if}</a></td>
                </tr>
                <tr class="pakomato_sender">
                    <td class="label">Twój paczkomat do wysyłki:</td>
                    <td>                        
                        <div class="pakomato_sender_paczkomat">					
                            <a href="{$doWys.name}" class="pakomato_box pakomato_send_machine_link">{$doWys.name}</a>
                            &nbsp;<a href="" class="button pakomato_change_sender_machine">Zmień</a>
                            <img class="pmLoader pakomato_loader1" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." />
                        </div>
                    </td>
                </tr>
                <tr class="pakomato_sender">
                    <td colspan="2"><div class="pakomato_send_machine_info"></div></td>
                </tr>
                <tr>
                    <td class="label">Ubezpieczenie:</td>
                    <td><div class="pakomato_insurance"><a href="{$insurance.value}" id="pakomato_insurance_selected" class="pakomato_box">{$insurance.desc}</a>&nbsp;<img class="pmLoader pakomato_loader2" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." /></div></td>
                </tr>
                <tr>
                    <td class="label">Pobranie:</td>
                    <td><a href="{$cod}" id="pakomato_cod_selected" class="pakomato_box">{if $cod=="true"}TAK{else}NIE{/if}</a></td>
                </tr>
                <tr class="pakomato_cod_amount"> 
                    <td class="label">Kwota pobrania (zł):</td>
                    <td><span class="pakomato_cod_amount">&nbsp;<input type="text" class="pakomato_cod_amount" value="{$cod_amount}" />&nbsp;<a href="" class="button pakomato_cod_update">Aktualizuj kwotę</a></span><img class="pmLoader pakomato_loader4" src="{$moduleDir}/img/ajax-loader.gif" alt="pracuję..." title="pracuję..." /></td>
                </tr>
                <tr class="pmCreatePackage">
                    <td colspan="2" class="center"><a href="" class="pmCreatePackage button">WYGENERUJ PACZKĘ NA PODSTAWIE POWYŻSZYCH DANYCH</a></td>
                </tr>
            </table>			            													
		</div><br />
		<div id="pakomato_packs">
			<h3>Wygenerowane paczki - kliknij na numer paczki aby wyświetlić więcej szczegółów.</h3>
            <table class="zlecenia"></table>
		</div>        
	{else}
		<h3 class="red">{$check_message}</h3>
	{/if}

{if isset($presta16)}
</div>
{else}
    </fieldset>
{/if}
<script type="text/javascript">
    $.pakomato.ajaxUrl="?{$ajax_url}";
    $.pakomato.loaderImg="{$moduleDir}img/ajax-loader.gif";
    $.pakomato.stickersDirUrl="{$moduleDir}stickers/";
    $.pakomato.messageDelay={$msgDelay};
    $.pakomato.init();
</script>