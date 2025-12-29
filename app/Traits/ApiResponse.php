<?php
namespace App\Traits;
trait ApiResponse {
    public static function success($data = [], $message = 'Success', $code = 200) {
        return response()->json(['status' => 'success','message' => $message,'data' => $data], $code);
    }
    public static function fail($message = 'Failure', $code = 400, $data = []) {
        return response()->json(['status' => 'error','message' => $message,'data' => $data], $code);
    }
}
 