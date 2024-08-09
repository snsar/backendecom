<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class VNPayController extends Controller
{
    private $vnpUrl = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
    private $vnpApiUrl = 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';
    private $vnpTmnCode = 'KVS4V0VY'; // Thay bằng mã TMN của bạn
    private $vnpHashSecret = 'TRH5ULVVITR4Y9KWQN11XVTDLPAXCHZ1'; // Thay bằng mã bí mật của bạn

    public function createOrderAndInitiatePayment(Request $request)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id(); // Lấy ID người dùng hiện tại

            // Tạo đơn hàng
            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'total_amount' => $request->total_amount,
                'status' => 'pending',
                'shipping_address' => $request->shipping_address,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Tạo URL thanh toán
            $vnpUrl = $this->vnpUrl;
            $vnpTmnCode = $this->vnpTmnCode;
            $vnpHashSecret = $this->vnpHashSecret;

            $inputData = [
                "vnp_Version" => "2.0.0",
                "vnp_TmnCode" => $vnpTmnCode,
                "vnp_Amount" => $request->total_amount * 100, // Amount in VND
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $request->ip(),
                "vnp_Locale" => "vn",
                "vnp_OrderInfo" => "Thanh toán đơn hàng #" . $orderId,
                "vnp_OrderType" => "other",
                "vnp_ReturnUrl" => route('vnpay.return'),
                "vnp_TxnRef" => $orderId,
            ];

            $vnp_SecureHash = $this->getSecureHash($inputData, $vnpHashSecret);
            $query = http_build_query($inputData);
            $vnpUrl .= "?" . $query . "&vnp_SecureHash=" . $vnp_SecureHash;

            // Lưu các sản phẩm trong đơn hàng
            $cartItems = $request->cart_items; // Giả sử cart_items được truyền vào request
            foreach ($cartItems as $item) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }

            DB::commit();

            return response()->json(['paymentUrl' => $vnpUrl]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation and payment initiation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Order creation failed.'], 500);
        }
    }

    private function getSecureHash($inputData, $secretKey)
    {
        ksort($inputData);
        $query = urldecode(http_build_query($inputData));
        $secureHash = hash_hmac('sha512', $query, $secretKey);
        return $secureHash;
    }

    public function paymentReturn(Request $request)
    {
        $vnpResponseCode = $request->input('vnp_ResponseCode');
        $vnpTxnRef = $request->input('vnp_TxnRef');
        $vnpSecureHash = $request->input('vnp_SecureHash');

        $inputData = $request->except('vnp_SecureHash');
        $vnpHashSecret = $this->vnpHashSecret;
        $secureHash = $this->getSecureHash($inputData, $vnpHashSecret);

        $frontendUrl = config('test.frontend_url'); // Make sure this is set in your .env file

        if ($vnpResponseCode === '00') {
            DB::beginTransaction();
            try {
                $order = DB::table('orders')->where('id', $vnpTxnRef)->first();
                if (!$order) {
                    throw new \Exception('Order not found');
                }

                DB::table('orders')->where('id', $vnpTxnRef)->update(['status' => 'processing']);

                $orderItems = DB::table('order_items')->where('order_id', $vnpTxnRef)->get();
                foreach ($orderItems as $item) {
                    DB::table('products')->where('id', $item->product_id)->decrement('stock_quantity', $item->quantity);
                }

                DB::commit();
                return redirect()->to($frontendUrl . '/payment-success?vnp_TxnRef=' . $vnpTxnRef);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Payment return handling failed: ' . $e->getMessage());
                return redirect()->to($frontendUrl . '/payment-error');
            }
        } else {
            return redirect()->to($frontendUrl . '/payment-error');
        }
    }
}
