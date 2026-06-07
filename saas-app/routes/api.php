<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StlController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NetvisorController;
use App\Http\Controllers\AtlassianSalesController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\TimesheetRowController;
use App\Http\Controllers\PdfDocumentBankController;
use App\Http\Controllers\WordBankController;
use App\Http\Controllers\ExcelBankController;
use App\Http\Controllers\IssueTrackerController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\PurchaseOrdersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('users')->group(function () {
	Route::post('/register', [AuthController::class, 'register'])->name('register.auth');
	Route::post('/login', [AuthController::class, 'login'])->name('login.auth');
	Route::get('/userdata', [AuthController::class, 'userData'])->name('userdata.auth');
	Route::get('/checkifemailverified/{token}', [AuthController::class, 'checkIfEmailVerified'])->name('user.verify.api');
	Route::get('/logout', [AuthController::class, 'logout'])->name('logout.auth');
	Route::get('/me', [AuthController::class, 'me'])->name('me.auth');
	Route::post('/forget-password', [AuthController::class, 'submitForgetPasswordForm'])->name('forget.password.post');
	Route::post('/reset-password', [AuthController::class, 'submitResetPasswordForm'])->name('reset.password.post');
});

Route::get('/settings', [SettingsController::class, 'settings'])->name('settings.get');

Route::prefix('manage')->group(function () {
	Route::post('/users/change-status', [ProfileController::class, 'usersChangeStatus'])->name('manage.users-change-status');
	Route::post('/users/verify', [ProfileController::class, 'usersVerify'])->name('manage.users-verify');
	Route::post('/users/change-password', [ProfileController::class, 'usersChangePassword'])->name('manage.users-change-password');
	Route::post('/users/add-user', [ProfileController::class, 'usersAdd'])->name('manage.users-add');
	Route::get('/users', [ProfileController::class, 'users'])->name('manage.users');

	Route::get('/domains', [ProfileController::class, 'domains'])->name('manage.domains');
	Route::get('/domain-edit/{id}', [ProfileController::class, 'domainEdit'])->name('manage.domain-edit');
	Route::post('/updateDomainRecord', [ProfileController::class, 'updateDomainRecord'])->name('manage.updateDomainRecord');
	Route::put('/domains/{id}', [ProfileController::class, 'updateDomain'])->name('manage.updateDomain');
	Route::delete('/domains/{id}', [ProfileController::class, 'removeDomain'])->name('manage.removeDomain');
	Route::get('/settings', [ProfileController::class, 'settings'])->name('manage.settings');
	Route::post('/updateSettings', [ProfileController::class, 'updateSettings'])->name('manage.updateSettings');
	Route::post('/upload-invoice-template', [ProfileController::class, 'uploadTemplate'])->name('manage.upload-invoice-template');
	Route::get('/template-exists', [ProfileController::class, 'templateExists'])->name('manage.templateExists');

	Route::get('/permissions', [ProfileController::class, 'permissions'])->name('manage.permissions');
	Route::get('/role/{id}', [ProfileController::class, 'roleDelete'])->name('manage.removeRole');
	Route::get('/roles/all', [ProfileController::class, 'rolesAll'])->name('manage.rolesAll');
	Route::get('/roles/foradd', [ProfileController::class, 'rolesAllforAddUser'])->name('manage.rolesAdd');
	Route::get('/roles', [ProfileController::class, 'roles'])->name('manage.roles');
	Route::post('/roles', [ProfileController::class, 'roleAdd'])->name('manage.addRoles');
	Route::post('/roles/setRole', [ProfileController::class, 'setRole'])->name('manage.setRole');

	Route::get('/myprofile', [ProfileController::class, 'myprofile'])->name('manage.myprofile');
	Route::post('/myprofile', [ProfileController::class, 'myprofileSave'])->name('manage.myprofileSave');
	Route::post('/capture-upload', [ProfileController::class, 'captureUpload'])->name('manage.captureUpload');
	Route::post('/update-invoice-start-number', [ProfileController::class, 'updateInvoiceStartNumber'])->name('manage.updateInvoiceStartNumber');
	Route::get('/get-invoice-start-number', [ProfileController::class, 'getInvoiceStartNumber'])->name('manage.getInvoiceStartNumber');

	Route::get('/bank-accounts', [ProfileController::class, 'getBankAccounts'])->name('manage.getBankAccounts');
    Route::post('/bank-accounts', [ProfileController::class, 'updateBankAccounts'])->name('manage.updateBankAccounts');
	Route::group(['prefix' => 'i18n'], function ($router) {
		Route::get('/payment-terms', [ProfileController::class, 'invoicePaymentTerms'])->name('manage.i18n.invoicePaymentTerms');
		Route::post('/payment-terms', [ProfileController::class, 'addInvoicePaymentTerms'])->name('manage.i18n.addInvoicePaymentTerms');
		Route::put('/payment-terms/{id}', [ProfileController::class, 'updateInvoicePaymentTerms'])->name('manage.i18n.updateInvoicePaymentTerms');
		Route::delete('/payment-terms', [ProfileController::class, 'deleteInvoicePaymentTerms'])->name('manage.i18n.deleteInvoicePaymentTerms');
	});
});

Route::prefix('stl')->group(function () {
	Route::post('/upload-stl', [StlController::class, 'uploadStlFile'])->name('stl.upload-stl');
	Route::get('/stl-items', [StlController::class, 'getStlItems'])->name('stl.stl-items');
	Route::get('/stl-item', [StlController::class, 'getStlItem'])->name('stl.stl-item');
	Route::post('/stl-file', [StlController::class, 'getStlFile'])->name('stl.stl-file');
	Route::delete('/delete-stl', [StlController::class, 'postStlDeleteFile'])->name('stl.delete-stl');
	Route::post('/generate-spaceship', [StlController::class, 'generateSpaceship']);
	Route::post('/generate-cyborg', [StlController::class, 'generateCyborg']);
	Route::post('/generate-car', [StlController::class, 'generateCar']);
});

Route::prefix('gallery')->group(function () {
	Route::get('/assets', [GalleryController::class, 'assets'])->name('assets.asset-items');
	Route::post('/upload-media', [GalleryController::class, 'uploadMedia'])->name('gallery.upload-media');
	Route::delete('/photos_videos/delete', [GalleryController::class, 'deleteMedia'])->name('gallery.delete-media');
});

Route::prefix('chat')->group(function () {
	Route::post('/messages', [ChatController::class, 'message']);
	Route::get('/messages', [ChatController::class, 'getMessages']);
	Route::post('/typing', [ChatController::class, 'userTyping']);
	Route::post('/thinking', [ChatController::class, 'thinking']);
	Route::post('/upload', [ChatController::class, 'uploadMessage']);
	Route::post('/capture-upload', [ChatController::class, 'captureUpload']);
	Route::post('/upload-video', [ChatController::class, 'uploadVideo']);
	Route::post('/generate-response', [ChatController::class, 'generateResponse']);
	Route::post('/save-message', [ChatController::class, 'saveMessageToDatabase']);
	Route::post('/tts', [ChatController::class, 'synthesize']);
	Route::post('/stt', [ChatController::class, 'transcribe']);
	Route::post('/generate-image', [ChatController::class, 'generateImage']);
	Route::post('/speech', [ChatController::class, 'speech']);
	Route::post('/word/send', [ChatController::class, 'generateWordFile'])->name('chatgpt.generate.word');
	Route::post('/excel/send', [ChatController::class, 'generateExcelFile'])->name('chatgpt.generate.excel');
	Route::post('/pdf/send', [ChatController::class, 'generatePdfFile'])->name('chatgpt.generate.pdf');
	Route::post('/analyze-pdf', [ChatController::class, 'uploadPDF'])->name('pdf.upload');
	Route::get('/openai-session', [ChatController::class, 'openAiSession']);
});

Route::prefix('netvisor')->group(function () {
	Route::get('/invoices', [NetvisorController::class, 'getSalesInvoices']);
});

// Route for Atlassian sales report
Route::prefix('reports')->group(function () {
	Route::get('/sales-report', [AtlassianSalesController::class, 'getSalesReport']);
	Route::get('/cumulative-sales', [AtlassianSalesController::class, 'getCumulativeSales']);
	Route::get('/transactions', [AtlassianSalesController::class, 'getTransactions']);
	Route::get('/combined-sales', [AtlassianSalesController::class, 'getCombinedSales']);
	Route::get('/merged-sales', [AtlassianSalesController::class, 'getMergedSales']);
	Route::get('/customer', [AtlassianSalesController::class, 'getAllCustomer']);
	Route::post('/customer', [AtlassianSalesController::class, 'addCustomer']);
	Route::post('/transaction', [AtlassianSalesController::class, 'addTransaction']);
	Route::get('/merged-monthly-sums', [AtlassianSalesController::class, 'getIncomeByMonthAllYears']);
	Route::get('/income-years', [AtlassianSalesController::class, 'getIncomeYears']);

});

Route::prefix('timesheet')->group(function () {
	Route::get('timesheets', [TimesheetController::class, 'index']);
	Route::post('timesheets', [TimesheetController::class, 'store']);
	Route::get('timesheets/{timesheet}/rows', [TimesheetRowController::class, 'index']);
	Route::post('timesheets/{timesheet}/rows', [TimesheetRowController::class, 'store']);
	Route::get('timesheets/{timesheet}/rows/{row}', [TimesheetRowController::class, 'show']);
	Route::put('timesheets/{timesheet}', [TimesheetController::class, 'update']);
	Route::put('timesheets/{timesheet}/rows/{row}', [TimesheetRowController::class, 'update']);
	Route::delete('timesheets/{timesheet}/rows/{row}', [TimesheetRowController::class, 'destroy']);
});

Route::prefix('documentbank')->group(function () {
	Route::get('/', [PdfDocumentBankController::class, 'index']);
	Route::post('/upload', [PdfDocumentBankController::class, 'upload']);
	Route::get('/view/{id}', [PdfDocumentBankController::class, 'view']);
	Route::get('/download/{id}', [PdfDocumentBankController::class, 'download']);
	Route::delete('/{id}', [PdfDocumentBankController::class, 'destroy']);
});

Route::prefix('wordbank')->group(function () {
	Route::get('/', [WordBankController::class, 'index']);
	Route::post('/upload', [WordBankController::class, 'upload']);
	Route::get('/view/{id}', [WordBankController::class, 'view']);
	Route::get('/download/{id}', [WordBankController::class, 'download']);
	Route::delete('/{id}', [WordBankController::class, 'destroy']);
});

Route::prefix('excelbank')->group(function () {
	Route::get('/', [ExcelBankController::class, 'index']);
	Route::post('/upload', [ExcelBankController::class, 'upload']);
	Route::get('/view/{id}', [ExcelBankController::class, 'view']);
	Route::get('/download/{id}', [ExcelBankController::class, 'download']);
	Route::delete('/{id}', [ExcelBankController::class, 'destroy']);
});

Route::prefix('issue-tracker')->group(function () {
	Route::get('/users', [IssueTrackerController::class, 'users']);
	Route::get('/', [IssueTrackerController::class, 'index']);
	Route::post('/', [IssueTrackerController::class, 'store']);
	Route::put('/{id}/status', [IssueTrackerController::class, 'updateStatus']);
    Route::put('/{id}/assign', [IssueTrackerController::class, 'assign']);
	Route::patch('/{id}', [IssueTrackerController::class, 'update']);
});

Route::prefix('cv')->group(function () {
    Route::get('/',    [CvController::class, 'show']);
    Route::post('/',   [CvController::class, 'store']);
    Route::delete('/', [CvController::class, 'destroy']);
});

Route::group(['prefix' => 'purchase-orders'], function ($router) {

	Route::get('/index', [PurchaseOrdersController::class, 'index'])->name('purchase-orders.index');
	Route::post('/store', [PurchaseOrdersController::class, 'store'])->name('purchase-orders.store');
	Route::get('/show/{id}', [PurchaseOrdersController::class, 'show'])->name('purchase-orders.show');
	Route::put('/update/{id}', [PurchaseOrdersController::class, 'update'])->name('purchase-orders.update');
	Route::delete('/destroy/{id}', [PurchaseOrdersController::class, 'destroy'])->name('purchase-orders.destroy');
});

Route::group(['prefix' => 'customers'], function ($router) {

	Route::get('/index', [CustomersController::class, 'index'])->name('customers.index');
	Route::post('/store', [CustomersController::class, 'store'])->name('customers.store');
	Route::get('/show/{id}', [CustomersController::class, 'show'])->name('customers.show');
	Route::put('/update/{id}', [CustomersController::class, 'update'])->name('customers.update');
	Route::delete('/destroy/{id}', [CustomersController::class, 'destroy'])->name('customers.destroy');
	Route::get('/search', [CustomersController::class, 'search'])->name('customers.search');

});

Route::group(['prefix' => 'invoices'], function ($router) {

	Route::get('/index', [InvoicesController::class, 'index'])->name('invoices.index');
	Route::get('/next-invoice-number', [InvoicesController::class, 'nextInvoiceNumber'])->name('invoices.next-invoice-number');
	Route::post('/store', [InvoicesController::class, 'store'])->name('invoices.store');
	Route::get('/show/{id}', [InvoicesController::class, 'show'])->name('invoices.show');
	Route::put('/update/{id}', [InvoicesController::class, 'update'])->name('invoices.update');
	Route::delete('/destroy/{id}', [InvoicesController::class, 'destroy'])->name('invoices.destroy');
	Route::get('/search', [InvoicesController::class, 'search'])->name('invoices.search');
	Route::post('/{id}/lines/add', [InvoicesController::class, 'addLine'])->name('invoices.lines.add');
	Route::get('/{id}/lines', [InvoicesController::class, 'getLines'])->name('invoices.lines.get');
	Route::put('/{id}/lines/update', [InvoicesController::class, 'updateLines'])->name('invoices.lines.update');
	Route::delete('/{id}/line/delete', [InvoicesController::class, 'deleteLine'])->name('invoices.lines.delete');
	Route::get('/{id}/download', [InvoicesController::class, 'downloadInvoice'])->name('invoices.download');

});