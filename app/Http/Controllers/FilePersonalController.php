<?php

namespace App\Http\Controllers;

use App\Models\FilePersonal;
use Illuminate\Http\Request;
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
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.file_personal.index',
            compact('filePersonal', 'search')
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', FilePersonal::class);

        return view('app.file_personal.create');
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

        return redirect()
            ->route('file-personal.edit', $filePersonal)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, FilePersonal $filePersonal)
    {
        $this->authorize('view', $filePersonal);

        return view('app.file_personal.show', compact('filePersonal'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, FilePersonal $filePersonal)
    {
        $this->authorize('update', $filePersonal);

        return view('app.file_personal.edit', compact('filePersonal'));
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

        return redirect()
            ->route('file-personal.edit', $filePersonal)
            ->withSuccess(__('crud.common.saved'));
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

        return redirect()
            ->route('file-personal.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
