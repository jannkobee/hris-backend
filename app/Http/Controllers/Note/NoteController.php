<?php

namespace App\Http\Controllers\Note;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteRequest;
use App\Repository\Note\NoteRepositoryInterface;

class NoteController extends Controller
{
    public function __construct(private readonly NoteRepositoryInterface $notes)
    {
        $this->requireResourcePermissions('notes');
    }

    public function index()
    {
        return $this->notes->getList();
    }

    public function store(NoteRequest $request)
    {
        return $this->notes->create($request->validated());
    }

    public function show(string $id)
    {
        return $this->notes->find($id);
    }

    public function update(NoteRequest $request, string $id)
    {
        return $this->notes->update($request->validated(), $id);
    }

    public function destroy(string $id)
    {
        return $this->notes->delete($id);
    }
}
