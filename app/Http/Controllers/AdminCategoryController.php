<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCategoryController extends Controller
{
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
     * Listado de categorías
     */
    public function index()
    {
        try {
            $categories = Category::orderBy('created_at', 'asc')->get();

            return $this->sendResponse($categories, 'Categorías recuperadas correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar categorías', $e->getMessage(), 500);
        }
    }

    /**
     * Detalle de una categoría
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);

            return $this->sendResponse($category, 'Categoría recuperada correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Categoría no encontrada', $e->getMessage(), 404);
        }
    }

    /**
     * Crear categoría
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            $category = Category::create([
                'name' => $request->name
            ]);

            return $this->sendResponse($category, 'Categoría creada correctamente', 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al crear categoría', $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            $category->update([
                'name' => $request->name
            ]);

            return $this->sendResponse($category, 'Categoría actualizada correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar categoría', $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar categoría
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return $this->sendResponse(null, 'Categoría eliminada correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar categoría', $e->getMessage(), 500);
        }
    }
}
