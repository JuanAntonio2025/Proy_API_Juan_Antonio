<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\File;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PetitionController extends Controller
{
    private function sendResponse($data, $message, $code = 200) {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    private function sendError($error, $errorMessages = [], $code = 404) {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (!empty($errorMessages)) { $response['errors'] = $errorMessages; }
        return response()->json($response, $code);
    }

    public function index(Request $request)
    {
        try {
            $peticiones = Petition::with(['user', 'category', 'files'])->get();
            return $this->sendResponse($peticiones, 'Peticiones recuperadas con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar peticiones', $e->getMessage(), 500);
        }

    }

    public function list() {
        try {
            $peticiones = Petition::jsonPaginate();
            return response()->json($peticiones, 200); // Paginación suele ir directa
        } catch (\Exception $e) {
            return $this->sendError('Error en la paginación', $e->getMessage(), 500);
        }
    }

    public function listMine(Request $request)
    {
        try {
            $userId = auth()->id();
            $peticiones = Petition::where('user_id', $userId)->with(['user', 'category', 'files'])->get();
            return $this->sendResponse($peticiones, 'Tus peticiones recuperadas con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar tus peticiones', $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'addressee' => 'required',
            'category_id' => 'required|exists:categories,id',
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {

            $peticion = new Petition($request->only([
                'title',
                'description',
                'addressee',
                'category_id'
            ]));

            $peticion->user_id = Auth::id();
            $peticion->signatories = 0;
            $peticion->status = 'pending';
            $peticion->save();

            // Guardar múltiples archivos
            foreach ($request->file('files') as $file) {

                $path = $file->store('fotos', 'public');

                $peticion->files()->create([
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path
                ]);
            }

            return $this->sendResponse(
                $peticion->load('files'),
                'Petición creada con éxito',
                201
            );

        } catch (\Exception $e) {
            return $this->sendError(
                'Error al crear la petición',
                $e->getMessage(),
                500
            );
        }
    }

    public function firmar (Request $request, $id)
    {
        try {
            $peticion = Petition::findOrFail($id);
            $userId = auth()->id();

            if ($peticion->userSigners()->where('user_id', $userId)->exists()) {
                return $this->sendError('Ya has firmado esta petición', [], 403);
            }

            $peticion->userSigners()->attach($userId);
            $peticion->increment('signatories');
            return $this->sendResponse($peticion, 'Petición firmada con éxito', 201);
        } catch (\Exception $e) {
            return $this->sendError('No se pudo firmar la petición', $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $petition = Petition::with(['user', 'category', 'files'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $petition,
                'message' => 'Petición recuperada con éxito'
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Petición no encontrada', [], 404);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $petition = Petition::findOrFail($id);

            if ($request->user()->cannot('update', $petition)) {
                return $this->sendError('No autorizado', [], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'addressee' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
                'files' => 'nullable|array',
                'files.*' => 'image|max:4096',
                'deleted_files' => 'nullable|array',
                'deleted_files.*' => 'integer|exists:files,id',
            ]);

            $petition->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'addressee' => $validated['addressee'],
                'category_id' => $validated['category_id'],
            ]);

            /*
            |----------------------------------
            | 🗑️ Eliminar imágenes seleccionadas
            |----------------------------------
            */
            if ($request->filled('deleted_files')) {
                $filesToDelete = $petition->files()
                    ->whereIn('id', $request->deleted_files)
                    ->get();

                foreach ($filesToDelete as $file) {
                    \Storage::disk('public')->delete($file->file_path);
                    $file->delete();
                }
            }

            /*
            |----------------------------------
            | 📤 Añadir nuevas imágenes
            |----------------------------------
            */
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $uploaded) {

                    $path = $uploaded->store('fotos', 'public');

                    $petition->files()->create([
                        'name' => $uploaded->getClientOriginalName(),
                        'file_path' => $path,
                    ]);
                }
            }

            return $this->sendResponse(
                $petition->load(['files', 'category', 'user']),
                'Petición actualizada con éxito'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar petición', [
                'petition_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->sendError(
                'Error al actualizar la petición',
                $e->getMessage(),
                500
            );
        }
    }

    public function cambiarEstado(Request $request, $id)
    {
        $peticion = Petition::findOrFail($id);
        $peticion->status = 'accepted';
        $peticion->save();
        return $peticion;
    }

    public function destroy(Request $request, $id)
    {
        try {
            $peticion = Petition::with('files')->findOrFail($id);
            if ($request->user()->cannot('delete', $peticion)) {
                return $this->sendError('No autorizado', [], 403);
            }
            // Eliminar archivos físicos
            foreach ($peticion->files as $file) {
                Storage::disk('public')->delete($file->file_path);
            }

            $peticion->delete();
            return $this->sendResponse(null, 'Petición eliminada con éxito');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar', $e->getMessage(), 500);
        }

    }

    public function peticionesFirmadas(Request $request)
    {
        $user = $request->user();
        $petitions = $user->signedPetitions()->with(['files', 'category', 'user'])->get();

        return response()->json([
            'success' => true,
            'data' => $petitions
        ]);
    }

}
