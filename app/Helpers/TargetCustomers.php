<?php
namespace App\Helpers;
use Illuminate\Support\Facades\Http;
use App\Models\ClientCustomer;
class TargetCustomers
{
public static function getCustomersByTargetMessageId($targetId,$userId,$spaceId)
    {
        //id=1 -> New Customer (Latest (today))
        //id=2 -> Active Customer (Order in last 30 days)
        //id=3 -> Recent (Order in last 7 days)
        //id=4 -> All Customers
        if($targetId==1){
            $customers=ClientCustomer::where('client_id',$userId)->where('space_id',$spaceId)->whereDate('created_at',now()->toDateString())->with('customer')->get();
            return $customers->pluck('customer.whatsapp_number')->filter()->values();
        
        }
        elseif($targetId==2){
            $customers=ClientCustomer::where('client_id',$userId)->where('space_id',$spaceId)->whereHas('customer.orders',function($q){
                $q->where('created_at','>=',now()->subDays(30));
            })->with('customer')->get();
            return $customers->pluck('customer.whatsapp_number')->filter()->values();
        }
        elseif($targetId==3){
            $customers=ClientCustomer::where('client_id',$userId)->where('space_id',$spaceId)->whereHas('customer.orders',function($q){
                $q->where('created_at','>=',now()->subDays(7));
            })->with('customer')->get();
            return $customers->pluck('customer.whatsapp_number')->filter()->values();
        }
        elseif($targetId==4){
            $customers=ClientCustomer::where('client_id',$userId)->where('space_id',$spaceId)->with('customer')->get();

            return $customers->pluck('customer.whatsapp_number')->filter()->values();
        }
        else{
            $customers=[];
        }

}
}
