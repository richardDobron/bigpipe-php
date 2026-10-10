<?php

namespace App\Http\Controllers;

use App\Arch\BigPipe\AsyncResponse;
use App\Arch\BigPipe\DialogResponse;
use App\Models\Post;
use dobron\BigPipe\MorePager;
use dobron\BigPipe\Quickling;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with('author')->latest('id')->simplePaginate(8);

        // The link of the pager, not a page transition, which is also an AJAX request.
        if ($request->ajax() && ! Quickling::isRequested()) {
            return (new AsyncResponse())
                ->appendContent('#posts', view('posts._items', compact('posts'))->render())
                ->replace('', $posts->hasMorePages()
                    ? (string) new MorePager($posts->nextPageUrl(), 'Load more posts')
                    : '')
                ->send();
        }

        return $this->page('posts.index', compact('posts'), 'Blog');
    }

    public function show(Post $post)
    {
        $post->load('author', 'comments.author');

        return $this->page('posts.show', compact('post'), $post->title);
    }

    public function edit(Post $post)
    {
        return $this->page('posts.edit', compact('post'), 'Edit: '.$post->title);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:5000',
        ]);

        $post->update($data);

        return (new AsyncResponse())
            ->setContent('#edit-status', 'Saved at '.now()->format('H:i:s').'.')
            ->setContent('#error-title', '')
            ->setContent('#error-body', '')
            ->send();
    }

    public function deleteDialog(Post $post)
    {
        return (new DialogResponse())
            ->setTitle('Delete "'.e($post->title).'"?')
            ->setBody('<p class="p-4">The post and its comments will be removed.</p>')
            ->setFooter(
                '<form action="'.route('posts.destroy', $post).'" method="POST" rel="async" class="flex gap-2 justify-end">'
                .csrf_field().method_field('DELETE')
                .'<button type="button" data-dismiss="modal" class="px-3 py-2 rounded border border-gray-300">Cancel</button>'
                .'<button type="submit" class="px-3 py-2 rounded bg-red-600 text-white">Delete</button>'
                .'</form>'
            )
            ->setBackdrop('static')
            ->setHideOnSuccess('form')
            ->setPosition(80)
            ->dialog()
            ->send();
    }

    public function destroy(Post $post)
    {
        $post->delete();

        // Back to all posts with a page transition, so that the toast survives it: the dialog can be
        // opened from the post itself, which is gone now.
        return (new AsyncResponse())
            ->call('bigpipe-util/dist/PageTransitions', 'go', [route('posts.index')])
            ->call('Toastr', 'success', ['The post was deleted.'])
            ->send();
    }
}
