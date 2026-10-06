<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TourPurpose;
use App\Models\TourType;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    protected array $types = [
        'tour-purposes' => [
            'model' => TourPurpose::class,
            'label' => 'Tour Purposes',
            'boolean_field' => 'requires_party',
            'boolean_label' => 'Requires Party',
        ],
        'tour-types' => [
            'model' => TourType::class,
            'label' => 'Tour Types',
            'boolean_field' => 'requires_vehicle',
            'boolean_label' => 'Requires Vehicle',
        ],
        'vehicle-types' => [
            'model' => VehicleType::class,
            'label' => 'Vehicle Types',
            'boolean_field' => 'requires_vehicle_no',
            'boolean_label' => 'Requires Vehicle No.',
            'has_rate' => true,
        ],
    ];

    public function index(Request $request, string $type)
    {
        $config = $this->resolveType($type);
        /** @var Model $modelClass */
        $modelClass = $config['model'];
        $records = $modelClass::query()->orderBy('name')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'type' => $type,
                'config' => $config,
                'records' => $records,
                'types' => $this->types,
            ]);
        }

        return view('admin.master-data.index', [
            'type' => $type,
            'config' => $config,
            'records' => $records,
            'types' => $this->types,
        ]);
    }

    public function store(Request $request, string $type)
    {
        $config = $this->resolveType($type);
        $validated = $this->validateRecord($request, $config);

        /** @var Model $modelClass */
        $modelClass = $config['model'];
        $record = $modelClass::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$config['label']} record created successfully.",
                'record' => $record,
            ]);
        }

        return redirect()
            ->route('admin.master-data.index', $type)
            ->with('success', "{$config['label']} record created successfully.");
    }

    public function update(Request $request, string $type, int $id)
    {
        $config = $this->resolveType($type);
        $record = $this->findRecord($config, $id);
        $validated = $this->validateRecord($request, $config, $record->id);

        $record->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$config['label']} record updated successfully.",
                'record' => $record->fresh(),
            ]);
        }

        return redirect()
            ->route('admin.master-data.index', $type)
            ->with('success', "{$config['label']} record updated successfully.");
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $config = $this->resolveType($type);
        $record = $this->findRecord($config, $id);
        $record->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$config['label']} record deleted successfully.",
            ]);
        }

        return redirect()
            ->route('admin.master-data.index', $type)
            ->with('success', "{$config['label']} record deleted successfully.");
    }

    protected function resolveType(string $type): array
    {
        if (! isset($this->types[$type])) {
            abort(404);
        }

        return $this->types[$type];
    }

    protected function findRecord(array $config, int $id): Model
    {
        /** @var Model $modelClass */
        $modelClass = $config['model'];

        return $modelClass::query()->findOrFail($id);
    }

    protected function validateRecord(Request $request, array $config, ?int $ignoreId = null): array
    {
        /** @var Model $modelClass */
        $modelClass = $config['model'];
        $table = (new $modelClass)->getTable();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table, 'name')->ignore($ignoreId),
            ],
            $config['boolean_field'] => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'rate_per_km' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated[$config['boolean_field']] = $request->boolean($config['boolean_field']);
        $validated['is_active'] = $request->boolean('is_active', true);
        
        if (!isset($config['has_rate'])) {
            unset($validated['rate_per_km']);
        }

        return $validated;
    }
}
