<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateBuyerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BuyerController extends Controller
{
    public function index(): Response
    {
        $buyers = User::query()
            ->where('role', 'buyer')
            ->select(['id', 'name', 'email', 'contact_no'])
            ->withCount('sales')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('buyers/index', compact('buyers'));
    }

    public function edit(int|string $id): Response
    {
        $buyer = $this->findBuyer($id);

        return Inertia::render('buyers/edit', [
            'buyer' => $buyer->only(['id', 'name', 'email', 'contact_no']),
        ]);
    }

    public function update(UpdateBuyerRequest $request, int|string $id): RedirectResponse
    {
        $buyer = $this->findBuyer($id);
        $buyer->fill($request->validated());

        if ($buyer->isDirty('email')) {
            $buyer->email_verified_at = null;
        }

        $buyer->save();

        return redirect()
            ->route('buyers.index')
            ->with('success', 'Buyer information updated successfully.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        DB::transaction(function () use ($id): void {
            $buyer = User::where('role', 'buyer')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($buyer->sales()->exists()) {
                throw ValidationException::withMessages([
                    'buyer' => 'Buyers with sales records cannot be deleted.',
                ]);
            }

            $buyer->delete();
        });

        return redirect()
            ->route('buyers.index')
            ->with('success', 'Buyer deleted successfully.');
    }

    private function findBuyer(int|string $id): User
    {
        return User::where('role', 'buyer')->findOrFail($id);
    }
}
