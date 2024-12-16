<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\StaffController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::get('/', function () {
    return "API";
});

// Route::middleware(['admin'])->group(function () {
//     Route::get('/displaystaff', [AdminController::class, 'displaystaff']);
//     Route::get('/findstaff/{id}', [AdminController::class, 'findstaff']);
//     Route::post('/addstaff', [AdminController::class, 'addstaff']);
//     Route::put('/updatestaff/{id}', [AdminController::class, 'updatestaff']);
//     Route::delete('/deletestaff/{id}', [AdminController::class, 'deletestaff']);
//     Route::post('/update-profile-image/{id}', [AdminController::class, 'updateProfileImage']);
//     // Add other admin routes here
// });

//admin - loginadmin
Route::post('/login',[AdminController::class, 'login']);
Route::post('/logout', [AdminController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/addAdmin',[AdminController::class, 'addAdmin']);


// admin - staff
Route::get('/displaystaff',[AdminController::class, 'displaystaff']);
Route::get('/findstaff/{id}',[AdminController::class, 'findstaff']);
Route::post('/addstaff',[AdminController::class, 'addstaff']);
Route::put('/updatestaff/{id}',[AdminController::class, 'updatestaff']);
Route::delete('/deletestaff/{id}',[AdminController::class, 'deletestaff']);
Route::post('/update-profile-image/{id}', [AdminController::class, 'updateProfileImage']);

// admin - pricemanagement
Route::get('/pricedisplay',[AdminController::class, 'pricedisplay']);
Route::get('/pricedisplayDestination',[AdminController::class, 'pricedisplayDestination']);
Route::post('/addprice',[AdminController::class, 'addprice']);
Route::delete('/deletecateg/{id}',[AdminController::class, 'deletecateg']);
Route::get('/findprice/{id}',[AdminController::class, 'findprice']);
Route::put('/updateprice/{id}',[AdminController::class, 'updateprice']);
Route::post('/addpricedestination',[AdminController::class, 'addpricedestination']);
Route::delete('/deletedestination/{id}',[AdminController::class, 'deletedestination']);
Route::get('/findpricedestination/{id}',[AdminController::class, 'findpricedestination']);
Route::put('/updatepricedestination/{id}',[AdminController::class, 'updatepricedestination']);

// admin - dashboard
Route::get('/dashdisplays',[AdminController::class, 'dashdisplays']);
Route::get('/dashdisplaysgraph',[AdminController::class, 'dashdisplaysgraph']);
Route::get('/expensendisplays',[AdminController::class, 'expensendisplays']);
Route::get('/displaystaffs',[AdminController::class, 'displaystaffs']);
Route::post('/cashinitial',[AdminController::class, 'cashinitial']);
Route::post('/remittance',[AdminController::class, 'remittance']);
Route::get('/veiwdeatils',[AdminController::class, 'veiwdeatils']);
Route::get('/CountDisplay',[AdminController::class, 'CountDisplay']);
Route::get('/dashdisplaysmonth',[AdminController::class, 'dashdisplaysmonth']);
Route::get('/dashdisplaysmonths/{year}/{month}',[AdminController::class, 'dashdisplaysmonths']);

// admin - customer
Route::get('/customerdisplay',[AdminController::class, 'customerdisplay']);
Route::get('/findcustomer/{id}',[AdminController::class, 'findcustomer']);
Route::get('/findtrans/{id}',[AdminController::class, 'findtrans']);
Route::get('/printtrans/{id}',[AdminController::class, 'printtrans']);
Route::put('/updateprofilecus/{id}',[AdminController::class, 'updateprofilecus']);
Route::delete('/deletecustomer/{id}',[AdminController::class, 'deletecustomer']);
Route::post('/updateprofile/{id}', [AdminController::class, 'updateprofile']);

// admin - transactions
Route::get('/Transadisplay',[AdminController::class, 'Transadisplay']);
// Route::get('/printTransac',[AdminController::class, 'printTransac']);
Route::get('/printTransac/{id}',[AdminController::class, 'printTransac']);
Route::get('/approveremit/{id}',[AdminController::class, 'approveremit']);
Route::get('/DisplayAllTransaction/{id}',[AdminController::class, 'DisplayAllTransaction']);
Route::get('/DisplayAllExpenses/{id}',[AdminController::class, 'DisplayAllExpenses']);
Route::get('/TransactiondisplayAll',[AdminController::class, 'TransactiondisplayAll']);
Route::get('/TransactiondisplayAlls',[AdminController::class, 'TransactiondisplayAlls']);
Route::get('/collectable',[AdminController::class, 'collectable']);


// admin- report
Route::get('/displayexpenses',[AdminController::class, 'displayexpenses']);
Route::get('/displayincome',[AdminController::class, 'displayincome']);
Route::get('/remittanceapproved',[AdminController::class, 'remittanceapproved']);
Route::get('/displayDisrepancy',[AdminController::class, 'displayDisrepancy']);
Route::get('/hisdisplayincome/{id}',[AdminController::class, 'hisdisplayincome']);
Route::get('/hisdisplayexpenses/{id}',[AdminController::class, 'hisdisplayexpenses']);
Route::get('/hisdisplaydiscripancy/{id}',[AdminController::class, 'hisdisplaydiscripancy']);

// admin- account 
Route::get('/admin/{id}',[AdminController::class, 'findstaff']);
Route::put('/updateaccount/{id}',[AdminController::class, 'updateaccount']);



//customer - login
Route::post('logins', [CustomerController::class,'login']);
Route::post('logouts', [CustomerController::class,'logout'])->middleware('auth:sanctum');
Route::post('/signup',[CustomerController::class,'signup']);

//customer - home
Route::post('/addtrans',[CustomerController::class,'addtrans']);
Route::post('/insertDetails',[CustomerController::class,'insertDetails']);
Route::post('/updateTransactionStatus', [CustomerController::class, 'updateStatus']);
Route::post('/transactions', [CustomerController::class, 'store']);
Route::post('/updatetrans', [CustomerController::class,'updatetrans']);
Route::get('/getlist',[CustomerController::class,'getlist']);
Route::get('/display/{id}',[CustomerController::class,'display']);
Route::get('/gethis/{id}',[CustomerController::class,'gethis']);
Route::get('/cancelTrans/{id}',[CustomerController::class,'cancelTrans']);
Route::get('/displayDet/{id}',[CustomerController::class,'displayDet']);
Route::delete('/deleteDetails', [CustomerController::class, 'deleteDetails']);



//customer - transactions
Route::get('/getTransId/{id}',[CustomerController::class,'getTransId']);
Route::get('getDetails/{id}',[CustomerController::class,'getDetails']);


//customer - account
Route::post('/updateCus', [CustomerController::class, 'updateCus']);
Route::get('/getcustomer/{id}',[CustomerController::class,'getcustomer']);
Route::post('upload/{trackingNumber}', [CustomerController::class, 'updateProfileImage']);


//customer - signup
Route::post('addcustomer', [CustomerController::class,'addcustomer']);




//staff - login
// Route::post('CustRegister', [StaffController::class, 'CustRegister']);
Route::get('user', function (Request $request) {
    return response()->json($request->user());
});
Route::post('/CustLogin', [StaffController::class, 'CustLogin']);
Route::post('AdminLogin', [StaffController::class, 'AdminLogin']);

Route::post('Logout', [StaffController::class, 'Logout'])->middleware('auth:sanctum');

Route::get('Transaction', [StaffController::class, 'getTransaction']); // Home - Receivings
Route::get('TransactionRel', [StaffController::class, 'getTrasactionsRel']); //Home - Receivings

Route::get('getTransCust/{id}', [StaffController::class, 'showTransCust']);
Route::post('updateStatus/{id}', [StaffController::class, 'updateStatus']); //Home - Receivings & Releasings - modal
Route::get('getLaundryDetails/{id}', [StaffController::class, 'showLaundryDetails']);
Route::get('getTransactionsRec', [StaffController::class, 'getTransactionsRec']);

Route::get('totalPrice/{id}', [StaffController::class, 'totalPrice']);
Route::get('getAddService/{id}', [StaffController::class, 'getAddService']);
Route::post('saveLaundryDetails', [StaffController::class, 'saveLaundryDetails']);
Route::post('saveServiceData', [StaffController::class, 'saveServiceData']);
Route::get('getCash/{id}', [StaffController::class, 'getCash']);
Route::post('addRem', [StaffController::class, 'addRem']);
Route::post('submitLaundryTrans/{id}', [StaffController::class, 'submitLaundryTrans']);
Route::post('updateStatus/{id}', [StaffController::class, 'updateStatus']);
Route::get('getForRel/{id}', [StaffController::class, 'getForRel']);
Route::get('getAddServRel/{id}', [StaffController::class, 'getAddServRel']);
Route::get('totalPriceLaundry/{id}', [StaffController::class, 'totalPriceLaundry']);
Route::get('totalPriceService/{id}', [StaffController::class, 'totalPriceService']);
Route::get('paymentStatus/{id}', [StaffController::class, 'paymentStatus']);
Route::post('doneTransac/{id}', [StaffController::class, 'doneTransac']);

Route::get('getCustomer', [StaffController::class, 'getCustomer']);
Route::get('getCustomerData/{id}', [StaffController::class, 'getCustomerData']);
Route::get('getCustTransacHistory/{id}', [StaffController::class, 'getCustTransacHistory']);















