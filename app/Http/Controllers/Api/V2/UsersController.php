<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Users\AddUserRequest;
use App\Http\Requests\V2\Users\ChangePasswordRequest;
use App\Http\Requests\V2\Users\UpdatePhotoRequest;
use App\Services\V2\UsersService;
use Illuminate\Http\Request;

/**
 * Users & security (API_REFERENCE.md §11.9).
 */
class UsersController extends BaseController
{
    public function __construct(private readonly UsersService $users)
    {
    }

    /** GET /users */
    public function index(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string'],
            'role' => ['nullable', 'in:all,governor,coordinator,sector_head,data_admin,facilitator,system_admin,field_officer,auditor'],
            'sector' => ['nullable', 'in:all,any,health,education,infrastructure,agriculture'],
        ]);

        return $this->users->listUsers(
            $request->user(),
            $validated['search'] ?? null,
            $validated['role'] ?? 'all',
            $validated['sector'] ?? 'all',
        );
    }

    /** GET /users/{id} */
    public function show(Request $request, string $id): array
    {
        return $this->users->getUserProfile($request->user(), $id);
    }

    /** POST /users (multipart) */
    public function store(AddUserRequest $request)
    {
        $this->users->createUser(
            $request->user(),
            $request->validated(),
            $request->file('photo'),
        );

        return $this->accepted();
    }

    /** POST /users/me/password */
    public function changeMyPassword(ChangePasswordRequest $request)
    {
        $data = $request->validated();
        $this->users->changeMyPassword($request->user(), $data['currentPassword'], $data['newPassword']);

        return $this->noContent();
    }

    /** POST /users/me/photo (multipart) */
    public function updateMyPhoto(UpdatePhotoRequest $request)
    {
        $this->users->updateMyPhoto($request->user(), $request->file('photo'));

        return $this->noContent();
    }

    /** GET /users/security-log */
    public function securityLog(Request $request): array
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'in:all,logins,changes,denied'],
            'q' => ['nullable', 'string'],
        ]);

        return $this->users->securityLog(
            $request->user(),
            $validated['filter'] ?? 'all',
            $validated['q'] ?? null,
        );
    }
}
