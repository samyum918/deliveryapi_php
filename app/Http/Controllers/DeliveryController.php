<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Orders;

class DeliveryController extends Controller
{
    public function placeOrder($origin, $destination) {

    }

    public function takeOrder($id) {

    }

    public function ordersList($page, $limit) {
        $skip = ($page - 1) * $limit;
        $orders = Orders::skip($skip)->take($limit)->get();
        return $orders;
    }
}
