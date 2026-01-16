<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Book;
use App\Models\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(): JsonResponse{
        $books = Auth::user()->books()->select('id', 'title')->get();

        return response()->json($books);
    }

    public function store(Request $request): JsonResponse{
        $request->validate([
            'title' => 'required|string|max:255',
            'book_text' => 'nullable|string',
            'file' => 'nullable|file|mimes:txt|max:2048',
        ]);

        $content = $request->book_text;

        if($request->hasFile('file')){
            $content = file_get_contents($request->file('file')->getRealPath());
        }

        if(empty($content)){
            return response()->json([
                'message' => 'Нужно указать текст книги или загрузить файл текста',
            ], 422);
        }

        $book = Auth::user()->books()->create([
            'title' => $request->title,
            'book_text' => $content,
            'author' => $request->author ?? null,
            'publishing' => $request->publishing ?? null,
        ]);

        return response()->json([
            'success' => true,
            'id' => $book->id,
            'message' => 'Книга успешно создана'
        ], 201);

    }

    public function show($id): JsonResponse{
        $book = Book::find($id);

        if(!$book){
            return response()->json([
                'message' => 'Книга не найдена'
            ], 404);
        }

        $isOwner = $book->user_id === Auth::id();

        $hasAccess = UserAccess::where('owner_id', $book->user_id)
                ->where('guest_id', Auth::id())
                ->exists();

        if (!$isOwner && !$hasAccess){
            return response()->json([
                'message' => 'Вы не имеете доступа к этой книге'
            ], 403);
        }

        return response()->json([
            'id' => $book->id,
            'title' => $book->title,
            'book_text' => $book->book_text,
        ]);
    }

    public function update(Request $request, $id): JsonResponse{
        $book = Auth::user()->books()->find($id);

        if(!$book){
            return response()->json([
                'message' => 'Книга не найдена или вы ей не владеете'
            ], 404);
        }

        $request->validate([
            'title' => 'string|max:255',
            'book_text' => 'string',
        ]);

        $book->update($request->only(['title', 'book_text']));

        return response()->json([
            'message' => 'Книга успешно обновлена',
            'book' => $book
        ]);
    }

    public function destroy($id): JsonResponse{
        $book = Auth::user()->books()->find($id);

        if(!$book){
            return response()->json([
                'message' => 'Книга не найдена или вы ей не владеете'
            ], 404);
        }

        $book->delete();

        return response()->json([
            'message' => 'Книга успешно удалена'
        ]);
    }

    public function restore($id): JsonResponse{
        $book = Auth::user()->books()->onlyTrashed()->find($id);

        if(!$book){
            return response()->json([
                'message' => 'Удаленная книга не была найдена'
            ], 404);
        }

        $book -> restore();

        return response()->json([
            'message' => 'Книга успешно восстановлена'
        ]);
    }

    public function userBooks($userId): JsonResponse{
        $hasAccess = UserAccess::where('owner_id', $userId)
                ->where('guest_id', Auth::id())
                ->exists();


        if(!$hasAccess){
            return response()->json([
                'message' => 'Нет доступа к библиотеке этого пользователя'
            ], 403);
        }

        $books = Book::where('user_id', $userId)->select('id', 'title')->get();

        return response()->json(
            $books
        );
    }

    public function searchExternal(Request $request): JsonResponse{
        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => $request->input('query'),
        ]);

        if($response->failed()){
            return response()->json([
                'message' => 'Ошибка при обращении к Google Api'
            ], 502);
        }

        $data = $response->json();
        $items = $data['items'] ?? [];

        $results = collect($items)->map(function($item){
            $info = $item['volumeInfo'] ?? [];

            return [
                'google_id' => $item['id'],
                'title' => $info['title'] ?? 'Без названия',
                'authors' => $info['authors'] ?? [],
                'url' => $info['previewLink'] ?? ($info['infoLink'] ?? null),
            ];
        });

        return response()->json($results);
    }

    public function saveExternal(Request $request): JsonResponse{
        $request->validate([
            'google_id' => 'required|string',
        ]);

        $googleId = $request->google_id;
        $response = Http::get("https://www.googleapis.com/books/v1/volumes/{$googleId}");

        if($response->failed()){
            return response()->json([
                'message' => 'Книга не найдена в Google Api'
            ], 404);
        }

        $bookData = $response->json();
        $info = $bookData['volumeInfo'] ?? [];

        $description = $info['description'] ?? null;
        $link = $info['previewLink'] ?? ($info['infoLink'] ?? null);

        $bookText = $description ?: ($link ?: 'Описание отсутствует');

        $book = Auth::user()->books()->create([
            'title' => $info['title'] ?? 'Без названия',
            'author' => implode(', ', $info['authors'] ?? []),
            'publishing' => $info['publisher'] ?? null,
            'book_text' => $bookText,
        ]);

        return response()->json([
            'message' => 'Книга успешно сохранена из Google',
            'book' => $book
        ], 201);
    }
}
