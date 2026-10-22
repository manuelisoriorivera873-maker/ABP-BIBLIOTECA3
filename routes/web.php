<?php

use App\Http\Controllers\AccesoController;
use App\Http\Controllers\AutorController;
use App\Http\Controllers\BibliotecarioController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CuentaAccesoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevolucionController;
use App\Http\Controllers\EjemplarController;
use App\Http\Controllers\LibroController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\RegistroPublicoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| INICIO DE SESION
|--------------------------------------------------------------------------
*/

Route::get('/login', [
    AccesoController::class,
    'formulario'
])->name('login');

Route::post('/login', [
    AccesoController::class,
    'iniciar'
])->middleware('throttle:5,1')->name('login.iniciar');

Route::post('/logout', [
    AccesoController::class,
    'salir'
])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| REGISTRO PUBLICO DE LECTORES
|--------------------------------------------------------------------------
| Estas rutas deben estar fuera del grupo auth.
| Un visitante puede registrar su propia cuenta.
*/

Route::get('/registro', [
    RegistroPublicoController::class,
    'formulario'
])->middleware('guest')->name('registro');

Route::post('/registro', [
    RegistroPublicoController::class,
    'registrar'
])->middleware([
    'guest',
    'throttle:3,1'
])->name('registro.guardar');

/*
|--------------------------------------------------------------------------
| ACCESO DENEGADO
|--------------------------------------------------------------------------
*/

Route::get('/sin-permisos', function () {
    return view('acceso.sinpermisos');
})->middleware('auth')->name('sin-permisos');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS DEL SISTEMA
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'biblioteca'])->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | ESCRITORIO
    |--------------------------------------------------------------------------
    */

    Route::get('/', [
        DashboardController::class,
        'index'
    ])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | LIBROS
    |--------------------------------------------------------------------------
    */

    Route::resource('libros', LibroController::class)
        ->parameters(['libros' => 'libro'])
        ->except('show');

    /*
    |--------------------------------------------------------------------------
    | AUTORES
    |--------------------------------------------------------------------------
    */

    Route::resource('autores', AutorController::class)
        ->parameters(['autores' => 'autor'])
        ->except('show');

    /*
    |--------------------------------------------------------------------------
    | PERSONAS
    |--------------------------------------------------------------------------
    */

    Route::resource('personas', PersonaController::class)
        ->parameters(['personas' => 'persona']);

    /*
    |--------------------------------------------------------------------------
    | USUARIOS LECTORES
    |--------------------------------------------------------------------------
    */

    Route::resource('usuarios', UsuarioController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->except('show');

    /*
    |--------------------------------------------------------------------------
    | BIBLIOTECARIOS
    |--------------------------------------------------------------------------
    */

    Route::resource('bibliotecarios', BibliotecarioController::class)
        ->parameters(['bibliotecarios' => 'bibliotecario'])
        ->except('show');

    /*
    |--------------------------------------------------------------------------
    | EJEMPLARES
    |--------------------------------------------------------------------------
    */

    Route::resource('ejemplares', EjemplarController::class)
        ->parameters(['ejemplares' => 'ejemplar'])
        ->except('show');

    /*
    |--------------------------------------------------------------------------
    | PRESTAMOS
    |--------------------------------------------------------------------------
    */

    Route::resource('prestamos', PrestamoController::class)
        ->parameters(['prestamos' => 'prestamo'])
        ->except('show');

    /*
    |--------------------------------------------------------------------------
    | DEVOLUCIONES
    |--------------------------------------------------------------------------
    */

    Route::post('devoluciones/{prestamo}/registrar', [
        DevolucionController::class,
        'registrar'
    ])->name('devoluciones.registrar');

    Route::resource('devoluciones', DevolucionController::class)
        ->parameters(['devoluciones' => 'devolucion'])
        ->only(['index', 'edit', 'update', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | CATALOGOS
    |--------------------------------------------------------------------------
    */

    Route::prefix('catalogos')
        ->name('catalogos.')
        ->group(function (): void {

            Route::get('{catalogo}', [
                CatalogoController::class,
                'index'
            ])->name('index');

            Route::get('{catalogo}/nuevo', [
                CatalogoController::class,
                'create'
            ])->name('create');

            Route::post('{catalogo}', [
                CatalogoController::class,
                'store'
            ])->name('store');

            Route::get('{catalogo}/{id}/editar', [
                CatalogoController::class,
                'edit'
            ])->name('edit');

            Route::put('{catalogo}/{id}', [
                CatalogoController::class,
                'update'
            ])->name('update');

            Route::delete('{catalogo}/{id}', [
                CatalogoController::class,
                'destroy'
            ])->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | REGISTROS RAPIDOS
    |--------------------------------------------------------------------------
    */

    Route::prefix('rapido')
        ->name('rapido.')
        ->group(function (): void {

            Route::post('autores', [
                AutorController::class,
                'rapido'
            ])->name('autores');

            Route::post('{catalogo}', [
                CatalogoController::class,
                'rapido'
            ])
                ->whereIn('catalogo', [
                    'categorias',
                    'editoriales'
                ])
                ->name('catalogos');
        });

    /*
    |--------------------------------------------------------------------------
    | CUENTAS Y PERMISOS
    |--------------------------------------------------------------------------
    */

    Route::prefix('cuentas')
        ->name('cuentas.')
        ->group(function (): void {

            Route::get('/', [
                CuentaAccesoController::class,
                'index'
            ])->name('index');

            Route::get('nueva', [
                CuentaAccesoController::class,
                'create'
            ])->name('create');

            Route::post('/', [
                CuentaAccesoController::class,
                'store'
            ])->name('store');

            Route::get('historial', [
                CuentaAccesoController::class,
                'historial'
            ])->name('historial');

            Route::get('roles/{rol}/permisos', [CuentaAccesoController::class, 'permisosRol'])->name('roles.permisos');
            Route::put('roles/{rol}/permisos', [CuentaAccesoController::class, 'guardarPermisosRol'])->name('roles.guardar');

            Route::get('{cuenta}/editar', [
                CuentaAccesoController::class,
                'edit'
            ])->name('edit');

            Route::put('{cuenta}', [
                CuentaAccesoController::class,
                'update'
            ])->name('update');

            Route::get('{cuenta}/permisos', [
                CuentaAccesoController::class,
                'permisos'
            ])->name('permisos');

            Route::put('{cuenta}/permisos', [
                CuentaAccesoController::class,
                'guardarPermisos'
            ])->name('permisos.guardar');
        });

});
