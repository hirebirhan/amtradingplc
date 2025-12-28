// Proforma routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    Route::prefix('proformas')->name('proformas.')->group(function () {
        Route::get('/', \App\Livewire\Proformas\Index::class)->name('index');
        Route::get('/create', \App\Livewire\Proformas\Create::class)->name('create');
        Route::get('/{proforma}', \App\Livewire\Proformas\Show::class)->name('show');
        Route::get('/{proforma}/edit', \App\Livewire\Proformas\Edit::class)->name('edit');
        Route::get('/{proforma}/print', [App\Http\Controllers\ProformaController::class, 'print'])->name('print');
        Route::get('/{proforma}/pdf', [App\Http\Controllers\ProformaController::class, 'pdf'])->name('pdf');
    });
});