<?php

namespace App\Http\Controllers\v1;

use Illuminate\Support\Facades\Validator;
use App\RestaurantMenu;
use App\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\AddToCart;
use App\OrderList;


class RestaurantController extends Controller
{
    public function restaurant(Request $request)//Get Restaurant List
    	{               
	       try{
	       		
	          	$fetchRes = Restaurant::all();
				return response(['status' => 1, 'message' => 'All Restaurant Listed', 'data' => $fetchRes]);
	        	}catch(Exception $e){
	      		return response()->json(['status'=>0,'message'=>'Exception Error','data'=>json_decode("{}")]);
	        	}
    	}


    public function getMenuListByID(Request $request)//Get Restaurant Menu List
    	{
    		try{
	    		$res_id = $request->restaurant_id;
	    		if(!isset($request->restaurant_id)){
                return response()->json(['status'=>$status,'message'=>'Please provide Restaurant ID','data'=>json_decode('{}')]);
            	}
            	$resName = Restaurant::where('id',$res_id)->get();
	    		$data = RestaurantMenu::where('res_id',$res_id)->get();
	    		return response()->json(['status'=>1,'message'=>'All Menu List According to Restaurant','RestaurntDetails'=>$resName,'RestaurantMenus'=>$data]);
		    	}catch(Exception $e){
		    	return response()->json(['status'=>0,'message'=>'Exception Error','data'=>json_decode("{}")]);
		    	}
	    	}

					//$gst = ($total_price * 0.18);
				    //$totalAmt_withGST = $total_price + ($total_price * 0.18); 
		public function AddtoCart(Request $request)//Add To Cart
	    {
	        try{
	    		$validator = Validator::make($request->all(), [            
            	'phone'				=>'required|string|max:10',
            	'cart_data' 		=>'required'
        		]);
            if($validator->fails()){
            return response()->json(["status"=>0,"responseCode"=>"NP997","message"=>"invalid input details","data"=>json_decode("{}")]);
        	}
    		$phone = $request->phone;
     		$cart_data = $request->cart_data;
     		$grand_amt=0;
     		$grand_qty=0;
     		//print_r($cart_data);die;
     		$cart = [];
     		foreach ($cart_data as $cart) {
     			$quantity       =        $cart['quantity'];
			    $price          =        $cart['price_per_item'];
			    $grand_qty      =        $grand_qty + $quantity;
			    $total_amt      =        $quantity * $price;
			   	$grand_amt      =        $grand_amt + $total_amt;   
				}
			
				$delivery_charge = 0;
				$total_with_delivery = $grand_amt + $delivery_charge;
				$gst = ($total_with_delivery * 0.18);
			    $totalAmt_withGST = $total_with_delivery + $gst;
				//	print_r( $total_with_delivery);die;
				$check = DB::table('order_list')->select('id')->where('phone', $phone)->first();
			if($check==true){
			    
			
				//echo $grand_amt;die;
				$update = DB::update("UPDATE order_list SET total_price = total_price + $grand_amt  WHERE phone = $phone");
				$results = OrderList::where('phone',$phone)->value('total_price');
				$gst = ($results * 0.18);
				$total_amt_with_gst = $results + $gst;
			//	echo $total_amt_with_gst;die;
				$update = DB::update("UPDATE order_list SET total_price_with_gst = $total_amt_with_gst, gst = $gst WHERE phone = $phone");
			    $getID =OrderList::where('phone',$phone)
                ->pluck('id')
                ->first();
			//echo $getID;die;
			        $cart_item = [];
				    foreach ($cart_data as $cart_item){
			        $cart_item = [
			    	'order_id' 			=> 	$getID,
			        'item_id' 			=> 	$cart_item['item_id'],
			        'quantity' 			=> 	$cart_item['quantity'],
			        'price_per_item' 	=>	$cart_item['price_per_item'],
			        'restaurant_id' 	=> 	$cart_item['restaurant_id']
			        ];
			        $addItemInCart = AddToCart::insert($cart_item);
			        }
				return response()->json(['status'=>'1',"responseCode"=>"TC001",'message'=>'Add to Cart Successfully','data'=>""]);
				   }else{
				   $data = OrderList::insertGetId ([ 
					'phone'   							=>$phone,
					'total_qty' 						=>$grand_qty,
					'total_price'						=>$total_with_delivery,
					'total_price_with_delivery'			=>$total_with_delivery,
					'total_price_with_gst' 				=>$totalAmt_withGST,
					'gst'								=>$gst,
					'delivery_charge' 					=>$delivery_charge
				   ]);
				   //$addTotal = OrderList::insertGetId($data);
				   $cart_item = [];
				    foreach ($cart_data as $cart_item){
			        $cart_item = [
			    	'order_id' 			=> 	$data,
			        'item_id' 			=> 	$cart_item['item_id'],
			        'quantity' 			=> 	$cart_item['quantity'],
			        'price_per_item' 	=>	$cart_item['price_per_item'],
			        'restaurant_id' 	=> 	$cart_item['restaurant_id']
			        ];
			        $addItemInCart = AddToCart::insert($cart_item);
			        }
			        return response()->json(['status'=>'1',"responseCode"=>"TC001",'message'=>'Add to Cart Successfully','data'=>""]);
				   }
				   //print_r($cart);die;
			    	}catch(Exception $e){
			    	return response()->json(['status'=>0,'message'=>'Exception Error','data'=>json_decode("{}")]);
			    	}
	    		}

//Start View Cart
	 public function viewCart(Request $request)
	    	{
	    	try {
    		    $phone = $request->phone;
    		    $totaldata = DB::table('order_list')->select('phone','total_price','total_price_with_gst','gst')->where('phone', $phone)->get();
                $data = DB::table('add_to_cart')
                ->join('order_list', 'add_to_cart.order_id', '=', 'order_list.id')
                ->join('restaurant_menus','restaurant_menus.id','=','add_to_cart.item_id')
                ->select('add_to_cart.restaurant_id','add_to_cart.item_id','restaurant_menus.item_name','add_to_cart.quantity as total_qty','add_to_cart.price_per_item','restaurant_menus.item_image')
                ->where('order_list.phone','=',$phone)
                ->get();
			    return response()->json(['status' =>1, 'message'=>'Item List Fetched Successfully ','totaldata'=> $totaldata, 'data'=>$data]);
	    	} catch (Exception $e) {
	    		
	    	}
	    }

 //Update Cart 
    public function updateCart(Request $request){     
            try{    
            	//	echo 1;die;
            	$phone 			= $request->phone;
            	$item_id 		= $request->item_id;
            	$restaurant_id 	= $request->restaurant_id;
            	$quantity 		= $request->quantity;  	
            	$price 			= $request->price;
            	$symbol 		=$request->symbol; 
            	$total_amt 		= $quantity * $price;
            	$gst = ($total_amt * 0.18);
			    $total_amt_with_gst = $total_amt + $gst;
//echo $total_amt_with_gst;die;
			    $action = $request->get('action'); 		           
                switch ($action)
                {
                case '1':
               $update_total_amt = DB::update("UPDATE order_list SET total_price_with_gst = total_price_with_gst + $total_amt_with_gst , gst = gst + $gst WHERE phone = $phone");

        	   $order_id = DB::table('order_list')->where('phone',$phone)->pluck('id')->first();
			   $results = DB::table('add_to_cart')->where('restaurant_id','=',$restaurant_id)
                 ->where('order_id','=',$order_id)
                 ->update([
                 	'item_id'   	=>$item_id,
                 	'quantity'		=>$quantity,
                 	'price_per_item'=>$price

                 ]);        
       		   return response()->json(['status'=>1,'message'=>'Cart updated successfully','data'=>""]);
                break;
               

                case '2':
               	$update_total_amt = DB::update("UPDATE order_list SET total_price_with_gst = total_price_with_gst - $total_amt_with_gst , gst = gst - $gst WHERE phone = $phone");
	        	$order_id = DB::table('order_list')->where('phone',$phone)->pluck('id')->first();
				$results = DB::table('add_to_cart')->where('restaurant_id','=',$restaurant_id)
                 ->where('order_id','=',$order_id)
                 ->update([
                 	'item_id'   	=>$item_id,
                 	'quantity'		=>$quantity,
                 	'price_per_item'=>$price

                 ]); 
         		return response()->json(['status'=>1,'message'=>'Cart updated successfully','data'=>""]);
                break;

                case '3':
               $update_total_amt = DB::update("UPDATE order_list SET total_price_with_gst = total_price_with_gst - $total_amt_with_gst , gst = gst - $gst WHERE phone = $phone");
	        	$order_id = DB::table('order_list')->where('phone',$phone)->pluck('id')->first();
	        	$delete_data = DB::table('add_to_cart')
	        	->where('restaurant_id',$restaurant_id)
	        	->where('order_id',$order_id)
	        	->delete();
	        	return response()->json(['status'=>1,'message'=>'Delete successfully','data'=>""]);
                break;
            	}		   
    		}catch(Exception $e){            
        	return response()->json(['status'=>0,'message'=>'Quantity Updated Error','data'=>json_decode("{}")]);
    		}
		}

	}
	