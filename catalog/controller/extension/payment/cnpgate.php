<?php
class ControllerExtensionPaymentCnpGate extends Controller {
	public function index() {
		$this->load->language('extension/payment/cnpgate');


		$data['text_wait'] = $this->language->get('text_wait');

		$data['button_confirm'] = $this->language->get('button_confirm');


		return $this->load->view('extension/payment/cnpgate', $data);
	}

	
	public function send() {
	
		$endpointId = $this->config->get('cnpgate_login');
		$merchantControl = $this->config->get('cnpgate_key');
	
	
		if ($this->config->get('cnpgate_server') == 'live') {
			$turl = 'https://gate.debitunit.com/paynet/api/v2/sale-form/'.$endpointId ;
		} elseif ($this->config->get('cnpgate_server') == 'test') {
			$turl = 'https://sandbox.debitunit.com/paynet/api/v2/sale-form/'.$endpointId ;
		}


		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']); 
		
		
		

			
	$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false) * 100;

	$control = SHA1($endpointId . $this->session->data['order_id'] . $amount . $order_info['email'] . $merchantControl);

	$requestFields = array(
    'client_orderid' => $this->session->data['order_id'], 
    'order_desc' => html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'), 
    'first_name' => html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8'), 
    'last_name' => html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8'), 
    'ssn' => '', 
    'birthday' => '', 
    'address1' => html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8'), 
    'city' => html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8'), 
    'state' => substr (html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8'),0,4) , 
    'zip_code' => html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8'), 
    'country' => substr(html_entity_decode($order_info['payment_iso_code_2'], ENT_QUOTES, 'UTF-8'),0,2), 
    'phone' => $order_info['telephone'], 
    'cell_phone' => $order_info['telephone'], 
    'amount' => $this->currency->format($order_info['total'], $order_info['currency_code'], 1.00000, false), 
    'email' => $order_info['email'], 
    'currency' => $this->session->data['currency'], 
    'ipaddress' => $this->request->server['REMOTE_ADDR'], 
    'site_url' => html_entity_decode($order_info['payment_company'], ENT_QUOTES, 'UTF-8'), 
    'destination' => '', 
    'redirect_url' => $this->url->link('extension/payment/cnpgate/callback', '', true),  
    'server_callback_url' => $this->url->link('extension/payment/cnpgate/callback', '', true), 
    'merchant_data' => 'VIP customer', 
    'control' => $control
);

//    'redirect_url' => $this->url->link('checkout/success', '', true),  
//$responseFields = sendRequest($turl, $requestFields);	



 $curl = curl_init($turl);

    curl_setopt_array($curl, array
    (
        CURLOPT_HEADER         => 0,
        CURLOPT_USERAGENT      => 'DebitUnit-Client/1.0',
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST           => 1,
        CURLOPT_RETURNTRANSFER => 1
    ));

    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($requestFields));

    $response = curl_exec($curl);

    if(curl_errno($curl))
    {
        $error_message  = 'Error occurred: ' . curl_error($curl);
        $error_code     = curl_errno($curl);
    }
    elseif(curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200)
    {
        $error_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error_message  = "Error occurred. HTTP code: '{$error_code}'";
    }

    curl_close($curl);

    if (!empty($error_message))
    {
        throw new RuntimeException($error_message, $error_code);
    }

    if(empty($response))
    {
        throw new RuntimeException('Host response is empty');
    }

    $responseFields = array();

    parse_str($response, $responseFields);
	

	$json = array();

		
	if (isset($responseFields['error-message'])){$json['error'] = $responseFields['error-message'];}
		else{$json['redirect'] = $responseFields['redirect-url'];}
	$this->response->addHeader('Content-Type: application/json');
	$this->response->setOutput(json_encode($json));

}
	
	
		public function callback() {
/*Array ( [error_message] => [processor-tx-id] => PNTEST-644893 
[merchant_order] => 32 
[orderid] => 644893 
[client_orderid] => 32 
[bin] => 411111 
[control] => 7f3e62a3fac057f5e18f6bd0f0822150feb1ae8a 
[gate-partial-reversal] => enabled [descriptor] => test.gate 
[gate-partial-capture] => enabled [type] => sale 
[card-type] => VISA [merchantdata] => VIP customer [phone] => 34534534 [last-four-digits] => 1111 [card-holder-name] => test tess 
[status] => approved )		
	*/

	
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']); 
		$merchantControl = $this->config->get('cnpgate_key');

		$return_control = sha1($_POST['status'].$_POST['orderid'].$_POST['client_orderid'].$merchantControl);
		

	$json = array();

if (isset($_POST['control'])){
	$status =  $_POST['status'];
	if ($_POST['control'] == $return_control){
		if($status == 'approved' ){
				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'),$status, false);
				$this->response->redirect($this->url->link('checkout/success', '', true));
		}
		else{
			
				$data['continue'] = $this->url->link('checkout/cart');

				$data['column_left'] = $this->load->controller('common/column_left');
				$data['column_right'] = $this->load->controller('common/column_right');
				$data['content_top'] = $this->load->controller('common/content_top');
				$data['content_bottom'] = $this->load->controller('common/content_bottom');
				$data['footer'] = $this->load->controller('common/footer');
				$data['header'] = $this->load->controller('common/header');
				$this->response->setOutput($this->load->view('extension/payment/cnpgate_failure', $data));
				
				$data['reason'] =  $_POST['error_message'];
				
			
		}
		
	}
	else {
		
		
				$data['continue'] = $this->url->link('checkout/cart');

				$data['column_left'] = $this->load->controller('common/column_left');
				$data['column_right'] = $this->load->controller('common/column_right');
				$data['content_top'] = $this->load->controller('common/content_top');
				$data['content_bottom'] = $this->load->controller('common/content_bottom');
				$data['footer'] = $this->load->controller('common/footer');
				$data['header'] = $this->load->controller('common/header');
				$this->response->setOutput($this->load->view('extension/payment/cnpgate_failure', $data));
				
				$data['reason'] =  $_POST['error_message'];

	}
	

}


	
		}

	
	

}