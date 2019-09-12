<?php

namespace Babble\API;

use Babble\Content\ContentLoader;
use Babble\Events\RecordChangeEvent;
use Babble\Exceptions\RecordNotFoundException;
use Babble\Models\Model;
use Babble\Models\Record;
use Exception;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
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
        // If ID is already taken (assuming not a single instance model).
        if (!$this->model->isSingle() && $this->model->exists($id)) {
            return new JsonResponse([
                'error' => 'Provided ID is already taken.'
            ], 400);
        }

        $data = $this->getContentFromRequest($request);

        $validator = $this->validate($id, $data);
        if (!$validator->isValid()) {
            return new JsonResponse([
                'errors' => $validator->getErrors()
            ], 419);
        }

        // Save model instance.
        $record = new Record($this->model, $id);
        $record->save($data);

        $this->dispatcher->dispatch(
            RecordChangeEvent::NAME,
            new RecordChangeEvent($this->model->getType(), $id)
        );

        return new JsonResponse($record);
    }

    public function read(Request $request, $id)
    {
        // Single instance models do not have IDs.
        if ($this->model->isSingle()) {
            if ($id) return new Response(null, 404);
            try {
                $record = Record::fromDisk($this->model);
            } catch (RecordNotFoundException $e) {
                $record = new Record($this->model);
            }
            return new JsonResponse($record);
        }

        // Multi instance models.
        $loader = new ContentLoader($this->model);
        if (!empty($id)) return $this->readOne($loader, $id);

        $sort = $request->get('sort');
        if ($sort) {
            $sortDirection = 'asc';
            if (substr($sort, 0, 1) === '-') {
                $sortDirection = 'desc';
                $sort = substr($sort, 1);
            }
            $loader = $loader->orderBy($sort, $sortDirection);
        }
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
            return new JsonResponse(array_values(iterator_to_array($records)));
        } catch (Exception $e) {
        }

        return new JsonResponse([]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->getContentFromRequest($request);

        $validator = $this->validate($id, $data);
        if (!$validator->isValid()) {
            return new JsonResponse([
                'errors' => $validator->getErrors()
            ], 419);
        }

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
        try {
            $deleteInstance = Record::fromDisk($this->model, $id);
            $deleteInstance->delete();
            return new JsonResponse([
                'message' => 'Delete successful.'
            ]);
        } catch (RecordNotFoundException $e) {
            return new JsonResponse([
                'error' => 'Not found.'
            ], 403);
        }
    }

    /**
     * Called on OPTIONS requests to this model.
     * @param Request $request
     * @return JsonResponse
     */
    public function describe(Request $request)
    {
        return new JsonResponse([
            'model' => $this->model,
            'blocks' => $this->model->getBlocks()
        ]);
    }

    /**
     * Returns an array of data from the request body and omits null values.
     *
     * @param Request $request
     * @return array
     */
    private function getContentFromRequest(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Calls jsonSchema() on the model and checks the provided data against the model's schema.
     *
     * @param $id
     * @param $data
     * @return Validator
     */
    private function validate($id, $data): \JsonSchema\Validator
    {
        $modelData = [
            'id' => $id,
            'fields' => $data
        ];

        $validator = new \JsonSchema\Validator();
        $validator->validate($modelData, $this->model->jsonSchema(), Constraint::CHECK_MODE_TYPE_CAST|Constraint::CHECK_MODE_COERCE_TYPES);

        return $validator;
    }
}