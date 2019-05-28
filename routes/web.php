<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::get('/', 'ProjectsController@index')->name('projects.index');
Route::get('/project/show/{id}', 'ProjectsController@show')->name('projects.show');
Route::get('/project/edit/{id}', 'ProjectsController@showEditForm')->name('projects.edit_form');

Route::post('/project/delete/{id}', 'ProjectsController@deleteNoJS')->name('projects.delete_nojs');
Route::post('/project/delete', 'ProjectsController@delete')->name('projects.delete');
Route::post('/project/edit/{id}', 'ProjectsController@edit')->name('projects.edit');

Route::get('/project/create', 'ProjectsController@showCreateForm')->name('projects.create_form');
Route::post('/project/create', 'ProjectsController@create')->name('projects.create');
