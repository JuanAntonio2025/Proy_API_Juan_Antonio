<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
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

    public function index()
    {
        try {
            $users = User::orderBy('created_at', 'asc')->get();

            return $this->sendResponse($users, 'Usuarios recuperados correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al recuperar usuarios', $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            return $this->sendResponse($user, 'Usuario recuperado correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Usuario no encontrado', $e->getMessage(), 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            return $this->sendResponse($user, 'Usuario creado correctamente', 201);
        } catch (\Exception $e) {
            return $this->sendError('Error al crear usuario', $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error de validación', $validator->errors(), 422);
        }

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->role = $request->role;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return $this->sendResponse($user, 'Usuario actualizado correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar usuario', $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            User::findOrFail($id)->delete();

            return $this->sendResponse(null, 'Usuario eliminado correctamente');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar usuario', $e->getMessage(), 500);
        }
    }
}
