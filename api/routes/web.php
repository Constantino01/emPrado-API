<?php

use Illuminate\Support\Facades\Route;

// Importação dos Controladores por Domínio
use App\Http\Controllers\Course\CourseController;
use App\Http\Controllers\Course\ModuleController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\GroupController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Event\InviteController;
use App\Http\Controllers\Media\MediaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Futuramente, deves envolver estas rotas no middleware de autenticação,
| por exemplo: Route::middleware('auth:sanctum')->group(function () { ... });
|--------------------------------------------------------------------------
*/

// =====================================================================
// DOMÍNIO: COURSES (Cursos e Módulos)
// =====================================================================
Route::apiResource('courses', CourseController::class);
Route::post('courses/{course}/enroll', [CourseController::class, 'enroll']); // Matrícula com regras de negócio

Route::apiResource('modules', ModuleController::class);

// =====================================================================
// DOMÍNIO: STUDENTS (Alunos e Turmas)
// =====================================================================
Route::apiResource('students', StudentController::class);

// Perfil do Aluno - Histórico e Ligações
Route::get('students/{student}/courses', [StudentController::class, 'courses']);
Route::get('students/{student}/modules', [StudentController::class, 'modules']);
Route::get('students/{student}/invitations', [StudentController::class, 'invitations']);

// Perfil do Aluno - Gestão
Route::post('students/{student}/groups/sync', [StudentController::class, 'syncGroups']);
Route::patch('students/{student}/modules/{moduleId}/status', [StudentController::class, 'updateModuleStatus']);

// Grupos / Turmas
Route::apiResource('groups', GroupController::class);
Route::post('groups/{group}/students', [GroupController::class, 'addStudent']);
Route::delete('groups/{group}/students/{student}', [GroupController::class, 'removeStudent']);

// =====================================================================
// DOMÍNIO: EVENTS (Eventos, Convites e SES Email)
// =====================================================================
Route::apiResource('events', EventController::class);

Route::apiResource('invites', InviteController::class)->except(['update']); // Convites não se editam
Route::post('invites/{invite}/dispatch', [InviteController::class, 'sendToStudents']); // Dispara SES Queue
Route::patch('invites/{invite}/students/{student}/rsvp', [InviteController::class, 'updateRsvp']); // Resposta

// =====================================================================
// DOMÍNIO: MEDIA (AWS S3, Presigned URLs e Glacier)
// =====================================================================

// 1. Upload Tradicional (Imagens -> Conversão WebP)
Route::post('media/image', [MediaController::class, 'uploadImage']);

// 2. Fluxo Multipart (Upload Direto do Frontend para S3 - 10GB+)
Route::post('media/multipart/initiate', [MediaController::class, 'initiateMultipart']);
Route::post('media/multipart/sign-part', [MediaController::class, 'signPart']);
Route::post('media/multipart/complete', [MediaController::class, 'completeMultipart']);

// 3. Gestão de Ficheiros
Route::get('media/{media}', [MediaController::class, 'show']);       // Recupera URLs assinadas / Lida com Glacier
Route::delete('media/{media}', [MediaController::class, 'destroy']); // Apaga fisicamente da S3