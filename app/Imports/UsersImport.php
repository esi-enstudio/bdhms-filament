<?php

namespace App\Imports;

use App\Models\House;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UsersImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection): void
    {
        // 🔵 Get all roles in one query to optimize performance
        $roles = Role::pluck('id', 'name'); // ['admin' => 1, 'manager' => 2, etc.]

        // 🔵 Get all houses in one query to optimize performance
        $houses = House::pluck('id', 'code'); // ['MYMVAI01' => 1, 'MYMVAI02' => 2, etc.]

        // 🟢 Map data with optimized queries
        $data = $collection->map(function ($row){
            return [
                'name'      => $row['name'],
                'phone'     => '0'.$row['phone_number'],
                'email'     => $row['email'],
                'password'  => Hash::make($row['password']),
            ];
        })->toArray();

        User::query()->upsert($data, ['email'], ['name', 'phone']);

        // 🟠 Fetch users again to assign roles
        foreach ($collection as $row) {
            $user = User::query()->where('email', $row['email'])->first();

            // 🔹 Assign Houses (if provided)
            if (!empty($row['dd_code'])) {
                $houseCodes = explode(',', str_replace(' ', '', $row['dd_code'])); // Convert CSV to array
                $houseIds = array_filter(array_map(fn($code) => $houses[$code] ?? null, $houseCodes));

                if (!empty($houseIds)) {
                    $user->houses()->sync($houseIds); // Attach houses via pivot
                } else {
                    Log::warning("No valid houses found for user {$row['email']} with codes: {$row['dd_code']}");
                }
            }

            // 🔹 Assign Roles (if provided)
            if ($user && isset($row['role']) && !empty($row['role'])) {
                $roleName = strtolower($row['role']); // Ensure consistent casing

                if (isset($roles[$roleName])) {
                    $user->assignRole($roleName);
                } else {
                    Log::warning("Role '{$row['role']}' not found for user {$row['email']}");
                }
            }
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    // public function rules(): array
    // {
    //     return [
    //         '*.phone' => ['required','unique:users,phone'],
    //         '*.email' => ['required','unique:users,email'],
    //     ];
    // }
}
