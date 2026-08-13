<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceRequest as ModelRequest;
use App\Models\Attendance;
use App\Repository\Attendance\AttendanceRepositoryInterface;
use App\Services\AppSettings\AppSettingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    private $modelRepository;

    public function __construct(
        AttendanceRepositoryInterface $modelRepository,
        private readonly AppSettingService $settings
    ) {
        $this->modelRepository = $modelRepository;
        $this->middleware('permission:view-attendances')->only(['index', 'show']);
        $this->middleware('permission:manage-attendances')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $date = $request->query('date');

        if ($date) {
            return $this->modelRepository->getListByDate($date, $request->query());
        }

        return $this->modelRepository->getList();
    }

    public function store(ModelRequest $request)
    {
        $this->ensureManualEntriesAllowed($request);

        return $this->modelRepository->create($request->validated());
    }

    public function show(Request $request, string $id)
    {
        return $this->modelRepository->find($id);
    }

    public function update(ModelRequest $request, string $id)
    {
        $this->ensureManualEntriesAllowed($request);

        return $this->modelRepository->update($request->validated(), $id);
    }

    public function destroy(Request $request, string $id)
    {
        $this->ensureManualEntriesAllowed($request);

        return $this->modelRepository->delete($id);
    }

    public function timeIn(ModelRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['source_ip'] = $request->ip();

        return $this->modelRepository->timeIn($this->employeeId($request), $data);
    }

    public function timeOut(ModelRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['source_ip'] = $request->ip();

        return $this->modelRepository->timeOut($this->employeeId($request), $data);
    }

    public function today(Request $request): JsonResponse
    {
        return $this->modelRepository->getTodayAttendance($this->employeeId($request));
    }

    public function history(Request $request): JsonResponse
    {
        return $this->modelRepository->getUserHistory($this->employeeId($request), $request->all());
    }

    public function photo(Request $request, Attendance $attendance, string $type)
    {
        if (! in_array($type, ['time-in', 'time-out'], true)) {
            abort(404);
        }

        $isOwner = $attendance->employee?->user_id === $request->user()?->id;
        $canViewAll = $request->user()?->hasPermission('view-attendances');

        if (! $isOwner && ! $canViewAll) {
            throw new AuthorizationException('You cannot view this attendance photo.');
        }

        $prefix = str_replace('-', '_', $type);
        $disk = $attendance->getAttribute("{$prefix}_photo_disk");
        $path = $attendance->getAttribute("{$prefix}_photo_path");

        if (! $disk || ! $path || ! Storage::disk($disk)->exists($path)) {
            abort(404, 'Attendance photo not found.');
        }

        return Storage::disk($disk)->download(
            $path,
            $attendance->getAttribute("{$prefix}_photo_name") ?: basename($path)
        );
    }

    private function employeeId(Request $request): string
    {
        $employeeId = $request->user()?->employee?->id;

        if (! $employeeId) {
            throw ValidationException::withMessages([
                'employee' => 'Your user account is not linked to an employee profile.',
            ]);
        }

        return $employeeId;
    }

    private function ensureManualEntriesAllowed(Request $request): void
    {
        if (! $this->settings->get('attendance.manual_entries_enabled', true)) {
            throw new AuthorizationException('Manual attendance management is disabled in App Settings.');
        }
    }
}
