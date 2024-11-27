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
use App\Models\TransactionStatus;

class CustomerController extends Controller
{

    //login
    public function login(Request $request)
    {

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
                    $trackingNumber = DB::table('transactions')
                        ->where('Transac_ID', $data['TransacDet_ID'])
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
                        'Transac_ID' => $data['Transac_ID'],
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
            ->join('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->select(
                DB::raw('GROUP_CONCAT(DISTINCT  laundry_categories.Category SEPARATOR ", ") as Category'),
                DB::raw('MAX(DISTINCT  transaction_status.Transac_status) as trans_stat'), // Get only the first status
                'customers.Cust_fname as fname',
                'customers.Cust_lname as lname',
                DB::raw('SUM(DISTINCT transaction_details.Qty) as totalQty'),
                DB::raw('SUM(DISTINCT transaction_details.Weight) as totalWeight'),
                DB::raw('SUM(DISTINCT transaction_details.Price) as totalprice'),
                'transactions.Tracking_number as track_num',
                'transactions.Transac_date as trans_date',
                'transactions.Transac_ID as trans_ID'
            )
            ->where('transactions.Cust_ID', $id)
            ->groupBy(
                'customers.Cust_fname',
                'customers.Cust_lname',
                'transactions.Tracking_number',
                'transactions.Transac_date',
                'transactions.Transac_ID'
            )
            ->get();

        return response()->json(['transaction' => $transactions]);
    }

    public function addtrans(Request $request)
    {
        $request->validate([
            'Cust_ID' => 'required|integer|exists:customers,Cust_ID',
            'Tracking_number' => 'required|string|max:255|unique:transactions,Tracking_number',
            'laundry' => 'required|array|min:1',
            'laundry.*.Categ_ID' => 'required|integer',
            'laundry.*.Qty' => 'required|integer',
            'Transac_status' => 'required|string',
        ]);


        // Insert into transactions table and get Transac_ID
        $transacId = DB::table('transactions')->insertGetId([
            'Cust_ID' => $request->Cust_ID,
            'Tracking_number' => $request->Tracking_number,
            // 'Transac_status' => $request->Transac_status,
            'Transac_date' => now(),
        ]);

        $transacStatusId = DB::table('transaction_status')->insertGetId([
            'Transac_status' => $request->Transac_status, 
            'TransacStatus_datetime' => now(),
            'Transac_ID' =>  $transacId,  // Specify the value for Transac_ID
        ]);

        $transactionDetails = [];
            foreach ($request->laundry as $laundryItem) {
                $transactionDetails[] = [
                    'Transac_ID' => $transacId,
                    'Categ_ID' => $laundryItem['Categ_ID'],
                    'Qty' => $laundryItem['Qty'],
                ];
            }
            DB::table('transaction_details')->insert($transactionDetails);

        return response()->json(['Transaction' => $transacId,'Transaction_details' => $transactionDetails], 200);
   
    }


    public function displayDet($id)
    {
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
            '*.Categ_ID' => 'required|integer',
            '*.Qty' => 'required|integer',
            '*.Tracking_Number' => 'required|string', // Ensure this matches your payload key
        ], [
            '*.Tracking_Number.required' => 'Each detail must have a Tracking_Number.',
            '*.Tracking_Number.string' => 'Tracking_Number must be a string.',
        ]);

        try {
            foreach ($validatedData as $detail) {
                $transaction = DB::table('transactions')
                    ->where('Tracking_Number', $detail['Tracking_Number'])
                    ->first();

                if (!$transaction) {
                    return response()->json(['error' => 'Transaction with Tracking_Number ' . $detail['Tracking_Number'] . ' not found.'], 404);
                }

                DB::table('transaction_details')->insert([
                    'Categ_ID' => $detail['Categ_ID'],
                    'Qty' => $detail['Qty'],
                    'Transac_ID' => $transaction->Transac_ID, // Reference to related transaction
                ]);
            }

            return response()->json(['message' => 'New transaction details added successfully']);
        } catch (\Exception $e) {
            Log::error('Insert Details Error:', ['exception' => $e]);
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

    public function cancelTrans(Request $request, $id)
    {
        $transactions = DB::table('transactions')
            ->join('customers', 'transactions.Cust_ID', '=', 'customers.Cust_ID')
            ->join('transaction_details', 'transactions.Transac_ID', '=', 'transaction_details.Transac_ID')
            ->join('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
            ->join('transaction_status', 'transactions.Transac_ID', '=', 'transaction_status.Transac_ID')
            ->select(
                'transactions.Tracking_number',
                'transactions.Transac_date',
                'customers.Cust_fname', 
                'customers.Cust_lname', 
                'transaction_status.Transac_status',
                DB::raw('GROUP_CONCAT(laundry_categories.Category SEPARATOR ", ") as Category'),
                DB::raw('SUM(transaction_details.Price) as totalprice'),
                DB::raw('SUM(transaction_details.Qty) as totalQty'),
                DB::raw('SUM(transaction_details.Weight) as totalWeight')
            )
            ->groupBy(
                'transactions.Tracking_number',
                'transactions.Transac_date',
                'customers.Cust_fname', 
                'customers.Cust_lname', 
                'transaction_status.Transac_status',
            )
            ->get();

            TransactionStatus::where('Transac_ID', $id)
                ->update(['Transac_status' => 'cancel']);

        return response()->json(['transaction' => $transactions], 200);
    }

    private function insertPayment($trackingNumber, $modeOfPayment, $amount, $custId)
    {
        return DB::table('payments')->insert([
            'Cust_ID' => $request->Cust_ID,
            'Transac_ID' => $request->trackingNumber,
            'Amount' => $request->Amount,
            'Mode_of_Payment' => $request->Mode_of_Payment,
            'Datetime_of_Payment' => now(),
        ]);
        
    }


    public function insertProofOfPayment(Request $request, $paymentId)
    {
        try {
            // Validate the incoming request to ensure the file is included
            $validated = $request->validate([
                'Proof_filename' => 'required|file|image|mimes:jpeg,png,jpg|max:4096',  // Validate file type and size
                'Cust_ID' => 'required|string',
                'Mode_of_Payment' => 'required|string',
                'Amount' => 'required|numeric|min:1',
            ]);
    
            // Check if the file is present and is valid
            if ($request->hasFile('Proof_filename') && $request->file('Proof_filename')->isValid()) {
                $file = $request->file('Proof_filename');
                
                // Generate a unique filename based on the current timestamp and original name
                $filename = time() . '_' . $file->getClientOriginalName();
    
                // Store the file in the 'public/receipt' directory
                $file->storeAs('public/receipt', $filename);
    
                // Log the filename to ensure it's being uploaded
                \Log::info('Uploaded file: ' . $filename);
            } else {
                throw new \Exception('File upload failed or no file uploaded');
            }
    
            // Insert the record into the database with the filename
            $proofOfPaymentId = DB::table('proof_of_payments')->insertGetId([
                'Payment_ID' => $paymentId,
                'Proof_filename' => $filename,  // Store the filename in the database
                'Upload_datetime' => now(),
            ]);
    
            return response()->json(['message' => 'Payment uploaded successfully', 'id' => $proofOfPaymentId], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error inserting proof of payment: ' . $e->getMessage());
            
            return response()->json(['error' => 'Failed to insert proof of payment: ' . $e->getMessage()], 500);
        }
    }


    private function handleImageUpload(Request $request, $trackingNumber)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'Proof_filename' => 'required|file|image|mimes:jpeg,png,jpg|max:4096', // Allow up to 4 MB
            'Cust_ID' => 'required|string',
            'Mode_of_Payment' => 'required|string',
            'Amount' => 'required|numeric|min:1',
        ]);

        try {
            // Handle the file upload
            $file = $request->file('Proof_filename');
            if (!$file) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/receipt', $filename);

            // Insert the data into the database
            DB::table('proof_of_payments')->insert([
                'Tracking_Number' => $trackingNumber,
                'Cust_ID' => $validated['Cust_ID'],
                'Proof_filename' => $filename,
                'Mode_of_Payment' => $validated['Mode_of_Payment'],
                'Amount' => $validated['Amount'],
                'Upload_datetime' => now(),
            ]);

            return response()->json(['message' => 'Payment uploaded successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Payment upload error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload payment: ' . $e->getMessage()], 500);
        }
    }
    //transactions
    public function getTransId($id)
    {
        $temp = DB::table('transaction_details')
                ->where('TransacDet_ID',$id)
                ->get();
                
        return $temp;
    }
    public function getDetails($id)
    {
        $temp = DB::table('transactions')
            ->where('Transac_ID', $id)
            ->get();

        $mainTransactionStatus = DB::table('transaction_status')
            ->where('Transac_ID', $id)
            ->orderBy('TransacStatus_datetime', 'asc')
            ->get();
    
        $transactions = [];
    
        foreach($temp as $t){
            $transaction_details = DB::table('transaction_details')
                ->LeftJoin('laundry_categories', 'transaction_details.Categ_ID', '=', 'laundry_categories.Categ_ID')
                // ->LeftJoin('transaction_status', 'transaction_details.Transac_ID', '=', 'transaction_status.Transac_ID')
                ->select(
                    'transaction_details.Transac_ID',
                    'transaction_details.TransacDet_ID',
                    'transaction_details.Price as price',
                    'transaction_details.created_at',
                    'laundry_categories.Category',
                    // 'transaction_status.Transac_status'
                )
                ->where('Transac_ID', $t->Transac_ID)
                ->get();
    
            $transactions[] = [
                'Tracking_number' => $t->Tracking_number,
                'Transac_status' =>  $mainTransactionStatus,
                // 'Transac_status' =>  $mainTransactionStatus->Transac_status,
                // 'Transac_date' =>  $mainTransactionStatus->TransacStatus_datetime,
                'total' => $transaction_details->sum('price'),
                'details' => $transaction_details,
                'transac' => $temp
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
            'Cust_ID' => 'nullable|integer|exists:customers,Cust_ID',
            'Cust_fname' => 'nullable|string|max:20',
            'Cust_lname' => 'nullable|string|max:20',
            'Cust_mname' => 'nullable|string|max:20',
            'Cust_phoneno' => 'nullable|string',
            'Cust_address' => 'nullable|string|max:50',
            'Cust_email' => 'nullable|email|max:50',
            'Cust_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Find the customer
        $customer = Customers::findOrFail($request->Cust_ID);

        // Update customer information
        $customer->Cust_fname = $request->Cust_fname;
        $customer->Cust_lname = $request->Cust_lname;
        $customer->Cust_mname = $request->Cust_mname;
        $customer->Cust_phoneno = $request->Cust_phoneno;
        $customer->Cust_address = $request->Cust_address;
        $customer->Cust_email = $request->Cust_email;

        // Handle image update if provided
        if ($request->hasFile('Cust_image')) {
            // Delete the old image if it exists
            if ($customer->Cust_image) {
                $oldImagePath = public_path('images/' . $customer->Cust_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Save the new image
            $extension = $request->file('Cust_image')->extension();
            $imageName = time() . '_' . $customer->Cust_ID . '.' . $extension;

            $destinationPath = public_path('images');
            $request->file('Cust_image')->move($destinationPath, $imageName);

            $customer->Cust_image = $imageName;
        }

        // Save the updated customer
        $customer->save();

        // Generate the public URL for the new image if updated
        $imageUrl = $customer->Cust_image ? asset('images/' . $customer->Cust_image) : null;

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer,
            'image_url' => $imageUrl
        ], 200);
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


    // signup
    public function addcustomer(Request $request)
    {
        $request->validate([
            'Cust_lname' => 'required|string|max:255',
            'Cust_fname' => 'required|string|max:255',
            'Cust_mname' => 'nullable|string|max:255',
            'Cust_phoneno' => 'required|string|max:12',
            'Cust_address' => 'required|string|max:255',
            'Cust_email' => 'required|email|max:255|unique:customers',
            'Cust_password' => 'required|confirmed|min:6',
        ]);
        
        $data = $request->all();
        $data['Cust_password'] = bcrypt($request->Cust_password);
        
        $customer = Customers::create($data);
        
        return response()->json([
            'message' => 'Customer added successfully',
            'customer' => $customer
        ], 201);        
    }
}