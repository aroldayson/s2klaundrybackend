<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Admins;
use App\Models\Laundrycategories;
use App\Models\Payments;
use App\Models\Expenses;
use App\Models\Customers;
use App\Models\TransactionDetails;
use App\Models\Transactions;
use App\Models\Cashdetails;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{

    //login
    public function login(Request $request){

        // return $request;
        $request->validate([
            'email'=> 'required|email|exists:customers,cust_email',    
            'password'=> 'required'
        ]);

        $user = Customers::where('Cust_email', $request->email)->first();
        
        if(!$user){
            return ['message'=>'The provided credentials are incorrect'];
        }

        $custid = $user->Cust_ID;
        $token = $user->createToken($user->Cust_lname);
        return [
            'user'=>$user,
            'userid'=>$custid,
            'token'=>$token->plainTextToken
        ];
    }
    public function logout(Request $request) 
    {
        $request->user()->tokens()->delete();
    
        return response()->json([
            'message' => 'You are logged out'
        ], 200);
    }
    public function signup(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'Cust_fname'    => 'required|string|max:255',
            'Cust_lname'    => 'required|string|max:255',
            'Cust_mname'    => 'nullable|string|max:255',
            'Cust_phoneno'  => 'required|string|max:20',
            'Cust_email'    => 'required|email|unique:customers,Cust_email',
            'Cust_address'  => 'required|string|max:500',
            'Cust_password' => 'required|string|min:8',
            'Cust_image' => 'nullable|string'
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        try {
            // Create the customer
            $customer = Customers::create([
                'Cust_fname'   => $request->Cust_fname,
                'Cust_lname'   => $request->Cust_lname,
                'Cust_mname'   => $request->Cust_mname,
                'Cust_phoneno' => $request->Cust_phoneno,
                'Cust_email'   => $request->Cust_email,
                'Cust_address' => $request->Cust_address,
                'Cust_password'=> bcrypt($request->Cust_password),  // Encrypt the password
                'Cust_image'   => $request->Cust_image
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer created successfully',
                'customer' => $customer
            ], 201);

        } catch (\Exception $e) {
            // Return an error message in case of failure (like duplicate entry)
            return response()->json([
                'status' => 'error',
                'message' => 'Duplicate entry or some other issue',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    //home
    public function gethis($id) 
    {
        Log::info('Customer ID:', ['id' => $id]);
        $temp = DB::table('transactions')
        
        ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
        ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
        ->select(
            'transactions.Transac_ID',
            'transactions.Tracking_number as trans_tracking_number',
            'transactions.Cust_ID',
            'transactions.Tracking_number',
            'transactions.Transac_date',
            'transactions.Transac_status',
            'transactions.Received_datetime',
            'transactions.Released_datetime',
            DB::raw('COALESCE(CAST(payments.amount AS CHAR), "No Payment") as payment_amount'),
            DB::raw('COALESCE(payments.Mode_of_Payment, "No Mode of Payment") as Mode_of_Payment'),
            // DB::raw('IF(transaction_details.Transac_ID IS NULL, "Cancelled", transactions.Transac_status) as service')
        )
        ->where('transactions.Cust_ID', $id)
      
        // Exclude rows where both payments.amount and payments.Mode_of_Payment are NULL
        // ->where(function($query) {
        //     $query->whereNotNull('payments.amount')
        //           ->orWhereNotNull('payments.Mode_of_Payment');
        // })
        ->groupBy(
            'transactions.Transac_ID',
            'transactions.Cust_ID',
            'transactions.Tracking_number',
            'transactions.Transac_date',
            'transactions.Transac_status',
            'transactions.Received_datetime',
            'transactions.Released_datetime',
            'trans_tracking_number',
            'payment_amount',
            'Mode_of_Payment'
        )
        ->get();

      
        return $temp;
    }
    
    public function getlist()
    {
        // $temp = DB::table('laundry_categorys')
        //         ->get();

        return response()->json(Laundrycategories::orderBy('Price','asc')->get(), 200);

        // return $temp;
    }
    
    public function updatetrans(Request $request)
    {
        // Adjust validation to handle updates and newEntries correctly
        $validatedData = $request->validate([
            'updates.*.Categ_ID' => 'required|integer',           // Validate each Categ_ID in the updates array
            'updates.*.Qty' => 'required|integer',                // Validate each Qty in the updates array
            'updates.*.TransacDet_ID' => 'required|integer',      // Validate each TransacDet_ID in the updates array
            'updates.*.Transac_status' => 'required|string',      // Validate each Transac_status in the updates array
    
            'newEntries.*.Categ_ID' => 'required|integer',        // Validate each Categ_ID in newEntries array
            'newEntries.*.Qty' => 'required|integer',             // Validate each Qty in newEntries array
            'newEntries.*.Tracking_number' => 'required|string',  // Validate each Tracking_number in newEntries array
        ]);
    
        try {
            DB::beginTransaction();
    
            // Loop over updates and apply them
            if (!empty($validatedData['updates'])) {
                foreach ($validatedData['updates'] as $data) {
                    // Update transaction details table
                    DB::table('transaction_details')
                        ->where('TransacDet_ID', $data['TransacDet_ID'])
                        ->update([
                            'Categ_ID' => $data['Categ_ID'],
                            'Qty' => $data['Qty'],
                        ]);
    
                    // Fetch the Tracking_number from transaction_details
                    $trackingNumber = DB::table('transaction_details')
                        ->where('TransacDet_ID', $data['TransacDet_ID'])
                        ->value('Tracking_number');
    
                    // Update the transactions table
                    DB::table('transactions')
                        ->where('Tracking_number', $trackingNumber)
                        ->update([
                            'Transac_status' => $data['Transac_status'],
                        ]);
                }
            }
    
            // Handle newEntries if present
            if (!empty($validatedData['newEntries'])) {
                foreach ($validatedData['newEntries'] as $data) {
                    // Insert new transaction details
                    DB::table('transaction_details')->insert([
                        'Categ_ID' => $data['Categ_ID'],
                        'Qty' => $data['Qty'],
                        'Tracking_number' => $data['Tracking_number'],
                    ]);
                }
            }
    
            DB::commit();
    
            return response()->json(['message' => 'Transaction updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    public function display($id)
    {
        $transactions = DB::table('transactions')
            ->join('customers', 'transactions.Cust_ID', '=', 'customers.Cust_ID')
            ->join('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->leftJoin('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
            ->select(
                DB::raw('GROUP_CONCAT(laundry_categories.Category SEPARATOR ", ") as Category'),
                'customers.Cust_fname as fname',
                'customers.Cust_lname as lname',
                'transactions.Received_datetime as rec_date',
                'transactions.Released_datetime as rel_date',
                DB::raw('SUM(transaction_details.Qty) as totalQty'),
                DB::raw('SUM(transaction_details.Weight) as totalWeight'),
                DB::raw('SUM(transaction_details.Price) as totalprice'),
                'transactions.Tracking_number as track_num',
                'transactions.Transac_date as trans_date',
                'transactions.Transac_status as trans_stat',
                'transactions.Transac_ID as trans_ID'
            )
            ->where('transactions.Cust_ID', $id)
            ->groupBy(
                'customers.Cust_fname',
                'customers.Cust_lname',
                'transactions.Received_datetime',
                'transactions.Released_datetime',
                'transactions.Tracking_number',
                'transactions.Transac_date',
                'transactions.Transac_status',
                'transactions.Transac_ID'
            )
            ->get();
    
        return response()->json(['transaction' => $transactions]);
    }
    
    // public function store(Request $request)
    // {
    //     // Validate the request data
    //     $validatedData = $request->validate([
    //         'id' => 'required|integer', // Cust_ID
    //         'trackingNumber' => 'required|string|max:255|unique:transactions,Tracking_number', // Primary key and uniqueness check
    //         'laundry' => 'required|array',
    //         'laundry.*.Categ_ID' => 'required|integer',
    //         'laundry.*.Qty' => 'required|integer',
    //         'Transac_status' => 'required|string'
    //     ]);
    
    //     try {
    //         $transaction = new Transactions();
    //         // Step 1: Insert into the `transactions` table
    //         $transaction->Transac_status = $validatedData['Transac_status']; // Get Transac_status from the object
    //         $transaction->Cust_ID = $validatedData['id']; // Cust_ID
    //         $transaction->Admin_ID = 0; // Assuming no Admin_ID for now
    //         $transaction->Transac_date = now();
    //         $transaction->Tracking_number = $validatedData['trackingNumber']; // Tracking_number as PK
    //         $transaction->Received_datetime = now(); // Initially set to now
    //         $transaction->Released_datetime = now(); // Initially set to now
            
    //         $transaction->save(); // This will save the transaction and return the ID
    
    //         // Get the transaction ID after saving
    //         $transactionId = $transaction->Transac_ID; // Use the correct property for the primary key
    
    //         // Step 2: Insert each laundry item into `transaction_details` table
    //         foreach ($validatedData['laundry'] as $item) {
    //             $detail = new TransactionDetails();
    //             $detail->Categ_ID = $item['Categ_ID'];
    //             $detail->Transac_ID = $transactionId; // Use the transaction ID
    //             $detail->Qty = $item['Qty'];
    //             $detail->Weight = 0; // Set Weight to 0 or another appropriate value
    //             $detail->Price = 0; // Set Price to 0 or another appropriate value
    //             $detail->save(); // Save each transaction detail
    //         }
    
    //         // Step 3: Return a success message
    //         return response()->json(['message' => 'Transaction successfully created'], 201);
    
    //     } catch (\Exception $e) {
    //         // Handle any errors that occur
    //         return response()->json(['error' => 'Error inserting transaction: ' . $e->getMessage()], 500);
    //     }
    // }
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'id' => 'required|integer', // Cust_ID
            'trackingNumber' => 'required|string|max:255|unique:transactions,Tracking_number', // Primary key and uniqueness check
            'laundry' => 'required|array',
            'laundry.*.Categ_ID' => 'required|integer',
            'laundry.*.Qty' => 'required|integer',
            'Transac_status' => 'required|string'
        ]);
    
        try {
            $transaction = new Transactions();
            // Step 1: Insert into the transactions table
            $transaction->Transac_status = $validatedData['Transac_status']; // Get Transac_status from the object
            $transaction->Cust_ID = $validatedData['id']; // Cust_ID
            $transaction->Admin_ID = 0; // Assuming no Admin_ID for now
            $transaction->Transac_date = now();
            $transaction->Tracking_number = $validatedData['trackingNumber']; // Tracking_number as PK
            $transaction->Received_datetime = now(); // Initially set to now
            $transaction->Released_datetime = now(); // Initially set to now
            
            $transaction->save(); // This will save the transaction and return the ID
    
            // Get the transaction ID after saving
            $transactionId = $transaction->Transac_ID; // Use the correct property for the primary key
    
            // Step 2: Insert each laundry item into transaction_details table
            foreach ($validatedData['laundry'] as $item) {
                $detail = new TransactionDetails();
                $detail->Categ_ID = $item['Categ_ID'];
                $detail->Transac_ID = $transactionId; // Use the transaction ID
                $detail->Qty = $item['Qty'];
                $detail->Weight = 0; // Set Weight to 0 or another appropriate value
                $detail->Price = 0; // Set Price to 0 or another appropriate value
                $detail->save(); // Save each transaction detail
            }
    
            // Step 3: Return a success message
            return response()->json(['message' => 'Transaction successfully created'], 201);
    
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json(['error' => 'Error inserting transaction: ' . $e->getMessage()], 500);
        }
    }

    public function displayDet($id) {
        $temp = DB::table('transactions')
            ->leftJoin('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->leftJoin('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->select('transactions.*', 'transaction_details.*','laundry_categories.*') // Make sure to select from the correct alias
            ->where('transactions.Tracking_number', $id)
            ->get();
    
        return $temp;
        // return response()->json(['updatetransaction' => $temp],201);
    }

    public function insertDetails(Request $request)
    {
        // Validate each item in the request array
        $validatedData = $request->validate([
            '*.Categ_ID' => 'required|integer',        // Category ID is required
            '*.Qty' => 'required|integer',             // Quantity is required
            '*.Tracking_number' => 'required|string',  // Tracking number is required, no Transac_status
        ]);
    
        try {
            // Insert all validated data into the transaction_details table
            DB::table('transaction_details')->insert($validatedData);
            return response()->json(['message' => 'New transaction details added successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateTransactionStatus($trackingNumber, $transacStatus)
    {
        // Validate input if necessary
        if (empty($trackingNumber) || empty($transacStatus)) {
            throw new \InvalidArgumentException('Tracking number and status are required.');
        }

        try {
            // Update the transaction status in the transactions table
            $updated = DB::table('transactions')
                ->where('Tracking_number', $trackingNumber)
                ->update(['Transac_status' => $transacStatus]);

            return $updated; // Return the number of affected rows
        } catch (\Exception $e) {
            // Handle any exceptions
            throw new \Exception('Error updating transaction status: ' . $e->getMessage());
        }
    }

    public function deleteDetails(Request $request)
    {
        $validatedData = $request->validate([
            'deletedEntries' => 'required|array',      // Expect an array of TransacDet_IDs
            'deletedEntries.*' => 'required|integer',  // Each entry must be an integer (the TransacDet_ID)
        ]);

        try {
            DB::table('transaction_details')
                ->whereIn('TransacDet_ID', $validatedData['deletedEntries'])
                ->delete();

            return response()->json(['message' => 'Transaction details deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function cancelTrans(Request $request, $id){
        $transactions = DB::table('transactions')
            ->join('customers', 'transactions.Cust_ID', '=', 'customers.Cust_ID')
            ->join('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->select(
                'transactions.Tracking_number',
                'transactions.Transac_date',
                'transactions.Transac_status',
                'transactions.Received_datetime',
                'transactions.Released_datetime',
                'customers.Cust_fname', 
                'customers.Cust_lname', 
                DB::raw('GROUP_CONCAT(laundry_categories.Category SEPARATOR ", ") as Category'),
                DB::raw('SUM(transaction_details.Price) as totalprice'),
                DB::raw('SUM(transaction_details.Qty) as totalQty'),
                DB::raw('SUM(transaction_details.Weight) as totalWeight')
            )
            ->groupBy(
                'transactions.Tracking_number',
                'transactions.Transac_date',
                'transactions.Transac_status',
                'transactions.Received_datetime',
                'transactions.Released_datetime',
                'customers.Cust_fname', 
                'customers.Cust_lname', 
            )
            ->get();

            Transactions::where('Tracking_number', $id)
                ->update(['Transac_status' => 'cancel']);

        return response()->json(['transaction' => $transactions], 200);
    }

    private function insertPayment($trackingNumber, $modeOfPayment, $amount,$cust_id)
    {
        return DB::table('payments')->insertGetId([
            'Transac_ID' => $trackingNumber,
            'Amount' => $amount,
            'Mode_of_Payment' => $modeOfPayment, 
            'Datetime_of_Payment' => now(),
            'Cust_ID' => $cust_id 
        ]);
    }

    public function insertProofOfPayment($paymentId)
    {
        return DB::table('proof_of_payments')->insertGetId([
            'Payment_ID' => $paymentId,
            'Proof_filename' => '', // Placeholder, will be updated with file
            'Upload_datetime' => now()
        ]);
    }
    private function handleImageUpload($request, $proofPayment)
    {
        // If there's an existing image, delete it from storage
        if ($proofPayment->Pro_filename) {
            // Delete from public storage
            Storage::disk('public')->delete('profile_images/' . $proofPayment->Pro_filename);

            // Also delete from htdocs if the image exists there
            $htdocsImagePath = public_path('profile_images/' . $proofPayment->Pro_filename);
            if (file_exists($htdocsImagePath)) {
                unlink($htdocsImagePath); // Delete from htdocs as well
            }
        }

        // Generate a new image name based on the timestamp and Proof ID
        $extension = $request->Pro_filename->extension();
        $imageName = time() . '_' . $proofPayment->Pro_ID . '.' . $extension;

        // Store the image in Laravel's public storage
        $request->Pro_filename->storeAs('public/receipt', $imageName);

        // Define the htdocs path (ensure the directory exists)
        $htdocsPath = 'D:\larabells\api-app1\public\storage\receipt';
        if (!file_exists($htdocsPath)) {
            mkdir($htdocsPath, 0777, true); // Create the folder if it doesn't exist
        }

        // Move the uploaded image to htdocs
        $request->Pro_filename->move($htdocsPath, $imageName);

        // Update the image filename in the proof_of_payments table
        DB::table('proof_of_payments')
            ->where('Proof_ID', $proofPayment->Pro_ID)
            ->update(['Proof_filename' => $imageName]);
    }

    //transactions
    public function getTransId($id){
        $temp = DB::table('transaction_details')
                ->where('TransacDet_ID',$id)
                ->get();
                
        return $temp;
    }
    public function getDetails($id){
        $temp = DB::table('transactions')
            ->where('Transac_ID', $id)
            ->get();
    
        $transactions = [];
    
        foreach($temp as $t){
            $transaction_details = DB::table('transaction_details')
                ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
                ->select(
                    'transaction_details.Transac_ID',
                    'transaction_details.TransacDet_ID',
                    'transaction_details.Price as price',
                    'laundry_categories.Category'
                )
                ->where('Transac_ID', $t->Transac_ID)
                ->get();
    
            $transactions[] = [
                'Tracking_number' => $t->Tracking_number,
                'status' => $t->Transac_status,
                'total' => $transaction_details->sum('price'),
                'details' => $transaction_details,
            ];
        }
    
        return $transactions;
    }
    
    //account
    public function updateProfileImage(Request $request, $trackingNumber)
    {
        $request->validate([
            'Proof_filename' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'Mode_of_Payment' => 'required|string',
            'Amount' => 'required|numeric',
            'Cust_ID' => 'required|string'
        ]);

        try {
            $proofPayment = DB::table('transactions')
                ->join('payments', 'transactions.Transac_ID', '=', 'payments.Transac_ID')
                ->join('proof_of_payments', 'payments.Payment_ID', '=', 'proof_of_payments.Payment_ID')
                ->where('transactions.Tracking_number', $trackingNumber)
                ->select('payments.*', 'proof_of_payments.*')
                ->first();

            if (!$proofPayment) {
                $paymentId = $this->insertPayment($trackingNumber, $request->Mode_of_Payment, $request->Amount,  $request->Cust_ID);
                $proofId = $this->insertProofOfPayment($paymentId);

                $proofPayment = DB::table('proof_of_payments')->where('Proof_ID', $proofId)->first();
            }

            if ($request->hasFile('Proof_filename')) {
                if ($request->file('Proof_filename')->isValid()) {
                    $filename = $request->file('Proof_filename')->store('profile_images', 'public');
                    DB::table('proof_of_payments')
                        ->where('Proof_ID', $proofPayment->Proof_ID)
                        ->update(['Proof_filename' => $filename]);
                    
                    return response()->json([
                        'message' => 'Profile image updated successfully',
                        'image_url' => asset('storage/' . $filename)
                    ], 200);
                } else {
                    return response()->json(['message' => 'Uploaded file is not valid.'], 400);
                }
            }

            return response()->json(['message' => 'No image file uploaded'], 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Transaction, payment, or proof of payment not found for the given tracking number.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the profile image.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateCus(Request $request)
    {
        // Validate the request
        $request->validate([
            'cid' => 'required|integer|exists:customers,Cust_ID',
            'fname' => 'required|string|max:20',
            'lname' => 'required|string|max:20',
            'mname' => 'required|string|max:20',
            'phonenum' => 'required|string',
            'address' => 'required|string|max:50',
            'email' => 'required|email|max:50',
            'cust_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048' // Adjust as needed
        ]);

        // Find the customer
        $customer = Customers::find($request->cid);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        // Update customer information
        $customer->Cust_fname = $request->fname;
        $customer->Cust_lname = $request->lname;
        $customer->Cust_mname = $request->mname;
        $customer->Cust_phoneno = $request->phonenum;
        $customer->Cust_address = $request->address;
        $customer->Cust_email = $request->email;

        // Handle the image upload if provided
        if ($request->hasFile('cust_image')) {
            // Delete the existing image if it exists
            if ($customer->Cust_image) {
                // Delete from storage
                Storage::delete('public/profile/' . $customer->Cust_image);
                
                // If you are managing files directly, ensure to unlink as well (optional)
                $htdocsImagePath = 'C:/xampp/htdocs/customer/profile/' . $customer->Cust_image;
                if (file_exists($htdocsImagePath)) {
                    unlink($htdocsImagePath);
                }
            }

            // Store the new image
            $imageName = time() . '_' . $customer->Cust_ID . '.' . $request->cust_image->extension();
            
            // Define storage path
            $htdocsPath = 'C:/xampp/htdocs/customer/profile'; 

            // Ensure the directory exists
            if (!file_exists($htdocsPath)) {
                mkdir($htdocsPath, 0777, true);
            }

            // Move the uploaded file to the specified directory
            $request->cust_image->move($htdocsPath, $imageName);

            // Update customer image path
            $customer->Cust_image = $imageName;
        }

        // Save the updated customer
        $customer->save();

        return response()->json(['message' => 'Customer updated successfully', 'customer' => $customer], 200);
    }
    public function getcustomer($id){
        $temp = DB::table('customers')
                ->where('Cust_ID',$id)
                ->get();

        $temp2 = DB::table('customers')
                ->where('Cust_ID',$id)
                ->first();
        
        return [
            'customerData' => $temp,
            'customerFirst' => $temp2
        ];
    } 
}
