<?php
function smarty_block_paynl_banks($params, $content, &$smarty, &$repeat)
{	$repeat = false;
	$processor_data = fn_get_processor_data($_SESSION['cart']['payment_id']);

	if($processor_data['processor_params']['optionId'] == 10 && $processor_data['processor_params']['issuersOption'] == 'show') {
		require_once (DIR_ROOT.'/app/addons/paynl_addon/func.php');
                $banks = fn_get_ideal_banks($processor_data);
                
                $banksHtml = "<select name='paymentOptionbSubId' >";
                $banksHtml .= "<option value=''>Kies uw bank...</option>";
                foreach($banks as $bank){
                    $banksHtml .= "<option value='".$bank['id']."'>".$bank['name']."</option>";
                }
                $banksHtml .= "</select><br /><br /><br /><br />";				
                
                return $banksHtml;
	} else {
		return '';
	}

}
?>