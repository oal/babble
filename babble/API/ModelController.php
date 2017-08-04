<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Babble\Events\RecordChangeEvent;
use Babble\Models\Model;
use Babble\Models\Record;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ModelController extends Controller
{
    private $model;
    private $dispatcher;

    public function __construct(EventDispatcher $dispatcher, string $modelType)
    {
        $this->model = new Model($modelType);
        $this->dispatcher = $dispatcher;
    }

    public function create(Request $request, $id)
    {
        // If ID is already taken.
        if ($this->model->exists($id)) {
            return new JsonResponse([
                'error' => 'Provided ID is already taken.'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);

        // Save model instance.
        $record = new Record($this->model, $id);
        $record->save($data);
        return new JsonResponse($record);
    }

    public function read(Request $request, $id)
    {
        $loader = new ContentLoader($this->model->getType());

        if (!empty($id)) return $this->readOne($loader, $id);
        return $this->readMany($loader);
    }

    private function readOne(ContentLoader $loader, $id)
    {
        try {
            $record = $loader->find($id);
            return new JsonResponse($record);
        } catch (Exception $e) {
        }

        return new Response(null, 404);
    }

    private function readMany(ContentLoader $loader)
    {
        try {
            $records = $loader->withChildren();
            return new JsonResponse(iterator_to_array($records));
        } catch (Exception $e) {
        }

        return new JsonResponse([]);
    }

    public function update(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);

        // If ID was changed and new ID is already taken.
        $oldId = $data['_old_id'] ?? null;
        if (!empty($oldId) && $oldId !== $id && $this->model->exists($id)) {
            return new JsonResponse([
                'error' => 'Provided ID is already in use.'
            ], 400);
        }

        $loadId = $oldId ?? $id;
        $record = Record::fromDisk($this->model, $loadId);
        $data['id'] = $id;
        $record->save($data);

        // If ID was changed, delete old version.
        if (!empty($oldId) && $oldId !== $id) {
            $deleteInstance = Record::fromDisk($this->model, $oldId);
            $deleteInstance->delete();
        }

        $this->dispatcher->dispatch(
            RecordChangeEvent::NAME,
            new RecordChangeEvent($this->model->getType(), $id)
        );

        // TODO: RecordDeleteEvent when ID is changed?

        return new JsonResponse($record);
    }

    public function delete(Request $request, $id)
    {
        return new JsonResponse([
            'error' => 'Not implemented'
        ], 400);
    }

    public function describe(Request $request)
    {
        return new JsonResponse($this->model);
    }
}