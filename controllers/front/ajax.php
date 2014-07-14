<?php
class PakomatoAjaxModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{        
        $this->module->ajaxFrontPostProcess();
	}
}
?>