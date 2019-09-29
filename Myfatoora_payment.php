<?php 
/*
         Manuigniter
        WWW.menu.house
*/
class Myfatoora_payment extends CI_Controller{
 function __construct()
 {
       parent::__construct();
      $this->load->model('Branches_model');
 }  
 /*

*/
/* do payment */
public function do_payment($cart_order_id)
{
	$this->load->model('cart_order_model');
	$this->load->model('Cart_item_model');
	$this->load->library('encryption');
	$this->load->model('Cust_address_model');
	$this->load->model('Product_model'); 
	$this->load->model('Product_choices_model'); 
	// echo $cart_order_id;
      $cart_order_id = strtr($cart_order_id, array('.' => '+', '-' => '=', '~' => '/'));
      //echo $cart_order_id;
      $cart_order_id=$this->encryption->decrypt($cart_order_id); 
     // echo $cart_order_id."<<<<<<<<<<<";
	  $cart_item= $this->Cart_item_model->get_all_cart_itembycartid($cart_order_id);
  	  $cart_order = $this->cart_order_model->get_cart_order($cart_order_id);
	  $order_items=array();
      $pro_data=array();
    //  echo $cart_order_id;
      if( $cart_order['payment_id']!=null)
      {
           if( $cart_order['payment_links']!=null)
            {
                $url_json=json_decode($cart_order['payment_links'],true);
               // var_dump( $url_json); 
                 if(isset($url_json['RedirectUrl']))
                {
                    //echo "if";
                    //echo $url_json['RedirectUrl'];
                    redirect($url_json['RedirectUrl']);
                }   
                else {
                       $this->session->set_flashdata('alertmsg', '<div class="alert alert-danger text-center">'.$this->mhlanguage->setvaluebasedonlang('try_again_link_not_gen_or_expired','Try again Payment Link Not generate or expired').'</div>');
                      redirect('checkout');
                    // show_error('Something error. please try again later.');    
                }      
            }
         
      }
      else {
             $cust_address = $this->Cust_address_model->get_cust_address($cart_order['address_id']);
		foreach($cart_item as $ci)
		{
			$product= $this->Product_model->get_product($ci['product_id']);
		//	$pro_data[]=$this->Product_model->get_productforpg($ci['product_id']);
			if($product!=null)
			{ 
			} 
			$re=array(); 
			$a=array();
            $product_name=$product['product_name'];
            $product_id=$product['product_id'];
			if($ci['product_or_pro_choice']=="pro_choice" && $ci['p_choice_id']!=null)
			{
				  $product_choices = $this->Product_choices_model->get_product_choices($ci['p_choice_id']);
				  if($product_choices!=null)
				  {
                        $product_name=$product_name."(".$product_choices['choiceName']."-".$product_choices['p_color'].")";
                        $product_id=$ci['p_choice_id'];
				  }
				
			}
            $a['ProductId']=null;
            $a['ProductName']=$product_name.""; 
            $a['Quantity']=$ci['quantity']; 
            $a['UnitPrice']=($ci['cart_price']/$ci['quantity'])+$ci['adones_json_tot_price'];
            $pro_data[]=$a;
            if($ci['adones_json_tot_price']!=null)
            {

            }
			/* $order_items['image']=$product['image'];
			$order_items['name']=$product['product_name'];
			$order_items['qty']=$ci['qty'];
			$order_items['sub_total']=$ci['cart_price'];*/
			$ci['product']=$product;
			$order_items[] =$ci;
		}
             $a=array();
            $a['ProductId']=null;
            $a['ProductName']="Service Charge";
            $a['Quantity']="1";
            $a['UnitPrice']=$cart_order['service_charge']; 
            
             $pro_data[]=$a;
             if( $cart_order['is_promocode']==1)
             {
                 $a=array();
                $a['ProductId']=null;
                $a['ProductName']="Discount Charge ".$cart_order['promo_code_percentage']."%";
                $a['Quantity']="1";
                $a['UnitPrice']="-".$cart_order['promo_code_dic_amount']; 
                
                $pro_data[]=$a;
             }
              $a=array();
            $a['ProductId']=null;
            $a['ProductName']="Delivery Charge";
            $a['Quantity']="1"; 
            $a['UnitPrice']=$cart_order['delivery_charge']; 
			$pro_data[]=$a;
           
             
		$data['cart_order']['cart_items']=$order_items; 
      //https://www.formget.com/curl-library-codeigniter/ 
        //echo $url;
       $customer_arr=array(
				'customer_name'=>$cart_order['fullname'],
				'email'=>$cart_order['email'],
				'mobile'=>$cart_order['phone'],
				'gender'=>'',
				'dob'=>'',
				'civilid_no'=>'',
				'city'=>$cust_address['city_id'],
				'block'=>$cust_address['block'],
				'street'=>$cust_address['street'],
				'avenue'=>$cust_address['jadda'],
				'building'=>$cust_address['houe_no'],
				'floor'=>$cust_address['floor'],
				'apartment'=>$cust_address['apartment'], 
			); 
			$product_arr=$pro_data;
			$customer_data=json_encode($customer_arr);
			$product_data=json_encode($product_arr); 
			$return_url=site_url('mf-success/'.$cart_order_id);
			$error_url=site_url('mf-error/'.$cart_order_id);
             $total_product=$cart_order['sum_total'];
            // echo 60*MYFAT_INVOICE_EXPIRY_MINUTE;
             $currentDate = strtotime(DATE_TIME);
             $futureDate = $currentDate+(60*MYFAT_INVOICE_EXPIRY_MINUTE);
            $ExpireDate = date("Y-m-d H:i:s", $futureDate);
            $ExpireDate= $ExpireDate;
           // echo DATE."T".TIME;
           // $ExpireDate = date('Y-m-d H:i:s', strtotime("+5 min"));
            $ExpireDate="2022-12-31T13:30:17.812Z";
			$merchantData = array(
					'InvoiceValue'=>$total_product,
                    'CustomerName'=>$customer_arr['customer_name'],
                    'CustomerBlock'=>$customer_arr['block'],
                    'CustomerStreet'=>$customer_arr['street'],
                    'CustomerHouseBuildingNo'=>$customer_arr['building'],
                    'CustomerCivilId'=>$customer_arr['civilid_no'],
                    'CustomerAddress'=>'City:'.$customer_arr['city'].',Avenue:'.$customer_arr['avenue'].'Building: ,'.$customer_arr['building'].',Floor: '.$customer_arr['floor'],
					'CustomerReference'=>$cart_order['customer_id'],
					'DisplayCurrencyIsoAlpha'=>MYFAT_DISPLAY_CURRENCY_CODE_ALPHA,
                    'CountryCodeId'=>MYFAT_COUNTRY_CODE,
                    'CustomerMobile'=>$customer_arr['mobile'],
                    'CustomerEmail'=>$customer_arr['email'],
                    'DisplayCurrencyId'=>MYFAT_DISPLAY_CURRENCY_ID,
                    'SendInvoiceOption'=>MYFAT_SENT_INVOICE_OPTION, 
                    'InvoiceItemsCreate'=>$product_arr,
                    'CallBackUrl'=>$return_url, 
					'Language'=>MYFAT_LANGUAGE,
					'ExpireDate'=>$ExpireDate,
                    'ApiCustomFileds' => 'weight=10,size=L,lenght=170',  
                    'ErrorUrl'=>$error_url, 
                );  
               // echo "<pre>",var_export($merchantData,true)."</pre>";
         try {  
            $post_string=json_encode($merchantData); 
           // echo strlen($post_string);
           // $product_data=json_encode($product_arr); 
           // echo  $post_string;
           // echo $product_data;
            $access_token_json=$this->checktoken();
          //  echo $access_token_json;
          //var_dump($access_token_json);
            if(isset($access_token_json['access_token']) && !empty($access_token_json['access_token']))
            {           
                $access_token= $access_token_json['access_token'];       
            }else{         
                $access_token='';       
            }       
            if(isset($access_token_json['token_type']) && !empty($access_token_json['token_type'])){       
                $token_type= $access_token_json['token_type'];         
            }else{             
                $token_type='';         
            }     // echo $access_token;  
		    $TranAmount = $total_product; 
            if(MYFATOO_LIVE_TEST==1)
            {
                $invoice_url=MYFAT_INVOICE_URL;
            }
            else
            {
                $invoice_url=MYFAT_INVOICE_URL_TEST;
            }
            if(isset($access_token_json['access_token']) && !empty($access_token_json['access_token']))    
            {         
               // echo "Token Generated Successfully.<br>";  
                $t= time();         
                $name = "Demo Name";
                $soap_do     = curl_init();      
                curl_setopt($soap_do, CURLOPT_URL, $invoice_url);     
                curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);        
                curl_setopt($soap_do, CURLOPT_TIMEOUT, 10);        
                curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);        
                curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);        
                curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);        
                curl_setopt($soap_do, CURLOPT_POST, true);        
                curl_setopt($soap_do, CURLOPT_POSTFIELDS, $post_string);        
                curl_setopt($soap_do, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8','Content-Length: ' . strlen($post_string),  'Accept: application/json','Authorization: Bearer '.$access_token));        
                $result1 = curl_exec($soap_do);       
               //  echo "<pre>";print_r($result1);die;        
                $err    = curl_error($soap_do);       
                $json1= json_decode($result1,true);       
                $RedirectUrl= $json1['RedirectUrl'];       
                $ref_Ex=explode('/',$RedirectUrl);      
                $referenceId =  $ref_Ex[4];        
                curl_close($soap_do);    
               // echo '<br><button type="button" id="paymentRedirect"  class="btn btn-success">Click here to Payment Link</button>';
               // var_dump($json1);
                $this->load->model('cart_order_model');
                $params = array(
                            'payment_id'=> isset($json1['Id']) ? $json1['Id'] : '' ,
                            'payment_links'=>$result1,
                            'payment_date'=>DATE_TIME,  
                            
                            'trx_error'=>isset($json1['FieldsErrors']) ? $json1['FieldsErrors'] : '' , 
        		            );
		        $this->cart_order_model->update_cart_order($cart_order_id,$params); 
                if($RedirectUrl!=null)
                {
                    redirect($json1['RedirectUrl']);
                }   
                else {
                  //  echo $json1['RedirectUrl']."<<<<<<<<<<<<<";
                      //$this->session->set_flashdata('alertmsg', '<div class="alert alert-danger text-center">'.$this->mhlanguage->setvaluebasedonlang('try_again','Try again').'</div>');
                       $this->session->set_flashdata('alertmsg', '<div class="alert alert-danger text-center">'.$this->mhlanguage->setvaluebasedonlang('try_again_link_not_gen_','Try again Payment Link Not generated').'</div>');
                    redirect('checkout');
                    // show_error('Something error. please try again later.');    
                }         
            }
            else{ 
                // print_r($json);       print_r("Error: ".$json['error']."<br>Description: ".$json['error_description']);  
                 $this->session->set_flashdata('alertmsg', '<div class="alert alert-danger text-center">'.$this->mhlanguage->setvaluebasedonlang('try_again_mf_token','Try again, Payment gateway token not generated').'</div>');
                      redirect('checkout');
                //show_error('Something error. please try again later.');    
            } /*isset of json if */ 
           } 
          catch(Exception $e) {  
            trigger_error(sprintf(
           'Curl failed with error #%d: %s',
           $e->getCode(), $e->getMessage()),
             E_USER_ERROR); 
          } 
      }/*already paiment id there */
	 
}



/* sucees url*/
public function knet_mf_success($cart_order_id=null,$data2=null)
{
		if(isset($_GET['paymentId']) ) 
		{
			   $access_token_json=$this->checktoken();
            //echo $access_token;
            if(isset($access_token_json['access_token']) && !empty($access_token_json['access_token']))
            {           
                $access_token= $access_token_json['access_token'];       
            }else{         
                $access_token='';       
            }       
            if(isset($access_token_json['token_type']) && !empty($access_token_json['token_type'])){       
                $token_type= $access_token_json['token_type'];         
            }else{             
                $token_type='';         
            }     // echo $access_token;  

            $id=$_GET['paymentId'];
           // echo $id;
           if(MYFATOO_LIVE_TEST==1)
            {
                $url=MYFAT_URL;
                $username=MYFAT_LIVE_UNAME;
                $password=MYFAT_LIVE_PWD;
            }
            else
            {
                $url=MYFAT_URL_TEST;
                $username=MYFAT_LIVE_UNAME_TEST;
                $password=MYFAT_LIVE_PWD_TEST;
            }
          //  echo $url."<br>";
           // echo $username."<br>";
           // echo $password."<br>";
             $url = $url.'/'.$id; 
        $soap_do1 = curl_init();         
        curl_setopt($soap_do1, CURLOPT_URL,$url );         
        curl_setopt($soap_do1, CURLOPT_CONNECTTIMEOUT, 10);         
        curl_setopt($soap_do1, CURLOPT_TIMEOUT, 10);         
        curl_setopt($soap_do1, CURLOPT_RETURNTRANSFER, true );         
        curl_setopt($soap_do1, CURLOPT_SSL_VERIFYPEER, false);         
        curl_setopt($soap_do1, CURLOPT_SSL_VERIFYHOST, false);         
        curl_setopt($soap_do1, CURLOPT_POST, false );         
        curl_setopt($soap_do1, CURLOPT_POST, 0);         
        curl_setopt($soap_do1, CURLOPT_HTTPGET, 1);         
        curl_setopt($soap_do1, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Accept: application/json','Authorization: Bearer '.$access_token)); 
        $result_in = curl_exec($soap_do1); 
        $err_in = curl_error($soap_do1); 
        $file_contents = htmlspecialchars(curl_exec($soap_do1));         curl_close($soap_do1); 
        $getRecorById = json_decode($result_in, true); 

        $this->load->model('cart_order_model');
		 $params = array(
             'payment_id'=> isset($_GET['paymentId']) ? $_GET['paymentId'] : $getRecorById['InvoiceId'],
           'invoice_id'=> isset($getRecorById['InvoiceId']) ? $getRecorById['InvoiceId'] : '' ,
           'result'=> isset($getRecorById['TransactionStatus']) ? $getRecorById['TransactionStatus'] : '',
		   'payment_date'=>DATE_TIME, 
		    'post_date'=>isset($getRecorById['CreatedDate']) ? $getRecorById['CreatedDate'] : DATE_TIME, 
		   'res_tranid'=>  isset($getRecorById['TransactionId']) ? $getRecorById['TransactionId'] : '' ,
           'ref'=>  isset($getRecorById['ReferenceId']) ? $getRecorById['ReferenceId'] : '',
            'trackid'=>  isset($getRecorById['TrackId']) ? $getRecorById['TrackId'] : '',
		   
		   'trx_errortext'=>  isset($getRecorById['Error']) ? $getRecorById['Error'] : '' ,
           'auth'=> isset($getRecorById['AuthorizationId']) ? $getRecorById['AuthorizationId'] : '',
		   
           'paid_amount'=>isset($getRecorById['TransationValue']) ? $getRecorById['TransationValue'] : '', 
           'invoice_result_json'=>$result_in,
           'gateway_service_charge'=>isset($getRecorById['CustomerServiceCharge']) ? $getRecorById['CustomerServiceCharge'] : ''
                );
           //    echo "<pre>".var_export($params,true)."</pre>";
		   $this->cart_order_model->update_cart_order($cart_order_id,$params); 
             $this->load->library('encryption'); 
             
             $this->change_cart_product_qty_status($cart_order_id,'paid');//change paid status in cart_product qty table

             $cart_order_id=$this->encryption->encrypt($cart_order_id); 
             
            $cart_order_id = strtr($cart_order_id, array('+' => '.', '=' => '-', '/' => '~'));
   
		   redirect('order-page/'.$cart_order_id);

		} else {
			     $this->session->set_flashdata('alertmsg', '<div class="alert alert-danger text-center">'.$this->mhlanguage->setvaluebasedonlang('try_again','Try again').'</div>');
                      redirect('checkout');
		}	 
		 

}
 

/*change_cart_product_qty_status*/
public function change_cart_product_qty_status($cart_order_id=null, $status=null)
{
	
		$this->load->model('cart_order_model');
		$this->load->model('Cart_item_model');
		$this->load->model('Cust_address_model');
   		$this->load->model('Product_model');
     	$this->load->model('Category_model');
     	$this->load->model('Cart_item_time_model');
	    $this->load->model('Cart_product_qty_model');
		  $cart_item= $this->Cart_item_model->get_all_cart_itembycartid($cart_order_id);
		   $data['cart_order'] = $this->cart_order_model->get_cart_order($cart_order_id);
		 if($cart_item!=null)
  {
    $data['cust_address'] = $this->Cust_address_model->get_cust_address($data['cart_order']['address_id']);
    $this->load->model('Product_model');
   // $order_data=array();
   // $order_data['netamount']=$netamount;
   //$order_data['service_charge']=$netamount;

    $order_items=array();
    foreach($cart_item as $ci)
    {
        $product= $this->Product_model->get_product($ci['product_id']); 
        if($data['cart_order']['result']=="2")
        {
			if($product!=null)
			{
				$params_up = array(
					'qty'=> $product['qty']-$ci['quantity'], 
					'cart_qty'=> $product['cart_qty']-$ci['quantity'], 
					); 
				// var_dimp( $params_up);
				$this->Product_model->update_product($product['product_id'],$params_up);  

				$sess_guest=$this->session->userdata(SESSION_NAME_GUEST);  
					$cust_uniqe_id_arr= $this->Cart_product_qty_model->get_cart_product_qtybyclm_nameByp_id('cust_uniqe_id',$sess_guest['cust_uniqe_id'],$product['product_id']); 
				$params_p_q_u = array(   
						
							'updated_date'=>DATE_TIME, 
							'status'=>'paid'
							);
				$this->Cart_product_qty_model->update_cart_product_qty($cust_uniqe_id_arr['cart_product_qty_id'],$params_p_q_u);
			}
			else
			{
				
			}
        }
        else
        {
         // echo "else".$data['cart_order']['result'];
        }
        
		/* $order_items['image']=$product['image'];
			$order_items['name']=$product['product_name'];
			$order_items['qty']=$ci['qty'];
			$order_items['sub_total']=$ci['cart_price'];*/
			$ci['product']=$product;
			$order_items[] =$ci; 
		}
		$data['cart_order']['cart_items']=$order_items;
    }
      else
    {
     // echo "else";
    }

}
/* error url*/
public function knet_mf_errors($cart_order_id=null)
{
	 
	//echo $trx_error;
		$this->load->model('cart_order_model');
		 $params = array(
            'payment_id'=> isset($_GET['paymentId'])?$_GET['paymentId']:'' ,
           'result'=> 'CANCELED',
		   'payment_date'=>DATE_TIME, 
		    
		   'trx_errortext'=> '' ,
           
        		);
		  $this->cart_order_model->update_cart_order($cart_order_id,$params); 
			 $this->load->library('encryption'); 
		     $cart_order_id=$this->encryption->encrypt($cart_order_id); 
            $cart_order_id = strtr($cart_order_id, array('+' => '.', '=' => '-', '/' => '~'));
   
		  redirect('order-page/'.$cart_order_id);
}


/*checktoken*/
public function checktoken()
{
   // echo "if";
    if(MYFATOO_LIVE_TEST==1)
    {
         $curl_opt_url=MYFAT_TOKEN_URL;
         $username=MYFAT_LIVE_UNAME;
        $password=MYFAT_LIVE_PWD;
    }
    else
    {
        $curl_opt_url=MYFAT_TOKEN_URL_TEST;
        $username=MYFAT_LIVE_UNAME_TEST;
        $password=MYFAT_LIVE_PWD_TEST;
    }
    $status=true;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL,$curl_opt_url);       
    curl_setopt($curl, CURLOPT_POST, 1);       
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);       
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);       
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);       
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array('grant_type' => 'password','username' => $username,'password' =>$password)));       
    $result = curl_exec($curl);       
    $info = curl_getinfo($curl);       
    curl_close($curl);    
   // echo $result;   
    $json = json_decode($result, true);       
   /* if(isset($json['access_token']) && !empty($json['access_token']))
    {           
        $access_token= $json['access_token'];       
    }else{         
        $access_token='';       
    }       
    if(isset($json['token_type']) && !empty($json['token_type'])){       
        $token_type= $json['token_type'];         
    }else{             
        $token_type='';         
    }     // echo $access_token; */

    return $json;
}
}