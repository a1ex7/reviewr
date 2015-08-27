<?php

namespace App\Repositories;

use App\Tag;
use App\Repositories\Interfaces\TagRepositoryInterface;

class TagRepository implements TagRepositoryInterface
{
    public function all()
    {
        return Tag::orderBy('title')->get();
    }

    public function create($data)
    {
        $tags = Tag::all();
        foreach($tags as $temp) {
            if ($data == $temp->title) return $temp;
        }
        $tag = new Tag();
        $tag->title = $data;
        $tag->save();
        return $tag;
    }

    public function OneById($id) {}

    public function update($id, $data) {}

    public function delete($id)
    {
        return Tag::findOrFail($id)->delete();
    }

    public function searchByKeyWord($keyword)
    {
        return Tag::where('title', 'like', '%'.$keyword.'%')->get();
    }

    public function getPopular()
    {
        $tags = $this->all();
        $tags_sorted = $tags->sortByDesc('requests_count');
        return $tags_sorted->values()->all();
    }
}