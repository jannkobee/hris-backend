<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatutoryRuleRequest;
use App\Models\StatutoryRule;
use App\Services\Utils\ResponseServiceInterface;
use Illuminate\Validation\ValidationException;

class StatutoryRuleController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
        $this->middleware('permission:manage-payroll-settings');
    }

    public function index()
    {
        return $this->response->successResponse(
            'Statutory rules',
            StatutoryRule::query()->latest('effective_from')->get()
        );
    }

    public function store(StatutoryRuleRequest $request)
    {
        $data = $request->validated();
        $this->ensureNoOverlap($data);
        $rule = StatutoryRule::create($data);

        return $this->response->storeResponse('Statutory rule', $rule);
    }

    public function update(StatutoryRuleRequest $request, StatutoryRule $statutoryRule)
    {
        $data = $request->validated();
        $this->ensureNoOverlap($data, $statutoryRule);
        $statutoryRule->update($data);

        return $this->response->updateResponse('Statutory rule', $statutoryRule->fresh());
    }

    public function destroy(StatutoryRule $statutoryRule)
    {
        $statutoryRule->delete();

        return $this->response->deleteResponse('Statutory rule', true);
    }

    private function ensureNoOverlap(array $data, StatutoryRule $except = null): void
    {
        $query = StatutoryRule::query()
            ->where('country_code', strtoupper($data['country_code']))
            ->whereDate('effective_from', '<=', $data['effective_until'] ?? '9999-12-31')
            ->where(function ($query) use ($data): void {
                $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $data['effective_from']);
            });

        if ($except) {
            $query->whereKeyNot($except->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'This effective period overlaps an existing statutory rule for the country.',
            ]);
        }
    }
}
