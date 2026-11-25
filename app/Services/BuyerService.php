<?php

namespace App\Services;

use App\Models\NurserySale;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BuyerService
{
    public function listBuyers(): LengthAwarePaginator
    {
        $buyers = User::query()
            ->where('role', 'buyer')
            ->select(['id', 'name', 'email', 'contact_no'])
            ->withCount('sales')
            ->orderBy('name')
            ->paginate(15);

        $buyers->getCollection()->transform(function (User $buyer): User {
            $buyer->contact_no = $this->formatContactNumber($buyer->contact_no);

            return $buyer;
        });

        return $buyers;
    }

    public function createBuyer(array $data): User
    {
        return User::create([
            ...$data,
            'role' => 'buyer',
        ]);
    }

    public function buyerForEdit(int|string $id): array
    {
        $buyer = $this->findBuyer($id);

        return [
            ...$buyer->only(['id', 'name', 'email']),
            'contact_no' => $this->formatContactNumber($buyer->contact_no),
        ];
    }

    public function updateBuyer(int|string $id, array $data): User
    {
        $buyer = $this->findBuyer($id);
        $buyer->fill($data);

        if ($buyer->isDirty('email')) {
            $buyer->email_verified_at = null;
        }

        $buyer->save();

        return $buyer;
    }

    public function deleteBuyer(int|string $id): void
    {
        DB::transaction(function () use ($id): void {
            $buyer = User::where('role', 'buyer')
                ->lockForUpdate()
                ->findOrFail($id);

            $buyer->sales()->each(function (NurserySale $sale): void {
                $sale->delete();
            });
            $buyer->delete();
        });
    }

    private function findBuyer(int|string $id): User
    {
        return User::where('role', 'buyer')->findOrFail($id);
    }

    private function formatContactNumber(?string $contactNumber): ?string
    {
        if ($contactNumber === null || trim($contactNumber) === '') {
            return $contactNumber;
        }

        $digits = preg_replace('/\D+/', '', $contactNumber) ?? '';

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        return $contactNumber;
    }
}
