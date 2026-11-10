<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request): View
    {
        return view('residents.index', [
            'residents' => Resident::search($request->query('search'))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'totalResidents' => Resident::count(),
            'search' => $request->query('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resident = Resident::create($this->validateResident($request, 'createResident'));

        return redirect()->route('residents.show', $resident)
            ->with('success', "Resident {$resident->name} has been saved.");
    }

    public function show(Resident $resident): View
    {
        return view('residents.show', [
            'resident' => $resident,
            'serviceRequests' => $resident->serviceRequests()->latest('date_received')->latest('id')->get(),
        ]);
    }

    public function update(Request $request, Resident $resident): RedirectResponse
    {
        $resident->update($this->validateResident($request, 'updateResident'));

        return redirect()->route('residents.show', $resident)
            ->with('success', 'Resident record has been updated.');
    }

    /**
     * @return array{name: string, address: string, contact_number: ?string, purok: string}
     */
    private function validateResident(Request $request, string $errorBag): array
    {
        return $request->validateWithBag($errorBag, [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'purok' => ['required', 'string', 'max:50'],
        ], [
            'contact_number.regex' => 'The contact number may only contain numbers, spaces, +, - and parentheses.',
        ]);
    }
}
