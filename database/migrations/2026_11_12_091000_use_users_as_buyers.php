<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'contact_no')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('contact_no', 20)->nullable()->index();
            });
        }

        if (! Schema::hasColumn('nursery_sales', 'user_id')) {
            Schema::table('nursery_sales', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();
            });
        }

        $usersByName = DB::table('users')
            ->select(['id', 'name', 'contact_no'])
            ->get()
            ->groupBy(fn (object $user) => mb_strtolower(trim($user->name)));
        $mappings = [];

        foreach (DB::table('buyers')->orderBy('id')->get() as $buyer) {
            $matches = $usersByName->get(mb_strtolower(trim($buyer->buyer_name)), collect());
            $buyerContact = preg_replace('/\D+/', '', $buyer->contact_no);
            $matchingContacts = $matches->filter(function (object $user) use ($buyerContact): bool {
                $contact = preg_replace('/\D+/', '', (string) $user->contact_no);

                return $contact === '' || $contact === $buyerContact;
            });

            if ($matchingContacts->count() === 1) {
                $userId = $matchingContacts->first()->id;
            } else {
                $userId = $this->createLegacyUser($buyer);
            }

            $mappings[] = [
                'buyer_id' => $buyer->id,
                'user_id' => $userId,
                'contact_no' => $this->formatContactNumber($buyer->contact_no),
            ];
        }

        foreach ($mappings as $mapping) {
            DB::table('nursery_sales')
                ->where('buyer_id', $mapping['buyer_id'])
                ->update(['user_id' => $mapping['user_id']]);

            DB::table('users')
                ->where('id', $mapping['user_id'])
                ->whereNull('contact_no')
                ->update(['contact_no' => $mapping['contact_no']]);
        }

        Schema::table('nursery_sales', function (Blueprint $table) {
            $table->dropForeign(['buyer_id']);
            $table->dropColumn('buyer_id');
        });

        Schema::drop('buyers');
    }

    public function down(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_name', 150);
            $table->string('contact_no', 20)->index();
            $table->timestamps();
        });

        $buyerIds = [];

        foreach (
            DB::table('nursery_sales')
                ->join('users', 'users.id', '=', 'nursery_sales.user_id')
                ->select('users.id', 'users.name', 'users.contact_no')
                ->distinct()
                ->get() as $user
        ) {
            $buyerIds[$user->id] = DB::table('buyers')->insertGetId([
                'buyer_name' => $user->name,
                'contact_no' => $user->contact_no ?? '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('nursery_sales', function (Blueprint $table) {
            $table->foreignId('buyer_id')->nullable();
        });

        foreach ($buyerIds as $userId => $buyerId) {
            DB::table('nursery_sales')
                ->where('user_id', $userId)
                ->update(['buyer_id' => $buyerId]);
        }

        Schema::table('nursery_sales', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreign('buyer_id')
                ->references('id')
                ->on('buyers')
                ->restrictOnDelete();
        });
    }

    private function formatContactNumber(string $contact): string
    {
        $digits = preg_replace('/\D+/', '', $contact) ?? '';

        if (str_starts_with($digits, '63')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        return $digits;
    }

    private function createLegacyUser(object $buyer): int
    {
        $email = sprintf('legacy-buyer-%d@example.invalid', $buyer->id);
        $existingId = DB::table('users')->where('email', $email)->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) DB::table('users')->insertGetId([
            'name' => $buyer->buyer_name,
            'email' => $email,
            'contact_no' => $this->formatContactNumber($buyer->contact_no),
            'password' => Hash::make(Str::random(64)),
            'email_verified_at' => null,
            'created_at' => $buyer->created_at,
            'updated_at' => $buyer->updated_at,
        ]);
    }
};
