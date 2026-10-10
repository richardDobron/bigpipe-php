<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Models\Post;
use dobron\BigPipe\TransportMarker;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $request->validate(['body' => 'required|string|max:500']);

        $comment = $post->comments()->create([
            'body' => $request->input('body'),
            'user_id' => $request->user()->id,
        ]);
        $comment->load('author');

        return (new AsyncResponse())
            ->prependContent('#comments', view('posts._comment', compact('comment'))->render())
            ->setContent('#error-body', '')
            ->call('Form/Reset', null, [TransportMarker::element('comment-form')])
            ->send();
    }
}
