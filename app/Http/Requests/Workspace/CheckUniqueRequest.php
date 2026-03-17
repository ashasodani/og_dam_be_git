<?php
namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckUniqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $modelMap = [
            'workspaces'   => ['model' => \App\Models\Workspaces::class, 'field' => 'slug'],
            'portals'      => ['model' => \App\Models\Portals::class, 'field' => 'slug'],
            'collections'  => ['model' => \App\Models\Collections::class, 'field' => 'slug'],
            'labels'       => ['model' => \App\Models\Labels::class, 'field' => 'name'],
            'sub_folders'  => ['model' => \App\Models\SubFolders::class, 'field' => 'slug'],
            'sections'     => ['model' => \App\Models\Sections::class, 'field' => 'name'],
            'tags'         => ['model' => \App\Models\Tags::class, 'field' => 'name'],
            'users'        => ['model' => \App\Models\User::class, 'field' => 'email'],
            'invite_users' => ['model' => \App\Models\InviteUsers::class, 'field' => 'email'],
            'roles'        => ['model' => \App\Models\Role::class, 'field' => 'name'],
            'tiles'        => ['model' => \App\Models\Tiles::class, 'field' => 'slug'],
        ];

        $allowedTypes = array_keys($modelMap);

        $rules = [
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'name' => ['required', 'string', 'min:1', 'max:255'],
        ];

        if (isset($modelMap[$this->type])) {
            $modelClass = $modelMap[$this->type]['model'];
            $column     = $modelMap[$this->type]['field'];

            $rules['name'][] = Rule::unique($modelClass, $column)->whereNull('deleted_at');
        }

        return $rules;
    }
}
