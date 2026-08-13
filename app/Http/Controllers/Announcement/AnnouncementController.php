<?php

namespace App\Http\Controllers\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest as ModelRequest;
use App\Repository\Announcement\AnnouncementRepositoryInterface;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementRepositoryInterface $modelRepository)
    {
        $this->middleware('permission:view-announcements')->only('show');
        $this->middleware('permission:manage-announcements')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        return $this->modelRepository->getList();
    }

    public function store(ModelRequest $request)
    {
        return $this->modelRepository->create($request->validated());
    }

    public function show(string $id)
    {
        return $this->modelRepository->find($id);
    }

    public function update(ModelRequest $request, string $id)
    {
        return $this->modelRepository->update($request->validated(), $id);
    }

    public function destroy(string $id)
    {
        return $this->modelRepository->delete($id);
    }
}
