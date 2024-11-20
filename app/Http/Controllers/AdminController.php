<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Admins;
use App\Models\Laundrycategories;
use App\Models\Payments;
use App\Models\Expenses;
use App\Models\Customers;
use App\Models\TransactionDetails;
use App\Models\Transactions;
use App\Models\Cashdetails;
// use App\Http\Requests\StoreAdminRequest;
// use App\Http\Requests\UpdateAdminRequest;

class AdminController extends Controller
{

    // ADMIN LOGIN
    public function login(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'Email' => 'required|Email',
            'Password' => 'required'
        ]);

        // Find the user based on the email
        $user = Admins::where('Email', $request->Email)->first();

        // Check if the user exists and the password is correct
        if (!$user || !Hash::check($request->Password, $user->Password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect'
            ], 401);
        }

        // Create a token for the authenticated user
        $token = $user->createToken($user->Admin_lname);

        // Return the token and user details
        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken
        ]);
    }
    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();
    
        return response()->json([
            'message' => 'You are logged out'
        ], 200);
    }
    public function addAdmin(Request $request)
    {
        $request->validate([
            'Admin_lname' => 'required|string|max:255',
            'Admin_fname' => 'required|string|max:255',
            'Admin_mname' => 'nullable|string|max:255',
            'Admin_image' => 'string',
            'Birthdate' => 'nullable|date',
            'Phone_no' => 'required|string|max:15',
            'Address' => 'required|string|max:255',
            'Role' => 'nullable|string|max:255',
            'Email' => 'required|email|max:255|unique:admins',
            'Password' => 'required|confirmed|min:6', 
        ]);

        $data = $request->all();
        $data['Password'] = bcrypt($request->Password);

        $staff = Admins::create($data);

        $staffList = Admins::orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Admin added successfully',
            'Admin' => $staff,
            'Adminlist' => $staffList
        ], 201);
    }

    // STAFF
    public function displaystaff(){

        $admins = Admins::orderBy('Admin_ID', 'desc')->get();

        $admins = $admins->map(function ($admin) {
            $admin->Admin_image = $admin->Admin_image
                ? asset('images/' . $admin->Admin_image)
                : null; 
            return $admin;
        });

        return response()->json($admins, 200);

    }
    
    public function findstaff(Request $request, $id)
    {    
        $staff = Admins::find($id);
        
        if (is_null($staff)) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        return response()->json($staff, 200);

    }
    public function addstaff(Request $request)
    {
        $request->validate([
            'Admin_lname' => 'required|string|max:255',
            'Admin_fname' => 'required|string|max:255',
            'Admin_mname' => 'nullable|string|max:255',
            'Admin_image' => 'string',
            'Birthdate' => 'nullable|date',
            'Phone_no' => 'required|string|max:15',
            'Address' => 'required|string|max:255',
            'Role' => 'nullable|string|max:255',
            'Email' => 'required|email|max:255|unique:admins',
            'Password' => 'required|confirmed|min:6', 
        ]);

        $data = $request->all();
        $data['Password'] = bcrypt($request->Password);

        $staff = Admins::create($data);

        $staffList = Admins::orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Staff added successfully',
            'staff' => $staff,
            'staffList' => $staffList
        ], 201);
    }
    public function updatestaff(Request $request, $id)
    {
        $staff = Admins::find($id);

        if (is_null($staff)) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $input = $request->all();

        if ($request->filled('Password')) {
            $input['Password'] = bcrypt($request->Password);
            $input['Oldpassword'] = $staff->Password;
        } else {
            unset($input['Password']); 
        }

        $staff->update($input);

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $staff
        ], 200);
    }

    public function deletestaff(Request $request, $id){
        $staff = Admins::find($id);
        if(is_null($staff)){
            return response()->json(['message' => 'Employee not Found'], 404);
        }
        $staff->delete();
        return response()->json(null,204);

    }
    // public function updateProfileImage(Request $request, $id)
    // {
    //     $request->validate([
    //         'Admin_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
    //     ]);
    
    //     $admin = Admins::findOrFail($id);
    
    //     if ($request->hasFile('Admin_image')) {
    //         if ($admin->Admin_image) {
    //             Storage::delete('public/profile_images/' . $admin->Admin_image);
                
    //             $htdocsImagePath = 'C:/xampp/htdocs/admin/profile_images/' . $admin->Admin_image;
    //             if (file_exists($htdocsImagePath)) {
    //                 unlink($htdocsImagePath);
    //             }
    //         }
    
    //         $extension = $request->Admin_image->extension();
    //         $imageName = time() . '_' . $admin->Admin_ID . '.' . $extension;
    //         $request->Admin_image->storeAs('public/profile_images', $imageName);
    
    //         $htdocsPath = 'c:/xampp/htdocs/admin/profile_images'; 
    
    //         if (!file_exists($htdocsPath)) {
    //             mkdir($htdocsPath, 0777, true);
    //         }
    
    //         $request->Admin_image->move($htdocsPath, $imageName);
    
    //         $admin->Admin_image = $imageName;
    //         $admin->save();
    
    //         return response()->json([
    //             'message' => 'Profile image updated successfully',
    //             'image_url' => asset('profile_images/' . $imageName) 
    //         ], 200);
    //     }
    //     $url = asset('storage/profile_images/' . $imageName);

    //     // return response()->json(['message' => 'No image file uploaded'], 400);
    //     return response()->json([
    //         'message' => 'Profile image updated successfully',
    //         'image_url' => asset('storage/profile_images/' . $imageName) 
    //     ], 200);
        
    // }
    public function updateProfileImage(Request $request, $id)
    {
        $request->validate([
            'Admin_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $admin = Admins::findOrFail($id);

        if ($request->hasFile('Admin_image')) {
            // Delete the old image if it exists
            if ($admin->Admin_image) {
                $oldImagePath = public_path('images/' . $admin->Admin_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Get the file extension and generate a unique name for the image
            $extension = $request->file('Admin_image')->extension();
            $imageName = time() . '_' . $admin->Admin_ID . '.' . $extension;

            // Move the file to the 'public/images' directory
            $destinationPath = public_path('images');
            $request->file('Admin_image')->move($destinationPath, $imageName);

            // Update the database with the new image name
            $admin->Admin_image = $imageName;
            $admin->save();

            // Generate the public URL for the new image
            $imageUrl = asset('images/' . $imageName);

            return response()->json([
                'message' => 'Profile image updated successfully',
                'image_url' => $imageUrl
            ], 200);
        }

        return response()->json(['message' => 'No image file uploaded'], 400);
    }



    // PRICE MANAGEMENT
    public function pricedisplay()
    {
        // return response()->json(Laundrycategorys::all(), 200);
        return response()->json(Laundrycategories::orderBy('Categ_ID', 'desc')->get(), 200);
    }
    public function addprice(Request $request)
    {
        $request->validate([
            'Category' => 'required|string',
            'Price' => 'required|numeric',
        ]);

        DB::table('laundry_categories')->insert([
            'Category' => $request->Category,
            'Price' => $request->Price,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $staffList = DB::table('laundry_categories')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Success',
            'data' => $staffList,
        ], 201);
    }
    public function deletecateg(Request $request, $id)
    {
        $pricecateg = Laundrycategories::find($id);
        if(is_null($pricecateg)){
            return response()->json(['message' => 'Employee not Found'], 404);
        }
        $pricecateg->delete();
        return response()->json(null,204);

    }
    public function updateprice(Request $request, $id)
    {
        $pricecateg = Laundrycategories::find($id);
        if(is_null($pricecateg)){
            return response()->json(['message' => 'Laundrycategorys not Found'], 404);
        }
        $pricecateg->update($request->all());
        return response($pricecateg, 200);
    }
    public function findprice($id)
    {   
        $pricecateg = Laundrycategories::find($id);
        
        if (is_null($pricecateg)) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        return response()->json($pricecateg, 200);
    }

     // DASHBOARD
     public function dashdisplays()
     {
         $date = now()->toDateString();  
 
         $payments = Payments::whereDate('Datetime_of_Payment', $date)->get();
         $totalAmount = $payments->sum('Amount');
 
         $totals = [
             'gcash' => 0,
             'cash' => 0,
             'bpi' => 0,
         ];
 
         $paymentsByMethod = [
             'gcash' => [],
             'cash' => [],
             'bpi' => [],
         ];
 
         foreach ($payments as $payment) {
             if (strtolower($payment->Mode_of_Payment) === 'gcash') {
                 $totals['gcash'] += $payment->Amount;
                 $paymentsByMethod['gcash'][] = $payment;
             } elseif (strtolower($payment->Mode_of_Payment) === 'cash') {
                 $totals['cash'] += $payment->Amount;
                 $paymentsByMethod['cash'][] = $payment;
             } elseif (strtolower($payment->Mode_of_Payment) === 'bpi') {
                 $totals['bpi'] += $payment->Amount;
                 $paymentsByMethod['bpi'][] = $payment;
             }
         }
 
         return response()->json([
             'payments' => $paymentsByMethod,
             'totals' => $totals,
             'total_amount' => $totalAmount
         ], 200);
     }
     public function dashdisplaysgraph(Request $request)
    {
        // Retrieve year and month from the request
        $year = $request->input('year');
        $month = $request->input('month');

        // Initialize the query
        $query = Payments::selectRaw('
                YEAR(Datetime_of_Payment) as year,
                MONTH(Datetime_of_Payment) as month,
                Mode_of_Payment,
                SUM(Amount) as total_amount
            ');

        // Apply filters for year and month if provided
        if ($year) {
            $query->whereYear('Datetime_of_Payment', $year);
        }
        if ($month) {
            $query->whereMonth('Datetime_of_Payment', $month);
        }

        // Group and order the results
        $payments = $query->groupBy('year', 'month', 'Mode_of_Payment')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $monthlyTotals = [];

        foreach ($payments as $payment) {
            $monthYearKey = $payment->year . '-' . str_pad($payment->month, 2, '0', STR_PAD_LEFT); 
            if (!isset($monthlyTotals[$monthYearKey])) {
                $monthlyTotals[$monthYearKey] = [
                    'gcash' => 0,
                    'cash' => 0,
                    'bpi' => 0,
                    'total_amount' => 0,
                ];
            }
            
            $monthlyTotals[$monthYearKey][$payment->Mode_of_Payment] += $payment->total_amount;
            $monthlyTotals[$monthYearKey]['total_amount'] += $payment->total_amount; 
        }

        return response()->json([
            'totals' => $monthlyTotals, // Grouped totals by month
        ], 200);
    }
     public function expensendisplays(){
         $date = now()->toDateString();  
 
         $payments = Expenses::whereDate('Datetime_taken', $date)->get();
 
         $totalAmount = $payments->sum('Amount');
         return response()->json([
             'total_amount' => $totalAmount,
             'expenses_det' => $payments
         ], 200);
     }
     public function displaystaffs(){
         // return response()->json(Admin::all(), 200);
         return response()->json(Admins::orderBy('Admin_ID', 'desc')->get(), 200);
     }
     public function cashinitial(Request $request)
     {
         // Validate the request input
         $request->validate([
             'Staff_ID' => 'required|string',
             'Initial_amount' => 'required|numeric|min:0',
         ]);
 
         DB::table('cash')->insert([
             'Staff_ID' => $request->Staff_ID,
             'Initial_amount' => $request->Initial_amount,
             'Fund_status' => 'Pending',
             'Datetime_InitialAmo' => now(),
         ]);
 
         $staffList = DB::table('cash')->orderBy('Datetime_InitialAmo', 'desc')->get();
 
         return response()->json([
             'message' => 'Success',
             'data' => $staffList,
         ], 201);
     }
     public function remittance(Request $request)
     {
         // Validate the request input
         $request->validate([
             'Admin_ID' => 'required|string',
             'Remittance' => 'required|numeric|min:0',
         ]);
 
         DB::table('cash')->insert([
             'Admin_ID' => $request->Admin_ID,
             'Remittance' => $request->Remittance,
             'Fund_status' => 'Pending',
             'Datetime_Remittance' => now(),
         ]);
 
         $staffList = DB::table('cash')->orderBy('Datetime_Remittance', 'desc')->get();
 
         return response()->json([
             'message' => 'Success',
             'data' => $staffList,
         ], 201);
     }
     public function veiwdeatils(){
         $payments = DB::table('cash')
             ->join('admins','cash.Staff_ID','=','admins.Admin_ID')
             ->select(
                 DB::raw("CONCAT(admins.Admin_fname, ' ', admins.Admin_mname, ' ', admins.Admin_lname) AS name"),
                 "cash.Cash_ID",
                 "cash.Initial_amount",
                 "cash.Datetime_InitialAmo",
             )
             ->get();
 
     
         return response()->json([
             'cashed' => $payments,
             // 'expenses' => $allExpenses
         ], 200);
     }
     public function CountDisplay()
    {
        $date = now()->toDateString();  
        // Count occurrences of each unique Tracking_number
        $trackingCounts = Transactions::select('Tracking_number', DB::raw('count(*) as total_count'))
            ->whereDate('Transac_date', $date)
            ->groupBy('Tracking_number')
            ->get();
        
        // Count total occurrences of all unique Tracking_numbers
        $totalTrackingCount = $trackingCounts->count('Tracking_number');

        // Returning the tracking numbers with their counts and the total count as a JSON response
        return response()->json([
            'tracking_counts' => $trackingCounts,
            'total_count' => $totalTrackingCount
        ], 200);
    }

    // CUSTOMERS
    public function customerdisplay(){
        return response()->json(Customers::all(), 200);
    }
    public function findcustomer($id)
    {   
        $customer = Customers::find($id);
        
        if (is_null($customer)) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        return response()->json($customer, 200);
    }
    public function findtrans($id)
    {
        $totalprice = TransactionDetails::where('transaction_details.Transac_ID', $id)
                                        ->sum('Price');
        $transaction = DB::table('transactions')
                    ->where('transactions.Cust_ID', $id)
                    ->LeftJoin('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
                    ->LeftJoin('customers', 'transactions.Cust_ID', '=', 'customers.Cust_ID')
                    ->LeftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
                    ->LeftJoin('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
                    ->join('admins', 'admins.Admin_ID', '=', 'transactions.Admin_ID')
                    ->select(
                            'transactions.Transac_ID',
                            'transactions.Tracking_number',
                            'transactions.Transac_date',
                            'transaction_status.Transac_status',
                            'transaction_status.TransacStatus_datetime',
                            // 'transactions.Received_datetime',
                            // 'transactions.Released_datetime',
                            'customers.Cust_ID', 
                            'customers.Cust_fname', 
                            'customers.Cust_lname', 
                            'admins.Admin_fname',
                            'admins.Admin_mname',
                            'admins.Admin_lname',
                            DB::raw('GROUP_CONCAT(CONCAT(transaction_details.Qty, "kgs ", laundry_categories.Category) SEPARATOR ", ") as Category'),
                            DB::raw('SUM(transaction_details.Price) as totalprice')
                    )
                    ->groupBy(
                            'transactions.Transac_ID',
                            'transactions.Tracking_number',
                            'transactions.Transac_date',
                            'transaction_status.Transac_status',
                            'transaction_status.TransacStatus_datetime',
                            // 'transactions.Received_datetime',
                            // 'transactions.Released_datetime',
                            'customers.Cust_ID', 
                            'customers.Cust_fname', 
                            'customers.Cust_lname', 
                            'admins.Admin_fname',
                            'admins.Admin_mname',
                            'admins.Admin_lname'
                    )
                    ->get();
    

            if ($transaction->isEmpty()) {
            return response()->json(['message' => 'Transaction not found'], 404);
            }

            return response()->json(['trans' => $transaction, 'price' => $totalprice], 200);
    }
    public function printtrans($id)
    {
        $totalprice = TransactionDetails::where('transaction_details.Transac_ID', $id)
                    ->sum('Price');
        $result = Transactions::where('transactions.Transac_ID', $id)
            ->join('customers', 'transactions.Cust_ID', '=', 'customers.Cust_ID')
            ->join('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->join('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->join('admins', 'admins.Admin_ID', '=', 'transactions.Admin_ID')
            ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->select(
                'transaction_details.Categ_ID',
                'transactions.Transac_ID',
                'transactions.Tracking_number',
                'transaction_status.Transac_status',
                // 'transactions.Released_datetime',
                // 'transactions.Staffincharge',
                'transaction_details.Qty',
                'transaction_details.Weight',
                'transaction_details.Price',
                'customers.Cust_Phoneno',
                'admins.Admin_fname',
                'admins.Admin_mname',
                'admins.Admin_lname',
                DB::raw('SUM(transaction_details.Price) as totalPrice'),
                DB::raw('GROUP_CONCAT(laundry_categories.Category SEPARATOR ", ") as Categories'),
                DB::raw('COUNT(DISTINCT transactions.Transac_ID) as total_count'),
                DB::raw('SUM(DISTINCT payments.Amount) as totalPaymentAmount'), // This aggregates the payment amounts
                DB::raw('SUM(payments.Amount) - SUM(transaction_details.Price) as balanceAmount'),
            )
            ->groupBy(
                'transaction_details.Categ_ID',
                'transactions.Transac_ID',
                'transaction_status.Transac_status',
                'transactions.Tracking_number',
                // 'transactions.Released_datetime',
                // 'transactions.Staffincharge',
                'transaction_details.Qty',
                'transaction_details.Weight',
                'transaction_details.Price',
                'customers.Cust_Phoneno',
                'admins.Admin_fname',
                'admins.Admin_mname',
                'admins.Admin_lname'
            )
            ->get();

        if ($result->isEmpty()) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        return response()->json(['data' => $result, 'price' => $totalprice], 200);
    }
    public function updateprofilecus(Request $request, $id)
    {
        $customer = Customers::find($id);

        if (is_null($customer)) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $input = $request->all();


        if ($request->filled('Cust_password')) {
            $input['Cust_password'] = bcrypt($request->Cust_password);
            $input['Cust_OldPassword'] = $staff->Cust_password;
        }
        else {
            unset($input['Cust_password']); 
        }

        $customer->update($input);

        return response()->json(['message' => 'Customer updated successfully', 'customer' => $customer], 200);
    }
    
    public function deletecustomer(Request $request, $id){
        $Customers = Customers::find($id);
        if(is_null($Customers)){
            return response()->json(['message' => 'Customer not Found'], 404);
        }
        $Customers->delete();
        return response()->json(null,204);

    }

    public function updateprofile(Request $request, $id)
    {
        $request->validate([
            'Cust_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
    
        $customer = Customers::findOrFail($id);
    
        if ($request->hasFile('Cust_image')) {
            if ($customer->Cust_image) {
                Storage::delete('public/profile/' . $customer->Cust_image);
                
                $htdocsImagePath = 'C:/xampp/htdocs/customer/profile/' . $customer->Cust_image;
                if (file_exists($htdocsImagePath)) {
                    unlink($htdocsImagePath);
                }
            }
    
            $extension = $request->Cust_image->extension();
            $imageName = time() . '_' . $customer->Cust_ID . '.' . $extension;
            // $request->Admin_image->storeAs('public/profile_images', $imageName);
    
            $htdocsPath = 'C:/xampp/htdocs/customer/profile'; 
    
            if (!file_exists($htdocsPath)) {
                mkdir($htdocsPath, 0777, true);
            }
    
            $request->Cust_image->move($htdocsPath, $imageName);
    
            $customer->Cust_image = $imageName;
            $customer->save();
    
            return response()->json([
                'message' => 'Profile image updated successfully',
                'image_url' => asset('profile/' . $imageName) 
            ], 200);
        }
    
        return response()->json(['message' => 'No image file uploaded'], 400);
    }

    // TRANSACTIONS
    public function Transadisplay()
    {
        $price = TransactionDetails::all();
        $date = now()->toDateString();  

        $totalprice = $price->sum('Price');

        $data = Transactions::join('customers', 'transactions.Cust_ID', '=', 'customers.Cust_ID')
        ->join('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
        ->join('admins', 'admins.Admin_ID', '=', 'transactions.Admin_ID')
        ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
        ->whereDate('transactions.Transac_date', $date)
        ->select(
            'transactions.Transac_ID',
            'transactions.Tracking_number',
            'transactions.Transac_date',
            'transactions.Transac_status',
            'transactions.Received_datetime',
            'transactions.Released_datetime',
            // 'transactions.Staffincharge',
            'customers.Cust_fname', 
            'customers.Cust_lname', 
            'admins.Admin_fname',
            'admins.Admin_mname',
            'admins.Admin_lname',
            DB::raw('GROUP_CONCAT(CONCAT(transaction_details.Qty, "kgs ",laundry_categories.Category) SEPARATOR ", ") as Category'),
            DB::raw('SUM(transaction_details.Price) as totalprice')
        )
        ->groupBy(
            'transactions.Transac_ID',
            'transactions.Tracking_number',
            'transactions.Transac_date',
            'transactions.Transac_status',
            'transactions.Received_datetime',
            'transactions.Released_datetime',
            // 'transactions.Staffincharge',
            'customers.Cust_fname', 
            'customers.Cust_lname', 
            'admins.Admin_fname',
            'admins.Admin_mname',
            'admins.Admin_lname'
        )
        ->get();

        return response()->json([
            'data' => $data,
            'totalsprice' => $totalprice,
        ], 200);
    }

    public function remittanceapproved(Request $request)
    {
        $results = DB::table('cash')
            ->leftJoin('expenses', function ($join) {
                $join->on(DB::raw('YEAR(cash.Datetime_Remittance)'), '=', DB::raw('YEAR(expenses.Datetime_taken)'))
                    ->on(DB::raw('MONTH(cash.Datetime_Remittance)'), '=', DB::raw('MONTH(expenses.Datetime_taken)'))
                    ->on(DB::raw('DAY(cash.Datetime_Remittance)'), '=', DB::raw('DAY(expenses.Datetime_taken)'));
            })
            ->leftJoin('payments', function ($join) {
                $join->on(DB::raw('YEAR(cash.Datetime_Remittance)'), '=', DB::raw('YEAR(payments.Datetime_of_Payment)'))
                    ->on(DB::raw('MONTH(cash.Datetime_Remittance)'), '=', DB::raw('MONTH(payments.Datetime_of_Payment)'))
                    ->on(DB::raw('DAY(cash.Datetime_Remittance)'), '=', DB::raw('DAY(payments.Datetime_of_Payment)'));
            })
            ->join('admins', function ($join) {
                $join->on('cash.Staff_ID', '=', 'admins.Admin_ID')
                    ->orOn('cash.Admin_ID', '=', 'admins.Admin_ID');
            })
            ->select(
                'cash.Cash_ID',
                'cash.Admin_ID',
                'cash.Staff_ID',
                'cash.Fund_status',
                DB::raw('GROUP_CONCAT(DISTINCT admins.Admin_fname, " ", admins.Admin_mname, " ", admins.Admin_lname SEPARATOR ", ") as name'),
                DB::raw('COALESCE(SUM(DISTINCT cash.Remittance), 0) AS remitAmount'),
                DB::raw('COALESCE(SUM(DISTINCT cash.Initial_amount), 0) AS initialAmount'),
                DB::raw('COALESCE(SUM(DISTINCT expenses.Amount), 0) AS ExpensesAmount'), // Return 0 if no expenses found
                DB::raw('COALESCE(SUM(DISTINCT payments.Amount), 0) AS paymentAmount'),

                DB::raw('MONTH(cash.Datetime_Remittance) AS remitMonth'),
                DB::raw('DAY(cash.Datetime_Remittance) AS remitDay'),
                DB::raw('YEAR(cash.Datetime_Remittance) AS remitYear'),

                DB::raw('MONTH(payments.Datetime_of_Payment) AS paymentMonth'),
                DB::raw('DAY(payments.Datetime_of_Payment) AS paymentDay'),
                DB::raw('YEAR(payments.Datetime_of_Payment) AS paymentYear')
            )
            ->groupBy(
                'cash.Admin_ID',
                'cash.Cash_ID',
                'cash.Staff_ID',
                'cash.Fund_status',
                'remitMonth',
                'remitDay',
                'remitYear',
                'paymentMonth',
                'paymentDay',
                'paymentYear'
            )
            ->orderBy('Cash_ID', 'desc')
            ->get();


        // Calculate additional fields based on correct field names
        foreach ($results as &$data) {
            $data->netIncome = $data->remitAmount - $data->initialAmount - $data->ExpensesAmount;
            
            // Total transactions and collections
            $data->totaltransac = $data->paymentAmount + $data->initialAmount;
            $data->totalcollec = $data->totaltransac - $data->ExpensesAmount;
            
            // Total profit calculation with conditions
            if ($data->remitAmount > 0 && $data->paymentAmount > 0) {
                $data->totalprofit = $data->remitAmount - $data->totalcollec;
            } else {
                $data->totalprofit = 0; // Set to zero if there are no remittances or payments
            }
        }        
        
        return response()->json($results, 200);


    }

    public function printTransac(Request $request,$id)
    {
        $results = DB::table('cash')
            ->leftJoin('expenses', function ($join) {
                $join->on(DB::raw('YEAR(cash.Datetime_Remittance)'), '=', DB::raw('YEAR(expenses.Datetime_taken)'))
                    ->on(DB::raw('MONTH(cash.Datetime_Remittance)'), '=', DB::raw('MONTH(expenses.Datetime_taken)'))
                    ->on(DB::raw('DAY(cash.Datetime_Remittance)'), '=', DB::raw('DAY(expenses.Datetime_taken)'));
            })
            ->leftJoin('payments', function ($join) {
                $join->on(DB::raw('YEAR(cash.Datetime_Remittance)'), '=', DB::raw('YEAR(payments.Datetime_of_Payment)'))
                    ->on(DB::raw('MONTH(cash.Datetime_Remittance)'), '=', DB::raw('MONTH(payments.Datetime_of_Payment)'))
                    ->on(DB::raw('DAY(cash.Datetime_Remittance)'), '=', DB::raw('DAY(payments.Datetime_of_Payment)'));
            })
            ->join('admins', function ($join) {
                $join->on('cash.Staff_ID', '=', 'admins.Admin_ID')
                    ->orOn('cash.Admin_ID', '=', 'admins.Admin_ID');
            })
            ->where('cash.Cash_ID',$id)
            ->select(
                'cash.Cash_ID',
                'cash.Admin_ID',
                'cash.Staff_ID',
                'cash.Fund_status',
                DB::raw('GROUP_CONCAT(DISTINCT admins.Admin_fname, " ", admins.Admin_mname, " ", admins.Admin_lname SEPARATOR ", ") as name'),
                DB::raw('COALESCE(SUM(DISTINCT cash.Remittance), 0) AS remitAmount'),
                DB::raw('COALESCE(SUM(DISTINCT cash.Initial_amount), 0) AS initialAmount'),
                DB::raw('COALESCE(SUM(DISTINCT expenses.Amount), 0) AS ExpensesAmount'), // Return 0 if no expenses found
                DB::raw('COALESCE(SUM(DISTINCT payments.Amount), 0) AS paymentAmount'),

                DB::raw('MONTH(cash.Datetime_Remittance) AS remitMonth'),
                DB::raw('DAY(cash.Datetime_Remittance) AS remitDay'),
                DB::raw('YEAR(cash.Datetime_Remittance) AS remitYear'),

                DB::raw('MONTH(payments.Datetime_of_Payment) AS paymentMonth'),
                DB::raw('DAY(payments.Datetime_of_Payment) AS paymentDay'),
                DB::raw('YEAR(payments.Datetime_of_Payment) AS paymentYear')
            )
            ->groupBy(
                'cash.Admin_ID',
                'cash.Cash_ID',
                'cash.Staff_ID',
                'cash.Fund_status',
                'remitMonth',
                'remitDay',
                'remitYear',
                'paymentMonth',
                'paymentDay',
                'paymentYear'
            )
            ->orderBy('Cash_ID', 'desc')
            ->get();


        // Calculate additional fields based on correct field names
        foreach ($results as &$data) {
            $data->netIncome = $data->remitAmount - $data->initialAmount - $data->ExpensesAmount;
            
            // Total transactions and collections
            $data->totaltransac = $data->paymentAmount + $data->initialAmount;
            $data->totalcollec = $data->totaltransac - $data->ExpensesAmount;
            
            // Total profit calculation with conditions
            if ($data->remitAmount > 0 && $data->paymentAmount > 0) {
                $data->totalprofit = $data->remitAmount - $data->totalcollec;
            } else {
                $data->totalprofit = 0; // Set to zero if there are no remittances or payments
            }
        }        
        
        return response()->json($results, 200);
    }

    public function approveremit($id){
        $approve = Cashdetails::where('Cash_ID', $id)
        ->update(['Fund_status' => 'Approve']);

        return $approve;
    }
    


    // REPORT
    public function displayexpenses()
    {
        $price = Expenses::join('admins', 'admins.Admin_ID', '=', 'expenses.Admin_ID')
            ->select(
                DB::raw('GROUP_CONCAT(DISTINCT admins.Admin_lname SEPARATOR ", ") as adminNames'),
                DB::raw('GROUP_CONCAT(DISTINCT expenses.Desc_reason SEPARATOR ", ") as reason'),
                DB::raw('GROUP_CONCAT(DISTINCT expenses.Receipt_filenameimg SEPARATOR ", ") as image'),
                DB::raw('SUM(DISTINCT expenses.Amount) as totalExpenses'), 
                DB::raw('COALESCE(DATE(expenses.Datetime_taken)) as transactionDate'), 
            ) 
            ->groupBy(
                'transactionDate'
            )
            ->orderBy('expenses.Datetime_taken', 'desc')  
            ->get();

        $totalAmount = Expenses::sum('Amount');

        // Return the result in JSON format
        return response()->json(["price" => $price, 'totalAmount' => $totalAmount], 200);
    }
    
    public function displayincome(Request $request)
    {
        $allTransactions = DB::table('expenses')
            // ->join('payments', function($join) {
            //     $join->on('expenses.Datetime_taken', '=', 'payments.Datetime_of_Payment');
            // })
            ->join('payments', function($join) {
                $join->on(DB::raw('YEAR(expenses.Datetime_taken)'), '=', DB::raw('YEAR(payments.Datetime_of_Payment)'))
                     ->on(DB::raw('MONTH(expenses.Datetime_taken)'), '=', DB::raw('MONTH(payments.Datetime_of_Payment)'))
                     ->on(DB::raw('DAY(expenses.Datetime_taken)'), '=', DB::raw('DAY(payments.Datetime_of_Payment)'));
            })
            // ->join('cash', function($join) {
            //     $join->on('expenses.Datetime_taken', '=', 'cash.Datetime_Remittance');
            // })
            ->join('cash', function($join) {
                $join->on(DB::raw('YEAR(expenses.Datetime_taken)'), '=', DB::raw('YEAR(cash.Datetime_Remittance)'))
                     ->on(DB::raw('MONTH(expenses.Datetime_taken)'), '=', DB::raw('MONTH(cash.Datetime_Remittance)'))
                     ->on(DB::raw('DAY(expenses.Datetime_taken)'), '=', DB::raw('DAY(cash.Datetime_Remittance)'));
            })
            ->join('admins', function($join) {
                $join->on('cash.Staff_ID', '=', 'admins.Admin_ID');
            })
            // ->leftJoin('expenses', 'expenses.Admin_ID', '=', 'payments.Admin_ID')
            ->select(
                'cash.Staff_ID',
                DB::raw('COALESCE(DATE(payments.Datetime_of_Payment), DATE(expenses.Datetime_taken)) as transactionDate'), 
                DB::raw('SUM(DISTINCT payments.Amount) as totalPayments'), 
                DB::raw('SUM(DISTINCT expenses.Amount) as totalExpenses'), 
                DB::raw('SUM(DISTINCT payments.Amount) - SUM(DISTINCT expenses.Amount) as total'),
                DB::raw('GROUP_CONCAT(DISTINCT admins.Admin_lname SEPARATOR ", ") as adminNames')
            )
            ->groupBy('cash.Staff_ID',DB::raw('transactionDate'))
            ->orderBy('transactionDate', 'desc') 
            ->get();

        $expense = Expenses::sum('Amount');
        $payments = Payments::sum('Amount');

        $total =  $payments - $expense;
        
        return response()->json([
            'transactions' => $allTransactions,
            'totalExpense' => $expense,
            'totalPayments' => $payments,
            'total' =>  $total
        ], 200);
    }


    //ACCOUNT
    public function admin(Request $request, $id)
    {    
        $staff = Admins::find($id);
        
        if (is_null($staff)) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        return response()->json($staff, 200);

    }
    public function updateaccount(Request $request, $id)
    {
        $staff = Admins::find($id);

        if (is_null($staff)) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $input = $request->all();

        if ($request->filled('Password')) {
            $input['Password'] = bcrypt($request->Password);
            $input['Admin_OldPassword'] = $staff->Password;
        } else {
            unset($input['Password']); 
        }

        $staff->update($input);

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $staff
        ], 200);
    }




}
