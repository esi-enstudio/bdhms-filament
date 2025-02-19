<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    /**
     * @param array $row
     *
     * @return Model|User|null
     */
    public function model(array $row): Model|User|null
    {
        return new User([
            'name' => $row['name'],
            'phone' => $row['phone_number'],
            'email' => $row['email'],
            'password' => $row['password'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.phone' => ['required','unique:users,phone'],
            '*.email' => ['required','unique:users,email'],
        ];
    }
}
