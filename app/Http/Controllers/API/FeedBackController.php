<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeedBackManagement;

class FeedBackController extends Controller
{
    // list
    public function index()
    {
        return FeedBackManagement::latest()->get();
    }

    // store
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|max:2048',
            'origin' => 'nullable|string',
            'type' => 'nullable|string',
            'priority' => 'nullable|string',
            'severity' => 'nullable|string',
            'tag' => 'nullable|string',
            'status' => 'nullable|string',
            'estimation' => 'nullable|numeric'
        ]);

        $data = $request->all();

        // file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment')->store('feedback', 'public');
            $data['attachment'] = $file;
        }

        $feedback = FeedBackManagement::create($data);

        return response()->json([
            'message' => 'Created',
            'data' => $feedback
        ]);
    }

    // show
    public function show($id)
    {
        return FeedBackManagement::findOrFail($id);
    }

    // update
    public function update(Request $request, $id)
    {
        $feedback = FeedBackManagement::findOrFail($id);

        $data = $request->all();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment')->store('feedback', 'public');
            $data['attachment'] = $file;
        }

        $feedback->update($data);

        return response()->json([
            'message' => 'Updated',
            'data' => $feedback
        ]);
    }

    // delete
    public function destroy($id)
    {
        $feedback = FeedBackManagement::findOrFail($id);
        $feedback->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
