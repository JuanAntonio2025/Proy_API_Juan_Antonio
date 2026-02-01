<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Petition;
use Illuminate\Http\Request;


class PetitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $peticiones = Petition::all();
        return $peticiones;
    }

    public function listMine(Request $request)
    {
        $id = 1;
        $peticiones = Petition::all()->where('user_id', $id);
        return $peticiones;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'addressee' => 'required|max:255',
            'category_id' => 'required|exists:categories,id',
            // 'file' => 'required',
        ]);

        $input = $request->all();

        $category = Category::findOrFail($request->input('category_id'));
        $user = 1;
        $petition = new Petition($input);
        $petition->user()->associate($user);
        $petition->category()->associate($category);
        $petition->signatories = 0;
        $petition->status = 'pending';

        $petition->save();
        return $petition;
    }

    public function firmar (Request $request, $id)
    {
        $petition = Petition::findOrFail($id);
        $user = 1;

        $user_id = [$user];

        $petition->signatories = $petition->signatories + 1;
        $petition->firmas()->attach($user_id);
        $petition->save();
        return $petition;
    }

    public function cambiarEstado(Request $request, $id)
    {
       $petition = Petition::findOrFail($id);
       $petition->status = 'accepted';
       $petition->save();
       return $petition;
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $peticion = Petition::findOrFail($id);
        return $peticion;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $peticion = Petition::findOrFail($id);
        $peticion->update($request->all());
        return $peticion;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request, $id)
    {
        $petition = Petition::findOrFail($id);
        $petition->delete();
        return $petition;
    }
}
