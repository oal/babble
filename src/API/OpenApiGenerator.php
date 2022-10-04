<?php


namespace Babble\API;


use Babble\Models\Model;
use Babble\Models\Record;

class OpenApiGenerator
{

    public function json()
    {
        $schema = $this->generate();
        return json_encode($schema, JSON_PRETTY_PRINT);
    }

    private function generate()
    {
        $doc = [
            'openapi' => '3.0.1',
            'info' => $this->getInfo(),
            'paths' => $this->getPaths()
        ];

        return $doc;
    }

    /**
     * @return array
     */
    private function getInfo(): array
    {
        $siteModel = new Model('Site');
        $siteRecord = Record::fromDisk($siteModel);
        $title = $siteRecord->getValue('title');
        return [
            'title' => $title,
            'description' => "API documentation for $title"
        ];
    }

    private function getPaths()
    {
        $models = Model::all();

        $paths = [];

        /** @var Model $model */
        foreach ($models as $model) {
            $modelType = $model->getType();
            $modelName = $model->getName();
            $verb = $model->isSingle() ? 'Get' : 'List';
            $paths["/api/$modelType"] = [
                'get' => [
                    'summary' => "$verb $modelName",
                    'responses' => [
                        '200' => [
                            'description' => 'Successful operation',
                            'content' => [
                                'application/json' => [
                                    'schema' => $model->jsonSchema()
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $paths;
    }
}