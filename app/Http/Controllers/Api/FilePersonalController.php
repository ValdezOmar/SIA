<?php

namespace App\Http\Controllers\Api;

use App\Models\FilePersonal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\FilePersonalResource;
use App\Http\Resources\FilePersonalCollection;
use App\Http\Requests\FilePersonalStoreRequest;
use App\Http\Requests\FilePersonalUpdateRequest;

class FilePersonalController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', FilePersonal::class);

        $search = $request->get('search', '');

        $filePersonal = FilePersonal::search($search)
            ->latest()
            ->paginate();

        return new FilePersonalCollection($filePersonal);
    }

    /**
     * @param \App\Http\Requests\FilePersonalStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(FilePersonalStoreRequest $request)
    {
        $this->authorize('create', FilePersonal::class);

        $validated = $request->validated();

        $filePersonal = FilePersonal::create($validated);

        return new FilePersonalResource($filePersonal);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, FilePersonal $filePersonal)
    {
        $this->authorize('view', $filePersonal);

        return new FilePersonalResource($filePersonal);
    }

    /**
     * @param \App\Http\Requests\FilePersonalUpdateRequest $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function update(
        FilePersonalUpdateRequest $request,
        FilePersonal $filePersonal
    ) {
        $this->authorize('update', $filePersonal);

        $validated = $request->validated();

        $filePersonal->update($validated);

        return new FilePersonalResource($filePersonal);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, FilePersonal $filePersonal)
    {
        $this->authorize('delete', $filePersonal);

        $filePersonal->delete();

        return response()->noContent();
    }
}
