<script type="text/javascript" src="{$js_url}"></script>
<link rel="stylesheet" href="{$css_url}" type="text/css" />
<div class="pmMessage"></div>
<fieldset class="PakoMato">
	<!-- KONTO UŻYTKOWNIKA-->
	<legend><img src="../img/admin/cog.gif" alt="" class="middle" />Ustawienia modułu PakoMato</legend>
    {if $license_message!=""}<div class="pmLicense">{$license_message}</div>{/if}
	{if $check==""}    
    <a href="ogolne" class="pm_button active">Ustawienia ogólne</a>
    <a href="nadawca" class="pm_button">Dane nadawcy</a>    
    <a href="zaawansowane" class="pm_button">Ustawienia zaawansowane</a>
    
    <div class="pm_tab zaawansowane">
        <div class="PakoMatoConf pn_message">
            <label>Komunikat gdy brak nr telefonu:</label>&nbsp;<input type="text" id="pnMessage" value="{$np_message}" /> <a href="" class="pmSmallBtn">Zapisz</a>&nbsp;
            <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
        </div>
        <div class="PakoMatoConf pmCarriersSelector">
            <label>Selektor listy kurierów:</label>&nbsp;<input type="text" id="pmCarriersListSelector" value="{$carriers_selector}" /> <a href="" class="pmSmallBtn">Zapisz</a>&nbsp;
            <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
        </div>
        <div class="PakoMatoConf pmPaymentsSelector">
            <label>Selektor listy płatności OPC:</label>&nbsp;<input type="text" id="pmPaymentsListSelector" value="{$payments_selector}" /> <a href="" class="pmSmallBtn">Zapisz</a>&nbsp;
            <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
        </div>
        <div class="PakoMatoConf pmButtonSelector">
            <label>Selektor przycisku 'dalej' (5 kroków):</label>&nbsp;<input type="text" value="{$button_selector}" /> <a href="" class="pmSmallBtn">Zapisz</a>&nbsp;
            <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
        </div>
        <div class="PakoMatoConf pmClearCache">
            <label>Cache listy paczkomatów:</label><a href="" class="pmSmallBtn">Odśwież listę paczkomatów i cennik</a>&nbsp;
            <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
        </div>
        <div class="PakoMatoConf pmForceUpdate">
            <label>Wywołaj skrypt aktualizacji:</label><input type="text" value="" /> <a href="" class="pmSmallBtn">Wykonaj</a>&nbsp;
            <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
        </div>
        <div>&nbsp;</div>
    </div>
    
    <div class="pm_tab nadawca">
        <form action="javascript:false" method="post" id="pmNadForm">
            <div class="PakoMatoConf">
                <label>Imię:</label>&nbsp;<input type="text" id="pmImie" />
            </div>
            <div class="PakoMatoConf">
                <label>Nazwisko:</label>&nbsp;<input type="text" id="pmNazwisko" />
            </div>            
            <div class="PakoMatoConf">
                <label>Numer telefonu:</label>&nbsp;<input type="text" id="pmTelefon" />
            </div>
            <div class="PakoMatoConf">
                <label>Ulica bez numeru domu:</label>&nbsp;<input type="text" id="pmUlica" />
            </div>
            <div class="PakoMatoConf">
                <label>Numer domu:</label>&nbsp;<input type="text" id="pmDom" />
            </div>
            <div class="PakoMatoConf">
                <label>Numer mieszkania:</label>&nbsp;<input type="text" id="pmMieszkanie" />
            </div>
            <div class="PakoMatoConf">
                <label>Miasto:</label>&nbsp;<input type="text" id="pmMiasto" />
            </div>
            <div class="PakoMatoConf">
                <label>Kod Pocztowy:</label>&nbsp;<input type="number" min="00" max="99" id="pmKod1" />-<input type="number" min="000" max="999" id="pmKod2" />
            </div>
            <div class="PakoMatoConf">
                <label>Województwo:</label>&nbsp;<input type="text" id="pmWojewodztwo" />
            </div>
            <a class="pmNadZapisz pmBigBtn" href="">Zapisz dane nadawcy</a>
        </form>
    </div>
            
    <div class="pm_tab ogolne active">
        
		<div class="PakoMato">
			<label>Konto nadawcze:</label>
				Login:<input type="text" title="Login do konta używanego do nadawania paczek w usłudze Paczkomaty 24/7" id="pmLogin" value="{$login}" />&nbsp;
				Hasło:<input type="password" title="Hasło do konta używanego do nadawania paczek w usłudze Paczkomaty 24/7" id="pmPass" value="{$haslo}" />&nbsp;
				<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" id="ajaxLoader1" />
                <a href="" id="pmSaveLogin" class="pmSmallBtn pmKontoZapisz">Zapisz login i hasło</a><br />
		</div>
        <div class="PakoMatoConf" id="pmMessageTime">
			<label>Czas wyświetlania komunikatów</label>&nbsp; 
            <a href="1" class="pmChange pmMessageTime time1">1s</a>
            <a href="2" class="pmChange pmMessageTime time2">2s</a>
            <a href="3" class="pmChange pmMessageTime time3">3s</a>
            <a href="4" class="pmChange pmMessageTime time4">4s</a>
            <a href="5" class="pmChange pmMessageTime time5">5s</a>
            
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
		</div>
		<!-- WYBRANY PACZKOMAT -->
		<div class="pmLoader ajaxLoader2"><label>Ładowanie danych</label> <img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader ajaxLoader2" /></div>
		<div id="pmPaczkomaty" class="PakoMatoConf">
			<label>Twój Paczkomat nadawczy:</label>&nbsp;<a href="" id="pmSelectedMachine" class="pmChange" title="kliknij aby zmienić Paczkomat do wysyłki"></a><span id="paczkomatSearch" class="pmHidden"></span><br />
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader ajaxLoader3" />
		</div>
		<div class="PakoMatoConf">
			<label>Domyślny gabaryt przesyłki:</label>&nbsp;
			<a href="" id="pmSize" class="pmChange" title="kliknij aby zmienić domyślny gabaryt"></a>
			<span id="pmSizeSelect" class="pmHidden"></span>
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader ajaxLoader4" />
		</div>
		<div class="PakoMatoConf">
			<label>Domyślne ubezpieczenie przesyłki:</label>&nbsp;
			<a href="" id="pmInsurance" class="pmChange" title="kliknij aby zmienić domyślne ubezpieczenie"></a>
			<span id="pmInsuranceSelect" class="pmHidden"></span>
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader ajaxLoader5" />
		</div>
		<div class="PakoMatoConf" id="pmSelfsend">
			<label>Domyślnie wysyłam w paczkomacie: </label>&nbsp; <a href="" class="pmChange pmSelfsend"></a>
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader ajaxLoader6" />
		</div>        
        <div class="PakoMatoConf" id="pmLabelSise">
			<label>Domyślniy format etykiety: </label>&nbsp; <a href="" class="pmChange pmLabelSize"></a>
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
		</div>
        {if $presta15}
        <div class="PakoMatoConf" id="pmRefType">
			<label>Na etykiecie w polu referencja: </label>&nbsp; 
            <a href="" class="pmChange ident">Numer zamówienia</a>
            <a href="" class="pmChange index">Indeks zamówienia</a>
			<img src="{$moduleDir}/img/ajax-loader.gif" title="pracuję..." alt="pracuję..." class="pmLoader" />
		</div>
        {/if}
		<div class="PakoMatoConf" id="pmBindedCarriers">
			<label>Wybierz powiązane metody wysyłki:</label>&nbsp;
		</div>
		<div class="PakoMatoConf" id="pmBindedCod">
			<label>Wskaż płatności pobraniowe:</label>&nbsp;
		</div>
        {if $showReqPhone}
        <div class="PakoMatoConf" id="pmPhoneReq">
			<label>Wyłączona opcja 'Wymagany telefon':</label>&nbsp; <a href="" class="pmSmallBtn">Włącz ją teraz</a>			
		</div>
        {/if}
   </div>		
	{else}
		<h3 class="red">{$check}</h3>
	{/if}
</fieldset>
<script type="text/javascript">
$.pakomato.ajaxUrl="?{$ajax_url}";
$.pakomato.sslEnabled={$ssl_enabled};
$.pakomato.init();
</script>