<?php

namespace App\Http\Controllers;

use App\Models\VeterinarianProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VetDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = VeterinarianProfile::with('user')
            ->where('is_listed', true);

        if ($request->filled('district')) {
            $query->where('district', 'like', '%'.$request->string('district').'%');
        }

        if ($request->filled('animal')) {
            match ($request->string('animal')->toString()) {
                'cattle' => $query->where('treats_cattle', true),
                'goats' => $query->where('treats_goats', true),
                'poultry' => $query->where('treats_poultry', true),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('upazila', 'like', '%'.$search.'%')
                    ->orWhere('address', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $vets = $query->latest()->paginate(12)->withQueryString();

        return view('vets.index', compact('vets'));
    }

    public function show(VeterinarianProfile $vet): View
    {
        Gate::authorize('view', $vet);
        $vet->load('user');

        return view('vets.show', compact('vet'));
    }
}
