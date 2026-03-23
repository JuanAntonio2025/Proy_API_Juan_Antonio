<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\File;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminPetitionController extends Controller {
    private function sendResponse($data, $message, $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $code);
    }

    private function sendError($error, $errorMessages = [], $code = 400)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Listado de peticiones para admin
     */
    public function index()
    {
        try {
            $petitions = Petition::with(['category', 'user', 'files'])
                ->orderBy('created_at', 'asc')
                ->get();

            return $this->sendResponse($petitions, 'Peticiones recuperadas correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar peticiones', $e->getMessage(), 500);
        }
    }

    /**
     * Detalle de una petición
     */
    public function show($id)
    {
        try {
            $petition = Petition::with(['category', 'user', 'files'])->findOrFail($id);

            return $this->sendResponse($petition, 'Petición recuperada correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Petición no encontrada', $e->getMessage(), 404);
        }
    }

    /**
     * Datos auxiliares para formularios admin
     */
    public function meta()
    {
        try {
            $users = User::select('id', 'name', 'email')->get();
            $categories = Category::select('id', 'name')->get();

            return $this->sendResponse([
                'users' => $users,
                'categories' => $categories
            ], 'Datos auxiliares recuperados correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar datos auxiliares', $e->getMessage(), 500);
        }
    }

    /**
     * Crear petición desde admin
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'addressee' => 'required|max:255',
            'status' => 'required|in:pending,accepted',
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            $petition = Petition::create([
                'title' => $request->title,
                'description' => $request->description,
                'addressee' => $request->addressee,
                'signatories' => 0,
                'status' => $request->status,
                'user_id' => $request->user_id,
                'category_id' => $request->category_id,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $this->fileUpload($file, $petition->id);
                }
            }

            return $this->sendResponse(
                $petition->load(['category', 'user', 'files']),
                'Petición creada con éxito',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError('Error al crear la petición', $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar petición desde admin
     */
    public function update(Request $request, $id)
    {
        $petition = Petition::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'addressee' => 'required|max:255',
            'status' => 'required|in:pending,accepted',
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            $petition->update([
                'title' => $request->title,
                'description' => $request->description,
                'addressee' => $request->addressee,
                'status' => $request->status,
                'user_id' => $request->user_id,
                'category_id' => $request->category_id,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $this->fileUpload($file, $petition->id);
                }
            }

            return $this->sendResponse(
                $petition->load(['category', 'user', 'files']),
                'Petición actualizada con éxito'
            );
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar la petición', $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar una imagen concreta
     */
    public function destroyFile($fileId)
    {
        try {
            $file = File::findOrFail($fileId);

            Storage::disk('public')->delete($file->file_path);
            $file->delete();

            return $this->sendResponse(null, 'Imagen eliminada correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar la imagen', $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar petición completa
     */
    public function destroy($id)
    {
        try {
            $petition = Petition::with('files')->findOrFail($id);

            foreach ($petition->files as $file) {
                Storage::disk('public')->delete($file->file_path);
                $file->delete();
            }

            $petition->delete();

            return $this->sendResponse(null, 'Petición eliminada correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar la petición', $e->getMessage(), 500);
        }
    }

    /**
     * Subida de archivos
     */
    private function fileUpload($file, $petitionId)
    {
        $path = $file->store('fotos', 'public');

        return File::create([
            'petition_id' => $petitionId,
            'name' => $file->getClientOriginalName(),
            'file_path' => $path
        ]);
    }
}
