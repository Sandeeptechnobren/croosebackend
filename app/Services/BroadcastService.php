<?php

namespace App\Services;

use App\Models\BroadcastHeader;

class BroadcastService
{
    public function getAll()
    {
        return BroadcastHeader::latest()->get();
    }

    public function getById($id)
    {
        return BroadcastHeader::findOrFail($id);
    }

    public function create(array $data)
    {
        $data['created_by'] = auth()->id();
        return BroadcastHeader::create($data);
    }

    public function update($id, array $data)
    {
        $broadcast = BroadcastHeader::findOrFail($id);
        $data['updated_by'] = auth()->id();
        $broadcast->update($data);
        return $broadcast;
    }

    public function delete($id)
    {
        $broadcast = BroadcastHeader::findOrFail($id);
        $broadcast->deleted_by = auth()->id();
        $broadcast->save();
        return $broadcast->delete();
    }
}
