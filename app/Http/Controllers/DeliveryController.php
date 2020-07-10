<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Orders;
use App\Http\Resources\OrdersDTO;

class DeliveryController extends Controller
{
    public function placeOrder(Request $request) {
        $origin = $request->input("origin");
        $destination = $request->input("destination");

        if(!is_array($origin) || !is_array($destination)) {
            return array("error" => "Origin or destination field is missing");
        }
        if(sizeof($origin) != 2 || sizeof($destination) != 2) {
            return array("error" => "Origin or destination has invalid array size");
        }
        $isOriginValid = $this->validateLatLng($origin);
        $isDestinationValid = $this->validateLatLng($destination);
        if(!$isOriginValid || !$isDestinationValid) {
            return array("error" => "Origin or destination is not a valid coordination");
        }
        
        //Distance Matrix API
        //e.g. 22.3352484,114.2046599/22.3263164,114.2045314
        $distanceObj = $this->calculateDistance($origin, $destination);
        if($distanceObj->rows[0]->elements[0]->status != "OK") {
            return array("error" => "Distance Matrix API cannot calculate the path");
        }
        $distance = $distanceObj->rows[0]->elements[0]->distance->value;

        //save new order
        $order = new Orders();
        $order->distance = $distance;
        $order->status = "UNASSIGNED";
        $order->save();

        return new OrdersDTO($order);
    }

    public function takeOrder($id) {
        if(!is_numeric($id)) {
            return array("error" => "Order ID is not a valid integer");
        }

        $order = Orders::find($id);
        if(!$order) {
            return array("error" => "Order cannot be found");
        }
        if($order->status != "UNASSIGNED") {
            return array("error" => "Order has been taken");
        }

        //update order
        $order->status = "ASSIGNED";
        $order->save();

        return array("status" => "SUCCESS");
    }

    public function ordersList(Request $request) {
        $page = $request->query("page");
        $limit = $request->query("limit");

        if(!is_numeric($page) || !is_numeric($limit)) {
            return array("error" => "Page or limit is not a valid integer");
        }

        if($page < 1) {
            return array("error" => "Page must be greater than or equal to 1");
        }

        $skip = ($page - 1) * $limit;
        $orders = Orders::skip($skip)->take($limit)->get();
        return OrdersDTO::collection($orders);
    }

    private function validateLatLng($coordinate) {
        $latitude = $coordinate[0];
        $longitude = $coordinate[1];
        if(!is_numeric($latitude) || !is_numeric($longitude)) {
            return false;
        }
        if($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return false;
        }
        return true;
    }

    private function calculateDistance($origin, $destination) {
        $ch = curl_init();
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json";

        $options = array(
            "units" => "imperial",
            "origins" => $origin[0] . ',' . $origin[1],
            "destinations" => $destination[0] . ',' . $destination[1],
            "key" => env('GOOGLE_DISTANCE_MATRIX_API_KEY')
        );

        $request = $url . "?" . http_build_query($options);

        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        return $output;
    }
}
