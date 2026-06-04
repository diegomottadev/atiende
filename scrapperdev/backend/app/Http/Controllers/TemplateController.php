<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->templates()->orderBy('trigger')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $template = $request->user()->templates()->create([
            'trigger' => $data['trigger'],
            'content' => $data['content'],
            'variables' => $this->extractVariables($data['content']),
        ]);

        return response()->json($template, 201);
    }

    public function update(Request $request, Template $template): JsonResponse
    {
        $this->authorizeOwner($request, $template);
        $data = $this->validateData($request, $template->id);

        $template->update([
            'trigger' => $data['trigger'],
            'content' => $data['content'],
            'variables' => $this->extractVariables($data['content']),
        ]);

        return response()->json($template);
    }

    public function destroy(Request $request, Template $template): JsonResponse
    {
        $this->authorizeOwner($request, $template);
        $template->delete();

        return response()->json(null, 204);
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'trigger' => [
                'required', 'string', 'max:255',
                Rule::unique('templates', 'trigger')
                    ->where('user_id', $request->user()->id)
                    ->ignore($ignoreId),
            ],
            'content' => ['required', 'string'],
        ]);
    }

    private function authorizeOwner(Request $request, Template $template): void
    {
        abort_unless($template->user_id === $request->user()->id, 403);
    }

    private function extractVariables(string $content): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $content, $matches);

        return array_values(array_unique($matches[1]));
    }
}
