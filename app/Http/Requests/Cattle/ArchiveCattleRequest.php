<?php

namespace App\Http\Requests\Cattle;

use App\Models\Cattle;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** "Archive" (route cattle.destroy, DELETE): owner, explicit confirmation, and the version the page showed. */
class ArchiveCattleRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('cattle'));
    }

    public function rules(): array
    {
        return [
            'confirm_archive' => ['accepted'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['confirm_archive.accepted' => __('Confirm that you want to archive this animal.')];
    }

    public function version(): int
    {
        return (int) $this->validated('version');
    }

    public function cattle(): Cattle
    {
        return $this->route('cattle');
    }
}
