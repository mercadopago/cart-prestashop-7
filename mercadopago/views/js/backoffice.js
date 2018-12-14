/**
*  @author    Mercado pago <dx@mercadopago.com>
*  @copyright modulos 2018
*  @license   GNU General Public License version 2
*  @version   1.1

*  www.mercadopago.com.br
*
* Languages: EN, PT
* PS version: 1.7
*
**/
$(document).ready(function(){
	$('.mercadopago-tabs nav .tab-title').click(function(){
		var elem = $(this);
		var target = $(elem.data('target'));
		elem.addClass('active').siblings().removeClass('active');
		target.show().siblings().hide();
	})

	if ($('.mercadopago-tabs nav .tab-title.active').length == 0){
		$('.mercadopago-tabs nav .tab-title:first').trigger("click");
	}

	$('[data-toggle="tooltip"]').tooltip();

	var list_payment = [
		'visa',
		'master',
		'hipercard',
		'amex',
		'diners',
		'elo',
		'melicard',
		'bolbradesco',
	];
});