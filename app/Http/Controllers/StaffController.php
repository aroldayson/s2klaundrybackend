<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Admins;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function AdminLogin(Request $request){
        $request->validate([
            'email'=>"required|email|exists:admins,Email",
            "password"=>"required"
        ]);

        $user = Admins::where('Email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->Password)){
            return response()->json([
                'message' => "The provided credentials are incorrect"
            ], 401);
        }

        $token = $user->createToken($user->Admin_lname);

        return response()->json([
            'user'=>$user,
            'token'=>$token->plainTextToken
        ]);
    }

    // public function CustLogin(Request $request){
    //     $request->validate([
    //         'email'=> 'required|email|exists:customers,Cust_email',
    //         'password'=> 'required'
    //     ]);
    //     $user = Customer::where('Cust_email', $request->email)->first();

    //     if(!$user || !Hash::check($request->password, $user->Cust_password)){
    //         return response()->json([
    //             'message' => "The provided credentials are incorrect"
    //         ], 401);
    //     }

    //     $custid = $user->Cust_ID;
    //     $token = $user->createToken($user->Cust_lname);
    //     return [
    //         'user'=>$user,
    //         'Cust_ID'=>$custid,
    //         'token'=>$token->plainTextToken
    //     ];
    // }

    public function Logout(Request $request){
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        $personalAccessToken = PersonalAccessToken::findToken($token);

        if (!$personalAccessToken) {
            return response()->json(['error' => 'Token not found or invalid'], 401);
        }

        if ($request->user()->User_ID !== $personalAccessToken->tokenable_id) {
            return response()->json(['error' => 'Token does not belong to the authenticated user'], 403);
        }

        $personalAccessToken->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
    public function getTransaction(){
        $trans = DB::table('transactions')
            ->leftJoin('customers', 'customers.Cust_ID', '=', 'transactions.Cust_ID')
            ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->leftJoin('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->leftJoin('additional_services', 'transactions.Transac_ID', '=', 'additional_services.Transac_ID')
            ->leftJoin('laundry_categories', 'laundry_categories.Categ_ID', '=', 'transaction_details.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->leftJoin('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
            ->leftJoin('admins', 'transactions.Admin_ID', '=', 'admins.Admin_ID')
            ->select(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                DB::raw("CONCAT(customers.Cust_fname, ' ', customers.Cust_mname, ' ', customers.Cust_lname) AS CustomerName"),
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                DB::raw("(SELECT TransacStatus_name
                  FROM transaction_status AS ts
                  WHERE ts.Transac_ID = transactions.Transac_ID
                  AND ts.TransacStatus_datetime = (SELECT MAX(TransacStatus_datetime)
                                     FROM transaction_status
                                     WHERE Transac_ID = transactions.Transac_ID)
                  LIMIT 1) AS latest_transac_status"),
                // DB::raw('SUM(CASE WHEN transactions.Received_datetime IS NOT NULL AND transactions.Admin_ID = admins.Admin_ID THEN transaction_details.Price ELSE NULL END) AS amount'),
                // DB::raw('CASE WHEN SUM(transaction_details.Price) IS NOT NULL AND transactions.Admin_ID = admins.Admin_ID THEN transactions.Received_datetime ELSE NULL END AS Received_datetime'),
                DB::raw("IF(payments.Mode_of_Payment IS NULL, 'unpaid', payments.Mode_of_Payment) AS payment"),
                // receiving_type
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM additional_services
                        WHERE additional_services.AddService_name = 'Pick-up Service' AND additional_services.Transac_ID = transactions.Transac_ID
                    )
                    THEN 'Pick-up Service'
                    ELSE 'Drop-off'
                END AS receiving_type"),
                // releasing_type
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM additional_services
                        WHERE additional_services.AddService_name = 'Delivery Service' AND additional_services.Transac_ID = transactions.Transac_ID
                    )
                    THEN 'Delivery Service'
                    ELSE 'Customer Pick-up'
                END AS releasing_type")
            )
            // ->whereNotIn('transactions.Transac_status', ['completed', 'canceled', 'forRelease'])
            ->groupBy(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                'customers.Cust_fname',
                'customers.Cust_mname',
                'customers.Cust_lname',
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                // 'transaction_status.TransacStatus_name',
                // 'transactions.Received_datetime',
                // 'transactions.Transac_status',
                'payments.Amount',
                'transactions.Admin_ID',
                'admins.Admin_ID',
                'payments.Mode_of_Payment'
            )
            ->get();
                // DB::raw("DATE_FORMAT(transactions.Received_datetime, '%Y-%m-%d') AS `date`"),
                // DB::raw("DATE_FORMAT(transactions.Received_datetime, '%H:%i:%s') AS `time`"),
                    // ->whereNotIn('transactions.Transac_status', ['completed', 'canceled'])


        if(is_null($trans)){
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($trans,200);
    }

    public function getCustTransacHistory($id){
        $trans = DB::table('transactions')
            ->leftJoin('customers', 'customers.Cust_ID', '=', 'transactions.Cust_ID')
            ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->leftJoin('additional_services', 'transactions.Transac_ID', '=', 'additional_services.Transac_ID')
            ->leftJoin('laundry_categories', 'laundry_categories.Categ_ID', '=', 'transaction_details.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->leftJoin('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
            ->leftJoin('admins', 'transactions.Admin_ID', '=', 'admins.Admin_ID')
            // Join a subquery to get the latest transaction status
            ->leftJoin(DB::raw("(SELECT Transac_ID, TransacStatus_name, MAX(TransacStatus_datetime) AS latest_status_date
                                FROM transaction_status
                                GROUP BY Transac_ID, TransacStatus_name) AS latest_status"),
                    'transactions.Transac_ID', '=', 'latest_status.Transac_ID')
            ->select(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                DB::raw("CONCAT(customers.Cust_fname, ' ', customers.Cust_mname, ' ', customers.Cust_lname) AS CustomerName"),
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                'latest_status.TransacStatus_name AS latest_transac_status',
                DB::raw("IF(payments.Mode_of_Payment IS NULL, 'unpaid', payments.Mode_of_Payment) AS payment"),
                // receiving_type
                DB::raw("CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM additional_services
                                WHERE additional_services.AddService_name = 'Pick-up Service'
                                AND additional_services.Transac_ID = transactions.Transac_ID
                            )
                            THEN 'Pick-up Service'
                            ELSE 'Drop-off'
                        END AS receiving_type"),
                // releasing_type
                DB::raw("CASE
                            WHEN EXISTS (
                                SELECT 1
                                FROM additional_services
                                WHERE additional_services.AddService_name = 'Delivery Service'
                                AND additional_services.Transac_ID = transactions.Transac_ID
                            )
                            THEN 'Delivery Service'
                            ELSE 'Customer Pick-up'
                        END AS releasing_type")
            )
            ->where('customers.Cust_ID', $id)
            ->groupBy(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                'customers.Cust_fname',
                'customers.Cust_mname',
                'customers.Cust_lname',
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                'latest_status.TransacStatus_name',
                'payments.Mode_of_Payment'
            )
            ->get();


        if(is_null($trans)){
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($trans,200);
    }

    public function getCustomer(){
        $custList = DB::table('customers')
            ->select('*')
            ->get();
        return $custList;
    }

    public function getCustomerData($id){
        $custData = DB::table('customers')
            ->select('*')
            ->where('Cust_ID', $id)
            ->first();

        if(is_null($custData)){
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json($custData, 200);
    }

    public function getTransactionsRec(){
        $trans = DB::table('transactions')
            ->join('customers', 'customers.Cust_ID', '=', 'transactions.Cust_ID')
            ->join('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->join('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->join('additional_services', 'transactions.Transac_ID', '=', 'additional_services.Transac_ID')
            ->leftJoin('laundry_categories', 'laundry_categories.Categ_ID', '=', 'transaction_details.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->leftJoin('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
            ->leftJoin('admins', 'transactions.Admin_ID', '=', 'admins.Admin_ID')
            ->select(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                DB::raw("CONCAT(customers.Cust_fname, ' ', customers.Cust_mname, ' ', customers.Cust_lname) AS CustomerName"),
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                DB::raw("(SELECT TransacStatus_name
                    FROM transaction_status
                    WHERE transaction_status.Transac_ID = transactions.Transac_ID
                    AND transaction_status.TransacStatus_datetime = (
                        SELECT MAX(transaction_status.TransacStatus_datetime)
                        FROM transaction_status
                        WHERE transaction_status.Transac_ID = transactions.Transac_ID
                    )
                    LIMIT 1) AS latest_transac_status"),
                DB::raw("SUM(CASE
                    WHEN transaction_status.TransacStatus_datetime IS NOT NULL
                        AND transaction_status.TransacStatus_name = 'received'
                        AND transactions.Admin_ID = admins.Admin_ID
                    THEN transaction_details.Price ELSE 0 END) AS amount"),
                DB::raw("SUM(DISTINCT transaction_details.Price) as TotalPrice"),
                DB::raw("SUM(DISTINCT additional_services.AddService_price) as TotalService"),
                DB::raw("CASE
                    WHEN transaction_status.TransacStatus_name = 'received'
                        AND transactions.Admin_ID = admins.Admin_ID
                    THEN transaction_status.TransacStatus_datetime ELSE NULL END AS Received_datetime"),
                // DB::raw("")
                DB::raw("IF(payments.Mode_of_Payment IS NULL, 'unpaid', payments.Mode_of_Payment) AS payment"),
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM additional_services
                        WHERE additional_services.AddService_name = 'Pick-up Service'
                        AND additional_services.Transac_ID = transactions.Transac_ID
                    ) THEN 'Pick-up Service' ELSE 'Drop-off' END AS receiving_type")
            )
            ->whereNotIn('transaction_status.TransacStatus_name', ['completed', 'canceled', 'forRelease'])
            ->groupBy(
                'transactions.Transac_ID',
                DB::raw('latest_transac_status'),
                DB::raw('Received_datetime'),
                'customers.Cust_ID',
                'customers.Cust_fname',
                'customers.Cust_mname',
                'customers.Cust_lname',
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_email',
                'customers.Cust_image',
                'admins.Admin_ID',
                'transactions.Admin_ID',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                'payments.Mode_of_Payment'
            )
            ->get();
                // DB::raw("DATE_FORMAT(transactions.Received_datetime, '%Y-%m-%d') AS `date`"),
                // DB::raw("DATE_FORMAT(transactions.Received_datetime, '%H:%i:%s') AS `time`"),
                    // ->whereNotIn('transactions.Transac_status', ['completed', 'canceled'])


        if(is_null($trans)){
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($trans,200);
    }

    public function getTrasactionsRel(){
        $trans = DB::table('transactions')
            ->leftJoin('customers', 'customers.Cust_ID', '=', 'transactions.Cust_ID')
            ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->leftJoin('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->leftJoin('additional_services', 'transactions.Transac_ID', '=', 'additional_services.Transac_ID')
            ->leftJoin('laundry_categories', 'laundry_categories.Categ_ID', '=', 'transaction_details.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->leftJoin('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
            ->leftJoin('admins', 'transactions.Admin_ID', '=', 'admins.Admin_ID')
            ->select(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                DB::raw("CONCAT(customers.Cust_fname, ' ', customers.Cust_mname, ' ', customers.Cust_lname) AS CustomerName"),
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                DB::raw("(SELECT TransacStatus_name
                    FROM transaction_status
                    WHERE transaction_status.Transac_ID = transactions.Transac_ID
                    AND transaction_status.TransacStatus_datetime = (
                        SELECT MAX(transaction_status.TransacStatus_datetime)
                        FROM transaction_status
                        WHERE transaction_status.Transac_ID = transactions.Transac_ID
                    )
                    LIMIT 1) AS latest_transac_status"),
                DB::raw("SUM(CASE
                    WHEN transaction_status.TransacStatus_datetime IS NOT NULL
                        AND transaction_status.TransacStatus_name = 'received'
                        AND transactions.Admin_ID = admins.Admin_ID
                    THEN transaction_details.Price ELSE 0 END) AS amount"),
                DB::raw("CASE
                    WHEN transaction_status.TransacStatus_name = 'received'
                        AND transactions.Admin_ID = admins.Admin_ID
                    THEN transaction_status.TransacStatus_datetime ELSE NULL END AS Received_datetime"),
                DB::raw("IF(payments.Mode_of_Payment IS NULL, 'unpaid', payments.Mode_of_Payment) AS payment"),
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM additional_services
                        WHERE additional_services.AddService_name = 'Delivery Service'
                        AND additional_services.Transac_ID = transactions.Transac_ID
                    ) THEN 'Delivery Service' ELSE 'Customer Pick-up' END AS releasing_type")
            )
            ->whereNotIn('transaction_status.TransacStatus_name', ['completed', 'canceled'])
            ->where('transaction_status.TransacStatus_name', ['forRelease'])
            ->groupBy(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                'customers.Cust_fname',
                'customers.Cust_mname',
                'customers.Cust_lname',
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_email',
                'customers.Cust_image',
                'admins.Admin_ID',
                'transactions.Admin_ID',
                'transaction_status.TransacStatus_name',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                'transaction_status.TransacStatus_name',
                'transaction_status.TransacStatus_datetime',
                'payments.Mode_of_Payment'
            )
            ->get();

        if(is_null($trans)){
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($trans,200);
    }

    public function showTransCust($id){
        // $trans = DB::table('transactions')
        //     ->leftJoin('customers', 'customers.Cust_ID', '=', 'transactions.Cust_ID')
        //     ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
        //     ->leftJoin('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
        //     ->leftJoin('additional_services', 'transactions.Transac_ID', '=', 'additional_services.Transac_ID')
        //     ->leftJoin('laundry_categories', 'laundry_categories.Categ_ID', '=', 'transaction_details.Categ_ID')
        //     ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
        //     ->leftJoin('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
        //     ->leftJoin('admins', 'transactions.Admin_ID', '=', 'admins.Admin_ID')
        //     ->select(
        //         'transactions.Transac_ID',
        //         'customers.Cust_ID',
        //         DB::raw("CONCAT(customers.Cust_fname, ' ', customers.Cust_mname, ' ', customers.Cust_lname) AS `CustomerName`"),
        //         'customers.Cust_phoneno',
        //         'customers.Cust_address',
        //         'customers.Cust_email',
        //         'transactions.Tracking_number',
        //         'transactions.Transac_datetime',
        //         DB::raw("CASE
        //             WHEN transaction_status.TransacStatus_name = 'received'
        //                 AND transactions.Admin_ID = admins.Admin_ID
        //             THEN transaction_status.TransacStatus_datetime ELSE NULL END AS Received_datetime"),
        //         DB::raw("(SELECT TransacStatus_name
        //             FROM transaction_status
        //             WHERE transaction_status.Transac_ID = transactions.Transac_ID
        //             AND transaction_status.TransacStatus_datetime = (
        //                 SELECT MAX(transaction_status.TransacStatus_datetime)
        //                 FROM transaction_status
        //                 WHERE transaction_status.Transac_ID = transactions.Transac_ID
        //             )
        //             LIMIT 1) AS latest_transac_status"),
        //         'transaction_details.TransacDet_ID',
        //         DB::raw('sum(transaction_details.Price) AS TotalPrice'),
        //         'additional_services.AddService_name',
        //         'additional_services.AddService_price',
        //          DB::raw('SUM(transaction_details.Price) AS `amount`'),
        //         DB::raw("IF(payments.Mode_of_Payment IS NULL, 'unpaid', payments.Mode_of_Payment) AS `payment`")
        //     )
        //     ->where('transactions.Transac_ID', $id)
        //     ->whereNotIn('transaction_status.TransacStatus_name', ['completed', 'canceled', 'forRelease'])
        //     ->groupBy(
        //         'transactions.Transac_ID',
        //         'customers.Cust_ID',
        //         'customers.Cust_fname',
        //         'customers.Cust_mname',
        //         'customers.Cust_lname',
        //         'customers.Cust_phoneno',
        //         'customers.Cust_address',
        //         'customers.Cust_email',
        //         'customers.Cust_image',
        //         'admins.Admin_ID',
        //         'transactions.Admin_ID',
        //         'transaction_details.TransacDet_ID',
        //         'additional_services.AddService_name',
        //         'additional_services.AddService_price',
        //         'transaction_status.TransacStatus_name',
        //         'transactions.Tracking_number',
        //         'transactions.Transac_datetime',
        //         'transaction_status.TransacStatus_name',
        //         'transaction_status.TransacStatus_datetime',
        //         'payments.Mode_of_Payment'
        //     )
        //     ->get();

        $trans = DB::table('transactions')
            ->leftJoin('customers', 'customers.Cust_ID', '=', 'transactions.Cust_ID')
            ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->leftJoin('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->leftJoin('additional_services', 'transactions.Transac_ID', '=', 'additional_services.Transac_ID')
            ->leftJoin('laundry_categories', 'laundry_categories.Categ_ID', '=', 'transaction_details.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->leftJoin('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
            ->leftJoin('admins', 'transactions.Admin_ID', '=', 'admins.Admin_ID')
            ->select(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                DB::raw("CONCAT(customers.Cust_fname, ' ', customers.Cust_mname, ' ', customers.Cust_lname) AS CustomerName"),
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_image',
                'customers.Cust_email',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                DB::raw("(SELECT TransacStatus_name
                    FROM transaction_status
                    WHERE transaction_status.Transac_ID = transactions.Transac_ID
                    AND transaction_status.TransacStatus_datetime = (
                        SELECT MAX(transaction_status.TransacStatus_datetime)
                        FROM transaction_status
                        WHERE transaction_status.Transac_ID = transactions.Transac_ID
                    )
                    LIMIT 1) AS latest_transac_status"),
                DB::raw("SUM(CASE
                    WHEN transaction_status.TransacStatus_datetime IS NOT NULL
                        AND transaction_status.TransacStatus_name = 'received'
                        AND transactions.Admin_ID = admins.Admin_ID
                    THEN transaction_details.Price ELSE 0 END) AS amount"),
                DB::raw("SUM(DISTINCT transaction_details.Price) as TotalPrice"),
                DB::raw("SUM(DISTINCT additional_services.AddService_price) as TotalService"),
                DB::raw("CASE
                    WHEN transaction_status.TransacStatus_name = 'received'
                        AND transactions.Admin_ID = admins.Admin_ID
                    THEN transaction_status.TransacStatus_datetime ELSE NULL END AS Received_datetime"),
                DB::raw("IF(payments.Mode_of_Payment IS NULL, 'unpaid', payments.Mode_of_Payment) AS payment"),
                DB::raw("CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM additional_services
                        WHERE additional_services.AddService_name = 'Pick-up Service'
                        AND additional_services.Transac_ID = transactions.Transac_ID
                    ) THEN 'Pick-up Service' ELSE 'Drop-off' END AS receiving_type")
            )
            ->whereNotIn('transaction_status.TransacStatus_name', ['completed', 'canceled', 'forRelease'])
            ->where('transactions.Transac_ID', $id)
            ->groupBy(
                'transactions.Transac_ID',
                'customers.Cust_ID',
                'customers.Cust_fname',
                'customers.Cust_mname',
                'customers.Cust_lname',
                'customers.Cust_phoneno',
                'customers.Cust_address',
                'customers.Cust_email',
                'customers.Cust_image',
                'admins.Admin_ID',
                'transactions.Admin_ID',
                'transaction_status.TransacStatus_name',
                'transactions.Tracking_number',
                'transactions.Transac_datetime',
                'transaction_status.TransacStatus_name',
                'transaction_status.TransacStatus_datetime',
                'payments.Mode_of_Payment'
            )
            ->get();

        if(is_null($trans)){
            return response()->json(['message' => 'Transaction not found'], 404);
        }
        return response()->json($trans,200);
    }

    public function showLaundryDetails($id){
        $laundryDetails = DB::table('transaction_details')
            ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->select(
                'laundry_categories.Categ_ID',
                'laundry_categories.Category',
                'laundry_categories.Price',
                'laundry_categories.Minimum_weight',
                'transaction_details.Qty',
                'transaction_details.Weight',
                'transaction_details.Price AS EachPrice',
                DB::raw('SUM(transaction_details.Price) AS TotalPrice'),
                'transaction_details.TransacDet_ID'
            )
            ->where('transaction_details.Transac_ID', $id)
            ->groupBy(
                'laundry_categories.Categ_ID',
                'laundry_categories.Category',
                'laundry_categories.Price',
                'laundry_categories.Minimum_weight',
                'transaction_details.Qty',
                'transaction_details.Weight',
                'transaction_details.Price',
                'transaction_details.TransacDet_ID'
            )
            ->get();

            if(is_null($laundryDetails)){
                return response()->json(['message' => 'Laundry Details not found'], 404);
            }
            return response()->json($laundryDetails,200);
    }

    public function getAddService($id){
        $service = DB::table('additional_services')
        ->select(
            'AddService_ID',
            'AddService_name AS result',
            'AddService_price AS service_price',
            DB::raw('SUM(AddService_price) AS TotalPrice'),
            'Transac_ID'
        )
        ->groupBy('Transac_ID', 'AddService_ID', 'AddService_name', 'AddService_price')
        ->where('Transac_ID', $id)

        // Using unionAll for the second part
        ->unionAll(
            DB::table(DB::raw('(SELECT NULL AS AddService_ID, "none" AS result, NULL AS service_price, 0 as TotalPrice, NULL AS Transac_ID) AS sub'))
            ->whereNotExists(function ($query) use ($id) {
                $query->select(DB::raw(1))
                    ->from('additional_services')
                    ->where('Transac_ID', $id);
            })
        )
        ->get();

        // Check if the service collection is empty
        if ($service->isEmpty()) {
            return response()->json(['message' => 'Additional Service not found'], 404);
        }

        return response()->json($service, 200);
    }


    public function totalPriceLaundry($id)
    {
        try {
            Log::info('totalPriceLaundry method started.', ['Transac_ID' => $id]);

            $totalPrice = DB::table('transaction_details')
                ->where('Transac_ID', $id)
                ->select(
                    DB::raw("SUM(Price) as LaundryTotal")
                )
                ->get();
                // ->sum('Price'); // Automatically handles null values as 0

            Log::info('Total price calculated.', ['Transac_ID' => $id, 'TotalPrice' => $totalPrice]);

            if ($totalPrice === 0) { // Check if no price data exists or the total is 0
                Log::warning('No laundry total found for the transaction.', ['Transac_ID' => $id]);
                return response()->json(['message' => 'No laundry total found for this transaction.'], 404);
            }

            Log::info('Laundry total retrieved successfully.', ['Transac_ID' => $id, 'LaundryTotal' => $totalPrice]);

            return response()->json($totalPrice, 200);
        } catch (\Exception $e) {
            Log::error('Error occurred in totalPriceLaundry.', ['Transac_ID' => $id, 'exception' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }


    public function saveLaundryDetails(Request $request)
    {
        foreach ($request->laundryDetails as $laundryDetail) {
            DB::table('transaction_details')
                ->where('TransacDet_ID', $laundryDetail['TransacDet_ID'])
                ->update([
                    'Weight' => $laundryDetail['Weight'],
                    'Price' => $laundryDetail['LaundryCharge'],
                ]);
        }

        return response()->json(['message' => 'Laundry details updated successfully.']);
    }

    public function saveServiceData(Request $request)
    {
        foreach ($request->services as $services) {
            DB::table('additional_services')
                ->where('Transac_ID', $services['Transac_ID'])
                ->where('AddService_ID', $services['AddService_ID'])
                ->update([
                    'AddService_price' => $services['AddService_price']
                ]);
        }

        return response()->json(['message' => 'Laundry details updated successfully.']);
    }


    public function submitLaundryTrans(Request $request, $id)
    {
        // Validate the input
        $validatedData = $request->validate([
            'staffId' => 'required',
            'amount' => 'nullable|numeric|min:0', // Validate amount if provided
        ]);

        $staffId = $validatedData['staffId'];
        $amount = $request->input('amount', 0); // Default to 0 if not provided

        try {
            $timezone = 'Asia/Manila';
            $localTime = Carbon::now($timezone);

            $result = DB::transaction(function () use ($id, $staffId, $amount, $localTime) {
                // Update `transactions` table
                $updated = DB::table('transactions')
                    ->where('Transac_ID', $id)
                    ->update([
                        'Admin_ID' => $staffId,
                    ]);

                // Update `transaction_status` table
                $insertTransStatus = DB::table('transaction_status')
                    ->where('Transac_ID', $id)
                    ->update([
                        'Admin_ID' => $staffId,
                        'TransacStatus_name' => 'received',
                        'TransacStatus_datetime' => $localTime,
                    ]);

                // Insert into `payments` table if amount is provided
                $insertPayment = true; // Default to true for cases where no payment is required
                if ($amount > 0) {
                    $insertPayment = DB::table('payments')
                        ->insert([
                            'Transac_ID' => $id,
                            'Admin_ID' => $staffId,
                            'Amount' => $amount,
                            'Mode_of_Payment' => 'cash',
                            'Datetime_of_Payment' => $localTime,
                        ]);
                }


                // Return true if any of the updates succeeded
                return $updated > 0 || $insertTransStatus > 0 || $insertPayment;
            });

            if ($result) {
                return response()->json(['message' => 'Laundry Details updated successfully.'], 200);
            } else {
                return response()->json(['message' => 'No changes were made.'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id){
        $validatedData = $request->validate([
            'status'=> 'required',
            'staffID' => 'required'
        ]);


        $validatedData['staffID'] != null;

        $updateStatus = DB::table('transactions')
        ->where('Transac_ID', $id)
        ->where('Admin_ID', $validatedData['staffID'])
        ->update(['Transac_status' => $validatedData['status']]);

        if ($updateStatus) {
            $timezone = 'Asia/Manila';

            $localTime = Carbon::now($timezone);

            $statusDatetime = DB::table('transaction_status')
                ->where('Transac_ID', $id)
                ->first();

            if($validatedData['status'] === 'received'){
                $del = DB::table('transaction_status')
                    ->where('Transac_ID', $id)
                    ->whereIn('TransacStatus_name', ['washing', 'forRelease'])
                    ->delete();
            }

            if($validatedData['status'] === 'washing'){
                $del = DB::table('transaction_status')
                    ->where('Transac_ID', $id)
                    ->where('TransacStatus_name', 'forRelease')
                    ->delete();
            }


            $status = DB::table('transaction_status')
            ->updateOrInsert(
                [
                    'Transac_ID' => $id,
                    'TransacStatus_name' => $validatedData['status']
                ],
                [
                    'Transac_ID'=> $id,
                    'TransacStatus_name' => $validatedData['status'],
                    'TransacStatus_datetime' => $localTime
                ]
            );

            return response()->json(['message' => 'Transaction status updated successfully.'], 200);
        } else {
            return response()->json(['message' => 'Update failed.'], 500);
        }
    }

    public function getForRel($id){
        $laundryDetails = DB::table('transaction_details')
            ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->select(
                'laundry_categories.Categ_ID',
                'laundry_categories.Category',
                'laundry_categories.Price',
                'transaction_details.Qty',
                'transaction_details.Weight',
                'transaction_details.Price AS EachPrice',
                DB::raw('SUM(transaction_details.Price) AS TotalPrice'),
                'transaction_details.TransacDet_ID'
            )
            ->where('transaction_details.Transac_ID', $id)
            ->groupBy(
                'laundry_categories.Categ_ID',
                'laundry_categories.Category',
                'laundry_categories.Price',
                'transaction_details.Qty',
                'transaction_details.Weight',
                'transaction_details.Price',
                'transaction_details.TransacDet_ID'
            )
            ->get();

            if(is_null($laundryDetails)){
                return response()->json(['message' => 'Laundry Details not found'], 404);
            }
            return response()->json($laundryDetails,200);

    }

    public function getAddServRel($id){
        $addServInfo = DB::table('additional_services')
            ->select(
                'AddService_ID',
                'Transac_ID',
                'AddService_name',
                'AddService_price',
                DB::raw('SUM(AddService_price) AS TotalServ_Price')
            )
            ->groupBy(
                'AddService_ID',
                'Transac_ID',
                'AddService_name',
                'AddService_price'
            )
            ->where('Transac_ID', $id)
            ->get();

        if(is_null($addServInfo)){
            return response()->json(['message' => 'No Additional Services are in for this transaction'], 404);
        }
        return response()->json($addServInfo, 200);
    }



    public function totalPriceService($id){
        $totalPrice = DB::table('additional_services')
            ->select(DB::raw('SUM(AddService_price) AS Total'))
            ->where('Transac_ID', $id)
            ->groupBy('Transac_ID')
            ->get();

            // if(is_null($totalPrice)){
            //     return response()->json(['message' => 'Laundry Details not found'], 404);
            // }
            if ($totalPrice->isEmpty()) {
                $totalPrice = 0;
            }

            return response()->json($totalPrice,200);
    }

    public function paymentStatus($id){
        $paymentStatus = DB::table('payments')
            ->select(
                'Payment_ID',
                'Admin_ID',
                'Transac_ID',
                'Amount',
                'Mode_of_Payment',
                'Datetime_of_Payment'
            )
            ->where('Transac_ID', $id)
            ->get();

            if ($paymentStatus->isEmpty()) {
                $paymentStatus = 'unpaid';
            }

            return response()->json($paymentStatus, 200);
    }
}
